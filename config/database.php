<?php
/**
 * Database Configuration & Connection File
 * Gym Management System - Academic Project
 */

// Database Credentials
// For local XAMPP: default user is 'root' with empty password ''
// For AWS EC2: adjust credentials according to your EC2 MySQL setup
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'gym_management');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

function get_db_connection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Display an educational, user-friendly troubleshooting screen for college demo
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Database Connection Error - Gym Management System</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body class="bg-light d-flex align-items-center min-vh-100">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-7">
                            <div class="card shadow border-0 rounded-4">
                                <div class="card-body p-5">
                                    <div class="text-center mb-4">
                                        <div class="display-4 text-danger mb-2">⚠️</div>
                                        <h3 class="fw-bold">Database Connection Failed</h3>
                                        <p class="text-muted">The Gym Management System could not connect to MySQL.</p>
                                    </div>
                                    <div class="alert alert-secondary font-monospace small">
                                        <?= htmlspecialchars($e->getMessage()) ?>
                                    </div>
                                    <h6 class="fw-bold mt-4">Quick Setup Instructions:</h6>
                                    <ol class="small text-muted ps-3">
                                        <li>Make sure <strong>Apache</strong> and <strong>MySQL</strong> are running in your <strong>XAMPP Control Panel</strong>.</li>
                                        <li>Open <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a> in your browser.</li>
                                        <li>Create a database named <code>gym_management</code> or import the file located at:
                                            <br><code>gym-management-system/database/schema.sql</code></li>
                                        <li>If you configured a MySQL password, update <code>config/database.php</code>.</li>
                                    </ol>
                                    <div class="d-grid mt-4">
                                        <button onclick="window.location.reload();" class="btn btn-primary">Try Again</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit();
        }
    }

    return $pdo;
}
