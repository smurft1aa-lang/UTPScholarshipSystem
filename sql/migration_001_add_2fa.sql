-- Add 2FA columns to users table
ALTER TABLE users ADD COLUMN totp_secret VARCHAR(64) DEFAULT NULL AFTER email_verified;
ALTER TABLE users ADD COLUMN totp_enabled TINYINT(1) DEFAULT 0 AFTER totp_secret;
