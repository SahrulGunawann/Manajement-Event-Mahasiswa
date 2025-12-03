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

    // Notify all non-admin users that a new event has been created
    public function notifyAllUsersOnNewEvent($event_id) {
        // get event details
        $esql = "SELECT id, title, event_date, event_time, location FROM events WHERE id = ? LIMIT 1";
        $estmt = $this->db->conn->prepare($esql);
        $estmt->bind_param("i", $event_id);
        $estmt->execute();
        $eres = $estmt->get_result();
        $event = $eres->fetch_assoc();
        if (!$event) return 0;

        $title = "New Event: " . $event['title'];
        $message = "Ada event baru: " . $event['title'] . " pada " 
                 . date('d M Y', strtotime($event['event_date'])) . " " 
                 . $event['event_time'] . " di " . $event['location'];

        // get all non-admin users
        $usersSql = "SELECT id FROM users WHERE role != 'admin'";
        $uStmt = $this->db->conn->prepare($usersSql);
        $uStmt->execute();
        $uRes = $uStmt->get_result();
        $users = $uRes->fetch_all(MYSQLI_ASSOC);

        $count = 0;

        foreach ($users as $u) {
            // check duplicate
            $checkSql = "SELECT id FROM notifications WHERE user_id = ? AND title = ? LIMIT 1";
            $checkStmt = $this->db->conn->prepare($checkSql);
            $checkStmt->bind_param("is", $u['id'], $title);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) continue;

            $insertSql = "INSERT INTO notifications (user_id, title, message, type)
                          VALUES (?, ?, ?, 'new_event')";
            $insertStmt = $this->db->conn->prepare($insertSql);
            $insertStmt->bind_param("iss", $u['id'], $title, $message);

            if ($insertStmt->execute()) {
                $count++;
            }
        }

        return $count;
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

    // Mark all notifications for a user as read
    public function markAllAsRead($user_id) {
        $sql = "UPDATE notifications 
                SET status = 'read' 
                WHERE user_id = ? AND (status IS NULL OR status != 'read')";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            return $this->db->conn->affected_rows;
        }
        return 0;
    }

    // Get count of unread notifications
    public function getUnreadCount($user_id) {
        $sql = "SELECT COUNT(*) as cnt 
                FROM notifications 
                WHERE user_id = ? AND (status IS NULL OR status != 'read')";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return (int) ($row['cnt'] ?? 0);
    }
}
?>
