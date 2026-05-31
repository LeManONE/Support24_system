<?php
require_once 'includes/logger.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

logAction('LOGOUT', 'Пользователь вышел из системы');
Auth::logout();
header('Location: login.php');
exit();
?>
