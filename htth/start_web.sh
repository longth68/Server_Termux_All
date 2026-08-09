#!/bin/bash
cd "$(dirname "$0")"

if ! command -v php >/dev/null 2>&1; then
    echo "Khong tim thay php."
    echo "Hay cai PHP bang lenh: pkg install php"
    exit 1
fi

echo "Khoi dong Web server tai http://127.0.0.1:8080 ..."
echo "Nhan Ctrl+C de dung."

if [ -f "web/router.php" ]; then
    php -S 127.0.0.1:8080 -t web web/router.php
else
    php -S 127.0.0.1:8080 -t web
fi
