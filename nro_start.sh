#!/bin/bash
# ============================================================
#  nro_start.sh - CHAY SERVER NGOC RONG ANWIN V3 (monolith)
#  (dung chung MariaDB tu start_db.sh, web 8888, game 14445, api 8085)
#  Anwin KHONG can ServerLogin rieng - chi 1 process game duy nhat
# ============================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NRO="$DIR/nro"
PID_DIR="$DIR/.pids"
WEB_PORT="${NRO_WEB_PORT:-8888}"
GAME_PORT=14445
API_PORT=8085

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

is_running() { [ -f "$1" ] && kill -0 "$(cat "$1" 2>/dev/null)" 2>/dev/null; }

# 1. Dam bao MariaDB dung chung dang chay (+ DB awnv3)
bash "$DIR/start_db.sh"

# 2. Giai nen data game neu chua co
if [ ! -d "$NRO/Server/data/map" ]; then
    if ls "$NRO"/ANWIN_data.tar.* >/dev/null 2>&1; then
        info "Giai nen du lieu game Anwin (ANWIN_data.tar.*)..."
        mkdir -p "$NRO/Server"
        (cd "$NRO/Server" && cat "$NRO"/ANWIN_data.tar.* > anwin_data.tar \
            && tar -xf anwin_data.tar && rm -f anwin_data.tar)
        [ -d "$NRO/Server/data/map" ] && info "Giai nen xong." || { error "Giai nen that bai."; exit 1; }
    else
        error "Thieu file du lieu game (ANWIN_data.tar.*)! Chay: bash update_nro.sh (tu tai bu part thieu)"
        exit 1
    fi
fi

# 3. Chay game server (classpath, khong jar)
if is_running "$PID_DIR/nro_server.pid"; then
    info "Ngoc Rong server da chay (PID $(cat "$PID_DIR/nro_server.pid"))."
else
    if [ ! -d "$NRO/Server/classes" ]; then error "Thieu $NRO/Server/classes! Chay: git pull"; exit 1; fi
    info "Khoi dong Ngoc Rong game server (port $GAME_PORT, api $API_PORT)..."
    mkdir -p "$DIR/logs" "$NRO/Server/log"
    cd "$NRO/Server"
    nohup java \
        -Xms256M -Xmx1024M \
        -XX:+UseG1GC \
        -Dfile.encoding=UTF-8 \
        -Djava.awt.headless=true \
        -Danwin.api.port=$API_PORT \
        -cp "classes:lib/*" nro.server.ServerManager >> "$DIR/logs/nro_server.log" 2>&1 &
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

echo -e "${GREEN}✔ Ngoc Rong Anwin V3: game port $GAME_PORT | web http://localhost:$WEB_PORT${NC}"
