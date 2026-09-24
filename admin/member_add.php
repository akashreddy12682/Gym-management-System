<?php
/**
 * Add New Member
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$error = '';

// Fetch all active trainers for dropdown
$trainersStmt = $pdo->query("
    SELECT t.id, u.name, t.specialization 
    FROM trainers t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.status = 'active' 
    ORDER BY u.name ASC
");
$trainers = $trainersStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? 'member123');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $address = trim($_POST['address'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $trainer_id = !empty($_POST['trainer_id']) ? (int)$_POST['trainer_id'] : null;
    $join_date = !empty($_POST['join_date']) ? $_POST['join_date'] : date('Y-m-d');
    $status = $_POST['status'] ?? 'active';

    if (empty($name) || empty($email)) {
        $error = 'Full name and email address are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email already registered
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = 'A user with this email address is already registered.';
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Insert into users table
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $userStmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, phone, status, created_at)
                    VALUES (?, ?, ?, 'member', ?, ?, NOW())
                ");
                $userStmt->execute([$name, $email, $hashedPassword, $phone, $status]);
                $userId = $pdo->lastInsertId();

                // 2. Insert into members table
                $memberStmt = $pdo->prepare("
                    INSERT INTO members (user_id, gender, dob, address, emergency_contact, trainer_id, join_date, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $memberStmt->execute([$userId, $gender, $dob, $address, $emergency_contact, $trainer_id, $join_date, $status]);
                $newMemberId = $pdo->lastInsertId();

                $pdo->commit();

                set_flash('success', "Member '{$name}' registered successfully! (Member ID: #M" . str_pad($newMemberId, 4, '0', STR_PAD_LEFT) . ")");
                header("Location: " . BASE_URL . "/admin/members.php");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to register member: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Add Member';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Members
    </a>
    <h3 class="fw-bold mb-1">Register New Gym Member</h3>
    <p class="text-muted small">Create member profile and login credentials.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="member_add.php">
            <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">1. Account & Login Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. John Doe" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="e.g. john@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Password <span class="text-muted fw-normal">(Defaults to 'member123')</span></label>
                    <input type="password" name="password" class="form-control" placeholder="member123" value="<?= htmlspecialchars($_POST['password'] ?? 'member123') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="+1 555-0199" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">2. Physical & Gym Profile</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Join Date</label>
                    <input type="date" name="join_date" class="form-control" value="<?= htmlspecialchars($_POST['join_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Account Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= (($_POST['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Assign Personal Trainer <span class="text-muted fw-normal">(Optional)</span></label>
                    <select name="trainer_id" class="form-select">
                        <option value="">-- No Assigned Trainer --</option>
                        <?php foreach ($trainers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= (($_POST['trainer_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['specialization']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Emergency Contact Info</label>
                    <input type="text" name="emergency_contact" class="form-control" placeholder="Name & Phone (e.g. Spouse +1 555-9002)" value="<?= htmlspecialchars($_POST['emergency_contact'] ?? '') ?>">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold small">Residential Address</label>
                    <textarea name="address" rows="2" class="form-control" placeholder="Street, City, Zipcode"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="fa-solid fa-check me-1"></i> Save Member
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
