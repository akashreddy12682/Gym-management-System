<?php
/**
 * Basic Summary Reports
 * Gym Management System - Academic Project
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
sync_membership_statuses($pdo);

// 1. Revenue by Payment Method
$methodStmt = $pdo->query("
    SELECT payment_method, COUNT(*) AS txn_count, SUM(amount) AS total_amount 
    FROM payments 
    WHERE payment_status = 'Paid' 
    GROUP BY payment_method
    ORDER BY total_amount DESC
");
$methodReport = $methodStmt->fetchAll();

// 2. Memberships Distribution by Plan
$planReportStmt = $pdo->query("
    SELECT mp.plan_name, mp.price, COUNT(ms.id) AS total_sold,
           SUM(CASE WHEN ms.status = 'active' THEN 1 ELSE 0 END) AS active_now,
           COALESCE(SUM(p.amount), 0) AS revenue_collected
    FROM membership_plans mp
    LEFT JOIN memberships ms ON mp.id = ms.plan_id
    LEFT JOIN payments p ON ms.id = p.membership_id AND p.payment_status = 'Paid'
    GROUP BY mp.id
    ORDER BY active_now DESC
");
$planReports = $planReportStmt->fetchAll();

// 3. Trainer Client Distribution
$trainerDistStmt = $pdo->query("
    SELECT u.name AS trainer_name, t.specialization,
           COUNT(m.id) AS total_clients,
           SUM(CASE WHEN m.status = 'active' THEN 1 ELSE 0 END) AS active_clients
    FROM trainers t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN members m ON t.id = m.trainer_id
    GROUP BY t.id
    ORDER BY active_clients DESC
");
$trainerReports = $trainerDistStmt->fetchAll();

// 4. Monthly Attendance Summary (Last 30 Days)
$attSumStmt = $pdo->query("
    SELECT status, COUNT(*) AS total_count 
    FROM attendance 
    WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
    GROUP BY status
");
$attSummaries = $attSumStmt->fetchAll();

$pageTitle = 'Summary Reports';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Gym Operational & Financial Reports</h3>
        <p class="text-muted small mb-0">High-level analytics on revenue, subscription distributions, and coach allocations.</p>
    </div>
    <div>
        <button onclick="window.print();" class="btn btn-outline-dark px-3 no-print">
            <i class="fa-solid fa-print me-1"></i> Print / Export Report
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- 1. Revenue by Payment Method -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-wallet me-2 text-success"></i>Revenue by Payment Method</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Payment Method</th>
                                <th>Transactions</th>
                                <th class="text-end">Total Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grandTotalRevenue = 0;
                            $grandTotalTxns = 0;
                            foreach ($methodReport as $mr): 
                                $grandTotalRevenue += (float)$mr['total_amount'];
                                $grandTotalTxns += (int)$mr['txn_count'];
                            ?>
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <i class="fa-solid fa-circle-dot me-2 text-primary small"></i>
                                        <?= htmlspecialchars($mr['payment_method']) ?>
                                    </td>
                                    <td><?= (int)$mr['txn_count'] ?> payments</td>
                                    <td class="text-end fw-bold text-success fs-6"><?= format_currency($mr['total_amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th class="fw-bold">Total</th>
                                <th class="fw-bold"><?= $grandTotalTxns ?> txns</th>
                                <th class="text-end fw-bold text-success fs-5"><?= format_currency($grandTotalRevenue) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Attendance Summary (Last 30 Days) -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-pie me-2 text-warning"></i>Attendance Breakdown (Past 30 Days)</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 text-center">
                    <?php 
                    $totalAttendances = 0;
                    foreach ($attSummaries as $as) {
                        $totalAttendances += (int)$as['total_count'];
                    }
                    ?>
                    <?php foreach ($attSummaries as $as): ?>
                        <?php 
                        $pct = $totalAttendances > 0 ? round(((int)$as['total_count'] / $totalAttendances) * 100) : 0;
                        ?>
                        <div class="col-4">
                            <div class="p-3 border rounded-3 bg-light">
                                <h4 class="fw-bold mb-0 text-dark"><?= (int)$as['total_count'] ?></h4>
                                <div class="small fw-semibold text-uppercase text-muted"><?= htmlspecialchars($as['status']) ?></div>
                                <span class="badge bg-secondary mt-1"><?= $pct ?>% of logs</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4 pt-3 border-top text-muted small">
                    <i class="fa-solid fa-circle-info me-1 text-primary"></i> Total attendance check-ins recorded in past 30 days: <strong><?= $totalAttendances ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Plan Distribution Report -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-tags me-2 text-primary"></i>Membership Packages Performance</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Plan Name</th>
                                <th>Plan Price</th>
                                <th>Total Subscriptions Sold</th>
                                <th>Currently Active</th>
                                <th class="text-end">Revenue Linked</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($planReports as $pr): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($pr['plan_name']) ?></td>
                                    <td><?= format_currency($pr['price']) ?></td>
                                    <td><?= (int)$pr['total_sold'] ?> subscribers</td>
                                    <td>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">
                                            <?= (int)$pr['active_now'] ?> active
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-success"><?= format_currency($pr['revenue_collected']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Trainer Client Allocations -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-ninja me-2 text-warning"></i>Trainer Client Allocations</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Trainer Name</th>
                                <th>Specialization</th>
                                <th>Total Clients Assigned</th>
                                <th>Active Clients</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trainerReports as $tr): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($tr['trainer_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($tr['specialization']) ?></span></td>
                                    <td><?= (int)$tr['total_clients'] ?> members</td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1">
                                            <?= (int)$tr['active_clients'] ?> active
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
