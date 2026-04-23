<?php

declare(strict_types=1);

/**
 * API: OCR Result Scanning
 *
 * Accepts an uploaded result slip image/PDF, runs OCR via OCR.space,
 * and returns extracted subject-grade pairs as JSON.
 */

require_once __DIR__ . '/../includes/init.php';

setSecurityHeaders();
initSession();

header('Content-Type: application/json; charset=utf-8');

// ── Auth check ──────────────────────────────────────────────────────
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// ── CSRF validation ─────────────────────────────────────────────────
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid form submission.']);
    exit;
}

// ── Per-user OCR rate limit (5 scans per 10 minutes) ────────────────
if (!checkRateLimit('ocr_' . $_SESSION['user_id'], 5, 10)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'You have reached the OCR scan limit. Please wait a few minutes before trying again.', 'new_csrf_token' => generateCSRFToken()]);
    exit;
}
recordLoginAttempt('ocr_' . $_SESSION['user_id']);

// ── Validate qualification type ─────────────────────────────────────
$qualType = sanitize($_POST['qual_type'] ?? '');
if (!in_array($qualType, ['SPM', 'O-Level', 'IGCSE'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please select a valid qualification type.', 'new_csrf_token' => generateCSRFToken()]);
    exit;
}

// ── Validate file upload ────────────────────────────────────────────
if (!isset($_FILES['result_slip']) || is_array($_FILES['result_slip']['error'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded.', 'new_csrf_token' => generateCSRFToken()]);
    exit;
}

$uploadError = $_FILES['result_slip']['error'];
if ($uploadError !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds maximum size.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds maximum size.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was selected.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error.',
        UPLOAD_ERR_CANT_WRITE => 'Server could not write file.',
    ];
    $msg = $errorMessages[$uploadError] ?? 'Upload failed.';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $msg, 'new_csrf_token' => generateCSRFToken()]);
    exit;
}

// ── MIME type validation ────────────────────────────────────────────
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($_FILES['result_slip']['tmp_name']);
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'];

if (!in_array($mimeType, $allowedMimes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type. JPG, PNG, WebP, HEIC, and PDF are allowed.', 'new_csrf_token' => generateCSRFToken()]);
    exit;
}

// ── File size check (5MB limit for Gemini API) ─────────────
if ($_FILES['result_slip']['size'] > 5242880) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit. Please compress or crop your image.', 'new_csrf_token' => generateCSRFToken()]);
    exit;
}

// ── Image structural validation (same as document upload) ───────────
if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
    $imageInfo = @getimagesize($_FILES['result_slip']['tmp_name']);
    if ($imageInfo === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'The uploaded image is corrupt or not a valid image file.', 'new_csrf_token' => generateCSRFToken()]);
        exit;
    }
}

// ── Call OCR Service ────────────────────────────────────────────────
try {
    $ocrService = new \UTP\Services\OcrService();
    $result = $ocrService->extractGrades(
        $_FILES['result_slip']['tmp_name'],
        $mimeType,
        $qualType
    );

    $userId = $_SESSION['user_id'];
    
    // Save to session for audit logging of student corrections later
    $_SESSION['ocr_last_result'] = $result['grades'];

    logAudit($userId, 'OCR Result Scan', 'Qualification', null, "Type: $qualType, Grades found: " . count($result['grades']));
    trackEvent('OCR Result Scan', [
        'user_id'      => $userId,
        'qual_type'    => $qualType,
        'grades_found' => count($result['grades']),
    ]);

    // Auto-save the uploaded OCR result slip as the official "certificate" document
    $db = getDB();
    $uploadDir = realpath(__DIR__ . '/../') . '/' . (getenv('UPLOAD_DIR') ?: 'uploads/documents') . '/' . $userId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0700, true);
    }
    
    $docType = 'certificate';
    $originalName = $_FILES['result_slip']['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg');
    $newName = $userId . '_' . $docType . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . '/' . $newName;

    if (move_uploaded_file($_FILES['result_slip']['tmp_name'], $targetPath)) {
        $stmt = $db->prepare("SELECT id, filename FROM documents WHERE user_id = ? AND doc_type = ?");
        $stmt->execute([$userId, $docType]);
        $existing = $stmt->fetch();

        if ($existing) {
            $oldPath = $uploadDir . '/' . $existing['filename'];
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
            $stmt = $db->prepare("UPDATE documents SET filename = ?, original_name = ?, file_size = ?, uploaded_at = NOW() WHERE id = ?");
            $stmt->execute([$newName, $originalName, $_FILES['result_slip']['size'], $existing['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO documents (user_id, doc_type, filename, original_name, file_size) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $docType, $newName, $originalName, $_FILES['result_slip']['size']]);
        }
        logAudit($userId, 'Document Auto-Uploaded from OCR', 'Document', null, 'Type: certificate');
    }

    echo json_encode([
        'success'  => true,
        'grades'   => $result['grades'],
        'raw_text' => $result['raw_text'],
        'count'    => count($result['grades']),
        'new_csrf_token' => generateCSRFToken(),
    ]);

} catch (\RuntimeException $e) {
    \UTP\Services\Telemetry::trackEvent('OCR Scan Failed', [
        'error'   => $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? 0,
    ], 'ERROR');

    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'new_csrf_token' => generateCSRFToken(),
    ]);
} catch (\Exception $e) {
    \UTP\Services\Telemetry::trackEvent('OCR Scan Exception', [
        'error'   => $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? 0,
    ], 'ERROR');

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'OCR processing failed. Please try again or use manual entry.',
        'new_csrf_token' => generateCSRFToken(),
    ]);
}
