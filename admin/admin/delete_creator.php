<?php
session_start();
require_once '../src/db.php';

// Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Get creator ID
if (!isset($_GET['creator_id'])) {
    $_SESSION['error'] = "No creator ID provided.";
    header('Location: admin_dashboard.php');
    exit();
}

$creator_id = (int)$_GET['creator_id'];

// Verify the user is actually a creator
try {
    $check_stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'creator' LIMIT 1");
    if (!$check_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $check_stmt->bind_param('i', $creator_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $creator = $result->fetch_assoc();

    if (!$creator) {
        $_SESSION['error'] = "Creator not found or invalid role.";
        header('Location: admin_dashboard.php');
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error verifying creator: " . $e->getMessage();
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
    
    // Get all tournaments created by this creator
    $tournaments_stmt = $conn->prepare("SELECT id FROM tournaments WHERE creator_id = ?");
    if (!$tournaments_stmt) {
        throw new Exception("Prepare failed for tournaments query: " . $conn->error);
    }
    $tournaments_stmt->bind_param('i', $creator_id);
    $tournaments_stmt->execute();
    $tournaments_result = $tournaments_stmt->get_result();
    $tournament_ids = [];
    while ($row = $tournaments_result->fetch_assoc()) {
        $tournament_ids[] = (int)$row['id'];
    }
    $tournaments_stmt->close();
    
    // Delete data related to creator's tournaments
    if (!empty($tournament_ids)) {
        $tournament_tables = [
            'registrations' => 'tournament_id',
            'team_registrations' => 'tournament_id',
            'tournament_wallets' => 'tournament_id',
            'payment_transactions' => 'tournament_id',
            'payments' => 'tournament_id'
        ];
        
        foreach ($tournament_ids as $tid) {
            foreach ($tournament_tables as $table => $column) {
                $stmt = $conn->prepare("DELETE FROM `$table` WHERE `$column` = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed for table $table: " . $conn->error);
                }
                $stmt->bind_param('i', $tid);
                if (!$stmt->execute()) {
                    throw new Exception("Delete failed for table $table: " . $stmt->error);
                }
                $stmt->close();
            }
        }
    }
    
    // Delete all tournaments created by this creator
    $stmt_tournaments = $conn->prepare("DELETE FROM `tournaments` WHERE `creator_id` = ?");
    if (!$stmt_tournaments) {
        throw new Exception("Prepare failed for tournaments deletion: " . $conn->error);
    }
    $stmt_tournaments->bind_param('i', $creator_id);
    if (!$stmt_tournaments->execute()) {
        throw new Exception("Delete failed for tournaments: " . $stmt_tournaments->error);
    }
    $stmt_tournaments->close();
    
    // Delete creator's user-related data
    $creator_tables = [
        'notification_reads',
        'notifications',
        'withdrawals',
        'payment_transactions',
        'payments',
        'wallet_transactions',
        'creators'
    ];
    
    foreach ($creator_tables as $table) {
        $stmt = $conn->prepare("DELETE FROM `$table` WHERE `user_id` = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed for table $table: " . $conn->error);
        }
        $stmt->bind_param('i', $creator_id);
        if (!$stmt->execute()) {
            throw new Exception("Delete failed for table $table: " . $stmt->error);
        }
        $stmt->close();
    }
    
    // Finally, delete the user account
    $stmt_user = $conn->prepare("DELETE FROM `users` WHERE `id` = ? AND `role` = 'creator'");
    if (!$stmt_user) {
        throw new Exception("Prepare failed for users table: " . $conn->error);
    }
    $stmt_user->bind_param('i', $creator_id);
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
        $_SESSION['success'] = "Creator '" . htmlspecialchars($creator['name']) . "' and all related data (including " . count($tournament_ids) . " tournaments) have been permanently deleted.";
    } else {
        $_SESSION['error'] = "Creator could not be deleted. No rows affected.";
    }
    
    header('Location: admin_dashboard.php');
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    if ($conn->inTransaction ?? false) {
        $conn->rollback();
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 1"); // Re-enable FK checks even on error
    
    $_SESSION['error'] = "Error deleting creator: " . $e->getMessage();
    header('Location: view_creator_profile.php?creator_id=' . $creator_id);
    exit();
}
?>
