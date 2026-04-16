<?php

use PHPUnit\Framework\TestCase;
use UTP\Security\RoleGuard;
use UTP\Core\SessionManager;

class RoleGuardTest extends TestCase
{
    private $pdo;
    private $sessionManager;
    private $roleGuard;
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->pdo = $this->createMock(\PDO::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->roleGuard = new RoleGuard($this->pdo, $this->sessionManager);
    }

    public function testIsLoggedIn()
    {
        $_SESSION['user_id'] = 1;
        $this->assertTrue($this->roleGuard->isLoggedIn());
        unset($_SESSION['user_id']);
        $this->assertFalse($this->roleGuard->isLoggedIn());
    }

    public function testIsAdmin()
    {
        $_SESSION['role'] = 'admin';
        $this->assertTrue($this->roleGuard->isAdmin());
        $_SESSION['role'] = 'student';
        $this->assertFalse($this->roleGuard->isAdmin());
    }

    public function testIsStudent()
    {
        $_SESSION['role'] = 'student';
        $this->assertTrue($this->roleGuard->isStudent());
        $_SESSION['role'] = 'admin';
        $this->assertFalse($this->roleGuard->isStudent());
    }

    public function testIsVerified()
    {
        $_SESSION['user_id'] = 1;
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(1);
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->assertTrue($this->roleGuard->isVerified());
    }

    public function testRequireLoginPasses()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role_verified_at'] = time();
// Prevent reVerifyRole from querying
        $this->roleGuard->requireLogin();
        $this->assertTrue(true);
    }

    public function testRequireAdminPasses()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
        $_SESSION['role_verified_at'] = time();
        $this->roleGuard->requireAdmin();
        $this->assertTrue(true);
    }

    public function testRequireStudentPasses()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'student';
        $_SESSION['role_verified_at'] = time();
        $this->roleGuard->requireStudent();
        $this->assertTrue(true);
    }

    public function testRequireVerifiedPasses()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'student';
        $_SESSION['role_verified_at'] = time();
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(1);
// verified
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->roleGuard->requireVerified();
        $this->assertTrue(true);
    }

    public function testReVerifyRoleUpdatesRole()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'student';
        $_SESSION['role_verified_at'] = time() - 120;
// force query

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn('admin');
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->roleGuard->reVerifyRole();
        $this->assertEquals('admin', $_SESSION['role']);
    }

    public function testReVerifyRoleFailsSilentlyOnException()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role_verified_at'] = time() - 120;
        $this->pdo->method('prepare')->willThrowException(new \Exception("DB failure"));
        $this->roleGuard->reVerifyRole();
        $this->assertTrue(true);
    }
}
