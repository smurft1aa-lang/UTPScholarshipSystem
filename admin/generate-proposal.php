<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$db = getDB();
$generator = new \UTP\Services\ProposalGenerator($db);
$error = '';
$success = '';
$generatedContent = null;
$recipients = [];
$audienceKey = '';
$templateType = '';
$scholarshipId = 0;

// Fetch active scholarships for dropdown
$stmt = $db->query("SELECT id, name FROM scholarships WHERE is_active = 1 ORDER BY name ASC");
$scholarships = $stmt->fetchAll();

// ─── Audience Query Map ───────────────────────────────────────────
function getRecipientsByAudience(\PDO $db, string $audience, int $scholarshipId): array
{
    switch ($audience) {
        case 'all_registered':
            $stmt = $db->query("SELECT id, full_name, email FROM users WHERE role = 'student' AND email_verified = 1 ORDER BY full_name ASC");
            return $stmt->fetchAll();

        case 'incomplete_applications':
            $stmt = $db->query("
                SELECT DISTINCT u.id, u.full_name, u.email
                FROM users u
                JOIN applications a ON u.id = a.user_id
                WHERE a.status = 'draft' AND u.email_verified = 1
                ORDER BY u.full_name ASC
            ");
            return $stmt->fetchAll();

        case 'submitted_pending':
            $stmt = $db->query("
                SELECT DISTINCT u.id, u.full_name, u.email
                FROM users u
                JOIN applications a ON u.id = a.user_id
                WHERE a.status = 'submitted' AND u.email_verified = 1
                ORDER BY u.full_name ASC
            ");
            return $stmt->fetchAll();

        case 'high_achievers':
            $stmt = $db->query("
                SELECT DISTINCT u.id, u.full_name, u.email
                FROM users u
                JOIN applications a ON u.id = a.user_id
                JOIN eligibility_results er ON a.id = er.application_id
                WHERE er.fit_percentage >= 90 AND er.eligible = 1 AND u.email_verified = 1
                ORDER BY u.full_name ASC
            ");
            return $stmt->fetchAll();

        case 'approved_students':
            $stmt = $db->query("
                SELECT DISTINCT u.id, u.full_name, u.email
                FROM users u
                JOIN applications a ON u.id = a.user_id
                WHERE a.status = 'approved' AND u.email_verified = 1
                ORDER BY u.full_name ASC
            ");
            return $stmt->fetchAll();

        default:
            return [];
    }
}

$audienceLabels = [
    'all_registered' => 'All Registered Students',
    'incomplete_applications' => 'Incomplete Applications',
    'submitted_pending' => 'Submitted & Pending Review',
    'high_achievers' => 'High Achievers (90%+ Fit)',
    'approved_students' => 'Approved Students',
];

// ─── Handle: Step 1 — Generate Template ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    if (!\UTP\Security\CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh the page and try again.';
    } else {
    $templateType = $_POST['template_type'] ?? '';
    $audienceKey = $_POST['target_audience'] ?? '';
    $scholarshipId = (int)($_POST['scholarship_id'] ?? 0);

    if ($templateType && $audienceKey) {
        try {
            $audienceLabel = $audienceLabels[$audienceKey] ?? $audienceKey;
            $generatedContent = $generator->generateMarketingTemplate($templateType, $scholarshipId, $audienceLabel);
            $recipients = getRecipientsByAudience($db, $audienceKey, $scholarshipId);
        } catch (Exception $e) {
            $error = "Failed to generate template: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
    } // end CSRF else
}

// ─── Handle: Step 2 — Broadcast Emails ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'broadcast') {
    if (!\UTP\Security\CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh the page and try again.';
    } else {
    $emailSubject = $_POST['email_subject'] ?? 'UTP Scholarship Update';
    $emailBody = $_POST['email_body'] ?? '';
    $recipientIds = json_decode($_POST['recipient_ids'] ?? '[]', true);

    if (!empty($emailBody) && !empty($recipientIds)) {
        $sentCount = 0;
        $failCount = 0;

        // Fetch recipient details
        $placeholders = implode(',', array_fill(0, count($recipientIds), '?'));
        $stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE id IN ({$placeholders})");
        $stmt->execute($recipientIds);
        $recipientList = $stmt->fetchAll();

        foreach ($recipientList as $recipient) {
            // Personalize: replace [Student Name] placeholder
            $personalizedBody = str_replace('[Student Name]', htmlspecialchars($recipient['full_name']), $emailBody);

            // Wrap in the UTP formal letterhead
            $wrappedEmail = \UTP\Services\Mailer::wrapLayout(
                $emailSubject . ' — UTP Scholarship System',
                $personalizedBody
            );

            try {
                $mail = \UTP\Services\Mailer::createMailer();
                $mail->addAddress($recipient['email'], $recipient['full_name']);
                $mail->Subject = $emailSubject;
                $mail->Body = $wrappedEmail;
                $mail->AltBody = strip_tags($personalizedBody);
                $mail->send();
                $sentCount++;
            } catch (\Exception $e) {
                error_log("Broadcast email failed for {$recipient['email']}: " . $e->getMessage());
                $failCount++;
            }
        }

        // Log the broadcast
        \UTP\Services\AuditLogger::log(
            $db,
            (int)$_SESSION['user_id'],
            'marketing_broadcast',
            'broadcast',
            null,
            "Sent {$sentCount} emails (Subject: {$emailSubject})"
        );

        $success = "Broadcast complete! Successfully sent {$sentCount} emails.";
        if ($failCount > 0) {
            $success .= " ({$failCount} failed — check error log.)";
        }
    } else {
        $error = "No email content or recipients to broadcast.";
    }
    } // end CSRF else
}

require_once __DIR__ . '/admin_header.php';
?>

<div class="flex-between mb-6">
    <div class="page-header" style="margin-bottom:0;">
        <h1>AI Broadcast Engine</h1>
        <p>Generate AI-powered marketing emails and broadcast them directly to targeted student segments.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="grid" style="grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">

    <!-- Controls Sidebar -->
    <div>
        <div class="card mb-6">
            <h3 style="font-size:1.1rem; font-weight:600; margin-bottom:16px;">📧 Campaign Configuration</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="generate">

                <div class="form-group">
                    <label class="form-label">Content Type</label>
                    <select name="template_type" class="form-input admin-focus" required>
                        <option value="Scholarship Announcement Email">Scholarship Announcement</option>
                        <option value="Application Deadline Reminder">Application Deadline Reminder</option>
                        <option value="Application Completion Nudge">Application Completion Nudge</option>
                        <option value="Congratulations & Next Steps">Congratulations & Next Steps</option>
                        <option value="Success Story Highlight">Success Story Highlight</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Target Audience</label>
                    <select name="target_audience" class="form-input admin-focus" required>
                        <?php foreach ($audienceLabels as $key => $label): ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
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
                    Generate AI Template
                </button>
            </form>
        </div>

        <?php if ($generatedContent && !empty($recipients)): ?>
        <!-- Recipient Panel -->
        <div class="card">
            <h3 style="font-size:1.1rem; font-weight:600; margin-bottom:12px;">🎯 Recipients Found</h3>
            <div style="background: var(--utp-teal); color: #fff; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; text-align:center;">
                <div style="font-size: 2rem; font-weight: 700;"><?= count($recipients) ?></div>
                <div style="font-size: 0.85rem; opacity: 0.9;">students matched</div>
            </div>
            <ul style="list-style:none; padding:0; margin:0; max-height: 200px; overflow-y: auto;">
                <?php foreach ($recipients as $r): ?>
                    <li style="padding:8px 0; border-bottom:1px solid var(--border-color); font-size:0.85rem;">
                        <div style="font-weight:600;"><?= htmlspecialchars($r['full_name']) ?></div>
                        <div style="color:var(--text-secondary); font-size:0.8rem;"><?= htmlspecialchars($r['email']) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php elseif ($generatedContent && empty($recipients)): ?>
        <div class="card">
            <h3 style="font-size:1.1rem; font-weight:600; margin-bottom:12px;">🎯 Recipients</h3>
            <p style="color:var(--text-muted); font-size:0.9rem;">No students match the selected audience criteria.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Output & Broadcast Panel -->
    <div class="card" style="min-height: 450px; display:flex; flex-direction:column;">
        <div class="flex-between mb-4" style="border-bottom: 1px solid var(--border-color); padding-bottom:16px;">
            <h3 style="font-size:1.1rem; font-weight:600; margin:0;">Email Preview</h3>
            <?php if ($generatedContent): ?>
                <a href="javascript:void(0);" onclick="copyContent()" class="btn btn-outline btn-sm">Copy HTML</a>
            <?php endif; ?>
        </div>

        <div style="flex-grow:1;">
            <?php if ($generatedContent): ?>
                <!-- Email Subject -->
                <div style="margin-bottom: 16px;">
                    <label style="font-size:0.85rem; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:4px;">Subject Line:</label>
                    <input type="text" id="email-subject" value="<?= htmlspecialchars($templateType . ' — Universiti Teknologi PETRONAS') ?>"
                           style="width:100%; padding:10px 12px; border:1px solid var(--border-color); border-radius:6px; font-size:0.95rem; background:var(--bg-secondary); color:var(--text-color);">
                </div>

                <!-- Editable email body -->
                <?php
                    $previewContent = \UTP\Security\InputSanitizer::sanitizeHtml($generatedContent);
                    // Show the first student's real name in the preview
                    if (!empty($recipients)) {
                        $previewContent = str_replace('[Student Name]', htmlspecialchars($recipients[0]['full_name']), $previewContent);
                    }
                ?>
                <div id="output-preview" contenteditable="true" style="padding:16px; border:1px solid var(--border-color); border-radius:6px; min-height:250px; outline:none; background:#fff; color:#1a1a2e;" class="raw-html-content">
                    <?= $previewContent ?>
                </div>

                <?php if (!empty($recipients)): ?>
                <!-- Personalization Info -->
                <div style="margin-top: 16px; padding: 12px 16px; background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 8px;">
                    <div style="font-size:0.85rem; font-weight:700; color:#166534; margin-bottom:6px;">✅ Auto-Personalization Active</div>
                    <div style="font-size:0.82rem; color:#15803d; line-height:1.5;">
                        Every <code style="background:#dcfce7; padding:2px 6px; border-radius:3px;">[Student Name]</code> in the email above will be automatically replaced with each student's real name from the database when sent.<br>
                        <strong>Example:</strong> <?= htmlspecialchars($recipients[0]['full_name']) ?> will receive: <em>"Congratulations, <?= htmlspecialchars($recipients[0]['full_name']) ?>!"</em>
                    </div>
                </div>

                <!-- Broadcast Button -->
                <form method="POST" id="broadcast-form" style="margin-top: 20px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\UTP\Security\CSRF::generateToken()) ?>">
                    <input type="hidden" name="action" value="broadcast">
                    <input type="hidden" name="email_subject" id="broadcast-subject" value="">
                    <input type="hidden" name="email_body" id="broadcast-body" value="">
                    <input type="hidden" name="recipient_ids" value='<?= json_encode(array_column($recipients, 'id')) ?>'>

                    <button type="submit" id="broadcast-btn" class="btn" style="width:100%; justify-content:center; padding:14px; font-size:1rem; font-weight:700; background: linear-gradient(135deg, #00A1B1, #0077B6); color:#fff; border:none; border-radius:8px; cursor:pointer; transition: all 0.3s ease;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:8px;"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        Broadcast Email to <?= count($recipients) ?> Student<?= count($recipients) > 1 ? 's' : '' ?>
                    </button>
                    <p style="text-align:center; font-size:0.8rem; color:var(--text-muted); margin-top:8px;">
                        Each student receives a personalized email with their real name, wrapped in UTP's formal letterhead.
                    </p>
                </form>
                <?php endif; ?>

            <?php else: ?>
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; min-height:300px; color:var(--text-muted); text-align:center;">
                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin-bottom:16px; opacity:0.5;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path>
                    </svg>
                    <p>Configure your campaign on the left and click <strong>"Generate AI Template"</strong>.<br>The AI will write the email, then you can broadcast it to students directly.</p>
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
    #broadcast-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 161, 177, 0.4); }
</style>

<script nonce="<?= $GLOBALS['csp_nonce'] ?>">
function copyContent() {
    const content = document.getElementById('output-preview').innerHTML;
    navigator.clipboard.writeText(content).then(() => {
        alert("HTML copied to clipboard!");
    }).catch(err => {
        console.error('Failed to copy: ', err);
        alert("Failed to copy content.");
    });
}

// Before broadcasting, capture the editable content
const broadcastForm = document.getElementById('broadcast-form');
if (broadcastForm) {
    broadcastForm.addEventListener('submit', function(e) {
        document.getElementById('broadcast-subject').value = document.getElementById('email-subject').value;
        document.getElementById('broadcast-body').value = document.getElementById('output-preview').innerHTML;

        // Confirmation
        const count = <?= !empty($recipients) ? count($recipients) : 0 ?>;
        if (!confirm(`Are you sure you want to broadcast this email to ${count} student(s)?`)) {
            e.preventDefault();
        }
    });
}
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
