<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/logger.php';
requireAdmin();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)$input['id'];

$hash = password_hash('P@ssw0rd', PASSWORD_DEFAULT);

$conn = db();
$stmt = $conn->prepare("UPDATE users SET password = ?, password_changed = FALSE WHERE id = ?");
$stmt->bind_param("si", $hash, $id);

if ($stmt->execute()) {
    logAction('PASSWORD_RESET', "Сброшен пароль пользователя ID: $id");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ошибка сброса пароля']);
}
?>
