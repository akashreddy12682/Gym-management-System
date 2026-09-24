<?php
/**
 * Membership Plans Management
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();

$stmt = $pdo->query("
    SELECT mp.*, 
           (SELECT COUNT(*) FROM memberships ms WHERE ms.plan_id = mp.id AND ms.status = 'active') AS active_subscribers
    FROM membership_plans mp 
    ORDER BY mp.price ASC
");
$plans = $stmt->fetchAll();

$pageTitle = 'Membership Plans';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Membership Packages</h3>
        <p class="text-muted small mb-0">Manage gym packages, durations, and pricing.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/admin/plan_add.php" class="btn btn-primary px-3">
            <i class="fa-solid fa-plus me-1"></i> Create New Plan
        </a>
    </div>
</div>

<div class="row g-4">
    <?php if (!empty($plans)): ?>
        <?php foreach ($plans as $p): ?>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-dark bg-opacity-10 text-dark fw-semibold px-2 py-1">
                            #PLN-<?= str_pad($p['id'], 2, '0', STR_PAD_LEFT) ?>
                        </span>
                        <?= get_status_badge($p['status']) ?>
                    </div>

                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($p['plan_name']) ?></h5>
                    <div class="display-6 fw-bold text-primary my-2">
                        <?= format_currency($p['price']) ?>
                    </div>
                    
                    <div class="small text-muted mb-3">
                        <i class="fa-regular fa-clock me-1 text-secondary"></i> <strong><?= (int)$p['duration_days'] ?> Days</strong> Duration
                    </div>

                    <p class="text-muted small flex-grow-1">
                        <?= htmlspecialchars($p['description'] ?? 'Standard gym membership offering.') ?>
                    </p>

                    <div class="border-top pt-3 mt-3 d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            <i class="fa-solid fa-users text-primary me-1"></i> <?= (int)$p['active_subscribers'] ?> Active
                        </span>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= BASE_URL ?>/admin/plan_edit.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary" title="Edit Plan">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/admin/plan_delete.php?id=<?= $p['id'] ?>" class="btn btn-outline-danger" title="Delete Plan" data-confirm-delete="Are you sure you want to delete plan '<?= htmlspecialchars($p['plan_name']) ?>'?">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5 text-muted">
            No membership plans found. Click 'Create New Plan' above to add one.
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
