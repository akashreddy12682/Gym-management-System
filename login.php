<?php
/**
 * Unified Login Page
 * Gym Management System
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_guard.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'member';
    if ($role === 'admin') header("Location: " . BASE_URL . "/admin/dashboard.php");
    elseif ($role === 'trainer') header("Location: " . BASE_URL . "/trainer/dashboard.php");
    else header("Location: " . BASE_URL . "/member/dashboard.php");
    exit();
}

$error = '';
$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email address and password.';
    } else {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
            if ($user['status'] !== 'active') {
                $error = 'Your account has been deactivated. Please contact gym administration.';
            } else {
                // Transparently upgrade plain-text password to secure bcrypt hash
                if ($password === $user['password'] || password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upStmt->execute([$newHash, $user['id']]);
                }

                // Retrieve role-specific profile ID (member ID or trainer ID)
                $profileId = null;
                if ($user['role'] === 'member') {
                    $memStmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
                    $memStmt->execute([$user['id']]);
                    $profileId = $memStmt->fetchColumn();
                } elseif ($user['role'] === 'trainer') {
                    $trStmt = $pdo->prepare("SELECT id FROM trainers WHERE user_id = ?");
                    $trStmt->execute([$user['id']]);
                    $profileId = $trStmt->fetchColumn();
                }

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['profile_id'] = $profileId;

                set_flash('success', 'Welcome back, ' . $user['name'] . '!');

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: " . BASE_URL . "/admin/dashboard.php");
                } elseif ($user['role'] === 'trainer') {
                    header("Location: " . BASE_URL . "/trainer/dashboard.php");
                } else {
                    header("Location: " . BASE_URL . "/member/dashboard.php");
                }
                exit();
            }
        } else {
            $error = 'Invalid email address or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .login-card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            width: 100%;
            max-width: 460px;
        }
        .demo-pill {
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.78rem;
        }
        .demo-pill:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="p-4 p-sm-5">
        <!-- Logo & Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-dark text-warning rounded-circle mb-3" style="width: 58px; height: 58px; font-size: 1.6rem;">
                <i class="fa-solid fa-dumbbell"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1"><?= APP_NAME ?></h4>
            <p class="text-muted small">Sign in to your Gym Management Portal</p>
        </div>

        <!-- Flash & Error Alerts -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show small py-2">
                <?= htmlspecialchars($flash['text']) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small py-2">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php" id="loginForm">
            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-regular fa-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control border-start-0" placeholder="name@domain.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control border-start-0" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mt-2 shadow-sm">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
            </button>
        </form>

        <!-- Viva & College Presentation Quick-Fill Helpers -->
        <div class="mt-4 pt-3 border-top text-center">
            <span class="text-muted small fw-semibold d-block mb-2">College Viva 1-Click Demo Login:</span>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-dark btn-sm demo-pill" onclick="quickFill('admin@gym.com', 'admin123')">
                    <i class="fa-solid fa-user-shield me-1 text-primary"></i>Admin
                </button>
                <button type="button" class="btn btn-outline-dark btn-sm demo-pill" onclick="quickFill('alex@gym.com', 'trainer123')">
                    <i class="fa-solid fa-user-ninja me-1 text-success"></i>Trainer
                </button>
                <button type="button" class="btn btn-outline-dark btn-sm demo-pill" onclick="quickFill('john@gmail.com', 'member123')">
                    <i class="fa-solid fa-user me-1 text-warning"></i>Member
                </button>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="<?= BASE_URL ?>/" class="text-decoration-none small text-muted">
                <i class="fa-solid fa-arrow-left me-1"></i>Back to Home
            </a>
        </div>
    </div>
</div>

<script>
function quickFill(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
