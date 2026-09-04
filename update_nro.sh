#!/bin/bash
# ============================================================
#  update_nro.sh - CAP NHAT NGOC RONG ANWIN V3
#  - Keo code moi nhat (git pull)
#  - Nap DB awnv3 neu chua co (khong dung den DB cu)
#  - Giai nen tai nguyen game neu chua co
# ============================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; CYAN='\033[0;36m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

echo -e "${CYAN}====================================================${NC}"
echo -e "${YELLOW}   CAP NHAT GAME NGOC RONG ANWIN V3 (TERMUX)      ${NC}"
echo -e "${CYAN}====================================================${NC}"

# 1. Git pull (HIEN LOI THAT de de chan doan)
if ! command -v git >/dev/null 2>&1; then
    warn "Chua cai git. Chay: pkg install git"
elif [ ! -d "$DIR/.git" ]; then
    warn "Thu muc nay KHONG phai git clone (co the ban tai file .zip)."
    warn "Muon cap nhat bang git, hay clone moi: git clone https://github.com/longth68/Server_Termux_All.git"
else
    # File runtime (server tu ghi khi chay) lam pull that bai -> stash truoc, pop sau
    RUNTIME_FILES="nro/Server/data/virtualplayer_save.json nro/Server/virtualplayer_config.txt nro/Server/data/virtualplayer_chat.txt nro/Server/active_event.txt nro/Server/maintenanceConfig.txt nro/Server/data/config/data_base.properties"
    STASHED=0
    if [ -n "$(git -C "$DIR" status --short $RUNTIME_FILES 2>/dev/null)" ]; then
        info "Cat tam file runtime bi server sua doi de git pull..."
        git -C "$DIR" stash push -q -m "update-backup-runtime" -- $RUNTIME_FILES 2>/dev/null && STASHED=1
    fi
    info "Dong bo code moi nhat tu GitHub..."
    GIT_MSG=$(git -C "$DIR" pull --ff-only origin main 2>&1)
    if [ $? -ne 0 ]; then
        warn "Khong the git pull. Loi tu git:"
        echo "$GIT_MSG" | tail -n 8
        echo "--- File bi sua/lech (neu co) ---"
        git -C "$DIR" status --short | head -n 10
        echo "Goi y: co mang khong? file nao bi sua thi backup roi chay: git checkout -- <file>"
        echo "Tiep tuc voi file hien co..."
    else
        echo "$GIT_MSG" | tail -n 3
    fi
    if [ "$STASHED" -eq 1 ]; then
        git -C "$DIR" stash pop -q 2>/dev/null && info "Da khoi phuc file runtime." || warn "Khong pop duoc stash (xem: git stash list)"
    fi
fi

