<?php
/**
 * Edit Member
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$error = '';
$memberId = (int)($_GET['id'] ?? 0);

// Fetch existing member details
$stmt = $pdo->prepare("
    SELECT m.*, u.name, u.email, u.phone, u.status AS user_status 
    FROM members m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.id = ?
");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    set_flash('danger', 'Member not found.');
    header("Location: " . BASE_URL . "/admin/members.php");
    exit();
}

// Fetch active trainers for dropdown
$trainersStmt = $pdo->query("
    SELECT t.id, u.name, t.specialization 
    FROM trainers t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.status = 'active' OR t.id = " . (int)($member['trainer_id'] ?: 0) . "
    ORDER BY u.name ASC
");
$trainers = $trainersStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $address = trim($_POST['address'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $trainer_id = !empty($_POST['trainer_id']) ? (int)$_POST['trainer_id'] : null;
    $join_date = !empty($_POST['join_date']) ? $_POST['join_date'] : $member['join_date'];
    $status = $_POST['status'] ?? 'active';

    if (empty($name) || empty($email)) {
        $error = 'Full name and email address are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email taken by another user
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $member['user_id']]);
        if ($checkStmt->fetch()) {
            $error = 'This email address is already in use by another user.';
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Update user
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $upUser = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, password = ?, phone = ?, status = ?
                        WHERE id = ?
                    ");
                    $upUser->execute([$name, $email, $hashedPassword, $phone, $status, $member['user_id']]);
                } else {
                    $upUser = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, phone = ?, status = ?
                        WHERE id = ?
                    ");
                    $upUser->execute([$name, $email, $phone, $status, $member['user_id']]);
                }

                // 2. Update member profile
                $upMember = $pdo->prepare("
                    UPDATE members 
                    SET gender = ?, dob = ?, address = ?, emergency_contact = ?, trainer_id = ?, join_date = ?, status = ?
                    WHERE id = ?
                ");
                $upMember->execute([$gender, $dob, $address, $emergency_contact, $trainer_id, $join_date, $status, $memberId]);

                $pdo->commit();

                set_flash('success', "Member '{$name}' updated successfully!");
                header("Location: " . BASE_URL . "/admin/member_view.php?id=" . $memberId);
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to update member: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Member - ' . $member['name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Members
    </a>
    <h3 class="fw-bold mb-1">Edit Member Profile</h3>
    <p class="text-muted small">Update profile information for <?= htmlspecialchars($member['name']) ?> (ID: #M<?= str_pad($member['id'], 4, '0', STR_PAD_LEFT) ?>)</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
        <form method="POST" action="member_edit.php?id=<?= $memberId ?>">
            <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">1. Account & Security</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? $member['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? $member['email']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">New Password <span class="text-muted fw-normal">(Leave blank to keep existing password)</span></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? $member['phone']) ?>">
                </div>
            </div>

            <h6 class="fw-bold text-uppercase text-secondary border-bottom pb-2 mb-3">2. Physical & Gym Profile</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Gender</label>
                    <select name="gender" class="form-select">
                        <?php $g = $_POST['gender'] ?? $member['gender']; ?>
                        <option value="Male" <?= $g === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $g === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $g === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? $member['dob']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Join Date</label>
                    <input type="date" name="join_date" class="form-control" value="<?= htmlspecialchars($_POST['join_date'] ?? $member['join_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Membership Status</label>
                    <select name="status" class="form-select">
                        <?php $st = $_POST['status'] ?? $member['status']; ?>
                        <option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $st === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Assigned Trainer</label>
                    <select name="trainer_id" class="form-select">
                        <option value="">-- No Assigned Trainer --</option>
                        <?php 
                        $currentTrainerId = $_POST['trainer_id'] ?? $member['trainer_id'];
                        foreach ($trainers as $t): 
                        ?>
                            <option value="<?= $t['id'] ?>" <?= $currentTrainerId == $t['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['specialization']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Emergency Contact Info</label>
                    <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($_POST['emergency_contact'] ?? $member['emergency_contact']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold small">Residential Address</label>
                    <textarea name="address" rows="2" class="form-control"><?= htmlspecialchars($_POST['address'] ?? $member['address']) ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="fa-solid fa-check me-1"></i> Update Member
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
