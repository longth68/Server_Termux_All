#!/bin/bash
cd "$(dirname "$0")"

# PREFIX fallback
if [ -z "$PREFIX" ]; then
    PREFIX="/data/data/com.termux/files/usr"
fi

# Tự phát hiện lệnh DB (Termux: mariadb)
MYSQL_CMD="mysql"
if ! command -v mysql >/dev/null 2>&1 && command -v mariadb >/dev/null 2>&1; then
    MYSQL_CMD="mariadb"
fi

if [ -z "$MYSQL_CMD" ]; then
    echo "Khong tim thay MariaDB. Hay cai: pkg install mariadb"
    exit 1
fi

# Khởi động DB nếu cần
if ! mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null; then
    echo "Dang khoi dong MariaDB..."
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
    sleep 4
fi

# Tạo user root nếu chưa có (Termux)
"$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u $(whoami) -e "CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY ''; GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;" 2>/dev/null || true

echo "Import database htth tu database/htth_full.sql..."
"$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root -e "CREATE DATABASE IF NOT EXISTS htth;" 2>/dev/null
if [ -f "database/htth_full.sql" ]; then
    "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root htth < "database/htth_full.sql" && echo "Import thanh cong." || echo "Import that bai!"
else
    echo "Khong tim thay database/htth_full.sql"
fi
