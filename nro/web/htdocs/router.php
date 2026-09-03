<?php
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
// Giai ma URL để phục vụ file có tên Unicode/khoảng trắng (logo Ỷ....png, "Nro HanZi.apk")
$path = rawurldecode($path);
$file = __DIR__ . $path;

if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

$clean = trim($path, '/');
if (!empty($clean)) {
    if (file_exists(__DIR__ . '/' . $clean . '.php')) {
        include __DIR__ . '/' . $clean . '.php';
        exit;
    }
}

if ($path === '/' || $path === '') {
    include __DIR__ . '/index.php';
    exit;
}

if (file_exists(__DIR__ . '/index.php')) {
    include __DIR__ . '/index.php';
    exit;
}
return false;
