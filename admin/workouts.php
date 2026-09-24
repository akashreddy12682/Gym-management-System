<?php
/**
 * Master Workouts Schedule View
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();

$stmt = $pdo->query("
    SELECT w.*, 
           m.id AS member_profile_id, u.name AS member_name, u.email AS member_email,
           tu.name AS trainer_name
    FROM workouts w
    JOIN members m ON w.member_id = m.id
    JOIN users u ON m.user_id = u.id
    LEFT JOIN trainers t ON w.trainer_id = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    ORDER BY u.name ASC, FIELD(w.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'All Days')
");
$workouts = $stmt->fetchAll();

$pageTitle = 'Workout Schedules';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Master Workout Schedules</h3>
        <p class="text-muted small mb-0">Overview of all exercise routines and diet plans prescribed to gym members.</p>
    </div>
</div>

<div class="row g-4">
    <?php if (!empty($workouts)): ?>
        <?php foreach ($workouts as $w): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-dark px-2 py-1"><?= htmlspecialchars($w['day_of_week']) ?></span>
                        <small class="text-muted"><i class="fa-solid fa-user-ninja me-1 text-warning"></i>Coach <?= htmlspecialchars($w['trainer_name'] ?? 'General') ?></small>
                    </div>

                    <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($w['title']) ?></h5>
                    <div class="small text-muted mb-3">
                        Assigned to: <a href="<?= BASE_URL ?>/admin/member_view.php?id=<?= $w['member_profile_id'] ?>" class="fw-bold text-dark text-decoration-none"><?= htmlspecialchars($w['member_name']) ?></a>
                    </div>

                    <div class="mb-3">
                        <div class="small fw-semibold text-secondary mb-1">Exercise Breakdown:</div>
                        <pre class="bg-light p-2 border rounded font-monospace small mb-0" style="white-space: pre-wrap; font-size: 0.8rem;"><?= htmlspecialchars($w['exercise_details']) ?></pre>
                    </div>

                    <?php if (!empty($w['diet_notes'])): ?>
                        <div class="mt-auto pt-2 border-top">
                            <div class="small fw-semibold text-success mb-1"><i class="fa-solid fa-apple-whole me-1"></i>Nutrition & Diet:</div>
                            <p class="text-muted small mb-0" style="font-size: 0.8rem;"><?= nl2br(htmlspecialchars($w['diet_notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="fa-solid fa-dumbbell display-6 d-block mb-2 opacity-50"></i>
            No workout routines logged yet. Trainers can assign workouts from the Trainer Portal.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
