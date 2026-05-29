<?php
require_once '../src/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_tournaments':
        getTournaments();
        break;
    case 'get_tournament':
        getTournament();
        break;
    case 'create_tournament':
        createTournament();
        break;
    case 'update_tournament':
        updateTournament();
        break;
    case 'delete_tournament':
        deleteTournament();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getTournaments() {
    $conn = dbConnect();
    $query = "SELECT * FROM tournaments WHERE status = 'active'";
    $result = mysqli_query($conn, $query);
    $tournaments = mysqli_fetch_all($result, MYSQLI_ASSOC);
    echo json_encode($tournaments);
}

function getTournament() {
    $conn = dbConnect();
    $id = intval($_GET['id']);
    $query = "SELECT * FROM tournaments WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $tournament = mysqli_fetch_assoc($result);
    echo json_encode($tournament);
}

function createTournament() {
    $conn = dbConnect();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $title = mysqli_real_escape_string($conn, $data['title']);
    $description = mysqli_real_escape_string($conn, $data['description']);
    $entry_fee = floatval($data['entry_fee']);
    $prize_pool = floatval($data['prize_pool']);
    $max_players = intval($data['max_players']);
    $match_type = mysqli_real_escape_string($conn, $data['match_type']);
    $map_name = mysqli_real_escape_string($conn, $data['map_name']);
    $date = mysqli_real_escape_string($conn, $data['date']);
    $time = mysqli_real_escape_string($conn, $data['time']);
    $created_by = intval($data['created_by']);
    
    $query = "INSERT INTO tournaments (title, description, entry_fee, prize_pool, max_players, match_type, map_name, date, time, created_by, status, created_at) 
              VALUES ('$title', '$description', $entry_fee, $prize_pool, $max_players, '$match_type', '$map_name', '$date', '$time', $created_by, 'active', NOW())";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => 'Tournament created successfully']);
    } else {
        echo json_encode(['error' => 'Failed to create tournament']);
    }
}

function updateTournament() {
    $conn = dbConnect();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($data['id']);
    $title = mysqli_real_escape_string($conn, $data['title']);
    $description = mysqli_real_escape_string($conn, $data['description']);
    $entry_fee = floatval($data['entry_fee']);
    $prize_pool = floatval($data['prize_pool']);
    $max_players = intval($data['max_players']);
    $match_type = mysqli_real_escape_string($conn, $data['match_type']);
    $map_name = mysqli_real_escape_string($conn, $data['map_name']);
    $date = mysqli_real_escape_string($conn, $data['date']);
    $time = mysqli_real_escape_string($conn, $data['time']);
    
    $query = "UPDATE tournaments SET title='$title', description='$description', entry_fee=$entry_fee, prize_pool=$prize_pool, max_players=$max_players, match_type='$match_type', map_name='$map_name', date='$date', time='$time' WHERE id=$id";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => 'Tournament updated successfully']);
    } else {
        echo json_encode(['error' => 'Failed to update tournament']);
    }
}

function deleteTournament() {
    $conn = dbConnect();
    $id = intval($_GET['id']);
    
    $query = "DELETE FROM tournaments WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => 'Tournament deleted successfully']);
    } else {
        echo json_encode(['error' => 'Failed to delete tournament']);
    }
}
?>