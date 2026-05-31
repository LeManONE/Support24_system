<?php
require_once '../../includes/auth.php';
require_once '../../includes/logger.php';
require_once '../../includes/functions.php';
requireAdmin();

$conn = db();

// Обработка массовых действий
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $selected = $_POST['selected'] ?? [];
    if (!empty($selected)) {
        $ids = implode(',', array_map('intval', $selected));
        switch ($_POST['bulk_action']) {
            case 'delete':
                $conn->query("DELETE FROM tickets WHERE id IN ($ids)");
                logAction('BULK_DELETE', "Удалены тикеты: $ids");
                break;
            case 'resolve':
                $conn->query("UPDATE tickets SET status = 'resolved', responded_at = NOW() WHERE id IN ($ids)");
                logAction('BULK_RESOLVE', "Отмечены решенными тикеты: $ids");
                break;
        }
        header('Location: dashboard.php');
        exit();
    }
}

// Фильтры
$where = ["1=1"];
$params = [];
$types = "";

if (!empty($_GET['status'])) {
    $where[] = "t.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

if (!empty($_GET['user_id'])) {
    $where[] = "t.user_id = ?";
    $params[] = $_GET['user_id'];
    $types .= "i";
}

if (!empty($_GET['priority'])) {
    $where[] = "t.priority = ?";
    $params[] = $_GET['priority'];
    $types .= "s";
}

if (!empty($_GET['date_from'])) {
    $where[] = "DATE(t.created_at) >= ?";
    $params[] = $_GET['date_from'];
    $types .= "s";
}

if (!empty($_GET['date_to'])) {
    $where[] = "DATE(t.created_at) <= ?";
    $params[] = $_GET['date_to'];
    $types .= "s";
}

if (!empty($_GET['search'])) {
    $where[] = "(t.problem_text LIKE ? OR t.admin_response LIKE ?)";
    $search = "%{$_GET['search']}%";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

$sql = "SELECT t.*, u.full_name, u.local_ip, u.username 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE " . implode(" AND ", $where) . "
        ORDER BY 
            CASE t.priority 
                WHEN 'high' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'low' THEN 3 
            END,
            t.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Пользователи для фильтра
$users = $conn->query("SELECT id, full_name FROM users WHERE role = 'user' ORDER BY full_name");
?>
<?php include '../../includes/header.php'; ?>

<div class="dashboard">
    <h1>Управление заявками</h1>
    
    <!-- Фильтры -->
    <div class="filters">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label>Статус</label>
                <select name="status">
                    <option value="">Все</option>
                    <option value="new" <?php echo (isset($_GET['status']) && $_GET['status'] == 'new') ? 'selected' : ''; ?>>🟡 Новые</option>
                    <option value="in_progress" <?php echo (isset($_GET['status']) && $_GET['status'] == 'in_progress') ? 'selected' : ''; ?>>🔵 В работе</option>
                    <option value="resolved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'resolved') ? 'selected' : ''; ?>>🟢 Решенные</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Приоритет</label>
                <select name="priority">
                    <option value="">Все</option>
                    <option value="high" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'high') ? 'selected' : ''; ?>>🔴 Высокий</option>
                    <option value="medium" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'medium') ? 'selected' : ''; ?>>🟡 Средний</option>
                    <option value="low" <?php echo (isset($_GET['priority']) && $_GET['priority'] == 'low') ? 'selected' : ''; ?>>🟢 Низкий</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Пользователь</label>
                <select name="user_id">
                    <option value="">Все</option>
                    <?php while($user = $users->fetch_assoc()): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Дата с</label>
                <input type="date" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>">
            </div>
            
            <div class="filter-group">
                <label>Дата по</label>
                <input type="date" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>">
            </div>
            
            <div class="filter-group">
                <label>Поиск</label>
                <input type="text" name="search" placeholder="Текст проблемы..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="btn">🔍 Применить</button>
            <a href="?" class="btn">✖ Сброс</a>
        </form>
    </div>
    
    <!-- Массовые действия -->
    <form method="POST" id="bulkForm">
        <div class="bulk-actions">
            <select name="bulk_action" id="bulkAction">
                <option value="">Массовые действия</option>
                <option value="delete">🗑 Удалить выбранные</option>
                <option value="resolve">✅ Отметить решенными</option>
            </select>
            <button type="button" class="btn" onclick="executeBulkAction()">Применить</button>
        </div>
    
        <!-- Список тикетов -->
        <div class="tickets-list">
            <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <p>📭 Нет заявок по заданным критериям</p>
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-card <?php echo $ticket['status']; ?> priority-<?php echo $ticket['priority']; ?>">
                    <div class="ticket-header">
                        <div class="ticket-checkbox">
                            <input type="checkbox" name="selected[]" value="<?php echo $ticket['id']; ?>" form="bulkForm">
                        </div>
                        <span class="ticket-id">#<?php echo $ticket['id']; ?></span>
                        <span class="ticket-user"><?php echo htmlspecialchars($ticket['full_name']); ?></span>
                        <span class="ticket-time"><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></span>
                        <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                            <?php echo $ticket['priority']; ?>
                        </span>
                        <span class="status-badge status-<?php echo $ticket['status']; ?>">
                            <?php 
                            $statuses = ['new' => 'Новая', 'in_progress' => 'В работе', 'resolved' => 'Решена'];
                            echo $statuses[$ticket['status']];
                            ?>
                        </span>
                    </div>
                    <div class="ticket-preview">
                        <?php echo nl2br(htmlspecialchars(substr($ticket['problem_text'], 0, 150) . '...')); ?>
                    </div>
                    <div class="ticket-actions">
                    	<a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-small">👁 Просмотр</a>
                        <button class="btn-small btn-danger" onclick="deleteTicket(<?php echo $ticket['id']; ?>)">🗑 Удалить</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Попап тикета -->
