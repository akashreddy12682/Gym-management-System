<?php
/**
 * Role-Based Dynamic Sidebar Navigation
 * Gym Management System
 */

$user = current_user();
$role = $user['role'] ?? 'member';
$currentScript = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <a href="<?= BASE_URL ?>" class="sidebar-brand">
        <i class="fa-solid fa-dumbbell text-warning"></i>
        <span><span class="highlight">Iron</span>Pulse</span>
    </a>

    <ul class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <!-- ADMIN NAVIGATION -->
            <li class="nav-header">Overview</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link <?= ($currentScript === 'dashboard.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-header">Management</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/members.php" class="nav-link <?= in_array($currentScript, ['members.php', 'member_add.php', 'member_edit.php', 'member_view.php']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i>
                    <span>Members</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/trainers.php" class="nav-link <?= in_array($currentScript, ['trainers.php', 'trainer_add.php', 'trainer_edit.php', 'trainer_view.php']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-ninja"></i>
                    <span>Trainers</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/plans.php" class="nav-link <?= in_array($currentScript, ['plans.php', 'plan_add.php', 'plan_edit.php']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-tags"></i>
                    <span>Membership Plans</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/memberships.php" class="nav-link <?= in_array($currentScript, ['memberships.php', 'membership_assign.php']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Subscriptions</span>
                </a>
            </li>

            <li class="nav-header">Operations</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/attendance.php" class="nav-link <?= ($currentScript === 'attendance.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/payments.php" class="nav-link <?= in_array($currentScript, ['payments.php', 'payment_add.php', 'payment_receipt.php']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Payments & Billing</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/workouts.php" class="nav-link <?= ($currentScript === 'workouts.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-heart-pulse"></i>
                    <span>Workout Schedules</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/reports.php" class="nav-link <?= ($currentScript === 'reports.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Summary Reports</span>
                </a>
            </li>

        <?php elseif ($role === 'trainer'): ?>
            <!-- TRAINER NAVIGATION -->
            <li class="nav-header">Trainer Portal</li>
            <li>
                <a href="<?= BASE_URL ?>/trainer/dashboard.php" class="nav-link <?= ($currentScript === 'dashboard.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/trainer/members.php" class="nav-link <?= in_array($currentScript, ['members.php', 'member_view.php']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-group"></i>
                    <span>Assigned Members</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/trainer/workouts.php" class="nav-link <?= in_array($currentScript, ['workouts.php', 'workout_add.php', 'workout_edit.php']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-dumbbell"></i>
                    <span>Workout & Diet Plans</span>
                </a>
            </li>

        <?php else: ?>
            <!-- MEMBER NAVIGATION -->
            <li class="nav-header">Member Portal</li>
            <li>
                <a href="<?= BASE_URL ?>/member/dashboard.php" class="nav-link <?= ($currentScript === 'dashboard.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-gauge"></i>
                    <span>My Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/member/profile.php" class="nav-link <?= ($currentScript === 'profile.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/member/membership.php" class="nav-link <?= ($currentScript === 'membership.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-id-card"></i>
                    <span>My Membership</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/member/attendance.php" class="nav-link <?= ($currentScript === 'attendance.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Attendance Log</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/member/payments.php" class="nav-link <?= ($currentScript === 'payments.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Payment History</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/member/workouts.php" class="nav-link <?= ($currentScript === 'workouts.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-fire-flame-curved"></i>
                    <span>Workout & Diet Routine</span>
                </a>
            </li>
        <?php endif; ?>

        <li class="nav-header mt-3">Account</li>
        <li>
            <a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Log Out</span>
            </a>
        </li>
    </ul>
</aside>
