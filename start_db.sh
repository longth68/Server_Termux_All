#!/bin/bash
# ============================================================
# start_db.sh - KHỞI ĐỘNG MARIADB DÙNG CHUNG (3 GAME)
# Ninja School + Hải Tặc Hot + Ngọc Rồng Hashirama
# Bản sửa: kiểm tra MariaDB thực sự, không báo thành công giả
# ============================================================

set -u

PREFIX="${PREFIX:-/data/data/com.termux/files/usr}"
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

SOCKET="$PREFIX/tmp/mysqld.sock"
PIDFILE="$PREFIX/var/run/mysqld/mysqld.pid"
LOGFILE="$PREFIX/var/log/mysql/mariadbd.err"
DATADIR="$PREFIX/var/lib/mysql"

MYSQL_CMD=""
for c in mariadb mysql; do
    if command -v "$c" >/dev/null 2>&1; then
        MYSQL_CMD="$c"
        break
    fi
done

MYSQLADMIN=""
for c in mariadb-admin mysqladmin; do
    if command -v "$c" >/dev/null 2>&1; then
        MYSQLADMIN="$c"
        break
    fi
done

MYSQLD_START=""
for c in mariadbd-safe mysqld_safe mariadbd mysqld; do
    if command -v "$c" >/dev/null 2>&1; then
        MYSQLD_START="$c"
        break
    fi
done

if [ -z "$MYSQL_CMD" ] || [ -z "$MYSQLADMIN" ] || [ -z "$MYSQLD_START" ]; then
    error "Không tìm thấy MariaDB."
    echo "Chạy: pkg install mariadb"
    exit 1
fi

mkdir -p "$PREFIX/tmp" \
         "$PREFIX/var/run/mysqld" \
         "$PREFIX/var/log/mysql" \
         "$DATADIR"

# ------------------------------------------------------------
# Hàm kiểm tra MariaDB thật sự hoạt động
# ------------------------------------------------------------
db_ping() {
    "$MYSQLADMIN" --protocol=socket --socket="$SOCKET" ping --silent >/dev/null 2>&1
}

# ------------------------------------------------------------
# 1. Kiểm tra / khởi động MariaDB
# ------------------------------------------------------------
if db_ping; then
    info "MariaDB đã chạy."
else
    info "MariaDB chưa chạy. Đang khởi động..."

    # Chỉ xóa socket/pid cũ; không xóa database.
    rm -f "$SOCKET" "$SOCKET.lock" "$PIDFILE" 2>/dev/null || true

    if [ -f "$LOGFILE" ]; then
        cp "$LOGFILE" "${LOGFILE}.old" 2>/dev/null || true
        : > "$LOGFILE"
    fi

    # Dùng mariadbd-safe nếu có; nếu không thì chạy mariadbd trực tiếp.
    if [[ "$MYSQLD_START" == *"safe" ]]; then
        "$MYSQLD_START" \
            --datadir="$DATADIR" \
            --socket="$SOCKET" \
            --pid-file="$PIDFILE" \
            --log-error="$LOGFILE" \
            >/dev/null 2>&1 &
    else
        "$MYSQLD_START" \
            --datadir="$DATADIR" \
            --socket="$SOCKET" \
            --pid-file="$PIDFILE" \
            --log-error="$LOGFILE" \
            --skip-networking=0 \
            >/dev/null 2>&1 &
    fi

    _waited=0
    while [ "$_waited" -lt 30 ]; do
        if db_ping; then
            break
        fi

        # Nếu process đã chết thì không cần chờ đủ 30 giây.
        if [ -f "$PIDFILE" ]; then
            _pid="$(cat "$PIDFILE" 2>/dev/null || true)"
            if [ -n "$_pid" ] && ! kill -0 "$_pid" 2>/dev/null; then
                break
            fi
        fi

        sleep 1
        _waited=$((_waited + 1))
    done

    if ! db_ping; then
        error "MariaDB KHÔNG khởi động được."
        echo
        echo "----- LOG MariaDB -----"
        if [ -f "$LOGFILE" ]; then
            tail -n 40 "$LOGFILE"
        else
            echo "Không tìm thấy log: $LOGFILE"
        fi
        echo "-----------------------"
        echo
        error "Không import database vì MariaDB chưa sẵn sàng."
        exit 1
    fi

    info "MariaDB đã chạy (sau ${_waited}s)."
