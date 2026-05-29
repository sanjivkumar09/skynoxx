<?php
/**
 * Tournament Status API
 * Returns real-time tournament status, participant count, and metadata
 */

session_start();
require_once '../src/db.php';
require_once '../src/TournamentStatusManager.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($tournament_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid tournament ID']);
    exit();
}

try {
    $manager = new TournamentStatusManager($conn);
    $tournament = $manager->getTournamentStatus($tournament_id);
    
    if (!$tournament) {
        echo json_encode(['success' => false, 'error' => 'Tournament not found']);
        exit();
    }
    
    // Check if current user is registered
    $user_id = (int)$_SESSION['user_id'];
    $check_stmt = $conn->prepare("
        SELECT id FROM registrations WHERE tournament_id = ? AND player_id = ?
    ");
    $check_stmt->bind_param('ii', $tournament_id, $user_id);
    $check_stmt->execute();
    $is_registered = (bool)$check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    $tournament['is_registered'] = $is_registered;
    $tournament['can_join'] = !$is_registered && 
                               $tournament['status'] === 'upcoming' && 
                               !$tournament['is_full'];
    
    echo json_encode([
        'success' => true,
        'tournament' => $tournament
    ]);
    
} catch (Exception $e) {
    error_log("Tournament status API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
