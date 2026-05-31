#!/bin/bash

# ============================================
#   Support24_system - Установщик веб-сервиса
#   Автор: LeManONE
#   Telegram: @artemsmvrv
# ============================================

set -e

# Цвета
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
BOLD='\033[1m'
NC='\033[0m'

# Функции
print_banner() {
    clear
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════════════╗"
    echo "║                                                          ║"
    echo "║     ███████╗██╗   ██╗██████╗ ██████╗  ██████╗ ██████╗    ║"
    echo "║     ██╔════╝██║   ██║██╔══██╗██╔══██╗██╔═══██╗██╔══██╗   ║"
    echo "║     ███████╗██║   ██║██████╔╝██████╔╝██║   ██║██████╔╝    ║"
    echo "║     ╚════██║██║   ██║██╔═══╝ ██╔═══╝ ██║   ██║██╔══██╗    ║"
    echo "║     ███████║╚██████╔╝██║     ██║     ╚██████╔╝██║  ██║    ║"
    echo "║     ╚══════╝ ╚═════╝ ╚═╝     ╚═╝      ╚═════╝ ╚═╝  ╚═╝    ║"
    echo "║                                                          ║"
    echo "║           📦 УСТАНОВЩИК ВЕБ-СЕРВИСА ТЕХПОДДЕРЖКИ          ║"
    echo "║                      Version 1.0.0                        ║"
    echo "╚══════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_step() {
    echo -e "\n${MAGENTA}┌──────────────────────────────────────────────────────────┐${NC}"
    echo -e "${MAGENTA}│${NC} ${BOLD}📌 $1${NC}"
    echo -e "${MAGENTA}└──────────────────────────────────────────────────────────┘${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_progress() {
    echo -e "${CYAN}⚙️ $1${NC}"
}

# Проверка root
if [[ $EUID -ne 0 ]]; then
    print_error "Скрипт должен запускаться от root!"
    echo -e "${YELLOW}Используйте: sudo ./install.sh${NC}"
    exit 1
fi

print_banner

echo -e "${CYAN}🔍 Проверка окружения...${NC}\n"
SERVER_IP=$(hostname -I | awk '{print $1}')
print_info "IP адрес сервера: ${BOLD}$SERVER_IP${NC}"
print_info "Время начала: $(date '+%d.%m.%Y %H:%M:%S')"
echo ""

# ============================================
# Шаг 1
# ============================================
print_step "1/10: Установка локализации и шрифтов"

print_progress "Устанавливаю языковые пакеты..."
apt-get update -qq
apt-get install -y -qq language-pack-ru fonts-dejavu-core fontconfig

export LC_ALL=ru_RU.UTF-8
export LANG=ru_RU.UTF-8
locale-gen ru_RU.UTF-8 > /dev/null 2>&1
update-locale LANG=ru_RU.UTF-8 > /dev/null 2>&1

print_success "Локализация и шрифты установлены"

# ============================================
# Шаг 2
# ============================================
print_step "2/10: Установка пакетов"

print_progress "Устанавливаю Apache, PHP 8.3 и модули..."
apt-get install -y -qq curl wget git apache2 \
    php8.3 php8.3-mysql php8.3-mbstring php8.3-curl \
    php8.3-zip php8.3-gd php8.3-xml php8.3-intl \
    libapache2-mod-php8.3 2>/dev/null

print_success "Все пакеты установлены"

# ============================================
# Шаг 3
# ============================================
print_step "3/10: Загрузка проекта с GitHub"

print_progress "Клонирую репозиторий..."
rm -rf /var/www/support_system
git clone https://github.com/LeManONE/Support24_system.git /var/www/support_system 2>/dev/null

mkdir -p /var/www/support_system/{images,articles,uploads,logs}
mkdir -p /var/www/support_system/mysql/{data,config,logs}

print_success "Проект загружен в /var/www/support_system"

# ============================================
# Шаг 4
# ============================================
print_step "4/10: Установка Docker"

if ! command -v docker &> /dev/null; then
    print_progress "Docker не найден, устанавливаю..."
    curl -fsSL https://get.docker.com -o get-docker.sh > /dev/null 2>&1
    sh get-docker.sh > /dev/null 2>&1
    rm get-docker.sh
    print_success "Docker установлен"
else
    print_success "Docker уже установлен"
fi

if ! docker compose version &> /dev/null; then
    print_progress "Устанавливаю Docker Compose..."
    apt-get install -y -qq docker-compose-plugin
    print_success "Docker Compose установлен"
else
    print_success "Docker Compose уже установлен"
fi

# ============================================
# Шаг 5
# ============================================
print_step "5/10: Настройка MySQL в Docker"

cat > /var/www/support_system/docker-compose.yml << 'EOF'
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

print_success "Конфиг docker-compose.yml создан"

# ============================================
# Шаг 6
# ============================================
print_step "6/10: Запуск MySQL контейнера"

cd /var/www/support_system
docker compose down -v 2>/dev/null || true
docker compose up -d > /dev/null 2>&1

print_progress "Ожидание запуска MySQL (⏳ 30 секунд)..."
for i in {30..1}; do
    echo -ne "${CYAN}   Осталось $i секунд...${NC}\r"
    sleep 1
done
echo ""

if docker exec support_mysql mysql -u root -p'P@ssw0rd' -e "SELECT 1" &>/dev/null; then
    print_success "MySQL запущен и работает"
else
    print_error "MySQL не запустился"
    docker logs support_mysql --tail 20
    exit 1
fi

# ============================================
# Шаг 7
# ============================================
print_step "7/10: Создание базы данных и таблиц"

