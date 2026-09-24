<?php
/**
 * Payments & Invoicing Management
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();

$search = trim($_GET['search'] ?? '');
$methodFilter = trim($_GET['method'] ?? 'all');
$statusFilter = trim($_GET['status'] ?? 'all');

$sql = "
    SELECT p.*, u.name AS member_name, u.email AS member_email, u.phone AS member_phone,
           mp.plan_name
    FROM payments p
    JOIN members m ON p.member_id = m.id
    JOIN users u ON m.user_id = u.id
    LEFT JOIN memberships ms ON p.membership_id = ms.id
    LEFT JOIN membership_plans mp ON ms.plan_id = mp.id
    WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR p.transaction_id LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($methodFilter !== 'all' && in_array($methodFilter, ['Cash', 'Card', 'UPI', 'Net Banking'])) {
    $sql .= " AND p.payment_method = ?";
    $params[] = $methodFilter;
}

if ($statusFilter !== 'all' && in_array($statusFilter, ['Paid', 'Pending', 'Failed'])) {
    $sql .= " AND p.payment_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY p.payment_date DESC, p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Total revenue calculation from matched results
$totalRevenue = 0;
foreach ($payments as $pay) {
    if ($pay['payment_status'] === 'Paid') {
        $totalRevenue += (float)$pay['amount'];
    }
}

$pageTitle = 'Payments & Billing';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Fee Payments & Invoicing</h3>
        <p class="text-muted small mb-0">Record membership fee transactions, generate receipts, and track gym revenue.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/payment_add.php" class="btn btn-primary px-3">
            <i class="fa-solid fa-plus me-1"></i> Record New Payment
        </a>
    </div>
</div>

<!-- Quick Total Collected Badge -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <form method="GET" action="payments.php" class="row g-2 align-items-center flex-grow-1 w-100">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by member or transaction ID..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="method" class="form-select">
                    <option value="all" <?= $methodFilter === 'all' ? 'selected' : '' ?>>All Payment Methods</option>
                    <option value="Cash" <?= $methodFilter === 'Cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="Card" <?= $methodFilter === 'Card' ? 'selected' : '' ?>>Credit / Debit Card</option>
                    <option value="UPI" <?= $methodFilter === 'UPI' ? 'selected' : '' ?>>UPI</option>
                    <option value="Net Banking" <?= $methodFilter === 'Net Banking' ? 'selected' : '' ?>>Net Banking</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="Paid" <?= $statusFilter === 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Failed" <?= $statusFilter === 'Failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <?php if (!empty($search) || $methodFilter !== 'all' || $statusFilter !== 'all'): ?>
                    <a href="payments.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="text-md-end border-start ps-md-4 flex-shrink-0">
            <span class="small text-muted d-block">Filtered Revenue:</span>
            <span class="fs-4 fw-bold text-success"><?= format_currency($totalRevenue) ?></span>
        </div>
    </div>
</div>

<!-- Payments Ledger Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Receipt #</th>
                        <th>Member</th>
                        <th>Plan / Purpose</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Transaction ID</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="fw-bold text-muted">#RCP-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/admin/member_view.php?id=<?= $p['member_id'] ?>" class="text-decoration-none fw-bold text-dark">
                                        <?= htmlspecialchars($p['member_name']) ?>
                                    </a>
                                    <div class="small text-muted"><?= htmlspecialchars($p['member_email']) ?></div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-secondary"><?= htmlspecialchars($p['plan_name'] ?? 'Gym Fee') ?></span>
                                </td>
                                <td class="fw-bold text-success fs-6">
                                    <?= format_currency($p['amount']) ?>
                                </td>
                                <td><?= format_date($p['payment_date']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid <?= $p['payment_method'] === 'Cash' ? 'fa-money-bill-1' : ($p['payment_method'] === 'Card' ? 'fa-credit-card' : 'fa-mobile-screen-button') ?> me-1 text-primary"></i>
                                        <?= htmlspecialchars($p['payment_method']) ?>
                                    </span>
                                </td>
                                <td><?= get_status_badge($p['payment_status']) ?></td>
                                <td class="small font-monospace text-muted"><?= htmlspecialchars($p['transaction_id'] ?? 'N/A') ?></td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>/admin/payment_receipt.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-3" title="View & Print Official Receipt">
                                        <i class="fa-solid fa-receipt me-1"></i> Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-file-invoice-dollar display-6 d-block mb-2 opacity-50"></i>
                                No payment records found matching your filters.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
