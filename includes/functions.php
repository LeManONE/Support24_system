<?php
/**
 * Вспомогательные функции
 */

function redirect($url) {
    header("Location: $url");
    exit();
}

function old($field, $default = '') {
    return $_POST[$field] ?? $default;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function formatDate($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

function truncate($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function getStatusBadge($status) {
    $badges = [
        'new' => 'badge-danger',
        'in_progress' => 'badge-warning',
        'resolved' => 'badge-success'
    ];
    $class = $badges[$status] ?? 'badge-secondary';
    return "<span class='badge $class'>$status</span>";
}

function ping($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return 'Неверный IP';
    }
    
    $output = [];
    $return = 0;
    exec("ping -c 1 -W 2 " . escapeshellarg($ip) . " 2>&1", $output, $return);
    
    if ($return === 0) {
        foreach ($output as $line) {
            if (preg_match('/time=([0-9.]+)/', $line, $matches)) {
                return "✅ Онлайн (" . $matches[1] . " ms)";
            }
        }
        return "✅ Онлайн";
    }
    return "❌ Не пингуется";
}
?>
