# Server_Termux_All — 3 Game trong 1 Bộ Cài

Bộ cài tích hợp **Ninja School Online**, **Hải Tặc Hot** và **Ngọc Rồng Anwin V3** (3 server game hoàn toàn độc lập) chạy trên Termux Android.
Cả 3 dùng chung môi trường (Java 17/21 + MariaDB + PHP) và **chỉ khác cách chạy server** (port riêng biệt không xung đột, DB riêng trên cùng MariaDB).

---

## ⚡ CẬP NHẬT BỘ CÀI (KHÔNG MẤT DỮ LIỆU)

Khi có bản mới trên GitHub, bạn **KHÔNG CẦN CÀI LẠI TỪ ĐẦU** — dữ liệu cả 3 game (DB + tài nguyên đã giải nén) được giữ nguyên!

### Cách 1: Cập nhật nhanh bằng Git (Khuyến nghị)
Mở ứng dụng Termux và gõ:
```bash
cd ~/Server_Termux_All
git pull origin main
bash update_nro.sh
```
*Script `update_nro.sh` sẽ nạp database `awnv3` nếu chưa có và giải nén tài nguyên NRO nếu chưa có (client APK/JAR có sẵn trên Web Download).*

### Cách 2: Cập nhật qua Menu điều khiển
1. Trong Termux, gõ: `menu` (hoặc `bash ~/Server_Termux_All/menu.sh`).
2. Chọn phím số **`13`** (*Cập nhật Ngọc Rồng Anwin V3*).

### Cách 3: Cập nhật thủ công (Nếu tải file nén .zip / .tar.gz trước đó)
1. Tải bản cập nhật mới nhất từ GitHub.
2. Sao chép thư mục `nro/` và các file `nro_start.sh`, `nro_stop.sh`, `update_nro.sh`, `menu.sh`, `start_db.sh`, `stop.sh` vào thư mục `~/Server_Termux_All`.
3. Mở Termux và chạy lệnh:
   ```bash
   cd ~/Server_Termux_All
   bash update_nro.sh
   ```

---

## 🔄 HƯỚNG DẪN CẬP NHẬT BỘ CÀI (KHÔNG MẤT DỮ LIỆU)

Khi có bản mới trên GitHub (BOT AI NSO, Web Admin, fix...), chỉ cần kéo code mới —
**dữ liệu cả 3 game (DB + file runtime) được giữ nguyên**.

### Bước 1: Kéo code mới
```bash
cd ~/Server_Termux_All
git pull origin main
```
* Nếu báo lỗi `local changes` ở file runtime (VD `virtualplayer_save.json`):
  ```bash
  git stash push -m backup -- <file-bị-báo>
  git pull origin main
  git stash pop
  ```

### Bước 2: Cập nhật Ninja School NSO (BOT AI + Web Admin mới)
```bash
bash ninja_stop.sh
bash start_db.sh
bash ninja_start.sh
```
* Nhận `ninja/server/app.jar` mới (BOT AI + lệnh `BOT_CONFIG`/`KILL_ONE_BOT`).
* `start_db.sh` tự tạo bảng `bot_status` nếu máy cũ chưa có, **không xóa DB
  `schoolzz` cũ** (chỉ tạo + nạp DB khi chưa có).
* Web Admin Ninja (`http://localhost:8000/admin/bot`) có ngay mục
  **Cấu hình BOT AI** + **Quản lý thông tin BOT**.

### Bước 3: Cập nhật Ngọc Rồng (nếu có bản NRO mới)
```bash
bash update_nro.sh
```
* Script tự nạp DB `awnv3` nếu chưa có, giải nén tài nguyên nếu chưa có,
  không đụng tới DB `schoolzz` / `htth` cũ.

### Bước 4: Khởi động lại game
```bash
bash menu.sh
```
* `3` → chạy Ninja | `4` → HTTH | `5` → NRO Anwin V3
* `6` → chạy cả 3 | `10` → dừng tất cả game (giữ MariaDB).

### Lưu ý
* `bot_config.txt` / `bot_save.json` là file text riêng, `git pull` không xóa.
* Muốn về bản cũ: `git log --oneline -5` rồi
  `git checkout <commit> -- <đường-dẫn-file>` (VD `ninja/server/app.jar`).

