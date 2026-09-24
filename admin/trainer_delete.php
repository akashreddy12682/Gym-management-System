<?php
/**
 * Delete Trainer Handler
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$trainerId = (int)($_GET['id'] ?? 0);

if ($trainerId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, u.name FROM trainers t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        $stmt->execute([$trainerId]);
        $trainer = $stmt->fetch();

        if ($trainer) {
            // Delete user cascades to trainers and sets members.trainer_id to NULL
            $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$trainer['user_id']]);

            set_flash('success', "Trainer '{$trainer['name']}' deleted successfully.");
        } else {
            set_flash('warning', "Trainer not found.");
        }
    } catch (Exception $e) {
        set_flash('danger', "Failed to delete trainer: " . $e->getMessage());
    }
}

header("Location: " . BASE_URL . "/admin/trainers.php");
exit();
