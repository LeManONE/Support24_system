<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/logger.php';
requireLogin();

$problem = $_POST['problem'] ?? '';
$priority = $_POST['priority'] ?? 'medium';

if (empty($problem)) {
    echo json_encode(['success' => false, 'error' => 'Опишите проблему']);
    exit();
}

$conn = db();
$user_id = $_SESSION['user_id'];

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO tickets (user_id, problem_text, priority) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $problem, $priority);
    $stmt->execute();
    $ticket_id = $conn->insert_id;
    
    $conn->query("UPDATE users SET tickets_count = tickets_count + 1 WHERE id = $user_id");
    
    $conn->commit();
    
    logAction('TICKET_CREATE', "Создан тикет #$ticket_id");
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Ошибка создания тикета']);
}
?>
