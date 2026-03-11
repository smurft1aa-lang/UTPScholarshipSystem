<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/ai_engine.php';

class EligibilityTest extends TestCase {

    protected function setUp(): void {
        $db = getDB();
        $db->exec("DELETE FROM grades");
        $db->exec("DELETE FROM qualifications WHERE id > 10");
        $db->exec("DELETE FROM entry_requirements");
        
        // Create baseline programme requirements
        $db->exec("INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES 
            (1, 'SPM', 'Mathematics', 'C', 1.00),
            (1, 'SPM', 'Physics', 'C', 1.00)
        ");
    }

    protected function createQualAndGrades($gradesMap) {
        $db = getDB();
        $db->exec("INSERT INTO qualifications (user_id, qual_type) VALUES (2, 'SPM')");
        $qualId = $db->lastInsertId();
        
        $stmt = $db->prepare("INSERT INTO grades (qualification_id, subject, grade) VALUES (?, ?, ?)");
        foreach ($gradesMap as $subj => $grade) {
            $stmt->execute([$qualId, $subj, $grade]);
        }
        return $qualId;
    }

    public function test_student_eligible_if_all_subjects_meet_minimum() {
        $qualId = $this->createQualAndGrades(['Mathematics' => 'C', 'Physics' => 'B']);
        $results = AIEngine::checkEligibility($qualId);
        
        // Extract results for programme 1
        $prog1 = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($prog1);
        
        $this->assertTrue($prog1['eligible']);
    }

    public function test_student_ineligible_if_one_subject_below_minimum() {
        $qualId = $this->createQualAndGrades(['Mathematics' => 'D', 'Physics' => 'A']);
        $results = AIEngine::checkEligibility($qualId);
        
        $prog1 = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($prog1);
        
        $this->assertFalse($prog1['eligible']);
    }

    public function test_fit_percentage_is_100_for_all_A_plus() {
        $qualId = $this->createQualAndGrades(['Mathematics' => 'A+', 'Physics' => 'A+']);
        $results = AIEngine::checkEligibility($qualId);
        
        $prog1 = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($prog1);
        
        $this->assertEquals(100.0, $prog1['fit_percentage']);
    }

    public function test_fit_percentage_is_0_for_all_F() {
        $qualId = $this->createQualAndGrades(['Mathematics' => 'F', 'Physics' => 'F']);
        $results = AIEngine::checkEligibility($qualId);
        
        $prog1 = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($prog1);
        
        $this->assertEquals(0.0, $prog1['fit_percentage']);
    }

    public function test_gap_analysis_lists_missing_subjects() {
        $qualId = $this->createQualAndGrades(['Mathematics' => 'A']); // Physics missing
        $results = AIEngine::checkEligibility($qualId);
        
        $prog1 = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($prog1);
        
        $this->assertFalse($prog1['eligible']);
        $this->assertCount(1, $prog1['gaps']);
        $this->assertEquals('Physics', $prog1['gaps'][0]['subject']);
    }

    public function test_other_subject_placeholder_gives_partial_credit() {
        $db = getDB();
        $db->exec("INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES (1, 'SPM', 'Other Subject', 'C', 0.8)");
        
        $qualId = $this->createQualAndGrades(['Mathematics' => 'A', 'Physics' => 'A']);
        $results = AIEngine::checkEligibility($qualId);
        
        $prog1 = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($prog1);
        
        // Total max points = 10(Math) + 10(Phys) + 8(Other) = 28
        // Total act points = 9(A) + 9(A) + (3 * 0.8)=2.4 (partial credit for Other) = 20.4
        $expectedFit = round((20.4 / 28.0) * 100, 1);
        
        $this->assertTrue($prog1['eligible']);
        $this->assertEquals($expectedFit, $prog1['fit_percentage']);
    }

    public function test_scholarship_not_matched_below_min_fit_percentage() {
        $scholarships = AIEngine::getMatchingScholarships([1], [1 => 40.0]); // Prog 1, Fit 40
        $this->assertEmpty($scholarships); // Test Scholarship requires 70
    }

    public function test_scholarship_matched_at_exact_min_fit_percentage() {
        $scholarships = AIEngine::getMatchingScholarships([1], [1 => 70.0]); // Prog 1, Fit 70
        $this->assertCount(1, $scholarships);
        $this->assertEquals('Test Scholarship', $scholarships[0]['name']);
    }

    public function test_confidence_label_excellent_at_90_percent() {
        $qualId = $this->createQualAndGrades(['Mathematics' => 'A+', 'Physics' => 'A']); // Fit 95%
        $results = AIEngine::checkEligibility($qualId);
        
        $prog1 = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($prog1);
        
        $this->assertEquals('Excellent Match', $prog1['confidence_label']);
    }

    public function test_confidence_label_not_recommended_below_40() {
        $qualId = $this->createQualAndGrades(['Mathematics' => 'D', 'Physics' => 'E']); // Fit 15%
        $results = AIEngine::checkEligibility($qualId);
        
        $prog1 = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($prog1);
        
        $this->assertEquals('Not Recommended', $prog1['confidence_label']);
    }

    public function test_confidence_label_possible_match_at_exactly_40_percent() {
        // C+ = 4. 4*2 = 8. 8/20 = 40%
        $qualId = $this->createQualAndGrades(['Mathematics' => 'C+', 'Physics' => 'C+']);
        $results = AIEngine::checkEligibility($qualId);
        $filtered = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($filtered);
        
        $this->assertEquals(40.0, $prog1['fit_percentage']);
        $this->assertEquals('Possible Match', $prog1['confidence_label']);
    }

    public function test_confidence_label_good_match_at_exactly_60_percent() {
        // B = 6. 6*2 = 12. 12/20 = 60%
        $qualId = $this->createQualAndGrades(['Mathematics' => 'B', 'Physics' => 'B']);
        $results = AIEngine::checkEligibility($qualId);
        $filtered = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($filtered);
        
        $this->assertEquals(60.0, $prog1['fit_percentage']);
        $this->assertEquals('Good Match', $prog1['confidence_label']);
    }

    public function test_confidence_label_strong_match_at_exactly_75_percent() {
        // A- = 8, B+ = 7. 8+7 = 15. 15/20 = 75%
        $qualId = $this->createQualAndGrades(['Mathematics' => 'A-', 'Physics' => 'B+']);
        $results = AIEngine::checkEligibility($qualId);
        $filtered = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($filtered);
        
        $this->assertEquals(75.0, $prog1['fit_percentage']);
        $this->assertEquals('Strong Match', $prog1['confidence_label']);
    }

    public function test_confidence_label_excellent_match_at_exactly_90_percent() {
        // A = 9. 9*2 = 18. 18/20 = 90%
        $qualId = $this->createQualAndGrades(['Mathematics' => 'A', 'Physics' => 'A']);
        $results = AIEngine::checkEligibility($qualId);
        $filtered = array_filter($results, fn($r) => $r['programme_id'] == 1);
        $prog1 = reset($filtered);
        
        $this->assertEquals(90.0, $prog1['fit_percentage']);
        $this->assertEquals('Excellent Match', $prog1['confidence_label']);
    }
}
