<?php
/**
 * Member - Payment History & Invoices
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('member');

$pdo = get_db_connection();
$user = current_user();

$mStmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
$mStmt->execute([$user['id']]);
$memberId = (int)$mStmt->fetchColumn();

// Fetch payments
$stmt = $pdo->prepare("
    SELECT p.*, mp.plan_name 
    FROM payments p 
    LEFT JOIN memberships ms ON p.membership_id = ms.id 
    LEFT JOIN membership_plans mp ON ms.plan_id = mp.id 
    WHERE p.member_id = ? 
    ORDER BY p.payment_date DESC, p.id DESC
");
$stmt->execute([$memberId]);
$payments = $stmt->fetchAll();

// Total Paid
$totalAmount = 0;
foreach ($payments as $p) {
    if ($p['payment_status'] === 'Paid') {
        $totalAmount += (float)$p['amount'];
    }
}

$pageTitle = 'My Payment History';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Billing & Payment History</h3>
        <p class="text-muted small mb-0">Inspect your transaction history and download official fee receipts.</p>
    </div>
    <div class="text-md-end">
        <span class="small text-muted d-block">Total Fees Paid:</span>
        <span class="fs-4 fw-bold text-success"><?= format_currency($totalAmount) ?></span>
    </div>
</div>

<!-- Payments Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-receipt me-2 text-primary"></i>Payment Invoices</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Receipt #</th>
                        <th>Subscription / Purpose</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Reference ID</th>
                        <th class="text-end">Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="fw-bold text-muted">#RCP-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['plan_name'] ?? 'Gym Fee') ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($p['notes'] ?? 'Gym membership payment') ?></div>
                                </td>
                                <td class="fw-bold text-success fs-6"><?= format_currency($p['amount']) ?></td>
                                <td><?= format_date($p['payment_date']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($p['payment_method']) ?>
                                    </span>
                                </td>
                                <td><?= get_status_badge($p['payment_status']) ?></td>
                                <td class="small font-monospace text-muted"><?= htmlspecialchars($p['transaction_id'] ?? 'OFFLINE') ?></td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>/admin/payment_receipt.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-3">
                                        <i class="fa-solid fa-file-invoice me-1"></i> View Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No payment records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
