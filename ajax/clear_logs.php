<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/logger.php';
requireAdmin();

if ($_SESSION['user_id'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Недостаточно прав']);
    exit();
}

$logger = Logger::getInstance();
if ($logger->clearLogs()) {
    $logger->log('LOGS_CLEAR', 'Все логи были очищены');
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ошибка очистки']);
}
?>
