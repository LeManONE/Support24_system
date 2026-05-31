<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
requireAdmin();

$id = (int)$_POST['id'];
$full_name = $_POST['full_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$telegram_id = $_POST['telegram_id'] ?? '';
$local_ip = $_POST['local_ip'] ?? '';
$role = isset($_POST['is_admin']) ? 'admin' : 'user';
$is_active = isset($_POST['is_active']) ? 1 : 0;

if (!$id || empty($full_name)) {
    echo json_encode(['success' => false, 'error' => 'Некорректные данные']);
    exit();
}

$conn = db();
$stmt = $conn->prepare("
    UPDATE users 
    SET full_name = ?, phone = ?, telegram_id = ?, local_ip = ?, role = ?, is_active = ? 
    WHERE id = ?
");
$stmt->bind_param("sssssii", $full_name, $phone, $telegram_id, $local_ip, $role, $is_active, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ошибка обновления']);
}
?>
