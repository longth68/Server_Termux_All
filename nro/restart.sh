#!/data/data/com.termux/files/usr/bin/bash
# ==========================================================
# Script khởi động lại máy chủ Ngọc Rồng trên Termux Android
# ==========================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR"

echo -e "\e[1;33mKhởi động lại máy chủ...\e[0m"
bash "$DIR/scripts/stop.sh"
sleep 2
bash "$DIR/scripts/start.sh"