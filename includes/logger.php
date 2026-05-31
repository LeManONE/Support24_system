<?php
require_once __DIR__ . '/db.php';

class Logger {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        $this->conn = db();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Запись действия в лог
     */
    public function log($action, $details = null, $userId = null, $username = null) {
        $userId = $userId ?? ($_SESSION['user_id'] ?? 0);
        $username = $username ?? ($_SESSION['username'] ?? 'guest');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $this->conn->prepare("
            INSERT INTO logs (user_id, username, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssss", $userId, $username, $action, $details, $ip, $ua);
        return $stmt->execute();
    }
    
    /**
     * Получение логов с фильтрацией
     */
    public function getLogs($limit = 100, $offset = 0, $filters = []) {
        $sql = "SELECT * FROM logs WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND action LIKE ?";
            $params[] = "%{$filters['action']}%";
            $types .= "s";
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Очистка логов (только для admin ID 1)
     */
    public function clearLogs() {
        if ($_SESSION['user_id'] != 1) {
            return false;
        }
        return $this->conn->query("TRUNCATE TABLE logs");
    }
    
    /**
     * Статистика по логам
     */
    public function getStats($days = 7) {
        $sql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                COUNT(DISTINCT user_id) as unique_users
            FROM logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Глобальная функция для удобства
function logAction($action, $details = null) {
    return Logger::getInstance()->log($action, $details);
}
?>
