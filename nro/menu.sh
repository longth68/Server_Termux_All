#!/data/data/com.termux/files/usr/bin/bash
# ==========================================================
# Menu điều khiển máy chủ Ngọc Rồng trên Termux Android
# ==========================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR"

while true; do
    clear
    echo -e "\e[1;32m===================================================\e[0m"
    echo -e "\e[1;33m       BẢNG ĐIỀU KHIỂN MÁY CHỦ NGỌC RỒNG HASHIRAMA \e[0m"
    echo -e "\e[1;36m                 PHIÊN BẢN TERMUX ANDROID          \e[0m"
    echo -e "\e[1;32m===================================================\e[0m"
    
    # Hiển thị nhanh trạng thái
    if pgrep -f "server.jar" >/dev/null 2>&1; then
        echo -e "  Trạng thái: \e[1;32m● SERVER ĐANG CHẠY (Port 14445)\e[0m"
    else
        echo -e "  Trạng thái: \e[1;31m○ SERVER ĐANG TẮT\e[0m"
    fi
    echo -e "\e[1;32m---------------------------------------------------\e[0m"
    echo -e "  \e[1;37m[1]\e[0m \e[1;32mKhởi động toàn bộ máy chủ (Start All)\e[0m"
    echo -e "  \e[1;37m[2]\e[0m \e[1;31mDừng toàn bộ máy chủ (Stop All)\e[0m"
    echo -e "  \e[1;37m[3]\e[0m \e[1;33mKhởi động lại máy chủ (Restart)\e[0m"
    echo -e "  \e[1;37m[4]\e[0m \e[1;36mXem trạng thái chi tiết & Địa chỉ IP kết nối\e[0m"
    echo -e "  \e[1;37m[5]\e[0m Xem nhật ký Game Server trực tiếp (Live Log)"
    echo -e "  \e[1;37m[6]\e[0m Xem nhật ký Web Server (Web Log)"
    echo -e "  \e[1;37m[7]\e[0m Sao lưu cơ sở dữ liệu (Backup DB)"
    echo -e "  \e[1;37m[8]\e[0m Nạp lại cơ sở dữ liệu gốc (Reset Database)"
    echo -e "  \e[1;37m[9]\e[0m Mở Web Admin trên trình duyệt Android"
    echo -e "  \e[1;37m[0]\e[0m Thoát menu"
    echo -e "\e[1;32m===================================================\e[0m"
    read -p "Vui lòng chọn [0-9]: " choice

    case $choice in
        1)
            bash "$DIR/scripts/start.sh"
            read -p "Nhấn Enter để tiếp tục..."
            ;;
        2)
            bash "$DIR/scripts/stop.sh"
            read -p "Nhấn Enter để tiếp tục..."
            ;;
        3)
            bash "$DIR/scripts/restart.sh"
            read -p "Nhấn Enter để tiếp tục..."
            ;;
        4)
            bash "$DIR/scripts/status.sh"
            read -p "Nhấn Enter để tiếp tục..."
            ;;
        5)
            echo -e "\e[1;33mNhấn Ctrl + C để thoát khỏi màn hình xem log.\e[0m"
            sleep 1
            if [ -f "$DIR/Server/log/server.log" ]; then
                tail -n 50 -f "$DIR/Server/log/server.log"
            else
                echo "Chưa có file log Server!"
                read -p "Nhấn Enter để tiếp tục..."
            fi
            ;;
        6)
            echo -e "\e[1;33mNhấn Ctrl + C để thoát khỏi màn hình xem log.\e[0m"
            sleep 1
            if [ -f "$DIR/Server/log/web.log" ]; then
                tail -n 50 -f "$DIR/Server/log/web.log"
            else
                echo "Chưa có file log Web!"
                read -p "Nhấn Enter để tiếp tục..."
            fi
            ;;
        7)
            bash "$DIR/scripts/backup.sh"
            read -p "Nhấn Enter để tiếp tục..."
            ;;
        8)
            read -p "CẢNH BÁO: Thao tác này sẽ xóa toàn bộ dữ liệu hiện tại và nạp lại DB gốc. Tiếp tục? (y/n): " confirm
            if [ "$confirm" == "y" ] || [ "$confirm" == "Y" ]; then
                echo "Đang nạp lại database..."
                mariadb -u root hashirama < "$DIR/database/hashirama.sql"
                echo -e "\e[1;32mĐã nạp lại DB gốc thành công!\e[0m"
            fi
            read -p "Nhấn Enter để tiếp tục..."
            ;;
        9)
            if command -v termux-open-url >/dev/null 2>&1; then
                termux-open-url "http://127.0.0.1:8888/admin.php"
            else
                echo "Hãy mở trình duyệt Chrome trên điện thoại và vào: http://127.0.0.1:8888/admin.php"
            fi
            read -p "Nhấn Enter để tiếp tục..."
            ;;
        0)
            echo "Tạm biệt!"
            exit 0
            ;;
        *)
            echo -e "\e[1;31mLựa chọn không hợp lệ!\e[0m"
            sleep 1
            ;;
    esac
done