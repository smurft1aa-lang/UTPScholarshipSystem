<?php
require_once __DIR__ . '/../includes/init.php';
requireVerified();

$db = getDB();
$userId = $_SESSION['user_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $docType = $_POST['doc_type'] ?? '';
        $allowedTypes = ['ic', 'certificate', 'photo'];
        
        if (!in_array($docType, $allowedTypes)) {
            $error = 'Invalid document type.';
        } elseif (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->file($_FILES['document']['tmp_name']);
            
            $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!in_array($mimeType, $allowedMimes)) {
                $error = 'Invalid file type. Only JPG, PNG, and PDF are allowed.';
            } elseif ($_FILES['document']['size'] > 2097152) { // 2MB
                $error = 'File size exceeds 2MB limit.';
            } else {
                // Ensure upload dir exists
                $uploadDir = __DIR__ . '/../' . (getenv('UPLOAD_DIR') ?: 'uploads/documents') . '/' . $userId;
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                
                // Check for PHP files
                if (preg_match('/\.php/i', $_FILES['document']['name'])) {
                    $error = 'PHP files are not allowed.';
                } else {
                    $newName = $userId . '_' . $docType . '_' . time() . '.' . $ext;
                    $targetPath = $uploadDir . '/' . $newName;
                    
                    if (move_uploaded_file($_FILES['document']['tmp_name'], $targetPath)) {
                        // Check if already exists
                        $stmt = $db->prepare("SELECT id, filename FROM documents WHERE user_id = ? AND doc_type = ?");
                        $stmt->execute([$userId, $docType]);
                        $existing = $stmt->fetch();
                        
                        if ($existing) {
                            // Delete old file
                            $oldPath = $uploadDir . '/' . $existing['filename'];
                            if (file_exists($oldPath)) unlink($oldPath);
                            
                            $stmt = $db->prepare("UPDATE documents SET filename = ?, original_name = ?, file_size = ?, uploaded_at = NOW() WHERE id = ?");
                            $stmt->execute([$newName, $_FILES['document']['name'], $_FILES['document']['size'], $existing['id']]);
                        } else {
                            $stmt = $db->prepare("INSERT INTO documents (user_id, doc_type, filename, original_name, file_size) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$userId, $docType, $newName, $_FILES['document']['name'], $_FILES['document']['size']]);
                        }
                        
                        logAudit($userId, 'Document Uploaded', 'Document', $docType);
                        
                        $success = 'Document uploaded successfully.';
                    } else {
                        $error = 'Failed to save the uploaded file.';
                    }
                }
            }
        } else {
            $error = 'Please select a valid file to upload.';
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
    
    <?php if ($error): ?><div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card mb-6">
        <h3 style="margin-bottom:16px;">Required Documents</h3>
        <p style="color:var(--text-secondary); margin-bottom:20px;">Allowed formats: PDF, JPG, PNG. Max size: 2MB per file.</p>
        
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
                    <input type="file" name="document" id="document" class="form-input" accept=".pdf,.jpg,.jpeg,.png" required style="padding:10px;">
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
                        <th style="text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); background: var(--bg-page);">Requirement</th>
                        <th style="text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); background: var(--bg-page);">Status</th>
                        <th style="text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); background: var(--bg-page);">Filename</th>
                        <th style="text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); background: var(--bg-page);">Uploaded At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $types = [
                        'ic' => 'IC/Passport Scan',
                        'certificate' => 'Academic Certificate (SPM/O-Level/etc)',
                        'photo' => 'Passport Photo'
                    ];
                    foreach ($types as $key => $label):
                        $isUploaded = isset($docs[$key]);
                    ?>
                    <tr>
                        <td style="padding: 14px 20px; font-size: 0.9rem; border-bottom: 1px solid var(--border-light);"><strong><?= $label ?></strong></td>
                        <td style="padding: 14px 20px; font-size: 0.9rem; border-bottom: 1px solid var(--border-light);">
                            <?php if ($isUploaded): ?>
                                <span class="badge badge-green">Uploaded</span>
                            <?php else: ?>
                                <span class="badge badge-red">Missing</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 14px 20px; font-size: 0.9rem; border-bottom: 1px solid var(--border-light);"><?= $isUploaded ? htmlspecialchars($docs[$key]['original_name']) : '-' ?></td>
                        <td style="padding: 14px 20px; font-size: 0.9rem; border-bottom: 1px solid var(--border-light);"><?= $isUploaded ? date('d M Y, h:i A', strtotime($docs[$key]['uploaded_at'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
