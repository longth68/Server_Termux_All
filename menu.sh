#!/bin/bash
# ============================================================
#  menu.sh - MENU QUẢN LÝ BỘ CÀI 2 SERVER
#  Server_Termux_All: NinjaSchool + Hai Tac Hot
#  Chỉ khác cách chạy server, dùng chung MariaDB + môi trường
# ============================================================



DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }

is_running() { [ -f "$1" ] && kill -0 "$(cat "$1" 2>/dev/null)" 2>/dev/null; }

status_line() {
    local name="$1" pidf="$2"
    if is_running "$pidf"; then
        echo -e "  ${GREEN}●${NC} $name: ${GREEN}ĐANG CHẠY${NC} (PID $(cat "$pidf"))"
    else
        echo -e "  ${RED}○${NC} $name: ${RED}Đang tắt${NC}"
    fi
}

show_status() {
    echo -e "${CYAN}--- Trạng thái hiện tại ---${NC}"
    status_line "Ninja game (14444)" "$DIR/.pids/ninja_server.pid"
    status_line "Ninja web   (8000)"  "$DIR/.pids/ninja_web.pid"
    status_line "HTTH game   (2236)"  "$DIR/.pids/htth_server.pid"
    status_line "HTTH web    (8080)"  "$DIR/.pids/htth_web.pid"
}

show_menu() {
    clear
    echo -e "${CYAN}================================================${NC}"
    echo -e "${YELLOW}   SERVER_TERMUX_ALL - 2 GAME TRONG 1 BỘ CÀI${NC}"
    echo -e "${CYAN}================================================${NC}"
    show_status
    echo -e "${CYAN}------------------------------------------------${NC}"
    echo -e "${GREEN} 1.${NC} Cài đặt môi trường chung (Java/MariaDB/PHP)"
    echo -e "${GREEN} 2.${NC} Khởi động MariaDB dùng chung (+ tạo DB)"
    echo -e "${GREEN} 3.${NC} Chạy ${YELLOW}Ninja School${NC} (game 14444 + web 8000)"
    echo -e "${GREEN} 4.${NC} Chạy ${YELLOW}Hải Tặc Hot${NC} (game 2236 + web 8080)"
    echo -e "${GREEN} 5.${NC} Chạy ${YELLOW}CẢ HAI${NC} game cùng lúc"
    echo -e "${GREEN} 6.${NC} Dừng game/web (không tắt MariaDB)"
    echo -e "${GREEN} 7.${NC} Dừng cả MariaDB"
    echo -e "${GREEN} 8.${NC} Xem log server"
    echo -e "${GREEN} 0.${NC} Thoát"
    echo -e "${CYAN}================================================${NC}"
    echo -n "Chọn chức năng [0-8]: "
    read -r choice
    case $choice in
        1) bash "$DIR/install.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        2) bash "$DIR/start_db.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        3) bash "$DIR/ninja_start.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        4) bash "$DIR/htth_start.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        5)
            bash "$DIR/ninja_start.sh"
            bash "$DIR/htth_start.sh"
            read -p "Nhấn Enter để tiếp tục..."; show_menu
            ;;
        6) bash "$DIR/stop.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        7) bash "$DIR/stop_db.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        8)
            echo -e "${CYAN}--- Log gần đây ---${NC}"
            ls -1 "$DIR/logs/"*.log 2>/dev/null || echo "Chưa có log."
            echo -n "Nhập tên log (vd ninja_server) hoặc Enter để thoát: "
            read -r lg
            if [ -n "$lg" ] && [ -f "$DIR/logs/$lg.log" ]; then
                tail -n 50 "$DIR/logs/$lg.log"
            fi
            read -p "Nhấn Enter để tiếp tục..."; show_menu
            ;;
        0) exit 0 ;;
        *) echo -e "${RED}Lựa chọn không hợp lệ!${NC}"; sleep 2; show_menu ;;
    esac
}

show_menu
