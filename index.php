<?php
/**
 * Public Landing Page
 * Gym Management System - Academic Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = get_db_connection();

// Fetch active membership plans for pricing section
$plans = [];
try {
    $stmt = $pdo->query("SELECT * FROM membership_plans WHERE status = 'active' ORDER BY price ASC");
    $plans = $stmt->fetchAll();
} catch (Exception $e) {
    $plans = [];
}

// Fetch trainers for showcase
$trainers = [];
try {
    $stmt = $pdo->query("
        SELECT t.*, u.name, u.email 
        FROM trainers t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.status = 'active' 
        LIMIT 3
    ");
    $trainers = $stmt->fetchAll();
} catch (Exception $e) {
    $trainers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - <?= APP_TAGLINE ?></title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.92)), url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1600&auto=format&fit=crop') center/cover;
            color: #fff;
            padding: 6rem 0;
        }
        .feature-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= BASE_URL ?>/">
            <i class="fa-solid fa-dumbbell text-warning fs-4"></i>
            <span><span class="text-warning">Iron</span>Pulse Gym</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="landingNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-3">
                <li class="nav-item"><a class="nav-link text-white" href="#about">About</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#plans">Membership Plans</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#trainers">Trainers</a></li>
                <li class="nav-item">
                    <a class="btn btn-warning text-dark fw-semibold px-4" href="<?= BASE_URL ?>/login.php">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Portal Login
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Banner -->
<header class="hero-section text-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <span class="badge bg-warning text-dark px-3 py-2 text-uppercase fw-bold mb-3">College Academic Project</span>
                <h1 class="display-4 fw-extrabold mb-3">Transform Your Body, Mind & Strength</h1>
                <p class="lead text-light opacity-75 mb-4">
                    Complete digital gym management solution designed for modern fitness facilities. Manage memberships, personal trainers, automated attendance tracking, workout plans, and instant billing.
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-warning btn-lg px-4 fw-bold">
                        <i class="fa-solid fa-gauge me-2"></i>Access System
                    </a>
                    <a href="#plans" class="btn btn-outline-light btn-lg px-4">
                        View Membership Plans
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Feature Highlights -->
<section class="py-5 bg-white" id="about">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-warning text-uppercase fw-bold">Why Choose IronPulse</h6>
            <h2 class="fw-bold">All-In-One Gym Management</h2>
            <p class="text-muted">Built from ground up with standard web technologies for seamless performance.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 text-center">
                    <div class="feature-icon-box bg-warning bg-opacity-25 text-warning mx-auto">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <h5 class="fw-bold">Role-Based Access</h5>
                    <p class="text-muted small">Tailored dashboards for Administrators, Personal Trainers, and Gym Members.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 text-center">
                    <div class="feature-icon-box bg-primary bg-opacity-10 text-primary mx-auto">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h5 class="fw-bold">Smart Attendance</h5>
                    <p class="text-muted small">Daily check-in and check-out recording with real-time analytics and history.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 text-center">
                    <div class="feature-icon-box bg-success bg-opacity-10 text-success mx-auto">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <h5 class="fw-bold">Payments & Invoicing</h5>
                    <p class="text-muted small">Record cash, card, and digital payments with printable tax invoices and receipts.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 text-center">
                    <div class="feature-icon-box bg-info bg-opacity-10 text-info mx-auto">
                        <i class="fa-solid fa-dumbbell"></i>
                    </div>
                    <h5 class="fw-bold">Workouts & Diet</h5>
                    <p class="text-muted small">Customized exercise routines and nutritional dietary guidelines assigned per member.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Membership Plans Section -->
<section class="py-5 bg-light" id="plans">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-warning text-uppercase fw-bold">Flexible Options</h6>
            <h2 class="fw-bold">Membership Plans</h2>
            <p class="text-muted">Choose the membership plan that best fits your training goals.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php if (!empty($plans)): ?>
                <?php foreach ($plans as $plan): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm text-center p-4">
                            <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($plan['plan_name']) ?></h5>
                            <div class="display-6 fw-bold text-warning my-3">
                                <?= format_currency($plan['price']) ?>
                            </div>
                            <span class="badge bg-light text-secondary border mx-auto mb-3">
                                <?= (int)$plan['duration_days'] ?> Days Access
                            </span>
                            <p class="text-muted small mb-4">
                                <?= htmlspecialchars($plan['description']) ?>
                            </p>
                            <div class="mt-auto">
                                <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-dark w-100">
                                    Get Started
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted">
                    No active membership plans available.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Trainers Section -->
<section class="py-5 bg-white" id="trainers">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-warning text-uppercase fw-bold">Meet The Coaches</h6>
            <h2 class="fw-bold">Certified Fitness Trainers</h2>
            <p class="text-muted">Guiding you towards peak physical condition and injury-free workouts.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php if (!empty($trainers)): ?>
                <?php foreach ($trainers as $trainer): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm p-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="bg-dark text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.3rem;">
                                    <i class="fa-solid fa-user-ninja"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($trainer['name']) ?></h5>
                                    <span class="badge bg-warning bg-opacity-25 text-dark fw-semibold small">
                                        <?= htmlspecialchars($trainer['specialization']) ?>
                                    </span>
                                </div>
                            </div>
                            <p class="text-muted small mb-0">
                                <?= htmlspecialchars($trainer['bio'] ?? 'Dedicated fitness professional committed to personal client growth.') ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted">
                    Trainer profiles are being updated.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-4 border-top">
    <div class="container text-center">
        <p class="mb-1 fw-bold"><?= APP_NAME ?> &bull; <?= APP_TAGLINE ?></p>
        <p class="text-muted small mb-0">Academic Project for 3rd Year B.E. Computer Science & Engineering | Tech Stack: HTML5, CSS3, Bootstrap 5, PHP 8, MySQL, Apache, AWS EC2</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
