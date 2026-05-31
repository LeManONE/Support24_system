<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/logger.php';
requireAdmin();

$full_name = $_POST['full_name'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? 'P@ssw0rd';
$phone = $_POST['phone'] ?? '';
$telegram_id = $_POST['telegram_id'] ?? '';
$local_ip = $_POST['local_ip'] ?? '';
$role = isset($_POST['is_admin']) ? 'admin' : 'user';
$is_active = isset($_POST['is_active']) ? 1 : 0;

if (empty($full_name) || empty($username)) {
    echo json_encode(['success' => false, 'error' => 'Заполните обязательные поля']);
    exit();
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$conn = db();

// Проверка уникальности логина
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Логин уже занят']);
    exit();
}

$stmt = $conn->prepare("
    INSERT INTO users (full_name, username, password, phone, telegram_id, local_ip, role, is_active, password_changed) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, FALSE)
");
$stmt->bind_param("sssssssi", $full_name, $username, $hash, $phone, $telegram_id, $local_ip, $role, $is_active);

if ($stmt->execute()) {
    logAction('USER_CREATE', "Создан пользователь $username");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
}
?>
