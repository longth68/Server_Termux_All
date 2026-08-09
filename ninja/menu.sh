#!/bin/bash
# ============================================================
#  NinjaServerTermux - Control Menu v2.0
#  Quan ly server Ninja School Online tren Termux
# ============================================================

# Chi chay trong interactive shell (tranh loop khi source tu script)
# case $- in
#     *i*) ;; # interactive - tiep tuc
#     *)   exit 0 ;;  # non-interactive - thoat
# esac

NINJA_DIR="$HOME/ninja-server"
WEB_PORT="8000"
SERVER_PORT="14444"
PID_DIR="$NINJA_DIR/.pids"
DB_NAME="schoolzz"

# PREFIX fallback neu khong duoc set (khi goi tu .bashrc tu dong)
if [ -z "$PREFIX" ]; then
    PREFIX="/data/data/com.termux/files/usr"
fi

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

mkdir -p "$PID_DIR" "$NINJA_DIR/logs"

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

is_running() {
    local pid
    if [ -f "$1" ]; then
        pid=$(cat "$1" 2>/dev/null)
        if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
            return 0
        fi
    fi
    return 1
}

server_running() { is_running "$PID_DIR/server.pid"; }
web_running()    { is_running "$PID_DIR/web.pid"; }
db_running()     { mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null; }

check_requirements() {
    if [ ! -f "$NINJA_DIR/Nso-jar-with-dependencies.jar" ]; then
        error "Khong tim thay server jar!"
        echo "        Hay chay install.sh truoc hoac dua file jar vao $NINJA_DIR"
        return 1
    fi
    command -v java >/dev/null 2>&1 || { error "Java chua duoc cai dat!"; return 1; }
    return 0
}

start_db() {
    if db_running; then
        info "MariaDB da dang chay."
        return
    fi
    info "Khoi dong MariaDB..."

    # Don socket/lock/pid cu
    rm -f "$PREFIX/tmp/mysqld.sock" \
          "$PREFIX/tmp/mysqld.sock.lock" \
          "$PREFIX/var/lib/mysql/mysql.sock" \
          "$PREFIX/var/run/mysqld/mysqld.pid" 2>/dev/null || true

    mkdir -p "$PREFIX/var/run/mysqld" "$PREFIX/var/log/mysql"

    if command -v mariadbd-safe >/dev/null 2>&1; then
        mariadbd-safe --no-defaults \
            --datadir="$PREFIX/var/lib/mysql" \
            --socket="$PREFIX/tmp/mysqld.sock" \
            --pid-file="$PREFIX/var/run/mysqld/mysqld.pid" \
            --log-error="$PREFIX/var/log/mysql/mariadbd.err" \
            >/dev/null 2>&1 &
    elif command -v mysqld_safe >/dev/null 2>&1; then
        mysqld_safe --no-defaults \
            --datadir="$PREFIX/var/lib/mysql" \
            --socket="$PREFIX/tmp/mysqld.sock" \
            --pid-file="$PREFIX/var/run/mysqld/mysqld.pid" \
            --log-error="$PREFIX/var/log/mysql/mariadbd.err" \
            >/dev/null 2>&1 &
    elif command -v mariadbd >/dev/null 2>&1; then
        mariadbd --no-defaults \
            --datadir="$PREFIX/var/lib/mysql" \
            --socket="$PREFIX/tmp/mysqld.sock" \
            --pid-file="$PREFIX/var/run/mysqld/mysqld.pid" \
            --log-error="$PREFIX/var/log/mysql/mariadbd.err" \
            >/dev/null 2>&1 &
    else
        error "Khong tim thay mysqld/mariadbd. Cai lai: pkg install mariadb"
        return
    fi

    local ok=0
    for i in $(seq 1 30); do
        sleep 1
        if db_running; then ok=1; break; fi
    done

    if [ "$ok" -eq 1 ]; then
        info "MariaDB da chay (sau ${i}s)."
    else
        warn "MariaDB chua san sang sau 30s."
        warn "Xem log: cat $PREFIX/var/log/mysql/mariadbd.err"
    fi
}

