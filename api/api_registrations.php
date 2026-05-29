<?php
// api/api_registrations.php

require_once '../src/db.php';
require_once '../src/auth.php';

header('Content-Type: application/json');

$action = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'POST':
        // Register a player for a tournament
        $tournament_id = $_POST['tournament_id'];
        $player_id = $_SESSION['user_id']; // Assuming user_id is stored in session
        $payment_status = 'pending'; // Default status until payment is confirmed

        // Insert registration into the database
        $stmt = $conn->prepare("INSERT INTO registrations (tournament_id, player_id, payment_status, joined_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $tournament_id, $player_id, $payment_status);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Successfully registered for the tournament.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
        }
        $stmt->close();
        break;

    case 'GET':
        // Get registrations for a specific tournament
        $tournament_id = $_GET['tournament_id'];

        $stmt = $conn->prepare("SELECT * FROM registrations WHERE tournament_id = ?");
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $registrations = [];
        while ($row = $result->fetch_assoc()) {
            $registrations[] = $row;
        }

        echo json_encode(['status' => 'success', 'data' => $registrations]);
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
        break;
}

$conn->close();
?>