# 2. Quyen thuc thi
chmod +x "$DIR"/*.sh 2>/dev/null || true

# 3. MariaDB + DB awnv3
info "Khoi dong MariaDB va kiem tra Database..."
bash "$DIR/start_db.sh"

# 4. Kiem tra + tai bu part data (can file ANWIN_data.parts de biet tong so)
NRO="$DIR/nro"
if [ ! -d "$NRO" ]; then
    error "Thu muc $NRO khong ton tai!"
    exit 1
fi
EXPECT=$(cat "$NRO/ANWIN_data.parts" 2>/dev/null || echo "18")
HAVE=$(ls "$NRO"/ANWIN_data.tar.* 2>/dev/null | wc -l)
info "Part data NRO: co $HAVE/$EXPECT"
if [ "$HAVE" -lt "$EXPECT" ]; then
    warn "Thieu part data! Thu keo tiep bang git..."
    if [ -d "$DIR/.git" ]; then
        git -C "$DIR" pull --ff-only origin main 2>&1 | tail -n 3
        HAVE=$(ls "$NRO"/ANWIN_data.tar.* 2>/dev/null | wc -l)
        info "Sau git pull: co $HAVE/$EXPECT part"
    fi
fi
# Van thieu -> tai bu tung part qua curl (ho tro resume)
if [ "$HAVE" -lt "$EXPECT" ]; then
    warn "Van thieu part, tai bu truc tiep tu GitHub (co the tiep tuc neu dut mang)..."
    i=1
    while [ "$i" -le "$EXPECT" ]; do
        f=$(printf "ANWIN_data.tar.%03d" "$i")
        if [ ! -f "$NRO/$f" ]; then
            info "Dang tai $f ($i/$EXPECT)..."
            if ! curl -L -C - --retry 3 -o "$NRO/$f" "https://raw.githubusercontent.com/longth68/Server_Termux_All/main/nro/$f"; then
                error "Tai that bai: $f. Kiem tra mang roi chay lai script."
                exit 1
            fi
        fi
        i=$((i + 1))
    done
    HAVE=$(ls "$NRO"/ANWIN_data.tar.* 2>/dev/null | wc -l)
fi

# 5. Giai nen tai nguyen NRO neu chua co
if [ ! -d "$NRO/Server/data/map" ]; then
    if [ "$HAVE" -ge "$EXPECT" ] && [ "$EXPECT" -gt 0 ]; then
        # 5a. Kiem tra dung luong trong (can ~2.7GB: tar 1.3GB + giai nen 1.2GB)
        FREE_KB=$(df -k "$NRO" 2>/dev/null | awk 'NR==2{print $4}')
        if [ -n "$FREE_KB" ] && [ "$FREE_KB" -lt 2800000 ]; then
            error "Bo nho trong con $((FREE_KB/1024))MB < 2700MB. Giai phong bo nho roi chay lai!"
            exit 1
        fi
        # 5b. Kiem tra tong size 18 part (thieu/hong part nao se lech)
        TOTAL=0
        for p in "$NRO"/ANWIN_data.tar.*; do
            sz=$(stat -c%s "$p" 2>/dev/null || stat -f%z "$p" 2>/dev/null || echo 0)
            TOTAL=$((TOTAL + sz))
        done
        info "Tong size 18 part: $TOTAL byte (chuan: 1292327936)"
        if [ "$TOTAL" -ne 1292327936 ]; then
            error "Part data thieu/hong (size lech)! Xoa part loi va chay lai de tai bu:"
            ls -l "$NRO"/ANWIN_data.tar.*
            exit 1
        fi
        # 5c. Giai nen (tar chua NOI DUNG cua data/ -> bung vao data/, don rac lan sai truoc)
        info "Dang giai nen tai nguyen Ngoc Rong Anwin..."
        mkdir -p "$NRO/Server"
        cd "$NRO/Server"
        cat "$NRO"/ANWIN_data.tar.* > anwin_data.tar
        TOPS=$(tar -tf anwin_data.tar 2>/dev/null | cut -d/ -f1 | sort -u)
        if [ "$(echo "$TOPS" | wc -l)" -eq 1 ] && [ "$TOPS" = "data" ]; then
            tar -xf anwin_data.tar > extract.log 2>&1
        else
            for t in $TOPS; do
                case "$t" in ""|.|..|data|classes|lib|src|log|anwin_data.tar|extract.log) continue ;; esac
                t_clean=$(basename "$t")
                [ -n "$t_clean" ] && [ -e "$t_clean" ] && rm -rf -- "$t_clean"
            done
            mkdir -p data
            tar -xf anwin_data.tar -C data > extract.log 2>&1
        fi
        RC=$?
        cd "$DIR"
        if [ $RC -eq 0 ] && [ -d "$NRO/Server/data/map" ]; then
            rm -f "$NRO/Server/anwin_data.tar" "$NRO/Server/extract.log"
            info "Giai nen tai nguyen NRO thanh cong!"
        else
            error "Giai nen NRO that bai! Loi tar:"
            tail -n 10 "$NRO/Server/extract.log" 2>/dev/null
            df -h "$NRO" 2>/dev/null | tail -n 2
            exit 1
        fi
    else
        error "Chua du $EXPECT part data (moi co $HAVE). Khong giai nen."
        exit 1
    fi
else
    info "Tai nguyen NRO da duoc giai nen san."
fi

echo -e "\n${GREEN}====================================================${NC}"
echo -e "${GREEN}   CAP NHAT THANH CONG!                              ${NC}"
echo -e "${GREEN}====================================================${NC}"
echo -e "  - Ninja School:  game port ${YELLOW}14444${NC} | web port ${YELLOW}8000${NC} (DB: schoolzz)"
echo -e "  - Hai Tac Hot:   game port ${YELLOW}2236${NC}  | web port ${YELLOW}8080${NC} (DB: htth)"
echo -e "  - Ngoc Rong Anwin V3: game port ${YELLOW}14445${NC} | web port ${YELLOW}8888${NC} | API: ${YELLOW}8085${NC} (DB: awnv3)"
echo -e "  - Tai Client:    Truy cap ${YELLOW}http://127.0.0.1:8888/${NC} (APK + JAR nap san IP 127.0.0.1)"
echo -e "${CYAN}----------------------------------------------------${NC}"
echo -e "  Chay menu dieu khien:   ${CYAN}bash menu.sh${NC}"
echo -e "  Chay rieng Ngoc Rong:    ${CYAN}bash nro_start.sh${NC}"
echo -e "${GREEN}====================================================${NC}"
