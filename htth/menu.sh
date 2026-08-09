#!/bin/bash

# Chi chay trong interactive shell
case $- in
    *i*) ;;
    *)   exit 0 ;;
esac

# PREFIX fallback
if [ -z "$PREFIX" ]; then
    PREFIX="/data/data/com.termux/files/usr"
fi

# Màu sắc
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # Không màu

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

function show_menu() {
    clear
    echo -e "${CYAN}================================================${NC}"
    echo -e "${YELLOW}       MENU QUẢN LÝ HTTH SERVER TERMUX        ${NC}"
    echo -e "${CYAN}================================================${NC}"
    echo -e "${GREEN} 1.${NC} Cài đặt môi trường (Java, MariaDB, PHP)"
    echo -e "${GREEN} 2.${NC} Khởi tạo & Import Database"
    echo -e "${GREEN} 3.${NC} Chạy Máy Chủ (Game + Web Server)"
    echo -e "${GREEN} 4.${NC} Dừng tất cả các tiến trình"
    echo -e "${GREEN} 5.${NC} Tạo lệnh gõ nhanh 'menu' ở mọi nơi"
    echo -e "${GREEN} 0.${NC} Thoát"
    echo -e "${CYAN}================================================${NC}"
    echo -n "Chon chuc nang [0-5]: "
    read -r choice
    case $choice in
        1) install_env ;;
        2) init_db ;;
        3) start_servers ;;
        4) stop_all ;;
        5) create_shortcut ;;
        0) exit 0 ;;
        *) echo -e "${RED}Lựa chọn không hợp lệ!${NC}"; sleep 2; show_menu ;;
    esac
}

function install_env() {
    echo -e "${YELLOW}[*] Đang cập nhật hệ thống...${NC}"
    pkg update && pkg upgrade -y
    echo -e "${YELLOW}[*] Đang cài đặt OpenJDK 21, MariaDB, PHP và các gói cần thiết...${NC}"
    pkg install openjdk-21 mariadb php procps psmisc curl wget unzip zip -y
    echo -e "${GREEN}[+] Cài đặt hoàn tất!${NC}"
    echo -n "Nhấn Enter để về menu..." 
    read -r _enter
    show_menu
}

