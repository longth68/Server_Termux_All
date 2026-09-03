#!/data/data/com.termux/files/usr/bin/bash
# ==========================================================
# Script tự động cài đặt môi trường Ngọc Rồng trên Termux Android
# ==========================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR"

echo -e "\e[1;32m===================================================\e[0m"
echo -e "\e[1;33m   BẮT ĐẦU CÀI ĐẶT MÔI TRƯỜNG NRO TRÊN TERMUX      \e[0m"
echo -e "\e[1;32m===================================================\e[0m"

echo -e "\n\e[1;36m[1/6] Cập nhật gói phần mềm Termux...\e[0m"
pkg update -y && pkg upgrade -y

echo -e "\n\e[1;36m[2/6] Cài đặt OpenJDK 17, MariaDB, PHP và công cụ hỗ trợ...\e[0m"
pkg install -y openjdk-17 mariadb php curl wget nano procps net-tools

echo -e "\n\e[1;36m[3/6] Cấu hình và khởi tạo cơ sở dữ liệu MariaDB...\e[0m"
if [ ! -d "$PREFIX/var/lib/mysql" ] || [ -z "$(ls -A "$PREFIX/var/lib/mysql" 2>/dev/null)" ]; then
    echo "Khởi tạo thư mục dữ liệu MariaDB..."
    mysql_install_db
fi

# Dừng MariaDB nếu đang chạy
mariadb-admin -u root shutdown 2>/dev/null || true
sleep 1

# Khởi động MariaDB tạm thời để nạp DB
echo "Khởi động MariaDB..."
mysqld_safe --skip-syslog &
sleep 3

# Kiểm tra kết nối
max_wait=15
while ! mariadb -u root -e "status" >/dev/null 2>&1; do
    echo "Đang đợi MariaDB khởi động..."
    sleep 1
    max_wait=$((max_wait - 1))
    if [ $max_wait -le 0 ]; then
        echo -e "\e[1;31mLỗi: Không thể khởi động MariaDB!\e[0m"
        exit 1
    fi
done

echo -e "\n\e[1;36m[4/6] Tạo database 'hashirama' và import dữ liệu...\e[0m"
mariadb -u root -e "CREATE DATABASE IF NOT EXISTS hashirama CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
if [ -f "$DIR/database/hashirama.sql" ]; then
    echo "Đang nạp database/hashirama.sql vào MariaDB (vui lòng chờ)..."
    mariadb -u root hashirama < "$DIR/database/hashirama.sql"
    echo -e "\e[1;32mNạp database thành công!\e[0m"
else
    echo -e "\e[1;31mCảnh báo: Không tìm thấy file $DIR/database/hashirama.sql!\e[0m"
fi

# Tắt MariaDB sau khi nạp xong
mariadb-admin -u root shutdown 2>/dev/null || true

echo -e "\n\e[1;36m[5/6] Tạo thư mục log và cấp quyền thực thi các script...\e[0m"
mkdir -p "$DIR/Server/log"
mkdir -p "$DIR/backup"
chmod +x "$DIR"/*.sh "$DIR"/scripts/*.sh 2>/dev/null || true

echo -e "\n\e[1;36m[6/6] Kiểm tra các thành phần...\e[0m"
java -version 2>&1 | head -n 2
php -v 2>&1 | head -n 1
mariadb --version 2>&1 | head -n 1

echo -e "\n\e[1;32m===================================================\e[0m"
echo -e "\e[1;32m   CÀI ĐẶT HOÀN TẤT THÀNH CÔNG 100%!               \e[0m"
echo -e "\e[1;32m===================================================\e[0m"
echo -e "\e[1;33mCác lệnh điều khiển nhanh:\e[0m"
echo -e "  - Mở menu điều khiển: \e[1;36mbash menu.sh\e[0m"
echo -e "  - Bật server:         \e[1;36mbash start.sh\e[0m"
echo -e "  - Tắt server:         \e[1;36mbash stop.sh\e[0m"
echo -e "  - Xem trạng thái:     \e[1;36mbash status.sh\e[0m"
echo -e "\e[1;32m===================================================\e[0m"