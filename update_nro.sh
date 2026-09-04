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
fi

# 2. Quyen thuc thi
chmod +x "$DIR"/*.sh 2>/dev/null || true

# 3. MariaDB + DB awnv3
info "Khoi dong MariaDB va kiem tra Database..."
bash "$DIR/start_db.sh"

# 4. Giai nen tai nguyen NRO neu chua co
NRO="$DIR/nro"
if [ -d "$NRO" ]; then
    if [ ! -d "$NRO/Server/data/map" ]; then
        if ls "$NRO"/ANWIN_data.tar.* >/dev/null 2>&1; then
            info "Dang giai nen tai nguyen Ngoc Rong Anwin..."
            mkdir -p "$NRO/Server"
            (cd "$NRO/Server" && cat "$NRO"/ANWIN_data.tar.* > anwin_data.tar \
                && tar -xf anwin_data.tar && rm -f anwin_data.tar)
            [ -d "$NRO/Server/data/map" ] && info "Giai nen tai nguyen NRO thanh cong!" || { error "Giai nen NRO that bai!"; exit 1; }
        else
            warn "Khong tim thay ANWIN_data.tar.* trong thu muc nro/!"
        fi
    else
        info "Tai nguyen NRO da duoc giai nen san."
    fi
else
    error "Thu muc $NRO khong ton tai!"
    exit 1
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
