<?php
/**
 * Check Eligibility Page
 * Step 1: Select qualification type
 * Step 1.5: Choose entry mode (Manual or AI OCR)
 * Step 2: Enter subject grades (manual or auto-filled from OCR)
 * Step 3: Submit
 */
require_once __DIR__ . '/../includes/init.php';
requireVerified();

// Enforce IC and Passport Photo prerequisites
$db = getDB();
$stmt = $db->prepare("SELECT COUNT(*) FROM documents WHERE user_id = ? AND doc_type IN ('ic', 'photo')");
$stmt->execute([$_SESSION['user_id']]);
$docCount = (int) $stmt->fetchColumn();

if ($docCount < 2) {
    header('Location: /student/upload-documents.php?error=missing_docs');
    exit;
}

// Enforce single active submission — block if a pending application exists
$stmt = $db->prepare("SELECT id, status, created_at FROM applications WHERE user_id = ? AND status IN ('submitted', 'processing') ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$pendingApp = $stmt->fetch();

if ($pendingApp) {
    $pageTitle = 'Application Pending — UTP Scholarship System';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="container" style="padding-top:48px; padding-bottom:48px;">
        <div class="card" style="max-width:600px; margin:0 auto; text-align:center; padding:40px;">
            <div style="font-size:3rem; margin-bottom:16px;">⏳</div>
            <h2 style="margin-bottom:8px; color:var(--text-primary);">Application Under Review</h2>
            <p style="color:var(--text-secondary); margin-bottom:24px; line-height:1.6;">
                You have already submitted an application <strong>(#<?= $pendingApp['id'] ?>)</strong> on 
                <strong><?= date('d M Y, h:i A', strtotime($pendingApp['created_at'])) ?></strong>.
                <br><br>
                Current status: 
                <span class="badge badge-<?= $pendingApp['status'] === 'processing' ? 'yellow' : 'blue' ?>" style="font-size:0.85rem;">
                    <?= ucfirst($pendingApp['status']) ?>
                </span>
            </p>
            <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:24px;">
                You may submit a new application once an administrator has reviewed your current submission.
            </p>
            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <a href="/student/results.php" class="btn btn-orange">View My Results</a>
                <a href="/student/dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = 'Check Eligibility — UTP Scholarship System';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:32px; padding-bottom:48px;">
    <div class="page-header">
        <h1>Check Your Eligibility</h1>
        <p>Select your qualification type and enter your grades to find matching programmes.</p>
    </div>

    <!-- Step Indicator -->
    <div class="steps">
        <div class="step active">
            <span class="step-number">1</span>
            <span class="step-text">Qualification</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <span class="step-number">2</span>
            <span class="step-text">Grades</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <span class="step-number">3</span>
            <span class="step-text">Results</span>
        </div>
    </div>

    <form method="POST" action="/api/check-eligibility.php" data-validate="true" id="eligibilityForm">
        <?= csrfField() ?>
        <input type="hidden" name="is_ocr_submission" id="isOcrSubmission" value="0">

        <!-- Step 1: Select Qualification -->
        <div class="card mb-6" id="step1">
            <h3 style="margin-bottom:16px; font-size:1.05rem;">Select Your Qualification</h3>
            <div class="grid-3">
                <label class="card card-flat qual-card" data-qual="SPM" style="cursor:pointer; text-align:center; padding:20px; border:2px solid var(--border); transition: border-color 0.2s;">
                    <input type="radio" name="qual_type" value="SPM" class="hidden">
                    <div class="qual-option" style="pointer-events:none;">
                        <strong style="font-size:1.1rem; display:block; margin-bottom:4px;">SPM</strong>
                        <span style="font-size:0.82rem; color:var(--text-secondary);">Sijil Pelajaran Malaysia</span>
                    </div>
                </label>
                <label class="card card-flat qual-card" data-qual="O-Level" style="cursor:pointer; text-align:center; padding:20px; border:2px solid var(--border); transition: border-color 0.2s;">
                    <input type="radio" name="qual_type" value="O-Level" class="hidden">
                    <div class="qual-option" style="pointer-events:none;">
                        <strong style="font-size:1.1rem; display:block; margin-bottom:4px;">O-Level</strong>
                        <span style="font-size:0.82rem; color:var(--text-secondary);">GCE Ordinary Level</span>
                    </div>
                </label>
                <label class="card card-flat qual-card" data-qual="IGCSE" style="cursor:pointer; text-align:center; padding:20px; border:2px solid var(--border); transition: border-color 0.2s;">
                    <input type="radio" name="qual_type" value="IGCSE" class="hidden">
                    <div class="qual-option" style="pointer-events:none;">
                        <strong style="font-size:1.1rem; display:block; margin-bottom:4px;">IGCSE</strong>
                        <span style="font-size:0.82rem; color:var(--text-secondary);">International GCSE</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Step 1.5: Choose Entry Mode -->
        <div class="card mb-6 hidden" id="entryModeCard">
            <h3 style="margin-bottom:8px; font-size:1.05rem;">How would you like to enter your grades?</h3>
            <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:20px;">Choose manual entry or let AI scan your result slip automatically.</p>
            <div class="entry-mode-toggle">
                <label class="entry-mode-card active" id="modeManual" data-mode="manual">
                    <div class="entry-mode-icon">📝</div>
                    <div class="entry-mode-info">
                        <strong>Manual Entry</strong>
                        <span>Type in your grades subject by subject</span>
                    </div>
                    <div class="entry-mode-check">✓</div>
                </label>
                <label class="entry-mode-card" id="modeOcr" data-mode="ocr">
                    <div class="entry-mode-icon">🤖</div>
                    <div class="entry-mode-info">
                        <strong>AI OCR Scan</strong>
                        <span>Upload a photo of your result slip</span>
                    </div>
                    <div class="entry-mode-check">✓</div>
                </label>
            </div>
        </div>

        <!-- OCR Upload Area (Hidden by default) -->
        <div class="card mb-6 hidden" id="ocrUploadCard">
            <h3 style="margin-bottom:8px; font-size:1.05rem;">📷 Upload Your Result Slip</h3>
            <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:20px;">
                Upload a clear photo or scanned PDF of your result slip. The AI will read and extract your subjects and grades automatically.
            </p>

            <div class="ocr-dropzone" id="ocrDropzone">
                <div class="ocr-dropzone-content">
                    <div class="ocr-dropzone-icon">📄</div>
                    <p class="ocr-dropzone-text"><strong>Drop your result slip here</strong></p>
                    <p class="ocr-dropzone-hint">or click to browse — JPG, PNG, WebP, HEIC, PDF (max 5MB)</p>
                    <input type="file" id="ocrFileInput" accept=".jpg,.jpeg,.png,.webp,.heic,.heif,.pdf" style="display:none;">
                </div>
                <!-- Image Preview (Hidden by default) -->
                <div id="ocrImagePreview" class="hidden" style="margin-top: 15px; text-align: center;">
                    <img id="ocrPreviewImg" src="" alt="Result Slip Preview" style="max-height: 200px; max-width: 100%; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                </div>
            </div>

            <!-- OCR Processing Loader -->
            <div class="ocr-loader hidden" id="ocrLoader">
                <div class="ocr-loader-spinner"></div>
                <h3 style="color:var(--orange); margin-bottom:4px;">AI is reading your result slip...</h3>
                <p style="color:var(--text-secondary); font-size:0.85rem;">Extracting subjects and grades via OCR. This may take a few seconds.</p>
            </div>

            <!-- OCR Error -->
            <div class="hidden" id="ocrErrorBox">
                <div class="alert alert-danger" id="ocrErrorMsg"></div>
                <button type="button" class="btn btn-outline btn-sm" id="ocrRetryBtn" style="margin-top:8px;">Try Again</button>
            </div>
        </div>

        <!-- OCR Results Preview (Hidden by default) -->
        <div class="card mb-6 hidden" id="ocrResultsCard">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
                <div>
                    <h3 style="font-size:1.05rem; margin-bottom:4px;">📋 Extracted Grades — Review & Confirm</h3>
                    <p style="color:var(--text-secondary); font-size:0.82rem;">
                        The AI found the grades below. Please verify and correct any mistakes before submitting.
                    </p>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <button type="button" class="btn btn-outline btn-sm" id="ocrAcceptAllBtn" style="display:none;">✅ Accept All High Confidence</button>
                    <span class="badge badge-blue" id="ocrCountBadge">0 subjects found</span>
                </div>
            </div>

            <!-- Subject warning alert -->
            <div id="ocrSubjectWarning" class="alert alert-warning hidden" style="margin-bottom:16px;">
                ⚠️ <strong>Missing Subjects?</strong> The AI detected fewer than 5 subjects. Please review and manually add any missing subjects below.
            </div>

            <div class="table-responsive">
                <style>
                @media (max-width: 768px) {
                    #ocrResultsTable thead { display: none; }
                    #ocrResultsTable tbody tr { display: block; border-bottom: 2px solid var(--border); margin-bottom: 12px; padding-bottom: 12px; }
                    #ocrResultsTable tbody td { display: flex; justify-content: space-between; align-items: center; padding: 8px 4px; border-bottom: none; }
                    #ocrResultsTable tbody td::before { content: attr(data-label); font-weight: 600; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; }
                    #ocrResultsTable tbody td select, #ocrResultsTable tbody td input { width: 60%; }
                    #ocrResultsTable tbody td:last-child { justify-content: flex-end; }
                }
                </style>
                <table class="table" style="width:100%; border-collapse:collapse;" id="ocrResultsTable">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:12px 16px; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--border); background:var(--bg-page);">
                                OCR Detected
                            </th>
                            <th style="text-align:left; padding:12px 16px; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--border); background:var(--bg-page);">
                                Matched Subject
                            </th>
                            <th style="text-align:left; padding:12px 16px; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--border); background:var(--bg-page);">
                                Grade
                            </th>
                            <th style="text-align:center; padding:12px 16px; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--border); background:var(--bg-page); width:60px;">
                                Status
                            </th>
                            <th style="text-align:center; padding:12px 16px; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--border); background:var(--bg-page); width:50px;">
                            </th>
                        </tr>
                    </thead>
                    <tbody id="ocrResultsBody">
                    </tbody>
                </table>
            </div>

            <div style="display:flex; gap:12px; margin-top:20px; flex-wrap:wrap;">
                <button type="button" class="btn btn-outline btn-sm" id="ocrAddRowBtn">+ Add Missing Subject</button>
                <button type="button" class="btn btn-outline btn-sm" id="ocrRescanBtn" style="margin-left:auto;">🔄 Re-scan</button>
            </div>
        </div>

        <!-- Step 2: Manual Grade Input (dynamically populated) -->
        <div class="card mb-6 hidden" id="step2">
            <h3 style="margin-bottom:16px; font-size:1.05rem;">Enter Your Grades</h3>
            <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:20px;">
                Select the grade you achieved for each subject. All subjects are checked against UTP entry requirements.
            </p>
            <div id="grade_inputs"></div>
        </div>

        <!-- Submit -->
        <div id="submit_container" style="display:none;">
            <button type="submit" class="btn btn-orange btn-lg btn-block" id="submit_btn">
                Check My Eligibility
            </button>
        </div>
    </form>


</div>

<script nonce="<?= $GLOBALS['csp_nonce'] ?>">
// ── State ────────────────────────────────────────────────────────────
var selectedQual = null;
var entryMode = 'manual'; // 'manual' or 'ocr'
var csrfToken = document.querySelector('input[name="csrf_token"]').value;

// ── Step 1: Qualification Selection ──────────────────────────────────
document.querySelectorAll('.qual-card').forEach(function(card) {
    card.addEventListener('click', function() {
        var input = this.querySelector('input');
        if (input) input.checked = true;
        
        document.querySelectorAll('.qual-card').forEach(function(el) {
            el.style.borderColor = 'var(--border)';
        });
        
        this.style.borderColor = 'var(--orange)';
        selectedQual = this.getAttribute('data-qual');

        // Show entry mode toggle
        document.getElementById('entryModeCard').classList.remove('hidden');

        // Reset: hide all grade areas
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('ocrUploadCard').classList.add('hidden');
        document.getElementById('ocrResultsCard').classList.add('hidden');
        document.getElementById('submit_container').style.display = 'none';

        // Apply current entry mode
        applyEntryMode();
        updateSteps(2);
    });
});

// ── Step 1.5: Entry Mode Toggle ──────────────────────────────────────
document.querySelectorAll('.entry-mode-card').forEach(function(card) {
    card.addEventListener('click', function() {
        document.querySelectorAll('.entry-mode-card').forEach(function(el) {
            el.classList.remove('active');
        });
        this.classList.add('active');
        entryMode = this.getAttribute('data-mode');
        applyEntryMode();
    });
});

function applyEntryMode() {
    if (!selectedQual) return;

    if (entryMode === 'manual') {
        document.getElementById('step2').classList.remove('hidden');
        document.getElementById('ocrUploadCard').classList.add('hidden');
        document.getElementById('ocrResultsCard').classList.add('hidden');
        document.getElementById('ocrResultsBody').innerHTML = ''; // Strip OCR data
        updateGradeInputs(selectedQual);
        document.getElementById('submit_container').style.display = 'block';
        document.getElementById('isOcrSubmission').value = '0';
    } else {
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('ocrUploadCard').classList.remove('hidden');
        document.getElementById('ocrResultsCard').classList.add('hidden');
        document.getElementById('grade_inputs').innerHTML = ''; // Strip manual data to prevent hidden required fields blocking submit
        document.getElementById('submit_container').style.display = 'none';
        document.getElementById('isOcrSubmission').value = '1';
        // Reset upload state
        resetOcrUpload();
    }
}

// ── OCR Upload Handling ──────────────────────────────────────────────
var ocrDropzone = document.getElementById('ocrDropzone');
var ocrFileInput = document.getElementById('ocrFileInput');

if (ocrDropzone) {
    ocrDropzone.addEventListener('click', function() {
        ocrFileInput.click();
    });

    ocrDropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    ocrDropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    ocrDropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            handleOcrFile(e.dataTransfer.files[0]);
        }
    });
}

