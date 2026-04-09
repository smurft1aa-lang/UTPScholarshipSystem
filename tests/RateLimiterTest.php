<?php
use PHPUnit\Framework\TestCase;
use UTP\Security\RateLimiter;

class RateLimiterTest extends TestCase
{
    private $pdo;
    private $stmt;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->stmt = $this->createMock(\PDOStatement::class);
    }

    protected function tearDown(): void
    {
        // Clean up any env vars set during tests
        putenv('RATE_LIMIT_MAX_ATTEMPTS');
        putenv('RATE_LIMIT_WINDOW_MINUTES');
    }

    public function testCheckLimits()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(3);

        $limiter = new RateLimiter($this->pdo);
        $this->assertTrue($limiter->check('127.0.0.1', 5, 1));
        
        $this->stmt = $this->createMock(\PDOStatement::class);
        $this->stmt->method('fetchColumn')->willReturn(5);
        $this->pdo = $this->createMock(\PDO::class);
        $this->pdo->method('prepare')->willReturn($this->stmt);
        
        $limiter2 = new RateLimiter($this->pdo);
        $this->assertFalse($limiter2->check('127.0.0.1', 5, 1));
    }

    public function testRecordInsertsAttempt()
    {
        $this->stmt->expects($this->once())->method('execute')->with(['127.0.0.1']);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $limiter = new RateLimiter($this->pdo);
        $limiter->record('127.0.0.1');
    }

    public function testClearDeletesAttempts()
    {
        $this->stmt->expects($this->once())->method('execute')->with(['127.0.0.1']);
        $this->pdo->method('prepare')->willReturn($this->stmt);

        $limiter = new RateLimiter($this->pdo);
        $limiter->clear('127.0.0.1');
    }

    public function testCheckRespectsEnvVars()
    {
        // Set env vars to non-default values
        putenv('RATE_LIMIT_MAX_ATTEMPTS=3');
        putenv('RATE_LIMIT_WINDOW_MINUTES=2');

        // Mock: 2 attempts recorded — below the env-configured max of 3
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(2);
        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $limiter = new RateLimiter($pdo);
        // Call with no explicit params — should use env defaults (max=3)
        $this->assertTrue($limiter->check('127.0.0.1'));

        // Mock: 3 attempts recorded — equals the env-configured max of 3
        $stmt2 = $this->createMock(\PDOStatement::class);
        $stmt2->method('fetchColumn')->willReturn(3);
        $pdo2 = $this->createMock(\PDO::class);
        $pdo2->method('prepare')->willReturn($stmt2);

        $limiter2 = new RateLimiter($pdo2);
        // Should be blocked: 3 >= 3
        $this->assertFalse($limiter2->check('127.0.0.1'));
    }
}