function init_db() {
    if ! command -v mariadbd >/dev/null 2>&1 && ! command -v mysqld >/dev/null 2>&1; then
        echo -e "${RED}[!] MariaDB chưa được cài đặt. Vui lòng chọn (1) trước.${NC}"
        echo -n "Nhấn Enter để về menu..." 
        read -r _enter
        show_menu
        return
    fi
    
    echo -e "${YELLOW}[*] Đang khởi tạo MariaDB...${NC}"
    mkdir -p "$PREFIX/tmp" "$PREFIX/var/lib/mysql" "$PREFIX/var/run/mysqld" "$PREFIX/var/log/mysql"
    if [ -z "$(ls -A "$PREFIX/var/lib/mysql" 2>/dev/null)" ]; then
        if command -v mariadb-install-db >/dev/null 2>&1; then
            mariadb-install-db --datadir="$PREFIX/var/lib/mysql" --auth-root-authentication-method=normal >/dev/null 2>&1
        elif command -v mysql_install_db >/dev/null 2>&1; then
            mysql_install_db --datadir="$PREFIX/var/lib/mysql" --auth-root-authentication-method=normal >/dev/null 2>&1
        fi
    fi
    
    echo -e "${YELLOW}[*] Đang bật MariaDB ngầm...${NC}"
    pkill -x mariadbd 2>/dev/null || pkill -x mysqld 2>/dev/null || true
    rm -f "$PREFIX/tmp/mysqld.sock" "$PREFIX/tmp/mysqld.sock.lock" "$PREFIX/var/run/mysqld/mysqld.pid" 2>/dev/null || true
    
    MYSQLD_START=""
    for c in mariadbd-safe mysqld_safe mariadbd mysqld; do
        command -v "$c" >/dev/null 2>&1 && { MYSQLD_START="$c"; break; }
    done
    if [ -n "$MYSQLD_START" ]; then
        "$MYSQLD_START" --no-defaults \
            --datadir="$PREFIX/var/lib/mysql" \
            --socket="$PREFIX/tmp/mysqld.sock" \
            --pid-file="$PREFIX/var/run/mysqld/mysqld.pid" \
            --log-error="$PREFIX/var/log/mysql/mariadbd.err" \
            >/dev/null 2>&1 &
    fi
    
    local ok=0
    for i in $(seq 1 30); do
        sleep 1
        if mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null; then ok=1; break; fi
    done
    
    if [ "$ok" -eq 1 ]; then
        echo -e "${YELLOW}[*] Tạo User root và Import Database...${NC}"
        mysql --socket="$PREFIX/tmp/mysqld.sock" -u $(whoami) -e "CREATE USER IF NOT EXISTS 'root'@'localhost'; GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost'; FLUSH PRIVILEGES;" 2>/dev/null || true
        
        if ! mysql --socket="$PREFIX/tmp/mysqld.sock" -u root -e "USE htth" 2>/dev/null; then
            mysql --socket="$PREFIX/tmp/mysqld.sock" -u root -e "CREATE DATABASE IF NOT EXISTS htth;" 2>/dev/null
        fi

        if [ -f "database/htth_full.sql" ]; then
            if mysql --socket="$PREFIX/tmp/mysqld.sock" -u root htth < "database/htth_full.sql"; then
                echo -e "${GREEN}[+] Import Database thành công!${NC}"
            else
                echo -e "${RED}[!] Lỗi khi Import Database.${NC}"
            fi
        else
            echo -e "${RED}[!] Không tìm thấy file database/htth_full.sql${NC}"
        fi
    else
        echo -e "${RED}[!] MariaDB chưa sẵn sàng.${NC}"
    fi
    
    echo -n "Nhấn Enter để về menu..." 
    read -r _enter
    show_menu
}

function start_servers() {
    if ! command -v java >/dev/null 2>&1 || ! command -v php >/dev/null 2>&1; then
        echo -e "${RED}[!] Java hoặc PHP chưa được cài đặt. Hãy chọn (1) cài đặt môi trường.${NC}"
        echo -n "Nhấn Enter để về menu..." 
        read -r _enter
        show_menu
        return
    fi
    
    if ! mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null; then
        echo -e "${YELLOW}[*] Đang bật MariaDB...${NC}"
        pkill -x mariadbd 2>/dev/null || pkill -x mysqld 2>/dev/null || true
        rm -f "$PREFIX/tmp/mysqld.sock" "$PREFIX/tmp/mysqld.sock.lock" "$PREFIX/var/run/mysqld/mysqld.pid" 2>/dev/null || true
        
        MYSQLD_START=""
        for c in mariadbd-safe mysqld_safe mariadbd mysqld; do
            command -v "$c" >/dev/null 2>&1 && { MYSQLD_START="$c"; break; }
        done
        if [ -n "$MYSQLD_START" ]; then
            "$MYSQLD_START" --no-defaults \
                --datadir="$PREFIX/var/lib/mysql" \
                --socket="$PREFIX/tmp/mysqld.sock" \
                --pid-file="$PREFIX/var/run/mysqld/mysqld.pid" \
                --log-error="$PREFIX/var/log/mysql/mariadbd.err" \
                >/dev/null 2>&1 &
        fi
        local _waited=0
        while ! mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null && [ $_waited -lt 15 ]; do
            sleep 1; _waited=$((_waited + 1))
        done
    fi

    echo -e "${YELLOW}[*] Đang khởi động Game Server (Port 2236)...${NC}"
    pkill -x java 2>/dev/null

    # Tự giải nén dữ liệu game nếu chưa có
    if [ ! -d "data/map" ]; then
        if [ -f "HTTH_data.part1" ]; then
            echo -e "${YELLOW}[*] Nối và giải nén dữ liệu game (HTTH_data.part1+2+3)...${NC}"
            cat HTTH_data.part1 HTTH_data.part2 HTTH_data.part3 > data.tar.gz
            tar -xzf data.tar.gz
            rm -f data.tar.gz
            if [ ! -d "data/map" ]; then
                echo -e "${RED}[!] Giải nén thất bại.${NC}"
                echo -n "Nhấn Enter để về menu..."
                read -r _enter
                show_menu
                return
            fi
        else
            echo -e "${RED}[!] Thiếu dữ liệu game (HTTH_data.part1/2/3 hoặc thư mục data/).${NC}"
            echo -n "Nhấn Enter để về menu..."
            read -r _enter
            show_menu
            return
        fi
    fi

    nohup java -Xms512M -Xmx1024M -XX:+UseG1GC -Dfile.encoding=UTF-8 -Djava.awt.headless=true -jar server.jar > game_log.txt 2>&1 &
    
    echo -e "${YELLOW}[*] Đang khởi động Web Server (Port 8080)...${NC}"
    cd "$DIR/web"
    pkill -x php 2>/dev/null
    sleep 1
    if [ -f "router.php" ]; then
        nohup php -S 127.0.0.1:8080 router.php > ../web_log.txt 2>&1 &
    else
        nohup php -S 127.0.0.1:8080 > ../web_log.txt 2>&1 &
    fi
    cd ..
    
    echo -e "${GREEN}[+] Đã chạy xong! Game và Web đang hoạt động ngầm.${NC}"
    echo -e "${CYAN}    - Game Server: 127.0.0.1:2236${NC}"
    echo -e "${CYAN}    - Web Đăng Ký: http://127.0.0.1:8080${NC}"
    echo -e "${CYAN}    (Log game xem tại file game_log.txt)${NC}"
    echo -n "Nhấn Enter để về menu..." 
    read -r _enter
    show_menu
}

