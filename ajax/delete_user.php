<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/logger.php';
requireAdmin();

$id = (int)$_GET['id'];

if ($id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'Нельзя удалить самого себя']);
    exit();
}

$conn = db();
$conn->query("DELETE FROM users WHERE id = $id");

logAction('USER_DELETE', "Удален пользователь ID: $id");
echo json_encode(['success' => true]);
?>
