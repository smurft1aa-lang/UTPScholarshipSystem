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

    public function testStaticLogCreatesDbEntry()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->expects($this->once())->method('execute')->willReturn(true);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $result = AuditLogger::log($this->pdo, 1, 'test action', 'User', 2, 'details');
        $this->assertTrue($result);
    }

    public function testInstanceLogActionCreatesDbEntry()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->expects($this->once())->method('execute')->willReturn(true);
        $logger = new AuditLogger($this->pdo);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $result = $logger->logAction(1, 'test action', 'User', 2, 'details');
        $this->assertTrue($result);
    }

    public function testLogHandlesException()
    {
        $this->pdo->method('prepare')->willThrowException(new \PDOException("DB Error"));
        // Suppress error_log to keep test output clean
        $oldErrorLog = ini_get('error_log');
        ini_set('error_log', PHP_OS_FAMILY === 'Windows' ? 'nul' : '/dev/null');
        $result = AuditLogger::log($this->pdo, 1, 'test action');
        ini_set('error_log', $oldErrorLog);
        $this->assertFalse($result);
    }
}
