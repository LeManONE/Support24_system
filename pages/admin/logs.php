<?php
require_once '../../includes/auth.php';
require_once '../../includes/logger.php';
require_once '../../includes/functions.php';
requireAdmin();

$logger = Logger::getInstance();
$page = (int)($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'action' => $_GET['action'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null
];

$logs = $logger->getLogs($limit, $offset, $filters);
$stats = $logger->getStats(14);

// Получаем список пользователей для фильтра
$conn = db();
$users = $conn->query("SELECT id, username FROM users ORDER BY username");
?>
<?php include '../../includes/header.php'; ?>

<div class="logs-page">
    <h1>📋 Системные логи</h1>
    
    <?php if ($_SESSION['user_id'] == 1): ?>
    <div class="admin-controls">
        <button class="btn btn-danger" onclick="clearLogs()">🧹 Очистить все логи</button>
    </div>
    <?php endif; ?>
    
    <!-- Статистика -->
    <div class="stats-grid">
        <?php foreach ($stats as $stat): ?>
        <div class="stat-card">
            <div class="stat-date"><?php echo $stat['date']; ?></div>
            <div class="stat-value"><?php echo $stat['total']; ?></div>
            <div class="stat-users">👥 <?php echo $stat['unique_users']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Фильтры -->
    <div class="filters">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label>Пользователь</label>
                <select name="user_id">
                    <option value="">Все</option>
                    <?php while($user = $users->fetch_assoc()): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo ($filters['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['username']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Действие</label>
                <input type="text" name="action" placeholder="Например: LOGIN" value="<?php echo htmlspecialchars($filters['action'] ?? ''); ?>">
            </div>
            
            <div class="filter-group">
                <label>Дата с</label>
                <input type="date" name="date_from" value="<?php echo $filters['date_from']; ?>">
            </div>
            
            <div class="filter-group">
                <label>Дата по</label>
                <input type="date" name="date_to" value="<?php echo $filters['date_to']; ?>">
            </div>
            
            <button type="submit" class="btn">🔍 Применить</button>
            <a href="?" class="btn">✖ Сброс</a>
        </form>
    </div>
    
    <!-- Таблица логов -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Время</th>
                    <th>Пользователь</th>
                    <th>Действие</th>
                    <th>Детали</th>
                    <th>IP</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>#<?php echo $log['id']; ?></td>
                    <td><?php echo formatDate($log['created_at']); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                        <?php if ($log['user_id']): ?>
                        <small>(ID: <?php echo $log['user_id']; ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo strtolower(str_replace('_', '-', $log['action'])); ?>">
                            <?php echo $log['action']; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                    <td><?php echo $log['ip_address']; ?></td>
                    <td><small><?php echo htmlspecialchars(substr($log['user_agent'] ?? '', 0, 50)); ?>...</small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Пагинация -->
    <div class="pagination">
        <a href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($filters); ?>" 
           class="btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>">← Назад</a>
        <span class="page-info">Страница <?php echo $page; ?></span>
        <a href="?page=<?php echo $page+1; ?>&<?php echo http_build_query($filters); ?>" class="btn">Вперед →</a>
    </div>
</div>

<script>
function clearLogs() {
    if (confirm('⚠️ Точно очистить все логи? Это действие необратимо.')) {
        fetch('/ajax/clear_logs.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Логи очищены', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Ошибка: ' + data.error, 'error');
                }
            });
    }
}
</script>

<style>
.logs-page { padding: 20px; }

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.stat-date { font-size: 12px; color: #999; }
.stat-value { font-size: 24px; font-weight: bold; color: #667eea; }
.stat-users { font-size: 11px; color: #666; }

.admin-controls { margin-bottom: 20px; text-align: right; }

.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.badge.login-success { background: #28a745; color: white; }
.badge.login-failed { background: #dc3545; color: white; }
.badge.logout { background: #6c757d; color: white; }
.badge.ticket-create { background: #007bff; color: white; }
.badge.ticket-update { background: #ffc107; color: #333; }
.badge.ticket-delete { background: #dc3545; color: white; }
.badge.user-create { background: #17a2b8; color: white; }
.badge.user-delete { background: #dc3545; color: white; }

.pagination {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 30px;
    align-items: center;
}
</style>

<?php include '../../includes/footer.php'; ?>
