<?php
namespace UTP\Security;

/**
 * Rate Limiter Service
 *
 * Tracks login attempts by IP address and enforces configurable
 * rate limits to prevent brute-force attacks.
 *
 * @method bool check(string $ip, int $maxAttempts, int $windowMinutes) Check if IP is within limits
 * @method void record(string $ip) Record a failed attempt
 * @method void clear(string $ip) Clear all attempts for an IP (on successful login)
 */
class RateLimiter
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Check whether the given IP has exceeded the allowed login attempts.
     *
     * @param string $ip          The client's IP address
     * @param int    $maxAttempts Maximum attempts allowed within the window
     * @param int    $windowMinutes Time window in minutes
     * @return bool True if the IP is within limits (allowed), false if rate-limited
     */
    public function check(string $ip, int $maxAttempts = 5, int $windowMinutes = 1): bool
    {
        // Use gmdate to ensure UTC, matching standard DB CURRENT_TIMESTAMP
        $threshold = gmdate('Y-m-d H:i:s', strtotime("-$windowMinutes minutes"));

        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
        $stmt->execute([$threshold]);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?");
        $stmt->execute([$ip, $threshold]);
        $count = $stmt->fetchColumn();

        return $count < $maxAttempts;
    }

    /**
     * Record a failed login attempt for the given IP.
     */
    public function record(string $ip): void
    {
        $stmt = $this->db->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
        $stmt->execute([$ip]);
    }

    /**
     * Clear all login attempts for the given IP (called on successful login).
     */
    public function clear(string $ip): void
    {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
    }
}
