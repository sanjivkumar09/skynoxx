<?php
session_start();
require_once '../src/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$registration_id = isset($_POST['registration_id']) ? (int)$_POST['registration_id'] : 0;

if ($registration_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Fetch registration and team details
$reg_stmt = $conn->prepare("
    SELECT r.*,
        t.title AS tournament_title,
        t.created_by AS tournament_creator,
        t.entry_fee AS entry_fee,
        t.status AS tournament_status,
        tr.user_id AS team_user_id,
        tr.role AS team_role
    FROM registrations r
    JOIN tournaments t ON r.tournament_id = t.id
    LEFT JOIN team_registrations tr ON tr.registration_id = r.id AND tr.user_id = ?
    WHERE r.id = ? AND (r.player_id = ? OR tr.user_id = ?)
");
    $reg_stmt->bind_param('iiii', $user_id, $registration_id, $user_id, $user_id);
$reg_stmt->execute();
$registration = $reg_stmt->get_result()->fetch_assoc();
$reg_stmt->close();

if (!$registration) {
    echo json_encode(['success' => false, 'error' => 'Registration not found or you are not part of this team']);
    exit;
}

// Only allow leaving before the tournament starts
$tStatus = strtolower((string)($registration['tournament_status'] ?? ''));
if (!in_array($tStatus, ['upcoming', 'open'])) {
    echo json_encode(['success' => false, 'error' => 'You can only leave teams for upcoming tournaments.']);
    exit;
}

$conn->begin_transaction();

try {
    // Get all team members before deletion
    $team_stmt = $conn->prepare("
        SELECT u.id, u.name 
        FROM team_registrations tr
        JOIN users u ON tr.user_id = u.id
        WHERE tr.registration_id = ? AND tr.user_id != ?
    ");
    $team_stmt->bind_param('ii', $registration_id, $user_id);
    $team_stmt->execute();
    $team_members = $team_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $team_stmt->close();
    
    // Delete the entire registration (cascade will delete team_registrations)
    $delete_stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
    $delete_stmt->bind_param('i', $registration_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Refund entry fee if paid
    $entry_fee = (float)($registration['entry_fee'] ?? 0);
    if ($entry_fee > 0 && strtolower((string)$registration['payment_status']) === 'success') {
        // Refund to captain
        $refund_stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $refund_stmt->bind_param('di', $entry_fee, $registration['player_id']);
        $refund_stmt->execute();
        $refund_stmt->close();
        
        // Log refund transaction
        $trans_stmt = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, tournament_id, description, status) VALUES (?, 'credit', ?, ?, ?, 'completed')");
        $desc = "Refund: Team disbanded for tournament '{$registration['tournament_title']}'";
        $trans_stmt->bind_param('idis', $registration['player_id'], $entry_fee, $registration['tournament_id'], $desc);
        $trans_stmt->execute();
        $trans_stmt->close();
        
        // Update tournament wallet
        $tw_stmt = $conn->prepare("UPDATE tournament_wallets SET balance = balance - ? WHERE tournament_id = ?");
        $tw_stmt->bind_param('di', $entry_fee, $registration['tournament_id']);
        $tw_stmt->execute();
        $tw_stmt->close();
    }
    
    // Send notifications to all team members
    $leaver_name = $_SESSION['name'] ?? 'A player';
    foreach ($team_members as $member) {
        $notif_title = 'Team Disbanded';
        $notif_message = "$leaver_name has left the team for tournament '{$registration['tournament_title']}'. The entire team registration has been cancelled.";
        
        $notif_stmt = $conn->prepare("INSERT INTO notifications (type, title, message, tournament_id, audience, audience_user_id) VALUES ('team_disbanded', ?, ?, ?, 'user', ?)");
        $notif_stmt->bind_param('ssii', $notif_title, $notif_message, $registration['tournament_id'], $member['id']);
        $notif_stmt->execute();
        $notif_stmt->close();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'You have left the team. The entire team registration has been cancelled and the slot is now available.'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Failed to leave team: ' . $e->getMessage()]);
}
