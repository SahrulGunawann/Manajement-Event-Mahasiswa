<?php
require_once __DIR__ . '/../config/database.php';

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

    // Get time series data untuk trend (sesuai ketentuan)
    public function getEventTrendData($months = 6) {
        $sql = "SELECT 
                    DATE_FORMAT(e.event_date, '%Y-%m') as period,
                    DATE_FORMAT(e.event_date, '%M %Y') as period_name,
                    COUNT(*) as events_count,
                    SUM(CASE WHEN ep.id IS NOT NULL THEN 1 ELSE 0 END) as total_participants
                FROM events e
                LEFT JOIN event_participants ep ON e.id = ep.event_id
                WHERE e.event_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(e.event_date, '%Y-%m'), period_name
                ORDER BY period ASC";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $months);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get participants per event category (sesuai ketentuan)
    public function getParticipantsByCategory() {
        $sql = "SELECT 
                    'Event Category' as category_type,
                    e.title as item_name,
                    COUNT(ep.id) as participant_count,
                    e.max_participants
                FROM events e
                LEFT JOIN event_participants ep ON e.id = ep.event_id
                GROUP BY e.id, e.title, e.max_participants
                ORDER BY participant_count DESC
                LIMIT 10";
        
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get monthly participation trend
    public function getMonthlyParticipationTrend() {
        $sql = "SELECT 
                    DATE_FORMAT(e.event_date, '%Y-%m') as month,
                    DATE_FORMAT(e.event_date, '%M %Y') as month_name,
                    COUNT(DISTINCT e.id) as events_count,
                    COUNT(DISTINCT ep.user_id) as unique_participants,
                    COUNT(ep.id) as total_registrations
                FROM events e
                LEFT JOIN event_participants ep ON e.id = ep.event_id
                WHERE e.event_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(e.event_date, '%Y-%m'), month_name
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

    // Analisis dan rekomendasi (sesuai ketentuan PDF) - Versi aman
    public function getAnalyticsSummary() {
        $summary = [];
        
        // Total events
        $sql = "SELECT COUNT(*) as total FROM events WHERE event_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $result = $this->db->conn->query($sql);
        $summary['events_this_month'] = $result->fetch_assoc()['total'];
        
        // Total participants - cek dulu apakah kolom registered_at ada
        $checkColumn = "SHOW COLUMNS FROM event_participants LIKE 'registered_at'";
        $columnResult = $this->db->conn->query($checkColumn);
        $hasRegisteredAt = $columnResult->num_rows > 0;
        
        if ($hasRegisteredAt) {
            $sql = "SELECT COUNT(DISTINCT user_id) as total FROM event_participants WHERE registered_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        } else {
            // Fallback: gunakan semua data jika kolom tidak ada
            $sql = "SELECT COUNT(DISTINCT user_id) as total FROM event_participants";
        }
        $result = $this->db->conn->query($sql);
        $summary['active_participants'] = $result->fetch_assoc()['total'];
        
        // Most popular event type
        $sql = "SELECT 
                    SUBSTRING_INDEX(e.title, ' ', 2) as event_type,
                    COUNT(*) as count
                FROM events e
                WHERE e.event_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                GROUP BY SUBSTRING_INDEX(e.title, ' ', 2)
                ORDER BY count DESC
                LIMIT 1";
        $result = $this->db->conn->query($sql);
        $popular = $result->fetch_assoc();
        $summary['popular_event_type'] = $popular['event_type'] ?? 'Tidak ada';
        
        // Average participants per event
        $sql = "SELECT AVG(participant_count) as avg_participants
                FROM (
                    SELECT COUNT(ep.id) as participant_count
                    FROM events e
                    LEFT JOIN event_participants ep ON e.id = ep.event_id
                    WHERE e.event_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                    GROUP BY e.id
                ) as event_stats";
        $result = $this->db->conn->query($sql);
        $avg = $result->fetch_assoc();
        $summary['avg_participants'] = round($avg['avg_participants'] ?? 0, 1);
        
        return $summary;
    }

  // Rekomendasi event berdasarkan analisis - Versi sederhana
    public function getRecommendations() {
        $recommendations = [];
        
        // Analisis event dengan partisipasi rendah
        $sql = "SELECT e.title, COUNT(ep.id) as participants, e.max_participants
                FROM events e
                LEFT JOIN event_participants ep ON e.id = ep.event_id
                WHERE e.event_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                  AND e.max_participants > 0
                GROUP BY e.id, e.title, e.max_participants
                HAVING participants < (e.max_participants * 0.5)
                ORDER BY participants ASC
                LIMIT 3";
        $result = $this->db->conn->query($sql);
        $lowParticipation = $result->fetch_all(MYSQLI_ASSOC);
        
        if (!empty($lowParticipation)) {
            $recommendations[] = [
                'type' => 'low_participation',
                'message' => 'Event dengan partisipasi rendah: ' . implode(', ', array_column($lowParticipation, 'title')),
                'action' => 'Tingkatkan promosi atau periksa jadwal event'
            ];
        }
        
        // Analisis waktu terbaik untuk event - FIX QUERY
        $sql = "SELECT 
                    DAYOFWEEK(e.event_date) as day_of_week,
                    COUNT(ep.id) as total_participants,
                    COUNT(DISTINCT e.id) as event_count
                FROM events e
                LEFT JOIN event_participants ep ON e.id = ep.event_id
                WHERE e.event_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                GROUP BY DAYOFWEEK(e.event_date)
                HAVING event_count > 0
                ORDER BY total_participants DESC
                LIMIT 1";
        $result = $this->db->conn->query($sql);
        $bestDay = $result->fetch_assoc();
        
        if ($bestDay) {
            $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $recommendations[] = [
                'type' => 'optimal_timing',
                'message' => 'Hari terbaik untuk event: ' . $dayNames[$bestDay['day_of_week'] - 1],
                'action' => 'Jadwalkan event penting pada hari ini'
            ];
        }
        
        // Rekomendasi kapasitas - FIX QUERY
        $sql = "SELECT 
                    AVG(participant_count) as current_avg,
                    AVG(max_participants) as max_avg
                FROM (
                    SELECT 
                        COUNT(ep.id) as participant_count,
                        e.max_participants
                    FROM events e
                    LEFT JOIN event_participants ep ON e.id = ep.event_id
                    WHERE e.event_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                      AND e.max_participants > 0
                    GROUP BY e.id, e.max_participants
                ) as event_stats";
        $result = $this->db->conn->query($sql);
        $capacity = $result->fetch_assoc();
        
        if ($capacity && $capacity['current_avg'] < $capacity['max_avg'] * 0.7) {
            $recommendations[] = [
                'type' => 'capacity_optimization',
                'message' => 'Kapasitas event terlalu besar (rata-rata ' . round($capacity['current_avg']) . ' dari ' . round($capacity['max_avg']) . ')',
                'action' => 'Pertimbangkan untuk mengurangi kapasitas atau meningkatkan promosi'
            ];
        }
        
        return $recommendations;
    }
}
?>