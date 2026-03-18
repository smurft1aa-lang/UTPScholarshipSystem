<?php
use PHPUnit\Framework\TestCase;

if (!defined('APP_ENV'))
    define('APP_ENV', 'testing');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/ai_engine.php';

/**
 * AIEngine Scoring Accuracy Tests
 * Tests exact scoring calculations against worked examples from ALGORITHM.md
 */
class AIEngineTest extends TestCase
{
    protected function setUp(): void
    {
        $db = getDB();
        // Clean up previous test data
        $db->exec("DELETE FROM grades WHERE qualification_id IN (SELECT id FROM qualifications WHERE user_id = 9000)");
        $db->exec("DELETE FROM qualifications WHERE user_id = 9000");
        $db->exec("DELETE FROM users WHERE id = 9000");

        // Create test student
        $hash = password_hash('Test@1234', PASSWORD_BCRYPT);
        $db->exec("INSERT INTO users (id, full_name, email, password_hash, ic_number, phone, role, email_verified) VALUES (9000, 'AI Test Student', 'aitest@test.com', '$hash', '900000000000', '0100000000', 'student', 1)");
    }

    protected function tearDown(): void
    {
        $db = getDB();
        $db->exec("DELETE FROM grades WHERE qualification_id IN (SELECT id FROM qualifications WHERE user_id = 9000)");
        $db->exec("DELETE FROM qualifications WHERE user_id = 9000");
        $db->exec("DELETE FROM users WHERE id = 9000");
    }

    /**
     * Helper: Insert qualification + grades for test student
     */
    private function createQualificationWithGrades(string $qualType, array $grades): int
    {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO qualifications (user_id, qual_type) VALUES (9000, ?)");
        $stmt->execute([$qualType]);
        $qualId = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO grades (qualification_id, subject, grade) VALUES (?, ?, ?)");
        foreach ($grades as $subject => $grade) {
            $stmt->execute([$qualId, $subject, $grade]);
        }

        return $qualId;
    }

