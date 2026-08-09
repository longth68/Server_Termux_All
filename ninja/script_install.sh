#!/bin/bash
# ============================================================
#  NinjaServerTermux - Bootstrap Installer
#  Cài đặt server Ninja School Online chạy trên Termux (Android)
#  Không cần root
#
#  Cách dùng:  bash script_install.sh
#  hoặc từ README:  curl -L .../script_install.sh --output script_install.sh && bash script_install.sh
# ============================================================

clear
printf "\n === NinjaServerTermux ===\n"
printf " Sau khi cài đặt, server Ninja School Online sẽ chạy được trên Termux của bạn.\n"
printf " Các gói phần mềm cần thiết (openjdk, mariadb, php, curl...) sẽ được cài đặt\n"
printf " bằng lệnh 'pkg install' của Termux.\n\n"

printf " - Bạn có muốn tiếp tục? [Y/N]\n\n"
read -p " Lựa chọn: " yesorno

if [[ $yesorno == "Y" ]] || [[ $yesorno == "y" ]]; then
    printf "\n\n"

    # Cấp quyền truy cập bộ nhớ cho Termux (để lưu backup...)
    echo "Y" | termux-setup-storage &> /dev/null

    # ================= Architecture detection =================
    url="https://raw.githubusercontent.com/longth68/Server_Play_Termux/master"
    cpu="$(getprop ro.product.cpu.abi 2>/dev/null || uname -m)"

    case "$cpu" in
        arm64-v8a|aarch64|aarch64_be)
            arch="aarch64"
            ;;
        armeabi-v7a|armeabi|armv7l)
            arch="arm"
            ;;
        x86_64|amd64)
            arch="x64"
            ;;
        x86|i386|i686)
            printf "\n Không hổ trợ x86 - 32bit!\n\n"
            exit 0
            ;;
        *)
            printf "\n Không xác định được kiến trúc (%s)!\n\n" "$cpu"
            exit 0
            ;;
    esac

    clear
    printf "\nDownloading package (%s)....\n\n" "$arch"
    if ! curl -L --max-redirs 15 --progress-bar "${url}/install.sh" --output install.sh; then
        printf "\n Internet ERROR\n\n"
        exit 1
    fi
    chmod +x install.sh
    bash install.sh
else
    printf "\n Đã hủy cài đặt.\n\n"
fi
