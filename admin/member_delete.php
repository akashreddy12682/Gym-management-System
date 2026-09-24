<?php
/**
 * Delete Member Handler
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$memberId = (int)($_GET['id'] ?? 0);

if ($memberId > 0) {
    try {
        // Find user_id associated with member
        $stmt = $pdo->prepare("SELECT user_id, name FROM members m JOIN users u ON m.user_id = u.id WHERE m.id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();

        if ($member) {
            // Deleting from users table cascades to members, memberships, attendance, payments, and workouts
            $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $delStmt->execute([$member['user_id']]);

            set_flash('success', "Member '{$member['name']}' was successfully deleted.");
        } else {
            set_flash('warning', "Member record not found.");
        }
    } catch (Exception $e) {
        set_flash('danger', "Failed to delete member: " . $e->getMessage());
    }
}

header("Location: " . BASE_URL . "/admin/members.php");
exit();
