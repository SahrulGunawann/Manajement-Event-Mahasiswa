<?php
require_once 'config/database.php';

try {
    $db = new Database();
    echo "✅ Koneksi database BERHASIL!";
    echo "<br>Database: " . getenv('DB_NAME');
    echo "<br>Folder Project: Final-Project-Manajement-event-mahasiswa";
    
    // Test query
    $result = $db->conn->query("SELECT COUNT(*) as total FROM users");
    $data = $result->fetch_assoc();
    echo "<br>Total users: " . $data['total'];
    
    // Test events
    $result2 = $db->conn->query("SELECT COUNT(*) as total FROM events");
    $data2 = $result2->fetch_assoc();
    echo "<br>Total events: " . $data2['total'];
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>