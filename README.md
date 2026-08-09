# Server_Termux_All — 2 Game trong 1 Bộ Cài

Bộ cài tích hợp **Ninja School** và **Hải Tặc Hot** (2 server game khác nhau) chạy trên Termux Android.
Cả 2 dùng chung môi trường (Java 21 + MariaDB + PHP) và **chỉ khác cách chạy server** (jar, port, DB riêng).

## Cấu trúc

```
Server_Termux_All/
├── install.sh        # Cài môi trường chung: Java 21 + MariaDB + PHP
├── start_db.sh       # Khởi động MariaDB dùng chung + tạo/import 2 DB
├── stop_db.sh        # Dừng MariaDB
├── ninja_start.sh    # Chạy Ninja School (game 14444 + web 8000)
├── htth_start.sh     # Chạy Hải Tặc Hot (game 2236 + web 8080)
├── stop.sh           # Dừng mọi game/web (giữ MariaDB)
├── menu.sh           # MENU CHÍNH: chọn game để chạy
├── ninja/            # Toàn bộ NinjaServerTermux
│   └── server/       #   app.jar, Data, web, src...
└── htth/             # Toàn bộ HTTH_Server_Termux
    └── web/ data/ database/ server.jar ...
```

## Cách dùng

```bash
# 1. Lần đầu: cài môi trường chung
bash install.sh

# 2. Mở menu quản lý (chọn game để chạy)
bash menu.sh
# hoặc gõ: menu  (nếu đã chạy install.sh)

# 3. Chạy nhanh không qua menu
bash ninja_start.sh    # chỉ chạy Ninja
bash htth_start.sh     # chỉ chạy Hải Tặc Hot
bash stop.sh           # dừng tất cả game/web
```

## Port / DB

| Game | Game port | Web port | Database | Thư mục |
|------|-----------|----------|----------|---------|
| Ninja School | 14444 | 8000 | `schoolzz` | `ninja/server/` |
| Hai Tac Hot | 2236 | 8080 | `htth` | `htth/` |

Cả 2 có thể chạy **cùng lúc** (port khác nhau, DB khác nhau, chung 1 MariaDB).

## Ghi chú

- `start_db.sh` tự tạo DB `schoolzz` (import `ninja/server/exe_nsoz.sql`) và DB `htth` (import `htth/database/htth_full.sql`).
- Ninja client: `ninja/server/web/files/` (jar/apk/zip).
- HTTH client: `htth/client/` (apk/jar).
- Log server: `logs/ninja_server.log`, `logs/htth_server.log`, `logs/*_web.log`.
