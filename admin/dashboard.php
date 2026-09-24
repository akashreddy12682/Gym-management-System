<?php
/**
 * Admin Dashboard
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

// Restrict access strictly to Admin
require_role('admin');

$pdo = get_db_connection();
$stats = get_admin_dashboard_stats($pdo);

// 1. Fetch Recent Members (Last 5)
$recentMembersStmt = $pdo->query("
    SELECT m.id AS member_id, u.name, u.email, u.phone, m.join_date, m.status,
           tu.name AS trainer_name
    FROM members m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN trainers t ON m.trainer_id = t.id
    LEFT JOIN users tu ON t.user_id = tu.id
    ORDER BY m.id DESC
    LIMIT 5
");
$recentMembers = $recentMembersStmt->fetchAll();

// 2. Fetch Recent Payments (Last 5)
$recentPaymentsStmt = $pdo->query("
    SELECT p.*, u.name AS member_name, mp.plan_name
    FROM payments p
    JOIN members m ON p.member_id = m.id
    JOIN users u ON m.user_id = u.id
    LEFT JOIN memberships ms ON p.membership_id = ms.id
    LEFT JOIN membership_plans mp ON ms.plan_id = mp.id
    ORDER BY p.id DESC
    LIMIT 5
");
$recentPayments = $recentPaymentsStmt->fetchAll();

// 3. Fetch Today's Attendance
$todayAttendanceStmt = $pdo->query("
    SELECT a.*, u.name AS member_name
    FROM attendance a
    JOIN members m ON a.member_id = m.id
    JOIN users u ON m.user_id = u.id
    WHERE a.attendance_date = CURDATE()
    ORDER BY a.check_in_time DESC
    LIMIT 5
");
$todayAttendance = $todayAttendanceStmt->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<!-- Quick Welcome & Action Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Gym Overview</h3>
        <p class="text-muted small mb-0">Welcome back, Administrator. Here is today's summary of gym operations.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/admin/member_add.php" class="btn btn-primary btn-sm px-3">
            <i class="fa-solid fa-user-plus me-1"></i> Add Member
        </a>
        <a href="<?= BASE_URL ?>/admin/membership_assign.php" class="btn btn-outline-dark btn-sm px-3">
            <i class="fa-solid fa-id-card me-1"></i> Assign Plan
        </a>
        <a href="<?= BASE_URL ?>/admin/attendance.php" class="btn btn-outline-dark btn-sm px-3">
            <i class="fa-solid fa-calendar-check me-1"></i> Mark Attendance
        </a>
        <a href="<?= BASE_URL ?>/admin/payment_add.php" class="btn btn-outline-dark btn-sm px-3">
            <i class="fa-solid fa-money-bill-wave me-1"></i> Record Payment
        </a>
    </div>
</div>

<!-- 6 KPI Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Total Members -->
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Total Members</div>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($stats['total_members']) ?></h3>
                <small class="text-muted">Registered in database</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-primary">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>

    <!-- Active Members -->
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Active Members</div>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($stats['active_members']) ?></h3>
                <small class="text-success fw-medium"><i class="fa-solid fa-circle-check me-1"></i>Good standing</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-success">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>
    </div>

    <!-- Total Trainers -->
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Total Trainers</div>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($stats['total_trainers']) ?></h3>
                <small class="text-muted">Active coaching staff</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-purple">
                <i class="fa-solid fa-user-ninja"></i>
            </div>
        </div>
    </div>

    <!-- Active Memberships -->
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Active Memberships</div>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($stats['active_memberships']) ?></h3>
                <small class="text-muted">Non-expired subscriptions</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-info">
                <i class="fa-solid fa-id-badge"></i>
            </div>
        </div>
    </div>

    <!-- Today's Attendance -->
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Today's Attendance</div>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($stats['today_attendance']) ?></h3>
                <small class="text-muted"><?= date('l, d M') ?></small>
            </div>
            <div class="stat-icon-wrapper stat-icon-warning">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <!-- Total Payments -->
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-medium text-uppercase">Total Payments</div>
                <h3 class="fw-bold mb-0 mt-1"><?= format_currency($stats['total_payments']) ?></h3>
                <small class="text-muted">Total collected revenue</small>
            </div>
            <div class="stat-icon-wrapper stat-icon-danger">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Dashboard Content Grid -->
<div class="row g-4">
    <!-- Recent Members -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-users me-2 text-primary"></i>Recent Members</h6>
                <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-sm btn-light border text-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Member</th>
                                <th>Trainer</th>
                                <th>Status</th>
                                <th>Join Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentMembers)): ?>
                                <?php foreach ($recentMembers as $rm): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($rm['name']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($rm['email']) ?></div>
                                        </td>
                                        <td class="small">
                                            <?= htmlspecialchars($rm['trainer_name'] ?? 'Unassigned') ?>
                                        </td>
                                        <td><?= get_status_badge($rm['status']) ?></td>
                                        <td class="small text-muted"><?= format_date($rm['join_date']) ?></td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>/admin/member_view.php?id=<?= $rm['member_id'] ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="View Profile">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No members recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-credit-card me-2 text-success"></i>Recent Payments</h6>
                <a href="<?= BASE_URL ?>/admin/payments.php" class="btn btn-sm btn-light border text-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Member</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th class="text-end">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentPayments)): ?>
                                <?php foreach ($recentPayments as $rp): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($rp['member_name']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($rp['plan_name'] ?? 'Gym Fee') ?></div>
                                        </td>
                                        <td class="fw-bold text-success">
                                            <?= format_currency($rp['amount']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($rp['payment_method']) ?></span>
                                        </td>
                                        <td class="small text-muted"><?= format_date($rp['payment_date']) ?></td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>/admin/payment_receipt.php?id=<?= $rp['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" title="Print Receipt">
                                                <i class="fa-solid fa-receipt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No payments recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Attendance Activity -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-calendar-day me-2 text-warning"></i>Today's Attendance Activity</h6>
                <a href="<?= BASE_URL ?>/admin/attendance.php" class="btn btn-sm btn-light border text-secondary">Manage Attendance</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Member Name</th>
                                <th>Check-in Time</th>
                                <th>Check-out Time</th>
                                <th>Status</th>
                                <th>Notes / Workout</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($todayAttendance)): ?>
                                <?php foreach ($todayAttendance as $ta): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($ta['member_name']) ?></td>
                                        <td class="text-muted"><i class="fa-regular fa-clock me-1 text-success"></i><?= format_time($ta['check_in_time']) ?></td>
                                        <td class="text-muted">
                                            <?= $ta['check_out_time'] ? '<i class="fa-regular fa-clock me-1 text-danger"></i>' . format_time($ta['check_out_time']) : '<span class="text-warning small fst-italic">Currently on floor</span>' ?>
                                        </td>
                                        <td><?= get_status_badge($ta['status']) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($ta['notes'] ?? 'Routine session') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No check-in activity recorded for today yet.</td>
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
