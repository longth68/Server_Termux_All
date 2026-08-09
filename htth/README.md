# HTTH_Server_Termux

Hải Tặc Tí Hon (HTTH) — Server + Web hoạt động trên **Termux (Android)** hoặc **Linux**, không cần root.

## ⚡ Tính năng

- 🎮 **Server game** HTTH chạy Java (port `2236`)
- 🌐 **Web** quản lý + đăng ký tài khoản chạy PHP built-in (port `8080`)
- 🗄️ **MariaDB** database `htth`
- ⚙️ Cấu hình qua file `htth.conf` (exp, port, db...)
- 🛠️ Web Admin tích hợp

## 📦 Yêu cầu hệ thống

- Termux (Android 7+), 32/64bit ARM, hoặc x86_64
- Hoặc bất kỳ Linux nào có: `openjdk-21`, `mariadb`, `php`
- RAM tối thiểu 1GB (khuyến nghị 2GB)

## 🚀 Cài đặt nhanh trên Termux

```bash
# 1. Mở Termux, cập nhật gói
pkg update -y && pkg upgrade -y

# 2. Clone repo (chứa ĐẦY ĐỦ dữ liệu game)
git clone https://github.com/longth68/HTTH_Server_Termux
cd HTTH_Server_Termux

# 3. Cấp quyền + mở menu (tự giải nén data + tạo DB)
chmod +x *.sh
./menu.sh
```

> 💡 **Dữ liệu game** được nén + chia thành 3 phần `HTTH_data.part1/2/3` (mỗi phần <100MB để đưa lên GitHub). Khi chạy server, script **tự động nối + giải nén** thành thư mục `data/` (lần đầu mất vài phút, chỉ 1 lần).

## 🎮 Menu quản lý (`menu`)

```
  1. Cài đặt môi trường (Java, MariaDB, PHP)
  2. Khởi tạo & Import Database
  3. Chạy Máy Chủ (Game + Web Server)
  4. Dừng tất cả các tiến trình
  5. Tạo lệnh gõ nhanh 'menu' ở mọi nơi
  0. Thoát
```

### Cách chạy thủ công

```bash
# Import database (lần đầu)
bash import_database.sh

# Chạy server game (port 2236)
bash start_server.sh

# Chạy web (port 8080) - trong terminal khác
bash start_web.sh
```

### Truy cập
- 🌐 **Web**: `http://localhost:8080`
- 🎮 **Game**: client HTTH kết nối `IP:2236`

## 🗄️ Thông tin bản server

| Thông số | Giá trị |
|----------|---------|
| Tên server | Hải Tặc Tí Hon (HTTH) |
| Port game | `2236` |
| Port web | `8080` |
| Database | `htth` (user `root`, không mật khẩu) |
| Tỉ lệ EXP | x5 (cấu hình `htth.conf`) |
| File DB | `database/htth_full.sql` |
| Tác giả gốc | truongbk |

## 📁 Cấu trúc thư mục

```
HTTH_Server_Termux/
├── menu.sh          # Menu quản lý (Termux)
├── server.jar       # Server game
├── htth.conf        # Cấu hình server
├── start_server.sh  # Chạy game (port 2236)
├── start_web.sh     # Chạy web (port 8080)
├── import_database.sh # Import database htth
├── database/        # htth_full.sql
├── data/            # Dữ liệu game (map, icon, template...)
├── web/             # Web PHP (Admin, Api...)
└── client/          # Client game
```

## 📄 Giấy phép

Server gốc bởi **truongbk**, đóng gói Termux bởi cộng đồng.

## 👤 Tác giả bản Termux

- **longth68** — đóng gói + đẩy lên GitHub
