<?php
require_once __DIR__ . '/../includes/init.php';
requireVerified();

$db = getDB();
$userId = $_SESSION['user_id'];

$error = '';
$success = '';

if (isset($_GET['error']) && $_GET['error'] === 'missing_docs') {
    $error = 'You must securely upload both your IC/Passport scan and Passport Photo before you can access the AI Eligibility Engine.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $docType = $_POST['doc_type'] ?? '';
        $allowedTypes = ['ic', 'certificate', 'photo'];

        if (!in_array($docType, $allowedTypes)) {
            $error = 'Invalid document type.';

            // ── FIX 4: Replace single UPLOAD_ERR_OK check with a full error map ──────────
        } elseif (!isset($_FILES['document'])) {
            $error = 'No file was received by the server.';
        } else {
            $uploadErrorCode = $_FILES['document']['error'];

            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds the server\'s maximum allowed size. Please upload a file under 2MB.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds the form\'s maximum allowed size.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_FILE => 'No file was selected.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server upload folder is missing. Please contact support.',
                UPLOAD_ERR_CANT_WRITE => 'Server failed to write the file to disk. Please contact support.',
                UPLOAD_ERR_EXTENSION => 'The upload was blocked by a server extension.',
            ];

            if ($uploadErrorCode !== UPLOAD_ERR_OK) {
                // Known PHP upload error — show a clear message
                $error = $uploadErrors[$uploadErrorCode] ?? 'An unknown upload error occurred (code ' . $uploadErrorCode . '). Please try again.';

            } else {
                // ── MIME type check (reads actual file bytes, not user-supplied header) ──
                $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $fileInfo->file($_FILES['document']['tmp_name']);

                $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];

                if (!in_array($mimeType, $allowedMimes)) {
                    $error = 'Invalid file type. Only JPG, PNG, and PDF are allowed.';

                } elseif ($_FILES['document']['size'] > 2097152) { // 2MB
                    $error = 'File size exceeds 2MB limit.';

                } else {
                    // ── FIX 1: Validate that image files are structurally valid images ────
                    // getimagesize() actually decodes the image structure, catching crafted
                    // files that fake a MIME type while containing a malicious payload.
                    if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
                        $imageInfo = @getimagesize($_FILES['document']['tmp_name']);
                        if ($imageInfo === false) {
                            $error = 'The uploaded image is corrupt or not a valid image file. Please upload a proper JPG or PNG.';
                        }
                    }

                    if (empty($error)) {
                        // ── Ensure upload dir exists ─────────────────────────────────────
                        $uploadDir = __DIR__ . '/../' . (getenv('UPLOAD_DIR') ?: 'uploads/documents') . '/' . $userId;

                        if (!is_dir($uploadDir)) {
                            // ── FIX 2: Use 0700 instead of 0755 ─────────────────────────
                            // 0755 allows the server group and all other system users to
                            // read files. IC scans and certificates are sensitive identity
                            // documents — only the web server user should have access.
                            mkdir($uploadDir, 0700, true);
                        }

                        $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                        $originalName = $_FILES['document']['name'];

                        // ── Whitelist: only known-safe extensions ────────────────────────
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

                        // ── FIX 3: Broaden the dangerous-pattern regex ───────────────────
                        // The old pattern used php[3-8] which only matches single-digit
                        // suffixes. php\d* matches php, php3, php8, php81, php82, etc.,
                        // closing the gap for all current and future PHP minor versions.
                        $dangerousPatterns = '/\.(php\d*|phtml|phar|shtml|cgi|pl|py|sh|bash|exe|bat|cmd)/i';
                        $hasDoubleExtension = substr_count($originalName, '.') > 1;

                        if (!in_array($ext, $allowedExtensions, true)) {
                            $error = 'Invalid file extension. Only JPG, PNG, and PDF files are allowed.';
                        } elseif ($hasDoubleExtension && preg_match($dangerousPatterns, $originalName)) {
                            $error = 'File contains a suspicious double extension and was rejected.';
                        } else {
                            $newName = $userId . '_' . $docType . '_' . time() . '.' . $ext;
                            $targetPath = $uploadDir . '/' . $newName;

                            if (move_uploaded_file($_FILES['document']['tmp_name'], $targetPath)) {
                                // Check if a document of this type already exists for the user
                                $stmt = $db->prepare("SELECT id, filename FROM documents WHERE user_id = ? AND doc_type = ?");
                                $stmt->execute([$userId, $docType]);
                                $existing = $stmt->fetch();

                                if ($existing) {
                                    // Delete the old file from disk before replacing the DB record
                                    $oldPath = $uploadDir . '/' . $existing['filename'];
                                    if (file_exists($oldPath)) {
                                        unlink($oldPath);
                                    }

                                    $stmt = $db->prepare("UPDATE documents SET filename = ?, original_name = ?, file_size = ?, uploaded_at = NOW() WHERE id = ?");
                                    $stmt->execute([$newName, $originalName, $_FILES['document']['size'], $existing['id']]);
                                } else {
                                    $stmt = $db->prepare("INSERT INTO documents (user_id, doc_type, filename, original_name, file_size) VALUES (?, ?, ?, ?, ?)");
                                    $stmt->execute([$userId, $docType, $newName, $originalName, $_FILES['document']['size']]);
                                }

                                logAudit($userId, 'Document Uploaded', 'Document', null, "Type: $docType");

                                $success = 'Document uploaded successfully.';
                            } else {
                                $error = 'Failed to save the uploaded file. Please try again.';
                            }
                        }
                    }
                }
            }
        }
    }
}

