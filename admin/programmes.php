<?php
/**
 * Admin Programmes & Entry Requirements Management
 */
require_once __DIR__ . '/admin_header.php';

$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'add_programme') {
        $name = sanitize($_POST['name'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        if ($name && $category) {
            $stmt = $db->prepare("INSERT INTO programmes (name, category, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $category, $description]);
        }
    } elseif ($action === 'update_programme') {
        $id = intval($_POST['programme_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($id && $name) {
            $stmt = $db->prepare("UPDATE programmes SET name = ?, category = ?, description = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $category, $description, $isActive, $id]);
        }
    } elseif ($action === 'add_requirement') {
        $progId = intval($_POST['programme_id'] ?? 0);
        $qualType = sanitize($_POST['qual_type'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $minGrade = sanitize($_POST['min_grade'] ?? '');
        $weight = floatval($_POST['weight'] ?? 1.0);
        if ($progId && $qualType && $subject && $minGrade) {
            $stmt = $db->prepare("INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$progId, $qualType, $subject, $minGrade, $weight]);
        }
    } elseif ($action === 'delete_requirement') {
        $reqId = intval($_POST['req_id'] ?? 0);
        if ($reqId) {
            $stmt = $db->prepare("DELETE FROM entry_requirements WHERE id = ?");
            $stmt->execute([$reqId]);
        }
    }

    header('Location: /admin/programmes.php' . (isset($_POST['programme_id']) ? '?edit=' . intval($_POST['programme_id']) : ''));
    exit;
}

// Get programmes
$programmes = $db->query("SELECT p.id, p.name, p.category, p.description, p.is_active, (SELECT COUNT(*) FROM entry_requirements er WHERE er.programme_id = p.id) as req_count FROM programmes p ORDER BY p.category, p.name")->fetchAll();

// Edit mode
$editProg = null;
$editReqs = [];
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT id, name, category, description, is_active FROM programmes WHERE id = ?");
    $stmt->execute([$editId]);
    $editProg = $stmt->fetch();

    if ($editProg) {
        $stmt = $db->prepare("SELECT id, programme_id, qual_type, subject, min_grade, weight FROM entry_requirements WHERE programme_id = ? ORDER BY qual_type, subject");
        $stmt->execute([$editId]);
        $editReqs = $stmt->fetchAll();
    }
}
?>

<div class="page-header">
    <h1>Programmes & Entry Requirements</h1>
    <p>Manage foundation programmes and their entry requirements.</p>
</div>

<div class="flex-between mb-4">
    <span style="font-size:0.9rem; color:var(--text-secondary);"><?= count($programmes) ?> programmes</span>
    <button data-modal-target="add_programme_modal" class="btn btn-purple btn-sm">Add Programme</button>
</div>

<?php if ($editProg): ?>
<!-- Edit Programme Details -->
<div class="card mb-6">
    <div class="flex-between mb-4">
        <h3 style="font-size:1.05rem;">Editing: <?= htmlspecialchars($editProg['name']) ?></h3>
        <a href="/admin/programmes.php" class="btn btn-outline btn-sm">Back to List</a>
    </div>

    <form method="POST" style="margin-bottom:24px;">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="update_programme">
        <input type="hidden" name="programme_id" value="<?= $editProg['id'] ?>">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Programme Name</label>
                <input type="text" name="name" class="form-input admin-focus" value="<?= htmlspecialchars($editProg['name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category" class="form-select" required>
                    <option value="Engineering & Science" <?= $editProg['category'] === 'Engineering & Science' ? 'selected' : '' ?>>Engineering & Science</option>
                    <option value="Technology" <?= $editProg['category'] === 'Technology' ? 'selected' : '' ?>>Technology</option>
                    <option value="Computer Science" <?= $editProg['category'] === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                    <option value="Business Management" <?= $editProg['category'] === 'Business Management' ? 'selected' : '' ?>>Business Management</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-input admin-focus" rows="2"><?= htmlspecialchars($editProg['description']) ?></textarea>
        </div>
        <div class="form-group">
            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                <input type="checkbox" name="is_active" <?= $editProg['is_active'] ? 'checked' : '' ?>> Active
            </label>
        </div>
        <button type="submit" class="btn btn-purple btn-sm">Save Changes</button>
    </form>

    <!-- Entry Requirements -->
    <h4 style="font-size:0.95rem; font-weight:600; margin-bottom:12px;">Entry Requirements (<?= count($editReqs) ?>)</h4>

    <?php if (!empty($editReqs)): ?>
    <div class="table-wrap mb-4">
        <table>
            <thead><tr><th>Qualification</th><th>Subject</th><th>Min Grade</th><th>Weight</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($editReqs as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['qual_type']) ?></td>
                    <td><?= htmlspecialchars($req['subject']) ?></td>
                    <td><strong><?= htmlspecialchars($req['min_grade']) ?></strong></td>
                    <td><?= $req['weight'] ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this requirement?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="form_action" value="delete_requirement">
                            <input type="hidden" name="req_id" value="<?= $req['id'] ?>">
                            <input type="hidden" name="programme_id" value="<?= $editProg['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Add Requirement -->
    <form method="POST" class="card card-flat" style="background:var(--bg-page); padding:18px;">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="add_requirement">
        <input type="hidden" name="programme_id" value="<?= $editProg['id'] ?>">
        <h5 style="font-size:0.85rem; font-weight:600; margin-bottom:12px;">Add Requirement</h5>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Qualification Type</label>
                <select name="qual_type" class="form-select" required>
                    <option value="SPM">SPM</option>
                    <option value="O-Level">O-Level</option>
                    <option value="IGCSE">IGCSE</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-input admin-focus" placeholder="e.g. Mathematics" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Minimum Grade</label>
                <input type="text" name="min_grade" class="form-input admin-focus" placeholder="e.g. C" required>
            </div>
            <div class="form-group">
                <label class="form-label">Weight (0.00 - 1.00)</label>
                <input type="number" name="weight" class="form-input admin-focus" value="1.00" min="0" max="1" step="0.05" required>
            </div>
        </div>
        <button type="submit" class="btn btn-purple btn-sm">Add Requirement</button>
    </form>
</div>

<?php else: ?>
<!-- Programme List -->
<div class="table-wrap">
    <table>
        <thead><tr><th>Programme</th><th>Category</th><th>Requirements</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($programmes as $prog): ?>
            <tr>
                <td><strong><?= htmlspecialchars($prog['name']) ?></strong></td>
                <td><?= htmlspecialchars($prog['category']) ?></td>
                <td><?= $prog['req_count'] ?> rules</td>
                <td><span class="badge badge-<?= $prog['is_active'] ? 'green' : 'red' ?>"><?= $prog['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td><a href="/admin/programmes.php?edit=<?= $prog['id'] ?>" class="btn btn-outline btn-sm">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Add Programme Modal -->
<div class="modal-overlay" id="add_programme_modal">
    <div class="modal">
        <h2>Add Programme</h2>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="add_programme">
            <div class="form-group">
                <label class="form-label">Programme Name</label>
                <input type="text" name="name" class="form-input admin-focus" required>
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category" class="form-select" required>
                    <option value="Engineering & Science">Engineering & Science</option>
                    <option value="Technology">Technology</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Business Management">Business Management</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input admin-focus" rows="3"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" data-modal-close class="btn btn-outline btn-sm">Cancel</button>
                <button type="submit" class="btn btn-purple btn-sm">Add Programme</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
