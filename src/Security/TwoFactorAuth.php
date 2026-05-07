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
 *
 * TOTP secrets are encrypted at rest using OpenSSL AES-256-GCM
 * with the APP_KEY environment variable as the encryption key.
 */
class TwoFactorAuth
{
    private \PDO $db;
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get the encryption key from environment. Falls back to a
     * deterministic key derived from APP_KEY or GEMINI_API_KEY.
     *
     * @return string 32-byte binary key for AES-256 encryption
     */
    private static function getEncryptionKey(): string
    {
        $appKey = getenv('APP_KEY') ?: getenv('GEMINI_API_KEY') ?: '';
        if (empty($appKey)) {
            throw new \RuntimeException('APP_KEY or GEMINI_API_KEY must be set for 2FA encryption.');
        }
        // Derive a fixed-length 32-byte key using SHA-256
        return hash('sha256', $appKey, true);
    }

    /**
     * Encrypt a TOTP secret for database storage using AES-256-GCM.
     */
    private static function encryptSecret(string $plainSecret): string
    {
        $key = self::getEncryptionKey();
        $iv = random_bytes(12); // 96-bit IV for GCM
        $tag = '';
        $ciphertext = openssl_encrypt($plainSecret, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new \RuntimeException('TOTP secret encryption failed.');
        }
        // Store as base64(iv + tag + ciphertext) for safe DB storage
        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a TOTP secret retrieved from the database.
     * Returns the input as-is if it's a legacy plaintext secret.
     */
    private static function decryptSecret(string $encrypted): ?string
    {
        $key = self::getEncryptionKey();
        $decoded = base64_decode($encrypted, true);
        // Minimum length: 12 (IV) + 16 (tag) + 1 (ciphertext) = 29 bytes
        if ($decoded === false || strlen($decoded) < 29) {
            // Not an encrypted value — may be a legacy plaintext secret
            return $encrypted;
        }

        $iv = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $ciphertext = substr($decoded, 28);
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($plaintext === false) {
            // Decryption failed — likely a legacy plaintext secret, return as-is
            return $encrypted;
        }

        return $plaintext;
    }

    /**
     * Generate a new TOTP secret for a user and store it encrypted (unverified).
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

        // Encrypt the secret before storing in the database
        $encryptedSecret = self::encryptSecret($secret);

        // Store the encrypted secret (not yet verified — user must confirm with a valid code first)
        $stmt = $this->db->prepare("
            UPDATE users SET totp_secret = ?, totp_enabled = 0 WHERE id = ?
        ");
        $stmt->execute([$encryptedSecret, $userId]);
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
        $storedSecret = $stmt->fetchColumn();
        if (!$storedSecret) {
            return false;
        }

        // Decrypt the secret from the database
        $secret = self::decryptSecret($storedSecret);
        if ($secret === null) {
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
