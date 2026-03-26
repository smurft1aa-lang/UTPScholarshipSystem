<?php
namespace UTP\Services;

/**
 * User Authentication Service
 *
 * Handles user registration, login, and current user retrieval.
 * Delegates to session management, rate limiting, and audit logging.
 *
 * @implements \UTP\Contracts\AuthenticatesUsers
 */
class UserAuth implements \UTP\Contracts\AuthenticatesUsers
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Register a new student user.
     *
     * @param string $fullName User's full name
     * @param string $email    Login email (must be unique)
     * @param string $password Raw password (will be hashed with bcrypt cost 12)
     * @param string $icNumber Malaysian IC or Passport number (must be unique)
     * @param string $phone    Contact number
     * @return array{success: bool, error?: string, user_id?: int}
     */
    public function registerUser(string $fullName, string $email, string $password, string $icNumber, string $phone): array
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Email already registered.'];
        }

        $cleanIC = preg_replace('/[-\s]/', '', $icNumber);
        $stmt = $this->db->prepare("SELECT id FROM users WHERE REPLACE(REPLACE(ic_number, '-', ''), ' ', '') = ?");
        $stmt->execute([$cleanIC]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'IC Number already registered.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $this->db->prepare("INSERT INTO users (full_name, email, password_hash, ic_number, phone, role, email_verified) VALUES (?, ?, ?, ?, ?, 'student', 0)");
        $stmt->execute([$fullName, $email, $hash, $icNumber, $phone]);

        $userId = $this->db->lastInsertId();

        if (function_exists('logAudit')) {
            logAudit($userId, 'User Registered', 'User', $userId, "Email: $email");
        }

        if (function_exists('sendVerificationEmail')) {
            sendVerificationEmail($userId, $email, $fullName);
        }

        if (function_exists('initSession')) {
            initSession();
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = 'student';
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email_verified'] = 0;

        if (function_exists('initTelemetry')) {
            initTelemetry();
        }

        return ['success' => true, 'user_id' => $userId];
    }

    /**
     * Authenticate a user by email and password.
     *
     * @param string $email    Login email
     * @param string $password Raw password to verify
     * @return array{success: bool, error?: string, role?: string}
     */
    public function loginUser(string $email, string $password): array
    {
        $ip = function_exists('getClientIP') ? getClientIP() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        if (function_exists('checkRateLimit') && !checkRateLimit($ip)) {
            return ['success' => false, 'error' => 'Too many login attempts. Please try again later.'];
        }

        $stmt = $this->db->prepare("SELECT id, full_name, email, password_hash, role, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            if (function_exists('recordLoginAttempt')) {
                recordLoginAttempt($ip);
            }
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        if (function_exists('clearLoginAttempts')) {
            clearLoginAttempts($ip);
        }

        if (function_exists('initSession')) {
            initSession();
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email_verified'] = $user['email_verified'];

        if (function_exists('initTelemetry')) {
            initTelemetry();
        }

        if (function_exists('logAudit')) {
            logAudit($user['id'], 'User Logged In');
        }

        return ['success' => true, 'role' => $user['role']];
    }

    /**
     * Get the current authenticated user's basic info from session.
     *
     * @return array{id: int, role: string, full_name: string}|null
     */
    public function getCurrentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return [
            'id'        => $_SESSION['user_id'],
            'role'      => $_SESSION['role'],
            'full_name' => $_SESSION['full_name'],
        ];
    }
}
