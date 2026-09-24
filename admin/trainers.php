<?php
/**
 * Trainer Management - List & Search
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

$sql = "
    SELECT t.id AS trainer_id, t.specialization, t.joining_date, t.status, t.bio,
           u.id AS user_id, u.name, u.email, u.phone,
           (SELECT COUNT(*) FROM members m WHERE m.trainer_id = t.id AND m.status = 'active') AS assigned_members_count
    FROM trainers t
    JOIN users u ON t.user_id = u.id
    WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR t.specialization LIKE ?)";
    $likeSearch = "%{$search}%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

if ($statusFilter !== 'all' && in_array($statusFilter, ['active', 'inactive'])) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trainers = $stmt->fetchAll();

$pageTitle = 'Trainer Management';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Gym Personal Trainers</h3>
        <p class="text-muted small mb-0">Total <?= count($trainers) ?> fitness trainer(s) registered.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/trainer_add.php" class="btn btn-primary px-3">
            <i class="fa-solid fa-user-plus me-1"></i> Add New Trainer
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="trainers.php" class="row g-2 align-items-center">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, email, or specialization..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Trainers</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Trainers</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <?php if (!empty($search) || $statusFilter !== 'all'): ?>
                    <a href="trainers.php" class="btn btn-outline-secondary" title="Clear"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Trainers Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>ID</th>
                        <th>Trainer Name</th>
                        <th>Specialization</th>
                        <th>Contact</th>
                        <th>Assigned Members</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($trainers)): ?>
                        <?php foreach ($trainers as $t): ?>
                            <tr>
                                <td class="fw-bold text-muted">#TR<?= str_pad($t['trainer_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($t['name']) ?></div>
                                    <div class="small text-muted"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($t['email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-warning bg-opacity-25 text-dark fw-semibold px-2 py-1">
                                        <?= htmlspecialchars($t['specialization']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small"><i class="fa-solid fa-phone me-1 text-muted"></i><?= htmlspecialchars($t['phone'] ?? 'N/A') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1">
                                        <i class="fa-solid fa-users me-1"></i> <?= (int)$t['assigned_members_count'] ?> active
                                    </span>
                                </td>
                                <td class="small text-muted"><?= format_date($t['joining_date']) ?></td>
                                <td><?= get_status_badge($t['status']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/admin/trainer_view.php?id=<?= $t['trainer_id'] ?>" class="btn btn-outline-secondary" title="View Profile">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/trainer_edit.php?id=<?= $t['trainer_id'] ?>" class="btn btn-outline-primary" title="Edit Trainer">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/trainer_delete.php?id=<?= $t['trainer_id'] ?>" class="btn btn-outline-danger" title="Delete Trainer" data-confirm-delete="Are you sure you want to delete coach '<?= htmlspecialchars($t['name']) ?>'? Members assigned to this coach will become unassigned.">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-user-slash display-6 d-block mb-2 opacity-50"></i>
                                No trainers found matching your search.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
