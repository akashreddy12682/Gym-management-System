<?php
/**
 * Trainer Dashboard
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('trainer');

$pdo = get_db_connection();
$user = current_user();

// Retrieve trainer ID
$trStmt = $pdo->prepare("SELECT * FROM trainers WHERE user_id = ?");
$trStmt->execute([$user['id']]);
$trainer = $trStmt->fetch();

if (!$trainer) {
    die("Trainer profile not linked to user account.");
}

$trainerId = $trainer['id'];

// 1. Stats
// Total Assigned Members
$stmt1 = $pdo->prepare("SELECT COUNT(*) FROM members WHERE trainer_id = ?");
$stmt1->execute([$trainerId]);
$totalMembers = (int)$stmt1->fetchColumn();

// Active Assigned Members
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM members WHERE trainer_id = ? AND status = 'active'");
$stmt2->execute([$trainerId]);
$activeMembers = (int)$stmt2->fetchColumn();

// Total Workout Plans Created
$stmt3 = $pdo->prepare("SELECT COUNT(*) FROM workouts WHERE trainer_id = ?");
$stmt3->execute([$trainerId]);
$totalWorkouts = (int)$stmt3->fetchColumn();

// 2. Fetch Assigned Members (Recent 5)
$membersStmt = $pdo->prepare("
    SELECT m.id AS member_id, u.name, u.email, u.phone, m.gender, m.status,
           (SELECT mp.plan_name FROM memberships ms JOIN membership_plans mp ON ms.plan_id = mp.id WHERE ms.member_id = m.id AND ms.status = 'active' ORDER BY ms.id DESC LIMIT 1) AS active_plan
    FROM members m
    JOIN users u ON m.user_id = u.id
    WHERE m.trainer_id = ?
    ORDER BY m.id DESC
    LIMIT 5
");
$membersStmt->execute([$trainerId]);
$recentMembers = $membersStmt->fetchAll();

// 3. Fetch Recent Workout Plans Created
$workoutsStmt = $pdo->prepare("
    SELECT w.*, u.name AS member_name 
    FROM workouts w 
    JOIN members m ON w.member_id = m.id 
    JOIN users u ON m.user_id = u.id 
    WHERE w.trainer_id = ? 
    ORDER BY w.id DESC 
    LIMIT 5
");
$workoutsStmt->execute([$trainerId]);
$recentWorkouts = $workoutsStmt->fetchAll();

$pageTitle = 'Trainer Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Coach Dashboard &bull; <?= htmlspecialchars($user['name']) ?></h3>
        <p class="text-muted small mb-0">Specialization: <span class="badge bg-warning bg-opacity-25 text-dark"><?= htmlspecialchars($trainer['specialization']) ?></span></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/trainer/workout_add.php" class="btn btn-primary btn-sm px-3">
            <i class="fa-solid fa-plus me-1"></i> Add Workout Plan
        </a>
    </div>
</div>

<!-- 3 KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">My Assigned Clients</div>
                <h3 class="fw-bold mb-0 mt-1"><?= $totalMembers ?></h3>
                <small class="text-muted">Members under coaching</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-primary">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Active Trainees</div>
                <h3 class="fw-bold mb-0 mt-1"><?= $activeMembers ?></h3>
                <small class="text-success"><i class="fa-solid fa-circle-check me-1"></i>Active gym status</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-success">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Workout Routines Assigned</div>
                <h3 class="fw-bold mb-0 mt-1"><?= $totalWorkouts ?></h3>
                <small class="text-muted">Daily routine sheets</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-purple">
                <i class="fa-solid fa-dumbbell"></i>
            </div>
        </div>
    </div>
</div>

<!-- Grid: Assigned Members & Workouts -->
<div class="row g-4">
    <!-- Assigned Members -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-users me-2 text-primary"></i>My Assigned Members</h6>
                <a href="<?= BASE_URL ?>/trainer/members.php" class="btn btn-sm btn-light border text-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Member</th>
                                <th>Active Plan</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentMembers)): ?>
                                <?php foreach ($recentMembers as $rm): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($rm['name']) ?></div>
                                            <div class="small text-muted"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($rm['email']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($rm['active_plan'] ?? 'No Plan') ?></span>
                                        </td>
                                        <td><?= get_status_badge($rm['status']) ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>/trainer/member_view.php?id=<?= $rm['member_id'] ?>" class="btn btn-outline-secondary" title="View Profile">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/trainer/workout_add.php?member_id=<?= $rm['member_id'] ?>" class="btn btn-outline-primary" title="Assign Workout">
                                                    <i class="fa-solid fa-plus"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No members assigned to your profile yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Workout Plans -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-dumbbell me-2 text-warning"></i>Recent Routines</h6>
                <a href="<?= BASE_URL ?>/trainer/workouts.php" class="btn btn-sm btn-light border text-secondary">View All</a>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($recentWorkouts)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentWorkouts as $rw): ?>
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($rw['title']) ?></h6>
                                    <span class="badge bg-dark small"><?= htmlspecialchars($rw['day_of_week']) ?></span>
                                </div>
                                <div class="small text-muted mb-2">Member: <strong class="text-secondary"><?= htmlspecialchars($rw['member_name']) ?></strong></div>
                                <div class="d-flex justify-content-end">
                                    <a href="<?= BASE_URL ?>/trainer/workout_edit.php?id=<?= $rw['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2 small">
                                        <i class="fa-regular fa-pen-to-square me-1"></i>Edit Routine
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted my-4">No workout routines created yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
