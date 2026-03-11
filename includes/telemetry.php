<?php
/**
 * Telemetry & Logging
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$GLOBALS['timers'] = [];
$GLOBALS['timers']['page_load'] = microtime(true);

if (!function_exists('initTelemetry')) {
    function initTelemetry() {
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
}

if (!function_exists('trackEvent')) {
    function trackEvent($eventName, $context = [], $level = 'INFO') {
        $env = getenv('APP_ENV') ?: 'production';
        
        // 1. Sentry breadcrumb/capture
        if ($env !== 'testing' && class_exists('\Sentry\SentrySdk')) {
            if ($level === 'ERROR' || $level === 'CRITICAL') {
                if (isset($context['exception']) && $context['exception'] instanceof Exception) {
                    \Sentry\captureException($context['exception']);
                } else {
                    \Sentry\captureMessage($eventName, \Sentry\Severity::error());
                }
            } else {
                \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    'app',
                    $eventName,
                    $context
                ));
            }
        }
        
        // 2. Local app.log
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
            @file_put_contents($logDir . '/.htaccess', "Order deny,allow\nDeny from all\n");
        }
        
        $userId = $_SESSION['user_id'] ?? 'SYSTEM';
        $timestamp = date('Y-m-d H:i:s');
        
        // Remove exception object from local text log to prevent huge dumps
        $logContext = $context;
        if (isset($logContext['exception'])) unset($logContext['exception']);
        $details = !empty($logContext) ? json_encode($logContext) : 'No details';
        
        $logMessage = "[$timestamp] [$level] [$userId] $eventName: $details\n";
        @file_put_contents($logDir . '/app.log', $logMessage, FILE_APPEND);
    }
}

if (!function_exists('startTimer')) {
    function startTimer($label) {
        if (getenv('APP_ENV') === 'testing') return;
        $GLOBALS['timers'][$label] = microtime(true);
    }
}

if (!function_exists('endTimer')) {
    function endTimer($label) {
        if (getenv('APP_ENV') === 'testing') return 0;
        if (!isset($GLOBALS['timers'][$label])) return 0;
        $durationMs = (microtime(true) - $GLOBALS['timers'][$label]) * 1000;
        return round($durationMs, 2);
    }
}
