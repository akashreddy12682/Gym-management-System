<?php
/**
 * Member Dashboard
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

// Fetch Member Details
$mStmt = $pdo->prepare("
    SELECT m.*, u.name, u.email, u.phone,
           tu.name AS trainer_name, tu.email AS trainer_email, tu.phone AS trainer_phone,
           t.specialization AS trainer_spec, t.bio AS trainer_bio
    FROM members m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN trainers t ON m.trainer_id = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    WHERE m.user_id = ?
");
$mStmt->execute([$user['id']]);
$member = $mStmt->fetch();

if (!$member) {
    die("Member record not associated with this user.");
}

$memberId = (int)$member['id'];

// 1. Fetch Current Active Membership
$subStmt = $pdo->prepare("
    SELECT ms.*, mp.plan_name, mp.duration_days, mp.price, mp.description 
    FROM memberships ms 
    JOIN membership_plans mp ON ms.plan_id = mp.id 
    WHERE ms.member_id = ? AND ms.status = 'active' 
    ORDER BY ms.id DESC LIMIT 1
");
$subStmt->execute([$memberId]);
$activeSub = $subStmt->fetch();

// 2. Today's Workout Routine (Day of week)
$todayDay = date('l');
$wStmt = $pdo->prepare("
    SELECT * FROM workouts 
    WHERE member_id = ? AND (day_of_week = ? OR day_of_week = 'All Days')
    LIMIT 1
");
$wStmt->execute([$memberId, $todayDay]);
$todayWorkout = $wStmt->fetch();

// 3. Stats: Attendance count this month
$attCountStmt = $pdo->prepare("
    SELECT COUNT(*) FROM attendance 
    WHERE member_id = ? AND MONTH(attendance_date) = MONTH(CURDATE()) AND YEAR(attendance_date) = YEAR(CURDATE()) AND status = 'Present'
");
$attCountStmt->execute([$memberId]);
$monthVisits = (int)$attCountStmt->fetchColumn();

// 4. Stats: Total Fees Paid
$payStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE member_id = ? AND payment_status = 'Paid'");
$payStmt->execute([$memberId]);
$totalPaid = (float)$payStmt->fetchColumn();

$pageTitle = 'Member Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-dark text-white p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="badge bg-warning text-dark text-uppercase fw-bold px-3 py-1 mb-2">Member Portal</span>
            <h3 class="fw-bold mb-1">Welcome back, <?= htmlspecialchars($member['name']) ?>!</h3>
            <p class="text-light opacity-75 small mb-0">Stay consistent, push your limits, and achieve your fitness milestones.</p>
        </div>
        <div>
            <?= get_status_badge($member['status']) ?>
        </div>
    </div>
</div>

<!-- Quick Stats Grid -->
<div class="row g-3 mb-4">
    <!-- Active Plan -->
    <div class="col-md-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Membership Plan</div>
                <h4 class="fw-bold mb-0 mt-1 text-primary">
                    <?= $activeSub ? htmlspecialchars($activeSub['plan_name']) : 'No Active Plan' ?>
                </h4>
                <?php if ($activeSub): ?>
                    <?php $daysLeft = (int)ceil((strtotime($activeSub['end_date']) - time()) / 86400); ?>
                    <small class="text-success fw-medium"><i class="fa-regular fa-clock me-1"></i><?= max(0, $daysLeft) ?> days remaining</small>
                <?php else: ?>
                    <small class="text-danger fw-medium">Subscription Expired</small>
                <?php endif; ?>
            </div>
            <div class="stat-icon-wrapper stat-icon-primary">
                <i class="fa-solid fa-id-card"></i>
            </div>
        </div>
    </div>

    <!-- Gym Visits This Month -->
    <div class="col-md-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Visits This Month</div>
                <h4 class="fw-bold mb-0 mt-1"><?= $monthVisits ?> Days</h4>
                <small class="text-muted">Attendance recorded in <?= date('F') ?></small>
            </div>
            <div class="stat-icon-wrapper stat-icon-success">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <!-- Total Invoices Paid -->
    <div class="col-md-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Total Fees Paid</div>
                <h4 class="fw-bold mb-0 mt-1"><?= format_currency($totalPaid) ?></h4>
                <small class="text-muted">Verified payment history</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-warning">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Section: Today's Workout & Trainer -->
<div class="row g-4">
    <!-- Today's Workout Card -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-dumbbell me-2 text-warning"></i>Today's Workout: <?= $todayDay ?>
                </h6>
                <a href="<?= BASE_URL ?>/member/workouts.php" class="btn btn-sm btn-light border text-secondary">Full Week Routine</a>
            </div>
            <div class="card-body p-4">
                <?php if ($todayWorkout): ?>
                    <h5 class="fw-bold text-primary mb-2"><?= htmlspecialchars($todayWorkout['title']) ?></h5>
                    <div class="mb-3">
                        <div class="small fw-semibold text-secondary mb-1">Target Exercises:</div>
                        <pre class="bg-light p-3 border rounded font-monospace small mb-0" style="white-space: pre-wrap; font-size: 0.85rem;"><?= htmlspecialchars($todayWorkout['exercise_details']) ?></pre>
                    </div>

                    <?php if (!empty($todayWorkout['diet_notes'])): ?>
                        <div class="pt-3 border-top">
                            <div class="small fw-semibold text-success mb-1"><i class="fa-solid fa-apple-whole me-1"></i>Dietary Note for Today:</div>
                            <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($todayWorkout['diet_notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-mug-hot display-6 d-block mb-2 opacity-50"></i>
                        <h5>Rest or Active Recovery Day</h5>
                        <p class="small mb-0">No specific workout routine scheduled for today (<?= $todayDay ?>). Hydrate and stretch well!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Assigned Personal Trainer Card -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-ninja me-2 text-primary"></i>My Personal Coach</h6>
            </div>
            <div class="card-body p-4">
                <?php if ($member['trainer_name']): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 58px; height: 58px;">
                            <i class="fa-solid fa-user-ninja"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($member['trainer_name']) ?></h5>
                            <span class="badge bg-warning bg-opacity-25 text-dark small fw-semibold">
                                <?= htmlspecialchars($member['trainer_spec']) ?>
                            </span>
                        </div>
                    </div>

                    <p class="text-muted small mb-3">
                        <?= htmlspecialchars($member['trainer_bio'] ?? 'Certified trainer dedicated to your athletic progression and posture.') ?>
                    </p>

                    <div class="border-top pt-3 small">
                        <div class="mb-1"><i class="fa-regular fa-envelope me-2 text-muted"></i><?= htmlspecialchars($member['trainer_email']) ?></div>
                        <div><i class="fa-solid fa-phone me-2 text-muted"></i><?= htmlspecialchars($member['trainer_phone'] ?? 'N/A') ?></div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-user-slash display-6 d-block mb-2 opacity-50"></i>
                        <h6>No Personal Coach Assigned</h6>
                        <p class="small mb-0">Speak with gym administration to assign a personal fitness trainer to guide your journey.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
