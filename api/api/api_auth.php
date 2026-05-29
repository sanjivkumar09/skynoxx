<?php
// api/api_auth.php

require_once '../src/db.php';
require_once '../src/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $response = login($email, $password);
            echo json_encode($response);
        } else {
            echo json_encode(['error' => 'Invalid request method.']);
        }
        break;

    case 'signup':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $password = $_POST['password'] ?? '';
            $response = signup($name, $email, $phone, $password);
            echo json_encode($response);
        } else {
            echo json_encode(['error' => 'Invalid request method.']);
        }
        break;

    case 'logout':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $response = logout();
            echo json_encode($response);
        } else {
            echo json_encode(['error' => 'Invalid request method.']);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action.']);
        break;
}
?>