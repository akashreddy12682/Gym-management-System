<?php
/**
 * Trainer - View Member Details & Workout History
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('trainer');

$pdo = get_db_connection();
$user = current_user();

$trStmt = $pdo->prepare("SELECT id FROM trainers WHERE user_id = ?");
$trStmt->execute([$user['id']]);
$trainerId = (int)$trStmt->fetchColumn();

$memberId = (int)($_GET['id'] ?? 0);

// Fetch member
$stmt = $pdo->prepare("
    SELECT m.*, u.name, u.email, u.phone
    FROM members m
    JOIN users u ON m.user_id = u.id
    WHERE m.id = ? AND m.trainer_id = ?
");
$stmt->execute([$memberId, $trainerId]);
$member = $stmt->fetch();

if (!$member) {
    set_flash('danger', 'Member not found or not assigned to your coaching profile.');
    header("Location: " . BASE_URL . "/trainer/members.php");
    exit();
}

// Fetch active membership
$subStmt = $pdo->prepare("
    SELECT ms.*, mp.plan_name 
    FROM memberships ms 
    JOIN membership_plans mp ON ms.plan_id = mp.id 
    WHERE ms.member_id = ? AND ms.status = 'active' 
    ORDER BY ms.id DESC LIMIT 1
");
$subStmt->execute([$memberId]);
$activeSub = $subStmt->fetch();

// Fetch workouts prescribed for this member
$wStmt = $pdo->prepare("
    SELECT * FROM workouts 
    WHERE member_id = ? 
    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'All Days')
");
$wStmt->execute([$memberId]);
$workouts = $wStmt->fetchAll();

// Fetch recent attendance logs
$attStmt = $pdo->prepare("
    SELECT * FROM attendance 
    WHERE member_id = ? 
    ORDER BY attendance_date DESC 
    LIMIT 10
");
$attStmt->execute([$memberId]);
$recentAttendance = $attStmt->fetchAll();

$pageTitle = 'Trainee Profile - ' . $member['name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/trainer/members.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to My Trainees
    </a>
</div>

<!-- Header Overview -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
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

            <div>
                <a href="<?= BASE_URL ?>/trainer/workout_add.php?member_id=<?= $member['id'] ?>" class="btn btn-primary px-3">
                    <i class="fa-solid fa-plus me-1"></i> Add Workout Routine
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Information Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-4 h-100">
            <h6 class="fw-bold text-secondary text-uppercase small mb-3">Trainee Physical & Contact Info</h6>
            <ul class="list-unstyled small mb-0">
                <li class="py-1"><strong>Gender:</strong> <?= htmlspecialchars($member['gender']) ?></li>
                <li class="py-1"><strong>Date of Birth:</strong> <?= format_date($member['dob']) ?></li>
                <li class="py-1"><strong>Emergency Contact:</strong> <?= htmlspecialchars($member['emergency_contact'] ?? 'None recorded') ?></li>
                <li class="py-1"><strong>Address:</strong> <?= htmlspecialchars($member['address'] ?? 'Not provided') ?></li>
            </ul>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-4 h-100">
            <h6 class="fw-bold text-secondary text-uppercase small mb-3">Gym Subscription & Training Status</h6>
            <?php if ($activeSub): ?>
                <div class="fw-bold text-primary fs-5 mb-1"><?= htmlspecialchars($activeSub['plan_name']) ?></div>
                <div class="small text-muted mb-2">Valid until: <strong><?= format_date($activeSub['end_date']) ?></strong></div>
                <span class="badge bg-success bg-opacity-10 text-success border border-success p-2">Active Subscription</span>
            <?php else: ?>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger p-2">No Active Subscription</span>
                <p class="text-muted small mt-2">Member subscription is currently expired or pending renewal.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Assigned Workout Routines -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-dumbbell me-2 text-warning"></i>Assigned Workout & Nutrition Routine</h6>
        <a href="<?= BASE_URL ?>/trainer/workout_add.php?member_id=<?= $member['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-plus me-1"></i> Add Day Schedule
        </a>
    </div>
    <div class="card-body p-4">
        <?php if (!empty($workouts)): ?>
            <div class="row g-3">
                <?php foreach ($workouts as $w): ?>
                    <div class="col-md-6">
                        <div class="card border rounded-3 p-3 h-100 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-dark"><?= htmlspecialchars($w['day_of_week']) ?></span>
                                <a href="<?= BASE_URL ?>/trainer/workout_edit.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2 small">
                                    <i class="fa-regular fa-pen-to-square me-1"></i> Edit
                                </a>
                            </div>
                            <h6 class="fw-bold text-primary mb-2"><?= htmlspecialchars($w['title']) ?></h6>
                            <div class="small mb-3">
                                <div class="fw-semibold text-secondary">Exercises & Sets:</div>
                                <pre class="bg-white p-2 border rounded font-monospace small mb-0" style="white-space: pre-wrap; font-size: 0.8rem;"><?= htmlspecialchars($w['exercise_details']) ?></pre>
                            </div>
                            <?php if (!empty($w['diet_notes'])): ?>
                                <div class="small">
                                    <div class="fw-semibold text-success"><i class="fa-solid fa-apple-whole me-1"></i>Diet & Nutrition Guidelines:</div>
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
                No workout routines assigned yet. Click 'Add Day Schedule' to prescribe workouts for this member.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Attendance History -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-calendar-check me-2 text-primary"></i>Recent Attendance Check-ins</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                    <?php if (!empty($recentAttendance)): ?>
                        <?php foreach ($recentAttendance as $ra): ?>
                            <tr>
                                <td class="fw-bold"><?= format_date($ra['attendance_date']) ?></td>
                                <td><i class="fa-regular fa-clock me-1 text-success"></i><?= format_time($ra['check_in_time']) ?></td>
                                <td><?= $ra['check_out_time'] ? '<i class="fa-regular fa-clock me-1 text-danger"></i>' . format_time($ra['check_out_time']) : '<span class="text-muted">--</span>' ?></td>
                                <td><?= get_status_badge($ra['status']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($ra['notes'] ?? 'Routine session') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No attendance logs available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
