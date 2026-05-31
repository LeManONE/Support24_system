<?php
require_once '../../includes/auth.php';
require_once '../../includes/logger.php';
require_once '../../includes/functions.php';
requireAdmin();

$conn = db();

// Фильтры
$where = [];
$params = [];
$types = "";

if (!empty($_GET['role'])) {
    $where[] = "role = ?";
    $params[] = $_GET['role'];
    $types .= "s";
}

if (!empty($_GET['is_active'])) {
    $where[] = "is_active = ?";
    $params[] = $_GET['is_active'];
    $types .= "i";
}

if (!empty($_GET['search'])) {
    $search = "%{$_GET['search']}%";
    $where[] = "(full_name LIKE ? OR username LIKE ? OR phone LIKE ? OR telegram_id LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "ssss";
}

$sql = "SELECT * FROM users";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Статистика
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'admins' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'],
    'users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'],
    'active' => $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = TRUE")->fetch_assoc()['count']
];
?>
<?php include '../../includes/header.php'; ?>

<div class="users-page">
    <h1>Управление пользователями</h1>
    
    <!-- Статистика -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Всего</div>
            <div class="stat-value"><?php echo $stats['total']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Админов</div>
            <div class="stat-value"><?php echo $stats['admins']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Пользователей</div>
            <div class="stat-value"><?php echo $stats['users']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Активных</div>
            <div class="stat-value"><?php echo $stats['active']; ?></div>
        </div>
    </div>
    
    <!-- Фильтры -->
    <div class="filters">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label>Роль</label>
                <select name="role">
                    <option value="">Все</option>
                    <option value="admin" <?php echo ($_GET['role'] == 'admin') ? 'selected' : ''; ?>>Админы</option>
                    <option value="user" <?php echo ($_GET['role'] == 'user') ? 'selected' : ''; ?>>Пользователи</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Статус</label>
                <select name="is_active">
                    <option value="">Все</option>
                    <option value="1" <?php echo ($_GET['is_active'] == '1') ? 'selected' : ''; ?>>Активные</option>
                    <option value="0" <?php echo ($_GET['is_active'] == '0') ? 'selected' : ''; ?>>Неактивные</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Поиск</label>
                <input type="text" name="search" placeholder="ФИО, логин, телефон..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="btn">🔍 Применить</button>
            <a href="?" class="btn">✖ Сброс</a>
        </form>
    </div>
    
    <button class="btn btn-primary" onclick="openAddUserPopup()">➕ Добавить пользователя</button>
    
    <!-- Таблица -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Логин</th>
                    <th>Телефон</th>
                    <th>Telegram</th>
                    <th>IP</th>
                    <th>Роль</th>
                    <th>Статус</th>
                    <th>Тикетов</th>
                    <th>Решено</th>
                    <th>Последний вход</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>#<?php echo $user['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                    <td><?php echo $user['username']; ?></td>
                    <td><?php echo $user['phone'] ?? '-'; ?></td>
                    <td><?php echo $user['telegram_id'] ?? '-'; ?></td>
                    <td><?php echo $user['local_ip'] ?? '-'; ?></td>
                    <td>
                        <span class="role-badge <?php echo $user['role']; ?>">
                            <?php echo $user['role'] == 'admin' ? '👑 Админ' : '👤 Юзер'; ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $user['is_active'] ? 'Активен' : 'Неактивен'; ?>
                        </span>
                    </td>
                    <td><?php echo $user['tickets_count']; ?></td>
                    <td><?php echo $user['resolved_tickets']; ?></td>
                    <td><?php echo $user['last_login'] ? formatDate($user['last_login']) : '-'; ?></td>
                    <td>
                        <button class="btn-small" onclick="editUser(<?php echo $user['id']; ?>)">✏️</button>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <button class="btn-small btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">🗑️</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Попап добавления -->
