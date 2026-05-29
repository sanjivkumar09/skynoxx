<?php
// routes.php

// Include necessary files
require_once 'db.php';
require_once 'auth.php';
require_once 'helpers.php';

// Define routes
$routes = [
    '/' => 'index.php',
    '/login' => 'login.php',
    '/signup' => 'signup.php',
    '/about' => 'about.php',
    '/contact' => 'contact.php',
    '/tournaments' => 'tournaments.php',
    '/join-tournament' => 'join_tournament.php',
    '/player/dashboard' => 'player/player_dashboard.php',
    '/creator/dashboard' => 'creator/creator_dashboard.php',
    '/admin/dashboard' => 'admin/admin_dashboard.php',
];

// Handle routing
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (array_key_exists($request_uri, $routes)) {
    include $routes[$request_uri];
} else {
    include '404.php'; // Include a 404 page if route not found
}
?>