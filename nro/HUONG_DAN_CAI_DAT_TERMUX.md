# HƯỚNG DẪN CÀI ĐẶT VÀ VẬN HÀNH NGỌC RỒNG TRÊN TERMUX ANDROID

Bộ cài đặt này giúp bạn chạy toàn bộ Game Server Ngọc Rồng Hashirama, Cơ sở dữ liệu MariaDB và Web Quản trị trực tiếp trên điện thoại Android mà **KHÔNG CẦN ROOT MÁY**.

---

## I. YÊU CẦU THIẾT BỊ
- Điện thoại Android phiên bản 7.0 trở lên.
- Dung lượng RAM khuyến nghị: từ 3GB trở lên.
- Bộ nhớ trong còn trống: từ 2GB trở lên.
- Ứng dụng **Termux** (Tải từ **F-Droid**, tuyệt đối KHÔNG tải từ Google Play Store vì bản CH Play đã cũ và lỗi kho gói).

---

## II. CÁC BƯỚC CÀI ĐẶT CHI TIẾT

### Bước 1: Cài đặt và cấp quyền cho Termux
1. Tải và cài đặt file APK **Termux** từ F-Droid.
2. Mở ứng dụng Termux lên, gõ lệnh sau để cấp quyền truy cập bộ nhớ:
   ```bash
   termux-setup-storage
   ```
   *(Nhấn "Cho phép" / "Allow" khi điện thoại hiện thông báo xin quyền).*

### Bước 2: Chuyển thư mục NRO-TERMUX vào điện thoại
1. Nối điện thoại với máy tính qua cáp USB (hoặc nén thư mục `NRO-TERMUX` thành file `.zip` rồi gửi qua Zalo/Drive/Telegram sang điện thoại).
2. Đặt thư mục `NRO-TERMUX` vào bộ nhớ trong của điện thoại (ví dụ: `/sdcard/NRO-TERMUX` hoặc trong thư mục `Download`).
3. Mở Termux và sao chép vào bộ nhớ trong của Termux để đạt hiệu năng cao nhất:
   ```bash
   cp -r /sdcard/NRO-TERMUX ~/
   cd ~/NRO-TERMUX
   ```
   *(Nếu bạn để trong thư mục Download, gõ: `cp -r /sdcard/Download/NRO-TERMUX ~/ && cd ~/NRO-TERMUX`)*.

### Bước 3: Chạy script cài đặt tự động (1 chạm)
Tại thư mục `~/NRO-TERMUX`, gõ lệnh:
```bash
bash install.sh
```
Hệ thống sẽ tự động:
- Cập nhật kho ứng dụng Termux.
- Cài đặt OpenJDK 17, MariaDB, PHP và các tiện ích mạng.
- Khởi tạo MariaDB, tạo database `hashirama` và nạp toàn bộ dữ liệu game ban đầu.
- Cấp quyền thực thi cho toàn bộ các script điều khiển.

---

## III. CÁCH KHỞI ĐỘNG VÀ QUẢN LÝ MÁY CHỦ

### 1. Bảng điều khiển tương tác (Khuyến nghị dùng)
Gõ lệnh:
```bash
bash menu.sh
```
Màn hình menu trực quan sẽ xuất hiện với các tùy chọn phím số:
- **[1]**: Khởi động toàn bộ (Database + Game Server + Web Admin).
- **[2]**: Dừng toàn bộ máy chủ an toàn.
- **[3]**: Khởi động lại máy chủ.
- **[4]**: Xem trạng thái và địa chỉ IP để kết nối game.
- **[5]**: Xem nhật ký Game Server thời gian thực (Live Console Log).
- **[6]**: Xem nhật ký Web Server.
- **[7]**: Sao lưu cơ sở dữ liệu (Backup DB).
- **[8]**: Khôi phục lại dữ liệu gốc ban đầu.
- **[9]**: Mở Web Admin trên trình duyệt Chrome điện thoại.
- **[0]**: Thoát menu.

### 2. Các lệnh chạy nhanh bằng dòng lệnh:
- **Bật Server**:
  ```bash
  bash start.sh
  ```
- **Tắt Server**:
  ```bash
  bash stop.sh
  ```
- **Khởi động lại Server**:
  ```bash
  bash restart.sh
  ```
- **Kiểm tra trạng thái & IP**:
  ```bash
  bash status.sh
  ```
- **Xem Log máy chủ trực tiếp**:
  ```bash
  tail -f Server/log/server.log
  ```

---

## IV. CÁCH KẾT NỐI VÀO CHƠI GAME

1. **Nếu chơi game trên cùng chiếc điện thoại đang chạy Termux**:
   - Sử dụng phiên bản Mod/Client NRO Android.
   - Cài đặt địa chỉ IP máy chủ là: `127.0.0.1`, cổng `14445`.
2. **Nếu chơi từ máy khác (điện thoại khác hoặc máy tính kết nối chung mạng WiFi)**:
   - Gõ lệnh `bash status.sh` trong Termux để xem địa chỉ IP WiFi (ví dụ: `192.168.1.15`).
   - Cài đặt địa chỉ IP trong client game trỏ về IP đó với cổng `14445`.

---

## V. TRUY CẬP BẢNG QUẢN TRỊ (WEB ADMIN)
- Mở trình duyệt Chrome trên điện thoại, truy cập:
  ```
  http://127.0.0.1:8888/admin.php
  ```
- Tài khoản quản trị mặc định:
  - **Tài khoản**: `admin1`
  - **Mật khẩu**: `admin123123123`

---

## VI. CẤU TRÚC THƯ MỤC NRO-TERMUX
```
NRO-TERMUX/
├── Server/                   # Thư mục mã nguồn máy chủ Java
│   ├── server.jar            # File JAR chạy game (OpenJDK 17)
│   ├── config/
│   │   └── server.properties # Cấu hình cổng, DB và rate server
│   ├── data/                 # Dữ liệu tài nguyên quái, kỹ năng, map
│   ├── log/                  # Chứa file log khi server chạy
│   └── virtualplayer_config.txt # Cấu hình BOT ảo
├── database/
│   └── hashirama.sql         # Cơ sở dữ liệu gốc gồm 80 bảng chuẩn
├── web/                      # Bộ Web Admin PHP chạy trên Termux
│   └── htdocs/               # Mã nguồn giao diện quản trị web
├── scripts/                  # Toàn bộ script shell điều khiển
├── install.sh                # Lệnh cài đặt nhanh
├── start.sh                  # Lệnh khởi động nhanh
├── stop.sh                   # Lệnh dừng nhanh
├── restart.sh                # Lệnh khởi động lại
├── status.sh                 # Lệnh kiểm tra trạng thái
├── menu.sh                   # Bảng điều khiển giao diện số
└── HUONG_DAN_CAI_DAT_TERMUX.md
```
