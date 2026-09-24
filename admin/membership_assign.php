<?php
/**
 * Assign Membership Plan to Member
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

// Fetch members
$membersStmt = $pdo->query("SELECT m.id, u.name, u.email FROM members m JOIN users u ON m.user_id = u.id WHERE m.status = 'active' ORDER BY u.name ASC");
$members = $membersStmt->fetchAll();

// Fetch active plans
$plansStmt = $pdo->query("SELECT * FROM membership_plans WHERE status = 'active' ORDER BY price ASC");
$plans = $plansStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $plan_id = (int)($_POST['plan_id'] ?? 0);
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
    $status = $_POST['status'] ?? 'active';
    $record_payment = isset($_POST['record_payment']);
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $transaction_id = trim($_POST['transaction_id'] ?? '');

    if ($member_id <= 0 || $plan_id <= 0) {
        $error = 'Please select both a member and a membership plan.';
    } else {
        // Fetch plan details to calculate accurate end date
        $pStmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id = ?");
        $pStmt->execute([$plan_id]);
        $plan = $pStmt->fetch();

        if (!$plan) {
            $error = 'Selected plan does not exist.';
        } else {
            // Calculate end date based on plan duration in days
            $durationDays = (int)$plan['duration_days'];
            $calculatedEndDate = date('Y-m-d', strtotime($start_date . " + {$durationDays} days"));
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : $calculatedEndDate;

            try {
                $pdo->beginTransaction();

                // 1. If new status is active, deactivate prior active memberships for this member
                if ($status === 'active') {
                    $deact = $pdo->prepare("UPDATE memberships SET status = 'expired' WHERE member_id = ? AND status = 'active'");
                    $deact->execute([$member_id]);
                }

                // 2. Insert new membership
                $subStmt = $pdo->prepare("
                    INSERT INTO memberships (member_id, plan_id, start_date, end_date, status, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $subStmt->execute([$member_id, $plan_id, $start_date, $endDate, $status]);
                $newMembershipId = $pdo->lastInsertId();

                // 3. Optionally record payment immediately
                if ($record_payment) {
                    $payStmt = $pdo->prepare("
                        INSERT INTO payments (member_id, membership_id, amount, payment_date, payment_method, payment_status, transaction_id, notes, created_at)
                        VALUES (?, ?, ?, ?, ?, 'Paid', ?, ?, NOW())
                    ");
                    $payNotes = "Payment for " . $plan['plan_name'] . " (" . $durationDays . " days)";
                    $payStmt->execute([$member_id, $newMembershipId, $plan['price'], $start_date, $payment_method, $transaction_id, $payNotes]);
                }

                $pdo->commit();

                set_flash('success', "Membership assigned successfully!" . ($record_payment ? " Payment of " . format_currency($plan['price']) . " was also recorded." : ""));
                header("Location: " . BASE_URL . "/admin/member_view.php?id=" . $member_id);
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to assign membership: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Assign Membership Plan';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/admin/memberships.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Subscriptions
    </a>
    <h3 class="fw-bold mb-1">Assign Membership Subscription</h3>
    <p class="text-muted small">Select a member, choose an active plan, and confirm validity dates.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="membership_assign.php">
                    <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">1. Select Member & Plan</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Gym Member <span class="text-danger">*</span></label>
                            <select name="member_id" class="form-select" required>
                                <option value="">-- Choose Member --</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= ($preselectedMemberId === (int)$m['id'] || (($_POST['member_id'] ?? '') == $m['id'])) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Membership Plan <span class="text-danger">*</span></label>
                            <select name="plan_id" id="plan_id_select" class="form-select" required>
                                <option value="">-- Select Plan --</option>
                                <?php foreach ($plans as $p): ?>
                                    <option value="<?= $p['id'] ?>" 
                                            data-duration="<?= (int)$p['duration_days'] ?>" 
                                            data-price="<?= format_currency($p['price']) ?>"
                                            <?= (($_POST['plan_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['plan_name']) ?> (<?= (int)$p['duration_days'] ?> Days - <?= format_currency($p['price']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">2. Validity Period (Auto-Calculated)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date_input" class="form-control" required value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">End Date <span class="text-muted fw-normal">(Auto calculated)</span></label>
                            <input type="date" name="end_date" id="end_date_input" class="form-control bg-light" required value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Initial Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= (($_POST['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="pending" <?= (($_POST['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">3. Billing & Payment Option</h6>
                    <div class="card bg-light border-0 p-3 mb-4 rounded-3">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="record_payment" id="recordPaymentCheck" checked onchange="document.getElementById('paymentFields').classList.toggle('d-none', !this.checked)">
                            <label class="form-check-label fw-semibold" for="recordPaymentCheck">
                                Record payment for this membership immediately
                            </label>
                        </div>

                        <div id="paymentFields" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="Cash">Cash (Front Desk)</option>
                                    <option value="Card">Credit / Debit Card</option>
                                    <option value="UPI">UPI / Google Pay / PhonePe</option>
                                    <option value="Net Banking">Net Banking / NEFT</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Transaction Reference / Receipt ID <span class="text-muted fw-normal">(Optional)</span></label>
                                <input type="text" name="transaction_id" class="form-control" placeholder="e.g. UPI/2026/01293 or CASH-001">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/admin/memberships.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="fa-solid fa-check me-1"></i> Assign Subscription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Trigger calculation on initial page load if plan already selected
document.addEventListener('DOMContentLoaded', function () {
    const planSel = document.getElementById('plan_id_select');
    if (planSel && planSel.value) {
        planSel.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
