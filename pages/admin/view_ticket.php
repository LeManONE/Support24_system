<?php
require_once '../../includes/auth.php';
require_once '../../includes/logger.php';
require_once '../../includes/functions.php';
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: dashboard.php');
    exit();
}

$conn = db();
$result = $conn->query("
    SELECT t.*, u.full_name, u.local_ip, u.username, u.phone, u.telegram_id
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = $id
");
$ticket = $result->fetch_assoc();

if (!$ticket) {
    header('Location: dashboard.php');
    exit();
}

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $response = $_POST['response'];
    
    $stmt = $conn->prepare("UPDATE tickets SET status = ?, priority = ?, admin_response = ?, responded_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssi", $status, $priority, $response, $id);
    $stmt->execute();
    
    logAction('TICKET_UPDATE', "Обновлен тикет #$id, статус: $status");
    
    // Редирект назад на эту же страницу
    header("Location: view_ticket.php?id=$id&saved=1");
    exit();
}

// Обработка удаления
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM tickets WHERE id = $id");
    logAction('TICKET_DELETE', "Удален тикет #$id");
    header('Location: dashboard.php');
    exit();
}
?>
<?php include '../../includes/header.php'; ?>

<div class="view-ticket">
    <div class="ticket-header">
        <h1>Тикет #<?php echo $ticket['id']; ?></h1>
        <div class="actions">
            <a href="dashboard.php" class="btn">← Назад к списку</a>
            <a href="?id=<?php echo $id; ?>&delete=1" class="btn btn-danger" onclick="return confirm('Удалить тикет?')">🗑 Удалить</a>
        </div>
    </div>
    
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">✅ Изменения сохранены!</div>
    <?php endif; ?>
    
    <form method="POST" class="ticket-form">
        <div class="form-row">
            <div class="form-group">
                <label>Пользователь</label>
                <input type="text" value="<?php echo htmlspecialchars($ticket['full_name']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>IP адрес</label>
                <div class="ip-control">
                    <input type="text" value="<?php echo htmlspecialchars($ticket['local_ip'] ?? 'не указан'); ?>" readonly>
                    <button type="button" class="btn-small" onclick="pingIP('<?php echo $ticket['local_ip']; ?>')">📡 Пинг</button>
                    <span id="pingResult" style="margin-left: 10px;"></span>
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Статус</label>
                <select name="status">
                    <option value="new" <?php echo $ticket['status'] == 'new' ? 'selected' : ''; ?>>🟡 Новая</option>
                    <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>🔵 В работе</option>
                    <option value="resolved" <?php echo $ticket['status'] == 'resolved' ? 'selected' : ''; ?>>🟢 Решена</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Приоритет</label>
                <select name="priority">
                    <option value="low" <?php echo $ticket['priority'] == 'low' ? 'selected' : ''; ?>>🟢 Низкий</option>
                    <option value="medium" <?php echo $ticket['priority'] == 'medium' ? 'selected' : ''; ?>>🟡 Средний</option>
                    <option value="high" <?php echo $ticket['priority'] == 'high' ? 'selected' : ''; ?>>🔴 Высокий</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Время создания</label>
            <input type="text" value="<?php echo date('d.m.Y H:i:s', strtotime($ticket['created_at'])); ?>" readonly>
        </div>
        
        <div class="form-group">
            <label>Проблема</label>
            <div class="problem-text"><?php echo nl2br(htmlspecialchars($ticket['problem_text'])); ?></div>
        </div>
        
        <div class="form-group">
            <label>Ответ администратора</label>
            <textarea name="response" rows="6"><?php echo htmlspecialchars($ticket['admin_response'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-success">💾 Сохранить изменения</button>
        </div>
    </form>
</div>

<script>
function pingIP(ip) {
    if (!ip || ip === 'не указан') {
        document.getElementById('pingResult').textContent = '❌ IP не указан';
        return;
    }
    
    document.getElementById('pingResult').textContent = '⏳ Пингую...';
    
    fetch('/ajax/ping.php?ip=' + encodeURIComponent(ip))
        .then(r => r.json())
        .then(data => {
            document.getElementById('pingResult').textContent = data.result;
        });
}
</script>

<style>
.view-ticket {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #667eea;
}

.ticket-header h1 {
    margin: 0;
    color: #333;
}

.actions {
    display: flex;
    gap: 10px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row .form-group {
    flex: 1;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #555;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.form-group input[readonly] {
    background: #f5f5f5;
    color: #666;
}

.problem-text {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    line-height: 1.6;
    white-space: pre-wrap;
}

.ip-control {
    display: flex;
    gap: 10px;
    align-items: center;
}

.ip-control input {
    flex: 1;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
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
</style>

<?php include '../../includes/footer.php'; ?>
