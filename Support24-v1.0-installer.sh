#!/bin/bash

# Скрипт установки Support24_system
# Автор: LeManONE
# Описание: Полная автоматическая установка веб-сервиса техподдержки

set -e  # Останавливаем скрипт при любой ошибке

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функция для вывода статуса
log_success() {
    echo -e "${GREEN}[✓] $1${NC}"
}

log_error() {
    echo -e "${RED}[✗] $1${NC}"
}

log_info() {
    echo -e "${BLUE}[→] $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}[!] $1${NC}"
}

# Проверка запуска от root
if [[ $EUID -ne 0 ]]; then
   log_error "Этот скрипт должен запускаться от root (sudo ./install_support_system.sh)"
   exit 1
fi

clear
echo "========================================="
echo "  Установка Support24_system"
echo "========================================="
echo ""

# Получаем IP адрес сервера
SERVER_IP=$(hostname -I | awk '{print $1}')

log_info "Начинаю установку на сервере с IP: $SERVER_IP"
log_info "Время установки: $(date)"
echo ""

# ============================================
# 1. Обновление системы и установка пакетов
# ============================================
log_info "Шаг 1/9: Обновление системы и установка пакетов..."

apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq curl wget git apache2 php8.3 php8.3-mysql php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-xml libapache2-mod-php8.3

log_success "Пакеты установлены"

# ============================================
# 2. Скачивание проекта с GitHub
# ============================================
log_info "Шаг 2/9: Скачивание проекта с GitHub..."

# Удаляем старую папку, если есть
rm -rf /var/www/support_system

# Клонируем репозиторий
git clone https://github.com/LeManONE/Support24_system.git /var/www/support_system

# Создаем недостающие папки
mkdir -p /var/www/support_system/{images,articles,uploads,logs}
mkdir -p /var/www/support_system/mysql/{data,config,logs}

log_success "Проект скачан в /var/www/support_system"

# ============================================
# 3. Установка Docker и Docker Compose
# ============================================
log_info "Шаг 3/9: Установка Docker..."

# Проверяем, установлен ли Docker
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
    log_success "Docker установлен"
else
    log_success "Docker уже установлен"
fi

# Устанавливаем Docker Compose плагин
if ! docker compose version &> /dev/null; then
    apt-get install -y -qq docker-compose-plugin
    log_success "Docker Compose установлен"
else
    log_success "Docker Compose уже установлен"
fi

# ============================================
# 4. Создание docker-compose.yml
# ============================================
log_info "Шаг 4/9: Настройка MySQL в Docker..."

cat > /var/www/support_system/docker-compose.yml << 'EOF'
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: support_mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: P@ssw0rd
      MYSQL_DATABASE: support_system
      TZ: Europe/Moscow
    ports:
      - "127.0.0.1:3306:3306"
    volumes:
      - ./mysql/data:/var/lib/mysql
      - ./mysql/config:/etc/mysql/conf.d
      - ./mysql/logs:/var/log/mysql
    command: --default-authentication-plugin=mysql_native_password
EOF

log_success "docker-compose.yml создан"

# ============================================
# 5. Запуск MySQL контейнера
# ============================================
log_info "Шаг 5/9: Запуск MySQL в Docker..."

cd /var/www/support_system
docker compose down -v 2>/dev/null || true
docker compose up -d

# Ждем запуска MySQL
log_info "Ожидание запуска MySQL (30 секунд)..."
sleep 30

# Проверяем, что MySQL работает
if docker exec support_mysql mysql -u root -p'P@ssw0rd' -e "SELECT 1" &>/dev/null; then
    log_success "MySQL запущен и работает"
else
    log_error "MySQL не запустился"
    exit 1
fi

# ============================================
# 6. Создание таблиц в БД
# ============================================
log_info "Шаг 6/9: Создание таблиц в базе данных..."

# Генерируем хеш для пароля P@ssw0rd
PASSWORD_HASH=$(php -r "echo password_hash('P@ssw0rd', PASSWORD_DEFAULT);")

docker exec -i support_mysql mysql -u root -p'P@ssw0rd' support_system << EOF
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    telegram_id VARCHAR(50),
    local_ip VARCHAR(15),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tickets_count INT DEFAULT 0,
    resolved_tickets INT DEFAULT 0,
    password_changed BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    problem_text TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'resolved') DEFAULT 'new',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    admin_response TEXT,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

CREATE TABLE IF NOT EXISTS faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    article_file VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_user (user_id)
);

CREATE TABLE IF NOT EXISTS sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_expires (expires_at)
);

