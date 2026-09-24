<?php
/**
 * Edit Membership Plan
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$error = '';
$planId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id = ?");
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    set_flash('danger', 'Membership plan not found.');
    header("Location: " . BASE_URL . "/admin/plans.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_name = trim($_POST['plan_name'] ?? '');
    $duration_days = (int)($_POST['duration_days'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (empty($plan_name) || $duration_days <= 0 || $price <= 0) {
        $error = 'Plan name, valid duration (days), and price are required.';
    } else {
        try {
            $up = $pdo->prepare("
                UPDATE membership_plans 
                SET plan_name = ?, duration_days = ?, price = ?, description = ?, status = ?
                WHERE id = ?
            ");
            $up->execute([$plan_name, $duration_days, $price, $description, $status, $planId]);

            set_flash('success', "Membership plan '{$plan_name}' updated successfully!");
            header("Location: " . BASE_URL . "/admin/plans.php");
            exit();
        } catch (Exception $e) {
            $error = 'Failed to update plan: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Membership Plan - ' . $plan['plan_name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/admin/plans.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Plans
    </a>
    <h3 class="fw-bold mb-1">Edit Membership Plan</h3>
    <p class="text-muted small">Update plan details for <?= htmlspecialchars($plan['plan_name']) ?></p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <form method="POST" action="plan_edit.php?id=<?= $planId ?>">
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="plan_name" class="form-control" required value="<?= htmlspecialchars($_POST['plan_name'] ?? $plan['plan_name']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Status</label>
                            <select name="status" class="form-select">
                                <?php $st = $_POST['status'] ?? $plan['status']; ?>
                                <option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $st === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Duration (in Days) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="duration_days" class="form-control" required min="1" value="<?= htmlspecialchars($_POST['duration_days'] ?? $plan['duration_days']) ?>">
                                <span class="input-group-text bg-light">Days</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Price (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><?= CURRENCY_SYMBOL ?></span>
                                <input type="number" step="0.01" name="price" class="form-control" required min="0" value="<?= htmlspecialchars($_POST['price'] ?? $plan['price']) ?>">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Plan Description & Amenities Included</label>
                            <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($_POST['description'] ?? $plan['description']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/admin/plans.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="fa-solid fa-check me-1"></i> Update Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
