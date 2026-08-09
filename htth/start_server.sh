#!/bin/bash
cd "$(dirname "$0")"

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Tự giải nén dữ liệu game nếu chưa có
# data/ được nén + chia thành 3 phần (HTTH_data.part1/2/3) để đưa lên GitHub (<100MB/phần)
if [ ! -d "data/map" ]; then
    if [ -f "HTTH_data.part1" ]; then
        info "Nối và giải nén dữ liệu game (HTTH_data.part1+2+3)..."
        cat HTTH_data.part1 HTTH_data.part2 HTTH_data.part3 > data.tar.gz
        tar -xzf data.tar.gz
        rm -f data.tar.gz
        if [ ! -d "data/map" ]; then
            error "Giải nén thất bại."
            exit 1
        fi
        info "Giải nén xong."
    else
        error "Thiếu dữ liệu game! Cần file HTTH_data.part1/2/3 (hoặc thư mục data/)."
        exit 1
    fi
fi

if ! command -v java >/dev/null 2>&1; then
    error "Chưa tìm thấy Java."
    echo "Hãy cài Java bằng lệnh: pkg install openjdk-21"
    exit 1
fi

echo "Khởi động Hải Tặc Tí Hon tại 127.0.0.1:2236..."
java -Xms512M -Xmx1024M -XX:+UseG1GC -Dfile.encoding=UTF-8 -Djava.awt.headless=true -jar server.jar 2>&1 | tee -a game_log.txt
