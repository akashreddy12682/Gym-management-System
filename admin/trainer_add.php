<?php
/**
 * Add New Trainer
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? 'trainer123');
    $phone = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $joining_date = !empty($_POST['joining_date']) ? $_POST['joining_date'] : date('Y-m-d');
    $status = $_POST['status'] ?? 'active';
    $bio = trim($_POST['bio'] ?? '');

    if (empty($name) || empty($email) || empty($specialization)) {
        $error = 'Name, email, and specialization are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = 'A user with this email address is already registered.';
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Insert user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $userStmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, phone, status, created_at)
                    VALUES (?, ?, ?, 'trainer', ?, ?, NOW())
                ");
                $userStmt->execute([$name, $email, $hashedPassword, $phone, $status]);
                $userId = $pdo->lastInsertId();

                // 2. Insert trainer
                $trainerStmt = $pdo->prepare("
                    INSERT INTO trainers (user_id, specialization, joining_date, bio, status, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $trainerStmt->execute([$userId, $specialization, $joining_date, $bio, $status]);
                $newTrainerId = $pdo->lastInsertId();

                $pdo->commit();

                set_flash('success', "Trainer '{$name}' created successfully! (Trainer ID: #TR" . str_pad($newTrainerId, 3, '0', STR_PAD_LEFT) . ")");
                header("Location: " . BASE_URL . "/admin/trainers.php");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to create trainer: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Add Trainer';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/admin/trainers.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Trainers
    </a>
    <h3 class="fw-bold mb-1">Add Personal Trainer</h3>
    <p class="text-muted small">Register a new fitness coach and assign login credentials.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="trainer_add.php">
            <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">1. Login Credentials & Contact</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Alex Mercer" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="e.g. alex@gym.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Password <span class="text-muted fw-normal">(Defaults to 'trainer123')</span></label>
                    <input type="password" name="password" class="form-control" placeholder="trainer123" value="<?= htmlspecialchars($_POST['password'] ?? 'trainer123') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="+1 555-0112" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">2. Coaching Profile & Expertise</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Primary Specialization <span class="text-danger">*</span></label>
                    <input type="text" name="specialization" class="form-control" required placeholder="e.g. Strength Training, CrossFit, Yoga & Pilates" value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?= htmlspecialchars($_POST['joining_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= (($_POST['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small">Bio & Professional Experience</label>
                    <textarea name="bio" rows="3" class="form-control" placeholder="Brief background, certifications (e.g. ACE, CSCS), achievements..."><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= BASE_URL ?>/admin/trainers.php" class="btn btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="fa-solid fa-check me-1"></i> Save Trainer
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
