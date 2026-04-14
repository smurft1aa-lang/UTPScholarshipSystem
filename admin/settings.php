<?php
/**
 * Admin Settings
 */
require_once __DIR__ . '/admin_header.php';

$db = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'change_password') {
        $currentPw = $_POST['current_password'] ?? '';
        $newPw = $_POST['new_password'] ?? '';
        $confirmPw = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($currentPw, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif ($newPw !== $confirmPw) {
            $error = 'New passwords do not match.';
        } else {
            $pwErrors = validatePassword($newPw);
            if (!empty($pwErrors)) {
                $error = implode(' ', $pwErrors);
            } else {
                $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hash, $_SESSION['user_id']]);
                $success = 'Password updated successfully.';
            }
        }
    } elseif ($action === 'add_admin') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$fullName || !$email || !$password) {
            $error = 'All fields are required.';
        } elseif (!validateEmail($email)) {
            $error = 'Invalid email address.';
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already exists.';
            } else {
                $pwErrors = validatePassword($password);
                if (!empty($pwErrors)) {
                    $error = implode(' ', $pwErrors);
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, ic_number, phone, role) VALUES (?, ?, ?, '', '', 'admin')");
                    $stmt->execute([$fullName, $email, $hash]);
                    $success = 'Admin account created.';
                }
            }
        }
    }
}

// Get all admins
$admins = $db->query("SELECT id, full_name, email, created_at FROM users WHERE role = 'admin' ORDER BY created_at")->fetchAll();

// System stats
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalApps = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
?>

<div class="page-header">
    <h1>Settings</h1>
    <p>Manage admin accounts and system settings.</p>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="grid-2">
    <!-- Change Password -->
    <div class="card">
        <h3 style="font-size:1.05rem; margin-bottom:16px;">Change Password</h3>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="change_password">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-input admin-focus" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-input admin-focus" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-input admin-focus" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-purple btn-sm">Update Password</button>
        </form>
    </div>

    <!-- Add Admin -->
    <div class="card">
        <h3 style="font-size:1.05rem; margin-bottom:16px;">Add Admin Account</h3>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="add_admin">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-input admin-focus" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input admin-focus" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input admin-focus" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-purple btn-sm">Create Admin</button>
        </form>
    </div>
</div>

<!-- Admin Accounts -->
<div class="card mt-6">
    <h3 style="font-size:1.05rem; margin-bottom:16px;">Admin Accounts</h3>
    <div class="table-wrap" style="border:none; box-shadow:none;">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Created</th></tr></thead>
            <tbody>
            <?php foreach ($admins as $admin): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($admin['full_name']) ?></strong></td>
                    <td><?= htmlspecialchars($admin['email']) ?></td>
                    <td><?= date('d M Y', strtotime($admin['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- System Info -->
<div class="card mt-6">
    <h3 style="font-size:1.05rem; margin-bottom:16px;">System Information</h3>
    <div class="table-wrap" style="border:none; box-shadow:none;">
        <table>
            <tr><td style="width:200px; font-weight:600;">Registered Students</td><td><?= $totalUsers ?></td></tr>
            <tr><td style="font-weight:600;">Total Applications</td><td><?= $totalApps ?></td></tr>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
