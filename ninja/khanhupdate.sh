#!/bin/bash
# ============================================================
#  NinjaServerTermux - Update Script
#  Cập nhật các script điều khiển từ repository lên server
# ============================================================

NINJA_DIR="$HOME/ninja-server"
SRC_URL="https://raw.githubusercontent.com/longth68/NinjaServerTermux/main"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }

# Kiểm tra kết nối
if ! curl -s --max-time 10 "$SRC_URL/menu.sh" -o /dev/null; then
    warn "Không kết nối được tới repository. Kiểm tra mạng."
    exit 1
fi

info "Cập nhật menu.sh ..."
curl -L --max-redirs 15 -s "$SRC_URL/menu.sh" --output "$NINJA_DIR/menu.sh" && chmod +x "$NINJA_DIR/menu.sh"

info "Cập nhật install.sh ..."
curl -L --max-redirs 15 -s "$SRC_URL/install.sh" --output "$NINJA_DIR/install.sh" && chmod +x "$NINJA_DIR/install.sh"

info "Cập nhật xong! Chạy lại menu: bash $NINJA_DIR/menu.sh"
