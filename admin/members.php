<?php
/**
 * Member Management - List, Search & Filter
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
sync_membership_statuses($pdo);

// Filtering & Search
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$genderFilter = trim($_GET['gender'] ?? 'all');

$sql = "
    SELECT m.id AS member_id, m.gender, m.dob, m.join_date, m.status,
           u.id AS user_id, u.name, u.email, u.phone,
           tu.name AS trainer_name,
           (SELECT mp.plan_name 
            FROM memberships ms 
            JOIN membership_plans mp ON ms.plan_id = mp.id 
            WHERE ms.member_id = m.id AND ms.status = 'active' 
            ORDER BY ms.id DESC LIMIT 1) AS active_plan
    FROM members m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN trainers t ON m.trainer_id = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $likeSearch = "%{$search}%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

if ($statusFilter !== 'all' && in_array($statusFilter, ['active', 'inactive'])) {
    $sql .= " AND m.status = ?";
    $params[] = $statusFilter;
}

if ($genderFilter !== 'all' && in_array($genderFilter, ['Male', 'Female', 'Other'])) {
    $sql .= " AND m.gender = ?";
    $params[] = $genderFilter;
}

$sql .= " ORDER BY m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

$pageTitle = 'Member Management';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Gym Members</h3>
        <p class="text-muted small mb-0">Total <?= count($members) ?> member(s) listed.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/member_add.php" class="btn btn-primary px-3">
            <i class="fa-solid fa-user-plus me-1"></i> Register New Member
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="members.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Members</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Members</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="gender" class="form-select">
                    <option value="all" <?= $genderFilter === 'all' ? 'selected' : '' ?>>All Genders</option>
                    <option value="Male" <?= $genderFilter === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $genderFilter === 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= $genderFilter === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <?php if (!empty($search) || $statusFilter !== 'all' || $genderFilter !== 'all'): ?>
                    <a href="members.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Members Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Contact</th>
                        <th>Active Plan</th>
                        <th>Trainer</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($members)): ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td class="fw-bold text-muted">#M<?= str_pad($m['member_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                                    <div class="small text-muted">
                                        <i class="fa-solid <?= $m['gender'] === 'Female' ? 'fa-venus text-danger' : 'fa-mars text-primary' ?> me-1"></i>
                                        <?= htmlspecialchars($m['gender']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div><i class="fa-regular fa-envelope me-1 text-muted small"></i><?= htmlspecialchars($m['email']) ?></div>
                                    <div class="small text-muted"><i class="fa-solid fa-phone me-1 text-muted small"></i><?= htmlspecialchars($m['phone'] ?? 'N/A') ?></div>
                                </td>
                                <td>
                                    <?php if ($m['active_plan']): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><?= htmlspecialchars($m['active_plan']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">No Active Plan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($m['trainer_name']): ?>
                                        <div class="small fw-semibold text-dark"><i class="fa-solid fa-user-ninja text-warning me-1"></i><?= htmlspecialchars($m['trainer_name']) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= format_date($m['join_date']) ?></td>
                                <td><?= get_status_badge($m['status']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/admin/member_view.php?id=<?= $m['member_id'] ?>" class="btn btn-outline-secondary" title="View Details">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/member_edit.php?id=<?= $m['member_id'] ?>" class="btn btn-outline-primary" title="Edit Member">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/member_delete.php?id=<?= $m['member_id'] ?>" class="btn btn-outline-danger" title="Delete Member" data-confirm-delete="Are you sure you want to delete member '<?= htmlspecialchars($m['name']) ?>'? All associated memberships, attendances, and payments will also be deleted.">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-users-slash display-6 d-block mb-2 opacity-50"></i>
                                No members match your search/filter criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
