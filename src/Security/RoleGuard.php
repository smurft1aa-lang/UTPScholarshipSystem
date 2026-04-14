<?php
declare(strict_types=1);
namespace UTP\Security;

use UTP\Core\SessionManager;

/**
 * Role & Authorization Guard
 *
 * Provides role-checking utilities and page guards that enforce
 * authentication and authorization with periodic DB re-verification.
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
        if (!isset($_SESSION['user_id'])) return;

        $now = time();
        $lastCheck = $_SESSION['role_verified_at'] ?? 0;
        if ($now - $lastCheck < 60) return;

        try {
            $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $dbRole = $stmt->fetchColumn();

            if ($dbRole === false) {
                session_destroy();
                header('Location: /auth/login.php');
                exit;
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
     */
    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /auth/login.php');
            exit;
        }
        $this->reVerifyRole();
    }

    /**
     * Require the user to be an admin; redirect to student dashboard otherwise.
     */
    public function requireAdmin(): void
    {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: /student/dashboard.php');
            exit;
        }
    }

    /**
     * Require the user to be a student; redirect to admin dashboard otherwise.
     */
    public function requireStudent(): void
    {
        $this->requireLogin();
        if (!$this->isStudent()) {
            header('Location: /admin/dashboard.php');
            exit;
        }
    }

    /**
     * Check if the current user's email is verified.
     */
    public function isVerified(): bool
    {
        $this->session->start();
        if (!isset($_SESSION['user_id'])) return false;

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
     */
    public function requireVerified(): void
    {
        $this->requireStudent();
        if (!$this->isVerified()) {
            $_SESSION['error'] = 'Please verify your email to access this page.';
            header('Location: /student/dashboard.php');
            exit;
        }
    }
}
