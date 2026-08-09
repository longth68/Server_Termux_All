#!/bin/bash
# ============================================================
#  start.sh - CHẠY HTTH SERVER CHỈ 1 CLICK (Termux)
#  Tự động: giải nén data + khởi động DB + import + game + web
# ============================================================
cd "$(dirname "$0")"

# PREFIX fallback
if [ -z "$PREFIX" ]; then
    PREFIX="/data/data/com.termux/files/usr"
fi

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

echo -e "${GREEN}=============================================="
echo "   HTTH Server - START ALL"
echo "==============================================${NC}"

# 1. Giải nén data game nếu chưa có
if [ ! -d "data/map" ]; then
    if [ -f "HTTH_data.part1" ]; then
        info "Giải nén dữ liệu game (HTTH_data.part1+2+3)..."
        cat HTTH_data.part1 HTTH_data.part2 HTTH_data.part3 > data_temp.tar.gz
        tar -xzf data_temp.tar.gz && rm -f data_temp.tar.gz
        [ -d "data/map" ] && info "Giải nén xong." || { error "Giải nén thất bại."; exit 1; }
    else
        error "Thiếu file dữ liệu game (HTTH_data.part1/2/3)!"
        exit 1
    fi
fi

# 2. Khởi động MariaDB
MYSQL_CMD="mysql"
if ! command -v mysql >/dev/null 2>&1 && command -v mariadb >/dev/null 2>&1; then
    MYSQL_CMD="mariadb"
fi

if ! command -v mariadbd >/dev/null 2>&1 && ! command -v mysqld >/dev/null 2>&1; then
    error "MariaDB chưa cài. Chạy: pkg install mariadb"
    exit 1
fi

if mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null; then
    info "MariaDB đã chạy."
else
    info "Khởi động MariaDB..."
    pkill -x mariadbd 2>/dev/null || pkill -x mysqld 2>/dev/null || true
    rm -f "$PREFIX/tmp/mysqld.sock" "$PREFIX/tmp/mysqld.sock.lock" "$PREFIX/var/run/mysqld/mysqld.pid" 2>/dev/null || true
    mkdir -p "$PREFIX/tmp" "$PREFIX/var/lib/mysql" "$PREFIX/var/run/mysqld" "$PREFIX/var/log/mysql"
    
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
    
    _waited=0
    while ! mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null && [ $_waited -lt 15 ]; do
        sleep 1; _waited=$((_waited + 1))
    done
    mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null && info "MariaDB đã chạy." || warn "MariaDB chưa sẵn sàng."
fi

# Tạo user root nếu chưa có (Termux)
"$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u $(whoami) -e "CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY ''; GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;" 2>/dev/null || true

# 3. Import database nếu chưa có
if ! "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root -e "USE htth" 2>/dev/null; then
    info "Import database htth..."
    "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root -e "CREATE DATABASE IF NOT EXISTS htth;" 2>/dev/null
    if [ -f "database/htth_full.sql" ]; then
        "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root htth < "database/htth_full.sql" && info "Import DB xong." || warn "Import DB lỗi."
    else
        warn "Không tìm thấy database/htth_full.sql"
    fi
fi

# 4. Chạy server game (nền)
info "Khởi động game server (port 2236)..."
nohup java -Xms512M -Xmx1024M -XX:+UseG1GC -Dfile.encoding=UTF-8 -Djava.awt.headless=true -jar server.jar > game_log.txt 2>&1 &

# 5. Chạy web (nền)
sleep 2
info "Khởi động web server (port 8080)..."
cd "web"
if [ -f "router.php" ]; then
    nohup php -S 127.0.0.1:8080 router.php > ../web_log.txt 2>&1 &
else
    nohup php -S 127.0.0.1:8080 > ../web_log.txt 2>&1 &
fi
cd ..

sleep 2
echo ""
echo -e "${GREEN}=============================================="
echo "   HTTH SERVER ĐANG CHẠY!"
echo "==============================================${NC}"
echo -e "  🎮 Game : port 2236"
echo -e "  🌐 Web  : http://localhost:8080"
echo -e "  📋 Log  : game_log.txt / web_log.txt"
echo -e "  ⏹ Dừng : killall java php"
echo ""
