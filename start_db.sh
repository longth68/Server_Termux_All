#!/bin/bash
# ============================================================
#  start_db.sh - KHỞI ĐỘNG MARIADB DÙNG CHUNG (cho cả 3 game)
#  + Tạo DB schoolzz (Ninja), htth (Hải Tặc Hot) và hashirama (Ngọc Rồng) nếu chưa có
# ============================================================

PREFIX="${PREFIX:-/data/data/com.termux/files/usr}"
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

MYSQL_CMD=""
for c in mariadb mysql; do command -v "$c" >/dev/null 2>&1 && { MYSQL_CMD="$c"; break; }; done
MYSQLD_START=""
for c in mariadbd-safe mysqld_safe mariadbd mysqld; do
    command -v "$c" >/dev/null 2>&1 && { MYSQLD_START="$c"; break; }
done

if [ -z "$MYSQL_CMD" ] || [ -z "$MYSQLD_START" ]; then
    error "MariaDB chưa cài. Chạy: bash install.sh (hoặc pkg install mariadb)"
    exit 1
fi

# 1. Kiểm tra / khởi động MariaDB
if mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null; then
    info "MariaDB đã chạy."
else
    info "Khởi động MariaDB..."
    rm -f "$PREFIX/tmp/mysqld.sock" "$PREFIX/tmp/mysqld.sock.lock" \
          "$PREFIX/var/run/mysqld/mysqld.pid" 2>/dev/null || true
    mkdir -p "$PREFIX/tmp" "$PREFIX/var/lib/mysql" "$PREFIX/var/run/mysqld" "$PREFIX/var/log/mysql"
    "$MYSQLD_START" --no-defaults \
        --datadir="$PREFIX/var/lib/mysql" \
        --socket="$PREFIX/tmp/mysqld.sock" \
        --pid-file="$PREFIX/var/run/mysqld/mysqld.pid" \
        --log-error="$PREFIX/var/log/mysql/mariadbd.err" \
        >/dev/null 2>&1 &
    _waited=0
    while ! mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null && [ "$_waited" -lt 30 ]; do
        sleep 1; _waited=$((_waited + 1))
    done
    mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null \
        && info "MariaDB đã chạy (sau ${_waited}s)." \
        || warn "MariaDB chưa sẵn sàng. Xem: cat $PREFIX/var/log/mysql/mariadbd.err"
fi

# 2. Tạo user root cho localhost và 127.0.0.1
"$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u "$(whoami)" \
    -e "CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
        CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
        FLUSH PRIVILEGES;" 2>/dev/null || true

# 3. Tạo database + import cho từng game
# --- Ninja School: DB schoolzz ---
if ! "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root -e "USE schoolzz" 2>/dev/null; then
    info "Tạo database schoolzz + import (Ninja)..."
    "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root \
        -e "CREATE DATABASE IF NOT EXISTS schoolzz CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
    for f in "$DIR/ninja/server/exe_nsoz.sql" "$DIR/ninja/exe_nsoz.sql"; do
        if [ -f "$f" ]; then
            "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root schoolzz < "$f" \
                && info "Import DB Ninja xong." || warn "Import DB Ninja lỗi."
            break
        fi
    done
fi
"$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root schoolzz -e \
    "CREATE TABLE IF NOT EXISTS server_status (id INT PRIMARY KEY AUTO_INCREMENT, online INT DEFAULT 0, bots INT DEFAULT 0, memory_mb INT DEFAULT 0, updated_at DATETIME DEFAULT NOW());
     INSERT IGNORE INTO server_status (id, online, bots) VALUES (1,0,0);
     CREATE TABLE IF NOT EXISTS web_admin_commands (id INT PRIMARY KEY AUTO_INCREMENT, command VARCHAR(50) NOT NULL, data TEXT DEFAULT '{}', status TINYINT DEFAULT 0, created_at DATETIME DEFAULT NOW());" 2>/dev/null || true

# --- Hai Tac Hot: DB htth ---
if ! "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root -e "USE htth" 2>/dev/null; then
    info "Tạo database htth + import (Hải Tặc Hot)..."
    "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root -e "CREATE DATABASE IF NOT EXISTS htth CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" 2>/dev/null
    if [ -f "$DIR/htth/database/htth_full.sql" ]; then
        "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root htth < "$DIR/htth/database/htth_full.sql" \
            && info "Import DB HTTH xong." || warn "Import DB HTTH lỗi."
    fi
fi

# --- Ngoc Rong: DB hashirama ---
if ! "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root -e "USE hashirama" 2>/dev/null; then
    info "Tạo database hashirama + import (Ngọc Rồng)..."
    "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root -e "CREATE DATABASE IF NOT EXISTS hashirama CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" 2>/dev/null
    for f in "$DIR/nro/database/hashirama.sql" "$DIR/nro/hashirama.sql"; do
        if [ -f "$f" ]; then
            "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root hashirama < "$f" \
                && info "Import DB Ngọc Rồng xong." || warn "Import DB Ngọc Rồng lỗi."
            break
        fi
    done
fi

echo ""
info "MariaDB sẵn sàng: DB schoolzz (Ninja) + DB htth (Hải Tặc Hot) + DB hashirama (Ngọc Rồng)."
