<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/init.php';

/**
 * Admin Eligibility Integration Tests
 * Tests the logic for admin impersonation during eligibility checks and OCR.
 */
class AdminEligibilityTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $db = getDB();
        $db->beginTransaction();
        
        // Setup dummy student and admin
        $db->exec("DELETE FROM users WHERE email IN ('admin_elig_test@test.com', 'student_elig_test@test.com')");
        $db->exec("INSERT INTO users (id, full_name, email, role, password_hash, ic_number, phone) VALUES 
            (801, 'Admin Tester', 'admin_elig_test@test.com', 'admin', 'dummy', '801801801801', '0123456789'),
            (802, 'Student Tester', 'student_elig_test@test.com', 'student', 'dummy', '802802802802', '0123456789')
        ");
    }

    protected function tearDown(): void
    {
        $db = getDB();
        if ($db->inTransaction()) {
            $db->rollBack();
        }
    }

    /**
     * Helper to simulate the logic at the top of api/check-eligibility.php
     * and api/ocr-result.php where student_id overrides user_id if admin.
     */
    private function simulateTargetUserResolution(int $sessionUserId, string $role, ?int $postStudentId = null)
    {
        $_SESSION['user_id'] = $sessionUserId;
        $_SESSION['role'] = $role;
        $_POST['student_id'] = $postStudentId;

        $targetUserId = $_SESSION['user_id'];
        
        // Logic from api/check-eligibility.php
        if (isAdmin() && !empty($_POST['student_id'])) {
            $targetUserId = (int) $_POST['student_id'];
        }

        return $targetUserId;
    }

    public function test_admin_can_impersonate_student_for_eligibility_check()
    {
        // Admin (801) checking for Student (802)
        $resolvedUserId = $this->simulateTargetUserResolution(801, 'admin', 802);
        
        $this->assertEquals(802, $resolvedUserId, "Admin should be able to set target user to the student ID.");
    }

    public function test_student_cannot_impersonate_another_student()
    {
        // Student (802) trying to check for another Student (803)
        $resolvedUserId = $this->simulateTargetUserResolution(802, 'student', 803);
        
        $this->assertEquals(802, $resolvedUserId, "Student must not be able to override the target user ID.");
        $this->assertNotEquals(803, $resolvedUserId);
    }
    
    public function test_admin_check_eligibility_redirects_to_admin_results_view()
    {
        // Simulate the redirect logic at the end of api/check-eligibility.php
        $_SESSION['role'] = 'admin';
        $_SESSION['user_id'] = 801;
        $appId = 999;
        
        $redirectUrl = isAdmin() ? "/admin/student-results.php?id={$appId}" : '/student/results.php';
        
        $this->assertEquals("/admin/student-results.php?id=999", $redirectUrl);
    }
    
    public function test_student_check_eligibility_redirects_to_student_results_view()
    {
        $_SESSION['role'] = 'student';
        $_SESSION['user_id'] = 802;
        $appId = 999;
        
        $redirectUrl = isAdmin() ? "/admin/student-results.php?id={$appId}" : '/student/results.php';
        
        $this->assertEquals("/student/results.php", $redirectUrl);
    }
}
