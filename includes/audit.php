<?php
/**
 * Audit Logging Function
 */
require_once __DIR__ . '/../config/database.php';

if (!function_exists('logAudit')) {
    function logAudit($userId, $action, $targetType = null, $targetId = null, $details = null) {
        try {
            $db = getDB();
            
            // Ensure IP function exists
            if (!function_exists('getClientIP')) {
                require_once __DIR__ . '/security.php';
            }
            
            $ipAddress = getClientIP();
            
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
        } catch (PDOException $e) {
            // Fail silently so we don't disrupt the user's flow, but error log it
            error_log("Audit log failed: " . $e->getMessage());
            return false;
        }
    }
}