PASSWORD_HASH=$(php -r "echo password_hash('P@ssw0rd', PASSWORD_DEFAULT);")

docker exec -i support_mysql mysql -u root -p'P@ssw0rd' support_system << EOF > /dev/null 2>&1
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DELETE FROM users WHERE username IN ('admin', 'user1');
INSERT INTO users (username, password, full_name, role, password_changed) 
VALUES ('admin', '$PASSWORD_HASH', 'Главный Администратор', 'admin', FALSE);

INSERT INTO users (username, password, full_name, phone, local_ip, role, password_changed) 
VALUES ('user1', '$PASSWORD_HASH', 'Иван Петров', '+79991234567', '192.168.1.100', 'user', FALSE);

INSERT INTO faq (title, description, image_path, article_file, sort_order) VALUES
('Не работает интернет', 'Проблемы с подключением к сети', '/images/no-internet.jpg', '/articles/no-internet.pdf', 1),
('Не включается компьютер', 'Компьютер не реагирует на кнопку питания', '/images/pc-off.jpg', '/articles/pc-off.docx', 2),
('Проблемы с почтой', 'Не получается отправить или получить письма', '/images/email.jpg', '/articles/email.pdf', 3),
('Принтер не печатает', 'Принтер не реагирует на команды печати', '/images/printer.jpg', '/articles/printer.pdf', 4);
EOF

print_success "База данных и таблицы созданы"
print_success "Пользователи созданы (admin / user1)"

# ============================================
# Шаг 8
# ============================================
print_step "8/10: Настройка конфигурации"

cat > /var/www/support_system/includes/config.php << 'EOF'
<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'P@ssw0rd');
define('DB_NAME', 'support_system');

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $host . '/');
define('BASE_PATH', '/var/www/support_system/');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
?>
EOF

print_success "Файл config.php настроен"

# ============================================
# Шаг 9
# ============================================
print_step "9/10: Настройка Apache"

cat > /etc/apache2/conf-available/charset.conf << 'EOF'
AddDefaultCharset UTF-8
AddCharset UTF-8 .html .php
EOF
a2enconf charset > /dev/null 2>&1

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

a2dissite 000-default.conf > /dev/null 2>&1 || true
a2ensite support_system.conf > /dev/null 2>&1
a2enmod rewrite > /dev/null 2>&1

chown -R www-data:www-data /var/www/support_system
chmod -R 755 /var/www/support_system

systemctl restart apache2
print_success "Apache настроен и перезапущен"

# ============================================
# Шаг 10
# ============================================
print_step "10/10: Настройка автозапуска"

systemctl enable docker > /dev/null 2>&1
systemctl enable apache2 > /dev/null 2>&1

print_success "Сервисы добавлены в автозапуск"

# ============================================
# ФИНАЛ
# ============================================
clear
print_banner

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                   ✅ УСТАНОВКА ЗАВЕРШЕНА!                   ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${CYAN}┌─────────────────────────────────────────────────────────────┐${NC}"
echo -e "${CYAN}│${NC}  🚀 ВЕБ-СЕРВИС УСПЕШНО УСТАНОВЛЕН                           ${CYAN}│${NC}"
echo -e "${CYAN}└─────────────────────────────────────────────────────────────┘${NC}"
echo ""

echo -e "${YELLOW}📋 ДАННЫЕ ДЛЯ ВХОДА:${NC}"
echo -e "   🔗 ${BOLD}Адрес:${NC}  http://$SERVER_IP/login.php"
echo -e "   👤 ${BOLD}Логин:${NC}  admin"
echo -e "   🔑 ${BOLD}Пароль:${NC} P@ssw0rd ${RED}(ОБЯЗАТЕЛЬНО СМЕНИТЕ ПРИ ПЕРВОМ ВХОДЕ!)${NC}"
echo ""

echo -e "${YELLOW}🗄️ БАЗА ДАННЫХ:${NC}"
echo -e "   🐳 ${BOLD}MySQL в Docker:${NC}"
echo -e "   docker exec -it support_mysql mysql -u root -p'P@ssw0rd'"
echo ""

echo -e "${YELLOW}📁 ПАПКИ ПРОЕКТА:${NC}"
echo -e "   📂 Сайт:        /var/www/support_system"
echo -e "   📂 Логи:        /var/www/support_system/logs"
echo -e "   📂 Загрузки:    /var/www/support_system/uploads"
echo ""

echo -e "${YELLOW}🔄 СТАТУС СЕРВИСОВ:${NC}"
if systemctl is-active --quiet docker; then
    echo -e "   ${GREEN}✅ Docker: активен${NC}"
else
    echo -e "   ${RED}❌ Docker: не активен${NC}"
fi

if systemctl is-active --quiet apache2; then
    echo -e "   ${GREEN}✅ Apache: активен${NC}"
else
    echo -e "   ${RED}❌ Apache: не активен${NC}"
fi

if docker ps --format "table {{.Names}}" 2>/dev/null | grep -q "support_mysql"; then
    echo -e "   ${GREEN}✅ MySQL: активен${NC}"
else
    echo -e "   ${RED}❌ MySQL: не активен${NC}"
fi
echo ""

echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC}  💬 ПОЛУЧИТЬ ПОМОЩЬ ИЛИ ЗАДАТЬ ВОПРОС:                     ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}                                                           ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}     🔗 Telegram: ${BLUE}${BOLD}@artemsmvrv${NC}                              ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}     📧 Email:    support@example.com                        ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}     🌐 GitHub:   https://github.com/LeManONE/Support24_system ${CYAN}║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${GREEN}🎉 Спасибо за установку! Удачи с дипломом!${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

exit 0
