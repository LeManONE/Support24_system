<?php
require_once __DIR__ . '/db.php';

class Auth {
    public static function login($username, $password) {
        $conn = db();
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = TRUE");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Обновляем время последнего входа
            $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update->bind_param("i", $user['id']);
            $update->execute();
            
            return true;
        }
        return false;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
    }
    
    public static function logout() {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        session_destroy();
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: ' . BASE_URL . 'index.php');
            exit();
        }
    }
    
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) return null;
        
        $conn = db();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public static function changePassword($userId, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $conn = db();
        $stmt = $conn->prepare("UPDATE users SET password = ?, password_changed = TRUE WHERE id = ?");
        $stmt->bind_param("si", $hash, $userId);
        return $stmt->execute();
    }
}

// Алиасы для удобства
function isLoggedIn() { return Auth::isLoggedIn(); }
function isAdmin() { return Auth::isAdmin(); }
function requireLogin() { Auth::requireLogin(); }
function requireAdmin() { Auth::requireAdmin(); }
?>
