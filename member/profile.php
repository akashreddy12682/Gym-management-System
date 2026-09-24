<?php
/**
 * Member Profile View & Update
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('member');

$pdo = get_db_connection();
$user = current_user();
$error = '';

// Fetch Member Details
$mStmt = $pdo->prepare("
    SELECT m.*, u.name, u.email, u.phone 
    FROM members m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.user_id = ?
");
$mStmt->execute([$user['id']]);
$member = $mStmt->fetch();

if (!$member) {
    die("Profile not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $new_password = trim($_POST['password'] ?? '');

    try {
        $pdo->beginTransaction();

        // 1. Update user phone & optionally password
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $uStmt = $pdo->prepare("UPDATE users SET phone = ?, password = ? WHERE id = ?");
            $uStmt->execute([$phone, $hashed, $user['id']]);
        } else {
            $uStmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $uStmt->execute([$phone, $user['id']]);
        }

        // 2. Update member profile fields
        $memUp = $pdo->prepare("UPDATE members SET address = ?, emergency_contact = ? WHERE id = ?");
        $memUp->execute([$address, $emergency_contact, $member['id']]);

        $pdo->commit();

        set_flash('success', 'Your profile information has been successfully updated!');
        header("Location: " . BASE_URL . "/member/profile.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Failed to update profile: ' . $e->getMessage();
    }
}

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold mb-1">My Personal Profile</h3>
    <p class="text-muted small">Manage your contact information and login credentials.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Readonly Overview Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div class="bg-dark text-warning rounded-circle d-flex align-items-center justify-content-center fw-bold fs-2 mx-auto mb-3" style="width: 80px; height: 80px;">
                <?= strtoupper(substr($member['name'], 0, 1)) ?>
            </div>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($member['name']) ?></h5>
            <p class="text-muted small mb-2"><?= htmlspecialchars($member['email']) ?></p>
            <div>
                <?= get_status_badge($member['status']) ?>
            </div>

            <hr class="my-4">

            <div class="text-start small text-muted">
                <div class="mb-2"><strong>Member ID:</strong> #M<?= str_pad($member['id'], 4, '0', STR_PAD_LEFT) ?></div>
                <div class="mb-2"><strong>Gender:</strong> <?= htmlspecialchars($member['gender']) ?></div>
                <div class="mb-2"><strong>Date of Birth:</strong> <?= format_date($member['dob']) ?></div>
                <div class="mb-0"><strong>Join Date:</strong> <?= format_date($member['join_date']) ?></div>
            </div>
        </div>
    </div>

    <!-- Editable Form Card -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-pen me-2 text-primary"></i>Update Contact Information</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="profile.php">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Full Name</label>
                            <input type="text" class="form-control bg-light" disabled value="<?= htmlspecialchars($member['name']) ?>">
                            <small class="text-muted" style="font-size: 0.75rem;">Name changes must be requested at gym front desk.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Registered Email</label>
                            <input type="email" class="form-control bg-light" disabled value="<?= htmlspecialchars($member['email']) ?>">
                            <small class="text-muted" style="font-size: 0.75rem;">Login email address cannot be modified directly.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member['phone'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Emergency Contact (Name & Phone)</label>
                            <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($member['emergency_contact'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Residential Address</label>
                            <textarea name="address" rows="2" class="form-control"><?= htmlspecialchars($member['address'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Change Password <span class="text-muted fw-normal">(Leave blank to keep existing)</span></label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="fa-solid fa-check me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
