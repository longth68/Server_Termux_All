#!/data/data/com.termux/files/usr/bin/bash
# ==========================================================
# Script khởi động máy chủ Ngọc Rồng trên Termux Android
# ==========================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR"

echo -e "\e[1;32m===================================================\e[0m"
echo -e "\e[1;33m   KHỞI ĐỘNG NGỌC RỒNG HASHIRAMA TRÊN TERMUX       \e[0m"
echo -e "\e[1;32m===================================================\e[0m"

mkdir -p "$DIR/Server/log"

# 1. Khởi động MariaDB
if ! pgrep -f "mysqld" >/dev/null 2>&1; then
    echo -e "\e[1;36m[1/3] Khởi động cơ sở dữ liệu MariaDB...\e[0m"
    mysqld_safe --skip-syslog >/dev/null 2>&1 &
    sleep 2
    max_wait=10
    while ! mariadb -u root -e "status" >/dev/null 2>&1; do
        sleep 1
        max_wait=$((max_wait - 1))
        if [ $max_wait -le 0 ]; then
            echo -e "\e[1;31mKhông thể kết nối MariaDB! Đang thử lại...\e[0m"
            break
        fi
    done
else
    echo -e "\e[1;32m[1/3] MariaDB đã đang chạy sẵn.\e[0m"
fi

# 2. Khởi động Game Server Java
if pgrep -f "server.jar" >/dev/null 2>&1; then
    echo -e "\e[1;33m[2/3] Game Server Java đã đang chạy rồi!\e[0m"
else
    echo -e "\e[1;36m[2/3] Khởi động Game Server Java (Port 14445, API 8080)...\e[0m"
    cd "$DIR/Server"
    nohup java -Xms256m -Xmx1024m -jar server.jar > log/server.log 2>&1 &
    JAVA_PID=$!
    cd "$DIR"
    echo -e "\e[1;32mGame Server đã chạy ngầm với PID: $JAVA_PID\e[0m"
fi

# 3. Khởi động Web Admin (PHP Built-in Server trên Port 8888)
if [ -d "$DIR/web/htdocs" ]; then
    if pgrep -f "php -S 0.0.0.0:8888" >/dev/null 2>&1; then
        echo -e "\e[1;32m[3/3] Web Server PHP đã đang chạy sẵn trên port 8888.\e[0m"
    else
        echo -e "\e[1;36m[3/3] Khởi động Web Server PHP trên Port 8888...\e[0m"
        nohup php -S 0.0.0.0:8888 -t "$DIR/web/htdocs" > "$DIR/Server/log/web.log" 2>&1 &
        echo -e "\e[1;32mWeb Server đã chạy trên: http://127.0.0.1:8888\e[0m"
    fi
fi

sleep 2

# Lấy địa chỉ IP máy Android
IP_WIFI=$(ip -4 addr show wlan0 2>/dev/null | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | head -n 1)
[ -z "$IP_WIFI" ] && IP_WIFI=$(ip -4 addr show ap0 2>/dev/null | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | head -n 1)
[ -z "$IP_WIFI" ] && IP_WIFI="127.0.0.1"

echo -e "\n\e[1;32m===================================================\e[0m"
echo -e "\e[1;32m   MÁY CHỦ ĐÃ KHỞI ĐỘNG XONG!                      \e[0m"
echo -e "\e[1;32m===================================================\e[0m"
echo -e "  - Cổng Game:       \e[1;33m14445\e[0m"
echo -e "  - Cổng Web API:    \e[1;33m8080\e[0m"
echo -e "  - Cổng Web Admin:  \e[1;33m8888\e[0m"
echo -e "  - IP kết nối WiFi: \e[1;36m$IP_WIFI\e[0m"
echo -e "  - Web Admin:       \e[1;36mhttp://127.0.0.1:8888/admin.php\e[0m"
echo -e "  - Xem log Server:  \e[1;36mtail -f Server/log/server.log\e[0m"
echo -e "\e[1;32m===================================================\e[0m"