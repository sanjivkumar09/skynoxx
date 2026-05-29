<?php
session_start();
require_once '../src/db.php';

// Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Get player ID
if (!isset($_GET['player_id'])) {
    $_SESSION['error'] = "No player ID provided.";
    header('Location: admin_dashboard.php');
    exit();
}

$player_id = (int)$_GET['player_id'];

// Verify the user is actually a player
try {
    $check_stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'player' LIMIT 1");
    if (!$check_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $check_stmt->bind_param('i', $player_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $player = $result->fetch_assoc();

    if (!$player) {
        $_SESSION['error'] = "Player not found or invalid role.";
        header('Location: admin_dashboard.php');
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error verifying player: " . $e->getMessage();
    header('Location: admin_dashboard.php');
    exit();
}

// Begin transaction
try {
    $conn->begin_transaction();
    
    // Disable foreign key checks temporarily
    if (!$conn->query("SET FOREIGN_KEY_CHECKS = 0")) {
        throw new Exception("Failed to disable foreign key checks: " . $conn->error);
    }
    
    // Delete in order of dependencies
    $tables_to_delete = [
        'notification_reads',
        'notifications', 
        'registration_team_members',
        'team_registrations' => 'team_leader_id',
        'registrations',
        'withdrawals',
        'payment_transactions',
        'payments',
        'wallet_transactions',
        'players_profile'
    ];
    
    foreach ($tables_to_delete as $key => $table) {
        $column = is_string($key) ? $table : 'user_id';
        $table_name = is_string($key) ? $key : $table;
        
        $stmt = $conn->prepare("DELETE FROM `$table_name` WHERE `$column` = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed for table $table_name: " . $conn->error);
        }
        $stmt->bind_param('i', $player_id);
        if (!$stmt->execute()) {
            throw new Exception("Delete failed for table $table_name: " . $stmt->error);
        }
        $stmt->close();
    }
    
    // Finally, delete the user account
    $stmt_user = $conn->prepare("DELETE FROM `users` WHERE `id` = ? AND `role` = 'player'");
    if (!$stmt_user) {
        throw new Exception("Prepare failed for users table: " . $conn->error);
    }
    $stmt_user->bind_param('i', $player_id);
    if (!$stmt_user->execute()) {
        throw new Exception("Delete failed for users table: " . $stmt_user->error);
    }
    
    $deleted_rows = $stmt_user->affected_rows;
    $stmt_user->close();
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Commit transaction
    $conn->commit();
    
    if ($deleted_rows > 0) {
        $_SESSION['success'] = "Player '" . htmlspecialchars($player['name']) . "' and all related data have been permanently deleted.";
    } else {
        $_SESSION['error'] = "Player could not be deleted. No rows affected.";
    }
    
    header('Location: admin_dashboard.php');
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    if ($conn->inTransaction ?? false) {
        $conn->rollback();
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 1"); // Re-enable FK checks even on error
    
    $_SESSION['error'] = "Error deleting player: " . $e->getMessage();
    header('Location: view_player_profile.php?player_id=' . $player_id);
    exit();
}
?>
