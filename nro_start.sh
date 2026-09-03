#!/bin/bash
# ============================================================
#  nro_start.sh - CHAY SERVER NGOC RONG HASHIRAMA
#  (dung chung MariaDB tu start_db.sh, web port 8888, game 14445)
# ============================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NRO="$DIR/nro"
PID_DIR="$DIR/.pids"
WEB_PORT="${NRO_WEB_PORT:-8888}"
GAME_PORT=14445

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

is_running() { [ -f "$1" ] && kill -0 "$(cat "$1" 2>/dev/null)" 2>/dev/null; }

# 1. Dam bao MariaDB dung chung dang chay
bash "$DIR/start_db.sh"

# 2. Xac dinh jar
JAR=""
for c in "$NRO/Server/server.jar" "$NRO/server.jar"; do
    [ -f "$c" ] && { JAR="$c"; break; }
done
if [ -z "$JAR" ]; then error "Khong tim thay server.jar Ngoc Rong!"; exit 1; fi

# 3. Chay game server
if is_running "$PID_DIR/nro_server.pid"; then
    info "Ngoc Rong server da chay (PID $(cat "$PID_DIR/nro_server.pid"))."
else
    info "Khoi dong Ngoc Rong game server (port $GAME_PORT)..."
    mkdir -p "$DIR/logs" "$NRO/Server/log"
    cd "$NRO/Server"
    nohup java \
        -Xms256M -Xmx1024M \
        -XX:+UseG1GC \
        -Dfile.encoding=UTF-8 \
        -Djava.awt.headless=true \
        -jar "$JAR" >> "$DIR/logs/nro_server.log" 2>&1 &
    echo $! > "$PID_DIR/nro_server.pid"
    sleep 4
    is_running "$PID_DIR/nro_server.pid" \
        && info "Ngoc Rong server da chay (PID $(cat "$PID_DIR/nro_server.pid"))." \
        || error "Ngoc Rong server loi. Xem: logs/nro_server.log"
fi

# 4. Chay web
if is_running "$PID_DIR/nro_web.pid"; then
    info "Ngoc Rong web da chay."
else
    if command -v php >/dev/null 2>&1; then
        info "Khoi dong Ngoc Rong web (port $WEB_PORT)..."
        cd "$NRO/web/htdocs"
        nohup php -S 0.0.0.0:$WEB_PORT router.php >> "$DIR/logs/nro_web.log" 2>&1 &
        echo $! > "$PID_DIR/nro_web.pid"
        sleep 2
        is_running "$PID_DIR/nro_web.pid" \
            && info "Ngoc Rong web da chay: http://localhost:$WEB_PORT" \
            || warn "Ngoc Rong web loi. Xem: logs/nro_web.log"
    else
        warn "PHP chua cai - bo qua web."
    fi
fi

echo -e "${GREEN}✔ Ngoc Rong Online: game port $GAME_PORT | web http://localhost:$WEB_PORT${NC}"
