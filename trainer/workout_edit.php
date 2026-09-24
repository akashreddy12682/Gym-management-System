<?php
/**
 * Trainer - Edit Workout & Diet Plan
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

$workoutId = (int)($_GET['id'] ?? 0);
$error = '';

// Fetch workout
$stmt = $pdo->prepare("
    SELECT w.*, u.name AS member_name 
    FROM workouts w 
    JOIN members m ON w.member_id = m.id 
    JOIN users u ON m.user_id = u.id 
    WHERE w.id = ? AND w.trainer_id = ?
");
$stmt->execute([$workoutId, $trainerId]);
$workout = $stmt->fetch();

if (!$workout) {
    set_flash('danger', 'Workout routine not found or unauthorized.');
    header("Location: " . BASE_URL . "/trainer/workouts.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $day_of_week = $_POST['day_of_week'] ?? 'Monday';
    $exercise_details = trim($_POST['exercise_details'] ?? '');
    $diet_notes = trim($_POST['diet_notes'] ?? '');

    if (empty($title) || empty($exercise_details)) {
        $error = 'Title and exercise details cannot be empty.';
    } else {
        try {
            $up = $pdo->prepare("
                UPDATE workouts 
                SET title = ?, day_of_week = ?, exercise_details = ?, diet_notes = ? 
                WHERE id = ? AND trainer_id = ?
            ");
            $up->execute([$title, $day_of_week, $exercise_details, $diet_notes, $workoutId, $trainerId]);

            set_flash('success', "Workout routine updated successfully!");
            header("Location: " . BASE_URL . "/trainer/member_view.php?id=" . $workout['member_id']);
            exit();
        } catch (Exception $e) {
            $error = 'Failed to update routine: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Workout Routine';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/trainer/workouts.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Routines
    </a>
    <h3 class="fw-bold mb-1">Edit Workout Routine</h3>
    <p class="text-muted small">Update workout schedule for trainee: <strong><?= htmlspecialchars($workout['member_name']) ?></strong></p>
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
                <form method="POST" action="workout_edit.php?id=<?= $workoutId ?>">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Trainee</label>
                            <input type="text" class="form-control bg-light" disabled value="<?= htmlspecialchars($workout['member_name']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Day of Week <span class="text-danger">*</span></label>
                            <select name="day_of_week" class="form-select" required>
                                <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'All Days'] as $day): ?>
                                    <option value="<?= $day ?>" <?= (($workout['day_of_week']) === $day) ? 'selected' : '' ?>>
                                        <?= $day ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Workout Routine Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($_POST['title'] ?? $workout['title']) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Exercise Details (Exercises, Sets, Reps) <span class="text-danger">*</span></label>
                            <textarea name="exercise_details" rows="6" class="form-control font-monospace" required><?= htmlspecialchars($_POST['exercise_details'] ?? $workout['exercise_details']) ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Diet & Nutritional Notes</label>
                            <textarea name="diet_notes" rows="3" class="form-control"><?= htmlspecialchars($_POST['diet_notes'] ?? $workout['diet_notes']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/trainer/workouts.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="fa-solid fa-check me-1"></i> Update Routine
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
