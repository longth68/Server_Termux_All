# Hướng Dẫn Chạy HTTH Localhost trên Termux (Android)

## 1. Chuẩn bị
Di chuyển thư mục `HTTH_Localhost_127.0.0.1` vào bộ nhớ trong của điện thoại để Termux có thể truy cập được.

Mở ứng dụng Termux, di chuyển vào thư mục nguồn của game (ví dụ bạn để ở thư mục `Download/HTTH_Localhost_127.0.0.1`):
```bash
cd /sdcard/Download/HTTH_Localhost_127.0.0.1
```

## 2. Sử dụng Menu Tự Động (Khuyên dùng)
Để giống với cấu trúc của các Server xịn (như Ninja_Server_Termux), tôi đã tích hợp sẵn một file `menu.sh` giúp bạn quản lý tất cả các tác vụ chỉ bằng các phím số.

Cấp quyền thực thi và mở Menu:
```bash
chmod +x *.sh
./menu.sh
```

**Các chức năng trong Menu:**
- **[1] Cài đặt môi trường:** Tự động cài đặt Java 17, MariaDB và PHP. (Chỉ cần chạy 1 lần duy nhất).
- **[2] Khởi tạo & Import Database:** Tự động thiết lập cấu hình MariaDB và đẩy dữ liệu `database/htth_full.sql` vào MySQL.
- **[3] Chạy Máy Chủ (Game + Web Server):** Tự động bật MariaDB, bật Game Server ở port `2236` và bật Web quản lý ở port `8080`. Chạy ngầm hoàn toàn nên bạn không cần lo bị treo Terminal.
- **[4] Dừng tất cả:** Tắt hoàn toàn Game, Web và Database đang chạy ngầm.
- **[5] Tạo lệnh gõ nhanh 'menu' ở mọi nơi:** Tạo shortcut vào hệ thống. Lần sau mở Termux lên bạn chỉ cần gõ chữ `menu` rồi Enter là bảng Menu này sẽ hiện ra, không cần phải dùng lệnh `cd` phiền phức nữa.
- **[0] Thoát:** Đóng Menu.