if (ocrFileInput) {
    ocrFileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleOcrFile(this.files[0]);
        }
    });
}

function handleOcrFile(file) {
    // Client-side validation
    var allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'];
    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(jpg|jpeg|png|webp|heic|heif|pdf)$/i)) {
        showOcrError('Invalid file type. Please upload a JPG, PNG, WebP, HEIC, or PDF file.');
        return;
    }
    if (file.size > 5242880) {
        showOcrError('File is larger than 5MB. Please compress or crop your image.');
        return;
    }

    // Show image preview
    if (file.type.startsWith('image/') || file.name.match(/\.(jpg|jpeg|png|webp|heic|heif)$/i)) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('ocrPreviewImg').src = e.target.result;
            document.getElementById('ocrImagePreview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('ocrImagePreview').classList.add('hidden');
    }

    // Show loader, hide dropzone
    document.getElementById('ocrDropzone').classList.add('hidden');
    document.getElementById('ocrErrorBox').classList.add('hidden');
    document.getElementById('ocrLoader').classList.remove('hidden');

    // Upload via AJAX
    var formData = new FormData();
    formData.append('result_slip', file);
    formData.append('qual_type', selectedQual);
    formData.append('csrf_token', csrfToken);

    fetch('/api/ocr-result.php', {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        document.getElementById('ocrLoader').classList.add('hidden');

        if (data.new_csrf_token) {
            csrfToken = data.new_csrf_token;
            var csrfInput = document.querySelector('input[name="csrf_token"]');
            if (csrfInput) csrfInput.value = data.new_csrf_token;
        }

        if (data.success && data.grades && data.grades.length > 0) {
            renderOcrResults(data.grades, selectedQual);
        } else if (data.success && data.grades && data.grades.length === 0) {
            showOcrError('The AI could not find any subject-grade pairs in your image. Make sure the result slip is clear and try again, or use Manual Entry.');
        } else {
            showOcrError(data.error || 'OCR processing failed. Please try again.');
        }
    })
    .catch(function(err) {
        document.getElementById('ocrLoader').classList.add('hidden');
        showOcrError('Network error. Please check your connection and try again.');
        console.error('OCR fetch error:', err);
    });
}

