<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Получаем соединение с БД
$conn = db();

$sql = "SELECT password_changed FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user['password_changed']) {
    header('Location: change_password.php');
    exit();
}

if (isAdmin()) {
    header('Location: pages/admin/dashboard.php');
} else {
    header('Location: pages/user/home.php');
}
exit();
?>
