<?php
/**
 * Member - Attendance History
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('member');

$pdo = get_db_connection();
$user = current_user();

$mStmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
$mStmt->execute([$user['id']]);
$memberId = (int)$mStmt->fetchColumn();

// Fetch member attendance history
$stmt = $pdo->prepare("
    SELECT * FROM attendance 
    WHERE member_id = ? 
    ORDER BY attendance_date DESC, check_in_time DESC
");
$stmt->execute([$memberId]);
$attendanceLogs = $stmt->fetchAll();

// Calculate Monthly Statistics
$totalDays = count($attendanceLogs);
$presentDays = 0;
$lateDays = 0;

foreach ($attendanceLogs as $log) {
    if ($log['status'] === 'Present') $presentDays++;
    elseif ($log['status'] === 'Late') $lateDays++;
}

$pageTitle = 'My Attendance Log';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h3 class="fw-bold mb-1">My Gym Attendance</h3>
    <p class="text-muted small">Track your daily workout consistency and gym check-ins.</p>
</div>

<!-- Stats Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Total Check-ins</div>
                <h3 class="fw-bold mb-0 mt-1"><?= $totalDays ?> Days</h3>
                <small class="text-muted">All-time gym visits</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-primary">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">On-Time Sessions</div>
                <h3 class="fw-bold mb-0 mt-1"><?= $presentDays ?> Days</h3>
                <small class="text-success"><i class="fa-solid fa-circle-check me-1"></i>Present status</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-success">
                <i class="fa-solid fa-check"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Late Check-ins</div>
                <h3 class="fw-bold mb-0 mt-1"><?= $lateDays ?> Days</h3>
                <small class="text-muted">Recorded after slot</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-warning">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Records Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-check me-2 text-primary"></i>Check-in Log History</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Session Duration</th>
                        <th>Status</th>
                        <th>Session Focus / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($attendanceLogs)): ?>
                        <?php foreach ($attendanceLogs as $log): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= format_date($log['attendance_date']) ?></td>
                                <td class="text-muted small"><?= date('l', strtotime($log['attendance_date'])) ?></td>
                                <td><i class="fa-regular fa-clock me-1 text-success"></i><?= format_time($log['check_in_time']) ?></td>
                                <td>
                                    <?php if ($log['check_out_time']): ?>
                                        <i class="fa-regular fa-clock me-1 text-danger"></i><?= format_time($log['check_out_time']) ?>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning">On Floor</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($log['check_in_time'] && $log['check_out_time']) {
                                        $t1 = strtotime($log['check_in_time']);
                                        $t2 = strtotime($log['check_out_time']);
                                        $mins = round(($t2 - $t1) / 60);
                                        $hrs = floor($mins / 60);
                                        $remMins = $mins % 60;
                                        echo "<span class='small text-muted'>{$hrs}h {$remMins}m</span>";
                                    } elseif (!$log['check_out_time']) {
                                        echo "<span class='small text-success fw-bold'>Active Session</span>";
                                    } else {
                                        echo "<span class='text-muted small'>--</span>";
                                    }
                                    ?>
                                </td>
                                <td><?= get_status_badge($log['status']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($log['notes'] ?? 'Routine workout') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No attendance activity recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
