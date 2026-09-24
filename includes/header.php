<?php
/**
 * Global Header Component
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$currentUser = current_user();
$pageTitle = $pageTitle ?? 'Dashboard';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= APP_NAME ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom Gym Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-content">
        <!-- Top Navigation Bar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                    <i class="fa-solid fa-bars fs-5"></i>
                </button>
                <h5 class="mb-0 fw-semibold text-secondary d-none d-sm-block">
                    <?= htmlspecialchars($pageTitle) ?>
                </h5>
            </div>

            <!-- User Menu -->
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-md-block">
                    <div class="fw-bold small text-dark"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></div>
                    <span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.68rem;">
                        <?= htmlspecialchars($currentUser['role'] ?? 'guest') ?>
                    </span>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light rounded-circle p-0 border" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-user-tie text-secondary"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li>
                            <h6 class="dropdown-header"><?= htmlspecialchars($currentUser['email'] ?? '') ?></h6>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (($currentUser['role'] ?? '') === 'member'): ?>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/member/profile.php"><i class="fa-regular fa-id-badge me-2"></i>My Profile</a></li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                                <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Page Body -->
        <main class="page-body">
            <!-- Flash Message Alert -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> me-2"></i>
                    <?= htmlspecialchars($flash['text']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
