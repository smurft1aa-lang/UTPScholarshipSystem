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

    <form method="POST" action="/api/check-eligibility.php" data-validate="true">
        <?= csrfField() ?>

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

        <!-- Step 2: Grade Input (dynamically populated) -->
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

    <!-- Skeleton Loader (Hidden by default) -->
    <div id="ai_loader" style="display:none; text-align:center; padding:40px 0;">
        <h2 style="font-size:1.5rem; color:var(--orange); margin-bottom:16px;">AI Engine is Evaluating...</h2>
        <p style="color:var(--text-secondary); margin-bottom:32px;">Analyzing your grades against entry requirements across all programmes.</p>
        
        <div class="card" style="max-width:600px; margin:0 auto; text-align:left;">
            <div class="skeleton skeleton-heading"></div>
            <div class="skeleton skeleton-text" style="width:80%"></div>
            <div class="skeleton skeleton-text" style="width:90%"></div>
            
            <div style="margin-top:24px;">
                <div class="skeleton skeleton-card"></div>
                <div class="skeleton skeleton-card"></div>
            </div>
        </div>
    </div>
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

// Intercept form submission to show skeleton loader
document.querySelector('form').addEventListener('submit', function(e) {
    if (validateForm(this)) {
        e.preventDefault(); // Pause submission instantly
        // Hide form and steps
        document.querySelector('.steps').style.display = 'none';
        this.style.display = 'none';
        // Show AI skeleton loader
        document.getElementById('ai_loader').style.display = 'block';
        window.scrollTo({top: 0, behavior: 'smooth'});
        
        // Wait 1.5s so the user can see the AI evaluation animation before actual HTTP POST
        var formElement = this;
        setTimeout(function() {
            formElement.submit();
        }, 1500);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
