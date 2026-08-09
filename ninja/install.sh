#!/bin/bash
# ============================================================
#  NinjaServerTermux - Main Installer v2.0
#  Cai dat server Ninja School Online chay tren Termux (Android)
#  Khong can root
#
#  Yeu cau:
#   - Termux (tu F-Droid, version 0.118 tro len)
#   - Da chay: pkg update && pkg upgrade
# ============================================================

set -eE
trap 'echo -e "\033[0;31m[ERROR]\033[0m Script thoat dot ngot tai dong $LINENO" >&2' ERR

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NINJA_DIR="$HOME/ninja-server"
SRC_URL="https://raw.githubusercontent.com/longth68/Server_Play_Termux/master"
DB_NAME="schoolzz"
DB_USER="root"
DB_PASS=""

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
    # Tat trap ERR tam thoi de tranh double message
    trap - ERR
    exit 1
}

check_environment() {
    if [ -z "$PREFIX" ] || [ ! -d "$PREFIX/bin" ]; then
        error "Script nay chi chay duoc tren Termux! (PREFIX='${PREFIX:-<rong>}')"
    fi
    info "Moi truong Termux OK: $PREFIX"
    mkdir -p "$PREFIX/tmp" "$PREFIX/var/lib/mysql" \
             "$PREFIX/var/run/mysqld" "$PREFIX/var/log/mysql" \
             "$NINJA_DIR/logs" "$NINJA_DIR/.pids"
}

install_packages() {
    info "Cap nhat danh sach goi..."
    pkg update -y 2>/dev/null || true
    # Java 21 (server JAR build voi Java 21)
    local pkgs="openjdk-21 mariadb php curl unzip zip wget procps psmisc"
    info "Cai dat: $pkgs"
    pkg install -y $pkgs || error "Cai dat goi that bai. Kiem tra ket noi mang."
    command -v java >/dev/null 2>&1 || error "openjdk chua duoc cai dat!"
    command -v mysqld_safe >/dev/null 2>&1 \
        || command -v mariadbd-safe >/dev/null 2>&1 \
        || command -v mariadbd >/dev/null 2>&1 \
        || error "mariadb chua duoc cai dat!"
    command -v php >/dev/null 2>&1 || error "php chua duoc cai dat!"
    # Kiem tra phien ban Java >= 21
    local jver
    jver=$(java -version 2>&1 | head -n1 | grep -oP '(\d+)' | head -n1)
    if [ -n "$jver" ] && [ "$jver" -lt 21 ] 2>/dev/null; then
        warn "Java hien tai la phien ban $jver. Server yeu cau Java 21+."
        warn "Chay: pkg install openjdk-21"
    else
        info "Java OK: $(java -version 2>&1 | head -n1)"
    fi
}

setup_mycnf() {
    local cnf="$PREFIX/etc/mysql/my.cnf"
    mkdir -p "$(dirname "$cnf")"
    if ! grep -q "NinjaServerTermux" "$cnf" 2>/dev/null; then
        # Dung EOF khong co nháy don de $PREFIX duoc expand
        cat > "$cnf" <<EOF
# NinjaServerTermux - my.cnf for Termux
[mysqld]
datadir         = $PREFIX/var/lib/mysql
socket          = $PREFIX/tmp/mysqld.sock
pid-file        = $PREFIX/var/run/mysqld/mysqld.pid
log-error       = $PREFIX/var/log/mysql/mariadbd.err
bind-address    = 127.0.0.1
character-set-server = utf8mb4
collation-server     = utf8mb4_unicode_ci
skip-name-resolve

[client]
socket = $PREFIX/tmp/mysqld.sock

[mysqladmin]
socket = $PREFIX/tmp/mysqld.sock

[mysqldump]
socket = $PREFIX/tmp/mysqld.sock
EOF
        info "Da tao my.cnf: $cnf"
    fi
}

