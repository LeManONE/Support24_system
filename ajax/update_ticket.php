<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/logger.php';
requireAdmin();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)$input['id'];
$status = $input['status'];
$priority = $input['priority'] ?? 'medium';
$response = $input['response'] ?? '';

$conn = db();

// Начинаем транзакцию
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
        UPDATE tickets 
        SET status = ?, priority = ?, admin_response = ?, responded_at = IF(? != '', NOW(), responded_at) 
        WHERE id = ?
    ");
    $stmt->bind_param("ssssi", $status, $priority, $response, $response, $id);
    $stmt->execute();
    
    // Если статус resolved, увеличиваем счетчик у админа
    if ($status == 'resolved') {
        $conn->query("UPDATE users SET resolved_tickets = resolved_tickets + 1 WHERE id = {$_SESSION['user_id']}");
    }
    
    $conn->commit();
    
    logAction('TICKET_UPDATE', "Обновлен тикет #$id, статус: $status");
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
