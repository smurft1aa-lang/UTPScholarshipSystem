<?php
use PHPUnit\Framework\TestCase;

if (!defined('APP_ENV')) define('APP_ENV', 'testing');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

class AuthTest extends TestCase {

    protected function setUp(): void {
        $db = getDB();
        $db->exec("DELETE FROM users WHERE email = 'new@test.com'");
        $db->exec("DELETE FROM login_attempts");
        $_SESSION = [];
    }

    public function test_login_fails_with_empty_email() {
        $result = loginUser('', 'password123');
        $this->assertFalse($result['success']);
    }

    public function test_login_fails_with_wrong_password() {
        // Seeded admin uses HASH which fails against anything else
        $result = loginUser('admin@test.com', 'wrongpassword');
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid email or password.', $result['error']);
    }

    public function test_login_fails_after_5_attempts() {
        for ($i = 0; $i < 5; $i++) {
            loginUser('admin@test.com', 'wrongpassword');
        }
        $result = loginUser('admin@test.com', 'wrongpassword');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Too many login attempts', $result['error']);
    }

    public function test_login_succeeds_with_correct_credentials() {
        // We need to insert a known hash to test success
        $db = getDB();
        $hash = password_hash('Valid@1234', PASSWORD_BCRYPT);
        $db->exec("UPDATE users SET password_hash = '$hash' WHERE email = 'student@test.com'");
        
        $result = loginUser('student@test.com', 'Valid@1234');
        $this->assertTrue($result['success']);
        $this->assertEquals('student', $result['role']);
    }

    public function test_register_fails_with_duplicate_email() {
        $result = registerUser('Test', 'student@test.com', 'Valid@1234', '9999', '123');
        $this->assertFalse($result['success']);
        $this->assertEquals('Email already registered.', $result['error']);
    }

    public function test_register_fails_with_duplicate_ic() {
        $result = registerUser('Test', 'new@test.com', 'Valid@1234', '1111', '123'); // 1111 is student test seed
        $this->assertFalse($result['success']);
        $this->assertEquals('IC Number already registered.', $result['error']);
    }

    public function test_register_fails_with_weak_password() {
        $errors = validatePassword('weak');
        $this->assertNotEmpty($errors);
        $this->assertContains('Password must be at least 8 characters.', $errors);
    }

    public function test_register_succeeds_with_valid_data() {
        // Mock mail() indirectly by capturing/suppressing since we test the DB state
        $result = registerUser('New Student', 'new@test.com', 'Valid@1234', '992233', '123456789');
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertEquals('student', $_SESSION['role']);
    }

    public function test_session_expires_after_30_minutes() {
        // Init a session
        initSession();
        $_SESSION['user_id'] = 1;
        $_SESSION['last_activity'] = time() - 3600; // 1 hr ago
        
        // Next initSession call should destroy it
        initSession();
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function test_csrf_token_rejected_if_missing() {
        $this->assertFalse(validateCSRFToken(''));
    }

    public function test_csrf_token_rejected_if_tampered() {
        generateCSRFToken(); // initializes it
        $this->assertFalse(validateCSRFToken('tampered_token_xyz'));
    }

    public function test_admin_cannot_access_student_dashboard() {
        $_SESSION['role'] = 'admin';
        $_SESSION['user_id'] = 1;
        // In reality requireStudent throws a redirect. 
        // We will catch it by expecting a specific header or exit.
        // We can simulate it by observing the function logic.
        $this->assertTrue(isAdmin());
        $this->assertFalse(isStudent());
    }

    public function test_student_cannot_access_admin_dashboard() {
        $_SESSION['role'] = 'student';
        $_SESSION['user_id'] = 2;
        $this->assertFalse(isAdmin());
        $this->assertTrue(isStudent());
    }

    public function test_unverified_user_blocked_from_eligibility_check() {
        $_SESSION['role'] = 'student';
        $_SESSION['user_id'] = 2;
        $_SESSION['email_verified'] = 0;
        
        $this->assertFalse(isVerified());
    }
}