setup_mariadb() {
    info "Khoi tao MariaDB..."
    setup_mycnf

    if [ -z "$(ls -A "$PREFIX/var/lib/mysql" 2>/dev/null)" ]; then
        info "Dang khoi tao datadir..."
        if command -v mariadb-install-db >/dev/null 2>&1; then
            { mariadb-install-db \
                --datadir="$PREFIX/var/lib/mysql" \
                --auth-root-authentication-method=normal \
                --skip-test-db || true; } 2>&1 | tail -8
        elif command -v mysql_install_db >/dev/null 2>&1; then
            { mysql_install_db \
                --datadir="$PREFIX/var/lib/mysql" \
                --auth-root-authentication-method=normal || true; } 2>&1 | tail -8
        else
            warn "Khong tim thay lenh khoi tao DB."
        fi
    else
        info "Datadir da ton tai."
    fi

    rm -f "$PREFIX/tmp/mysqld.sock" \
          "$PREFIX/tmp/mysqld.sock.lock" \
          "$PREFIX/var/lib/mysql/mysql.sock" \
          "$PREFIX/var/run/mysqld/mysqld.pid" 2>/dev/null || true
    chmod 755 "$PREFIX/var/lib/mysql" "$PREFIX/var/run/mysqld" 2>/dev/null || true

    if mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null; then
        info "MariaDB da dang chay."
    else
        info "Khoi dong MariaDB..."
        if command -v mariadbd-safe >/dev/null 2>&1; then
            mariadbd-safe --no-defaults \
                --datadir="$PREFIX/var/lib/mysql" \
                --socket="$PREFIX/tmp/mysqld.sock" \
                --pid-file="$PREFIX/var/run/mysqld/mysqld.pid" \
                --log-error="$PREFIX/var/log/mysql/mariadbd.err" \
                >/dev/null 2>&1 &
        elif command -v mysqld_safe >/dev/null 2>&1; then
            mysqld_safe --no-defaults \
                --datadir="$PREFIX/var/lib/mysql" \
                --socket="$PREFIX/tmp/mysqld.sock" \
                --pid-file="$PREFIX/var/run/mysqld/mysqld.pid" \
                --log-error="$PREFIX/var/log/mysql/mariadbd.err" \
                >/dev/null 2>&1 &
        elif command -v mariadbd >/dev/null 2>&1; then
            mariadbd --no-defaults \
                --datadir="$PREFIX/var/lib/mysql" \
                --socket="$PREFIX/tmp/mysqld.sock" \
                --pid-file="$PREFIX/var/run/mysqld/mysqld.pid" \
                --log-error="$PREFIX/var/log/mysql/mariadbd.err" \
                >/dev/null 2>&1 &
        else
            warn "Khong tim thay mysqld/mariadbd. Cai lai: pkg install mariadb"
            return
        fi

        info "Doi MariaDB khoi dong (toi da 60s)..."
        local started=0
        for i in $(seq 1 60); do
            if mysqladmin ping --silent --socket="$PREFIX/tmp/mysqld.sock" 2>/dev/null; then
                started=1; break
            fi
            sleep 1
        done

        if [ "$started" -eq 1 ]; then
            info "MariaDB san sang sau ${i}s."
        else
            warn "MariaDB chua san sang sau 60s."
            warn "Xem log: cat $PREFIX/var/log/mysql/mariadbd.err"
        fi
    fi

    mysql --socket="$PREFIX/tmp/mysqld.sock" -u root \
        -e "FLUSH PRIVILEGES;" 2>/dev/null || true
}

import_database() {
    local sock_opt="--socket=$PREFIX/tmp/mysqld.sock"
    if ! mysqladmin ping --silent $sock_opt 2>/dev/null; then
        warn "MariaDB khong chay - bo qua import database."
        return
    fi

    info "Tao database '$DB_NAME'..."
    if ! mysql $sock_opt -u root \
        -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
        2>/dev/null; then
        warn "Khong tao duoc database. Co the MariaDB chua san sang."
        return
    fi

    local sql_file="$NINJA_DIR/exe_nsoz.sql"
    if [ ! -f "$sql_file" ]; then
        warn "Khong tim thay $sql_file - bo qua import."
        return
    fi

    local has_table
    local count
    count=0
    has_table=$(mysql $sock_opt -u root -D "$DB_NAME" -N \
        -e "SHOW TABLES LIKE 'players';" 2>/dev/null | head -n1) || true
    if [ -n "$has_table" ]; then
        count=$(mysql $sock_opt -u root -D "$DB_NAME" -N \
            -e "SELECT COUNT(*) FROM players;" 2>/dev/null) || true
        if [ -n "$count" ] && [ "$count" -gt 0 ] 2>/dev/null; then
            warn "Database da co $count player - bo qua import."
            return
        fi
    fi

    info "Import du lieu tu exe_nsoz.sql (co the mat vai phut)..."
    if mysql $sock_opt -u root --default-character-set=utf8mb4 "$DB_NAME" < "$sql_file"; then
        info "Import hoan tat."
    else
        warn "Import gap loi. Import thu cong bang: mysql -u root $DB_NAME < $sql_file"
    fi
}

