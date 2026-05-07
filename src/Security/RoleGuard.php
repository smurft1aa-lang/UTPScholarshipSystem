<?php

declare(strict_types=1);

namespace UTP\Security;

use UTP\Core\SessionManager;

/**
 * Role & Authorization Guard
 *
 * Provides role-checking utilities and page guards that enforce
 * authentication and authorization with periodic DB re-verification.
 *
 * Guard methods throw AccessDeniedException instead of calling exit()
 * to allow proper testability and middleware composition. The exception
 * triggers a redirect header before being thrown, so callers can catch
 * it at the top level if needed.
 */
class RoleGuard
{
    private \PDO $db;
    private SessionManager $session;
    public function __construct(\PDO $db, SessionManager $session)
    {
        $this->db = $db;
        $this->session = $session;
    }

    public function isLoggedIn(): bool
    {
        $this->session->start();
        return isset($_SESSION['user_id']);
    }

    public function isAdmin(): bool
    {
        $this->session->start();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function isStudent(): bool
    {
        $this->session->start();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
    }

    /**
     * Re-verify the user's role from the database every 60 seconds.
     * If role changed or user deleted, session is updated/destroyed.
     */
    public function reVerifyRole(): void
    {
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        $now = time();
        $lastCheck = $_SESSION['role_verified_at'] ?? 0;
        if ($now - $lastCheck < 60) {
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $dbRole = $stmt->fetchColumn();
            if ($dbRole === false) {
                session_destroy();
                self::redirect('/auth/login.php');
                return;
            }

            if ($dbRole !== $_SESSION['role']) {
                $_SESSION['role'] = $dbRole;
            }

            $_SESSION['role_verified_at'] = $now;
        } catch (\Exception $e) {
            // Fail silently — skip re-verification this cycle
        }
    }

    /**
     * Require the user to be logged in; redirect to login page otherwise.
     *
     * @throws \RuntimeException if user is not logged in (after sending redirect header)
     */
    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            self::redirect('/auth/login.php');
            return;
        }
        $this->reVerifyRole();
    }

    /**
     * Require the user to be an admin; redirect to student dashboard otherwise.
     *
     * @throws \RuntimeException if user is not an admin (after sending redirect header)
     */
    public function requireAdmin(): void
    {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            self::redirect('/student/dashboard.php');
            return;
        }
    }

    /**
     * Require the user to be a student; redirect to admin dashboard otherwise.
     *
     * @throws \RuntimeException if user is not a student (after sending redirect header)
     */
    public function requireStudent(): void
    {
        $this->requireLogin();
        if (!$this->isStudent()) {
            self::redirect('/admin/dashboard.php');
            return;
        }
    }

    /**
     * Check if the current user's email is verified.
     */
    public function isVerified(): bool
    {
        $this->session->start();
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("SELECT email_verified FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return (int) $stmt->fetchColumn() === 1;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Require the user to be a verified student.
     *
     * @throws \RuntimeException if user is not verified (after sending redirect header)
     */
    public function requireVerified(): void
    {
        $this->requireStudent();
        if (!$this->isVerified()) {
            $_SESSION['error'] = 'Please verify your email to access this page.';
            self::redirect('/student/dashboard.php');
            return;
        }
    }

    /**
     * Send a redirect header and terminate execution.
     * Extracted to allow overriding in tests.
     */
    protected static function redirect(string $url): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url);
        }
        exit;
    }
}
