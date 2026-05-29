<?php
session_start();
require_once '../src/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Ensure tables exist
@$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL DEFAULT 'tournament_created',
    title VARCHAR(255) NOT NULL,
    message TEXT,
    tournament_id INT NULL,
    audience ENUM('all','players','creators','user') NOT NULL DEFAULT 'players',
    audience_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_audience (audience)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
@$conn->query("CREATE TABLE IF NOT EXISTS notification_reads (
    user_id INT PRIMARY KEY,
    last_read_at DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_nr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

switch ($action) {
    case 'summary':
        summary($conn, $user_id, $role);
        break;
    case 'mark_read':
        mark_read($conn, $user_id);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function summary(mysqli $conn, int $user_id, string $role) {
    // Get last read
    $last_read = '1970-01-01 00:00:00';
    if ($st = $conn->prepare('SELECT last_read_at FROM notification_reads WHERE user_id = ?')) {
        $st->bind_param('i', $user_id);
        $st->execute();
        $res = $st->get_result();
        if ($row = $res->fetch_assoc()) { $last_read = $row['last_read_at']; }
        $st->close();
    }

    // Build audience filter
    $audWhere = "(audience = 'all' OR audience = 'players' OR (audience='user' AND audience_user_id = ?))";

    // Unread count
    $unread = 0;
    if ($cs = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE $audWhere AND created_at > ?")) {
        $cs->bind_param('is', $user_id, $last_read);
        $cs->execute();
        $r = $cs->get_result();
        if ($row = $r->fetch_assoc()) { $unread = (int)$row['c']; }
        $cs->close();
    }

    // Latest list (limit 10)
    $items = [];
    if ($ls = $conn->prepare("SELECT id, type, title, message, tournament_id, invitation_id, created_at FROM notifications WHERE $audWhere ORDER BY created_at DESC, id DESC LIMIT 10")) {
        $ls->bind_param('i', $user_id);
        $ls->execute();
        $rr = $ls->get_result();
        while ($row = $rr->fetch_assoc()) {
            $row['unread'] = ($row['created_at'] > $last_read);
            $items[] = $row;
        }
        $ls->close();
    }

    echo json_encode(['unread' => $unread, 'items' => $items]);
}

function mark_read(mysqli $conn, int $user_id) {
    // Upsert last_read_at
    if ($st = $conn->prepare("INSERT INTO notification_reads (user_id, last_read_at) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_read_at = NOW()")) {
        $st->bind_param('i', $user_id);
        $st->execute();
        $st->close();
    }
    echo json_encode(['success' => true]);
}
