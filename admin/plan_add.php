<?php
/**
 * Add Membership Plan
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
    $plan_name = trim($_POST['plan_name'] ?? '');
    $duration_days = (int)($_POST['duration_days'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (empty($plan_name) || $duration_days <= 0 || $price <= 0) {
        $error = 'Plan name, valid duration (days), and price are required.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO membership_plans (plan_name, duration_days, price, description, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$plan_name, $duration_days, $price, $description, $status]);

            set_flash('success', "Membership plan '{$plan_name}' created successfully!");
            header("Location: " . BASE_URL . "/admin/plans.php");
            exit();
        } catch (Exception $e) {
            $error = 'Failed to create plan: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Membership Plan';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/admin/plans.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Plans
    </a>
    <h3 class="fw-bold mb-1">Create Membership Plan</h3>
    <p class="text-muted small">Define plan duration in days and pricing structure.</p>
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
                <form method="POST" action="plan_add.php">
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="plan_name" class="form-control" required placeholder="e.g. Monthly Standard, Quarterly Pro, Annual VIP" value="<?= htmlspecialchars($_POST['plan_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= (($_POST['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Duration (in Days) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="duration_days" class="form-control" required min="1" placeholder="e.g. 30, 90, 180, 365" value="<?= htmlspecialchars($_POST['duration_days'] ?? '') ?>">
                                <span class="input-group-text bg-light">Days</span>
                            </div>
                            <small class="text-muted">30 = 1 Mo, 90 = 3 Mo, 180 = 6 Mo, 365 = 1 Yr</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Price (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><?= CURRENCY_SYMBOL ?></span>
                                <input type="number" step="0.01" name="price" class="form-control" required min="0" placeholder="e.g. 49.99" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Plan Description & Amenities Included</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Describe access rights, trainer inclusion, steam bath, etc..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/admin/plans.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="fa-solid fa-check me-1"></i> Save Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
