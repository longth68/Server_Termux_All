# Server_Termux_All — 3 Game trong 1 Bộ Cài

Bộ cài tích hợp **Ninja School Online**, **Hải Tặc Hot** và **Ngọc Rồng Hashirama** (3 server game hoàn toàn độc lập) chạy trên Termux Android.
Cả 3 dùng chung môi trường (Java 17/21 + MariaDB + PHP) và **chỉ khác cách chạy server** (jar, port riêng biệt không xung đột, DB riêng trên cùng MariaDB).

---

## ⚡ HƯỚNG DẪN CẬP NHẬT THÊM NGỌC RỒNG (CHO MÁY ĐÃ CÀI 2 GAME TRƯỚC)

Nếu điện thoại của bạn **đã cài đặt và đang chơi 2 game Ninja School + Hải Tặc Hot**, bạn **KHÔNG CẦN CÀI LẠI TỪ ĐẦU** và **KHÔNG BỊ MẤT DỮ LIỆU CỦA 2 GAME CŨ**!

Hãy làm theo 1 trong các cách sau:

### Cách 1: Cập nhật nhanh bằng Git (Khuyến nghị)
Mở ứng dụng Termux và gõ:
```bash
cd ~/Server_Termux_All
git pull origin main
bash update_nro.sh
```
*Script `update_nro.sh` sẽ tự động khởi động MariaDB, nạp thêm database `hashirama` và giải nén tài nguyên game Ngọc Rồng trong vài giây mà không đụng tới dữ liệu cũ.*

### Cách 2: Cập nhật qua Menu điều khiển
1. Trong Termux, gõ: `menu` (hoặc `bash ~/Server_Termux_All/menu.sh`).
2. Chọn phím số **`13`** (*Cập nhật thêm game Ngọc Rồng*).

### Cách 3: Cập nhật thủ công (Nếu tải file nén .zip / .tar.gz trước đó)
1. Tải bản cập nhật mới nhất từ GitHub.
2. Sao chép thư mục `nro/` và các file `nro_start.sh`, `nro_stop.sh`, `update_nro.sh`, `menu.sh`, `start_db.sh`, `stop.sh` vào thư mục `~/Server_Termux_All`.
3. Mở Termux và chạy lệnh:
   ```bash
   cd ~/Server_Termux_All
   bash update_nro.sh
   ```

---

## Bảng thông số Port & Database (Đã tối ưu không trùng nhau)

| Game | Cổng Game | Cổng Web | Cổng API Server | Database | Thư mục |
|---|---|---|---|---|---|
| **Ninja School** | **14444** | **8000** | — | `schoolzz` | `ninja/` |
| **Hải Tặc Hot** | **2236** | **8080** | — | `htth` | `htth/` |
| **Ngọc Rồng Hashirama** | **14445** | **8888** | **8085** | `hashirama` | `nro/` |

> 💡 **Quy hoạch cổng tránh xung đột:**
> - Cổng Game NRO đặt là `14445` (không trùng `14444` của Ninja và `2236` của HTTH).
> - Cổng API Server NRO đổi thành `8085` (để nhường cổng `8080` cho Web HTTH).
> - Cổng Web Admin NRO đặt là `8888` (không trùng `8000` của Ninja và `8080` của HTTH).
> - Cả 3 game có thể chạy **đồng thời cùng một lúc** mà không hề bị chiếm cổng hay lỗi xung đột.

---

## 1. Cài đặt Termux (Dành cho máy mới cài lần đầu)

```bash
# Cập nhật package (bắt buộc lần đầu)
pkg update -y && pkg upgrade -y

# Cấp quyền lưu trữ để truy cập bộ nhớ
termux-setup-storage
```

---

## 2. Cài môi trường chung (Chỉ làm 1 lần)

```bash
bash install.sh
```

Script này sẽ:
- Cài đặt OpenJDK 21, MariaDB, PHP và các công cụ mạng cần thiết.
- Khởi tạo thư mục dữ liệu MariaDB.
- Tạo alias gõ nhanh lệnh `menu` ở mọi nơi.

---

## 3. Khởi động MariaDB dùng chung

```bash
bash start_db.sh
```

Script này sẽ:
- Khởi động MariaDB (dùng chung cho cả 3 game trên cổng `3306`).
- Tự động tạo và nạp cơ sở dữ liệu `schoolzz` (Ninja School).
- Tự động tạo và nạp cơ sở dữ liệu `htth` (Hải Tặc Hot).
- Tự động tạo và nạp cơ sở dữ liệu `hashirama` (Ngọc Rồng Hashirama).

---

## 4. Chạy server game

### Cách 1: Dùng menu trực quan (Khuyến nghị)

```bash
bash menu.sh
```

Menu cho phép:
- `3` → Chạy **Ninja School** (game 14444 + web 8000)
- `4` → Chạy **Hải Tặc Hot** (game 2236 + web 8080)
- `5` → Chạy **Ngọc Rồng Hashirama** (game 14445 + web 8888 + api 8085)
- `6` → Chạy **CẢ 3 GAME CÙNG LÚC**
- `7` → Dừng Ninja School
- `8` → Dừng Hải Tặc Hot
- `9` → Dừng Ngọc Rồng
- `10` → Dừng TẤT CẢ game (giữ MariaDB)
- `11` → Dừng cả MariaDB
- `12` → Xem log server
- `13` → **Cập nhật thêm game Ngọc Rồng** (cho máy đã cài 2 game cũ)

### Cách 2: Chạy nhanh qua dòng lệnh

```bash
bash ninja_start.sh    # Chạy Ninja School
bash htth_start.sh     # Chạy Hải Tặc Hot
bash nro_start.sh      # Chạy Ngọc Rồng Hashirama
bash stop.sh           # Dừng tất cả game (giữ MariaDB)
bash stop_db.sh        # Dừng MariaDB
```

---

## 5. Kết nối chơi game & Quản trị Web

### Chơi trên cùng điện thoại chạy server
- **Ninja School**: Client trỏ về `127.0.0.1:14444`. Web đăng ký: `http://localhost:8000`.
- **Hải Tặc Hot**: Client trỏ về `127.0.0.1:2236`. Web đăng ký: `http://localhost:8080`.
- **Ngọc Rồng**: Client trỏ về `127.0.0.1:14445`. Web Admin: `http://localhost:8888/admin.php` (tài khoản: `admin1` / `admin123123123`).

### Chơi từ máy khác trong cùng WiFi/LAN
1. Gõ `ip addr show wlan0` trong Termux để xem IP điện thoại (ví dụ: `192.168.1.50`).
2. Trỏ IP trong client của máy khác về IP đó với cổng game tương ứng:
   - Ninja: `192.168.1.50:14444` | Web: `http://192.168.1.50:8000`
   - HTTH: `192.168.1.50:2236` | Web: `http://192.168.1.50:8080`
   - NRO: `192.168.1.50:14445` | Web Admin: `http://192.168.1.50:8888/admin.php`

---

## 6. Nhật ký Log Server
Toàn bộ log được lưu trong thư mục `logs/`:
- `logs/ninja_server.log`, `logs/ninja_web.log`
- `logs/htth_server.log`, `logs/htth_web.log`
- `logs/nro_server.log`, `logs/nro_web.log`