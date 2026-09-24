<?php
/**
 * Delete Membership Plan Handler
 * Gym Management System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_role('admin');

$pdo = get_db_connection();
$planId = (int)($_GET['id'] ?? 0);

if ($planId > 0) {
    try {
        // Check if active memberships exist for this plan
        $chk = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE plan_id = ? AND status = 'active'");
        $chk->execute([$planId]);
        $activeCount = (int)$chk->fetchColumn();

        if ($activeCount > 0) {
            set_flash('danger', "Cannot delete this plan because {$activeCount} member(s) currently have active subscriptions to it. You can set the plan to 'Inactive' instead.");
        } else {
            $del = $pdo->prepare("DELETE FROM membership_plans WHERE id = ?");
            $del->execute([$planId]);
            set_flash('success', "Membership plan deleted successfully.");
        }
    } catch (Exception $e) {
        set_flash('danger', "Failed to delete plan: " . $e->getMessage());
    }
}

header("Location: " . BASE_URL . "/admin/plans.php");
exit();
