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
        $newId = mysqli_insert_id($conn);
        // Ensure tournament_wallets table exists
        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tournament_wallets (
            tournament_id INT PRIMARY KEY,
            balance DECIMAL(12,2) NOT NULL DEFAULT 0,
            required_prize_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            prize_distributed_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            status ENUM('open','settled','cancelled') NOT NULL DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_tw_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        // Create wallet row
        if ($iw = mysqli_prepare($conn, "INSERT IGNORE INTO tournament_wallets (tournament_id, balance, required_prize_total, prize_distributed_total, status) VALUES (?, 0, ?, 0, 'open')")) {
            mysqli_stmt_bind_param($iw, 'id', $newId, $prize_pool);
            mysqli_stmt_execute($iw);
            mysqli_stmt_close($iw);
        }
        // Notifications tables and broadcast notification
        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
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
        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notification_reads (
            user_id INT PRIMARY KEY,
            last_read_at DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        if ($nn = mysqli_prepare($conn, "INSERT INTO notifications (type, title, message, tournament_id, audience) VALUES ('tournament_created', ?, ?, ?, 'players')")) {
            $ntitle = 'New Tournament: ' . $title;
            $nmsg = 'Creator posted a new tournament on ' . $date . ' ' . $time . ' | Entry ₹' . number_format($entry_fee,2) . ' | Prize ₹' . number_format($prize_pool,2);
            mysqli_stmt_bind_param($nn, 'ssi', $ntitle, $nmsg, $newId);
            mysqli_stmt_execute($nn);
            mysqli_stmt_close($nn);
        }
        echo json_encode(['success' => 'Tournament created successfully', 'tournament_id' => $newId]);
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

    // Check for references in registrations and wallet_transactions
    $regCount = 0; $txCount = 0;
    if ($st1 = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM registrations WHERE tournament_id = ?")) {
        mysqli_stmt_bind_param($st1, 'i', $id);
        mysqli_stmt_execute($st1);
        $res1 = mysqli_stmt_get_result($st1);
        if ($row = mysqli_fetch_assoc($res1)) { $regCount = (int)$row['c']; }
        mysqli_stmt_close($st1);
    }
    if ($st2 = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM wallet_transactions WHERE tournament_id = ?")) {
        mysqli_stmt_bind_param($st2, 'i', $id);
        mysqli_stmt_execute($st2);
        $res2 = mysqli_stmt_get_result($st2);
        if ($row = mysqli_fetch_assoc($res2)) { $txCount = (int)$row['c']; }
        mysqli_stmt_close($st2);
    }

    if ($regCount > 0 || $txCount > 0) {
        // Refund and cancel
        $entry_fee = 0.0; $creator_id = 0;
        if ($tq = mysqli_prepare($conn, "SELECT entry_fee, created_by FROM tournaments WHERE id = ? LIMIT 1")) {
            mysqli_stmt_bind_param($tq, 'i', $id);
            mysqli_stmt_execute($tq);
            $tr = mysqli_stmt_get_result($tq);
            if ($row = mysqli_fetch_assoc($tr)) { $entry_fee = (float)$row['entry_fee']; $creator_id = (int)$row['created_by']; }
            mysqli_stmt_close($tq);
        }
        $playerIds = [];
        if ($rg = mysqli_prepare($conn, "SELECT player_id FROM registrations WHERE tournament_id = ? AND (payment_status = 'success' OR payment_status = 'paid')")) {
            mysqli_stmt_bind_param($rg, 'i', $id);
            mysqli_stmt_execute($rg);
            $rr = mysqli_stmt_get_result($rg);
            while ($r = mysqli_fetch_assoc($rr)) { $playerIds[] = (int)$r['player_id']; }
            mysqli_stmt_close($rg);
        }
        if (!empty($playerIds) && $entry_fee > 0) {
            // Check creator balance
            $needed = $entry_fee * count($playerIds);
            $creatorBal = 0.0;
            if ($wb = mysqli_prepare($conn, "SELECT wallet_balance FROM users WHERE id = ? LIMIT 1")) {
                mysqli_stmt_bind_param($wb, 'i', $creator_id);
                mysqli_stmt_execute($wb);
                $wbr = mysqli_stmt_get_result($wb);
                if ($b = mysqli_fetch_assoc($wbr)) { $creatorBal = (float)$b['wallet_balance']; }
                mysqli_stmt_close($wb);
            }
            if ($creatorBal < $needed) {
                echo json_encode(['error' => 'Insufficient creator funds to refund players. Cancellation aborted.']);
                return;
            }
            // Process refunds
            mysqli_begin_transaction($conn);
            $ok = true;
            foreach ($playerIds as $pid) {
                if ($ded = mysqli_prepare($conn, "UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ? AND wallet_balance >= ?")) {
                    mysqli_stmt_bind_param($ded, 'dii', $entry_fee, $creator_id, $entry_fee);
                    mysqli_stmt_execute($ded);
                    $affected = mysqli_stmt_affected_rows($ded);
                    mysqli_stmt_close($ded);
                    if ($affected !== 1) { $ok = false; break; }
                } else { $ok = false; break; }
                if ($cr = mysqli_prepare($conn, "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")) {
                    mysqli_stmt_bind_param($cr, 'di', $entry_fee, $pid);
                    mysqli_stmt_execute($cr);
                    mysqli_stmt_close($cr);
                } else { $ok = false; break; }
                if ($wtp = mysqli_prepare($conn, "INSERT INTO wallet_transactions (user_id, type, amount, related_user_id, tournament_id, description, status) VALUES (?, 'credit', ?, ?, ?, ?, 'completed')")) {
                    $descP = 'Refund for cancelled tournament #' . $id;
                    mysqli_stmt_bind_param($wtp, 'idiis', $pid, $entry_fee, $creator_id, $id, $descP);
                    mysqli_stmt_execute($wtp);
                    mysqli_stmt_close($wtp);
                }
                if ($wtc = mysqli_prepare($conn, "INSERT INTO wallet_transactions (user_id, type, amount, related_user_id, tournament_id, description, status) VALUES (?, 'debit', ?, ?, ?, ?, 'completed')")) {
                    $descC = 'Refund payout for cancelled tournament #' . $id;
                    mysqli_stmt_bind_param($wtc, 'idiis', $creator_id, $entry_fee, $pid, $id, $descC);
                    mysqli_stmt_execute($wtc);
                    mysqli_stmt_close($wtc);
                }
                if ($rup = mysqli_prepare($conn, "UPDATE registrations SET payment_status = 'refunded' WHERE tournament_id = ? AND player_id = ?")) {
                    mysqli_stmt_bind_param($rup, 'ii', $id, $pid);
                    mysqli_stmt_execute($rup);
                    mysqli_stmt_close($rup);
                }
            }
            if ($ok) {
                if ($up = mysqli_prepare($conn, "UPDATE tournaments SET status = 'cancelled' WHERE id = ?")) {
                    mysqli_stmt_bind_param($up, 'i', $id);
                    mysqli_stmt_execute($up);
                    mysqli_stmt_close($up);
                } else { $ok = false; }
            }
            if ($ok) { mysqli_commit($conn); echo json_encode(['success' => 'Tournament cancelled and players refunded.']); }
            else { mysqli_rollback($conn); echo json_encode(['error' => 'Refund failed; no changes applied.']); }
            return;
        } else {
            // No paid entries or free tournament: just cancel
            if ($up = mysqli_prepare($conn, "UPDATE tournaments SET status = 'cancelled' WHERE id = ?")) {
                mysqli_stmt_bind_param($up, 'i', $id);
                if (mysqli_stmt_execute($up)) { echo json_encode(['success' => 'Tournament cancelled.']); }
                else { echo json_encode(['error' => 'Failed to cancel tournament']); }
                mysqli_stmt_close($up);
            } else { echo json_encode(['error' => 'Server error']); }
            return;
        }
    }

    // No references: safe to delete
    if ($del = mysqli_prepare($conn, "DELETE FROM tournaments WHERE id = ?")) {
        mysqli_stmt_bind_param($del, 'i', $id);
        if (mysqli_stmt_execute($del)) {
            echo json_encode(['success' => 'Tournament deleted successfully']);
        } else {
            echo json_encode(['error' => 'Failed to delete tournament']);
        }
        mysqli_stmt_close($del);
    } else {
        echo json_encode(['error' => 'Server error']);
    }
}
?>