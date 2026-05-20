<?php
/**
 * Admin Document Download Endpoint
 *
 * Streams a student's uploaded document to the admin browser.
 * Output buffering is started immediately to prevent any stray
 * output from corrupting the binary file stream.
 */
ob_start();

require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$targetUserId = (int)($_GET['user_id'] ?? 0);
$docType = sanitize($_GET['doc_type'] ?? '');

$validTypes = ['ic', 'photo', 'certificate'];
if (!in_array($docType, $validTypes) || $targetUserId <= 0) {
    ob_end_clean();
    http_response_code(400);
    echo "Invalid request parameters.";
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT filename, original_name, doc_type FROM documents WHERE user_id = ? AND doc_type = ?");
$stmt->execute([$targetUserId, $docType]);
$doc = $stmt->fetch();

if (!$doc) {
    ob_end_clean();
    http_response_code(404);
    echo "Document not found.";
    exit;
}

// Whitelist UPLOAD_DIR against known-safe values (mirrors the upload endpoint guard).
$allowedUploadDirs = ['uploads/documents', 'uploads', 'storage/documents'];
$envUploadDir = getenv('UPLOAD_DIR') ?: '';
$uploadSubdir = in_array($envUploadDir, $allowedUploadDirs, true) ? $envUploadDir : 'uploads/documents';
$uploadDir = realpath(__DIR__ . '/../') . '/' . $uploadSubdir . '/' . $targetUserId;
$filePath = $uploadDir . '/' . $doc['filename'];

if (!file_exists($filePath)) {
    ob_end_clean();
    http_response_code(404);
    echo "Physical file is missing from server.";
    exit;
}

// Log admin action securely
logAudit($_SESSION['user_id'], 'Admin Downloaded Document', 'Document', null, "Target User ID: $targetUserId, Type: $docType");

// Extract mime type safely
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);

// Discard ALL buffered output (session headers, CSP, etc.) so only raw bytes are sent
ob_end_clean();

// Sanitize the download filename to prevent HTTP response header injection (CRLF).
// basename() does NOT strip \r or \n, so a crafted original_name like
// "file.pdf\r\nSet-Cookie: x=y" would inject arbitrary headers.
// Strip control characters and non-printable bytes, then fall back to a safe default.
$safeDownloadName = preg_replace('/[\x00-\x1f\x7f]/', '', basename((string) $doc['original_name']));
$safeDownloadName = str_replace('"', '', $safeDownloadName);
$safeDownloadName = $safeDownloadName !== '' ? $safeDownloadName : 'document';

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $safeDownloadName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;
