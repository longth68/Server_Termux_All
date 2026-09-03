#!/bin/bash
# ============================================================
#  nro_start.sh - CHẠY SERVER NGỌC RỒNG HASHIRAMA
#  (dùng chung MariaDB từ start_db.sh, game port 14445, web port 8888, api port 8085)
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

# 1. Đảm bảo MariaDB dùng chung đang chạy
bash "$DIR/start_db.sh"

# 2. Giải nén data game nếu chưa có (từ NRO_data.part1..5)
if [ ! -d "$NRO/resources" ] || [ ! -d "$NRO/data" ]; then
    if [ -f "$NRO/NRO_data.part1" ]; then
        info "Giải nén dữ liệu game NRO (NRO_data.part1..5)..."
        (cd "$NRO" && cat NRO_data.part* > nro_data.tar.gz \
            && tar -xzf nro_data.tar.gz && rm -f nro_data.tar.gz)
        [ -d "$NRO/resources" ] && info "Giải nén NRO xong." || { error "Giải nén NRO thất bại."; exit 1; }
    else
        error "Thiếu file dữ liệu game NRO (NRO_data.part1..5)!"
        exit 1
    fi
fi

# 3. Lắp ráp file client APK nếu chưa có (từ Nro_HanZi_apk.part1..2)
if [ ! -f "$NRO/web/Downloads/Nro HanZi.apk" ] && [ -f "$NRO/web/Downloads/Nro_HanZi_apk.part1" ]; then
    info "Đang lắp ráp file APK Ngọc Rồng (cho phép tải từ Web)..."
    cat "$NRO/web/Downloads/Nro_HanZi_apk.part"* > "$NRO/web/Downloads/Nro HanZi.apk"
fi

# 4. Kiểm tra server.jar
if [ ! -f "$NRO/server.jar" ]; then
    error "Không tìm thấy file $NRO/server.jar!"
    exit 1
fi

# 5. Khởi động NRO Game Server
if is_running "$PID_DIR/nro_server.pid"; then
    info "NRO server đã chạy (PID $(cat "$PID_DIR/nro_server.pid"))."
else
    info "Khởi động NRO game server (port $GAME_PORT, API $API_PORT)..."
    cd "$NRO"
    nohup java \
        -Xms256M -Xmx1024M \
        -XX:+UseG1GC \
        -Dfile.encoding=UTF-8 \
        -Djava.awt.headless=true \
        -jar "$NRO/server.jar" >> "$DIR/logs/nro_server.log" 2>&1 &
    echo $! > "$PID_DIR/nro_server.pid"
    sleep 4
    is_running "$PID_DIR/nro_server.pid" \
        && info "NRO server đã chạy (PID $(cat "$PID_DIR/nro_server.pid"))." \
        || error "NRO server lỗi. Xem: logs/nro_server.log"
fi

# 6. Khởi động Web Admin NRO
if is_running "$PID_DIR/nro_web.pid"; then
    info "NRO web đã chạy."
else
    if command -v php >/dev/null 2>&1; then
        info "Khởi động NRO web admin (port $WEB_PORT)..."
        cd "$NRO/web"
        nohup php -S 0.0.0.0:$WEB_PORT >> "$DIR/logs/nro_web.log" 2>&1 &
        echo $! > "$PID_DIR/nro_web.pid"
        sleep 2
        is_running "$PID_DIR/nro_web.pid" \
            && info "NRO web đã chạy: http://localhost:$WEB_PORT/admin.php" \
            || warn "NRO web lỗi. Xem: logs/nro_web.log"
    else
        warn "PHP chưa cài - bỏ qua web."
    fi
fi

echo -e "${GREEN}✔ Ngọc Rồng Hashirama: game port $GAME_PORT | API port $API_PORT | web http://localhost:$WEB_PORT${NC}"