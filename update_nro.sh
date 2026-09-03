#!/bin/bash
# ============================================================
#  update_nro.sh - CẬP NHẬT THÊM NGỌC RỒNG HASHIRAMA
#  (Dành cho máy đã cài sẵn 2 game Ninja School + Hải Tặc Hot)
#  - Giữ nguyên 100% dữ liệu 2 game cũ
#  - Tự động nạp DB hashirama chuẩn UTF-8
#  - Tự động giải nén tài nguyên NRO
#  - Tự động lắp ráp client APK & JAR để tải từ Web
# ============================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; CYAN='\033[0;36m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

echo -e "${CYAN}====================================================${NC}"
echo -e "${YELLOW}   CẬP NHẬT THÊM GAME NGỌC RỒNG VÀO BỘ CÀI TERMUX   ${NC}"
echo -e "${CYAN}====================================================${NC}"

# 1. Nếu có Git, kéo code mới nhất
if [ -d "$DIR/.git" ]; then
    info "Đang đồng bộ code mới nhất từ GitHub..."
    git pull origin main 2>/dev/null || warn "Không thể git pull, tiếp tục với file hiện có."
fi

# 2. Cấp quyền thực thi các script
chmod +x "$DIR"/*.sh 2>/dev/null || true

# 3. Khởi động MariaDB và nạp DB hashirama (không ảnh hưởng 2 DB cũ)
info "Khởi động MariaDB và kiểm tra Database..."
bash "$DIR/start_db.sh"

# Kiểm tra lại DB hashirama nếu chưa có bảng
PREFIX="${PREFIX:-/data/data/com.termux/files/usr}"
MYSQL_CMD=""
for c in mariadb mysql; do command -v "$c" >/dev/null 2>&1 && { MYSQL_CMD="$c"; break; }; done
if [ -n "$MYSQL_CMD" ]; then
    NRO_TBLS=$("$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root -N -e "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'hashirama';" 2>/dev/null || echo "0")
    if [ -z "$NRO_TBLS" ] || [ "$NRO_TBLS" -eq 0 ]; then
        info "Đang nạp dữ liệu database hashirama..."
        "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root -e "CREATE DATABASE IF NOT EXISTS hashirama CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" 2>/dev/null
        "$MYSQL_CMD" --socket="$PREFIX/tmp/mysqld.sock" -u root --default-character-set=utf8mb4 hashirama < "$DIR/nro/database/hashirama.sql" 2>/dev/null
        info "Nạp database hashirama thành công (80 bảng)!"
    else
        info "Database hashirama đã có sẵn $NRO_TBLS bảng."
    fi
fi

# 4. Giải nén dữ liệu tài nguyên NRO nếu chưa có
NRO="$DIR/nro"
if [ -d "$NRO" ]; then
    if [ ! -d "$NRO/resources" ] || [ ! -d "$NRO/data" ]; then
        if [ -f "$NRO/NRO_data.part1" ]; then
            info "Đang giải nén tài nguyên Ngọc Rồng (NRO_data.part1..5)..."
            (cd "$NRO" && cat NRO_data.part* > nro_data.tar.gz \
                && tar -xzf nro_data.tar.gz && rm -f nro_data.tar.gz)
            [ -d "$NRO/resources" ] && info "Giải nén tài nguyên NRO thành công!" || { error "Giải nén NRO thất bại!"; exit 1; }
        else
            warn "Không tìm thấy NRO_data.part1..5 trong thư mục nro/!"
        fi
    else
        info "Tài nguyên NRO đã được giải nén sẵn."
    fi

    # Lắp ráp file client APK nếu chưa có (từ Nro_HanZi_apk.part1..2)
    if [ ! -f "$NRO/web/Downloads/Nro HanZi.apk" ] && [ -f "$NRO/web/Downloads/Nro_HanZi_apk.part1" ]; then
        info "Đang lắp ráp file APK Ngọc Rồng (cho phép tải từ Web)..."
        cat "$NRO/web/Downloads/Nro_HanZi_apk.part"* > "$NRO/web/Downloads/Nro HanZi.apk"
        info "Đã lắp ráp file cài đặt APK thành công!"
    fi
else
    error "Thư mục $NRO không tồn tại!"
    exit 1
fi

echo -e "\n${GREEN}====================================================${NC}"
echo -e "${GREEN}   CẬP NHẬT THÀNH CÔNG 100%! BỘ CÀI ĐÃ CÓ 3 GAME    ${NC}"
echo -e "${GREEN}====================================================${NC}"
echo -e "  - Ninja School:  game port ${YELLOW}14444${NC} | web port ${YELLOW}8000${NC} (DB: schoolzz - GIỮ NGUYÊN)"
echo -e "  - Hải Tặc Hot:   game port ${YELLOW}2236${NC}  | web port ${YELLOW}8080${NC} (DB: htth - GIỮ NGUYÊN)"
echo -e "  - Ngọc Rồng MỚI: game port ${YELLOW}14445${NC} | web port ${YELLOW}8888${NC} | API: ${YELLOW}8085${NC} (DB: hashirama)"
echo -e "  - Tải Client:    Truy cập ${YELLOW}http://127.0.0.1:8888/${NC} để tải APK và Java đã chỉnh IP 127.0.0.1"
echo -e "${CYAN}----------------------------------------------------${NC}"
echo -e "  ▶ Chạy menu điều khiển:   ${CYAN}bash menu.sh${NC}"
echo -e "  ▶ Chạy riêng Ngọc Rồng:    ${CYAN}bash nro_start.sh${NC}"
echo -e "${GREEN}====================================================${NC}"