---

## Bảng thông số Port & Database (Đã tối ưu không trùng nhau)

| Game | Cổng Game | Cổng Web | Cổng API Server | Database | Thư mục |
|---|---|---|---|---|---|
| **Ninja School** | **14444** | **8000** | — | `schoolzz` | `ninja/` |
| **Hải Tặc Hot** | **2236** | **8080** | — | `htth` | `htth/` |
| **Ngọc Rồng Anwin V3** | **14445** | **8888** | **8085** | `awnv3` | `nro/` |

> 💡 **Quy hoạch cổng tránh xung đột:**
> - Cổng Game NRO đặt là `14445` (không trùng `14444` của Ninja và `2236` của HTTH).
> - Cổng API Server NRO đổi thành `8085` (để nhường cổng `8080` cho Web HTTH).
> - Cổng Web Admin NRO đặt là `8888` (không trùng `8000` của Ninja và `8080` của HTTH).
> - NRO Anwin V3 là server **monolith 1 process** (không cần ServerLogin riêng như bản Hashirama cũ).
> - Cả 3 game có thể chạy **đồng thời cùng một lúc** mà không hề bị chiếm cổng hay lỗi xung đột.

---

## 🤖 BOT AI Ninja School (kiến trúc NRO — đã kiểm chứng với game)

Ninja School đã được nâng cấp BOT AI theo kiến trúc `VirtualPlayer` của Ngọc Rồng:
`Personality` (20 tính cách) + `Needs` (EXP/GOLD/ITEM/QUEST/SOCIAL/REST/EXPLORE) +
`Memory` + `State machine` (18 trạng thái) + `Brain` + `Manager` —
bot tự farm, nhặt đồ, hồi phục, chat, lập tổ đội, nhường quái cho người chơi thật
(`player_protection`), tự đi theo map có người chơi.

Đã rà soát khớp với game NSO (`Char`/`Zone`/`Mob`/`ItemMap`/`MapService`):
* **Trang bị hiển thị đúng**: `equipment[type]` map đúng slot `NON=0..PHU=9`,
  `FashionFromEquip` dựng `weapon/body/leg/head` từ đồ, `setFashion()` chạy
  trước `zone.join()` nên client khác thấy ngoại hình chuẩn; `speed` đã fix
  (trước đây bị ghi đè về 0).
* **Di chuyển đúng tọa độ map**: bước 55 trong `waypoints`, snap đất bằng
  `collisionY`, kẹp `short` theo `tilemap.pxw/pxh`, qua `zone.move`
  (bot được miễn check anti-cheat tốc độ như người chơi).
* **Combat đúng map**: khóa `mob.lock`, trừ HP thật, broadcast `attackMonster`,
  `mob.die()` hồi sinh chuẩn; bot nhường quái trong vòng `player_protection`
  quanh người chơi thật.

* Web Admin `/admin/bot` (web Ninja `http://localhost:8000`):
  - **Cấu hình BOT AI** (`enabled`, `population`, `exp_rate`, `bots_per_map`,
    `presence_per_player`, `player_protection`, các `*_rate`).
  - **Quản lý thông tin BOT** (Tên/Lv/Phái-Class/Map-Khu/HP/Vàng/State/Mục tiêu/
    Need/Bạn/Online + needs, chỉ số AI, người gần nhất).
  - **Chi tiết từng BOT**: xem full + **chỉnh sửa** (level/HP/dame),
    **sửa trang bị-túi đồ** (cho/gỡ/mặc), thao tác nhanh (dịch chuyển tới người
    chơi, cộng vàng, mặc lại đồ, xóa lẻ, xóa tất cả).
  - **Câu chat tùy chỉnh** (`bot_chat.txt`): thêm/xóa trên web, server tự nạp.
* Web Admin `/admin/user`: **đổi tên nhân vật**, **tặng đồ cho người online**
  (mẫu NRO), bên cạnh Kick/Ban/Xóa/Tìm kiếm có sẵn.
