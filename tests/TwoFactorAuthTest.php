<?php

use PHPUnit\Framework\TestCase;
use UTP\Security\TwoFactorAuth;

class TwoFactorAuthTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $tfa;
    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->stmt = $this->createMock(\PDOStatement::class);
        $this->tfa = new TwoFactorAuth($this->pdo);
    }

    // ─── generateSecret ─────────────────────────────────────────────

    public function testGenerateSecret()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->expects($this->once())->method('execute');
        $result = $this->tfa->generateSecret(1, 'test@example.com');
        $this->assertArrayHasKey('secret', $result);
        $this->assertArrayHasKey('provisioningUri', $result);
        $this->assertNotEmpty($result['secret']);
    }

    public function testGenerateSecretReturnsProvisioningUri()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $result = $this->tfa->generateSecret(1, 'user@utp.edu.my');
        $this->assertStringContainsString('otpauth://totp/', $result['provisioningUri']);
        $this->assertStringContainsString('UTP%20Scholarship%20System', $result['provisioningUri']);
    }

    public function testGenerateSecretStoresEncryptedValue()
    {
        // Capture the value written to the DB — it should NOT be the plaintext secret
        $capturedArgs = null;
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function ($args) use (&$capturedArgs) {
                $capturedArgs = $args;
                return true;
            });

        $result = $this->tfa->generateSecret(1, 'test@example.com');
        // The stored value (capturedArgs[0]) must differ from the plaintext secret
        $this->assertNotEquals($result['secret'], $capturedArgs[0]);
        // The stored value should be base64-encoded
        $this->assertNotFalse(base64_decode($capturedArgs[0], true));
    }

    // ─── verifyCode ─────────────────────────────────────────────────

    public function testVerifyCodeInvalidSecret()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(false);
        $this->assertFalse($this->tfa->verifyCode(1, '123456'));
    }

    public function testVerifyCodeReturnsFalseForNonStringSecret()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(null);
        $this->assertFalse($this->tfa->verifyCode(1, '123456'));
    }

    public function testVerifyCodeReturnsFalseForEmptyStringSecret()
    {
        // v30 edge case: empty string after decrypt should return false
        $this->pdo->method('prepare')->willReturn($this->stmt);
        // An empty string is stored — decrypt will return '' which should fail
        $this->stmt->method('fetchColumn')->willReturn('');
        $this->assertFalse($this->tfa->verifyCode(1, '123456'));
    }

    public function testVerifyCodeWithValidTotp()
    {
        // Generate a real secret, encrypt it, then verify the current TOTP code
        $totp = \OTPHP\TOTP::generate();
        $secret = $totp->getSecret();

        // Use the real TFA to generate (which encrypts) then verify
        $realPdo = $this->createRealSqlitePdo();
        $tfa = new TwoFactorAuth($realPdo);

        // Insert a test user
        $realPdo->exec("INSERT INTO users (id, full_name, email, password_hash, ic_number, phone, role)
                         VALUES (99, 'TOTP User', 'totp@test.com', 'hash', '999999999999', '0199999999', 'admin')");

        // generateSecret stores the encrypted secret in the DB
        $result = $tfa->generateSecret(99, 'totp@test.com');
        $plainSecret = $result['secret'];

        // Create a TOTP from the plain secret and generate the current code
        $verifier = \OTPHP\TOTP::createFromSecret($plainSecret);
        $validCode = $verifier->now();

        $this->assertTrue($tfa->verifyCode(99, $validCode));

        // After verification, totp_enabled should be 1
        $enabled = $realPdo->query("SELECT totp_enabled FROM users WHERE id = 99")->fetchColumn();
        $this->assertEquals(1, (int) $enabled);
    }

    public function testVerifyCodeWithInvalidCodeReturnsFalse()
    {
        $realPdo = $this->createRealSqlitePdo();
        $tfa = new TwoFactorAuth($realPdo);

        $realPdo->exec("INSERT INTO users (id, full_name, email, password_hash, ic_number, phone, role)
                         VALUES (98, 'Bad Code User', 'bad@test.com', 'hash', '888888888888', '0188888888', 'admin')");

        $tfa->generateSecret(98, 'bad@test.com');

        // Use a guaranteed-wrong code
        $this->assertFalse($tfa->verifyCode(98, '000000'));
    }

    public function testVerifyCodeWithLegacyPlaintextSecret()
    {
        // Simulate a legacy unencrypted secret stored directly in the DB
        $totp = \OTPHP\TOTP::generate();
        $plainSecret = $totp->getSecret();

        $realPdo = $this->createRealSqlitePdo();
        $tfa = new TwoFactorAuth($realPdo);

        // Insert user with plaintext secret (legacy behaviour)
        $realPdo->exec("INSERT INTO users (id, full_name, email, password_hash, ic_number, phone, role, totp_secret)
                         VALUES (97, 'Legacy User', 'legacy@test.com', 'hash', '777777777777', '0177777777', 'admin', '$plainSecret')");

        $verifier = \OTPHP\TOTP::createFromSecret($plainSecret);
        $validCode = $verifier->now();

        // Should still work — decryptSecret falls back to plaintext for legacy values
        $this->assertTrue($tfa->verifyCode(97, $validCode));
    }

    // ─── isEnabled ──────────────────────────────────────────────────

    public function testIsEnabled()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(1);
        $this->assertTrue($this->tfa->isEnabled(1));
    }

    public function testIsNotEnabled()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('fetchColumn')->willReturn(0);
        $this->assertFalse($this->tfa->isEnabled(1));
    }

    // ─── disable ────────────────────────────────────────────────────

    public function testDisable()
    {
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->expects($this->once())->method('execute')->with([1]);
        $this->tfa->disable(1);
    }

    public function testDisableClearsSecretAndFlag()
    {
        $realPdo = $this->createRealSqlitePdo();
        $tfa = new TwoFactorAuth($realPdo);

        $realPdo->exec("INSERT INTO users (id, full_name, email, password_hash, ic_number, phone, role, totp_secret, totp_enabled)
                         VALUES (96, 'Disable User', 'disable@test.com', 'hash', '666666666666', '0166666666', 'admin', 'some_secret', 1)");

        $tfa->disable(96);

        $row = $realPdo->query("SELECT totp_secret, totp_enabled FROM users WHERE id = 96")->fetch();
        $this->assertNull($row['totp_secret']);
        $this->assertEquals(0, (int) $row['totp_enabled']);
    }

    // ─── encrypt / decrypt round-trip ──────────────────────────────

    public function testEncryptDecryptRoundTrip()
    {
        // Use reflection to test the private encrypt/decrypt methods directly
        $encrypt = new \ReflectionMethod(TwoFactorAuth::class, 'encryptSecret');
        $decrypt = new \ReflectionMethod(TwoFactorAuth::class, 'decryptSecret');

        $originalSecret = 'JBSWY3DPEHPK3PXP';
        $encrypted = $encrypt->invoke(null, $originalSecret);

        // Encrypted value should be different from plaintext
        $this->assertNotEquals($originalSecret, $encrypted);
        // Encrypted value should be valid base64
        $this->assertNotFalse(base64_decode($encrypted, true));

        // Decryption should return the original secret
        $decrypted = $decrypt->invoke(null, $encrypted);
        $this->assertEquals($originalSecret, $decrypted);
    }

    public function testEncryptProducesDifferentCiphertextEachTime()
    {
        $encrypt = new \ReflectionMethod(TwoFactorAuth::class, 'encryptSecret');

        $secret = 'JBSWY3DPEHPK3PXP';
        $enc1 = $encrypt->invoke(null, $secret);
        $enc2 = $encrypt->invoke(null, $secret);

        // Random IV should produce different ciphertexts
        $this->assertNotEquals($enc1, $enc2);
    }

    public function testDecryptFallsBackForLegacyPlaintext()
    {
        $decrypt = new \ReflectionMethod(TwoFactorAuth::class, 'decryptSecret');

        // A short plaintext string that doesn't look like base64(iv+tag+ciphertext)
        $legacy = 'JBSWY3DPEHPK3PXP';
        $result = $decrypt->invoke(null, $legacy);
        $this->assertEquals($legacy, $result);
    }

    // ─── getEncryptionKey edge case ────────────────────────────────

    public function testGetEncryptionKeyThrowsWithoutEnvVar()
    {
        // Temporarily clear relevant env vars
        $origApp = getenv('APP_KEY');
        $origGemini = getenv('GEMINI_API_KEY');
        putenv('APP_KEY');
        putenv('GEMINI_API_KEY');

        try {
            $getKey = new \ReflectionMethod(TwoFactorAuth::class, 'getEncryptionKey');
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('APP_KEY or GEMINI_API_KEY must be set');
            $getKey->invoke(null);
        } finally {
            // Restore env vars
            if ($origApp !== false) {
                putenv("APP_KEY=$origApp");
            }
            if ($origGemini !== false) {
                putenv("GEMINI_API_KEY=$origGemini");
            }
        }
    }

    // ─── Helper: real SQLite PDO for integration-style tests ───────

    private function createRealSqlitePdo(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL,
            password_hash TEXT NOT NULL,
            ic_number TEXT NOT NULL,
            phone TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'student',
            totp_secret TEXT DEFAULT NULL,
            totp_enabled INTEGER DEFAULT 0
        )");
        return $pdo;
    }
}
