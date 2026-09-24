<?php
/**
 * Trainer Detailed View
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$trainerId = (int)($_GET['id'] ?? 0);

// Fetch Trainer
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

// Fetch Assigned Members
$membersStmt = $pdo->prepare("
    SELECT m.id AS member_id, m.join_date, m.status, u.name, u.email, u.phone, m.gender,
           (SELECT mp.plan_name FROM memberships ms JOIN membership_plans mp ON ms.plan_id = mp.id WHERE ms.member_id = m.id AND ms.status = 'active' ORDER BY ms.id DESC LIMIT 1) AS active_plan
    FROM members m
    JOIN users u ON m.user_id = u.id
    WHERE m.trainer_id = ?
    ORDER BY m.id DESC
");
$membersStmt->execute([$trainerId]);
$assignedMembers = $membersStmt->fetchAll();

$pageTitle = 'Trainer Profile - ' . $trainer['name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>/admin/trainers.php" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Trainers
    </a>
</div>

<!-- Trainer Header Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 70px; height: 70px;">
                    <i class="fa-solid fa-user-ninja"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($trainer['name']) ?></h4>
                        <span class="badge bg-light text-muted border">#TR<?= str_pad($trainer['id'], 3, '0', STR_PAD_LEFT) ?></span>
                        <?= get_status_badge($trainer['status']) ?>
                    </div>
                    <div class="text-muted small mt-1 d-flex flex-wrap gap-3">
                        <span><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($trainer['email']) ?></span>
                        <span><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($trainer['phone'] ?? 'N/A') ?></span>
                        <span><i class="fa-solid fa-calendar me-1"></i>Joined <?= format_date($trainer['joining_date']) ?></span>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/admin/trainer_edit.php?id=<?= $trainer['id'] ?>" class="btn btn-outline-dark btn-sm px-3">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit Trainer
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Info & Assigned Members -->
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-4 h-100">
            <h6 class="fw-bold text-secondary text-uppercase small mb-3">Specialization & Bio</h6>
            <div class="mb-3">
                <span class="badge bg-warning bg-opacity-25 text-dark fw-bold fs-6 p-2">
                    <?= htmlspecialchars($trainer['specialization']) ?>
                </span>
            </div>
            <p class="text-muted small mb-0">
                <?= nl2br(htmlspecialchars($trainer['bio'] ?? 'No bio entered.')) ?>
            </p>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-users me-2 text-primary"></i>Assigned Clients (<?= count($assignedMembers) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Member</th>
                                <th>Contact</th>
                                <th>Active Plan</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($assignedMembers)): ?>
                                <?php foreach ($assignedMembers as $am): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($am['name']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($am['gender']) ?></div>
                                        </td>
                                        <td class="small">
                                            <div><?= htmlspecialchars($am['email']) ?></div>
                                            <div class="text-muted"><?= htmlspecialchars($am['phone'] ?? 'N/A') ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($am['active_plan'] ?? 'No Plan') ?></span>
                                        </td>
                                        <td><?= get_status_badge($am['status']) ?></td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>/admin/member_view.php?id=<?= $am['member_id'] ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="View Member Profile">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No members are assigned to this coach yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
