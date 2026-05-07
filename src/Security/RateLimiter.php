<?php

declare(strict_types=1);

namespace UTP\Security;

/**
 * Rate Limiter Service
 *
 * Tracks login attempts by IP address and enforces configurable
 * rate limits with progressive lockout to prevent brute-force attacks.
 *
 * Lockout tiers:
 *   - Tier 1: Standard rate limit (default 5 attempts / 1 minute)
 *   - Tier 2: After 10 cumulative failures → 15-minute lockout
 *   - Tier 3: After 20 cumulative failures → 60-minute lockout
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
     * Implements progressive lockout based on cumulative failure count.
     *
     * @param string   $ip            The client's IP address
     * @param int|null $maxAttempts   Maximum attempts allowed (env: RATE_LIMIT_MAX_ATTEMPTS, default 5)
     * @param int|null $windowMinutes Time window in minutes (env: RATE_LIMIT_WINDOW_MINUTES, default 1)
     * @return bool True if the IP is within limits (allowed), false if rate-limited
     */
    public function check(string $ip, ?int $maxAttempts = null, ?int $windowMinutes = null): bool
    {
        $maxAttempts ??= (int) (getenv('RATE_LIMIT_MAX_ATTEMPTS') ?: 5);
        $windowMinutes ??= (int) (getenv('RATE_LIMIT_WINDOW_MINUTES') ?: 1);

        // Progressive lockout: check cumulative attempts in the last 60 minutes
        $lockoutWindow = gmdate('Y-m-d H:i:s', strtotime('-60 minutes'));
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?");
        $stmt->execute([$ip, $lockoutWindow]);
        $totalRecent = (int) $stmt->fetchColumn();

        // Tier 3: 20+ failures in last hour → locked for 60 minutes
        if ($totalRecent >= 20) {
            return false;
        }

        // Tier 2: 10+ failures in last hour → locked for 15 minutes
        if ($totalRecent >= 10) {
            $tier2Window = gmdate('Y-m-d H:i:s', strtotime('-15 minutes'));
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?");
            $stmt->execute([$ip, $tier2Window]);
            $tier2Count = (int) $stmt->fetchColumn();
            if ($tier2Count > 0) {
                return false;
            }
        }

        // Tier 1: Standard rate limit within the configured window
        // Clean up old entries beyond the lockout window
        $cleanupThreshold = gmdate('Y-m-d H:i:s', strtotime('-60 minutes'));
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
        $stmt->execute([$cleanupThreshold]);

        // Use gmdate to ensure UTC, matching standard DB CURRENT_TIMESTAMP
        $threshold = gmdate('Y-m-d H:i:s', strtotime("-$windowMinutes minutes"));
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?");
        $stmt->execute([$ip, $threshold]);
        $count = (int) $stmt->fetchColumn();

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
