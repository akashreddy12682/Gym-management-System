<?php
/**
 * Member - Membership Details & History
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('member');

$pdo = get_db_connection();
sync_membership_statuses($pdo);
$user = current_user();

$mStmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
$mStmt->execute([$user['id']]);
$memberId = (int)$mStmt->fetchColumn();

// Fetch all memberships
$stmt = $pdo->prepare("
    SELECT ms.*, mp.plan_name, mp.duration_days, mp.price, mp.description 
    FROM memberships ms 
    JOIN membership_plans mp ON ms.plan_id = mp.id 
    WHERE ms.member_id = ? 
    ORDER BY ms.id DESC
");
$stmt->execute([$memberId]);
$memberships = $stmt->fetchAll();

// Find active membership
$activeSub = null;
foreach ($memberships as $sub) {
    if ($sub['status'] === 'active') {
        $activeSub = $sub;
        break;
    }
}

$pageTitle = 'My Membership';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold mb-1">My Membership Plan</h3>
    <p class="text-muted small">View your active subscription package, expiration date, and past renewal history.</p>
</div>

<!-- Active Membership Banner -->
<?php if ($activeSub): ?>
    <?php
    $daysLeft = (int)ceil((strtotime($activeSub['end_date']) - time()) / 86400);
    $totalDays = (int)$activeSub['duration_days'];
    $daysPassed = max(0, $totalDays - $daysLeft);
    $progressPct = ($totalDays > 0) ? min(100, round(($daysPassed / $totalDays) * 100)) : 100;
    ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden border-start border-primary border-5">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-1 mb-2">ACTIVE SUBSCRIPTION</span>
                    <h2 class="fw-extrabold text-dark mb-1"><?= htmlspecialchars($activeSub['plan_name']) ?></h2>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($activeSub['description'] ?? 'Unlimited access to gym facilities.') ?></p>
                </div>
                <div class="text-md-end">
                    <div class="display-6 fw-bold text-primary mb-1"><?= format_currency($activeSub['price']) ?></div>
                    <div class="badge bg-light text-dark border"><?= $totalDays ?> Days Validity</div>
                </div>
            </div>

            <div class="row g-3 py-3 border-top border-bottom mb-4">
                <div class="col-sm-4">
                    <span class="text-muted small d-block">Start Date</span>
                    <strong class="text-dark fs-6"><?= format_date($activeSub['start_date']) ?></strong>
                </div>
                <div class="col-sm-4">
                    <span class="text-muted small d-block">Expiry Date</span>
                    <strong class="text-dark fs-6"><?= format_date($activeSub['end_date']) ?></strong>
                </div>
                <div class="col-sm-4">
                    <span class="text-muted small d-block">Time Remaining</span>
                    <strong class="text-success fs-6"><?= max(0, $daysLeft) ?> days left</strong>
                </div>
            </div>

            <div>
                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>Plan Duration Progress</span>
                    <span><?= $daysPassed ?> / <?= $totalDays ?> days used</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $progressPct ?>%;"></div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4 text-center p-5 bg-white">
        <div class="display-4 text-danger mb-2">⚠️</div>
        <h4 class="fw-bold">No Active Membership</h4>
        <p class="text-muted small mb-3">Your membership plan is currently expired or inactive. Please contact the front desk to renew your membership.</p>
    </div>
<?php endif; ?>

<!-- Membership History Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Subscription History</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Subscription ID</th>
                        <th>Plan Name</th>
                        <th>Duration</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($memberships)): ?>
                        <?php foreach ($memberships as $ms): ?>
                            <tr>
                                <td class="fw-bold text-muted">#SUB-<?= str_pad($ms['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($ms['plan_name']) ?></td>
                                <td><?= (int)$ms['duration_days'] ?> Days</td>
                                <td><?= format_date($ms['start_date']) ?></td>
                                <td><?= format_date($ms['end_date']) ?></td>
                                <td class="fw-semibold"><?= format_currency($ms['price']) ?></td>
                                <td><?= get_status_badge($ms['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No past subscriptions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
