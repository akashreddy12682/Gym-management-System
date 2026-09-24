<?php
/**
 * Attendance Management System
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$error = '';

// Handle Checkout Action
if (isset($_GET['action']) && $_GET['action'] === 'checkout') {
    $attId = (int)($_GET['id'] ?? 0);
    if ($attId > 0) {
        $coStmt = $pdo->prepare("UPDATE attendance SET check_out_time = CURTIME() WHERE id = ? AND check_out_time IS NULL");
        $coStmt->execute([$attId]);
        set_flash('success', 'Member check-out time logged successfully.');
        header("Location: " . BASE_URL . "/admin/attendance.php");
        exit();
    }
}

// Handle New Attendance Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_attendance'])) {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $attendance_date = !empty($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');
    $check_in_time = !empty($_POST['check_in_time']) ? $_POST['check_in_time'] : date('H:i:s');
    $check_out_time = !empty($_POST['check_out_time']) ? $_POST['check_out_time'] : null;
    $status = $_POST['status'] ?? 'Present';
    $notes = trim($_POST['notes'] ?? '');

    if ($member_id <= 0) {
        $error = 'Please select a member to mark attendance.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO attendance (member_id, attendance_date, check_in_time, check_out_time, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$member_id, $attendance_date, $check_in_time, $check_out_time, $status, $notes]);

            set_flash('success', 'Attendance record logged successfully!');
            header("Location: " . BASE_URL . "/admin/attendance.php?date=" . urlencode($attendance_date));
            exit();
        } catch (Exception $e) {
            $error = 'Failed to record attendance: ' . $e->getMessage();
        }
    }
}

// Filters
$selectedDate = $_GET['date'] ?? '';
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');

// Fetch active members for the quick attendance form
$activeMembersStmt = $pdo->query("
    SELECT m.id, u.name, u.email 
    FROM members m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.status = 'active' 
    ORDER BY u.name ASC
");
$activeMembers = $activeMembersStmt->fetchAll();

// Build Query for Attendance Records
$sql = "
    SELECT a.*, u.name AS member_name, u.email AS member_email, u.phone AS member_phone
    FROM attendance a
    JOIN members m ON a.member_id = m.id
    JOIN users u ON m.user_id = u.id
    WHERE 1=1
";

$params = [];

if (!empty($selectedDate)) {
    $sql .= " AND a.attendance_date = ?";
    $params[] = $selectedDate;
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($statusFilter !== 'all' && in_array($statusFilter, ['Present', 'Absent', 'Late'])) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY a.attendance_date DESC, a.check_in_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendanceLogs = $stmt->fetchAll();

$pageTitle = 'Attendance Management';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Attendance Tracking</h3>
        <p class="text-muted small mb-0">Record daily check-ins, departures, and inspect member attendance history.</p>
    </div>
    <div>
        <button class="btn btn-primary px-3" data-bs-toggle="modal" data-bs-target="#recordAttendanceModal">
            <i class="fa-solid fa-user-check me-1"></i> Log Member Attendance
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter & Search Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="attendance.php" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by member name or phone..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-regular fa-calendar"></i></span>
                    <input type="date" name="date" class="form-control border-start-0" value="<?= htmlspecialchars($selectedDate) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="Present" <?= $statusFilter === 'Present' ? 'selected' : '' ?>>Present</option>
                    <option value="Late" <?= $statusFilter === 'Late' ? 'selected' : '' ?>>Late</option>
                    <option value="Absent" <?= $statusFilter === 'Absent' ? 'selected' : '' ?>>Absent</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <?php if (!empty($selectedDate) || !empty($search) || $statusFilter !== 'all'): ?>
                    <a href="attendance.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Records Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>ID</th>
                        <th>Member Name</th>
                        <th>Date</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Duration / Floor Status</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($attendanceLogs)): ?>
                        <?php foreach ($attendanceLogs as $log): ?>
                            <tr>
                                <td class="fw-bold text-muted">#ATT-<?= str_pad($log['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/admin/member_view.php?id=<?= $log['member_id'] ?>" class="text-decoration-none fw-bold text-dark">
                                        <?= htmlspecialchars($log['member_name']) ?>
                                    </a>
                                    <div class="small text-muted"><?= htmlspecialchars($log['member_email']) ?></div>
                                </td>
                                <td class="fw-semibold text-dark"><?= format_date($log['attendance_date']) ?></td>
                                <td>
                                    <i class="fa-regular fa-clock me-1 text-success"></i> <?= format_time($log['check_in_time']) ?>
                                </td>
                                <td>
                                    <?php if ($log['check_out_time']): ?>
                                        <i class="fa-regular fa-clock me-1 text-danger"></i> <?= format_time($log['check_out_time']) ?>
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
                                        echo "<span class='small text-success fw-bold'><i class='fa-solid fa-circle me-1 small animate-pulse'></i> Active Now</span>";
                                    } else {
                                        echo "<span class='text-muted small'>--</span>";
                                    }
                                    ?>
                                </td>
                                <td><?= get_status_badge($log['status']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($log['notes'] ?? 'Routine session') ?></td>
                                <td class="text-end">
                                    <?php if (empty($log['check_out_time'])): ?>
                                        <a href="<?= BASE_URL ?>/admin/attendance.php?action=checkout&id=<?= $log['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" title="Mark Check-out Now">
                                            <i class="fa-solid fa-person-walking-arrow-right me-1"></i> Check-out
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="fa-solid fa-check text-success"></i> Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-regular fa-calendar-xmark display-6 d-block mb-2 opacity-50"></i>
                                No attendance records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Log Attendance -->
<div class="modal fade" id="recordAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-calendar-check me-2 text-primary"></i>Log Member Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="attendance.php">
                <input type="hidden" name="record_attendance" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Member <span class="text-danger">*</span></label>
                        <select name="member_id" class="form-select" required>
                            <option value="">-- Choose Member --</option>
                            <?php foreach ($activeMembers as $am): ?>
                                <option value="<?= $am['id'] ?>">
                                    <?= htmlspecialchars($am['name']) ?> (<?= htmlspecialchars($am['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Attendance Date <span class="text-danger">*</span></label>
                            <input type="date" name="attendance_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Status</label>
                            <select name="status" class="form-select">
                                <option value="Present" selected>Present</option>
                                <option value="Late">Late</option>
                                <option value="Absent">Absent</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Check-in Time <span class="text-danger">*</span></label>
                            <input type="time" name="check_in_time" class="form-control" required value="<?= date('H:i') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Check-out Time <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="time" name="check_out_time" class="form-control">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Notes / Session Details</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Cardio & Core, Chest Workout">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
