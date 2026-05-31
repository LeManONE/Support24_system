<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
requireAdmin();

$id = (int)$_GET['id'];

$conn = db();
$stmt = $conn->prepare("
    SELECT id, username, full_name, phone, telegram_id, local_ip, role, is_active 
    FROM users WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo json_encode(['success' => true, 'user' => $user]);
?>
