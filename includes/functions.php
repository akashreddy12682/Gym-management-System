<?php
/**
 * Common Helper Functions
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Sanitize user inputs for safe display
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

// Format date into human-readable format (e.g. 15 Mar 2026)
function format_date($dateStr, $format = 'd M Y') {
    if (empty($dateStr) || $dateStr === '0000-00-00') {
        return 'N/A';
    }
    $timestamp = strtotime($dateStr);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

// Format time into human-readable format (e.g. 08:30 AM)
function format_time($timeStr, $format = 'h:i A') {
    if (empty($timeStr)) {
        return 'N/A';
    }
    $timestamp = strtotime($timeStr);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

// Format currency
function format_currency($amount) {
  return '₹' . number_format($amount, 2);;
}

// Return Bootstrap badge HTML based on status string
function get_status_badge($status) {
    $statusLower = strtolower(trim((string)$status));
    $badgeClass = 'bg-secondary';

    switch ($statusLower) {
        case 'active':
        case 'paid':
        case 'present':
            $badgeClass = 'bg-success';
            break;
        case 'expired':
        case 'inactive':
        case 'failed':
        case 'absent':
        case 'cancelled':
            $badgeClass = 'bg-danger';
            break;
        case 'pending':
        case 'late':
            $badgeClass = 'bg-warning text-dark';
            break;
        default:
            $badgeClass = 'bg-info text-dark';
            break;
    }

    return '<span class="badge ' . $badgeClass . ' text-uppercase px-2 py-1">' . htmlspecialchars(ucfirst($status)) . '</span>';
}

// Automatically sync and expire memberships that have passed end_date
function sync_membership_statuses($pdo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE memberships 
            SET status = 'expired' 
            WHERE status = 'active' AND end_date < CURDATE()
        ");
        $stmt->execute();
    } catch (PDOException $e) {
        // Silently catch to avoid crashing page
    }
}

// Global stats getter for admin dashboard
function get_admin_dashboard_stats($pdo) {
    sync_membership_statuses($pdo);

    $stats = [
        'total_members' => 0,
        'active_members' => 0,
        'total_trainers' => 0,
        'active_memberships' => 0,
        'today_attendance' => 0,
        'total_payments' => 0.00
    ];

    // Total Members
    $stmt = $pdo->query("SELECT COUNT(*) FROM members");
    $stats['total_members'] = (int)$stmt->fetchColumn();

    // Active Members
    $stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE status = 'active'");
    $stats['active_members'] = (int)$stmt->fetchColumn();

    // Total Trainers
    $stmt = $pdo->query("SELECT COUNT(*) FROM trainers WHERE status = 'active'");
    $stats['total_trainers'] = (int)$stmt->fetchColumn();

    // Active Memberships
    $stmt = $pdo->query("SELECT COUNT(*) FROM memberships WHERE status = 'active'");
    $stats['active_memberships'] = (int)$stmt->fetchColumn();

    // Today's Attendance
    $stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'Present'");
    $stats['today_attendance'] = (int)$stmt->fetchColumn();

    // Total Payments
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'Paid'");
    $stats['total_payments'] = (float)$stmt->fetchColumn();

    return $stats;
}
