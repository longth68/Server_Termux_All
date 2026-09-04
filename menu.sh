#!/bin/bash
# ============================================================
#  menu.sh - MENU QUẢN LÝ BỘ CÀI 3 SERVER TRONG 1
#  Server_Termux_All: NinjaSchool + Hai Tac Hot + Ngoc Rong Hashirama
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
    # Kiểm tra MariaDB
    status_line "MariaDB Database" "${PREFIX:-/data/data/com.termux/files/usr}/var/run/mysqld/mysqld.pid"
    status_line "Ninja game (14444)" "$DIR/.pids/ninja_server.pid"
    status_line "Ninja web   (8000)"  "$DIR/.pids/ninja_web.pid"
    status_line "HTTH game   (2236)"  "$DIR/.pids/htth_server.pid"
    status_line "HTTH web    (8080)"  "$DIR/.pids/htth_web.pid"
    status_line "NRO game   (14445)"  "$DIR/.pids/nro_server.pid"
    status_line "NRO web     (8888)"  "$DIR/.pids/nro_web.pid"
}

show_menu() {
    clear
    echo -e "${CYAN}================================================${NC}"
    echo -e "${YELLOW}   SERVER_TERMUX_ALL - 3 GAME TRONG 1 BỘ CÀI${NC}"
    echo -e "${CYAN}================================================${NC}"
    show_status
    echo -e "${CYAN}------------------------------------------------${NC}"
    echo -e "${GREEN} 1.${NC} Cài đặt môi trường chung (Java/MariaDB/PHP)"
    echo -e "${GREEN} 2.${NC} Khởi động MariaDB dùng chung (+ tạo DB)"
    echo -e "${GREEN} 3.${NC} Chạy ${YELLOW}Ninja School${NC} (game 14444 + web 8000)"
    echo -e "${GREEN} 4.${NC} Chạy ${YELLOW}Hải Tặc Hot${NC} (game 2236 + web 8080)"
    echo -e "${GREEN} 5.${NC} Chạy ${YELLOW}Ngọc Rồng Anwin V3${NC} (game 14445 + web 8888 + api 8085)"
    echo -e "${GREEN} 6.${NC} Chạy ${YELLOW}TẤT CẢ 3 GAME${NC} cùng lúc"
    echo -e "${GREEN} 7.${NC} Dừng ${YELLOW}Ninja School${NC}"
    echo -e "${GREEN} 8.${NC} Dừng ${YELLOW}Hải Tặc Hot${NC}"
    echo -e "${GREEN} 9.${NC} Dừng ${YELLOW}Ngọc Rồng${NC}"
    echo -e "${GREEN} 10.${NC} Dừng ${YELLOW}TẤT CẢ${NC} game (không tắt MariaDB)"
    echo -e "${GREEN} 11.${NC} Dừng cả MariaDB"
    echo -e "${GREEN} 12.${NC} Xem log server"
    echo -e "${GREEN} 13.${NC} Cập nhật ${YELLOW}Ngọc Rồng Anwin V3${NC} (bản mới + DB + tài nguyên)"
    echo -e "${GREEN} 0.${NC} Thoát"
    echo -e "${CYAN}================================================${NC}"
    echo -n "Chọn chức năng [0-13]: "
    read -r choice
    case $choice in
        1) bash "$DIR/install.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        2) bash "$DIR/start_db.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        3) bash "$DIR/ninja_start.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        4) bash "$DIR/htth_start.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        5) bash "$DIR/nro_start.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        6)
            bash "$DIR/ninja_start.sh"
            bash "$DIR/htth_start.sh"
            bash "$DIR/nro_start.sh"
            read -p "Nhấn Enter để tiếp tục..."; show_menu
            ;;
        7) bash "$DIR/ninja_stop.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        8) bash "$DIR/htth_stop.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        9) bash "$DIR/nro_stop.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        10) bash "$DIR/stop.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        11) bash "$DIR/stop_db.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        12)
            echo -e "${CYAN}--- Log gần đây ---${NC}"
            ls -1 "$DIR/logs/"*.log 2>/dev/null || echo "Chưa có log."
            echo -n "Nhập tên log (vd nro_server, ninja_server, htth_server) hoặc Enter để thoát: "
            read -r lg
            if [ -n "$lg" ]; then
                lg_file="$DIR/logs/$lg"
                [[ "$lg_file" != *.log ]] && lg_file="${lg_file}.log"
                if [ -f "$lg_file" ]; then
                    tail -n 50 "$lg_file"
                else
                    echo "Không tìm thấy file: $lg_file"
                fi
            fi
            read -p "Nhấn Enter để tiếp tục..."; show_menu
            ;;
        13) bash "$DIR/update_nro.sh"; read -p "Nhấn Enter để tiếp tục..."; show_menu ;;
        0) exit 0 ;;
        *) echo -e "${RED}Lựa chọn không hợp lệ!${NC}"; sleep 2; show_menu ;;
    esac
}

show_menu