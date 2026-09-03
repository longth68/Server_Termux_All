#!/bin/bash
# ============================================================
#  nro_start.sh - CHáº Y SERVER NGá»ŒC Rá»’NG HASHIRAMA
#  (dĂ¹ng chung MariaDB tá»« start_db.sh, game port 14445, web port 8888, api port 8085)
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

mkdir -p "$PID_DIR" "$DIR/logs"

# 1. Äáº£m báº£o MariaDB dĂ¹ng chung Ä‘ang cháº¡y
bash "$DIR/start_db.sh"

# 2. Giáº£i nĂ©n data game náº¿u chÆ°a cĂ³ (tá»« NRO_data.part1..5)
if [ ! -d "$NRO/resources" ] || [ ! -d "$NRO/data" ]; then
    if [ -f "$NRO/NRO_data.part1" ]; then
        info "Giáº£i nĂ©n dá»¯ liá»‡u game NRO (NRO_data.part1..5)..."
        (cd "$NRO" && cat NRO_data.part* > nro_data.tar.gz \
            && tar -xzf nro_data.tar.gz && rm -f nro_data.tar.gz)
        [ -d "$NRO/resources" ] && info "Giáº£i nĂ©n NRO xong." || { error "Giáº£i nĂ©n NRO tháº¥t báº¡i."; exit 1; }
    else
        error "Thiáº¿u file dá»¯ liá»‡u game NRO (NRO_data.part1..5)!"
        exit 1
    fi
fi

# 3. Láº¯p rĂ¡p file client APK náº¿u chÆ°a cĂ³ (tá»« Nro_HanZi_apk.part1..2)
if [ ! -f "$NRO/web/Downloads/Nro HanZi.apk" ] && [ -f "$NRO/web/Downloads/Nro_HanZi_apk.part1" ]; then
    info "Äang láº¯p rĂ¡p file APK Ngá»c Rá»“ng (cho phĂ©p táº£i tá»« Web)..."
    cat "$NRO/web/Downloads/Nro_HanZi_apk.part"* > "$NRO/web/Downloads/Nro HanZi.apk"
fi

# 4. Kiá»ƒm tra server.jar
if [ ! -f "$NRO/server.jar" ]; then
    error "KhĂ´ng tĂ¬m tháº¥y file $NRO/server.jar!"
    exit 1
fi

# 5. Khá»Ÿi Ä‘á»™ng NRO Game Server
if is_running "$PID_DIR/nro_server.pid"; then
    info "NRO server Ä‘Ă£ cháº¡y (PID $(cat "$PID_DIR/nro_server.pid"))."
else
    info "Khá»Ÿi Ä‘á»™ng NRO game server (port $GAME_PORT, API $API_PORT)..."
    cd "$NRO"
    nohup java \
        -Xms128M -Xmx1024M \
        
        -Dfile.encoding=UTF-8 \
        -Djava.awt.headless=true \
        -jar "$NRO/server.jar" >> "$DIR/logs/nro_server.log" 2>&1 &
    echo $! > "$PID_DIR/nro_server.pid"
    sleep 4
    is_running "$PID_DIR/nro_server.pid" \
        && info "NRO server Ä‘Ă£ cháº¡y (PID $(cat "$PID_DIR/nro_server.pid"))." \
        || error "NRO server lá»—i. Xem: logs/nro_server.log"
fi

# 6. Khá»Ÿi Ä‘á»™ng Web Admin NRO
if is_running "$PID_DIR/nro_web.pid"; then
    info "NRO web Ä‘Ă£ cháº¡y."
else
    if command -v php >/dev/null 2>&1; then
        info "Khá»Ÿi Ä‘á»™ng NRO web admin (port $WEB_PORT)..."
        cd "$NRO/web"
        nohup php -S 0.0.0.0:$WEB_PORT >> "$DIR/logs/nro_web.log" 2>&1 &
        echo $! > "$PID_DIR/nro_web.pid"
        sleep 2
        is_running "$PID_DIR/nro_web.pid" \
            && info "NRO web Ä‘Ă£ cháº¡y: http://localhost:$WEB_PORT/admin.php" \
            || warn "NRO web lá»—i. Xem: logs/nro_web.log"
    else
        warn "PHP chÆ°a cĂ i - bá» qua web."
    fi
fi

echo -e "${GREEN}âœ” Ngá»c Rá»“ng Hashirama: game port $GAME_PORT | API port $API_PORT | web http://localhost:$WEB_PORT${NC}"