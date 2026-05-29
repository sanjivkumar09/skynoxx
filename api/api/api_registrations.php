<?php
// api/api_registrations.php

require_once '../src/db.php';
require_once '../src/auth.php';

header('Content-Type: application/json');

$action = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'POST':
        // Register a player for a tournament
        $tournament_id = (int)($_POST['tournament_id'] ?? 0);
        if ($tournament_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid tournament ID.']);
            break;
        }

        // Prevent registration if tournament is not upcoming or full
        $ts = $conn->prepare("SELECT status, max_players FROM tournaments WHERE id = ? LIMIT 1");
        $ts->bind_param('i', $tournament_id);
        $ts->execute();
        $tres = $ts->get_result();
        $tinfo = $tres->fetch_assoc();
        $ts->close();
        if (!$tinfo) {
            echo json_encode(['status' => 'error', 'message' => 'Tournament not found.']);
            break;
        }
        if (strtolower($tinfo['status'] ?? '') !== 'upcoming') {
            echo json_encode(['status' => 'error', 'message' => 'Registration closed. Tournament already started or not available.']);
            break;
        }
        if (!is_null($tinfo['max_players'])) {
            if ($cs = $conn->prepare("SELECT COUNT(*) AS c FROM registrations WHERE tournament_id = ? AND payment_status IN ('success','paid')")) {
                $cs->bind_param('i', $tournament_id);
                $cs->execute();
                $crow = $cs->get_result()->fetch_assoc();
                $cs->close();
                if ((int)($crow['c'] ?? 0) >= (int)$tinfo['max_players']) {
                    echo json_encode(['status' => 'error', 'message' => 'Registration full.']);
                    break;
                }
            }
        }

        $player_id = (int)($_SESSION['user_id'] ?? 0); // Assuming user_id is stored in session
        if ($player_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            break;
        }
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