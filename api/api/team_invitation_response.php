<?php
session_start();
require_once '../src/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$invitation_id = isset($_POST['invitation_id']) ? (int)$_POST['invitation_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : ''; // 'accept' or 'reject'

if ($invitation_id <= 0 || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Fetch invitation details
$inv_stmt = $conn->prepare("
    SELECT ti.*, t.title as tournament_title, u.name as inviter_name
    FROM team_invitations ti
    JOIN tournaments t ON ti.tournament_id = t.id
    JOIN users u ON ti.invited_by = u.id
    WHERE ti.id = ? AND ti.invited_user = ? AND ti.status = 'pending'
");
$inv_stmt->bind_param('ii', $invitation_id, $user_id);
$inv_stmt->execute();
$invitation = $inv_stmt->get_result()->fetch_assoc();
$inv_stmt->close();

if (!$invitation) {
    echo json_encode(['success' => false, 'error' => 'Invitation not found or already responded']);
    exit;
}

$conn->begin_transaction();

try {
    $new_status = ($action === 'accept') ? 'accepted' : 'rejected';
    
    // Update invitation status
    $update_inv = $conn->prepare("UPDATE team_invitations SET status = ?, responded_at = NOW() WHERE id = ?");
    $update_inv->bind_param('si', $new_status, $invitation_id);
    $update_inv->execute();
    $update_inv->close();
    
    // Update team_registrations status
    $update_team = $conn->prepare("UPDATE team_registrations SET status = ? WHERE registration_id = ? AND user_id = ?");
    $update_team->bind_param('sii', $new_status, $invitation['registration_id'], $user_id);
    $update_team->execute();
    $update_team->close();
    
    // Send notification to captain
    $captain_id = $invitation['invited_by'];
    $response_text = ($action === 'accept') ? 'accepted' : 'rejected';
    $player_name = $_SESSION['name'] ?? 'A player';
    $notif_title = "Team Invitation " . ucfirst($response_text);
    $notif_message = "$player_name has $response_text your invitation to join the team '{$invitation['team_name']}' for the tournament '{$invitation['tournament_title']}'.";
    
    $notif_stmt = $conn->prepare("INSERT INTO notifications (type, title, message, tournament_id, audience, audience_user_id) VALUES ('team_response', ?, ?, ?, 'user', ?)");
    $notif_stmt->bind_param('ssii', $notif_title, $notif_message, $invitation['tournament_id'], $captain_id);
    $notif_stmt->execute();
    $notif_stmt->close();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'action' => $action,
        'message' => $action === 'accept' ? 'You have accepted the invitation!' : 'You have rejected the invitation.'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Failed to process response']);
}