stop_db() {
    if db_running; then
        info "Dung MariaDB..."
        if mysqladmin -u root --socket="$PREFIX/tmp/mysqld.sock" shutdown 2>/dev/null; then
            sleep 2
        else
            pkill -x mysqld 2>/dev/null || pkill -x mariadbd 2>/dev/null || true
            sleep 2
        fi
        if db_running; then
            warn "MariaDB van dang chay."
        else
            info "MariaDB da dung."
        fi
    else
        warn "MariaDB khong dang chay."
    fi
}

start_server() {
    check_requirements || return
    if server_running; then
        warn "Server da dang chay."
        return
    fi
    if ! db_running; then
        warn "MariaDB chua chay - khoi dong truoc..."
        start_db
        # Cho them neu DB van chua san sang
        local waited=0
        while ! db_running && [ $waited -lt 10 ]; do
            sleep 1
            waited=$((waited + 1))
        done
        if ! db_running; then
            warn "MariaDB van chua chay. Server co the bi loi ket noi DB."
        fi
    fi
    mkdir -p "$NINJA_DIR/logs"
    info "Khoi dong server (port $SERVER_PORT)..."
    cd "$NINJA_DIR"
    nohup java \
        -Xms256M -Xmx512M \
        -XX:+UseG1GC \
        -Dfile.encoding=UTF-8 \
        -Dstdout.encoding=UTF-8 \
        -Djava.awt.headless=true \
        -jar Nso-jar-with-dependencies.jar \
        >> "$NINJA_DIR/logs/server.log" 2>&1 &
    echo $! > "$PID_DIR/server.pid"
    sleep 3
    if server_running; then
        info "Server da chay (PID: $(cat "$PID_DIR/server.pid"))."
    else
        error "Server khong khoi dong duoc. Xem log: $NINJA_DIR/logs/server.log"
    fi
}

stop_server() {
    if server_running; then
        local pid
        pid=$(cat "$PID_DIR/server.pid")
        info "Dung server (PID $pid)..."
        kill "$pid" 2>/dev/null
        sleep 2
        if server_running; then
            warn "Server chua tat - thu force kill..."
            kill -9 "$pid" 2>/dev/null
            sleep 1
        fi
        # Xoa pid file trong moi truong hop
        rm -f "$PID_DIR/server.pid"
        info "Server da dung."
    else
        warn "Server khong dang chay."
    fi
}

status_server() {
    echo ""
    echo -e "${CYAN}----- Trang thai server -----${NC}"
    if server_running; then
        echo -e " Server   : ${GREEN}Dang chay${NC} (PID $(cat "$PID_DIR/server.pid"))"
    else
        echo -e " Server   : ${RED}Dang tat${NC}"
    fi
    if web_running; then
        echo -e " Web      : ${GREEN}Dang chay${NC} (http://localhost:$WEB_PORT)"
    else
        echo -e " Web      : ${RED}Dang tat${NC}"
    fi
    if db_running; then
        echo -e " MariaDB  : ${GREEN}Dang chay${NC}"
    else
        echo -e " MariaDB  : ${RED}Dang tat${NC}"
    fi
    echo -e "${CYAN}-----------------------------${NC}"
    echo ""
}

start_web() {
    if web_running; then
        warn "Web da dang chay."
        return
    fi
    if [ ! -d "$NINJA_DIR/web" ]; then
        error "Thieu thu muc web/ trong $NINJA_DIR"
        return
    fi
    command -v php >/dev/null 2>&1 || { error "PHP chua duoc cai dat!"; return; }
    mkdir -p "$NINJA_DIR/logs"
    info "Khoi dong web (port $WEB_PORT)..."
    cd "$NINJA_DIR/web"
    pkill -x php 2>/dev/null || true
    sleep 1
    if [ -f "router.php" ]; then
        nohup php -S 127.0.0.1:$WEB_PORT router.php \
            >> "$NINJA_DIR/logs/web.log" 2>&1 &
    else
        nohup php -S 127.0.0.1:$WEB_PORT \
            >> "$NINJA_DIR/logs/web.log" 2>&1 &
    fi
    echo $! > "$PID_DIR/web.pid"
    sleep 2
    if web_running; then
        info "Web da chay: http://localhost:$WEB_PORT"
    else
        error "Web khong khoi dong duoc."
    fi
}