    public function test_all_A_plus_gives_100_percent_fit()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'A+',
            'English' => 'A+',
            'Mathematics' => 'A+',
            'Additional Mathematics' => 'A+',
            'Physics' => 'A+',
            'Chemistry' => 'A+',
        ]);

        $results = AIEngine::checkEligibility($qualId);
        $this->assertNotEmpty($results);

        // Find Chemical Engineering (programme_id 2)
        $chemEng = null;
        foreach ($results as $r) {
            if (stripos($r['programme_name'], 'Chemical Engineering') !== false) {
                $chemEng = $r;
                break;
            }
        }
        $this->assertNotNull($chemEng, 'Chemical Engineering should appear in results');
        $this->assertTrue($chemEng['eligible']);
        $this->assertEquals(100.0, $chemEng['fit_percentage']);
        $this->assertEquals('Excellent Match', $chemEng['confidence_label']);
    }

    public function test_all_C_grades_gives_minimum_eligible()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'C',
            'English' => 'C',
            'Mathematics' => 'C',
            'Additional Mathematics' => 'C',
            'Physics' => 'C',
            'Chemistry' => 'C',
        ]);

        $results = AIEngine::checkEligibility($qualId);
        $this->assertNotEmpty($results);

        // Student should be eligible for engineering programmes (C is minimum)
        $engResult = null;
        foreach ($results as $r) {
            if (stripos($r['programme_name'], 'Mechanical Engineering') !== false) {
                $engResult = $r;
                break;
            }
        }
        $this->assertNotNull($engResult);
        $this->assertTrue($engResult['eligible']);
        // C = 3 points, so fit = (3*1 + 3*1 + 3*1 + 3*1 + 3*0.9 + 3*0.8) / (10*1+10*1+10*1+10*1+10*0.9+10*0.8)
        // = (3 + 3 + 3 + 3 + 2.7 + 2.4) / (10+10+10+10+9+8) = 17.1 / 57 = 30%
        $this->assertEquals(30.0, $engResult['fit_percentage']);
        $this->assertEquals('Not Recommended', $engResult['confidence_label']);
    }

    public function test_missing_required_subject_is_ineligible()
    {
        // Missing Chemistry for engineering
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'A+',
            'English' => 'A+',
            'Mathematics' => 'A+',
            'Additional Mathematics' => 'A+',
            'Physics' => 'A+',
            // No Chemistry
        ]);

        $results = AIEngine::checkEligibility($qualId);
        $chemEng = null;
        foreach ($results as $r) {
            if (stripos($r['programme_name'], 'Chemical Engineering') !== false) {
                $chemEng = $r;
                break;
            }
        }
        $this->assertNotNull($chemEng);
        $this->assertFalse($chemEng['eligible']);
        $this->assertNotEmpty($chemEng['gaps']);

        // Should have a gap for Chemistry
        $chemGap = false;
        foreach ($chemEng['gaps'] as $g) {
            if (stripos($g['subject'], 'Chemistry') !== false) {
                $chemGap = true;
            }
        }
        $this->assertTrue($chemGap, 'Should identify Chemistry as a gap');
    }

    public function test_below_minimum_grade_is_ineligible()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'A',
            'English' => 'A',
            'Mathematics' => 'A',
            'Additional Mathematics' => 'A',
            'Physics' => 'A',
            'Chemistry' => 'D',  // Below C minimum
        ]);

        $results = AIEngine::checkEligibility($qualId);
        $mechEng = null;
        foreach ($results as $r) {
            if (stripos($r['programme_name'], 'Mechanical Engineering') !== false) {
                $mechEng = $r;
                break;
            }
        }
        $this->assertNotNull($mechEng);
        $this->assertFalse($mechEng['eligible']);
    }

    public function test_eligible_programmes_sorted_first()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'B+',
            'English' => 'A',
            'Mathematics' => 'A',
            'Additional Mathematics' => 'A',
            'Physics' => 'A',
            'Chemistry' => 'A',
        ]);

        $results = AIEngine::checkEligibility($qualId);
        $this->assertNotEmpty($results);

        // All eligible results should come before ineligible
        $foundIneligible = false;
        foreach ($results as $r) {
            if (!$r['eligible']) {
                $foundIneligible = true;
            }
            if ($foundIneligible && $r['eligible']) {
                $this->fail('Eligible programme found after ineligible — sorting is wrong');
            }
        }
    }

    public function test_scholarship_matching_returns_results_for_eligible_students()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'A+',
            'English' => 'A+',
            'Mathematics' => 'A+',
            'Additional Mathematics' => 'A+',
            'Physics' => 'A+',
            'Chemistry' => 'A+',
        ]);

        $results = AIEngine::checkEligibility($qualId);
        $eligibleIds = [];
        $fitMap = [];
        foreach ($results as $r) {
            if ($r['eligible']) {
                $eligibleIds[] = $r['programme_id'];
                $fitMap[$r['programme_id']] = $r['fit_percentage'];
            }
        }

        $this->assertNotEmpty($eligibleIds, 'Should have eligible programmes');

        $scholarships = AIEngine::getMatchingScholarships($eligibleIds, $fitMap);
        $this->assertNotEmpty($scholarships, 'A+ student should match at least some scholarships');
    }

    public function test_confidence_labels_are_correct()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'A',
            'English' => 'A',
            'Mathematics' => 'A',
            'Additional Mathematics' => 'A',
            'Physics' => 'A',
            'Chemistry' => 'A',
        ]);

        $results = AIEngine::checkEligibility($qualId);
        foreach ($results as $r) {
            $pct = $r['fit_percentage'];
            if ($pct >= 90) {
                $this->assertEquals('Excellent Match', $r['confidence_label']);
            } elseif ($pct >= 75) {
                $this->assertEquals('Strong Match', $r['confidence_label']);
            } elseif ($pct >= 60) {
                $this->assertEquals('Good Match', $r['confidence_label']);
            } elseif ($pct >= 40) {
                $this->assertEquals('Possible Match', $r['confidence_label']);
            } else {
                $this->assertEquals('Not Recommended', $r['confidence_label']);
            }
        }
    }

    public function test_recommendation_text_is_generated()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'A+',
            'English' => 'A+',
            'Mathematics' => 'A+',
            'Additional Mathematics' => 'A+',
            'Physics' => 'A+',
            'Chemistry' => 'A+',
        ]);

        $results = AIEngine::checkEligibility($qualId);
        foreach ($results as $r) {
            $this->assertNotEmpty($r['recommendation'], 'Each result should have a recommendation');
            $this->assertIsString($r['recommendation']);
        }
    }

    public function test_empty_scholarship_list_returns_empty()
    {
        $result = AIEngine::getMatchingScholarships([], []);
        $this->assertEmpty($result);
    }
}
