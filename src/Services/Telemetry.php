<?php

declare(strict_types=1);

namespace UTP\Services;

/**
 * Telemetry & Observability Service
 *
 * Provides Sentry integration, local file logging, and performance
 * timing instrumentation for monitoring application health.
 */
class Telemetry
{
    /**
     * Initialize Sentry SDK and user scope.
     */
    public static function init(): void
    {
        $env = getenv('APP_ENV') ?: 'production';
        $dsn = getenv('SENTRY_DSN');
        if ($env !== 'testing' && $dsn && class_exists('\Sentry\SentrySdk')) {
            \Sentry\init([
                'dsn' => $dsn,
                'environment' => $env,
                'traces_sample_rate' => 1.0,
            ]);
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
                \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {

                    $scope->setUser([
                        'id' => $_SESSION['user_id'],
                        'segment' => $_SESSION['role'] ?? 'guest',
                    ]);
                });
            }
        }
    }

    /**
     * Track an event to both Sentry and local log file.
     *
     * @param string $eventName  Human-readable event name
     * @param array  $context    Additional context data
     * @param string $level      Log level (INFO, WARNING, ERROR, CRITICAL)
     */
    public static function trackEvent(string $eventName, array $context = [], string $level = 'INFO'): void
    {
        $env = getenv('APP_ENV') ?: 'production';
// 1. Sentry breadcrumb/capture
        if ($env !== 'testing' && class_exists('\Sentry\SentrySdk')) {
            if ($level === 'ERROR' || $level === 'CRITICAL') {
                if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
                    \Sentry\captureException($context['exception']);
                } else {
                    \Sentry\captureMessage($eventName, \Sentry\Severity::error());
                }
            } else {
                \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(\Sentry\Breadcrumb::LEVEL_INFO, \Sentry\Breadcrumb::TYPE_DEFAULT, 'app', $eventName, $context));
            }
        }

        // 2. Local app.log
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
            @file_put_contents($logDir . '/.htaccess', "Order deny,allow\nDeny from all\n");
        }

        $userId = $_SESSION['user_id'] ?? 'SYSTEM';
        $timestamp = date('Y-m-d H:i:s');
        $logContext = $context;
        if (isset($logContext['exception'])) {
            unset($logContext['exception']);
        }
        $details = !empty($logContext) ? json_encode($logContext) : 'No details';
        $logMessage = "[$timestamp] [$level] [$userId] $eventName: $details\n";
        @file_put_contents($logDir . '/app.log', $logMessage, FILE_APPEND);
    }

    /** @var array<string, float> */
    private static array $timers = [];
/**
     * Start a performance timer.
     */
    public static function startTimer(string $label): void
    {
        if (getenv('APP_ENV') === 'testing') {
            return;
        }
        self::$timers[$label] = microtime(true);
    }

    /**
     * Stop a timer and return elapsed milliseconds.
     */
    public static function endTimer(string $label): float
    {
        if (getenv('APP_ENV') === 'testing') {
            return 0;
        }
        if (!isset(self::$timers[$label])) {
            return 0;
        }
        return round((microtime(true) - self::$timers[$label]) * 1000, 2);
    }
}