fi

# ------------------------------------------------------------
# Hàm chạy SQL qua socket
# ------------------------------------------------------------
sql() {
    "$MYSQL_CMD" --protocol=socket --socket="$SOCKET" -u root "$@"
}

# ------------------------------------------------------------
# 2. Tạo user root nếu cần
# ------------------------------------------------------------
if ! sql -e "SELECT 1;" >/dev/null 2>&1; then
    error "Đã kết nối socket nhưng không đăng nhập được root."
    error "Kiểm tra quyền/user MariaDB."
    exit 1
fi

sql -e "
CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;
" >/dev/null 2>&1 || true

# ------------------------------------------------------------
# Hàm đếm table
# ------------------------------------------------------------
table_count() {
    local db="$1"
    sql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${db}';" 2>/dev/null || echo "0"
}

# ------------------------------------------------------------
# Hàm import database
# ------------------------------------------------------------
import_db() {
    local db="$1"
    local label="$2"
    local sqlfile="$3"

    if [ ! -f "$sqlfile" ]; then
        warn "Không tìm thấy file DB $label: $sqlfile"
        return 1
    fi

    info "Tạo/import DB $db ($label)..."

    if ! sql -e "CREATE DATABASE IF NOT EXISTS \`$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" >/dev/null 2>&1; then
        error "Không tạo được database $db."
        return 1
    fi

    if ! "$MYSQL_CMD" --protocol=socket --socket="$SOCKET" -u root \
        --default-character-set=utf8mb4 "$db" < "$sqlfile"; then
        error "Import DB $label thất bại."
        return 1
    fi

    local count
    count="$(table_count "$db")"
    if [ -z "$count" ] || [ "$count" -eq 0 ] 2>/dev/null; then
        error "Import $label đã chạy nhưng database $db vẫn không có table."
        return 1
    fi

    info "Import DB $label xong ($count table)."
    return 0
}

# ------------------------------------------------------------
# 3. Ninja School - schoolzz
# ------------------------------------------------------------
NINJA_OK=0
NINJA_TBLS="$(table_count schoolzz)"
if [ -n "$NINJA_TBLS" ] && [ "$NINJA_TBLS" -gt 0 ] 2>/dev/null; then
    info "DB schoolzz đã có dữ liệu ($NINJA_TBLS table)."
    NINJA_OK=1
else
    NINJA_SQL=""
    for f in \
        "$DIR/ninja/server/exe_nsoz.sql" \
        "$DIR/ninja/exe_nsoz.sql"
    do
        if [ -f "$f" ]; then
            NINJA_SQL="$f"
            break
        fi
    done

    if [ -n "$NINJA_SQL" ]; then
        import_db "schoolzz" "Ninja School" "$NINJA_SQL" && NINJA_OK=1
    else
        warn "Không tìm thấy file SQL Ninja School."
    fi
fi

