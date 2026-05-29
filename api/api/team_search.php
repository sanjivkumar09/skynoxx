<?php
/**
 * AJAX API for Team Member Search
 * Allows searching for registered users by username or game UID
 * Returns profile data for team building
 */

session_start();
header('Content-Type: application/json');
include '../src/db.php';

// Only allow logged-in players
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$current_user_id = (int)$_SESSION['user_id'];

// Search users by username or game UID
if ($action === 'search') {
    $query = trim($_GET['q'] ?? '');
    $tournament_id = (int)($_GET['tournament_id'] ?? 0);
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'users' => []]);
        exit();
    }
    
    // Search in users and players_profile tables
    $search_pattern = '%' . $conn->real_escape_string($query) . '%';
    
    $sql = "SELECT DISTINCT
                u.id,
                u.name,
                u.email,
                u.profile_verified,
                pp.in_game_name,
                pp.game_uid,
                pp.avatar,
                CASE 
                    WHEN pp.game_uid IS NOT NULL AND pp.game_uid != '' 
                         AND pp.in_game_name IS NOT NULL AND pp.in_game_name != '' 
                    THEN 1 
                    ELSE 0 
                END AS profile_complete,
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM registrations r 
                        WHERE r.tournament_id = ? 
                        AND r.player_id = u.id
                        AND r.payment_status IN ('success', 'paid')
                    ) THEN 1
                    ELSE 0
                END AS already_registered
            FROM users u
            LEFT JOIN players_profile pp ON u.id = pp.user_id
            WHERE u.role = 'player'
            AND u.id != ?
            AND (
                u.name LIKE ? 
                OR u.email LIKE ?
                OR pp.in_game_name LIKE ?
                OR pp.game_uid LIKE ?
            )
            ORDER BY profile_complete DESC, u.name ASC
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iissss', $tournament_id, $current_user_id, $search_pattern, $search_pattern, $search_pattern, $search_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'in_game_name' => $row['in_game_name'] ?? '',
            'game_uid' => $row['game_uid'] ?? '',
            'avatar' => $row['avatar'] ?? '',
            'profile_complete' => (bool)$row['profile_complete'],
            'profile_verified' => (bool)$row['profile_verified'],
            'already_registered' => (bool)$row['already_registered']
        ];
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
    exit();
}

// Get user profile details by ID
if ($action === 'get_profile') {
    $user_id = (int)($_GET['user_id'] ?? 0);
    
    if ($user_id === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
        exit();
    }
    
    $sql = "SELECT 
                u.id,
                u.name,
                u.email,
                u.profile_verified,
                pp.in_game_name,
                pp.game_uid,
                pp.avatar,
                pp.screenshot,
                CASE 
                    WHEN pp.game_uid IS NOT NULL AND pp.game_uid != '' 
                         AND pp.in_game_name IS NOT NULL AND pp.in_game_name != '' 
                    THEN 1 
                    ELSE 0 
                END AS profile_complete
            FROM users u
            LEFT JOIN players_profile pp ON u.id = pp.user_id
            WHERE u.id = ?
            AND u.role = 'player'
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'in_game_name' => $row['in_game_name'] ?? '',
                'game_uid' => $row['game_uid'] ?? '',
                'avatar' => $row['avatar'] ?? '',
                'screenshot' => $row['screenshot'] ?? '',
                'profile_complete' => (bool)$row['profile_complete'],
                'profile_verified' => (bool)$row['profile_verified']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
    exit();
}

// Validate team composition
if ($action === 'validate_team') {
    $team_members = json_decode($_POST['team_members'] ?? '[]', true);
    $match_type = strtolower(trim($_POST['match_type'] ?? ''));
    $tournament_id = (int)($_POST['tournament_id'] ?? 0);
    
    $errors = [];
    $warnings = [];
    
    // Determine required team size
    $required_size = 1;
    if ($match_type === 'duo') $required_size = 2;
    elseif ($match_type === 'squad' || $match_type === 'clash squad') $required_size = 4;
    
    // Check team size
    if (count($team_members) !== $required_size) {
        $errors[] = "Team must have exactly $required_size members for $match_type match";
    }
    
    // Check for duplicate user IDs
    $user_ids = array_column($team_members, 'user_id');
    if (count($user_ids) !== count(array_unique($user_ids))) {
        $errors[] = 'Duplicate team members detected';
    }
    
    // Check if captain is included
    if (!in_array($current_user_id, $user_ids)) {
        $errors[] = 'You must be included in the team';
    }
    
    // Validate each member's profile
    foreach ($team_members as $member) {
        $uid = (int)($member['user_id'] ?? 0);
        if ($uid === 0) {
            $errors[] = 'Invalid member data';
            continue;
        }
        
        // Check profile completeness
        $stmt = $conn->prepare("SELECT u.name, pp.game_uid, pp.in_game_name 
                                FROM users u 
                                LEFT JOIN players_profile pp ON u.id = pp.user_id 
                                WHERE u.id = ?");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (empty($row['game_uid']) || empty($row['in_game_name'])) {
                $errors[] = "{$row['name']} has incomplete profile (missing Game UID or IGN)";
            }
        } else {
            $errors[] = "Member ID $uid not found";
        }
        
        // Check if already registered
        $check_stmt = $conn->prepare("SELECT id FROM registrations 
                                       WHERE tournament_id = ? 
                                       AND player_id = ?
                                       AND payment_status IN ('success', 'paid')
                                       LIMIT 1");
        $check_stmt->bind_param('ii', $tournament_id, $uid);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errors[] = "{$row['name']} is already registered for this tournament";
        }
    }
    
    echo json_encode([
        'success' => count($errors) === 0,
        'valid' => count($errors) === 0,
        'errors' => $errors,
        'warnings' => $warnings
    ]);
    exit();
}

// Get current user's profile completeness
if ($action === 'check_profile') {
    $sql = "SELECT 
                pp.game_uid,
                pp.in_game_name,
                CASE 
                    WHEN pp.game_uid IS NOT NULL AND pp.game_uid != '' 
                         AND pp.in_game_name IS NOT NULL AND pp.in_game_name != '' 
                    THEN 1 
                    ELSE 0 
                END AS profile_complete
            FROM players_profile pp
            WHERE pp.user_id = ?
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'profile_complete' => (bool)$row['profile_complete'],
            'game_uid' => $row['game_uid'] ?? '',
            'in_game_name' => $row['in_game_name'] ?? ''
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'profile_complete' => false,
            'game_uid' => '',
            'in_game_name' => ''
        ]);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
