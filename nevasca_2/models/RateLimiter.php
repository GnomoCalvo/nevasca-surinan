<?php
class RateLimiter {
    private $conn;
    private $table_name = "login_attempts";
    private $maxAttempts = 5;
    private $lockoutTime = 900; // 15 minutos

    public function __construct($db) {
        $this->conn = $db;
        $this->createTable();
    }

    private function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            attempts INT DEFAULT 1,
            last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (ip_address)
        )";
        $this->conn->exec($query);
    }

    public function isBlocked($ip) {
        $query = "SELECT attempts, last_attempt FROM " . $this->table_name . "
                 WHERE ip_address = :ip AND 
                 last_attempt > DATE_SUB(NOW(), INTERVAL " . $this->lockoutTime . " SECOND)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ip", $ip);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['attempts'] >= $this->maxAttempts;
    }

    public function incrementAttempts($ip) {
        $query = "INSERT INTO " . $this->table_name . " (ip_address, attempts)
                 VALUES (:ip, 1)
                 ON DUPLICATE KEY UPDATE 
                 attempts = IF(last_attempt > DATE_SUB(NOW(), INTERVAL " . $this->lockoutTime . " SECOND),
                             attempts + 1, 1),
                 last_attempt = CURRENT_TIMESTAMP";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ip", $ip);
        $stmt->execute();
        
        return $this->getAttempts($ip);
    }

    public function getAttempts($ip) {
        $query = "SELECT attempts FROM " . $this->table_name . "
                 WHERE ip_address = :ip AND 
                 last_attempt > DATE_SUB(NOW(), INTERVAL " . $this->lockoutTime . " SECOND)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ip", $ip);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['attempts'] : 0;
    }

    public function resetAttempts($ip) {
        $query = "DELETE FROM " . $this->table_name . " WHERE ip_address = :ip";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ip", $ip);
        $stmt->execute();
    }

    public function cleanOldAttempts() {
        $query = "DELETE FROM " . $this->table_name . "
                 WHERE last_attempt < DATE_SUB(NOW(), INTERVAL " . $this->lockoutTime . " SECOND)";
        $this->conn->exec($query);
    }
}
?>