CREATE TABLE IF NOT EXISTS response_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Создаем админа
DELETE FROM users WHERE username = 'admin';
INSERT INTO users (username, password, full_name, role, password_changed) 
VALUES ('admin', '$PASSWORD_HASH', 'Главный Администратор', 'admin', FALSE);

-- Создаем тестового пользователя
DELETE FROM users WHERE username = 'user1';
INSERT INTO users (username, password, full_name, phone, local_ip, role, password_changed) 
VALUES ('user1', '$PASSWORD_HASH', 'Иван Петров', '+79991234567', '192.168.1.100', 'user', FALSE);

-- Добавляем тестовые статьи FAQ
INSERT INTO faq (title, description, image_path, article_file, sort_order) VALUES
('Не работает интернет', 'Проблемы с подключением к сети', '/images/no-internet.jpg', '/articles/no-internet.pdf', 1),
('Не включается компьютер', 'Компьютер не реагирует на кнопку питания', '/images/pc-off.jpg', '/articles/pc-off.docx', 2),
('Проблемы с почтой', 'Не получается отправить или получить письма', '/images/email.jpg', '/articles/email.pdf', 3),
('Принтер не печатает', 'Принтер не реагирует на команды печати', '/images/printer.jpg', '/articles/printer.pdf', 4);

-- Добавляем шаблоны ответов
INSERT INTO response_templates (title, content) VALUES
('Перезагрузка', 'Попробуйте перезагрузить компьютер. Это часто решает проблему.'),
('Проверка кабелей', 'Проверьте, все ли кабели подключены плотно.'),
('Обновление драйверов', 'Обновите драйверы устройства через диспетчер устройств.');
EOF

if [ $? -eq 0 ]; then
    log_success "Таблицы созданы, пользователи добавлены"
else
    log_error "Ошибка при создании таблиц"
    exit 1
fi

# ============================================
# 7. Настройка config.php
# ============================================
log_info "Шаг 7/9: Настройка конфигурации..."

cat > /var/www/support_system/includes/config.php << 'EOF'
<?php
// Настройки базы данных
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'P@ssw0rd');
define('DB_NAME', 'support_system');

// Пути
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $host . '/');
define('BASE_PATH', '/var/www/support_system/');

// Версия системы
define('VERSION', '1.0.0');

// Настройки сессий
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Режим отладки
define('DEBUG_MODE', false);
?>
EOF

log_success "config.php настроен"

# ============================================
# 8. Настройка Apache и прав
# ============================================
log_info "Шаг 8/9: Настройка веб-сервера..."

# Настраиваем виртуальный хост
cat > /etc/apache2/sites-available/support_system.conf << EOF
<VirtualHost *:80>
    ServerAdmin admin@localhost
    ServerName $SERVER_IP
    DocumentRoot /var/www/support_system

    <Directory /var/www/support_system>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Отключаем дефолтный сайт, включаем наш
a2dissite 000-default.conf 2>/dev/null || true
a2ensite support_system.conf
a2enmod rewrite

# Устанавливаем права
chown -R www-data:www-data /var/www/support_system
chmod -R 755 /var/www/support_system
chmod -R 777 /var/www/support_system/mysql/data 2>/dev/null || true

# Перезапускаем Apache
systemctl restart apache2

log_success "Apache настроен"

# ============================================
# 9. Настройка автозапуска
# ============================================
log_info "Шаг 9/9: Настройка автозапуска..."

systemctl enable docker
systemctl enable apache2

log_success "Сервисы добавлены в автозапуск"

# ============================================
# Завершение
# ============================================
clear
echo "========================================="
echo "  УСТАНОВКА ЗАВЕРШЕНА УСПЕШНО!"
echo "========================================="
echo ""
log_success "Веб-сервис Support24_system установлен!"
echo ""
echo "📋 Данные для входа:"
echo "   🔗 Адрес: http://$SERVER_IP/login.php"
echo "   👤 Логин: admin"
echo "   🔑 Пароль: P@ssw0rd (будет запрошена смена при первом входе)"
echo ""
echo "🗄️ База данных:"
echo "   MySQL в Docker: docker exec -it support_mysql mysql -u root -p'P@ssw0rd'"
echo ""
echo "📁 Папка сайта: /var/www/support_system"
echo ""
echo "🔄 Статус сервисов:"
systemctl is-active --quiet docker && echo "   ✅ Docker: активен" || echo "   ❌ Docker: не активен"
systemctl is-active --quiet apache2 && echo "   ✅ Apache: активен" || echo "   ❌ Apache: не активен"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep -E "support_mysql" && echo "   ✅ MySQL: активен" || echo "   ❌ MySQL: не активен"
echo ""
echo "========================================="
