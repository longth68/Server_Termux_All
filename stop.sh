#!/bin/bash
# ============================================================
#  stop.sh - DỪNG TẤT CẢ SERVER (Ninja + HTTH + NRO)
# ============================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_DIR="$DIR/.pids"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }

stop_pid() {
    local f="$1" name="$2"
    if [ -f "$f" ]; then
        local pid
        pid="$(cat "$f" 2>/dev/null)"
        if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
            kill "$pid" 2>/dev/null
            info "Đã dừng $name (PID $pid)."
        fi
        rm -f "$f"
    fi
}

stop_pid "$PID_DIR/ninja_server.pid" "Ninja game server"
stop_pid "$PID_DIR/ninja_web.pid"    "Ninja web server"
stop_pid "$PID_DIR/htth_server.pid"  "HTTH game server"
stop_pid "$PID_DIR/htth_web.pid"     "HTTH web server"
stop_pid "$PID_DIR/nro_server.pid"   "NRO game server"
stop_pid "$PID_DIR/nro_login.pid"    "NRO login server"
stop_pid "$PID_DIR/nro_web.pid"      "NRO web admin"

# Fallback: dừng mọi java/php do bộ cài này khởi động nếu vẫn còn
pkill -f "server/app.jar" 2>/dev/null || true
pkill -f "htth.*server.jar" 2>/dev/null || true
pkill -f "nro.*server.jar" 2>/dev/null || true
pkill -f "ServerLogin.jar" 2>/dev/null || true

echo ""
info "Đã dừng toàn bộ server game. MariaDB vẫn chạy (dùng chung, không tắt)."
info "Muốn tắt MariaDB: bash stop_db.sh"