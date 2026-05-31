<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
// requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID не указан']);
    exit();
}

$conn = db();
$stmt = $conn->prepare("
    SELECT t.*, u.full_name, u.local_ip 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if ($ticket) {
    echo json_encode(['success' => true, 'ticket' => $ticket]);
} else {
    echo json_encode(['success' => false, 'error' => 'Тикет не найден']);
}
?>
