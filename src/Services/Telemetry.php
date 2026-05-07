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
     *
     * @param string|null $userId Optional user ID for Sentry scope (falls back to session)
     * @param string|null $role   Optional user role for Sentry scope (falls back to session)
     */
    public static function init(?string $userId = null, ?string $role = null): void
    {
        $env = getenv('APP_ENV') ?: 'production';
        $dsn = getenv('SENTRY_DSN');
        if ($env !== 'testing' && $dsn && class_exists('\Sentry\SentrySdk')) {
            \Sentry\init([
                'dsn' => $dsn,
                'environment' => $env,
                'traces_sample_rate' => 1.0,
            ]);

            // Resolve user context: prefer explicit params, then session
            $resolvedUserId = $userId;
            $resolvedRole = $role ?? 'guest';
            if ($resolvedUserId === null && session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
                $sessionId = $_SESSION['user_id'];
                $resolvedUserId = is_scalar($sessionId) ? strval($sessionId) : null;
                $sessionRole = $_SESSION['role'] ?? null;
                $resolvedRole = is_string($sessionRole) ? $sessionRole : 'guest';
            }

            if ($resolvedUserId !== null) {
                \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($resolvedUserId, $resolvedRole): void {
                    $scope->setUser([
                        'id' => $resolvedUserId,
                        'segment' => $resolvedRole,
                    ]);
                });
            }
        }
    }

    /**
     * Track an event to both Sentry and local log file.
     *
     * @param string      $eventName  Human-readable event name
     * @param array       $context    Additional context data
     * @param string      $level      Log level (INFO, WARNING, ERROR, CRITICAL)
     * @param string|null $userId     Optional user ID (falls back to session, then 'SYSTEM')
     */
    public static function trackEvent(string $eventName, array $context = [], string $level = 'INFO', ?string $userId = null): void
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
            if (!mkdir($logDir, 0755, true) && !is_dir($logDir)) {
                error_log("Telemetry: failed to create log directory: {$logDir}");
                return;
            }
            $htaccessPath = $logDir . '/.htaccess';
            if (!file_exists($htaccessPath)) {
                file_put_contents($htaccessPath, "Order deny,allow\nDeny from all\n");
            }
        }

        // Resolve user ID: prefer explicit param, then session, then 'SYSTEM'
        $resolvedUserId = $userId;
        if ($resolvedUserId === null && session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $sessionId = $_SESSION['user_id'];
            $resolvedUserId = is_scalar($sessionId) ? strval($sessionId) : null;
        }
        $resolvedUserId ??= 'SYSTEM';

        $timestamp = date('Y-m-d H:i:s');
        $logContext = $context;
        if (isset($logContext['exception'])) {
            unset($logContext['exception']);
        }
        $details = !empty($logContext) ? json_encode($logContext) : 'No details';
        $logMessage = "[{$timestamp}] [{$level}] [{$resolvedUserId}] {$eventName}: {$details}\n";

        $logFile = $logDir . '/app.log';
        $written = file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        if ($written === false) {
            error_log("Telemetry: failed to write to {$logFile}");
        }
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
