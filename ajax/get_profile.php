<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit();
}

$conn = db();
$stmt = $conn->prepare("SELECT id, username, full_name, phone, telegram_id, local_ip, role, created_at, tickets_count, resolved_tickets FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo json_encode(['success' => true, 'user' => $user]);
?>
