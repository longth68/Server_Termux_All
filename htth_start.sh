#!/bin/bash
# ============================================================
#  htth_start.sh - CHẠY SERVER HẢI TẶC HOT (Hai Tac Hot)
#  (dùng chung MariaDB từ start_db.sh, web port 8080)
# ============================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
HTTH="$DIR/htth"
PID_DIR="$DIR/.pids"
WEB_PORT="${HTTH_WEB_PORT:-8080}"
GAME_PORT=2236

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

is_running() { [ -f "$1" ] && kill -0 "$(cat "$1" 2>/dev/null)" 2>/dev/null; }

# 1. Đảm bảo MariaDB dùng chung đang chạy
bash "$DIR/start_db.sh"

# 2. Giải nén data game nếu chưa có
if [ ! -d "$HTTH/data/map" ]; then
    if [ -f "$HTTH/HTTH_data.part1" ]; then
        info "Giải nén dữ liệu game (HTTH_data.part1+2+3)..."
        (cd "$HTTH" && cat HTTH_data.part1 HTTH_data.part2 HTTH_data.part3 > htth_data.tar.gz \
            && tar -xzf htth_data.tar.gz && rm -f htth_data.tar.gz)
        [ -d "$HTTH/data/map" ] && info "Giải nén xong." || { error "Giải nén thất bại."; exit 1; }
    else
        error "Thiếu file dữ liệu game (HTTH_data.part1/2/3)!"
        exit 1
    fi
fi

# 3. Xác định jar
JAR=""
for c in "$HTTH/server.jar" "$HTTH/start_server.jar"; do
    [ -f "$c" ] && { JAR="$c"; break; }
done
if [ -z "$JAR" ]; then error "Không tìm thấy server.jar HTTH!"; exit 1; fi

# 4. Chạy game server
if is_running "$PID_DIR/htth_server.pid"; then
    info "HTTH server đã chạy (PID $(cat "$PID_DIR/htth_server.pid"))."
else
    info "Khởi động HTTH game server (port $GAME_PORT)..."
    mkdir -p "$DIR/logs"
    cd "$HTTH"
    nohup java \
        -Xms512M -Xmx1024M \
        -XX:+UseG1GC \
        -Dfile.encoding=UTF-8 \
        -Djava.awt.headless=true \
        -jar "$JAR" >> "$DIR/logs/htth_server.log" 2>&1 &
    echo $! > "$PID_DIR/htth_server.pid"
    sleep 4
    is_running "$PID_DIR/htth_server.pid" \
        && info "HTTH server đã chạy (PID $(cat "$PID_DIR/htth_server.pid"))." \
        || error "HTTH server lỗi. Xem: logs/htth_server.log"
fi

# 5. Chạy web
if is_running "$PID_DIR/htth_web.pid"; then
    info "HTTH web đã chạy."
else
    if command -v php >/dev/null 2>&1; then
        info "Khởi động HTTH web (port $WEB_PORT)..."
        cd "$HTTH/web"
        if [ -f "router.php" ]; then
            nohup php -S 127.0.0.1:$WEB_PORT router.php >> "$DIR/logs/htth_web.log" 2>&1 &
        else
            nohup php -S 127.0.0.1:$WEB_PORT >> "$DIR/logs/htth_web.log" 2>&1 &
        fi
        echo $! > "$PID_DIR/htth_web.pid"
        sleep 2
        is_running "$PID_DIR/htth_web.pid" \
            && info "HTTH web đã chạy: http://localhost:$WEB_PORT" \
            || warn "HTTH web lỗi. Xem: logs/htth_web.log"
    else
        warn "PHP chưa cài - bỏ qua web."
    fi
fi

echo -e "${GREEN}✔ Hai Tac Hot: game port $GAME_PORT | web http://localhost:$WEB_PORT${NC}"
