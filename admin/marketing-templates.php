<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$db = getDB();
$generator = new \UTP\Services\ProposalGenerator($db);
$error = '';
$generatedContent = null;

// Fetch active scholarships for dropdown
$stmt = $db->query("SELECT id, name FROM scholarships WHERE is_active = 1 ORDER BY name ASC");
$scholarships = $stmt->fetchAll();

// Handle Generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    if (!\UTP\Security\CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $type = $_POST['template_type'] ?? '';
    $audience = $_POST['target_audience'] ?? '';
    $scholarshipId = (int)($_POST['scholarship_id'] ?? 0);
    
    if ($type && $audience) {
        try {
            $generatedContent = $generator->generateMarketingTemplate($type, $scholarshipId, $audience);
        } catch (Exception $e) {
            $error = "Failed to generate marketing template: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

require_once __DIR__ . '/admin_header.php';
?>

<div class="flex-between mb-6">
    <div class="page-header" style="margin-bottom:0;">
        <h1>AI Marketing Templates</h1>
        <p>Auto-generate engaging emails and announcements tailored to your students.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid" style="grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
    
    <!-- Controls Sidebar -->
    <div class="card">
        <h3 style="font-size:1.1rem; font-weight:600; margin-bottom:16px;">Template Configuration</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="generate">
            
            <div class="form-group">
                <label class="form-label">Content Type</label>
                <select name="template_type" class="form-input admin-focus" required>
                    <option value="Scholarship Announcement Email">Scholarship Announcement Email</option>
                    <option value="Application Deadline Reminder">Application Deadline Reminder</option>
                    <option value="Success Story Highlight">Success Story Highlight</option>
                    <option value="Social Media Post">Social Media Post</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Target Audience</label>
                <select name="target_audience" class="form-input admin-focus" required>
                    <option value="All Registered Students">All Registered Students</option>
                    <option value="Eligible Students (Not Yet Applied)">Eligible Students (Not Yet Applied)</option>
                    <option value="Incomplete Applications">Incomplete Applications</option>
                    <option value="High Achievers (90%+ Fit)">High Achievers (90%+ Fit)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Related Scholarship (Optional)</label>
                <select name="scholarship_id" class="form-input admin-focus">
                    <option value="0">General (No specific scholarship)</option>
                    <?php foreach ($scholarships as $sch): ?>
                        <option value="<?= $sch['id'] ?>"><?= htmlspecialchars($sch['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-purple" style="width:100%; justify-content:center; margin-top:8px;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" style="margin-right:8px;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                Generate Template
            </button>
        </form>
    </div>

    <!-- Output Editor -->
    <div class="card" style="min-height: 450px; display:flex; flex-direction:column;">
        <div class="flex-between mb-4" style="border-bottom: 1px solid var(--border-color); padding-bottom:16px;">
            <h3 style="font-size:1.1rem; font-weight:600; margin:0;">Generated Content</h3>
            <?php if ($generatedContent): ?>
                <a href="javascript:void(0);" onclick="copyContent()" class="btn btn-outline btn-sm">Copy HTML</a>
            <?php endif; ?>
        </div>
        
        <div style="flex-grow:1;">
            <?php if ($generatedContent): ?>
                <!-- Editable output area -->
                <div id="output-preview" contenteditable="true" style="padding:16px; border:1px solid var(--border-color); border-radius:6px; min-height:300px; outline:none;" class="raw-html-content">
                    <?= \UTP\Security\InputSanitizer::sanitizeHtml($generatedContent) ?>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; min-height:300px; color:var(--text-muted); text-align:center;">
                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom:16px; opacity:0.5;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    <p>Configure your template on the left and click generate.<br>The AI will write the content for you.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .raw-html-content h1, .raw-html-content h2, .raw-html-content h3 { color: var(--utp-navy); margin-top: 16px; margin-bottom: 12px; }
    .raw-html-content p { margin-bottom: 16px; line-height: 1.6; }
    .raw-html-content ul { margin-bottom: 16px; padding-left: 24px; }
    .raw-html-content li { margin-bottom: 8px; }
    #output-preview:focus { border-color: var(--utp-teal); box-shadow: 0 0 0 3px rgba(0, 161, 177, 0.1); }
</style>

<script nonce="<?= $GLOBALS['csp_nonce'] ?>">
function copyContent() {
    const content = document.getElementById('output-preview').innerHTML;
    navigator.clipboard.writeText(content).then(() => {
        alert("HTML copied to clipboard! You can paste this directly into your email client or marketing tool.");
    }).catch(err => {
        console.error('Failed to copy: ', err);
        alert("Failed to copy content.");
    });
}
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
