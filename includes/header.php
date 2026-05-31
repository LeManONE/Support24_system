<?php
require_once __DIR__ . '/auth.php';

// Текущая страница для подсветки меню
$current_page = basename($_SERVER['PHP_SELF']);
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    $current_page = 'admin_' . basename($_SERVER['PHP_SELF']);
}

// Количество новых тикетов для админа
$new_tickets_count = 0;
if (isAdmin()) {
    $conn = db();
    $result = $conn->query("SELECT COUNT(*) as count FROM tickets WHERE status = 'new'");
    $new_tickets_count = $result->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Техническая поддержка</title>
    <link rel="stylesheet" href="/styles/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="nav-left">
                <?php if (isAdmin()): ?>
                    <a href="<?php echo BASE_URL; ?>pages/admin/dashboard.php" 
                       class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
                        Главная 
                        <?php if ($new_tickets_count > 0): ?>
                            <span class="badge"><?php echo $new_tickets_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>pages/admin/users.php" 
                       class="<?php echo ($current_page == 'admin_users.php') ? 'active' : ''; ?>">
                        Пользователи
                    </a>
                    <a href="<?php echo BASE_URL; ?>pages/admin/logs.php" 
                       class="<?php echo ($current_page == 'admin_logs.php') ? 'active' : ''; ?>">
                        📋 Логи
                    </a>
                    <a href="<?php echo BASE_URL; ?>pages/admin/monitoring.php" 
                       class="<?php echo ($current_page == 'admin_monitoring.php') ? 'active' : ''; ?>">
                        📊 Мониторинг
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>pages/user/home.php" 
                       class="<?php echo ($current_page == 'user_home.php') ? 'active' : ''; ?>">
                        Главная
                    </a>
                    <a href="<?php echo BASE_URL; ?>pages/user/faq.php" 
                       class="<?php echo ($current_page == 'user_faq.php') ? 'active' : ''; ?>">
                        📚 Частые проблемы
                    </a>
                <?php endif; ?>
            </div>
            <div class="nav-right">
                <a href="#" onclick="openProfilePopup(); return false;">👤 Профиль</a>
                <a href="<?php echo BASE_URL; ?>logout.php">🚪 Выйти</a>
            </div>
        </nav>
    </header>
    <main>
