#!/bin/bash
# ============================================================
#  stop_db.sh - DỪNG MARIADB DÙNG CHUNG
# ============================================================

PREFIX="${PREFIX:-/data/data/com.termux/files/usr}"
GREEN='\033[0;32m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

if mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null; then
    mysqladmin --socket="$PREFIX/tmp/mysqld.sock" -u root shutdown 2>/dev/null \
        && info "Đã dừng MariaDB." \
        || { pkill -x mariadbd 2>/dev/null; pkill -x mysqld 2>/dev/null; info "Đã dừng MariaDB (kill)."; }
else
    info "MariaDB không chạy."
fi
