<?php
use PHPUnit\Framework\TestCase;
use UTP\Services\AuditLogger;

class AuditLoggerTest extends TestCase
{
    private $pdo;
    private $stmt;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->stmt = $this->createMock(\PDOStatement::class);
    }

    public function testLogCreatesDbEntry()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->expects($this->once())->method('execute')->willReturn(true);

        $logger = new AuditLogger($this->pdo);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        
        $result = $logger->log(1, 'test action', 'User', 2, 'details');
        $this->assertTrue($result);
    }
    
    public function testLogHandlesException()
    {
        $this->pdo->method('prepare')->willThrowException(new \PDOException("DB Error"));

        $logger = new AuditLogger($this->pdo);
        $result = $logger->log(1, 'test action');
        $this->assertFalse($result);
    }
}
