<?php
/**
 * Edit Trainer
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$error = '';
$trainerId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT t.*, u.name, u.email, u.phone 
    FROM trainers t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->execute([$trainerId]);
$trainer = $stmt->fetch();

if (!$trainer) {
    set_flash('danger', 'Trainer not found.');
    header("Location: " . BASE_URL . "/admin/trainers.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $joining_date = !empty($_POST['joining_date']) ? $_POST['joining_date'] : $trainer['joining_date'];
    $status = $_POST['status'] ?? 'active';
    $bio = trim($_POST['bio'] ?? '');

    if (empty($name) || empty($email) || empty($specialization)) {
        $error = 'Name, email, and specialization are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $trainer['user_id']]);
        if ($checkStmt->fetch()) {
            $error = 'This email address is already in use by another user.';
        } else {
            try {
                $pdo->beginTransaction();

                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $upUser = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, phone = ?, status = ? WHERE id = ?");
                    $upUser->execute([$name, $email, $hashedPassword, $phone, $status, $trainer['user_id']]);
                } else {
                    $upUser = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, status = ? WHERE id = ?");
                    $upUser->execute([$name, $email, $phone, $status, $trainer['user_id']]);
                }

                $upTrainer = $pdo->prepare("UPDATE trainers SET specialization = ?, joining_date = ?, bio = ?, status = ? WHERE id = ?");
                $upTrainer->execute([$specialization, $joining_date, $bio, $status, $trainerId]);

                $pdo->commit();

                set_flash('success', "Trainer '{$name}' updated successfully!");
                header("Location: " . BASE_URL . "/admin/trainer_view.php?id=" . $trainerId);
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to update trainer: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Trainer - ' . $trainer['name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/admin/trainers.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Trainers
    </a>
    <h3 class="fw-bold mb-1">Edit Trainer Profile</h3>
    <p class="text-muted small">Update profile and specialization for <?= htmlspecialchars($trainer['name']) ?></p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="trainer_edit.php?id=<?= $trainerId ?>">
            <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">1. Login Credentials & Contact</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? $trainer['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? $trainer['email']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Change Password <span class="text-muted fw-normal">(Leave blank to keep current)</span></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? $trainer['phone']) ?>">
                </div>
            </div>

            <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">2. Coaching Profile & Expertise</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Primary Specialization <span class="text-danger">*</span></label>
                    <input type="text" name="specialization" class="form-control" required value="<?= htmlspecialchars($_POST['specialization'] ?? $trainer['specialization']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?= htmlspecialchars($_POST['joining_date'] ?? $trainer['joining_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Status</label>
                    <select name="status" class="form-select">
                        <?php $st = $_POST['status'] ?? $trainer['status']; ?>
                        <option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $st === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small">Bio & Experience</label>
                    <textarea name="bio" rows="3" class="form-control"><?= htmlspecialchars($_POST['bio'] ?? $trainer['bio']) ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= BASE_URL ?>/admin/trainers.php" class="btn btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="fa-solid fa-check me-1"></i> Update Trainer
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
