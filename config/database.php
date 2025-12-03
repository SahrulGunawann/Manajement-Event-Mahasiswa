<?php
class Database {
    private $host;
    private $username;
    private $password;
    private $database;
    public $conn;

    public function __construct() {
        // Load environment variables
        require_once __DIR__ . '/env.php';
        loadEnvironmentVariables();

        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->username = getenv('DB_USERNAME') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->database = getenv('DB_NAME') ?: 'event_mahasiswa';

        $this->connect();
    }

    private function connect() {
        $this->conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