deploy_server() {
    mkdir -p "$NINJA_DIR"
    info "Dong bo du lieu server vao $NINJA_DIR ..."

    local items=(Data item_roi web config.properties exe_nsoz.sql)
    local SRC_ROOT=""

    for cand_root in "$SCRIPT_DIR" "$SCRIPT_DIR/server" "$PWD" "$PWD/server"; do
        if [ -d "$cand_root/Data" ] && [ -f "$cand_root/config.properties" ]; then
            SRC_ROOT="$cand_root"; break
        fi
    done

    local TEMP_DIR=""
    if [ -z "$SRC_ROOT" ]; then
        info "Khong tim thay du lieu cuc bo. Tai tu GitHub (~420MB)..."
        TEMP_DIR="$HOME/temp_nso_$$"
        mkdir -p "$TEMP_DIR"

        if ! curl -L --progress-bar \
                -o "$TEMP_DIR/Server_Play_Termux.tar.gz" \
                "https://github.com/longth68/Server_Play_Termux/releases/download/v1.1/Server_Play_Termux.tar.gz"; then
            rm -rf "$TEMP_DIR"
            error "Loi khi tai Server_Play_Termux.tar.gz!"
        fi

        info "Dang giai nen..."
        if ! tar -xzf "$TEMP_DIR/Server_Play_Termux.tar.gz" -C "$TEMP_DIR"; then
            rm -rf "$TEMP_DIR"; error "Giai nen that bai!"
        fi

        for cand_root in \
            "$TEMP_DIR" "$TEMP_DIR/Server_Play_Termux" \
            "$TEMP_DIR/server" "$TEMP_DIR/Server_Play_Termux/server"; do
            if [ -d "$cand_root/Data" ] && [ -f "$cand_root/config.properties" ]; then
                SRC_ROOT="$cand_root"; break
            fi
        done

        if [ -z "$SRC_ROOT" ]; then
            rm -rf "$TEMP_DIR"
            error "Khong tim thay Data/ va config.properties sau khi giai nen!"
        fi
    fi

    info "Nguon du lieu: $SRC_ROOT"
    for it in "${items[@]}"; do
        if [ -e "$SRC_ROOT/$it" ] && [ ! -e "$NINJA_DIR/$it" ]; then
            cp -rf "$SRC_ROOT/$it" "$NINJA_DIR/" 2>/dev/null || true
            info "  + $it"
        fi
    done

    if [ ! -f "$NINJA_DIR/Nso-jar-with-dependencies.jar" ]; then
        for cand in \
            "$SRC_ROOT/Nso-jar-with-dependencies.jar" \
            "$SRC_ROOT/target/Nso-jar-with-dependencies.jar" \
            "$SRC_ROOT/app.jar" \
            "$SCRIPT_DIR/server/app.jar" \
            "$SCRIPT_DIR/app.jar"; do
            if [ -f "$cand" ]; then
                cp "$cand" "$NINJA_DIR/Nso-jar-with-dependencies.jar"
                info "Da copy jar: $(basename "$cand")"
                break
            fi
        done
    fi

    if [ -n "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR" 2>/dev/null || true
    fi

    if [ ! -f "$NINJA_DIR/Nso-jar-with-dependencies.jar" ]; then
        warn "Chua co server jar. Dua vao: $NINJA_DIR/"
    else
        info "Server jar OK."
    fi

    if [ -f "$NINJA_DIR/config.properties" ]; then
        sed -i "s/^db\.host=.*/db.host=127.0.0.1/"       "$NINJA_DIR/config.properties" 2>/dev/null || true
        sed -i "s/^db\.port=.*/db.port=3306/"             "$NINJA_DIR/config.properties" 2>/dev/null || true
        sed -i "s/^db\.user=.*/db.user=$DB_USER/"         "$NINJA_DIR/config.properties" 2>/dev/null || true
        sed -i "s/^db\.password=.*/db.password=$DB_PASS/" "$NINJA_DIR/config.properties" 2>/dev/null || true
        sed -i "s/^db\.dbname=.*/db.dbname=$DB_NAME/"     "$NINJA_DIR/config.properties" 2>/dev/null || true
        info "Da cap nhat config.properties"
    fi
}

install_menu() {
    info "Cai dat menu dieu khien..."

    # --- Tim va copy menu.sh ---
    local found_menu=0

    # Uu tien: neu menu.sh da co san trong NINJA_DIR (deploy_server da copy)
    if [ -f "$NINJA_DIR/menu.sh" ] && head -n1 "$NINJA_DIR/menu.sh" | grep -q '^#!'; then
        chmod +x "$NINJA_DIR/menu.sh"
        info "menu.sh da co san trong $NINJA_DIR."
        found_menu=1
    fi

    if [ "$found_menu" -eq 0 ]; then
        for cand in \
            "$SCRIPT_DIR/menu.sh" \
            "$PWD/menu.sh" \
            "$SCRIPT_DIR/../menu.sh"; do
            if [ -f "$cand" ] && head -n1 "$cand" | grep -q '^#!'; then
                cp -f "$cand" "$NINJA_DIR/menu.sh"
                chmod +x "$NINJA_DIR/menu.sh"
                info "Da copy menu.sh tu: $cand"
                found_menu=1
                break
            fi
        done
    fi

    if [ "$found_menu" -eq 0 ]; then
        warn "Khong tim thay menu.sh ben canh install.sh."
        info "Thu tai menu.sh tu mang..."
        if curl -fsSL --max-redirs 10 --connect-timeout 15 \
                "${SRC_URL}/menu.sh" -o "$NINJA_DIR/menu.sh" 2>/dev/null; then
            chmod +x "$NINJA_DIR/menu.sh"
            info "Tai menu.sh thanh cong."
            found_menu=1
        else
            warn "Khong tai duoc menu.sh - khong co mang hoac URL sai."
        fi
    fi

    if [ "$found_menu" -eq 0 ] || [ ! -f "$NINJA_DIR/menu.sh" ]; then
        warn "CANH BAO: menu.sh chua duoc cai dat."
        warn "Sau khi co internet, chay: curl -fsSL ${SRC_URL}/menu.sh -o $NINJA_DIR/menu.sh && chmod +x $NINJA_DIR/menu.sh"
        return
    fi

    # Xac nhan file hop le (co shebang)
    if ! head -n1 "$NINJA_DIR/menu.sh" | grep -q '^#!'; then
        warn "menu.sh bi loi (khong co shebang). Xoa va thu lai."
        rm -f "$NINJA_DIR/menu.sh"
        return
    fi

    # --- Ghi vao shell profile de tu dong mo menu va tao alias ---
    local ALIAS_LINE='alias menu="bash $HOME/ninja-server/menu.sh"'
    local MENU_LINE='if [ -f "$HOME/ninja-server/menu.sh" ]; then bash "$HOME/ninja-server/menu.sh"; fi'
    local MARKER='# NinjaServerTermux-menu-autostart'

    for rc_file in \
        "$HOME/.bashrc" \
        "$HOME/.bash_profile" \
        "$HOME/.profile"; do
        # Tao file neu chua co
        touch "$rc_file" 2>/dev/null || continue
        # Chi them neu chua co
        if ! grep -qF "NinjaServerTermux-menu-autostart" "$rc_file" 2>/dev/null; then
            printf '\n%s\n%s\n%s\n' "$MARKER" "$ALIAS_LINE" "$MENU_LINE" >> "$rc_file"
            info "Da them autostart va alias vao: $rc_file"
        else
            info "$rc_file da co autostart."
        fi
    done

    # Termux: ghi them vao bash.bashrc he thong (fallback)
    if test -w "$PREFIX/etc/bash.bashrc"; then
        if ! grep -qF "NinjaServerTermux-menu-autostart" "$PREFIX/etc/bash.bashrc" 2>/dev/null; then
            printf '\n%s\n%s\n%s\n' "$MARKER" "$ALIAS_LINE" "$MENU_LINE" >> "$PREFIX/etc/bash.bashrc"
            info "Da them autostart va alias vao: $PREFIX/etc/bash.bashrc"
        fi
    fi

    info "Cai menu hoan tat! Mo lai Termux de thu."
    info "Chay ngay: bash $NINJA_DIR/menu.sh"
}

# ------------------------------------------------------------
# MAIN
# ------------------------------------------------------------
clear
echo -e "${CYAN}"
echo "=============================================="
echo "       NinjaServerTermux - Installer v2.0"
echo "   Ninja School Online Server on Termux"
echo "=============================================="
echo -e "${NC}"
sleep 1

check_environment
install_packages
setup_mariadb
deploy_server
import_database
install_menu

echo -e "${GREEN}"
echo "=============================================="
echo "  Cai dat hoan tat!"
echo "  Server nam tai: $NINJA_DIR"
echo "  Khoi dong lai Termux de mo menu,"
echo "  hoac chay: bash $NINJA_DIR/menu.sh"
echo "=============================================="
echo -e "${NC}"