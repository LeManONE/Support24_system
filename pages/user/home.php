<?php
require_once '../../includes/auth.php';
require_once '../../includes/logger.php';
require_once '../../includes/functions.php';
requireLogin();

$conn = db();
$user_id = $_SESSION['user_id'];

// Получаем последний тикет
$last_ticket = $conn->query("
    SELECT * FROM tickets 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC LIMIT 1
")->fetch_assoc();

// Получаем историю тикетов
$tickets = $conn->query("
    SELECT * FROM tickets 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>
<?php include '../../includes/header.php'; ?>

<div class="user-home">
    <div class="welcome-block">
        <h1>👋 Добро пожаловать, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <p>Опишите вашу проблему, и мы поможем как можно скорее</p>
    </div>
    
    <div class="quick-actions">
        <button class="btn btn-primary btn-large" onclick="openCreateTicketPopup()">
            ➕ СОЗДАТЬ ТИКЕТ
        </button>
    </div>
    
    <div class="tickets-section">
        <h2>📋 Ваши обращения</h2>
        
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <p>📭 У вас пока нет обращений в поддержку</p>
            </div>
        <?php else: ?>
            <?php foreach ($tickets as $ticket): ?>
            <div class="ticket-card <?php echo $ticket['status']; ?>">
                <div class="ticket-header">
                    <span class="ticket-id">#<?php echo $ticket['id']; ?></span>
                    <span class="ticket-time"><?php echo formatDate($ticket['created_at']); ?></span>
                    <span class="status-badge <?php echo $ticket['status']; ?>">
                        <?php 
                        $statuses = ['new' => 'Новая', 'in_progress' => 'В работе', 'resolved' => 'Решена'];
                        echo $statuses[$ticket['status']];
                        ?>
                    </span>
                </div>
                
                <div class="ticket-problem">
                    <strong>Проблема:</strong>
                    <p><?php echo nl2br(htmlspecialchars($ticket['problem_text'])); ?></p>
                </div>
                
                <?php if ($ticket['admin_response']): ?>
                    <div class="ticket-response">
                        <strong>👨‍💼 Ответ поддержки:</strong>
                        <p><?php echo nl2br(htmlspecialchars($ticket['admin_response'])); ?></p>
                        <small class="response-time"><?php echo formatDate($ticket['responded_at']); ?></small>
                    </div>
                <?php else: ?>
                    <div class="ticket-waiting">
                        ⏳ Ожидает ответа
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Попап создания тикета -->
<div class="popup" id="createTicketPopup">
    <span class="close-btn" onclick="closeAllPopups()">&times;</span>
    <h2>➕ Создать новый тикет</h2>
    <form id="createTicketForm" onsubmit="createTicket(event)">
        <div class="form-group">
            <label>Опишите проблему подробно:</label>
            <textarea name="problem" rows="8" required 
                      placeholder="Что случилось? Что вы делали перед этим? Какая ошибка появляется?"></textarea>
        </div>
        
        <div class="form-group">
            <label>Приоритет</label>
            <select name="priority">
                <option value="medium">🟡 Средний</option>
                <option value="low">🟢 Низкий</option>
                <option value="high">🔴 Высокий (срочно)</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-success">Отправить</button>
    </form>
</div>

<script>
function openCreateTicketPopup() {
    document.getElementById('createTicketPopup').classList.add('active');
    document.getElementById('popupOverlay').classList.add('active');
}

function createTicket(e) {
    e.preventDefault();
    
    let formData = new FormData(document.getElementById('createTicketForm'));
    
    fetch('/ajax/create_ticket.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Тикет создан!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Ошибка: ' + data.error, 'error');
        }
    })
    .catch(error => {
        showNotification('Ошибка сети', 'error');
    });
}
</script>

<style>
.user-home { max-width: 900px; margin: 0 auto; }

.welcome-block {
    text-align: center;
    padding: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    margin-bottom: 30px;
}

.welcome-block h1 { font-size: 28px; margin-bottom: 10px; }

.quick-actions {
    text-align: center;
    margin-bottom: 40px;
}

.btn-large {
    font-size: 18px;
    padding: 15px 30px;
}

.tickets-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.tickets-section h2 {
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
}

.ticket-card {
    border: 1px solid #eee;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s;
}

.ticket-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

.ticket-card.new { border-left: 4px solid #dc3545; }
.ticket-card.in_progress { border-left: 4px solid #ffc107; }
.ticket-card.resolved { border-left: 4px solid #28a745; }

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.ticket-id {
    font-weight: bold;
    color: #667eea;
    font-size: 16px;
}

.ticket-time { color: #999; font-size: 14px; }

.status-badge {
    padding: 3px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.new { background: #dc3545; color: white; }
.status-badge.in_progress { background: #ffc107; color: #333; }
.status-badge.resolved { background: #28a745; color: white; }

.ticket-problem {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin: 10px 0;
}

.ticket-problem p { margin-top: 10px; line-height: 1.6; }

.ticket-response {
    background: #f0fff0;
    padding: 15px;
    border-radius: 5px;
    margin-top: 10px;
    border-left: 3px solid #28a745;
}

.ticket-response p { margin: 10px 0; }
.response-time { color: #999; font-size: 12px; }

.ticket-waiting {
    text-align: center;
    padding: 15px;
    color: #999;
    font-style: italic;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}
</style>

<?php include '../../includes/footer.php'; ?>
