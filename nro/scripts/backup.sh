#!/data/data/com.termux/files/usr/bin/bash
# ==========================================================
# Script sao lưu database máy chủ Ngọc Rồng trên Termux Android
# ==========================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
mkdir -p "$DIR/backup"

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$DIR/backup/hashirama_$TIMESTAMP.sql"

echo -e "\e[1;36mĐang sao lưu cơ sở dữ liệu...\e[0m"
if mariadb-dump -u root --default-character-set=utf8mb4 --hex-blob --routines --triggers hashirama > "$BACKUP_FILE"; then
    echo -e "\e[1;32mSao lưu thành công: $BACKUP_FILE\e[0m"
    echo -e "Dung lượng: $(du -h "$BACKUP_FILE" | cut -f1)"
else
    echo -e "\e[1;31mLỗi sao lưu cơ sở dữ liệu! Hãy đảm bảo MariaDB đang chạy.\e[0m"
fi