#!/data/data/com.termux/files/usr/bin/bash
# ==========================================================
# Script dừng toàn bộ máy chủ Ngọc Rồng trên Termux Android
# ==========================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR"

echo -e "\e[1;33m===================================================\e[0m"
echo -e "\e[1;33m   ĐANG DỪNG MÁY CHỦ NGỌC RỒNG TRÊN TERMUX...     \e[0m"
echo -e "\e[1;33m===================================================\e[0m"

# 1. Dừng Game Server Java
if pgrep -f "server.jar" >/dev/null 2>&1; then
    echo -e "Dừng Game Server Java..."
    pkill -f "server.jar" 2>/dev/null || true
    sleep 1
    if pgrep -f "server.jar" >/dev/null 2>&1; then
        pkill -9 -f "server.jar" 2>/dev/null || true
    fi
    echo -e "\e[1;32mĐã dừng Game Server.\e[0m"
else
    echo "Game Server không chạy."
fi

# 2. Dừng Web Server PHP
if pgrep -f "php -S 0.0.0.0:8888" >/dev/null 2>&1; then
    echo -e "Dừng Web Server PHP..."
    pkill -f "php -S 0.0.0.0:8888" 2>/dev/null || true
    echo -e "\e[1;32mĐã dừng Web Server.\e[0m"
else
    echo "Web Server không chạy."
fi

# 3. Dừng MariaDB
if pgrep -f "mysqld" >/dev/null 2>&1; then
    echo -e "Dừng MariaDB an toàn..."
    mariadb-admin -u root shutdown 2>/dev/null || pkill -f "mysqld" 2>/dev/null || true
    echo -e "\e[1;32mĐã dừng MariaDB.\e[0m"
else
    echo "MariaDB không chạy."
fi

echo -e "\n\e[1;32mToàn bộ dịch vụ máy chủ đã được dừng an toàn!\e[0m"