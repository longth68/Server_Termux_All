#!/data/data/com.termux/files/usr/bin/bash
# ==========================================================
# Script kiểm tra trạng thái máy chủ Ngọc Rồng trên Termux Android
# ==========================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo -e "\e[1;32m===================================================\e[0m"
echo -e "\e[1;33m   TRẠNG THÁI MÁY CHỦ NGỌC RỒNG HASHIRAMA (TERMUX) \e[0m"
echo -e "\e[1;32m===================================================\e[0m"

# 1. Kiểm tra MariaDB
if pgrep -f "mysqld" >/dev/null 2>&1; then
    MARIA_PID=$(pgrep -f "mysqld" | head -n 1)
    echo -e "  [MariaDB Database]:  \e[1;32mĐANG CHẠY\e[0m (PID: $MARIA_PID, Port: 3306)"
else
    echo -e "  [MariaDB Database]:  \e[1;31mĐÃ DỪNG\e[0m"
fi

# 2. Kiểm tra Game Server Java
if pgrep -f "server.jar" >/dev/null 2>&1; then
    JAVA_PID=$(pgrep -f "server.jar" | head -n 1)
    echo -e "  [Game Server Java]:  \e[1;32mĐANG CHẠY\e[0m (PID: $JAVA_PID, Port: 14445, API: 8080)"
else
    echo -e "  [Game Server Java]:  \e[1;31mĐÃ DỪNG\e[0m"
fi

# 3. Kiểm tra Web Server PHP
if pgrep -f "php -S 0.0.0.0:8888" >/dev/null 2>&1; then
    WEB_PID=$(pgrep -f "php -S 0.0.0.0:8888" | head -n 1)
    echo -e "  [Web Server PHP]:    \e[1;32mĐANG CHẠY\e[0m (PID: $WEB_PID, Port: 8888)"
else
    echo -e "  [Web Server PHP]:    \e[1;31mĐÃ DỪNG\e[0m"
fi

echo -e "\e[1;32m---------------------------------------------------\e[0m"

# 4. Địa chỉ IP máy Android
echo -e "\e[1;33mThông tin kết nối mạng:\e[0m"
IP_WIFI=$(ip -4 addr show wlan0 2>/dev/null | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | head -n 1)
IP_HOTSPOT=$(ip -4 addr show ap0 2>/dev/null | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | head -n 1)
[ -n "$IP_WIFI" ] && echo -e "  - IP WiFi (wlan0):   \e[1;36m$IP_WIFI\e[0m"
[ -n "$IP_HOTSPOT" ] && echo -e "  - IP Phát WiFi (ap0): \e[1;36m$IP_HOTSPOT\e[0m"
echo -e "  - IP Cục bộ (Local): \e[1;36m127.0.0.1\e[0m"

echo -e "\e[1;32m---------------------------------------------------\e[0m"

# 5. Bộ nhớ RAM trên Android
echo -e "\e[1;33mBộ nhớ RAM thiết bị:\e[0m"
free -m 2>/dev/null || cat /proc/meminfo | grep -E "MemTotal|MemFree|MemAvailable"

echo -e "\e[1;32m===================================================\e[0m"