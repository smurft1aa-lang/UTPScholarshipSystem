<?php
namespace UTP\Services;

/**
 * Audit Logging Service
 *
 * Records user actions in the audit_log table for compliance
 * and security monitoring purposes.
 */
class AuditLogger
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Log an auditable action.
     *
     * @param int         $userId     The user performing the action
     * @param string      $action     Human-readable action description
     * @param string|null $targetType The entity type affected (e.g., 'User', 'Application')
     * @param int|null    $targetId   The ID of the affected entity
     * @param string|null $details    Additional contextual details
     * @return bool True on success, false on failure
     */
    public function log(int $userId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): bool
    {
        try {
            $ipAddress = \UTP\Security\InputSanitizer::getClientIP();

            $stmt = $this->db->prepare("
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
}
