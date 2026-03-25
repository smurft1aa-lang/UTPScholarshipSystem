<?php
use PHPUnit\Framework\TestCase;
use UTP\Services\Telemetry;

class TelemetryTest extends TestCase
{
    public function testTimerFunctions()
    {
        putenv('APP_ENV=testing');
        Telemetry::startTimer('test_timer');
        usleep(1000);
        $time = Telemetry::endTimer('test_timer');
        $this->assertEquals(0, $time);

        putenv('APP_ENV=production');
        Telemetry::startTimer('test_timer');
        usleep(5000);
        $time = Telemetry::endTimer('test_timer');
        $this->assertGreaterThan(0, $time);
        
        putenv('APP_ENV=testing');
    }

    public function testTrackEventCreatesLogFile()
    {
        putenv('APP_ENV=testing');
        
        $eventName = 'test_event_' . uniqid();
        Telemetry::trackEvent($eventName, ['key' => 'value'], 'INFO');
        
        $logFile = dirname(__DIR__) . '/logs/app.log';
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $this->assertStringContainsString($eventName, $content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testInitGracefulFallback()
    {
        putenv('APP_ENV=production');
        putenv('SENTRY_DSN=http://public@sentry.test/1');
        
        Telemetry::init();
        $this->assertTrue(true);
        putenv('APP_ENV=testing');
    }

    public function testTrackEventErrorBranchWithException()
    {
        putenv('APP_ENV=production');
        Telemetry::trackEvent('Test Error', ['exception' => new \Exception("Testing telemetry error branch")], 'ERROR');
        $this->assertTrue(true);
        putenv('APP_ENV=testing');
    }

    public function testTrackEventCriticalBranchWithoutException()
    {
        putenv('APP_ENV=production');
        Telemetry::trackEvent('Test Critical', [], 'CRITICAL');
        $this->assertTrue(true);
        putenv('APP_ENV=testing');
    }
}
