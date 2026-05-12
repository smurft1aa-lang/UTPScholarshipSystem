<?php

declare(strict_types=1);

/**
 * Shared API Response Helpers
 *
 * Provides apiError() and apiSuccess() for all API endpoints.
 * Previously these were copy-pasted in check-eligibility.php and submit-application.php.
 */

if (!function_exists('apiError')) {
    /**
     * Respond with an error in either JSON or redirect format.
     */
    function apiError(int $httpCode, string $message, string $redirectUrl = '/student/dashboard.php'): never
    {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($acceptHeader, 'application/json')) {
            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $message]);
        } else {
            $_SESSION['error'] = $message;
            header('Location: ' . $redirectUrl);
        }
        exit;
    }
}

if (!function_exists('apiSuccess')) {
    /**
     * Respond with success in either JSON or redirect format.
     */
    function apiSuccess(string $redirectUrl, string $message = ''): never
    {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($acceptHeader, 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
        } else {
            if ($message) {
                $_SESSION['success'] = $message;
            }
            header('Location: ' . $redirectUrl);
        }
        exit;
    }
}
