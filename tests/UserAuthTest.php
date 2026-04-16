<?php

/**
 * UserAuth Service Tests
 *
 * Tests user registration, login, and getCurrentUser via
 * the namespaced UTP\Services\UserAuth class.
 */

use PHPUnit\Framework\TestCase;
use UTP\Services\UserAuth;

class UserAuthTest extends TestCase
{
    private \PDO $db;
    private UserAuth $auth;
    protected function setUp(): void
    {
        $this->db = getDB();
        $this->auth = new UserAuth($this->db);
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testRegisterUserSuccess(): void
    {
        $result = $this->auth->registerUser('New Student', 'new_' . uniqid() . '@test.com', 'Valid@1234', '88' . str_pad((string)rand(0, 9999999999), 10, '0', STR_PAD_LEFT), '0123456789');
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertNotEmpty($result['user_id']);
    }

    public function testRegisterDuplicateEmail(): void
    {
        $result = $this->auth->registerUser(
            'Duplicate Test',
            'student@test.com', // Already seeded in bootstrap
            'Valid@1234',
            '999999999901',
            '0123456789'
        );
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Email already registered', $result['error']);
    }

    public function testRegisterDuplicateIC(): void
    {
        $result = $this->auth->registerUser(
            'Duplicate IC Test',
            'unique_' . uniqid() . '@test.com',
            'Valid@1234',
            '111111111111', // Already seeded in bootstrap
            '0123456789'
        );
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('IC Number already registered', $result['error']);
    }

    public function testLoginSuccess(): void
    {
        $result = $this->auth->loginUser('student@test.com', 'Valid@1234');
        $this->assertTrue($result['success']);
        $this->assertEquals('student', $result['role']);
        $this->assertEquals('Test Student', $_SESSION['full_name']);
    }

    public function testLoginInvalidPassword(): void
    {
        $result = $this->auth->loginUser('student@test.com', 'WrongPassword!');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid email or password', $result['error']);
    }

    public function testLoginNonexistentUser(): void
    {
        $result = $this->auth->loginUser('nobody@test.com', 'SomePass@1');
        $this->assertFalse($result['success']);
    }

    public function testGetCurrentUserReturnsNullWhenNotLoggedIn(): void
    {
        $_SESSION = [];
        $result = $this->auth->getCurrentUser();
        $this->assertNull($result);
    }

    public function testGetCurrentUserReturnsDataWhenLoggedIn(): void
    {
        $_SESSION['user_id'] = 2;
        $_SESSION['role'] = 'student';
        $_SESSION['full_name'] = 'Test Student';
        $result = $this->auth->getCurrentUser();
        $this->assertNotNull($result);
        $this->assertEquals(2, $result['id']);
        $this->assertEquals('student', $result['role']);
        $this->assertEquals('Test Student', $result['full_name']);
    }
}
