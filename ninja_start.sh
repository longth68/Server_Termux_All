#!/bin/bash
# ============================================================
#  ninja_start.sh - CHẠY SERVER NINJA SCHOOL
#  (dùng chung MariaDB từ start_db.sh, web port 8000)
# ============================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NINJA="$DIR/ninja"
PID_DIR="$DIR/.pids"
WEB_PORT="${WEB_PORT:-8000}"
GAME_PORT=14444

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

is_running() { [ -f "$1" ] && kill -0 "$(cat "$1" 2>/dev/null)" 2>/dev/null; }

# 1. Đảm bảo MariaDB dùng chung đang chạy
bash "$DIR/start_db.sh"

# 2. Xác định jar
JAR=""
for c in "$NINJA/server/app.jar" "$NINJA/app.jar" "$NINJA/server/Nso.jar"; do
    [ -f "$c" ] && { JAR="$c"; break; }
done
if [ -z "$JAR" ]; then error "Không tìm thấy app.jar Ninja!"; exit 1; fi

# 3. Chạy game server
if is_running "$PID_DIR/ninja_server.pid"; then
    info "Ninja server đã chạy (PID $(cat "$PID_DIR/ninja_server.pid"))."
else
    info "Khởi động Ninja game server (port $GAME_PORT)..."
    mkdir -p "$DIR/logs"
    cd "$NINJA/server"
    nohup java \
        -Xms256M -Xmx512M \
        -XX:+UseG1GC \
        -Dfile.encoding=UTF-8 \
        -Dstdout.encoding=UTF-8 \
        -Djava.awt.headless=true \
        -jar "$JAR" >> "$DIR/logs/ninja_server.log" 2>&1 &
    echo $! > "$PID_DIR/ninja_server.pid"
    sleep 4
    is_running "$PID_DIR/ninja_server.pid" \
        && info "Ninja server đã chạy (PID $(cat "$PID_DIR/ninja_server.pid"))." \
        || error "Ninja server lỗi. Xem: logs/ninja_server.log"
fi

# 4. Chạy web
if is_running "$PID_DIR/ninja_web.pid"; then
    info "Ninja web đã chạy."
else
    if command -v php >/dev/null 2>&1; then
        info "Khởi động Ninja web (port $WEB_PORT)..."
        cd "$NINJA/server/web"
        if [ -f "router.php" ]; then
            nohup php -S 127.0.0.1:$WEB_PORT router.php >> "$DIR/logs/ninja_web.log" 2>&1 &
        else
            nohup php -S 127.0.0.1:$WEB_PORT >> "$DIR/logs/ninja_web.log" 2>&1 &
        fi
        echo $! > "$PID_DIR/ninja_web.pid"
        sleep 2
        is_running "$PID_DIR/ninja_web.pid" \
            && info "Ninja web đã chạy: http://localhost:$WEB_PORT" \
            || warn "Ninja web lỗi. Xem: logs/ninja_web.log"
    else
        warn "PHP chưa cài - bỏ qua web."
    fi
fi

echo -e "${GREEN}✔ Ninja School: game port $GAME_PORT | web http://localhost:$WEB_PORT${NC}"
