<?php
/**
 * Memberships (Subscriptions) Management
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
sync_membership_statuses($pdo);

$statusFilter = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT ms.*, u.name AS member_name, u.email AS member_email, u.phone AS member_phone,
           mp.plan_name, mp.duration_days, mp.price
    FROM memberships ms
    JOIN members m ON ms.member_id = m.id
    JOIN users u ON m.user_id = u.id
    JOIN membership_plans mp ON ms.plan_id = mp.id
    WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR mp.plan_name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($statusFilter !== 'all' && in_array($statusFilter, ['active', 'expired', 'pending', 'cancelled'])) {
    $sql .= " AND ms.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY ms.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$memberships = $stmt->fetchAll();

$pageTitle = 'Memberships & Subscriptions';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Member Subscriptions</h3>
        <p class="text-muted small mb-0">Total <?= count($memberships) ?> subscription record(s).</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/membership_assign.php" class="btn btn-primary px-3">
            <i class="fa-solid fa-plus me-1"></i> Assign Plan to Member
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="memberships.php" class="row g-2 align-items-center">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by member name, email or plan..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <?php if (!empty($search) || $statusFilter !== 'all'): ?>
                    <a href="memberships.php" class="btn btn-outline-secondary" title="Clear"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Subscriptions Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Subscription ID</th>
                        <th>Member</th>
                        <th>Plan</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Validity</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($memberships)): ?>
                        <?php foreach ($memberships as $ms): ?>
                            <?php
                            $daysDiff = (int)ceil((strtotime($ms['end_date']) - time()) / 86400);
                            ?>
                            <tr>
                                <td class="fw-bold text-muted">#SUB-<?= str_pad($ms['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/admin/member_view.php?id=<?= $ms['member_id'] ?>" class="text-decoration-none fw-bold text-dark">
                                        <?= htmlspecialchars($ms['member_name']) ?>
                                    </a>
                                    <div class="small text-muted"><?= htmlspecialchars($ms['member_email']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary"><?= htmlspecialchars($ms['plan_name']) ?></div>
                                    <div class="small text-muted"><?= format_currency($ms['price']) ?> &bull; <?= (int)$ms['duration_days'] ?> Days</div>
                                </td>
                                <td><?= format_date($ms['start_date']) ?></td>
                                <td class="fw-semibold"><?= format_date($ms['end_date']) ?></td>
                                <td>
                                    <?php if ($ms['status'] === 'active'): ?>
                                        <?php if ($daysDiff > 7): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success"><?= $daysDiff ?> days left</span>
                                        <?php elseif ($daysDiff >= 0): ?>
                                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning">Expiring soon (<?= $daysDiff ?>d)</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Expired</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">--</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= get_status_badge($ms['status']) ?></td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>/admin/member_view.php?id=<?= $ms['member_id'] ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="View Member Profile">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No subscription records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
