# NinjaServerTermux 🥷

> Chạy server **Ninja School Online** trên **Termux (Android)** mà không cần root.

Dựa trên cấu trúc và cách làm của [Ninja_Server_Termux (KhanhNguyen9872)](https://github.com/KhanhNguyen9872/Ninja_Server_Termux), nguồn server lấy từ **NinjaSchoolZ (Server_Play_Termux / Exe_Z)** — Java server hoàn chỉnh với **12 sự kiện**, web nạp thẻ, 177 map, Shinwa, Lôi Đài, Thiên Địa...

---

## ✨ Tính năng

- ✅ Cài đặt 1 dòng trên Termux (không root)
- ✅ Server Java 17 (tương thích `openjdk-17` chuẩn của Termux)
- ✅ MariaDB tự khởi tạo + import database `schoolzz` tự động
- ✅ Web PHP (đăng ký, ranking, nạp thẻ, admin) port `8000`
- ✅ Server port `14444`
- ✅ Menu điều khiển: bật/tắt server, web, DB, backup, xem log
- ✅ `.bash_profile` tự mở menu khi mở Termux

## 🎮 Sự kiện server

Halloween, Tết (Lunar New Year), Noel, Trung Thu, Summer, KoroKing, 8/3, 20/10, KoroKing... (cấu hình trong `server/config.properties`, dòng `game.event`)

## 📋 Yêu cầu hệ thống

- Android 7 trở lên (chạy được Termux)
- Kiến trúc: `arm64-v8a` / `armeabi-v7a` / `x86_64` (không hỗ trợ x86 32bit)
- Khoảng **2–4 GB trống** cho server + database

## 🚀 Cài đặt

### Cách 1: Một dòng (khuyên dùng)

Mở Termux và dán:

```bash
function install () {
  clear
  curl -L --max-redirs 15 --progress-bar "https://raw.githubusercontent.com/longth68/Server_Play_Termux/master/script_install.sh" --output script_install.sh
  bash script_install.sh
  unset install
}
install
```

### Cách 2: Thủ công

```bash
pkg update -y && pkg upgrade -y
pkg install -y openjdk-17 mariadb php curl unzip zip wget

# Khởi tạo + chạy MariaDB
mariadb-install-db --datadir=$PREFIX/var/lib/mysql
mysqld_safe -u root &

# Import database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS schoolzz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root --default-character-set=utf8mb4 schoolzz < server/exe_nsoz.sql

# Chạy server
cd server
java -server -Xms256M -Xmx512M -Dfile.encoding=UTF-8 -jar Nso-jar-with-dependencies.jar
```

## 🕹️ Menu điều khiển

```bash
bash $HOME/ninja-server/menu.sh
```

| Lựa chọn | Chức năng |
|---|---|
| `1` | Bắt đầu server |
| `2` | Dừng server |
| `3` | Trạng thái server |
| `4` / `5` | Mở / Dừng web PHP |
| `6` / `7` | Khởi động / Dừng MariaDB |
| `8` | Xem log server |
| `9` | Backup database |
| `0` | Thoát |

## 🗂️ Cấu trúc repo

```
NinjaServerTermux/
├── script_install.sh      # Bootstrap installer
├── install.sh             # Cài đặt chính (deps + DB + deploy)
├── menu.sh                # Menu điều khiển
├── khanhupdate.sh         # Cập nhật script
├── .bash_profile          # Tự mở menu khi mở Termux
├── server/                # Toàn bộ server (Java + web + data)
│   ├── Nso-jar-with-dependencies.jar
│   ├── config.properties
│   ├── exe_nsoz.sql
│   ├── Data/              # Map, Img, Lang
│   ├── item_roi/          # Drop-table sự kiện (44 JSON)
│   └── web/               # Web PHP (register, nạp thẻ, admin)
├── info/                  # Version, welcome, changelog
├── CONF_FILE/             # File cấu hình termux/ninja
├── bin32/ binx64/         # (dành cho package nhị phân tùy chọn)
├── lib/                   # (thư viện tùy chọn)
└── tamp/bin/              # (công cụ hỗ trợ tùy chọn)
```

## ⚙️ Cấu hình

Mọi cấu hình nằm ở `server/config.properties`:

```properties
server.port=14444
db.dbname=schoolzz
game.event=Exe_Z.event.SumMer
game.maxLv=131
game.shinwa.active=true
game.arena.active=true
```

## 📚 Nguồn gốc & License

- Cấu trúc repo tham khảo: [KhanhNguyen9872/Ninja_Server_Termux](https://github.com/KhanhNguyen9872/Ninja_Server_Termux)
- Nguồn server: **NinjaSchoolZ / Server_Play_Termux** (`com.kitakeyos:Exe_Z`)
- Toàn bộ project phát hành theo [GPL-3.0](LICENSE)

## 👤 Tác giả & Hỗ trợ

- Project cá nhân. Tạo issue trên GitHub nếu gặp vấn đề.

---

> ⚠️ **Lưu ý:** Project này dành cho mục đích học tập/nghiên cứu. Vui lòng tôn trọng bản quyền game gốc.
