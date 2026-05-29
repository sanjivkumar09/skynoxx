<?php
// Minimal NotificationManager stub
class NotificationManager {
    protected $conn;
    public function __construct($conn = null) { $this->conn = $conn; }
    public function send($toUserId, $title, $body, $meta = []) {
        // For local testing, we simply log to DB if available
        if ($this->conn) {
            $stmt = $this->conn->prepare('INSERT INTO notifications (user_id, title, body, meta, created_at) VALUES (?, ?, ?, ?, NOW())');
            if ($stmt) {
                $metaJson = json_encode($meta);
                $stmt->bind_param('isss', $toUserId, $title, $body, $metaJson);
                $stmt->execute();
                $stmt->close();
            }
        }
        return true;
    }
}

?>