# ------------------------------------------------------------
# Các bảng phụ cho Ninja/Web
# ------------------------------------------------------------
if [ "$NINJA_OK" -eq 1 ]; then
    if ! sql schoolzz -e "
        CREATE TABLE IF NOT EXISTS server_status (
            id INT PRIMARY KEY AUTO_INCREMENT,
            online INT DEFAULT 0,
            bots INT DEFAULT 0,
            memory_mb INT DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        INSERT IGNORE INTO server_status (id, online, bots) VALUES (1,0,0);

        CREATE TABLE IF NOT EXISTS web_admin_commands (
            id INT PRIMARY KEY AUTO_INCREMENT,
            command VARCHAR(50) NOT NULL,
            data TEXT DEFAULT '{}',
            status TINYINT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS bot_status (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL UNIQUE,
            level INT DEFAULT 1,
            map_id INT DEFAULT 0,
            zone_id INT DEFAULT 0,
            x INT DEFAULT 0,
            y INT DEFAULT 0,
            hp BIGINT DEFAULT 0,
            max_hp BIGINT DEFAULT 0,
            state VARCHAR(30) DEFAULT '',
            personality VARCHAR(255) DEFAULT '',
            top_need VARCHAR(30) DEFAULT '',
            gold BIGINT DEFAULT 0,
            gender TINYINT DEFAULT 0,
            class_id TINYINT DEFAULT 0,
            goal VARCHAR(30) DEFAULT '',
            damage INT DEFAULT 0,
            friends INT DEFAULT 0,
            online_min INT DEFAULT 0,
            gear TEXT,
            needs TEXT,
            profile TEXT,
            near VARCHAR(60) DEFAULT '',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    " >/dev/null 2>&1; then
        warn "Không tạo được bảng phụ Ninja."
    fi
fi

# ------------------------------------------------------------
# 4. Hải Tặc Hot - htth
# ------------------------------------------------------------
HTTH_OK=0
HTTH_TBLS="$(table_count htth)"
if [ -n "$HTTH_TBLS" ] && [ "$HTTH_TBLS" -gt 0 ] 2>/dev/null; then
    info "DB htth đã có dữ liệu ($HTTH_TBLS table)."
    HTTH_OK=1
else
    HTTH_SQL="$DIR/htth/database/htth_full.sql"
    if [ -f "$HTTH_SQL" ]; then
        import_db "htth" "Hải Tặc Hot" "$HTTH_SQL" && HTTH_OK=1
    else
        warn "Không tìm thấy file SQL Hải Tặc Hot: $HTTH_SQL"
    fi
fi

# ------------------------------------------------------------
# 5. Ngọc Rồng Anwin V3 - awnv3 (DB hashirama ban cu duoc giu nguyen)
# ------------------------------------------------------------
NRO_OK=0
NRO_TBLS="$(table_count awnv3)"
if [ -n "$NRO_TBLS" ] && [ "$NRO_TBLS" -gt 0 ] 2>/dev/null; then
    info "DB awnv3 đã có dữ liệu ($NRO_TBLS table)."
    NRO_OK=1
else
    NRO_SQL=""
    for f in \
        "$DIR/nro/database/awnv3.sql"
    do
        if [ -f "$f" ]; then
            NRO_SQL="$f"
            break
        fi
    done

    if [ -n "$NRO_SQL" ]; then
        import_db "awnv3" "Ngọc Rồng Anwin V3" "$NRO_SQL" && NRO_OK=1
    else
        warn "Không tìm thấy file SQL Ngọc Rồng."
    fi
fi

# ------------------------------------------------------------
# 6. Kiểm tra cuối cùng - KHÔNG báo giả
# ------------------------------------------------------------
echo
if [ "$NINJA_OK" -eq 1 ] && [ "$HTTH_OK" -eq 1 ] && [ "$NRO_OK" -eq 1 ]; then
    info "MariaDB sẵn sàng."
    info "DB schoolzz (Ninja) + DB htth (Hải Tặc Hot) + DB awnv3 (Ngọc Rồng Anwin V3) đều OK."
    exit 0
fi

error "MariaDB đang chạy nhưng database chưa hoàn tất."
echo
echo "Trạng thái:"
echo "  Ninja School : $([ "$NINJA_OK" -eq 1 ] && echo OK || echo LOI)"
echo "  Hải Tặc Hot  : $([ "$HTTH_OK" -eq 1 ] && echo OK || echo LOI)"
echo "  Ngọc Rồng    : $([ "$NRO_OK" -eq 1 ] && echo OK || echo LOI)"
echo
echo "Có thể xem log MariaDB bằng:"
echo "  cat $LOGFILE"
exit 1
