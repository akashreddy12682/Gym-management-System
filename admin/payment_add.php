<?php
/**
 * Record New Payment
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$error = '';
$preselectedMemberId = (int)($_GET['member_id'] ?? 0);

// Fetch active members
$membersStmt = $pdo->query("SELECT m.id, u.name, u.email FROM members m JOIN users u ON m.user_id = u.id WHERE m.status = 'active' ORDER BY u.name ASC");
$members = $membersStmt->fetchAll();

// Fetch active or pending memberships
$membershipsStmt = $pdo->query("
    SELECT ms.id, ms.member_id, mp.plan_name, mp.price, u.name AS member_name
    FROM memberships ms 
    JOIN membership_plans mp ON ms.plan_id = mp.id 
    JOIN members m ON ms.member_id = m.id 
    JOIN users u ON m.user_id = u.id 
    ORDER BY ms.id DESC
");
$memberships = $membershipsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $membership_id = !empty($_POST['membership_id']) ? (int)$_POST['membership_id'] : null;
    $amount = (float)($_POST['amount'] ?? 0);
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $payment_status = $_POST['payment_status'] ?? 'Paid';
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($member_id <= 0 || $amount <= 0) {
        $error = 'Please select a member and enter a valid payment amount.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payments (member_id, membership_id, amount, payment_date, payment_method, payment_status, transaction_id, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$member_id, $membership_id, $amount, $payment_date, $payment_method, $payment_status, $transaction_id, $notes]);
            $paymentId = $pdo->lastInsertId();

            set_flash('success', "Payment of " . format_currency($amount) . " successfully recorded! (Receipt #RCP-" . str_pad($paymentId, 5, '0', STR_PAD_LEFT) . ")");
            header("Location: " . BASE_URL . "/admin/payment_receipt.php?id=" . $paymentId);
            exit();
        } catch (Exception $e) {
            $error = 'Failed to record payment: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Record Payment';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/admin/payments.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Payments
    </a>
    <h3 class="fw-bold mb-1">Record Fee Payment</h3>
    <p class="text-muted small">Log a payment received for gym membership or personal training.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="payment_add.php">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Member <span class="text-danger">*</span></label>
                            <select name="member_id" id="member_select" class="form-select" required>
                                <option value="">-- Choose Member --</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= ($preselectedMemberId === (int)$m['id'] || (($_POST['member_id'] ?? '') == $m['id'])) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Linked Subscription <span class="text-muted fw-normal">(Optional)</span></label>
                            <select name="membership_id" id="membership_select" class="form-select">
                                <option value="">-- Standalone / General Gym Fee --</option>
                                <?php foreach ($memberships as $ms): ?>
                                    <option value="<?= $ms['id'] ?>" data-member="<?= $ms['member_id'] ?>" data-price="<?= $ms['price'] ?>">
                                        <?= htmlspecialchars($ms['member_name']) ?> - <?= htmlspecialchars($ms['plan_name']) ?> (<?= format_currency($ms['price']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Amount Paid (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><?= CURRENCY_SYMBOL ?></span>
                                <input type="number" step="0.01" name="amount" id="amount_input" class="form-control" required min="0.01" placeholder="e.g. 40.00" value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" required value="<?= htmlspecialchars($_POST['payment_date'] ?? date('Y-m-d')) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash" <?= (($_POST['payment_method'] ?? '') === 'Cash') ? 'selected' : '' ?>>Cash (Front Desk)</option>
                                <option value="Card" <?= (($_POST['payment_method'] ?? '') === 'Card') ? 'selected' : '' ?>>Credit / Debit Card</option>
                                <option value="UPI" <?= (($_POST['payment_method'] ?? '') === 'UPI') ? 'selected' : '' ?>>UPI / QR / Online App</option>
                                <option value="Net Banking" <?= (($_POST['payment_method'] ?? '') === 'Net Banking') ? 'selected' : '' ?>>Net Banking / Bank Transfer</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="Paid" selected>Paid</option>
                                <option value="Pending">Pending</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Transaction Reference / Cheque / UPI ID</label>
                            <input type="text" name="transaction_id" class="form-control" placeholder="e.g. TXN-8921820 or UPI/2603/4920" value="<?= htmlspecialchars($_POST['transaction_id'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Notes / Remarks</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Additional notes, discounts applied, or invoice remarks..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/admin/payments.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="fa-solid fa-receipt me-1"></i> Record & Generate Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-fill amount when selecting linked membership
document.getElementById('membership_select').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    const memberId = selectedOption.getAttribute('data-member');
    if (price) {
        document.getElementById('amount_input').value = price;
    }
    if (memberId) {
        document.getElementById('member_select').value = memberId;
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
