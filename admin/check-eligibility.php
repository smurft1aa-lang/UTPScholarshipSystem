<?php
/**
 * Admin Check Eligibility Page
 * Allows an admin to run the eligibility engine on behalf of a student.
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$db = getDB();

// Get list of all students for the dropdown
$stmt = $db->query("SELECT id, full_name, email FROM users WHERE role = 'student' ORDER BY full_name");
$students = $stmt->fetchAll();

$pageTitle = 'Admin: Run Eligibility Check — UTP Scholarship System';
require_once __DIR__ . '/admin_header.php';
?>

<div class="page-header">
    <h1>Run Eligibility Check</h1>
    <p>Perform an eligibility check (via OCR or manual input) on behalf of a student.</p>
</div>

<div class="steps" style="margin-top: 24px;">
    <div class="step active">
        <span class="step-number">1</span>
        <span class="step-text">Student & Qual</span>
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

    <!-- Admin: Select Student -->
    <div class="card mb-6">
        <h3 style="margin-bottom:16px; font-size:1.05rem;">Select Student</h3>
        <div class="form-group" style="max-width: 400px;">
            <select name="student_id" id="student_id" class="form-select admin-focus" required>
                <option value="">-- Choose a Student --</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Step 1: Select Qualification -->
    <div class="card mb-6" id="step1">
        <h3 style="margin-bottom:16px; font-size:1.05rem;">Select Qualification</h3>
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
        <h3 style="margin-bottom:8px; font-size:1.05rem;">Grade Entry Mode</h3>
        <div class="entry-mode-toggle">
            <label class="entry-mode-card active" id="modeManual" data-mode="manual">
                <div class="entry-mode-icon">📝</div>
                <div class="entry-mode-info">
                    <strong>Manual Entry</strong>
                    <span>Type in grades manually</span>
                </div>
                <div class="entry-mode-check">✓</div>
            </label>
            <label class="entry-mode-card" id="modeOcr" data-mode="ocr">
                <div class="entry-mode-icon">🤖</div>
                <div class="entry-mode-info">
                    <strong>AI OCR Scan</strong>
                    <span>Upload result slip on behalf of student</span>
                </div>
                <div class="entry-mode-check">✓</div>
            </label>
        </div>
    </div>

    <!-- OCR Upload Area (Hidden by default) -->
    <div class="card mb-6 hidden" id="ocrUploadCard">
        <h3 style="margin-bottom:8px; font-size:1.05rem;">📷 Upload Result Slip</h3>
        <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:20px;">
            The document will be saved to the student's vault and grades will be extracted.
        </p>

        <div class="ocr-dropzone" id="ocrDropzone">
            <div class="ocr-dropzone-content">
                <div class="ocr-dropzone-icon">📄</div>
                <p class="ocr-dropzone-text"><strong>Drop result slip here</strong></p>
                <input type="file" id="ocrFileInput" accept=".jpg,.jpeg,.png,.webp,.heic,.heif,.pdf" style="display:none;">
            </div>
            <div id="ocrImagePreview" class="hidden" style="margin-top: 15px; text-align: center;">
                <img id="ocrPreviewImg" src="" alt="Preview" style="max-height: 200px; max-width: 100%; border-radius: 4px;">
            </div>
        </div>

        <div class="ocr-loader hidden" id="ocrLoader">
            <div class="ocr-loader-spinner"></div>
            <h3 style="color:var(--purple); margin-bottom:4px;">Scanning document...</h3>
        </div>

        <div class="hidden" id="ocrErrorBox">
            <div class="alert alert-danger" id="ocrErrorMsg"></div>
            <button type="button" class="btn btn-outline btn-sm" id="ocrRetryBtn" style="margin-top:8px;">Try Again</button>
        </div>
    </div>

    <!-- OCR Results Preview -->
    <div class="card mb-6 hidden" id="ocrResultsCard">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
            <div>
                <h3 style="font-size:1.05rem; margin-bottom:4px;">📋 Extracted Grades</h3>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <span class="badge badge-blue" id="ocrCountBadge">0 subjects found</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" style="width:100%; border-collapse:collapse;" id="ocrResultsTable">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:12px 16px;">OCR Detected</th>
                        <th style="text-align:left; padding:12px 16px;">Matched Subject</th>
                        <th style="text-align:left; padding:12px 16px;">Grade</th>
                        <th style="text-align:center; padding:12px 16px;">Status</th>
                        <th style="text-align:center; padding:12px 16px;"></th>
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

    <!-- Step 2: Manual Grade Input -->
    <div class="card mb-6 hidden" id="step2">
        <h3 style="margin-bottom:16px; font-size:1.05rem;">Enter Grades</h3>
        <div id="grade_inputs"></div>
    </div>

    <!-- Submit -->
    <div id="submit_container" style="display:none;">
        <button type="submit" class="btn btn-purple btn-lg btn-block" id="submit_btn" onclick="return document.getElementById('student_id').value !== '' || alert('Please select a student');">
            Run Eligibility Engine
        </button>
    </div>
</form>

<script nonce="<?= $GLOBALS['csp_nonce'] ?>">
// ── State ────────────────────────────────────────────────────────────
var selectedQual = null;
var entryMode = 'manual';
var csrfToken = document.querySelector('input[name="csrf_token"]').value;

// ── Step 1: Qualification Selection ──────────────────────────────────
document.querySelectorAll('.qual-card').forEach(function(card) {
    card.addEventListener('click', function() {
        var input = this.querySelector('input');
        if (input) input.checked = true;
        
        document.querySelectorAll('.qual-card').forEach(function(el) {
            el.style.borderColor = 'var(--border)';
        });
        
        this.style.borderColor = 'var(--purple)'; // Admin color
        selectedQual = this.getAttribute('data-qual');

        document.getElementById('entryModeCard').classList.remove('hidden');
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('ocrUploadCard').classList.add('hidden');
        document.getElementById('ocrResultsCard').classList.add('hidden');
        document.getElementById('submit_container').style.display = 'none';

        applyEntryMode();
    });
});

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
        document.getElementById('ocrResultsBody').innerHTML = ''; 
        updateGradeInputs(selectedQual); // Assume updateGradeInputs is available globally or we will reimplement it simply
        document.getElementById('submit_container').style.display = 'block';
        document.getElementById('isOcrSubmission').value = '0';
    } else {
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('ocrUploadCard').classList.remove('hidden');
        document.getElementById('ocrResultsCard').classList.add('hidden');
        document.getElementById('grade_inputs').innerHTML = ''; 
        document.getElementById('submit_container').style.display = 'none';
        document.getElementById('isOcrSubmission').value = '1';
        resetOcrUpload();
    }
}

// Minimal updateGradeInputs since we don't have the student JS loaded here
function updateGradeInputs(qualType) {
    var subjects = getSubjectList(qualType);
    var grades = getGradeList(qualType);
    var html = '<div class="grid-2">';
    
    subjects.slice(0, 10).forEach(function(subj, idx) {
        html += '<div class="form-group flex" style="align-items:center; gap:12px;">';
        html += '<select name="subjects[]" class="form-select admin-focus" style="flex:2;"><option value="'+subj+'">'+subj+'</option></select>';
        html += '<select name="grades[]" class="form-select admin-focus" style="flex:1;" required><option value="">Grade</option>';
        grades.forEach(function(g) { html += '<option value="'+g+'">'+g+'</option>'; });
        html += '</select></div>';
    });
    html += '</div>';
    document.getElementById('grade_inputs').innerHTML = html;
}

// ── OCR Upload Handling ──────────────────────────────────────────────
var ocrDropzone = document.getElementById('ocrDropzone');
var ocrFileInput = document.getElementById('ocrFileInput');

if (ocrDropzone) {
    ocrDropzone.addEventListener('click', function() {
        if (!document.getElementById('student_id').value) {
            alert("Please select a student first!");
            return;
        }
        ocrFileInput.click();
    });
}

if (ocrFileInput) {
    ocrFileInput.addEventListener('change', function() {
        if (this.files.length > 0) handleOcrFile(this.files[0]);
    });
}

function handleOcrFile(file) {
    var studentId = document.getElementById('student_id').value;
    if (!studentId) {
        alert("Please select a student before uploading.");
        return;
    }

    if (file.type.startsWith('image/') || file.name.match(/\.(jpg|jpeg|png|webp|heic|heif)$/i)) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('ocrPreviewImg').src = e.target.result;
            document.getElementById('ocrImagePreview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }

    document.getElementById('ocrDropzone').classList.add('hidden');
    document.getElementById('ocrErrorBox').classList.add('hidden');
    document.getElementById('ocrLoader').classList.remove('hidden');

    var formData = new FormData();
    formData.append('result_slip', file);
    formData.append('qual_type', selectedQual);
    formData.append('student_id', studentId);
    formData.append('csrf_token', csrfToken);

    fetch('/api/ocr-result.php', {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('ocrLoader').classList.add('hidden');
        if (data.new_csrf_token) {
            csrfToken = data.new_csrf_token;
            document.querySelector('input[name="csrf_token"]').value = data.new_csrf_token;
        }
        if (data.success && data.grades) {
            renderOcrResults(data.grades, selectedQual);
        } else {
            showOcrError(data.error || 'OCR processing failed.');
        }
    })
    .catch(err => {
        document.getElementById('ocrLoader').classList.add('hidden');
        showOcrError('Network error.');
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

document.getElementById('ocrRetryBtn')?.addEventListener('click', resetOcrUpload);
document.getElementById('ocrRescanBtn')?.addEventListener('click', function() {
    document.getElementById('ocrResultsCard').classList.add('hidden');
    document.getElementById('ocrUploadCard').classList.remove('hidden');
    resetOcrUpload();
});

// ── Render OCR Results ───────────────────────────────────────────────
function renderOcrResults(grades, qualType) {
    document.getElementById('ocrUploadCard').classList.add('hidden');
    document.getElementById('ocrResultsCard').classList.remove('hidden');
    document.getElementById('ocrCountBadge').textContent = grades.length + ' subjects';

    var tbody = document.getElementById('ocrResultsBody');
    tbody.innerHTML = '';
    grades.forEach(function(item, index) { addOcrResultRow(tbody, item, qualType, index); });

    document.getElementById('submit_container').style.display = 'block';
}

function addOcrResultRow(tbody, item, qualType, index) {
    var allSubjects = getSubjectList(qualType);
    var gradeList = getGradeList(qualType);
    var row = document.createElement('tr');

    var tdDetected = document.createElement('td');
    tdDetected.textContent = item.subject || '—';
    row.appendChild(tdDetected);

    var tdSubject = document.createElement('td');
    var subjectSelect = document.createElement('select');
    subjectSelect.name = 'subjects[]';
    subjectSelect.className = 'form-select admin-focus form-select-sm';
    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
    allSubjects.forEach(s => {
        var opt = document.createElement('option');
        opt.value = s; opt.textContent = s;
        if (item.matched_key === s) opt.selected = true;
        subjectSelect.appendChild(opt);
    });
    tdSubject.appendChild(subjectSelect);
    row.appendChild(tdSubject);

    var tdGrade = document.createElement('td');
    var gradeSelect = document.createElement('select');
    gradeSelect.name = 'grades[]';
    gradeSelect.className = 'form-select admin-focus form-select-sm';
    gradeSelect.innerHTML = '<option value="">Grade</option>';
    gradeList.forEach(g => {
        var opt = document.createElement('option');
        opt.value = g; opt.textContent = g;
        if (item.grade === g) opt.selected = true;
        gradeSelect.appendChild(opt);
    });
    tdGrade.appendChild(gradeSelect);
    row.appendChild(tdGrade);

    var tdConf = document.createElement('td');
    tdConf.innerHTML = item.confidence === 'high' ? '✅' : '⚠️';
    row.appendChild(tdConf);

    var tdRemove = document.createElement('td');
    tdRemove.innerHTML = '<button type="button" class="btn btn-red btn-sm">✕</button>';
    tdRemove.querySelector('button').onclick = () => row.remove();
    row.appendChild(tdRemove);

    tbody.appendChild(row);
}

document.getElementById('ocrAddRowBtn')?.addEventListener('click', function() {
    var tbody = document.getElementById('ocrResultsBody');
    addOcrResultRow(tbody, { subject: '', matched_key: '', grade: '', confidence: 'none' }, selectedQual, tbody.children.length);
});

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
        'Perniagaan', 'Sains Komputer', 'Grafik Komunikasi Teknikal', 'Pendidikan Seni Visual', 'Reka Cipta'
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
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
