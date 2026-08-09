# Server_Termux_All — 2 Game trong 1 Bộ Cài

Bộ cài tích hợp **Ninja School** và **Hải Tặc Hot** (2 server game khác nhau) chạy trên Termux Android.
Cả 2 dùng chung môi trường (Java 21 + MariaDB + PHP) và **chỉ khác cách chạy server** (jar, port, DB riêng).

## Yêu cầu

- Android (mọi phiên bản), đã cài **Termux** từ F-Droid.
- Đủ dung lượng: bộ cài giải nén ~1.1 GB + dữ liệu MariaDB.
- Kết nối mạng (WiFi/LAN) để máy khác vào chơi được.

---

## 1. Cài đặt Termux

```bash
# Cập nhật package (bắt buộc lần đầu)
pkg update -y && pkg upgrade -y

# Cấp quyền lưu trữ để truy cập thư mục Downloads
termux-setup-storage
```

---

## 2. Tải bộ cài về

Tải file `Server_Termux_All.tar.gz` (~1 GB) từ Release:
https://github.com/longth68/Server_Termux_All/releases

Cách tải nhanh bằng gh CLI trong Termux:

```bash
pkg install -y gh
gh auth login
gh release download v1.0 --repo longth68/Server_Termux_All
```

Hoặc tải thủ công bằng `curl` (lấy link download từ trang Release):
```bash
curl -L -o Server_Termux_All.tar.gz "LINK_DOWNLOAD"
```

---

## 3. Giải nén

```bash
# Nên để ở thư mục chính của Termux (~)
mv Server_Termux_All.tar.gz ~/
cd ~
tar -xzf Server_Termux_All.tar.gz
cd Server_Termux_All
```

---

## 4. Cài môi trường chung (CHỈ LÀM 1 LẦN)

```bash
bash install.sh
```

Script này sẽ:
- `pkg install openjdk-21 mariadb php php-mbstring git curl unzip tar`
- Khởi tạo thư mục dữ liệu MariaDB
- Tạo lệnh gõ nhanh `menu` ở mọi nơi

> Nếu `pkg install` lỗi, hãy chạy lại `pkg update -y` rồi thử lại.

---

## 5. Khởi động MariaDB dùng chung

```bash
bash start_db.sh
```

Script này sẽ:
- Khởi động MariaDB (dùng chung cho cả 2 game)
- Tạo user `root`@`localhost` (mật khẩu trống)
- Tạo + import DB `schoolzz` (từ `ninja/server/exe_nsoz.sql`)
- Tạo + import DB `htth` (từ `htth/database/htth_full.sql`)

> Chỉ cần chạy 1 lần. Lần sau MariaDB đã chạy sẵn, script sẽ bỏ qua.

---

## 6. Chạy server game

### Cách 1: Dùng menu (gợi ý)

```bash
bash menu.sh
```

Menu cho phép:
- `3` → Chạy **Ninja School** (game 14444 + web 8000)
- `4` → Chạy **Hải Tặc Hot** (game 2236 + web 8080)
- `5` → Chạy **CẢ HAI** cùng lúc
- `6` → Dừng game/web (giữ MariaDB)
- `8` → Xem log server

### Cách 2: Chạy nhanh không qua menu

```bash
bash ninja_start.sh    # chỉ chạy Ninja School
bash htth_start.sh     # chỉ chạy Hải Tặc Hot
bash stop.sh           # dừng tất cả game/web
```

---

## 7. Chơi game

### Từ chính chiếc điện thoại chạy server (cùng máy)

Mở client và vào game với IP **127.0.0.1**.

- **Ninja School**: client trong `ninja/server/web/files/` (jar/apk/zip). Web đăng ký tài khoản: `http://localhost:8000`.
- **Hải Tặc Hot**: client trong `htth/client/` (apk/jar). Web đăng ký tài khoản: `http://localhost:8080`.

### Từ máy khác trong cùng WiFi/LAN

1. Tìm IP của điện thoại chạy server:
   ```bash
   ifconfig wlan0 | grep inet
   # hoặc
   ip addr show wlan0 | grep inet
   ```
   Ví dụ IP là `192.168.1.50`.

2. Trên máy chơi game, vào **cài đặt IP server** của client và đổi sang IP đó (ví dụ `192.168.1.50`), giữ nguyên port game.

3. Truy cập web đăng ký từ máy khác:
   - Ninja: `http://192.168.1.50:8000`
   - HTTH: `http://192.168.1.50:8080`

> ⚠️ Các client kèm sẵn trong bộ cài mặc định trỏ về **127.0.0.1** (chơi cùng máy). Muốn chơi từ máy khác phải sửa IP trong client (hoặc dùng bản client đã đổi IP).

---

## 8. Port / DB tóm tắt

| Game | Game port | Web port | Database | Thư mục | Client |
|------|-----------|----------|----------|---------|--------|
| Ninja School | 14444 | 8000 | `schoolzz` | `ninja/server/` | `ninja/server/web/files/` |
| Hai Tac Hot | 2236 | 8080 | `htth` | `htth/` | `htth/client/` |

Cả 2 có thể chạy **cùng lúc** (port khác nhau, DB khác nhau, chung 1 MariaDB).

---

## 9. Dừng server

```bash
bash stop.sh       # dừng mọi game/web, giữ MariaDB
bash stop_db.sh    # dừng cả MariaDB (khi không dùng nữa)
```

---

## 10. Xem log & lỗi thường gặp

Log server nằm trong thư mục `logs/`:
- `logs/ninja_server.log`
- `logs/ninja_web.log`
- `logs/htth_server.log`
- `logs/htth_web.log`

```bash
tail -f logs/ninja_server.log   # xem log trực tiếp
```

| Lỗi | Cách xử lý |
|-----|-----------|
| `pkg: command not found` | Chạy `termux-change-repo` rồi `pkg update -y` |
| `Access denied for user root` | Chạy lại `bash start_db.sh` (tự tạo user root) |
| Web vào báo "Connection failed" | MariaDB chưa chạy → `bash start_db.sh` |
| Game không vào được, port trùng | Đổi `WEB_PORT`/port trong script start |
| Hết dung lượng | Xóa `logs/*.log`, hoặc bỏ bớt client không dùng trong `web/files` |
| Chơi từ máy khác không vào | Đổi IP client từ 127.0.0.1 sang IP điện thoại (xem mục 7) |

---

## Ghi chú

- `start_db.sh` tự tạo DB `schoolzz` (import `ninja/server/exe_nsoz.sql`) và DB `htth` (import `htth/database/htth_full.sql`).
- Ninja client: `ninja/server/web/files/` (jar/apk/zip).
- HTTH client: `htth/client/` (apk/jar).
- Log server: `logs/ninja_server.log`, `logs/htth_server.log`, `logs/*_web.log`.
