<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/logger.php';
requireAdmin();

$id = (int)$_GET['id'];

$conn = db();
$conn->query("DELETE FROM tickets WHERE id = $id");

logAction('TICKET_DELETE', "Удален тикет #$id");
echo json_encode(['success' => true]);
?>
