<?php
/**
 * Admin Scholarships Management
 */
require_once __DIR__ . '/admin_header.php';
require_once __DIR__ . '/../includes/security.php';

$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'add_scholarship') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $budgetMin = floatval($_POST['budget_min'] ?? 0);
        $budgetMax = floatval($_POST['budget_max'] ?? 0);
        $minFit = intval($_POST['min_fit_percentage'] ?? 50);
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;
        $programmeIds = $_POST['programme_ids'] ?? [];

        if ($name) {
            $stmt = $db->prepare("INSERT INTO scholarships (name, description, budget_min, budget_max, min_fit_percentage, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $budgetMin, $budgetMax, $minFit, $startDate ?: null, $endDate ?: null]);
            $schId = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO scholarship_programme (scholarship_id, programme_id) VALUES (?, ?)");
            foreach ($programmeIds as $pid) {
                $stmt->execute([$schId, intval($pid)]);
            }
        }
    } elseif ($action === 'update_scholarship') {
        $id = intval($_POST['scholarship_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $budgetMin = floatval($_POST['budget_min'] ?? 0);
        $budgetMax = floatval($_POST['budget_max'] ?? 0);
        $minFit = intval($_POST['min_fit_percentage'] ?? 50);
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $programmeIds = $_POST['programme_ids'] ?? [];

        if ($id && $name) {
            $stmt = $db->prepare("UPDATE scholarships SET name = ?, description = ?, budget_min = ?, budget_max = ?, min_fit_percentage = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $description, $budgetMin, $budgetMax, $minFit, $startDate ?: null, $endDate ?: null, $isActive, $id]);

            $db->prepare("DELETE FROM scholarship_programme WHERE scholarship_id = ?")->execute([$id]);
            $stmt = $db->prepare("INSERT INTO scholarship_programme (scholarship_id, programme_id) VALUES (?, ?)");
            foreach ($programmeIds as $pid) {
                $stmt->execute([$id, intval($pid)]);
            }
        }
    } elseif ($action === 'delete_scholarship') {
        $id = intval($_POST['scholarship_id'] ?? 0);
        if ($id) {
            $db->prepare("DELETE FROM scholarships WHERE id = ?")->execute([$id]);
        }
    }

    header('Location: /admin/scholarships.php');
    exit;
}

// Build query for filtering
$statusFilter = $_GET['status'] ?? '';
$searchStr = sanitize($_GET['search'] ?? '');

$where = [];
$params = [];

if ($statusFilter !== '') {
    $where[] = "s.is_active = ?";
    $params[] = intval($statusFilter);
}
if ($searchStr !== '') {
    $where[] = "(s.name LIKE ? OR s.description LIKE ?)";
    $params[] = "%{$searchStr}%";
    $params[] = "%{$searchStr}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT s.*, (SELECT GROUP_CONCAT(p.name SEPARATOR ', ') FROM scholarship_programme sp JOIN programmes p ON sp.programme_id = p.id WHERE sp.scholarship_id = s.id) as programme_names FROM scholarships s $whereClause ORDER BY s.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$scholarships = $stmt->fetchAll();

$allProgrammes = $db->query("SELECT id, name FROM programmes WHERE is_active = 1 ORDER BY name")->fetchAll();

$editSch = null;
$editProgIds = [];
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM scholarships WHERE id = ?");
    $stmt->execute([$editId]);
    $editSch = $stmt->fetch();
    if ($editSch) {
        $stmt = $db->prepare("SELECT programme_id FROM scholarship_programme WHERE scholarship_id = ?");
        $stmt->execute([$editId]);
        $editProgIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>

<div class="page-header">
    <h1>Scholarships & Sponsorships</h1>
    <p>Manage available scholarships and their programme associations.</p>
</div>

<div class="flex-between mb-4">
    <span style="font-size:0.9rem; color:var(--text-secondary);"><?= count($scholarships) ?> scholarships found</span>
    <button data-modal-target="add_sch_modal" class="btn btn-purple btn-sm">Add Scholarship</button>
</div>

<?php if (!$editSch): ?>
<!-- Filters -->
<div class="card mb-4" style="padding:16px 20px;">
    <form method="GET" class="flex" style="gap:12px; align-items:center; flex-wrap:wrap;">
        <select name="status" class="form-select" style="width:auto; min-width:160px;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <input type="text" name="search" class="form-input admin-focus" style="width:auto; min-width:240px; flex-grow:1;" placeholder="Search by name or description..." value="<?= htmlspecialchars($searchStr) ?>">
        <button type="submit" class="btn btn-purple btn-sm">Search</button>
        <?php if ($statusFilter !== '' || $searchStr !== ''): ?>
            <a href="/admin/scholarships.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>
<?php endif; ?>

<?php if ($editSch): ?>
<!-- Edit Scholarship -->
<div class="card mb-6">
    <div class="flex-between mb-4">
        <h3 style="font-size:1.05rem;">Editing: <?= htmlspecialchars($editSch['name']) ?></h3>
        <a href="/admin/scholarships.php" class="btn btn-outline btn-sm">Back</a>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="update_scholarship">
        <input type="hidden" name="scholarship_id" value="<?= $editSch['id'] ?>">
        <div class="form-group">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-input admin-focus" value="<?= htmlspecialchars($editSch['name']) ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-input admin-focus" rows="3"><?= htmlspecialchars($editSch['description']) ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Budget Min (RM)</label>
                <input type="number" name="budget_min" class="form-input admin-focus" value="<?= $editSch['budget_min'] ?>" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">Budget Max (RM)</label>
                <input type="number" name="budget_max" class="form-input admin-focus" value="<?= $editSch['budget_max'] ?>" step="0.01">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Min Fit Percentage (%)</label>
                <input type="number" name="min_fit_percentage" class="form-input admin-focus" value="<?= $editSch['min_fit_percentage'] ?>" min="0" max="100">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <label style="display:flex; align-items:center; gap:8px; margin-top:8px; font-size:0.9rem; cursor:pointer;">
                    <input type="checkbox" name="is_active" <?= $editSch['is_active'] ? 'checked' : '' ?>> Active
                </label>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-input admin-focus" value="<?= $editSch['start_date'] ?>">
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-input admin-focus" value="<?= $editSch['end_date'] ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Linked Programmes</label>
            <div style="max-height:200px; overflow-y:auto; border:1px solid var(--border); border-radius:var(--radius); padding:10px;">
                <?php foreach ($allProgrammes as $p): ?>
                    <label style="display:flex; align-items:center; gap:8px; padding:4px 0; font-size:0.9rem; cursor:pointer;">
                        <input type="checkbox" name="programme_ids[]" value="<?= $p['id'] ?>" <?= in_array($p['id'], $editProgIds) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="btn btn-purple btn-sm">Save Changes</button>
        </div>
    </form>
    <form method="POST" style="margin-top:16px;" onsubmit="return confirm('Delete this scholarship?')">
        <?= csrfField() ?>
        <input type="hidden" name="form_action" value="delete_scholarship">
        <input type="hidden" name="scholarship_id" value="<?= $editSch['id'] ?>">
        <button type="submit" class="btn btn-danger btn-sm">Delete Scholarship</button>
    </form>
</div>

<?php else: ?>
<!-- Scholarships List -->
<?php if (empty($scholarships)): ?>
    <div class="card text-center" style="padding:48px; color:var(--text-muted);">
        No scholarships found matching your criteria.
    </div>
<?php else: ?>
<div class="grid-auto">
    <?php foreach ($scholarships as $s): ?>
    <div class="result-card">
        <div class="result-card-header">
            <span class="badge badge-<?= $s['is_active'] ? 'green' : 'red' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span>
            <a href="/admin/scholarships.php?edit=<?= $s['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
        </div>
        <h3><?= htmlspecialchars($s['name']) ?></h3>
        <p><?= htmlspecialchars(substr($s['description'], 0, 120)) ?><?= strlen($s['description']) > 120 ? '...' : '' ?></p>
        <div class="result-card-footer">
            <span>RM <?= number_format($s['budget_min']) ?> - RM <?= number_format($s['budget_max']) ?></span>
            <span>Min Fit: <?= $s['min_fit_percentage'] ?>%</span>
        </div>
        <?php if ($s['programme_names']): ?>
        <p style="font-size:0.78rem; color:var(--text-muted); margin-top:8px;">
            <?= htmlspecialchars($s['programme_names']) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Add Scholarship Modal -->
<div class="modal-overlay" id="add_sch_modal">
    <div class="modal">
        <h2>Add Scholarship</h2>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="add_scholarship">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-input admin-focus" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input admin-focus" rows="3"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Budget Min (RM)</label>
                    <input type="number" name="budget_min" class="form-input admin-focus" step="0.01" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Budget Max (RM)</label>
                    <input type="number" name="budget_max" class="form-input admin-focus" step="0.01" value="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Min Fit %</label>
                    <input type="number" name="min_fit_percentage" class="form-input admin-focus" value="50" min="0" max="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-input admin-focus">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-input admin-focus">
            </div>
            <div class="form-group">
                <label class="form-label">Linked Programmes</label>
                <div style="max-height:150px; overflow-y:auto; border:1px solid var(--border); border-radius:var(--radius); padding:10px;">
                    <?php foreach ($allProgrammes as $p): ?>
                        <label style="display:flex; align-items:center; gap:8px; padding:4px 0; font-size:0.9rem; cursor:pointer;">
                            <input type="checkbox" name="programme_ids[]" value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" data-modal-close class="btn btn-outline btn-sm">Cancel</button>
                <button type="submit" class="btn btn-purple btn-sm">Add Scholarship</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
