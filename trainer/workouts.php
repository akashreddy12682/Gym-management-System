<?php
/**
 * Trainer - Manage Workout & Diet Plans
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

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $delId = (int)($_GET['id'] ?? 0);
    if ($delId > 0) {
        $delStmt = $pdo->prepare("DELETE FROM workouts WHERE id = ? AND trainer_id = ?");
        $delStmt->execute([$delId, $trainerId]);
        set_flash('success', 'Workout routine deleted successfully.');
        header("Location: " . BASE_URL . "/trainer/workouts.php");
        exit();
    }
}

$stmt = $pdo->prepare("
    SELECT w.*, u.name AS member_name, u.email AS member_email, m.id AS member_profile_id 
    FROM workouts w 
    JOIN members m ON w.member_id = m.id 
    JOIN users u ON m.user_id = u.id 
    WHERE w.trainer_id = ? 
    ORDER BY u.name ASC, FIELD(w.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'All Days')
");
$stmt->execute([$trainerId]);
$workouts = $stmt->fetchAll();

$pageTitle = 'Workout Plans';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Prescribed Workout Routines</h3>
        <p class="text-muted small mb-0">Total <?= count($workouts) ?> workout & diet plan(s) assigned to your trainees.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/trainer/workout_add.php" class="btn btn-primary px-3">
            <i class="fa-solid fa-plus me-1"></i> Add Workout Routine
        </a>
    </div>
</div>

<div class="row g-4">
    <?php if (!empty($workouts)): ?>
        <?php foreach ($workouts as $w): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-dark px-2 py-1"><?= htmlspecialchars($w['day_of_week']) ?></span>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border py-0 px-2" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/trainer/workout_edit.php?id=<?= $w['id'] ?>">
                                        <i class="fa-regular fa-pen-to-square me-2 text-primary"></i>Edit Routine
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/trainer/workouts.php?action=delete&id=<?= $w['id'] ?>" data-confirm-delete="Are you sure you want to delete this routine?">
                                        <i class="fa-regular fa-trash-can me-2"></i>Delete Routine
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($w['title']) ?></h5>
                    <div class="small text-muted mb-3">
                        Trainee: <a href="<?= BASE_URL ?>/trainer/member_view.php?id=<?= $w['member_profile_id'] ?>" class="fw-bold text-dark text-decoration-none"><?= htmlspecialchars($w['member_name']) ?></a>
                    </div>

                    <div class="mb-3">
                        <div class="small fw-semibold text-secondary mb-1">Exercise List:</div>
                        <pre class="bg-light p-2 border rounded font-monospace small mb-0" style="white-space: pre-wrap; font-size: 0.8rem;"><?= htmlspecialchars($w['exercise_details']) ?></pre>
                    </div>

                    <?php if (!empty($w['diet_notes'])): ?>
                        <div class="mt-auto pt-2 border-top">
                            <div class="small fw-semibold text-success mb-1"><i class="fa-solid fa-apple-whole me-1"></i>Diet & Nutrition:</div>
                            <p class="text-muted small mb-0" style="font-size: 0.8rem;"><?= nl2br(htmlspecialchars($w['diet_notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="fa-solid fa-dumbbell display-6 d-block mb-2 opacity-50"></i>
            No workout routines logged yet. Click 'Add Workout Routine' to prescribe exercises for your members.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
