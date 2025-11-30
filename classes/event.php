<?php
require_once __DIR__ . '/../config/Database.php';

class Event {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    // Method untuk ambil event mendatang
    public function getUpcomingEvents($limit = null) {
        $sql = "SELECT * FROM events 
                WHERE event_date >= CURDATE() 
                ORDER BY event_date ASC, event_time ASC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Method untuk ambil event berdasarkan bulan
    public function getEventsByMonth($month, $year, $limit = null) {
        $sql = "SELECT * FROM events 
                WHERE MONTH(event_date) = ? AND YEAR(event_date) = ? 
                AND event_date >= CURDATE()
                ORDER BY event_date ASC, event_time ASC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("ii", $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Method untuk ambil semua events
    public function getAllEvents() {
        $sql = "SELECT e.*, u.name as creator_name 
                FROM events e 
                LEFT JOIN users u ON e.created_by = u.id 
                ORDER BY e.event_date ASC";
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getEventById($id) {
        $sql = "SELECT e.*, u.name as creator_name 
                FROM events e 
                LEFT JOIN users u ON e.created_by = u.id 
                WHERE e.id = ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createEvent($title, $description, $event_date, $event_time, $location, $max_participants, $created_by) {
        $sql = "INSERT INTO events (title, description, event_date, event_time, location, max_participants, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("sssssii", $title, $description, $event_date, $event_time, $location, $max_participants, $created_by);
        if ($stmt->execute()) {
            return $this->db->conn->insert_id;
        }
        return false;
    }

    public function updateEvent($id, $title, $description, $event_date, $event_time, $location, $max_participants) {
        $sql = "UPDATE events SET title=?, description=?, event_date=?, event_time=?, location=?, max_participants=? WHERE id=?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("sssssii", $title, $description, $event_date, $event_time, $location, $max_participants, $id);
        return $stmt->execute();
    }

    public function deleteEvent($id) {
        $sql = "DELETE FROM events WHERE id = ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getEventParticipants($event_id) {
        $sql = "SELECT u.name, u.email, ep.registered_at, ep.status 
                FROM event_participants ep 
                JOIN users u ON ep.user_id = u.id 
                WHERE ep.event_id = ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function registerForEvent($event_id, $user_id) {
        // Check if already registered
        $checkSql = "SELECT id FROM event_participants WHERE event_id = ? AND user_id = ?";
        $checkStmt = $this->db->conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $event_id, $user_id);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            return false; // Already registered
        }
        
        // Register user
        $sql = "INSERT INTO event_participants (event_id, user_id) VALUES (?, ?)";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("ii", $event_id, $user_id);
        return $stmt->execute();
    }

    // Method untuk hapus event yang sudah lewat (auto-cleanup)
    public function cleanupPastEvents() {
        $sql = "DELETE FROM events WHERE event_date < CURDATE()";
        return $this->db->conn->query($sql);
    }
    // Tambahkan method ini di class Event:
    public function getTotalEvents() {
        $sql = "SELECT COUNT(*) as total FROM events";
        $result = $this->db->conn->query($sql);
        $data = $result->fetch_assoc();
        return $data['total'];
    }

    public function getTotalParticipants() {
        $sql = "SELECT COUNT(*) as total FROM event_participants";
        $result = $this->db->conn->query($sql);
        $data = $result->fetch_assoc();
        return $data['total'];
    }

    public function getRecentRegistrations($limit = 5) {
        $sql = "SELECT ep.*, u.name, e.title 
                FROM event_participants ep
                JOIN users u ON ep.user_id = u.id
                JOIN events e ON ep.event_id = e.id
                ORDER BY ep.registered_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>