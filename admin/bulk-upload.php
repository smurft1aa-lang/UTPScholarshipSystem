<?php
/**
 * Admin: Bulk CSV Upload
 *
 * Allows administrators to import students, programmes, or scholarships
 * from CSV files. Shows a preview before confirming the import.
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$db = getDB();
$importer = new \UTP\Services\BulkCsvImporter($db);

$error = '';
$success = '';
$importResult = null;
$preview = null;
$uploadedFile = null;
$entityType = '';

// Handle file upload and import
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $entityType = $_POST['entity_type'] ?? '';
        $validEntities = ['students', 'programmes', 'scholarships'];

        if (!in_array($entityType, $validEntities, true)) {
            $error = 'Please select a valid import type.';
        } elseif (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            // Validate file
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->file($_FILES['csv_file']['tmp_name']);
            $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];

            if (!in_array($mimeType, $allowedMimes, true)) {
                $error = 'Invalid file type. Only CSV files are allowed.';
            } elseif ($_FILES['csv_file']['size'] > 5242880) { // 5MB
                $error = 'File size exceeds 5MB limit.';
            } else {
                $tmpPath = $_FILES['csv_file']['tmp_name'];

                // Step 1: Preview mode (no 'confirm' flag yet)
                if (!isset($_POST['confirm'])) {
                    $preview = $importer->preview($tmpPath);
                    // Save temp file for confirmation step
                    $uploadDir = sys_get_temp_dir();
                    $savedPath = $uploadDir . '/utp_csv_' . session_id() . '_' . time() . '.csv';
                    move_uploaded_file($tmpPath, $savedPath);
                    $_SESSION['csv_import_path'] = $savedPath;
                    $_SESSION['csv_import_type'] = $entityType;
                }
            }
        } elseif (isset($_POST['confirm']) && isset($_SESSION['csv_import_path'])) {
            // Step 2: Confirmed import
            $savedPath = $_SESSION['csv_import_path'];
            $entityType = $_SESSION['csv_import_type'] ?? '';

            if (file_exists($savedPath)) {
                switch ($entityType) {
                    case 'students':
                        $importResult = $importer->importStudents($savedPath);
                        break;
                    case 'programmes':
                        $importResult = $importer->importProgrammes($savedPath);
                        break;
                    case 'scholarships':
                        $importResult = $importer->importScholarships($savedPath);
                        break;
                }

                // Cleanup temp file
                @unlink($savedPath);
                unset($_SESSION['csv_import_path'], $_SESSION['csv_import_type']);

                if ($importResult && $importResult['success']) {
                    logAudit(
                        $_SESSION['user_id'],
                        'Bulk CSV Import',
                        ucfirst($entityType),
                        null,
                        "Imported: {$importResult['imported']}, Skipped: {$importResult['skipped']}"
                    );
                    $success = "Import complete: {$importResult['imported']} records imported, {$importResult['skipped']} skipped.";
                } else {
                    $error = 'Import failed. ' . ($importResult['errors'][0] ?? '');
                }
            } else {
                $error = 'Upload session expired. Please upload the file again.';
            }
        } else {
            $error = 'Please select a CSV file to upload.';
        }
    }
}

require_once __DIR__ . '/admin_header.php';
?>

<div class="page-header">
    <h1>Bulk CSV Upload</h1>
    <p>Import students, programmes, or scholarships from a CSV file.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Import Result Details -->
<?php if ($importResult && !empty($importResult['errors'])): ?>
<div class="card mb-6">
    <h3 style="font-size:1rem; font-weight:600; margin-bottom:12px; color:var(--text-warning, #e8630a);">Import Warnings</h3>
    <div style="max-height:200px; overflow-y:auto; font-size:0.85rem; font-family:monospace; background:var(--bg-page); padding:12px; border-radius:8px;">
        <?php foreach ($importResult['errors'] as $err): ?>
            <div style="padding:2px 0; color:var(--text-secondary);"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Preview Mode -->
<?php if ($preview && !empty($preview['headers'])): ?>
<div class="card mb-6">
    <h3 style="font-size:1rem; font-weight:600; margin-bottom:12px;">Preview (first <?= count($preview['rows']) ?> rows)</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php foreach ($preview['headers'] as $h): ?>
                        <th><?= htmlspecialchars($h) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preview['rows'] as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <td><?= htmlspecialchars($cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="POST" style="margin-top:16px;">
        <?= csrfField() ?>
        <input type="hidden" name="entity_type" value="<?= htmlspecialchars($entityType) ?>">
        <input type="hidden" name="confirm" value="1">
        <div class="flex gap-2">
            <button type="submit" class="btn btn-purple">Confirm Import</button>
            <a href="bulk-upload.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Upload Form -->
<?php if (!$preview): ?>
<div class="card mb-6">
    <h3 style="font-size:1rem; font-weight:600; margin-bottom:16px;">Upload CSV File</h3>
    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="entity_type">Import Type</label>
                <select name="entity_type" id="entity_type" class="form-select admin-focus" required>
                    <option value="">-- Select Type --</option>
                    <option value="students">Students</option>
                    <option value="programmes">Programmes</option>
                    <option value="scholarships">Scholarships</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="csv_file">CSV File (max 5MB)</label>
                <input type="file" name="csv_file" id="csv_file" class="form-input admin-focus" accept=".csv" required style="padding:10px;">
            </div>
        </div>
        <button type="submit" class="btn btn-purple">Upload & Preview</button>
    </form>
</div>

<!-- Template Downloads -->
<div class="card">
    <h3 style="font-size:1rem; font-weight:600; margin-bottom:16px;">CSV Templates</h3>
    <p style="color:var(--text-secondary); margin-bottom:16px;">Download template files with the correct column headers and example data.</p>
    <div class="flex gap-2" style="flex-wrap:wrap;">
        <a href="/templates/csv/students_template.csv" class="btn btn-outline btn-sm" download>📄 Students Template</a>
        <a href="/templates/csv/programmes_template.csv" class="btn btn-outline btn-sm" download>📄 Programmes Template</a>
        <a href="/templates/csv/scholarships_template.csv" class="btn btn-outline btn-sm" download>📄 Scholarships Template</a>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