* BOT scale theo **cấp độ** người chơi (NRO scale theo sức mạnh): level không
  vượt người online mạnh nhất, chỉ số 80% mốc chuẩn — ngang tầm, không quá mạnh.
* File cấu hình: `ninja/server/bot_config.txt`, `bot_chat.txt`. Bảng
  `bot_status` tự tạo/nâng cấp khi server chạy (máy mới có sẵn trong
  `exe_nsoz.sql`), không cần import tay.
* Cập nhật trên Termux: `git pull origin main` rồi
  `bash ninja_stop.sh; bash ninja_start.sh` (không mất dữ liệu `schoolzz`).
* Chi tiết: xem [`HUONG_DAN_BOT_AI.md`](HUONG_DAN_BOT_AI.md).

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
- Tự động tạo và nạp cơ sở dữ liệu `awnv3` (Ngọc Rồng Anwin V3).

---

## 4. Chạy server game

### Cách 1: Dùng menu trực quan (Khuyến nghị)

```bash
bash menu.sh
```

Menu cho phép:
- `3` → Chạy **Ninja School** (game 14444 + web 8000)
- `4` → Chạy **Hải Tặc Hot** (game 2236 + web 8080)
- `5` → Chạy **Ngọc Rồng Anwin V3** (game 14445 + web 8888 + api 8085)
- `6` → Chạy **CẢ 3 GAME CÙNG LÚC**
- `7` → Dừng Ninja School
- `8` → Dừng Hải Tặc Hot
- `9` → Dừng Ngọc Rồng
- `10` → Dừng TẤT CẢ game (giữ MariaDB)
- `11` → Dừng cả MariaDB
- `12` → Xem log server
- `13` → **Cập nhật Ngọc Rồng Anwin V3** (kéo bản mới + nạp DB + giải nén tài nguyên)

### Cách 2: Chạy nhanh qua dòng lệnh

```bash
bash ninja_start.sh    # Chạy Ninja School
bash htth_start.sh     # Chạy Hải Tặc Hot
bash nro_start.sh      # Chạy Ngọc Rồng Anwin V3
bash stop.sh           # Dừng tất cả game (giữ MariaDB)
bash stop_db.sh        # Dừng MariaDB
```

---

## 5. Tải Client & Kết nối chơi game

### Tải Client game Ngọc Rồng (Đã cấu hình sẵn IP 127.0.0.1:14445)
Truy cập trang Web trên điện thoại: **`http://127.0.0.1:8888/`**
- Bấm vào nút **Android** để tải file APK (`NRO-LOCAL.apk`, client gốc theo server).
- Bấm vào nút **Java** để tải file JAR (`Nro HanZi.jar`).
*(Cả 2 bản đều đã được nạp sẵn IP `127.0.0.1` cổng `14445`, tải về là đăng nhập chơi được ngay!)*

### Chơi trên cùng điện thoại chạy server
- **Ninja School**: Client trỏ về `127.0.0.1:14444`. Web đăng ký: `http://localhost:8000`.
- **Hải Tặc Hot**: Client trỏ về `127.0.0.1:2236`. Web đăng ký: `http://localhost:8080`.
- **Ngọc Rồng**: Client trỏ về `127.0.0.1:14445`. Web: `http://localhost:8888/` | Quản trị: `http://localhost:8888/admin.php` (đăng ký acc trên web rồi set `is_admin=1` trong DB).

### Chơi từ máy khác trong cùng WiFi/LAN
1. Gõ `ip addr show wlan0` trong Termux để xem IP điện thoại (ví dụ: `192.168.1.50`).
2. Trỏ IP trong client của máy khác về IP đó với cổng game tương ứng:
   - Ninja: `192.168.1.50:14444` | Web: `http://192.168.1.50:8000`
   - HTTH: `192.168.1.50:2236` | Web: `http://192.168.1.50:8080`
   - NRO: `192.168.1.50:14445` | Web: `http://192.168.1.50:8888/`
---

## 6. Nhật ký Log Server
Toàn bộ log được lưu trong thư mục `logs/`:
- `logs/ninja_server.log`, `logs/ninja_web.log`
- `logs/htth_server.log`, `logs/htth_web.log`
- `logs/nro_server.log`, `logs/nro_web.log`