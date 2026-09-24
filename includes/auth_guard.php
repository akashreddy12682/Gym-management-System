<?php
/**
 * Authentication & Access Control Guards
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'name'     => $_SESSION['user_name'] ?? 'User',
        'email'    => $_SESSION['user_email'] ?? '',
        'role'     => $_SESSION['user_role'] ?? 'member',
        'profile_id' => $_SESSION['profile_id'] ?? null // member_id or trainer_id
    ];
}

function require_login() {
    if (!is_logged_in()) {
        set_flash('danger', 'Please log in to access this page.');
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

function require_role($allowedRoles) {
    require_login();

    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    $currentUser = current_user();
    if (!in_array($currentUser['role'], $allowedRoles)) {
        set_flash('danger', 'Access denied. You do not have permission to access that section.');
        // Redirect to their respective dashboard
        switch ($currentUser['role']) {
            case 'admin':
                header("Location: " . BASE_URL . "/admin/dashboard.php");
                break;
            case 'trainer':
                header("Location: " . BASE_URL . "/trainer/dashboard.php");
                break;
            case 'member':
            default:
                header("Location: " . BASE_URL . "/member/dashboard.php");
                break;
        }
        exit();
    }
}
