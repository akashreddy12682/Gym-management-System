<?php
/**
 * Trainer - Add Workout & Diet Plan
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

$preselectedMemberId = (int)($_GET['member_id'] ?? 0);
$error = '';

// Fetch assigned members
$membersStmt = $pdo->prepare("
    SELECT m.id, u.name, u.email 
    FROM members m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.trainer_id = ? 
    ORDER BY u.name ASC
");
$membersStmt->execute([$trainerId]);
$assignedMembers = $membersStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $day_of_week = $_POST['day_of_week'] ?? 'Monday';
    $exercise_details = trim($_POST['exercise_details'] ?? '');
    $diet_notes = trim($_POST['diet_notes'] ?? '');

    if ($member_id <= 0 || empty($title) || empty($exercise_details)) {
        $error = 'Please select a member and provide a title and exercise details.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO workouts (member_id, trainer_id, title, day_of_week, exercise_details, diet_notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$member_id, $trainerId, $title, $day_of_week, $exercise_details, $diet_notes]);

            set_flash('success', "Workout routine '{$title}' created successfully!");
            header("Location: " . BASE_URL . "/trainer/member_view.php?id=" . $member_id);
            exit();
        } catch (Exception $e) {
            $error = 'Failed to create workout routine: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Workout Routine';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/trainer/workouts.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Routines
    </a>
    <h3 class="fw-bold mb-1">Prescribe Workout & Diet Plan</h3>
    <p class="text-muted small">Create a targeted exercise schedule and nutritional guidelines for your trainee.</p>
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
                <form method="POST" action="workout_add.php">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Select Trainee <span class="text-danger">*</span></label>
                            <select name="member_id" class="form-select" required>
                                <option value="">-- Choose Assigned Member --</option>
                                <?php foreach ($assignedMembers as $am): ?>
                                    <option value="<?= $am['id'] ?>" <?= ($preselectedMemberId === (int)$am['id'] || (($_POST['member_id'] ?? '') == $am['id'])) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($am['name']) ?> (<?= htmlspecialchars($am['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Day of Week <span class="text-danger">*</span></label>
                            <select name="day_of_week" class="form-select" required>
                                <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'All Days'] as $day): ?>
                                    <option value="<?= $day ?>" <?= (($_POST['day_of_week'] ?? 'Monday') === $day) ? 'selected' : '' ?>>
                                        <?= $day ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Workout Routine Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Chest & Triceps Hypertrophy, Legs & Core Power" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Exercise Details (Exercises, Sets, Reps) <span class="text-danger">*</span></label>
                            <textarea name="exercise_details" rows="6" class="form-control font-monospace" required placeholder="1. Barbell Flat Bench Press: 4 sets x 8-10 reps
2. Incline Dumbbell Press: 3 sets x 10 reps
3. Cable Crossovers: 3 sets x 15 reps
4. Tricep Pushdowns: 4 sets x 12 reps"><?= htmlspecialchars($_POST['exercise_details'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Diet & Nutritional Notes <span class="text-muted fw-normal">(Optional)</span></label>
                            <textarea name="diet_notes" rows="3" class="form-control" placeholder="Target calories, protein requirements, pre/post workout meals, water intake..."><?= htmlspecialchars($_POST['diet_notes'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/trainer/workouts.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="fa-solid fa-check me-1"></i> Save Workout Routine
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
