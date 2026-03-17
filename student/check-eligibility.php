<?php
/**
 * Check Eligibility Page
 * Step 1: Select qualification type
 * Step 2: Enter subject grades
 * Step 3: Submit
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
requireVerified();

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

    <form method="POST" action="/api/check-eligibility.php" data-validate="true">
        <?= csrfField() ?>

        <!-- Step 1: Select Qualification -->
        <div class="card mb-6" id="step1">
            <h3 style="margin-bottom:16px; font-size:1.05rem;">Select Your Qualification</h3>
            <div class="grid-3">
                <label class="card card-flat qual-card" data-qual="SPM" style="cursor:pointer; text-align:center; padding:20px; border:2px solid var(--border); transition: border-color 0.2s;">
                    <input type="radio" name="qual_type" value="SPM" class="hidden" required>
                    <div class="qual-option" style="pointer-events:none;">
                        <strong style="font-size:1.1rem; display:block; margin-bottom:4px;">SPM</strong>
                        <span style="font-size:0.82rem; color:var(--text-secondary);">Sijil Pelajaran Malaysia</span>
                    </div>
                </label>
                <label class="card card-flat qual-card" data-qual="O-Level" style="cursor:pointer; text-align:center; padding:20px; border:2px solid var(--border); transition: border-color 0.2s;">
                    <input type="radio" name="qual_type" value="O-Level" class="hidden" required>
                    <div class="qual-option" style="pointer-events:none;">
                        <strong style="font-size:1.1rem; display:block; margin-bottom:4px;">O-Level</strong>
                        <span style="font-size:0.82rem; color:var(--text-secondary);">GCE Ordinary Level</span>
                    </div>
                </label>
                <label class="card card-flat qual-card" data-qual="IGCSE" style="cursor:pointer; text-align:center; padding:20px; border:2px solid var(--border); transition: border-color 0.2s;">
                    <input type="radio" name="qual_type" value="IGCSE" class="hidden" required>
                    <div class="qual-option" style="pointer-events:none;">
                        <strong style="font-size:1.1rem; display:block; margin-bottom:4px;">IGCSE</strong>
                        <span style="font-size:0.82rem; color:var(--text-secondary);">International GCSE</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Step 2: Grade Input (dynamically populated) -->
        <div class="card mb-6 hidden" id="step2">
            <h3 style="margin-bottom:16px; font-size:1.05rem;">Enter Your Grades</h3>
            <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:20px;">
                Select the grade you achieved for each subject. All subjects are checked against UTP entry requirements.
            </p>
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

document.querySelectorAll('.qual-card').forEach(function(card) {
    card.addEventListener('click', function() {
        var input = this.querySelector('input');
        if (input) input.checked = true;
        
        document.querySelectorAll('.qual-card').forEach(function(el) {
            el.style.borderColor = 'var(--border)';
        });
        
        this.style.borderColor = 'var(--orange)';
        updateGradeInputs(this.getAttribute('data-qual'));
        document.getElementById('submit_container').style.display = 'block';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
