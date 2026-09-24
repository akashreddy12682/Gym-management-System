<?php
/**
 * Member Detailed Profile & History View
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
sync_membership_statuses($pdo);

$memberId = (int)($_GET['id'] ?? 0);

// Fetch Member Details
$stmt = $pdo->prepare("
    SELECT m.*, u.name, u.email, u.phone, u.created_at AS user_created,
           tu.name AS trainer_name, tu.email AS trainer_email, tu.phone AS trainer_phone,
           t.specialization AS trainer_spec
    FROM members m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN trainers t ON m.trainer_id = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    WHERE m.id = ?
");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    set_flash('danger', 'Member not found.');
    header("Location: " . BASE_URL . "/admin/members.php");
    exit();
}

// 1. Memberships
$membershipsStmt = $pdo->prepare("
    SELECT ms.*, mp.plan_name, mp.duration_days, mp.price 
    FROM memberships ms 
    JOIN membership_plans mp ON ms.plan_id = mp.id 
    WHERE ms.member_id = ? 
    ORDER BY ms.id DESC
");
$membershipsStmt->execute([$memberId]);
$memberships = $membershipsStmt->fetchAll();

// 2. Attendance History
$attendanceStmt = $pdo->prepare("
    SELECT * FROM attendance 
    WHERE member_id = ? 
    ORDER BY attendance_date DESC, check_in_time DESC 
    LIMIT 30
");
$attendanceStmt->execute([$memberId]);
$attendanceLogs = $attendanceStmt->fetchAll();

// 3. Payment History
$paymentsStmt = $pdo->prepare("
    SELECT p.*, mp.plan_name 
    FROM payments p 
    LEFT JOIN memberships ms ON p.membership_id = ms.id 
    LEFT JOIN membership_plans mp ON ms.plan_id = mp.id 
    WHERE p.member_id = ? 
    ORDER BY p.id DESC
");
$paymentsStmt->execute([$memberId]);
$payments = $paymentsStmt->fetchAll();

// 4. Assigned Workouts
$workoutsStmt = $pdo->prepare("
    SELECT w.*, tu.name AS trainer_name 
    FROM workouts w 
    LEFT JOIN trainers t ON w.trainer_id = t.id 
    LEFT JOIN users tu ON t.user_id = tu.id 
    WHERE w.member_id = ? 
    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'All Days')
");
$workoutsStmt->execute([$memberId]);
$workouts = $workoutsStmt->fetchAll();

$pageTitle = 'Member Profile - ' . $member['name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Members
    </a>
</div>

<!-- Header Card: Member Overview -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-dark text-warning rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 70px; height: 70px;">
                    <?= strtoupper(substr($member['name'], 0, 1)) ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($member['name']) ?></h4>
                        <span class="badge bg-light text-muted border">#M<?= str_pad($member['id'], 4, '0', STR_PAD_LEFT) ?></span>
                        <?= get_status_badge($member['status']) ?>
                    </div>
                    <div class="text-muted small mt-1 d-flex flex-wrap gap-3">
                        <span><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($member['email']) ?></span>
                        <span><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($member['phone'] ?? 'N/A') ?></span>
                        <span><i class="fa-solid fa-calendar me-1"></i>Joined <?= format_date($member['join_date']) ?></span>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= BASE_URL ?>/admin/member_edit.php?id=<?= $member['id'] ?>" class="btn btn-outline-dark btn-sm px-3">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit Profile
                </a>
                <a href="<?= BASE_URL ?>/admin/membership_assign.php?member_id=<?= $member['id'] ?>" class="btn btn-primary btn-sm px-3">
                    <i class="fa-solid fa-id-card me-1"></i> Assign Subscription
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Info Cards Grid -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
            <h6 class="fw-bold text-secondary text-uppercase small mb-3">Personal Information</h6>
            <ul class="list-unstyled small mb-0">
                <li class="py-1"><strong>Gender:</strong> <?= htmlspecialchars($member['gender']) ?></li>
                <li class="py-1"><strong>Date of Birth:</strong> <?= format_date($member['dob']) ?></li>
                <li class="py-1"><strong>Address:</strong> <?= htmlspecialchars($member['address'] ?? 'Not provided') ?></li>
                <li class="py-1"><strong>Emergency Contact:</strong> <?= htmlspecialchars($member['emergency_contact'] ?? 'None recorded') ?></li>
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
            <h6 class="fw-bold text-secondary text-uppercase small mb-3">Assigned Personal Trainer</h6>
            <?php if ($member['trainer_name']): ?>
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-25 text-dark rounded-circle p-3">
                        <i class="fa-solid fa-user-ninja fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($member['trainer_name']) ?></div>
                        <span class="badge bg-light text-secondary border"><?= htmlspecialchars($member['trainer_spec']) ?></span>
                        <div class="text-muted small mt-1"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($member['trainer_phone'] ?? 'N/A') ?></div>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-3">No personal coach assigned to this member currently.</p>
                <a href="<?= BASE_URL ?>/admin/member_edit.php?id=<?= $member['id'] ?>" class="btn btn-sm btn-outline-secondary w-100">
                    Assign a Trainer
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
            <h6 class="fw-bold text-secondary text-uppercase small mb-3">Active Subscription Status</h6>
            <?php 
            $activeSub = null;
            foreach ($memberships as $sub) {
                if ($sub['status'] === 'active') {
                    $activeSub = $sub;
                    break;
                }
            }
            ?>
            <?php if ($activeSub): ?>
                <div class="fw-bold text-primary fs-5"><?= htmlspecialchars($activeSub['plan_name']) ?></div>
                <div class="small text-muted mb-2">
                    <?= format_date($activeSub['start_date']) ?> to <?= format_date($activeSub['end_date']) ?>
                </div>
                <?php
                $remainingDays = (int)ceil((strtotime($activeSub['end_date']) - time()) / 86400);
                ?>
                <div class="badge bg-success bg-opacity-10 text-success border border-success p-2">
                    <i class="fa-regular fa-clock me-1"></i> <?= max(0, $remainingDays) ?> day(s) remaining
                </div>
            <?php else: ?>
                <div class="text-danger fw-semibold small mb-2"><i class="fa-solid fa-triangle-exclamation me-1"></i>No Active Plan</div>
                <p class="text-muted small">Subscription is either expired or not yet assigned.</p>
                <a href="<?= BASE_URL ?>/admin/membership_assign.php?member_id=<?= $member['id'] ?>" class="btn btn-sm btn-primary w-100">
                    Subscribe Now
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabs Section for Detailed Records -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom pt-3 pb-0">
        <ul class="nav nav-tabs border-bottom-0" id="memberTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-semibold" id="subs-tab" data-bs-toggle="tab" data-bs-target="#subs-pane" type="button">
                    <i class="fa-solid fa-id-card me-1"></i> Subscriptions (<?= count($memberships) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance-pane" type="button">
                    <i class="fa-solid fa-calendar-check me-1"></i> Attendance Log (<?= count($attendanceLogs) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-pane" type="button">
                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> Payments (<?= count($payments) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" id="workout-tab" data-bs-toggle="tab" data-bs-target="#workout-pane" type="button">
                    <i class="fa-solid fa-dumbbell me-1"></i> Workout & Diet (<?= count($workouts) ?>)
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <div class="tab-content" id="memberTabContent">
            <!-- 1. Subscriptions Tab -->
            <div class="tab-pane fade show active" id="subs-pane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th>Plan Name</th>
                                <th>Duration</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($memberships)): ?>
                                <?php foreach ($memberships as $ms): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($ms['plan_name']) ?></td>
                                        <td><?= (int)$ms['duration_days'] ?> Days</td>
                                        <td><?= format_date($ms['start_date']) ?></td>
                                        <td><?= format_date($ms['end_date']) ?></td>
                                        <td class="fw-semibold"><?= format_currency($ms['price']) ?></td>
                                        <td><?= get_status_badge($ms['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No subscriptions found for this member.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Attendance Log Tab -->
            <div class="tab-pane fade" id="attendance-pane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th>Date</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($attendanceLogs)): ?>
                                <?php foreach ($attendanceLogs as $att): ?>
                                    <tr>
                                        <td class="fw-bold"><?= format_date($att['attendance_date']) ?></td>
                                        <td><i class="fa-regular fa-clock me-1 text-success"></i><?= format_time($att['check_in_time']) ?></td>
                                        <td><?= $att['check_out_time'] ? '<i class="fa-regular fa-clock me-1 text-danger"></i>' . format_time($att['check_out_time']) : '<span class="text-muted">--</span>' ?></td>
                                        <td><?= get_status_badge($att['status']) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($att['notes'] ?? 'General session') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No attendance activity recorded.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Payments Tab -->
            <div class="tab-pane fade" id="payments-pane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                                <th class="text-end">Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($payments)): ?>
                                <?php foreach ($payments as $pay): ?>
                                    <tr>
                                        <td class="fw-bold">#RCP-<?= str_pad($pay['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= format_date($pay['payment_date']) ?></td>
                                        <td class="fw-bold text-success"><?= format_currency($pay['amount']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($pay['payment_method']) ?></span></td>
                                        <td class="font-monospace small text-muted"><?= htmlspecialchars($pay['transaction_id'] ?? 'N/A') ?></td>
                                        <td><?= get_status_badge($pay['payment_status']) ?></td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>/admin/payment_receipt.php?id=<?= $pay['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" title="Print Invoice">
                                                <i class="fa-solid fa-receipt me-1"></i> Print
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No payments recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Workout & Diet Tab -->
            <div class="tab-pane fade" id="workout-pane" role="tabpanel">
                <?php if (!empty($workouts)): ?>
                    <div class="row g-3">
                        <?php foreach ($workouts as $w): ?>
                            <div class="col-md-6">
                                <div class="card border rounded-3 p-3 h-100 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-dark"><?= htmlspecialchars($w['day_of_week']) ?></span>
                                        <small class="text-muted">Coach: <?= htmlspecialchars($w['trainer_name'] ?? 'General') ?></small>
                                    </div>
                                    <h6 class="fw-bold text-primary mb-2"><?= htmlspecialchars($w['title']) ?></h6>
                                    <div class="small mb-3">
                                        <div class="fw-semibold text-secondary">Exercises:</div>
                                        <pre class="bg-white p-2 border rounded font-monospace small mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($w['exercise_details']) ?></pre>
                                    </div>
                                    <?php if (!empty($w['diet_notes'])): ?>
                                        <div class="small">
                                            <div class="fw-semibold text-success"><i class="fa-solid fa-apple-whole me-1"></i>Diet & Nutrition:</div>
                                            <p class="text-muted mb-0 small"><?= nl2br(htmlspecialchars($w['diet_notes'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-dumbbell display-6 d-block mb-2 opacity-50"></i>
                        No workout routines assigned to this member yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
