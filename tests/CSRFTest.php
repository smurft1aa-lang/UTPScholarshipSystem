<?php
use PHPUnit\Framework\TestCase;
use UTP\Security\CSRF;

class CSRFTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testGenerateTokenCreatesAndStoresToken()
    {
        $token = CSRF::generateToken();
        $this->assertNotEmpty($token);
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testValidateTokenReturnsTrueForValidToken()
    {
        $token = CSRF::generateToken();
        
        // Save old token to check rotation
        $oldToken = $_SESSION['csrf_token'];
        
        $isValid = CSRF::validateToken($token);
        $this->assertTrue($isValid);
        
        // Check if token was rotated
        $this->assertNotEquals($oldToken, $_SESSION['csrf_token']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testValidateTokenReturnsFalseForInvalidToken()
    {
        CSRF::generateToken();
        $isValid = CSRF::validateToken('invalid_token');
        $this->assertFalse($isValid);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFieldReturnsHiddenInput()
    {
        $field = CSRF::field();
        $this->assertStringContainsString('<input type="hidden" name="csrf_token"', $field);
    }
}
