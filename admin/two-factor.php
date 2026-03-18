<?php
require_once __DIR__ . '/../includes/init.php';
setSecurityHeaders();
requireAdmin();

$db = getDB();
$userId = $_SESSION['user_id'];
$email = $_SESSION['email'] ?? '';

$twoFA = new \UTP\Security\TwoFactorAuth($db);
$is2FAEnabled = $twoFA->isEnabled($userId);

$message = '';
$error = '';
$qrUri = '';
$secret = '';

// Handle 2FA setup/verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'generate') {
            // Generate new TOTP secret
            if (!$email) {
                $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $email = $stmt->fetchColumn();
            }
            $result = $twoFA->generateSecret($userId, $email);
            $qrUri = $result['provisioningUri'];
            $secret = $result['secret'];
        } elseif ($action === 'verify') {
            $code = trim($_POST['totp_code'] ?? '');
            if ($twoFA->verifyCode($userId, $code)) {
                $message = 'Two-factor authentication enabled successfully!';
                $is2FAEnabled = true;
            } else {
                $error = 'Invalid verification code. Please try again.';
                // Re-show the QR code
                $stmt = $db->prepare("SELECT totp_secret, email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $row = $stmt->fetch();
                if ($row && $row['totp_secret']) {
                    $totp = \OTPHP\TOTP::createFromSecret($row['totp_secret']);
                    $totp->setLabel($row['email']);
                    $totp->setIssuer('UTP Scholarship System');
                    $qrUri = $totp->getProvisioningUri();
                    $secret = $row['totp_secret'];
                }
            }
        } elseif ($action === 'disable') {
            $twoFA->disable($userId);
            $message = 'Two-factor authentication has been disabled.';
            $is2FAEnabled = false;
        }
    }
}

$pageTitle = 'Two-Factor Authentication';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="admin-main">
    <div class="container" style="max-width:600px; margin:2rem auto; padding:2rem;">
        <h1 style="margin-bottom:1.5rem;">🔐 Two-Factor Authentication</h1>

        <?php if ($message): ?>
            <div class="alert alert-success" style="padding:1rem; background:#d4edda; border-radius:8px; margin-bottom:1rem;">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="padding:1rem; background:#f8d7da; border-radius:8px; margin-bottom:1rem;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($is2FAEnabled): ?>
            <div style="padding:2rem; background:var(--bg-card, #f8f9fa); border-radius:12px; text-align:center;">
                <div style="font-size:3rem; margin-bottom:1rem;">✅</div>
                <h3>2FA is Enabled</h3>
                <p style="color:#6c757d; margin:1rem 0;">Your account is protected with two-factor authentication.</p>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="disable">
                    <button type="submit" class="btn btn-danger" style="padding:0.75rem 2rem; background:#dc3545; color:#fff; border:none; border-radius:8px; cursor:pointer;"
                            onclick="return confirm('Are you sure you want to disable 2FA?')">
                        Disable 2FA
                    </button>
                </form>
            </div>

        <?php elseif ($qrUri): ?>
            <div style="padding:2rem; background:var(--bg-card, #f8f9fa); border-radius:12px;">
                <h3 style="margin-bottom:1rem;">Step 1: Scan QR Code</h3>
                <p style="margin-bottom:1rem;">Open <strong>Google Authenticator</strong> (or similar app) and scan this QR code:</p>
                <div style="text-align:center; margin:1.5rem 0;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($qrUri) ?>"
                         alt="2FA QR Code" style="border-radius:8px; border:2px solid #dee2e6;">
                </div>
                <p style="font-size:0.85rem; color:#6c757d; word-break:break-all;">
                    <strong>Manual key:</strong> <?= e($secret) ?>
                </p>

                <h3 style="margin:1.5rem 0 1rem;">Step 2: Enter Verification Code</h3>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="verify">
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="text" name="totp_code" placeholder="Enter 6-digit code"
                               maxlength="6" pattern="[0-9]{6}" required
                               style="flex:1; padding:0.75rem; border:1px solid #dee2e6; border-radius:8px; font-size:1.25rem; text-align:center; letter-spacing:0.5em;">
                        <button type="submit" class="btn btn-primary"
                                style="padding:0.75rem 1.5rem; background:var(--primary, #4f46e5); color:#fff; border:none; border-radius:8px; cursor:pointer;">
                            Verify
                        </button>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <div style="padding:2rem; background:var(--bg-card, #f8f9fa); border-radius:12px; text-align:center;">
                <div style="font-size:3rem; margin-bottom:1rem;">🛡️</div>
                <h3>Protect Your Admin Account</h3>
                <p style="color:#6c757d; margin:1rem 0;">
                    Add an extra layer of security by enabling two-factor authentication.
                    You'll need a TOTP app like <strong>Google Authenticator</strong>.
                </p>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="generate">
                    <button type="submit" class="btn btn-primary"
                            style="padding:0.75rem 2rem; background:var(--primary, #4f46e5); color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:1.1rem;">
                        Enable 2FA
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div style="margin-top:1.5rem; text-align:center;">
            <a href="/admin/dashboard.php" style="color:var(--primary, #4f46e5);">← Back to Dashboard</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
