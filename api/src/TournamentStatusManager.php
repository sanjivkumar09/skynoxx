<?php
// Minimal TournamentStatusManager stub for compatibility
class TournamentStatusManager {
    protected $conn;
    public function __construct($conn) { $this->conn = $conn; }
    public function getStatus($tournamentId) {
        $tournamentId = (int)$tournamentId;
        $st = $this->conn->prepare('SELECT status FROM tournaments WHERE id = ? LIMIT 1');
        if (!$st) return null;
        $st->bind_param('i', $tournamentId);
        $st->execute();
        $res = $st->get_result();
        $row = $res->fetch_assoc();
        return $row['status'] ?? null;
    }
}

?>
