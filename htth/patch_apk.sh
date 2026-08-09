#!/bin/bash
# Tool tự động cấu hình (đổi IP) cho file APK client Hải Tặc Hot
APK_FILE=$1
OLD_IP=$2
NEW_IP=$3

if [ -z "$3" ]; then
    echo "Sử dụng: ./patch_apk.sh <tên_file.apk> <ip_cũ> <ip_mới>"
    echo "Ví dụ: ./patch_apk.sh client/HaiTacHot_localhost.apk 54.255.184.239 127.0.0.1"
    exit 1
fi

if [ ! -f "$APK_FILE" ]; then
    echo "Lỗi: Không tìm thấy file $APK_FILE!"
    exit 1
fi

echo "1. Đang kiểm tra và cài đặt công cụ cần thiết (apktool, apksigner)..."
# Termux commands to install requirements
if ! command -v apktool &> /dev/null; then
    pkg install -y apktool
fi
if ! command -v apksigner &> /dev/null; then
    pkg install -y apksigner
fi
if ! command -v keytool &> /dev/null; then
    pkg install -y openjdk-17
fi

echo "2. Đang bung file (Decompile) $APK_FILE..."
rm -rf temp_patch_apk
apktool d "$APK_FILE" -o temp_patch_apk

echo "3. Đang tự động quét và thay đổi IP từ $OLD_IP sang $NEW_IP..."
# Thay IP trong mã nguồn DEX (đã dịch ra smali)
if [ -d "temp_patch_apk/smali" ]; then
    find temp_patch_apk/smali* -type f -name "*.smali" -exec sed -i "s/$OLD_IP/$NEW_IP/g" {} +
fi
# Thay IP trong file Text Data nếu có
if [ -d "temp_patch_apk/assets" ]; then
    find temp_patch_apk/assets -type f -exec sed -i "s/$OLD_IP/$NEW_IP/g" {} +
fi

echo "4. Đang đóng gói lại file APK..."
apktool b temp_patch_apk -o "mod_$APK_FILE"

echo "5. Đang ký (Sign) file APK để có thể cài đặt..."
if [ ! -f debug.keystore ]; then
    keytool -genkey -v -keystore debug.keystore -storepass android -alias androiddebugkey -keypass android -keyalg RSA -keysize 2048 -validity 10000 -dname "CN=Android Debug,O=Android,C=US"
fi
apksigner sign --ks debug.keystore --ks-pass pass:android "mod_$APK_FILE"

echo "6. Dọn dẹp rác..."
rm -rf temp_patch_apk

echo "HOÀN TẤT! File game mới của bạn là: mod_$APK_FILE"
