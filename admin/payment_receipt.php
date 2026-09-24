<?php
/**
 * Official Payment Receipt & Tax Invoice
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_login();
$currentUser = current_user();

$paymentId = (int)($_GET['id'] ?? 0);
$pdo = get_db_connection();

$stmt = $pdo->prepare("
    SELECT p.*, 
           m.id AS member_profile_id, m.gender, m.join_date, m.address,
           u.name AS member_name, u.email AS member_email, u.phone AS member_phone,
           mp.plan_name, mp.duration_days,
           ms.start_date, ms.end_date
    FROM payments p
    JOIN members m ON p.member_id = m.id
    JOIN users u ON m.user_id = u.id
    LEFT JOIN memberships ms ON p.membership_id = ms.id
    LEFT JOIN membership_plans mp ON ms.plan_id = mp.id
    WHERE p.id = ?
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    die("Error: Receipt not found.");
}

// Security: If logged in as a member, ensure this receipt belongs to them!
if ($currentUser['role'] === 'member' && (int)$payment['member_profile_id'] !== (int)$currentUser['profile_id']) {
    die("Error: Access denied. You can only view your own receipts.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #RCP-<?= str_pad($payment['id'], 5, '0', STR_PAD_LEFT) ?> - <?= APP_NAME ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            padding: 2rem 0;
        }
        .receipt-container {
            max-width: 780px;
            margin: 0 auto;
            background: #fff;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
        }
        .receipt-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .receipt-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.85rem;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border: none;
                padding: 1.5rem;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Action Bar (Hidden in Print) -->
    <div class="receipt-container p-3 mb-3 d-flex justify-content-between align-items-center no-print bg-light border">
        <a href="javascript:window.history.back();" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print();" class="btn btn-dark btn-sm">
                <i class="fa-solid fa-print me-1"></i> Print Receipt / Invoice
            </button>
        </div>
    </div>

    <!-- Printable Receipt Content -->
    <div class="receipt-container">
        <!-- Receipt Header -->
        <div class="receipt-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="fa-solid fa-dumbbell text-warning fs-3"></i>
                    <h3 class="fw-extrabold mb-0"><?= APP_NAME ?></h3>
                </div>
                <div class="text-muted small"><?= APP_TAGLINE ?></div>
                <div class="text-muted small">Academic College Project Demo &bull; CS&E</div>
            </div>

            <div class="text-end">
                <div class="badge bg-success bg-opacity-10 text-success border border-success receipt-badge fw-bold text-uppercase mb-2">
                    Payment <?= htmlspecialchars($payment['payment_status']) ?>
                </div>
                <h5 class="fw-bold mb-0">RECEIPT #RCP-<?= str_pad($payment['id'], 5, '0', STR_PAD_LEFT) ?></h5>
                <div class="text-muted small mt-1">Date: <?= format_date($payment['payment_date']) ?></div>
            </div>
        </div>

        <!-- Billed To & Gym Details Grid -->
        <div class="row g-4 mb-4 pb-3 border-bottom">
            <div class="col-sm-6">
                <div class="text-muted small text-uppercase fw-bold mb-2">Billed To (Member):</div>
                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($payment['member_name']) ?></h5>
                <div class="text-muted small"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($payment['member_email']) ?></div>
                <div class="text-muted small"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($payment['member_phone'] ?? 'N/A') ?></div>
                <div class="text-muted small mt-1">Member ID: #M<?= str_pad($payment['member_profile_id'], 4, '0', STR_PAD_LEFT) ?></div>
            </div>

            <div class="col-sm-6 text-sm-end">
                <div class="text-muted small text-uppercase fw-bold mb-2">Payment Details:</div>
                <div class="small mb-1"><strong>Method:</strong> <?= htmlspecialchars($payment['payment_method']) ?></div>
                <div class="small mb-1"><strong>Reference ID:</strong> <?= htmlspecialchars($payment['transaction_id'] ?? 'OFFLINE-DESK') ?></div>
                <div class="small"><strong>Generated:</strong> <?= format_date($payment['created_at'], 'd M Y, h:i A') ?></div>
            </div>
        </div>

        <!-- Itemized Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>#</th>
                        <th>Description & Subscription</th>
                        <th>Duration</th>
                        <th>Validity Period</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($payment['plan_name'] ?? 'Gym Membership Fee') ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($payment['notes'] ?? 'Standard gym membership payment') ?></div>
                        </td>
                        <td><?= $payment['duration_days'] ? (int)$payment['duration_days'] . ' Days' : 'N/A' ?></td>
                        <td class="small">
                            <?php if ($payment['start_date'] && $payment['end_date']): ?>
                                <?= format_date($payment['start_date']) ?> to <?= format_date($payment['end_date']) ?>
                            <?php else: ?>
                                Standard billing
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-bold fs-6">
                            <?= format_currency($payment['amount']) ?>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end text-uppercase">Total Amount Paid:</th>
                        <th class="text-end text-success fs-5"><?= format_currency($payment['amount']) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Terms & Signature -->
        <div class="row pt-4 mt-4 align-items-end">
            <div class="col-7">
                <h6 class="fw-bold small text-muted text-uppercase mb-1">Terms & Conditions:</h6>
                <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                    1. Membership fees are non-refundable once registered.<br>
                    2. Members must follow gym etiquette, wear proper sports attire, and check in daily.<br>
                    3. This is a computer-generated receipt issued for the college project demonstration.
                </p>
            </div>
            <div class="col-5 text-end">
                <div class="border-top border-dark pt-2 d-inline-block px-4">
                    <span class="small fw-bold">Authorized Signatory</span><br>
                    <span class="text-muted small">IronPulse Gym Admin</span>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
