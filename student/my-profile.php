<?php
/**
 * Student Profile Page
 */
require_once __DIR__ . '/../includes/init.php';
requireVerified();

$db = getDB();
$userId = $_SESSION['user_id'];

// Get user
$stmt = $db->prepare("SELECT id, full_name, email, ic_number, phone, password_hash, email_verified FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $old_pwd = $_POST['old_password'] ?? '';
        $new_pwd = $_POST['new_password'] ?? '';
        $confirm_pwd = $_POST['confirm_password'] ?? '';

        if (!password_verify($old_pwd, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif ($new_pwd !== $confirm_pwd) {
            $error = 'New passwords do not match.';
        } else {
            $pwErrors = validatePassword($new_pwd);
            if (!empty($pwErrors)) {
                $error = implode('<br>', $pwErrors);
            } else {
                $hash = password_hash($new_pwd, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hash, $userId]);
                $success = 'Password successfully updated.';
                
                logAudit($userId, 'Password Changed');
            }
        }
    }
}

$pageTitle = 'My Profile — UTP Scholarship System';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:32px; padding-bottom:48px;">
    <div class="page-header">
        <h1>My Profile</h1>
        <p>View your personal details and change your password.</p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger mb-4"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="grid-2">
        <div class="card">
            <h3 style="margin-bottom:16px;">Personal Information</h3>
            <table class="table" style="box-shadow:none; border:none; margin:0;">
                <tr>
                    <td style="width:35%; color:var(--text-secondary); border-top:none;"><strong>Full Name</strong></td>
                    <td style="border-top:none;"><?= htmlspecialchars($user['full_name']) ?></td>
                </tr>
                <tr>
                    <td style="color:var(--text-secondary);"><strong>Email Address</strong></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                </tr>
                <tr>
                    <td style="color:var(--text-secondary);"><strong>IC / Passport</strong></td>
                    <td><?= htmlspecialchars($user['ic_number']) ?></td>
                </tr>
                <tr>
                    <td style="color:var(--text-secondary);"><strong>Phone Number</strong></td>
                    <td><?= htmlspecialchars($user['phone']) ?></td>
                </tr>
                <tr>
                    <td style="color:var(--text-secondary);"><strong>Account Type</strong></td>
                    <td><span class="badge badge-blue">Student</span></td>
                </tr>
                <tr>
                    <td style="color:var(--text-secondary); border-bottom:none;"><strong>Verification Status</strong></td>
                    <td style="border-bottom:none;">
                        <?php if ($user['email_verified']): ?>
                            <span class="badge badge-green">Verified</span>
                        <?php else: ?>
                            <span class="badge badge-yellow">Unverified</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h3 style="margin-bottom:16px;">Change Password</h3>
            <form method="POST">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label" for="old_password">Current Password</label>
                    <input type="password" id="old_password" name="old_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" required>
                    <small style="color:var(--text-muted); display:block; margin-top:4px;">Min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special character.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-orange">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