<div class="popup" id="addUserPopup">
    <span class="close-btn" onclick="closeAllPopups()">&times;</span>
    <h2>➕ Добавить пользователя</h2>
    <form id="addUserForm" onsubmit="addUser(event)">
        <div class="form-group">
            <label>ФИО *</label>
            <input type="text" name="full_name" required>
        </div>
        
        <div class="form-group">
            <label>Логин *</label>
            <input type="text" name="username" required>
        </div>
        
        <div class="form-group">
            <label>Пароль * (по умолчанию P@ssw0rd)</label>
            <input type="text" name="password" value="P@ssw0rd" required>
        </div>
        
        <div class="form-group">
            <label>Телефон</label>
            <input type="text" name="phone">
        </div>
        
        <div class="form-group">
            <label>Telegram ID</label>
            <input type="text" name="telegram_id">
        </div>
        
        <div class="form-group">
            <label>Локальный IP</label>
            <input type="text" name="local_ip">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_admin"> Администратор
            </label>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" checked> Активен
            </label>
        </div>
        
        <button type="submit" class="btn btn-success">Создать</button>
    </form>
</div>

<!-- Попап редактирования -->
<div class="popup" id="editUserPopup">
    <span class="close-btn" onclick="closeAllPopups()">&times;</span>
    <h2>✏️ Редактировать пользователя</h2>
    <form id="editUserForm" onsubmit="updateUser(event)">
        <input type="hidden" name="id" id="edit_id">
        
        <div class="form-group">
            <label>ФИО *</label>
            <input type="text" name="full_name" id="edit_full_name" required>
        </div>
        
        <div class="form-group">
            <label>Логин</label>
            <input type="text" id="edit_username" disabled>
            <small>Логин изменить нельзя</small>
        </div>
        
        <div class="form-group">
            <label>Телефон</label>
            <input type="text" name="phone" id="edit_phone">
        </div>
        
        <div class="form-group">
            <label>Telegram ID</label>
            <input type="text" name="telegram_id" id="edit_telegram">
        </div>
        
        <div class="form-group">
            <label>Локальный IP</label>
            <input type="text" name="local_ip" id="edit_ip">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_admin" id="edit_is_admin"> Администратор
            </label>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" id="edit_is_active"> Активен
            </label>
        </div>
        
        <div class="form-group">
            <button type="button" class="btn" onclick="resetPassword()">🔄 Сбросить пароль на P@ssw0rd</button>
        </div>
        
        <button type="submit" class="btn btn-success">Сохранить</button>
    </form>
</div>

<script>
function openAddUserPopup() {
    document.getElementById('addUserPopup').classList.add('active');
    document.getElementById('popupOverlay').classList.add('active');
}

function editUser(id) {
    fetch('/ajax/get_user.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_id').value = data.user.id;
                document.getElementById('edit_full_name').value = data.user.full_name;
                document.getElementById('edit_username').value = data.user.username;
                document.getElementById('edit_phone').value = data.user.phone || '';
                document.getElementById('edit_telegram').value = data.user.telegram_id || '';
                document.getElementById('edit_ip').value = data.user.local_ip || '';
                document.getElementById('edit_is_admin').checked = (data.user.role === 'admin');
                document.getElementById('edit_is_active').checked = data.user.is_active;
                
                document.getElementById('editUserPopup').classList.add('active');
                document.getElementById('popupOverlay').classList.add('active');
            }
        });
}

function addUser(e) {
    e.preventDefault();
    
    let formData = new FormData(document.getElementById('addUserForm'));
    
    fetch('/ajax/add_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Пользователь создан', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Ошибка: ' + data.error, 'error');
        }
    });
}

function updateUser(e) {
    e.preventDefault();
    
    let formData = new FormData(document.getElementById('editUserForm'));
    
    fetch('/ajax/update_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Данные сохранены', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Ошибка: ' + data.error, 'error');
        }
    });
}

function deleteUser(id) {
    if (confirm('Удалить пользователя? Все его тикеты тоже удалятся.')) {
        fetch('/ajax/delete_user.php?id=' + id, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Пользователь удален', 'success');
                location.reload();
            } else {
                showNotification('Ошибка: ' + data.error, 'error');
            }
        });
    }
}

function resetPassword() {
    let id = document.getElementById('edit_id').value;
    
    if (confirm('Сбросить пароль на P@ssw0rd?')) {
        fetch('/ajax/reset_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Пароль сброшен на P@ssw0rd', 'success');
            } else {
                showNotification('Ошибка: ' + data.error, 'error');
            }
        });
    }
}
</script>

<style>
.users-page { padding: 20px; }

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
}

.role-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.role-badge.admin { background: #dc3545; color: white; }
.role-badge.user { background: #28a745; color: white; }

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.status-badge.active { background: #28a745; color: white; }
.status-badge.inactive { background: #6c757d; color: white; }
</style>

<?php include '../../includes/footer.php'; ?>
