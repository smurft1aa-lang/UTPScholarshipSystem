<?php

use PHPUnit\Framework\TestCase;

if (!defined('APP_ENV')) {
    define('APP_ENV', 'testing');
}

class DocumentTest extends TestCase
{
    private string $uploadDir;
    private int $testUserId = 2;

    protected function setUp(): void
    {
        $this->uploadDir = __DIR__ . '/../uploads/documents/test_tmp/';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {

        // Clean up test uploads
        $files = glob($this->uploadDir . '*');
        if ($files) {
            foreach ($files as $f) {
                    unlink($f);
            }
        }
        if (is_dir($this->uploadDir)) {
            rmdir($this->uploadDir);
        }
    }

    private function makeFakeUpload(string $name, string $type, int $sizeBytes, string $content = 'fake'): array
    {
        return [
            'name'     => $name,
            'type'     => $type,
            'size'     => $sizeBytes,
            'tmp_name' => '',
            'error'    => UPLOAD_ERR_OK,
            '_content' => $content,
        ];
    }

    private function validateUpload(array $file): array
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxSize = 2 * 1024 * 1024;
// 2MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Upload error.'];
        }
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'File exceeds 2MB limit.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            return ['valid' => false, 'error' => 'File type not allowed.'];
        }
        if (!in_array($file['type'], $allowedMimes)) {
            return ['valid' => false, 'error' => 'MIME type not allowed.'];
        }

        return ['valid' => true];
    }

    private function generateStoredFilename(int $userId, string $docType, string $ext): string
    {
        return "{$userId}_{$docType}_" . time() . ".{$ext}";
    }

    public function test_upload_fails_for_disallowed_extension(): void
    {
        $file = $this->makeFakeUpload('shell.exe', 'application/octet-stream', 1024);
        $result = $this->validateUpload($file);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('not allowed', $result['error']);
    }

    public function test_upload_fails_for_php_extension(): void
    {
        $file = $this->makeFakeUpload('shell.php', 'application/x-php', 1024);
        $result = $this->validateUpload($file);
        $this->assertFalse($result['valid']);
    }

    public function test_upload_fails_if_mime_type_spoofed(): void
    {
        // .jpg extension but PHP MIME
        $file = $this->makeFakeUpload('photo.jpg', 'text/x-php', 512);
        $result = $this->validateUpload($file);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('MIME', $result['error']);
    }

    public function test_upload_fails_if_file_exceeds_2mb(): void
    {
        $file = $this->makeFakeUpload('big.pdf', 'application/pdf', 3 * 1024 * 1024);
        $result = $this->validateUpload($file);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('2MB', $result['error']);
    }

    public function test_upload_succeeds_for_valid_pdf(): void
    {
        $file = $this->makeFakeUpload('cert.pdf', 'application/pdf', 500 * 1024);
        $result = $this->validateUpload($file);
        $this->assertTrue($result['valid']);
    }

    public function test_upload_succeeds_for_valid_jpg(): void
    {
        $file = $this->makeFakeUpload('photo.jpg', 'image/jpeg', 800 * 1024);
        $result = $this->validateUpload($file);
        $this->assertTrue($result['valid']);
    }

    public function test_upload_succeeds_for_valid_png(): void
    {
        $file = $this->makeFakeUpload('ic.png', 'image/png', 1 * 1024 * 1024);
        $result = $this->validateUpload($file);
        $this->assertTrue($result['valid']);
    }

    public function test_uploaded_filename_never_uses_original_name(): void
    {
        $original = 'my_secret_document.pdf';
        $stored = $this->generateStoredFilename($this->testUserId, 'certificate', 'pdf');
        $this->assertStringNotContainsString('my_secret_document', $stored);
        $this->assertStringStartsWith("{$this->testUserId}_certificate_", $stored);
    }

    public function test_stored_filename_format_is_correct(): void
    {
        $stored = $this->generateStoredFilename(5, 'ic', 'png');
        $this->assertMatchesRegularExpression('/^5_ic_\d+\.png$/', $stored);
    }

    public function test_path_traversal_in_filename_is_blocked(): void
    {
        $file = $this->makeFakeUpload('../../config/.env', 'application/pdf', 1024);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
// .env has no extension so ext will be empty string — blocked
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $this->assertNotContains($ext, $allowedExtensions);
    }

    public function test_upload_at_exact_2mb_limit_is_accepted(): void
    {
        $file = $this->makeFakeUpload('exactly2mb.pdf', 'application/pdf', 2 * 1024 * 1024);
        $result = $this->validateUpload($file);
        $this->assertTrue($result['valid']);
    }

    public function test_upload_1_byte_over_2mb_is_rejected(): void
    {
        $file = $this->makeFakeUpload('over2mb.pdf', 'application/pdf', 2 * 1024 * 1024 + 1);
        $result = $this->validateUpload($file);
        $this->assertFalse($result['valid']);
    }

    // ─── Header Injection (CRLF) Tests ────────────────────────────────

    /**
     * Verify that the download filename sanitizer strips CRLF sequences.
     * A raw basename() call does NOT remove \r\n, so without sanitization
     * a crafted original_name injects arbitrary HTTP response headers.
     */
    public function test_download_filename_strips_crlf_injection(): void
    {
        $malicious = "evil.pdf\r\nSet-Cookie: session=hijacked";
        $safe = $this->sanitizeDownloadFilename($malicious);
        $this->assertStringNotContainsString("\r", $safe);
        $this->assertStringNotContainsString("\n", $safe);
        $this->assertStringContainsString('evil.pdf', $safe);
    }

    public function test_download_filename_strips_lf_only_injection(): void
    {
        $malicious = "report.pdf\nX-Injected: header";
        $safe = $this->sanitizeDownloadFilename($malicious);
        $this->assertStringNotContainsString("\n", $safe);
    }

    public function test_download_filename_strips_null_bytes(): void
    {
        $malicious = "file\x00.pdf";
        $safe = $this->sanitizeDownloadFilename($malicious);
        $this->assertStringNotContainsString("\x00", $safe);
    }

    public function test_download_filename_strips_all_control_characters(): void
    {
        // Build a name with every ASCII control character (0x00–0x1f and 0x7f)
        $controls = '';
        for ($i = 0; $i <= 0x1f; $i++) {
            $controls .= chr($i);
        }
        $controls .= chr(0x7f);
        $malicious = 'file' . $controls . '.pdf';
        $safe = $this->sanitizeDownloadFilename($malicious);
        $this->assertMatchesRegularExpression('/^[\x20-\x7e]+$/', $safe);
    }

    public function test_download_filename_falls_back_when_result_is_empty(): void
    {
        // A name consisting entirely of control characters should fall back to 'document'
        $malicious = "\r\n\x00\x1f";
        $safe = $this->sanitizeDownloadFilename($malicious);
        $this->assertSame('document', $safe);
    }

    public function test_download_filename_preserves_normal_name(): void
    {
        $normal = 'my_certificate_2024.pdf';
        $safe = $this->sanitizeDownloadFilename($normal);
        $this->assertSame($normal, $safe);
    }

    public function test_download_filename_preserves_unicode_name(): void
    {
        $unicode = 'sijil_peperiksaan_2024.pdf';
        $safe = $this->sanitizeDownloadFilename($unicode);
        $this->assertSame($unicode, $safe);
    }

    // ─── UPLOAD_DIR Whitelist Tests ────────────────────────────────────

    public function test_upload_dir_whitelist_accepts_known_safe_dirs(): void
    {
        $allowed = ['uploads/documents', 'uploads', 'storage/documents'];
        foreach ($allowed as $dir) {
            $resolved = $this->resolveUploadDir($dir);
            $this->assertSame($dir, $resolved, "Expected '$dir' to be accepted by whitelist");
        }
    }

    public function test_upload_dir_whitelist_rejects_traversal_attempt(): void
    {
        $resolved = $this->resolveUploadDir('../../etc/passwd');
        $this->assertSame('uploads/documents', $resolved);
    }

    public function test_upload_dir_whitelist_rejects_arbitrary_path(): void
    {
        $resolved = $this->resolveUploadDir('/var/www/html/secret');
        $this->assertSame('uploads/documents', $resolved);
    }

    public function test_upload_dir_whitelist_uses_default_when_env_empty(): void
    {
        $resolved = $this->resolveUploadDir('');
        $this->assertSame('uploads/documents', $resolved);
    }

    // ─── Helpers (mirror production logic) ────────────────────────────

    /**
     * Mirrors the sanitization applied in admin/download-document.php
     * before writing the Content-Disposition filename.
     */
    private function sanitizeDownloadFilename(string $originalName): string
    {
        $safe = preg_replace('/[\x00-\x1f\x7f]/', '', basename($originalName));
        return ($safe !== '' && $safe !== null) ? $safe : 'document';
    }

    /**
     * Mirrors the UPLOAD_DIR whitelist resolution in admin/download-document.php.
     */
    private function resolveUploadDir(string $envValue): string
    {
        $allowedUploadDirs = ['uploads/documents', 'uploads', 'storage/documents'];
        return in_array($envValue, $allowedUploadDirs, true) ? $envValue : 'uploads/documents';
    }
}
