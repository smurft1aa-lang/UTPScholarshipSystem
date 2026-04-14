<?php
declare(strict_types=1);
namespace UTP\Security;

use OTPHP\TOTP;

/**
 * Two-Factor Authentication Service
 *
 * Provides TOTP-based (Google Authenticator compatible) 2FA
 * for admin accounts. Supports secret generation, QR provisioning
 * URI creation, and OTP verification.
 */
class TwoFactorAuth
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Generate a new TOTP secret for a user and store it (unverified).
     *
     * @param int    $userId The user's ID
     * @param string $email  The user's email (used as the TOTP label)
     * @return array{secret: string, provisioningUri: string}
     */
    public function generateSecret(int $userId, string $email): array
    {
        $totp = TOTP::generate();
        $totp->setLabel($email);
        $totp->setIssuer('UTP Scholarship System');

        $secret = $totp->getSecret();

        // Store the secret (not yet verified — user must confirm with a valid code first)
        $stmt = $this->db->prepare("
            UPDATE users SET totp_secret = ?, totp_enabled = 0 WHERE id = ?
        ");
        $stmt->execute([$secret, $userId]);

        return [
            'secret' => $secret,
            'provisioningUri' => $totp->getProvisioningUri(),
        ];
    }

    /**
     * Verify a TOTP code and enable 2FA for the user on first successful verification.
     *
     * @param int    $userId The user's ID
     * @param string $code   The 6-digit TOTP code
     * @return bool True if the code is valid
     */
    public function verifyCode(int $userId, string $code): bool
    {
        $stmt = $this->db->prepare("SELECT totp_secret FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $secret = $stmt->fetchColumn();

        if (!$secret) {
            return false;
        }

        $totp = TOTP::createFromSecret($secret);
        $valid = $totp->verify($code, null, 1); // Allow 1 time window of drift

        if ($valid) {
            // Enable 2FA on first successful verification
            $stmt = $this->db->prepare("UPDATE users SET totp_enabled = 1 WHERE id = ?");
            $stmt->execute([$userId]);
        }

        return $valid;
    }

    /**
     * Check if a user has 2FA enabled.
     */
    public function isEnabled(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT totp_enabled FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn() === 1;
    }

    /**
     * Disable 2FA for a user (admin action).
     */
    public function disable(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?");
        $stmt->execute([$userId]);
    }
}