// Get current docs
$stmt = $db->prepare("SELECT doc_type, original_name, uploaded_at FROM documents WHERE user_id = ?");
$stmt->execute([$userId]);
$docs = [];
while ($row = $stmt->fetch()) {
    $docs[$row['doc_type']] = $row;
}

$pageTitle = 'Upload Documents — UTP Scholarship System';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:32px; padding-bottom:48px;">
    <div class="page-header">
        <h1>Upload Documents</h1>
        <p>Please upload the required supporting documents for your application.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card mb-6">
        <h3 style="margin-bottom:16px;">Required Documents</h3>
        <p style="color:var(--text-secondary); margin-bottom:20px;">Allowed formats: PDF, JPG, PNG. Max size: 2MB per
            file.</p>

        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="doc_type">Document Type</label>
                    <select name="doc_type" id="doc_type" class="form-select" required>
                        <option value="">-- Select Type --</option>
                        <option value="ic">IC/Passport Scan</option>
                        <option value="certificate">Academic Certificate (SPM/O-Level/etc)</option>
                        <option value="photo">Passport Photo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="document">Select File</label>
                    <input type="file" name="document" id="document" class="form-input" accept=".pdf,.jpg,.jpeg,.png"
                        required style="padding:10px;">
                </div>
            </div>
            <button type="submit" class="btn btn-orange">Upload Selected Document</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-bottom:16px;">Uploaded Documents Status</h3>
        <div class="table-responsive">
            <table class="table" style="width: 100%; min-width: 600px; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th
                            style="text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); background: var(--bg-page);">
                            Requirement</th>
                        <th
                            style="text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); background: var(--bg-page);">
                            Status</th>
                        <th
                            style="text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); background: var(--bg-page);">
                            Filename</th>
                        <th
                            style="text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); background: var(--bg-page);">
                            Uploaded At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $types = [
                        'ic' => 'IC/Passport Scan',
                        'certificate' => 'Academic Certificate (SPM/O-Level/etc)',
                        'photo' => 'Passport Photo',
                    ];
                    foreach ($types as $key => $label):
                        $isUploaded = isset($docs[$key]);
                        ?>
                        <tr>
                            <td
                                style="padding: 14px 20px; font-size: 0.9rem; border-bottom: 1px solid var(--border-light);">
                                <strong><?= $label ?></strong>
                            </td>
                            <td
                                style="padding: 14px 20px; font-size: 0.9rem; border-bottom: 1px solid var(--border-light);">
                                <?php if ($isUploaded): ?>
                                    <span class="badge badge-green">Uploaded</span>
                                <?php else: ?>
                                    <span class="badge badge-red">Missing</span>
                                <?php endif; ?>
                            </td>
                            <td
                                style="padding: 14px 20px; font-size: 0.9rem; border-bottom: 1px solid var(--border-light);">
                                <?= $isUploaded ? htmlspecialchars($docs[$key]['original_name']) : '-' ?>
                            </td>
                            <td
                                style="padding: 14px 20px; font-size: 0.9rem; border-bottom: 1px solid var(--border-light);">
                                <?= $isUploaded ? date('d M Y, h:i A', strtotime($docs[$key]['uploaded_at'])) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>