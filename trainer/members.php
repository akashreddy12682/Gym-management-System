<?php
/**
 * Trainer - Assigned Members View
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('trainer');

$pdo = get_db_connection();
$user = current_user();

// Retrieve trainer ID
$trStmt = $pdo->prepare("SELECT id FROM trainers WHERE user_id = ?");
$trStmt->execute([$user['id']]);
$trainerId = (int)$trStmt->fetchColumn();

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT m.id AS member_id, m.gender, m.dob, m.join_date, m.status,
           u.name, u.email, u.phone,
           (SELECT mp.plan_name FROM memberships ms JOIN membership_plans mp ON ms.plan_id = mp.id WHERE ms.member_id = m.id AND ms.status = 'active' ORDER BY ms.id DESC LIMIT 1) AS active_plan,
           (SELECT COUNT(*) FROM workouts w WHERE w.member_id = m.id AND w.trainer_id = ?) AS workout_count
    FROM members m
    JOIN users u ON m.user_id = u.id
    WHERE m.trainer_id = ?
";

$params = [$trainerId, $trainerId];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

$pageTitle = 'My Assigned Members';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">My Assigned Trainees</h3>
        <p class="text-muted small mb-0">Total <?= count($members) ?> member(s) assigned to your fitness coaching.</p>
    </div>
</div>

<!-- Search Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="members.php" class="row g-2 align-items-center">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by trainee name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-filter me-1"></i> Search</button>
                <?php if (!empty($search)): ?>
                    <a href="members.php" class="btn btn-outline-secondary" title="Clear"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Members Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Member ID</th>
                        <th>Member Name</th>
                        <th>Contact</th>
                        <th>Current Plan</th>
                        <th>Routines</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($members)): ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td class="fw-bold text-muted">#M<?= str_pad($m['member_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($m['gender']) ?> &bull; Joined <?= format_date($m['join_date']) ?></div>
                                </td>
                                <td>
                                    <div><i class="fa-regular fa-envelope me-1 text-muted small"></i><?= htmlspecialchars($m['email']) ?></div>
                                    <div class="small text-muted"><i class="fa-solid fa-phone me-1 text-muted small"></i><?= htmlspecialchars($m['phone'] ?? 'N/A') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($m['active_plan'] ?? 'No Plan') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                        <?= (int)$m['workout_count'] ?> routine(s)
                                    </span>
                                </td>
                                <td><?= get_status_badge($m['status']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/trainer/member_view.php?id=<?= $m['member_id'] ?>" class="btn btn-outline-secondary" title="View Trainee Profile">
                                            <i class="fa-regular fa-eye me-1"></i> View Details
                                        </a>
                                        <a href="<?= BASE_URL ?>/trainer/workout_add.php?member_id=<?= $m['member_id'] ?>" class="btn btn-outline-primary" title="Prescribe Workout Routine">
                                            <i class="fa-solid fa-plus me-1"></i> Add Workout
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-users-slash display-6 d-block mb-2 opacity-50"></i>
                                No trainees currently assigned to your account.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