function showOcrError(msg) {
    document.getElementById('ocrDropzone').classList.add('hidden');
    document.getElementById('ocrLoader').classList.add('hidden');
    document.getElementById('ocrErrorBox').classList.remove('hidden');
    document.getElementById('ocrErrorMsg').textContent = msg;
}

function resetOcrUpload() {
    document.getElementById('ocrDropzone').classList.remove('hidden');
    document.getElementById('ocrLoader').classList.add('hidden');
    document.getElementById('ocrErrorBox').classList.add('hidden');
    document.getElementById('ocrResultsCard').classList.add('hidden');
    if (ocrFileInput) ocrFileInput.value = '';
}

// Retry button
var retryBtn = document.getElementById('ocrRetryBtn');
if (retryBtn) {
    retryBtn.addEventListener('click', resetOcrUpload);
}

// Re-scan button
var rescanBtn = document.getElementById('ocrRescanBtn');
if (rescanBtn) {
    rescanBtn.addEventListener('click', function() {
        document.getElementById('ocrResultsCard').classList.add('hidden');
        document.getElementById('ocrUploadCard').classList.remove('hidden');
        resetOcrUpload();
    });
}

// ── Render OCR Results ───────────────────────────────────────────────
function renderOcrResults(grades, qualType) {
    document.getElementById('ocrUploadCard').classList.add('hidden');
    document.getElementById('ocrResultsCard').classList.remove('hidden');
    document.getElementById('ocrCountBadge').textContent = grades.length + ' subjects found';

    var tbody = document.getElementById('ocrResultsBody');
    tbody.innerHTML = '';

    var hasHighConf = false;

    grades.forEach(function(item, index) {
        addOcrResultRow(tbody, item, qualType, index);
        if (item.confidence === 'high' || !item.confidence) {
            hasHighConf = true;
        }
    });

    if (grades.length < 5) {
        document.getElementById('ocrSubjectWarning').classList.remove('hidden');
    } else {
        document.getElementById('ocrSubjectWarning').classList.add('hidden');
    }

    var acceptAllBtn = document.getElementById('ocrAcceptAllBtn');
    if (acceptAllBtn) {
        acceptAllBtn.style.display = hasHighConf ? 'block' : 'none';
        acceptAllBtn.onclick = function() {
            var rows = document.querySelectorAll('.ocr-result-row');
            var firstWarningRow = null;
            rows.forEach(function(row) {
                var confCell = row.querySelector('td:nth-child(4)').innerHTML;
                if (confCell.includes('✅')) {
                    row.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
                    row.style.borderLeft = '4px solid #10b981';
                } else if (!firstWarningRow) {
                    firstWarningRow = row;
                }
            });
            if (firstWarningRow) {
                firstWarningRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        };
    }

    // Show submit button
    document.getElementById('submit_container').style.display = 'block';
    updateSteps(2);
}

function addOcrResultRow(tbody, item, qualType, index) {
    var allSubjects = getSubjectList(qualType);
    var gradeList = getGradeList(qualType);

    var row = document.createElement('tr');
    row.className = 'ocr-result-row';

    // Column 1: OCR detected text
    var tdDetected = document.createElement('td');
    tdDetected.style.cssText = 'padding:12px 16px; font-size:0.85rem; border-bottom:1px solid var(--border-light); color:var(--text-secondary); font-style:italic;';
    tdDetected.setAttribute('data-label', 'OCR Detected');
    tdDetected.textContent = item.subject || '—';
    row.appendChild(tdDetected);

    // Column 2: Subject dropdown (auto-selected)
    var tdSubject = document.createElement('td');
    tdSubject.style.cssText = 'padding:8px 16px; border-bottom:1px solid var(--border-light);';
    tdSubject.setAttribute('data-label', 'Matched Subject');
    var subjectSelect = document.createElement('select');
    subjectSelect.name = 'subjects[]';
    subjectSelect.className = 'form-select form-select-sm';
    subjectSelect.required = true;
    subjectSelect.innerHTML = '<option value="" disabled>Select Subject</option>';
    allSubjects.forEach(function(s) {
        var opt = document.createElement('option');
        opt.value = s;
        opt.textContent = s;
        if (item.matched_key === s) opt.selected = true;
        subjectSelect.appendChild(opt);
    });
    if (!item.matched_key) subjectSelect.selectedIndex = 0;
    tdSubject.appendChild(subjectSelect);
    row.appendChild(tdSubject);

    // Column 3: Grade dropdown (auto-selected)
    var tdGrade = document.createElement('td');
    tdGrade.style.cssText = 'padding:8px 16px; border-bottom:1px solid var(--border-light);';
    tdGrade.setAttribute('data-label', 'Grade');
    var gradeSelect = document.createElement('select');
    gradeSelect.name = 'grades[]';
    gradeSelect.className = 'form-select form-select-sm';
    gradeSelect.required = true;
    gradeSelect.innerHTML = '<option value="" disabled>Grade</option>';
    gradeList.forEach(function(g) {
        var opt = document.createElement('option');
        opt.value = g;
        opt.textContent = g;
        if (item.grade === g) opt.selected = true;
        gradeSelect.appendChild(opt);
    });
    tdGrade.appendChild(gradeSelect);
    row.appendChild(tdGrade);

    // Column 4: Confidence icon
    var tdConf = document.createElement('td');
    tdConf.style.cssText = 'padding:12px 16px; border-bottom:1px solid var(--border-light); text-align:center; font-size:1.1rem;';
    tdConf.setAttribute('data-label', 'Status');
    var confIcon = '✅';
    var confTitle = 'High confidence match';
    if (item.confidence === 'medium') {
        confIcon = '🟡';
        confTitle = 'Medium confidence — please verify';
    } else if (item.confidence === 'low') {
        confIcon = '⚠️';
        confTitle = 'Low confidence — please check carefully';
    } else if (item.confidence === 'none' || !item.matched_key) {
        confIcon = '❌';
        confTitle = 'Could not auto-match — please select manually';
    }
    tdConf.innerHTML = '<span title="' + confTitle + '" style="cursor:help;">' + confIcon + '</span>';
    row.appendChild(tdConf);

    // Column 5: Remove button
    var tdRemove = document.createElement('td');
    tdRemove.style.cssText = 'padding:8px 16px; border-bottom:1px solid var(--border-light); text-align:center;';
    tdRemove.setAttribute('data-label', 'Action');
    var removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-red btn-sm';
    removeBtn.textContent = '✕';
    removeBtn.style.cssText = 'padding:4px 10px; font-size:0.8rem; min-width:0;';
    removeBtn.addEventListener('click', function() {
        row.remove();
        updateOcrCount();
    });
    tdRemove.appendChild(removeBtn);
    row.appendChild(tdRemove);

    tbody.appendChild(row);
}

function updateOcrCount() {
    var count = document.querySelectorAll('#ocrResultsBody tr').length;
    document.getElementById('ocrCountBadge').textContent = count + ' subjects found';
}

// Add missing subject row
var addRowBtn = document.getElementById('ocrAddRowBtn');
if (addRowBtn) {
    addRowBtn.addEventListener('click', function() {
        var tbody = document.getElementById('ocrResultsBody');
        addOcrResultRow(tbody, { subject: '', matched_key: '', grade: '', confidence: 'none' }, selectedQual, tbody.children.length);
        updateOcrCount();
    });
}

// ── Subject & Grade Lists ────────────────────────────────────────────
function getSubjectList(qualType) {
    var core = {
        'SPM': ['Bahasa Melayu', 'English', 'Mathematics', 'Sejarah'],
        'O-Level': ['English Language', 'Mathematics'],
        'IGCSE': ['English Language', 'Mathematics']
    };
    var optional = [
        'Additional Mathematics', 'Physics', 'Chemistry', 'Biology', 'Science',
        'Pendidikan Islam', 'Pendidikan Moral', 'Prinsip Perakaunan', 'Ekonomi',
        'Perniagaan', 'Sains Komputer', 'Grafik Komunikasi Teknikal', 'Pendidikan Seni Visual', 'Reka Cipta',
        'Other Subject', 'Other Subject I', 'Other Subject II', 'Other Subject III', 'Other Subject IV',
        'Other Non-Language Subject', 'Other Non-Language Subject I', 'Other Non-Language Subject II', 'Other Non-Language Subject III', 'Other Non-Language Subject IV'
    ];
    return (core[qualType] || []).concat(optional);
}

function getGradeList(qualType) {
    var grades = {
        'SPM': ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'E', 'G'],
        'O-Level': ['A*', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'U'],
        'IGCSE': ['A*', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'U']
    };
    return grades[qualType] || [];
}

// Form submission is natively handled via check-eligibility.php POST action.
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
