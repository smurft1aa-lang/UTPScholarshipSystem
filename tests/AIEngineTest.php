<?php

use PHPUnit\Framework\TestCase;
use UTP\Services\AIEngine;

if (!defined('APP_ENV')) {
    define('APP_ENV', 'testing');
}
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
/**
 * AIEngine Scoring Accuracy Tests (OOP)
 *
 * Tests the namespaced UTP\Services\AIEngine class to ensure
 * full code coverage on the OOP implementation.
 */
class AIEngineTest extends TestCase
{
    private \PDO $db;
    private AIEngine $engine;
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $this->db = getDB();
        $this->engine = new AIEngine($this->db);
        $this->db->beginTransaction();
        $this->db->exec("DELETE FROM entry_requirements");
        $this->db->exec("INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
            (1, 'SPM', 'Mathematics', 'C', 1.00),
            (1, 'SPM', 'Physics', 'C', 1.00),
            (2, 'SPM', 'Mathematics', 'C', 1.00),
            (2, 'SPM', 'Chemistry', 'C', 1.00)
        ");
        $this->db->exec("DELETE FROM users WHERE id = 9000");
        $hash = password_hash('Test@1234', PASSWORD_BCRYPT);
        $this->db->exec("INSERT INTO users (id, full_name, email, password_hash, ic_number, phone, role, email_verified) VALUES (9000, 'AI Test Student', 'aitest@test.com', '$hash', '900000000000', '0100000000', 'student', 1)");
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    private function createQualificationWithGrades(string $qualType, array $grades): int
    {
        $stmt = $this->db->prepare("INSERT INTO qualifications (user_id, qual_type) VALUES (9000, ?)");
        $stmt->execute([$qualType]);
        $qualId = $this->db->lastInsertId();
        $stmt = $this->db->prepare("INSERT INTO grades (qualification_id, subject, grade) VALUES (?, ?, ?)");
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
        $results = $this->engine->checkEligibility($qualId);
        $this->assertNotEmpty($results);
        $mechEng = null;
        foreach ($results as $r) {
            if (stripos($r['programme_name'], 'Mechanical Engineering') !== false) {
                $mechEng = $r;
                break;
            }
        }
        $this->assertNotNull($mechEng, 'Mechanical Engineering should appear in results');
        $this->assertTrue($mechEng['eligible']);
        $this->assertEquals(100.0, $mechEng['fit_percentage']);
        $this->assertEquals('Excellent Match', $mechEng['confidence_label']);
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
        $results = $this->engine->checkEligibility($qualId);
        $this->assertNotEmpty($results);
        $mechEng = null;
        foreach ($results as $r) {
            if (stripos($r['programme_name'], 'Mechanical Engineering') !== false) {
                $mechEng = $r;
                break;
            }
        }
        $this->assertNotNull($mechEng);
        $this->assertTrue($mechEng['eligible']);
        $this->assertEquals(30.0, $mechEng['fit_percentage']);
        $this->assertEquals('Not Recommended', $mechEng['confidence_label']);
    }

    public function test_below_minimum_grade_is_ineligible()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'A+',
            'English' => 'A+',
            'Mathematics' => 'D',
            'Additional Mathematics' => 'C',
            'Physics' => 'A+',
            'Chemistry' => 'C',
        ]);
        $results = $this->engine->checkEligibility($qualId);
        $mechEng = null;
        foreach ($results as $r) {
            if (stripos($r['programme_name'], 'Mechanical Engineering') !== false) {
                $mechEng = $r;
                break;
            }
        }
        $this->assertNotNull($mechEng);
        $this->assertFalse($mechEng['eligible']);
        $mathGap = false;
        foreach ($mechEng['gaps'] as $g) {
            if (stripos($g['subject'], 'Mathematics') !== false) {
                $mathGap = true;
            }
        }
        $this->assertTrue($mathGap, 'Should identify missing Mathematics grade');
    }

    public function test_missing_required_subject_is_ineligible()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'A+',
            'English' => 'A+',
            'Mathematics' => 'A+',
            'Additional Mathematics' => 'A+',
            'Physics' => 'A+',
        ]);
        $results = $this->engine->checkEligibility($qualId);
        $mechEng = null;
        foreach ($results as $r) {
            if (stripos($r['programme_name'], 'Mechanical Engineering') !== false) {
                $mechEng = $r;
                break;
            }
        }
        $this->assertNotNull($mechEng);
        $this->assertFalse($mechEng['eligible']);
        $this->assertNotEmpty($mechEng['gaps']);
        $chemGap = false;
        foreach ($mechEng['gaps'] as $g) {
            if (stripos($g['subject'], 'Chemistry') !== false) {
                $chemGap = true;
            }
        }
        $this->assertTrue($chemGap, 'Should identify missing Chemistry subject');
    }

    public function test_below_minimum_grade_is_ineligible_for_chemistry()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Bahasa Melayu' => 'A',
            'English' => 'A',
            'Mathematics' => 'A',
            'Additional Mathematics' => 'A',
            'Physics' => 'A',
            'Chemistry' => 'D',
        ]);
        $results = $this->engine->checkEligibility($qualId);
        $mechEng = null;
        foreach ($results as $r) {
            if (stripos($r['programme_name'], 'Mechanical Engineering') !== false) {
                $mechEng = $r;
                break;
            }
        }
        $this->assertNotNull($mechEng);
        $this->assertFalse($mechEng['eligible']);
        $chemGap = false;
        foreach ($mechEng['gaps'] as $g) {
            if (stripos($g['subject'], 'Chemistry') !== false) {
                $chemGap = true;
            }
        }
        $this->assertTrue($chemGap, 'Should identify missing Chemistry grade');
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
        $results = $this->engine->checkEligibility($qualId);
        $this->assertNotEmpty($results);
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
        $results = $this->engine->checkEligibility($qualId);
        $eligibleIds = [];
        $fitMap = [];
        foreach ($results as $r) {
            if ($r['eligible']) {
                $eligibleIds[] = $r['programme_id'];
                $fitMap[$r['programme_id']] = $r['fit_percentage'];
            }
        }

        $this->assertNotEmpty($eligibleIds, 'Should have eligible programmes');
        $scholarships = $this->engine->getMatchingScholarships($eligibleIds, $fitMap);
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
        $results = $this->engine->checkEligibility($qualId);
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
        $results = $this->engine->checkEligibility($qualId);
        foreach ($results as $r) {
            $this->assertNotEmpty($r['recommendation'], 'Each result should have a recommendation');
            $this->assertIsString($r['recommendation']);
        }
    }

    public function test_empty_scholarship_list_returns_empty()
    {
        $result = $this->engine->getMatchingScholarships([], []);
        $this->assertEmpty($result);
    }

    public function test_invalid_qualification_returns_empty()
    {
        $results = $this->engine->checkEligibility(99999);
        $this->assertEmpty($results);
    }

    public function test_no_matching_qual_type_returns_empty()
    {
        $qualId = $this->createQualificationWithGrades('STPM', [
            'Mathematics' => 'A',
        ]);
        $results = $this->engine->checkEligibility($qualId);
        $this->assertEmpty($results);
    }

    public function test_results_are_cached_in_session()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Mathematics' => 'A',
            'Physics' => 'A',
        ]);
        $results1 = $this->engine->checkEligibility($qualId);
        $results2 = $this->engine->checkEligibility($qualId);
        $this->assertEquals($results1, $results2);
        $userId = $_SESSION['user_id'] ?? 0;
        $this->assertArrayHasKey('eligibility_' . $userId . '_' . $qualId, $_SESSION);
    }

    public function test_force_refresh_bypasses_cache()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Mathematics' => 'A',
            'Physics' => 'A',
        ]);
        $results1 = $this->engine->checkEligibility($qualId);
        $results2 = $this->engine->checkEligibility($qualId, true);
        $this->assertEquals($results1, $results2);
    }

    public function test_result_contains_all_expected_keys()
    {
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Mathematics' => 'B+',
            'Physics' => 'B+',
        ]);
        $results = $this->engine->checkEligibility($qualId);
        $this->assertNotEmpty($results);
        $expected = [
            'programme_id',
            'programme_name',
            'category',
            'description',
            'eligible',
            'fit_percentage',
            'confidence_label',
            'subject_results',
            'gaps',
            'recommendation'
        ];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $results[0], "Missing key: $key");
        }
    }

    public function test_scholarship_below_threshold_returns_empty()
    {
        $scholarships = $this->engine->getMatchingScholarships([1], [1 => 30.0]);
        $this->assertEmpty($scholarships);
    }

    public function test_scholarship_above_threshold_returns_results()
    {
        $scholarships = $this->engine->getMatchingScholarships([1], [1 => 90.0]);
        $this->assertNotEmpty($scholarships);
        $this->assertEquals(90.0, $scholarships[0]['best_fit']);
    }

    public function test_good_match_recommendation_text()
    {
        // Eligible with moderate grades → "Good match" recommendation branch
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Mathematics' => 'B',
            'Physics' => 'B',
        ]);
        $results = $this->engine->checkEligibility($qualId);
        $csResult = null;
        foreach ($results as $r) {
            if ($r['programme_name'] === 'Computer Science') {
                $csResult = $r;
                break;
            }
        }
        $this->assertNotNull($csResult);
        $this->assertTrue($csResult['eligible']);
        $this->assertStringContainsString($csResult['programme_name'], $csResult['recommendation']);
    }

    public function test_close_to_qualifying_recommendation_text()
    {
        // Ineligible but high fit → "close to qualifying" branch
        $qualId = $this->createQualificationWithGrades('SPM', [
            'Mathematics' => 'A+',
            'Physics' => 'D',  // Below minimum
        ]);
        $results = $this->engine->checkEligibility($qualId);
        $csResult = null;
        foreach ($results as $r) {
            if ($r['programme_name'] === 'Computer Science') {
                $csResult = $r;
                break;
            }
        }
        $this->assertNotNull($csResult);
        $this->assertFalse($csResult['eligible']);
        $this->assertNotEmpty($csResult['recommendation']);
    }

    public function test_pdo_exception_causes_runtime_exception()
    {
        $mockPdo = $this->createMock(\PDO::class);
        $mockPdo->method('prepare')
            ->willThrowException(new \PDOException('Connection lost'));
        $engine = new AIEngine($mockPdo);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Eligibility check failed/');
        $engine->checkEligibility(1);
    }
}
