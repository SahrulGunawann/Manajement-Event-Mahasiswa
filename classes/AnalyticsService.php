<?php
require_once __DIR__ . '/../config/Database.php';

class AnalyticsService {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Get events count per month untuk chart
    public function getEventsPerMonth() {
        $sql = "SELECT 
                    DATE_FORMAT(event_date, '%Y-%m') as month,
                    DATE_FORMAT(event_date, '%M %Y') as month_name,
                    COUNT(*) as count
                FROM events 
                WHERE event_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(event_date, '%Y-%m'), month_name
                ORDER BY month ASC";
        
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get participants count per event
    public function getParticipantsPerEvent() {
        $sql = "SELECT 
                    e.title,
                    COUNT(ep.id) as participant_count
                FROM events e
                LEFT JOIN event_participants ep ON e.id = ep.event_id
                GROUP BY e.id, e.title
                ORDER BY participant_count DESC
                LIMIT 5";
        
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Generate CSV report
    public function generateEventsReport() {
        $sql = "SELECT 
                    e.title,
                    e.event_date,
                    e.location,
                    e.max_participants,
                    COUNT(ep.id) as registered_participants,
                    u.name as created_by
                FROM events e
                LEFT JOIN event_participants ep ON e.id = ep.event_id
                LEFT JOIN users u ON e.created_by = u.id
                GROUP BY e.id, e.title, e.event_date, e.location, e.max_participants, u.name
                ORDER BY e.event_date DESC";
        
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
