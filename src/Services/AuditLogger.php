<?php

declare(strict_types=1);

namespace UTP\Services;

/**
 * Audit Logging Service
 *
 * Records user actions in the audit_log table for compliance
 * and security monitoring purposes.
 *
 * Supports both static and instance usage:
 *   - Static: AuditLogger::log($db, $userId, $action, ...)
 *   - Instance: $logger->logAction($userId, $action, ...)
 */
class AuditLogger
{
    private \PDO $db;
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Log an auditable action (static convenience method).
     *
     * This method is the preferred entry point for one-off audit calls
     * where constructing an AuditLogger instance is unnecessary.
     *
     * @param \PDO        $db         Database connection
     * @param int         $userId     The user performing the action
     * @param string      $action     Human-readable action description
     * @param string|null $targetType The entity type affected (e.g., 'User', 'Application')
     * @param int|null    $targetId   The ID of the affected entity
     * @param string|null $details    Additional contextual details
     * @return bool True on success, false on failure
     */
    public static function log(\PDO $db, int $userId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): bool
    {
        try {
            $ipAddress = \UTP\Security\InputSanitizer::getClientIP();
            $stmt = $db->prepare("
                INSERT INTO audit_log (user_id, action, target_type, target_id, details, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $targetType,
                $targetId,
                $details,
                $ipAddress
            ]);
            return true;
        } catch (\PDOException $e) {
            error_log("Audit log failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log an auditable action (instance method).
     *
     * @param int         $userId     The user performing the action
     * @param string      $action     Human-readable action description
     * @param string|null $targetType The entity type affected (e.g., 'User', 'Application')
     * @param int|null    $targetId   The ID of the affected entity
     * @param string|null $details    Additional contextual details
     * @return bool True on success, false on failure
     */
    public function logAction(int $userId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): bool
    {
        return self::log($this->db, $userId, $action, $targetType, $targetId, $details);
    }
}