<div class="popup" id="ticketPopup">
    <span class="close-btn" onclick="closeAllPopups()">&times;</span>
    <h2>Детали заявки</h2>
    <div id="ticketDetails"></div>
</div>

<script>
function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        let d = new Date(dateString);
        return d.toLocaleString('ru-RU');
    } catch(e) {
        return dateString;
    }
}

function closeAllPopups() {
    document.getElementById('ticketPopup').classList.remove('active');
    var overlay = document.getElementById('popupOverlay');
    if (overlay) overlay.classList.remove('active');
}

function openTicket(id) {
    console.log('openTicket called with id:', id);
    
    fetch('/ajax/get_ticket.php?id=' + id)
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            console.log('Response:', data);
            if (data.success) {
                var t = data.ticket;
                var html = '<div class="ticket-details">' +
                    '<div class="info-row"><span class="label">Пользователь:</span><span class="value">' + escapeHtml(t.full_name) + ' (ID: ' + t.user_id + ')</span></div>' +
                    '<div class="info-row"><span class="label">IP адрес:</span><span class="value">' + escapeHtml(t.local_ip || 'не указан') + ' <button class="btn-small" onclick="pingIP(\'' + escapeHtml(t.local_ip) + '\')">📡 Пинг</button> <span id="pingResult"></span></span></div>' +
                    '<div class="info-row"><span class="label">Статус:</span><span class="value"><select id="statusSelect" class="form-control">' +
                    '<option value="new" ' + (t.status == 'new' ? 'selected' : '') + '>🟡 Новая</option>' +
                    '<option value="in_progress" ' + (t.status == 'in_progress' ? 'selected' : '') + '>🔵 В работе</option>' +
                    '<option value="resolved" ' + (t.status == 'resolved' ? 'selected' : '') + '>🟢 Решена</option>' +
                    '</select></span></div>' +
                    '<div class="info-row"><span class="label">Приоритет:</span><span class="value"><select id="prioritySelect" class="form-control">' +
                    '<option value="low" ' + (t.priority == 'low' ? 'selected' : '') + '>🟢 Низкий</option>' +
                    '<option value="medium" ' + (t.priority == 'medium' ? 'selected' : '') + '>🟡 Средний</option>' +
                    '<option value="high" ' + (t.priority == 'high' ? 'selected' : '') + '>🔴 Высокий</option>' +
                    '</select></span></div>' +
                    '<div class="info-row"><span class="label">Время создания:</span><span class="value">' + formatDate(t.created_at) + '</span></div>' +
                    '<div class="info-row"><span class="label">Проблема:</span><div class="problem-text">' + escapeHtml(t.problem_text).replace(/\n/g, '<br>') + '</div></div>' +
                    '<div class="info-row"><span class="label">Ответ:</span><textarea id="responseText" class="form-control" rows="5">' + escapeHtml(t.admin_response || '') + '</textarea></div>' +
                    '<div class="ticket-actions"><button class="btn btn-success" onclick="saveTicket(' + t.id + ')">💾 Сохранить</button>' +
                    '<button class="btn btn-danger" onclick="deleteTicket(' + t.id + ')">🗑 Удалить</button></div>' +
                    '</div>';
                document.getElementById('ticketDetails').innerHTML = html;
                document.getElementById('ticketPopup').classList.add('active');
                var overlay = document.getElementById('popupOverlay');
                if (overlay) overlay.classList.add('active');
            } else {
                alert('Ошибка загрузки тикета: ' + (data.error || 'неизвестная ошибка'));
            }
        })
        .catch(function(error) {
            console.error('Fetch error:', error);
            alert('Ошибка соединения: ' + error);
        });
}

