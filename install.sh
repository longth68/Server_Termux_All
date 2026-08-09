#!/bin/bash
# ============================================================
#  install.sh - CÀI MÔI TRƯỜNG DÙNG CHUNG CHO 2 SERVER
#  Server_Termux_All = NinjaSchool + Hai Tac Hot
#  Cài: Java 21 + MariaDB + PHP (dùng chung cho cả 2 game)
# ============================================================

PREFIX="${PREFIX:-/data/data/com.termux/files/usr}"
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; CYAN='\033[0;36m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

echo -e "${CYAN}==============================================${NC}"
echo -e "${YELLOW}   Server_Termux_All - INSTALL ENV (chung)${NC}"
echo -e "${CYAN}==============================================${NC}"

# 1. Cập nhật + cài gói
info "Cập nhật package list..."
pkg update -y 2>/dev/null || apt update -y

info "Cài đặt Java, MariaDB, PHP, git, curl..."
pkg install -y openjdk-17 mariadb php git curl wget unzip tar coreutils \
    2>/dev/null || apt install -y openjdk-21 mariadb-server php php-mysql git curl wget unzip tar coreutils \
    2>/dev/null || error "Không thể cài gói. Hãy chạy: pkg install openjdk-17 mariadb php git curl"

# 2. Kiểm tra Java
if command -v java >/dev/null 2>&1; then
    _jver=$(java -version 2>&1 | awk -F '"' '/version/ {print $2}')
    info "Java $_jver đã cài."
else
    error "Java chưa cài được. Chạy thủ công: pkg install openjdk-17"
fi

# 3. Khởi tạo thư mục dữ liệu MariaDB
info "Khởi tạo thư mục dữ liệu MariaDB..."
mkdir -p "$PREFIX/var/lib/mysql" "$PREFIX/var/run/mysqld" "$PREFIX/var/log/mysql" "$PREFIX/tmp"
if [ ! -d "$PREFIX/var/lib/mysql/mysql" ]; then
    if command -v mariadb-install-db >/dev/null 2>&1; then
        mariadb-install-db --datadir="$PREFIX/var/lib/mysql" --auth-root-authentication-method=normal 2>/dev/null \
            && info "Khởi tạo DB xong." || warn "Khởi tạo DB gặp lỗi (có thể đã có)."
    fi
fi

# 4. Tạo shortcut 'menu' chạy ở mọi nơi
info "Tạo lệnh gõ nhanh 'menu'..."
echo "alias menu='bash $DIR/menu.sh'" >> "$HOME/.bashrc" 2>/dev/null
echo -e "${GREEN}[OK]${NC} Gõ 'menu' ở mọi nơi để mở menu quản lý (hoặc: bash $DIR/menu.sh)"

echo ""
echo -e "${GREEN}=============================================="
echo "   CÀI ĐẶT HOÀN TẤT!"
echo "==============================================${NC}"
echo -e "  🎮 2 Game: NinjaSchool (ninja/) + Hai Tac Hot (htth/)"
echo -e "  🌐 Chạy menu: bash $DIR/menu.sh"
echo ""
