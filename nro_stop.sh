#!/bin/bash
# ============================================================
#  nro_stop.sh - DUNG NGOC RONG ANWIN V3 (Game + Web)
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
            info "Da dung $name (PID $pid)."
        fi
        rm -f "$f"
    fi
}

stop_pid "$PID_DIR/nro_server.pid" "Ngoc Rong game server"
stop_pid "$PID_DIR/nro_web.pid"    "Ngoc Rong web server"

echo ""
info "Da dung Ngoc Rong Anwin V3."