function stop_all() {
    echo -e "${YELLOW}[*] Đang dừng tất cả tiến trình...${NC}"
    pkill -x java 2>/dev/null
    pkill -x php 2>/dev/null
    
    if mysqladmin --socket="$PREFIX/tmp/mysqld.sock" -u root shutdown 2>/dev/null; then
        sleep 2
    else
        pkill -x mariadbd 2>/dev/null || pkill -x mysqld 2>/dev/null || true
    fi
    
    echo -e "${GREEN}[+] Đã dừng tất cả (Game, Web, DB).${NC}"
    echo -n "Nhấn Enter để về menu..." 
    read -r _enter
    show_menu
}

function create_shortcut() {
    echo -e "${YELLOW}[*] Đang tạo lệnh gõ nhanh 'menu'...${NC}"
    
    if [ -n "$PREFIX" ] && [ -w "$PREFIX/etc/bash.bashrc" ] 2>/dev/null; then
        if ! grep -qF "HTTH-Server-aliases" "$PREFIX/etc/bash.bashrc" 2>/dev/null; then
            echo "# HTTH-Server-aliases" >> "$PREFIX/etc/bash.bashrc"
            echo "alias menu='bash $DIR/menu.sh'" >> "$PREFIX/etc/bash.bashrc"
        fi
    fi
    
    for rc in "$HOME/.bashrc" "$HOME/.bash_profile" "$HOME/.profile"; do
        touch "$rc" 2>/dev/null || continue
        if ! grep -qF "HTTH-Server-aliases" "$rc" 2>/dev/null; then
            echo "# HTTH-Server-aliases" >> "$rc"
            echo "alias menu='bash $DIR/menu.sh'" >> "$rc"
        fi
    done
    
    echo -e "${GREEN}[+] Tạo thành công! Giờ bạn có thể gõ chữ 'menu' ở bất cứ đâu trong Termux để mở Menu này.${NC}"
    echo -n "Nhấn Enter để về menu..." 
    read -r _enter
    show_menu
}

# Run
show_menu

