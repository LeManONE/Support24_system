<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
requireAdmin();

$ip = $_GET['ip'] ?? '';

if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    echo json_encode(['success' => true, 'result' => '❌ Неверный IP']);
    exit();
}

exec("ping -c 1 -W 2 " . escapeshellarg($ip) . " 2>&1", $output, $return);

if ($return === 0) {
    foreach ($output as $line) {
        if (preg_match('/time=([0-9.]+)/', $line, $matches)) {
            echo json_encode(['success' => true, 'result' => "✅ Онлайн (" . $matches[1] . " ms)"]);
            exit();
        }
    }
    echo json_encode(['success' => true, 'result' => '✅ Онлайн']);
} else {
    echo json_encode(['success' => true, 'result' => '❌ Не пингуется']);
}
?>
