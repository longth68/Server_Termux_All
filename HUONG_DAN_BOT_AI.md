# Hướng dẫn cập nhật BOT AI NSO (NRO-style)

Bản này port kiến trúc `VirtualPlayer` của NRO sang Ninja School (`Exe_Z.bot.ai`).

## 1. Có gì mới

* Package mới `ninja/server/src/main/java/Exe_Z/bot/ai/` (14 module):
  `BotPersonality` (20 tính cách), `BotState` (18 trạng thái), `BotProfile`,
  `BotNeeds` (EXP/GOLD/ITEM/QUEST/SOCIAL/REST/EXPLORE), `BotMemory`,
  `BotGoals`, `BotDecision`, `BotPerception` (nhường quái `player_protection`),
  `BotCombat`, `BotMovement`, `BotChat`, `BotSocial`, `BotEconomy`,
  `BotEquipment`, `BotPersistence` (JSON, không đụng DB), `BotConfig`,
  `BotBrain`, `BotManager`.
* `AutoFarmBot.java`: giữ logic farm cũ, thêm nhánh AI mới (`aiEnabled=true`
  mặc định). Tắt AI về logic cũ: sửa `aiEnabled=false` rồi build lại.
* `WebAdminCommandPoller.java`: thêm lệnh `BOT_CONFIG`, `KILL_ONE_BOT`,
  tự ghi bảng `bot_status` mỗi ~3 giây.
* Web Admin `/admin/bot`: thêm form **Cấu hình BOT AI**, bảng
  **Quản lý thông tin BOT** (Tên/Lv/Map-Khu/HP/State/Personality/Need + nút Xóa lẻ).
* `ninja/server/bot_config.txt`: cấu hình mặc định (port từ NRO).
* `ninja/server/exe_nsoz.sql`: thêm `CREATE TABLE bot_status` cho máy cài mới.
  Máy cũ **không cần import tay** — server tự tạo bảng khi chạy.
* Đợt fix `46a8f92d`: `speed` hiển thị đúng (trước đây bị ghi đè 0),
  fallback tọa độ kẹp `short` theo `tilemap.pxw/pxh` (hết rơi đáy map),
  `BotManager` đếm bot thật theo zone (`countInZone`), `bot_save.json`
  lưu snapshot thật.

## 2. Cập nhật trên Termux (máy đang chạy bản cũ)

```bash
cd ~/Server_Termux_All
git pull origin main
# Nếu báo local changes ở file runtime:
#   git stash push -m backup -- nro/Server/data/virtualplayer_save.json
#   git pull origin main
#   git stash pop

# Khởi động lại Ninja để nạp jar mới + bảng bot_status tự tạo
bash ninja_stop.sh
bash ninja_start.sh
```

Không mất dữ liệu: BOT không ghi DB `schoolzz`, chỉ đọc user/char hiện có.
`bot_save.json` / `bot_config.txt` là file text, pull code không xóa.

## 3. Dùng Web Admin

1. Mở `http://127.0.0.1:8000/admin/bot` (quyền `admin_web=1`).
2. **Cấu hình BOT AI**: đổi `population` (tổng số), `bots_per_map` (1-8),
   `player_protection` (px nhường quái), các `*_rate` rồi **Lưu** → lệnh
   `BOT_CONFIG` → server áp dụng trong vài giây, lưu `bot_config.txt`.
3. **Quản lý thông tin BOT**: xem từng bot (state/personality/need),
   nút **Xóa** gửi `KILL_ONE_BOT`. Nút **Xóa toàn bộ** gửi `KILL_BOT`.
4. Lịch sử lệnh hiển thị cả `SPAWN_BOT/KILL_BOT/KILL_ONE_BOT/BOT_CONFIG`.

## 4. Build lại từ source (PC)

Yêu cầu JDK 17+ (khuyên 21). Không cần Maven nếu chỉ sửa nhỏ:

```powershell
$javac = "C:\Program Files\Java\jdk-21.0.10\bin\javac.exe"
$srv = "F:\Source NSO\Server_Termux_All\ninja\server"
$cp = "$srv\target\classes;$env:USERPROFILE\.m2\repository\com\googlecode\json-simple\json-simple\1.1\json-simple-1.1.jar"
$files = (Get-ChildItem "$srv\src\main\java\Exe_Z\bot\ai" -Filter *.java | % FullName)
$files += "$srv\src\main\java\Exe_Z\bot\AutoFarmBot.java"
$files += "$srv\src\main\java\Exe_Z\server\WebAdminCommandPoller.java"
& $javac -encoding UTF-8 -proc:none -d "$srv\target\classes" -cp $cp @files
```

Build chuẩn bằng Maven (khi có mạng):

```bash
cd ninja/server
mvn -q package -DskipTests
cp target/Nso-jar-with-dependencies.jar app.jar
```

## 5. Rollback nhanh

* Tắt AI mới, giữ farm cũ: trong `AutoFarmBot.java` đặt
  `public boolean aiEnabled = false;` → build lại `app.jar`.
* Hoặc về bản trước khi update: `git log --oneline -5` rồi
  `git revert <commit>` / `git checkout <commit> -- ninja/server/app.jar`.

## 6. Kiểm tra sau update

* Log server có `[BOT][MANAGER] NsoBotManager started` và `[BOT][SWEEPER]`.
* Bảng `bot_status` có dữ liệu: `SELECT COUNT(*) FROM bot_status;`
* `/admin/bot` hiện số bot + bảng chi tiết sau ~1 vòng poll (3 giây).
* Vào game kiểm tra: BOT mặc đồ đúng (áo/quần/vũ khí theo level, đầu nam/nữ),
  di chuyển mượt trong map (không đứng yên, không rơi đáy map), đánh quái
  mất HP, chat/party/giao dịch bình thường.

## 7. Đã kiểm chứng khớp game (2 game khác nhau cũng an toàn)

BOT NSO chạy process `ninja/server/app.jar` hoàn toàn độc lập với NRO —
chỉ mượn ý tưởng kiến trúc, toàn bộ API gọi (`Zone.move/join/out`,
`MapService.chat/attackMonster/pickItem/loadHP/playerMove`, `Mob.lock/addHp/die`,
`collisionY`, `setXY`, `joinZone`) đều là hàm NSO gốc:

* Trang bị: `equipment[type 0..9]` đúng slot, `FashionFromEquip` dựng hình,
  `setFashion()` trước `zone.join()` nên client thấy chuẩn.
* Tọa độ: bước 55 trong `waypoints`, snap đất `collisionY`, kẹp `short`,
  bot được miễn check anti-cheat tốc độ của người chơi.
* Combat: khóa mob, trừ HP thật, broadcast đúng, `die()` hồi sinh chuẩn,
  nhường quái trong `player_protection` px quanh người chơi thật.
* Không đụng DB `schoolzz`: config + save đều là file text
  (`bot_config.txt`, `bot_save.json`).
