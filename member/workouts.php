<?php
/**
 * Member - Workout Routines & Nutrition Schedule
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

// Fetch workouts
$stmt = $pdo->prepare("
    SELECT w.*, tu.name AS trainer_name, t.specialization 
    FROM workouts w 
    LEFT JOIN trainers t ON w.trainer_id = t.id 
    LEFT JOIN users tu ON t.user_id = tu.id 
    WHERE w.member_id = ? 
    ORDER BY FIELD(w.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'All Days')
");
$stmt->execute([$memberId]);
$workouts = $stmt->fetchAll();

$pageTitle = 'My Workout & Diet Routine';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold mb-1">My Weekly Training & Nutrition Plan</h3>
    <p class="text-muted small">Targeted exercise breakdown and nutritional guidelines curated by your fitness coach.</p>
</div>

<div class="row g-4">
    <?php if (!empty($workouts)): ?>
        <?php foreach ($workouts as $w): ?>
            <?php $isToday = (strtolower(date('l')) === strtolower($w['day_of_week'])); ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 position-relative <?= $isToday ? 'border-start border-warning border-5' : '' ?>">
                    <?php if ($isToday): ?>
                        <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-3 fw-bold">TODAY'S WORKOUT</span>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-dark px-3 py-1"><?= htmlspecialchars($w['day_of_week']) ?></span>
                    </div>

                    <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($w['title']) ?></h5>
                    <div class="small text-muted mb-3">
                        <i class="fa-solid fa-user-ninja text-warning me-1"></i> Coach: <?= htmlspecialchars($w['trainer_name'] ?? 'Gym Coach') ?>
                    </div>

                    <div class="mb-3">
                        <div class="small fw-semibold text-secondary mb-1">Exercises & Sets:</div>
                        <pre class="bg-light p-3 border rounded font-monospace small mb-0" style="white-space: pre-wrap; font-size: 0.85rem;"><?= htmlspecialchars($w['exercise_details']) ?></pre>
                    </div>

                    <?php if (!empty($w['diet_notes'])): ?>
                        <div class="mt-auto pt-3 border-top">
                            <div class="small fw-semibold text-success mb-1">
                                <i class="fa-solid fa-apple-whole me-1"></i>Diet & Nutrition Notes:
                            </div>
                            <p class="text-muted small mb-0" style="font-size: 0.85rem;"><?= nl2br(htmlspecialchars($w['diet_notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="fa-solid fa-dumbbell display-6 d-block mb-3 opacity-50"></i>
            <h5>No Workout Routines Assigned Yet</h5>
            <p class="small mb-0">Your personal coach or gym admin has not yet assigned a routine to your profile. Please speak with your trainer!</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
