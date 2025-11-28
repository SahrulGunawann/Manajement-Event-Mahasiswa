<?php
require_once __DIR__ . '/../config/Database.php';

class NotificationService {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Check untuk event besok dan buat notifikasi
    public function checkUpcomingEvents() {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $sql = "SELECT e.*, ep.user_id 
                FROM events e 
                JOIN event_participants ep ON e.id = ep.event_id 
                WHERE e.event_date = ? AND ep.status = 'registered'";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("s", $tomorrow);
        $stmt->execute();
        $result = $stmt->get_result();
        $events = $result->fetch_all(MYSQLI_ASSOC);

        $notificationCount = 0;

        foreach ($events as $event) {
            $title = "Reminder: " . $event['title'];
            $message = "Jangan lupa event besok: " . $event['title'] . " di " . $event['location'];
            
            // Cek apakah notifikasi sudah dikirim
            $checkSql = "SELECT id FROM notifications 
                        WHERE user_id = ? AND title = ? AND DATE(created_at) = CURDATE()";
            $checkStmt = $this->db->conn->prepare($checkSql);
            $checkStmt->bind_param("is", $event['user_id'], $title);
            $checkStmt->execute();
            $checkStmt->store_result();
            
            if ($checkStmt->num_rows === 0) {
                // Buat notifikasi baru
                $insertSql = "INSERT INTO notifications (user_id, title, message, type) 
                              VALUES (?, ?, ?, 'reminder')";
                $insertStmt = $this->db->conn->prepare($insertSql);
                $insertStmt->bind_param("iss", $event['user_id'], $title, $message);
                
                if ($insertStmt->execute()) {
                    $notificationCount++;
                }
            }
        }

        return $notificationCount;
    }

    // Get notifications untuk user
    public function getUserNotifications($user_id, $limit = 5) {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Mark notification as read
    public function markAsRead($notification_id) {
        $sql = "UPDATE notifications SET status = 'read' WHERE id = ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $notification_id);
        return $stmt->execute();
    }
}
?>