<?php
use PHPUnit\Framework\TestCase;
use UTP\Security\TwoFactorAuth;

class TwoFactorAuthTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $tfa;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->stmt = $this->createMock(\PDOStatement::class);
        $this->tfa = new TwoFactorAuth($this->pdo);
    }

    public function testGenerateSecret()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->expects($this->once())->method('execute');

        $result = $this->tfa->generateSecret(1, 'test@example.com');
        
        $this->assertArrayHasKey('secret', $result);
        $this->assertArrayHasKey('provisioningUri', $result);
        $this->assertNotEmpty($result['secret']);
    }

    public function testVerifyCodeInvalidSecret()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(false);

        $this->assertFalse($this->tfa->verifyCode(1, '123456'));
    }

    public function testIsEnabled()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(1);

        $this->assertTrue($this->tfa->isEnabled(1));
    }

    public function testDisable()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->expects($this->once())->method('execute')->with([1]);

        $this->tfa->disable(1);
    }
}
