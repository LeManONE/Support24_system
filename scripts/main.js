// Управление попапами
function openProfilePopup() {
    const popup = document.getElementById('profilePopup');
    const overlay = document.getElementById('popupOverlay');
    
    popup.classList.add('active');
    overlay.classList.add('active');
    
    // Загрузка данных профиля
    fetch('/ajax/get_profile.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                let html = `
                    <div class="info-row">
                        <span class="label">ФИО:</span>
                        <span class="value">${escapeHtml(user.full_name)}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Логин:</span>
                        <span class="value">${escapeHtml(user.username)}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Телефон:</span>
                        <span class="value">${escapeHtml(user.phone || 'не указан')}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Telegram ID:</span>
                        <span class="value">${escapeHtml(user.telegram_id || 'не указан')}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Локальный IP:</span>
                        <span class="value">${escapeHtml(user.local_ip || 'не указан')}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Дата регистрации:</span>
                        <span class="value">${formatDate(user.created_at)}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Всего тикетов:</span>
                        <span class="value">${user.tickets_count || 0}</span>
                    </div>
                `;
                
                if (user.role === 'admin') {
                    html += `
                        <div class="info-row">
                            <span class="label">Решено тикетов:</span>
                            <span class="value">${user.resolved_tickets || 0}</span>
                        </div>
                    `;
                }
                
                document.getElementById('profileContent').innerHTML = html;
            } else {
                document.getElementById('profileContent').innerHTML = '<div class="error">Ошибка загрузки профиля</div>';
            }
        })
        .catch(error => {
            document.getElementById('profileContent').innerHTML = '<div class="error">Ошибка соединения</div>';
        });
}

function closeAllPopups() {
    document.querySelectorAll('.popup').forEach(p => p.classList.remove('active'));
    document.getElementById('popupOverlay').classList.remove('active');
}

// Закрытие по ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllPopups();
    }
});

// Закрытие по клику на оверлей
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('popupOverlay');
    if (overlay) {
        overlay.addEventListener('click', closeAllPopups);
    }
});

// Вспомогательные функции
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Уведомления
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        border-radius: 5px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        z-index: 2000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Добавляем стили для уведомлений
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