stop_web() {
    if web_running; then
        local pid
        pid=$(cat "$PID_DIR/web.pid")
        info "Dung web (PID $pid)..."
        kill "$pid" 2>/dev/null
        rm -f "$PID_DIR/web.pid"
        info "Web da dung."
    else
        warn "Web khong dang chay."
    fi
}

view_log() {
    local log="$NINJA_DIR/logs/server.log"
    if [ ! -f "$log" ]; then
        warn "Chua co log server ($log)."
        return
    fi
    echo -e "${CYAN}----- Log server (Ctrl+C de quay lai menu) -----${NC}"
    (
        trap 'exit 0' INT
        tail -f "$log"
    )
    echo ""
}

backup_db() {
    if ! db_running; then
        warn "MariaDB chua chay - khong backup duoc."
        return
    fi
    local stamp
    stamp=$(date +%Y%m%d_%H%M%S)
    local out="$NINJA_DIR/logs/backup_${DB_NAME}_$stamp.sql"
    mkdir -p "$NINJA_DIR/logs"
    info "Backup $DB_NAME -> $out"
    if mysqldump --socket="$PREFIX/tmp/mysqld.sock" -u root "$DB_NAME" > "$out" 2>/dev/null; then
        info "Backup hoan tat: $out"
    else
        error "Backup that bai."
        rm -f "$out"
    fi
}

open_web() {
    if ! web_running; then
        start_web
    fi
    if command -v termux-open-url >/dev/null 2>&1; then
        termux-open-url "http://localhost:$WEB_PORT"
    else
        info "Mo trinh duyet: http://localhost:$WEB_PORT"
    fi
}

show_menu() {
    clear
    echo -e "${CYAN}"
    echo "=============================================="
    echo "        NinjaServerTermux - MENU v2.0"
    echo "    Ninja School Online Server on Termux"
    echo "=============================================="
    echo -e "${NC}"

    if server_running; then
        echo -e "  Server  : ${GREEN}● Dang chay${NC}"
    else
        echo -e "  Server  : ${RED}○ Dang tat${NC}"
    fi
    if db_running; then
        echo -e "  MariaDB : ${GREEN}● Dang chay${NC}"
    else
        echo -e "  MariaDB : ${RED}○ Dang tat${NC}"
    fi
    if web_running; then
        echo -e "  Web PHP : ${GREEN}● Dang chay${NC} (Port: $WEB_PORT)"
    else
        echo -e "  Web PHP : ${RED}○ Dang tat${NC}"
    fi
    echo ""
    echo -e "${YELLOW}  1.${NC} Bat dau server         ${YELLOW}2.${NC} Dung server"
    echo -e "${YELLOW}  3.${NC} Trang thai server      ${YELLOW}4.${NC} Mo web (PHP)"
    echo -e "${YELLOW}  5.${NC} Dung web               ${YELLOW}6.${NC} Khoi dong MariaDB"
    echo -e "${YELLOW}  7.${NC} Dung MariaDB           ${YELLOW}8.${NC} Xem log server"
    echo -e "${YELLOW}  9.${NC} Backup database        ${YELLOW}10.${NC} Mo web tren trinh duyet"
    echo -e "${YELLOW}  0.${NC} Thoat menu"
    echo ""
    echo -e "${CYAN}==============================================${NC}"
}

while true; do
    show_menu
    echo -n " Lua chon: "
    read -r choice
    case "$choice" in
        1) start_server ;;
        2) stop_server ;;
        3) status_server ;;
        4) start_web ;;
        5) stop_web ;;
        6) start_db ;;
        7) stop_db ;;
        8) view_log ;;
        9) backup_db ;;
        10) open_web ;;
        0)
            echo -e "${GREEN} Tam biet!${NC}"
            exit 0
            ;;
        *)
            warn "Lua chon khong hop le!"
            sleep 1
            ;;
    esac
    echo ""
    echo -n " Nhan Enter de quay lai menu..."
    read -r _enter
done