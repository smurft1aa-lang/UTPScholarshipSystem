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
}
