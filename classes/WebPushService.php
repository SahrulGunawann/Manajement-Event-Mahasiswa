<?php
require_once __DIR__ . '/../config/Database.php';

class WebPushService {
    private $db;
    private $vapidPublicKey;
    private $vapidPrivateKey;

    public function __construct() {
        $this->db = new Database();
        // VAPID keys untuk Web Push
        $this->vapidPublicKey = getenv('VAPID_PUBLIC_KEY') ?: 'BMz5sJv_9wA6kLj8zKx7n3Q4rT2yF5uX8wZ1aB2cD3eF4gH5iJ6kL7mN8oP9qR0sT';
        $this->vapidPrivateKey = getenv('VAPID_PRIVATE_KEY') ?: 'zX9sJv_9wA6kLj8zKx7n3Q4rT2yF5uX8wZ1aB2cD3eF4gH5iJ6kL7mN8oP9qR0sT';
    }

    // Simpan subscription user
    public function saveSubscription($userId, $endpoint, $publicKey, $authToken) {
        // Check if subscription already exists
        $checkSql = "SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?";
        $checkStmt = $this->db->conn->prepare($checkSql);
        $checkStmt->bind_param("is", $userId, $endpoint);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            // Update existing subscription
            $updateSql = "UPDATE push_subscriptions SET public_key = ?, auth_token = ?, updated_at = NOW() WHERE user_id = ? AND endpoint = ?";
            $updateStmt = $this->db->conn->prepare($updateSql);
            $updateStmt->bind_param("ssis", $publicKey, $authToken, $userId, $endpoint);
            return $updateStmt->execute();
        } else {
            // Insert new subscription
            $insertSql = "INSERT INTO push_subscriptions (user_id, endpoint, public_key, auth_token) VALUES (?, ?, ?, ?)";
            $insertStmt = $this->db->conn->prepare($insertSql);
            $insertStmt->bind_param("isss", $userId, $endpoint, $publicKey, $authToken);
            return $insertStmt->execute();
        }
    }

    // Kirim push notification ke user
    public function sendPushNotification($userId, $title, $message, $data = []) {
        // Get user subscriptions
        $sql = "SELECT * FROM push_subscriptions WHERE user_id = ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $subscriptions = $result->fetch_all(MYSQLI_ASSOC);

        $successCount = 0;
        foreach ($subscriptions as $subscription) {
            if ($this->sendWebPush($subscription, $title, $message, $data)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    // Kirim push notification ke multiple users
    public function sendBulkPushNotification($userIds, $title, $message, $data = []) {
        $totalSuccess = 0;
        foreach ($userIds as $userId) {
            $totalSuccess += $this->sendPushNotification($userId, $title, $message, $data);
        }
        return $totalSuccess;
    }

    // Kirim Web Push menggunakan cURL
    private function sendWebPush($subscription, $title, $message, $data = []) {
        $payload = json_encode([
            'title' => $title,
            'message' => $message,
            'icon' => '/assets/img/logo.png',
            'badge' => '/assets/img/badge.png',
            'data' => $data,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Lihat Detail'
                ]
            ]
        ]);

        $headers = [
            'Content-Type: application/json',
            'TTL: 86400', // 24 hours
            'Content-Length: ' . strlen($payload)
        ];

        // VAPID Authentication (simplified)
        $vapidHeader = $this->generateVAPIDHeader($subscription['endpoint']);
        if ($vapidHeader) {
            $headers[] = 'Authorization: ' . $vapidHeader;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $subscription['endpoint']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log hasil
        $this->logPushAttempt($subscription['user_id'], $subscription['endpoint'], $httpCode, $response);

        return $httpCode >= 200 && $httpCode < 300;
    }

    // Generate VAPID header (simplified version)
    private function generateVAPIDHeader($endpoint) {
        // This is a simplified version
        // In production, use proper Web Push library like minishlink/web-push
        $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
        $expiration = time() + 43200; // 12 hours
        
        $token = json_encode([
            'aud' => $audience,
            'exp' => $expiration,
            'sub' => 'mailto:admin@campus.edu'
        ]);

        // Simplified signature (use proper JWT in production)
        $signature = base64_encode(hash_hmac('sha256', $token, $this->vapidPrivateKey, true));
        
        return 'vapid t=' . base64_encode($token) . ', k=' . $this->vapidPublicKey;
    }

    // Log push attempt
    private function logPushAttempt($userId, $endpoint, $httpCode, $response) {
        $sql = "INSERT INTO push_logs (user_id, endpoint, http_code, response, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("isis", $userId, $endpoint, $httpCode, $response);
        $stmt->execute();
    }

    // Get VAPID public key untuk frontend
    public function getVAPIDPublicKey() {
        return $this->vapidPublicKey;
    }
}
?>