function saveTicket(id) {
    var status = document.getElementById('statusSelect').value;
    var priority = document.getElementById('prioritySelect').value;
    var response = document.getElementById('responseText').value;
    
    fetch('/ajax/update_ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, status: status, priority: priority, response: response })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            alert('Тикет обновлен');
            location.reload();
        } else {
            alert('Ошибка сохранения');
        }
    });
}

function deleteTicket(id) {
    if (confirm('Удалить тикет?')) {
        fetch('/ajax/delete_ticket.php?id=' + id, { method: 'DELETE' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    alert('Тикет удален');
                    location.reload();
                }
            });
    }
}

function pingIP(ip) {
    if (!ip || ip === 'не указан' || ip === 'null') {
        var resultSpan = document.getElementById('pingResult');
        if (resultSpan) resultSpan.textContent = '❌ IP не указан';
        return;
    }
    
    var resultSpan = document.getElementById('pingResult');
    if (resultSpan) resultSpan.textContent = '⏳ Пингую...';
    
    fetch('/ajax/ping.php?ip=' + encodeURIComponent(ip))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (resultSpan) resultSpan.textContent = data.result;
        });
}

function executeBulkAction() {
    var action = document.getElementById('bulkAction').value;
    if (!action) {
        alert('Выберите действие');
        return;
    }
    
    var checkboxes = document.querySelectorAll('input[name="selected[]"]:checked');
    if (checkboxes.length === 0) {
        alert('Выберите тикеты');
        return;
    }
    
    if (confirm('Применить действие к ' + checkboxes.length + ' тикетам?')) {
        document.getElementById('bulkForm').submit();
    }
}
</script>

<style>
.dashboard { padding: 20px; }

.filters {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filters-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-size: 12px;
    font-weight: bold;
    color: #666;
}

.filter-group select,
.filter-group input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.bulk-actions {
    margin: 20px 0;
    display: flex;
    gap: 10px;
}

.ticket-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border-left: 4px solid;
    transition: transform 0.2s;
}

.ticket-card:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.ticket-card.new { border-left-color: #dc3545; background-color: #fff5f5; }
.ticket-card.in_progress { border-left-color: #ffc107; background-color: #fff9e6; }
.ticket-card.resolved { border-left-color: #28a745; background-color: #f0fff0; }

.ticket-card.priority-high { border-left-width: 6px; }
.ticket-card.priority-medium { border-left-width: 4px; }
.ticket-card.priority-low { border-left-width: 2px; }

.ticket-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.ticket-checkbox { margin-right: 5px; }

.ticket-id {
    font-weight: bold;
    color: #667eea;
}

.ticket-user { font-weight: 500; }

.ticket-time {
    color: #999;
    font-size: 12px;
}

.priority-badge {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-badge.priority-high { background: #dc3545; color: white; }
.priority-badge.priority-medium { background: #ffc107; color: #333; }
.priority-badge.priority-low { background: #28a745; color: white; }

.status-badge {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.status-new { background: #dc3545; color: white; }
.status-badge.status-in_progress { background: #ffc107; color: #333; }
.status-badge.status-resolved { background: #28a745; color: white; }

.ticket-preview {
    color: #666;
    margin: 10px 0;
    line-height: 1.5;
}

.ticket-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.empty-state {
    text-align: center;
    padding: 60px;
    background: white;
    border-radius: 10px;
    color: #999;
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
}

.btn-small {
    padding: 5px 10px;
    font-size: 12px;
}

.btn-danger {
    background: #dc3545;
}

.btn-success {
    background: #28a745;
}

.btn:hover {
    opacity: 0.9;
}

.info-row {
    display: flex;
    margin-bottom: 15px;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.label {
    font-weight: bold;
    width: 120px;
    color: #555;
}

.value {
    flex: 1;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.problem-text {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    white-space: pre-wrap;
    line-height: 1.6;
}
</style>

<?php include '../../includes/footer.php'; ?>
