<?php
// Router cho PHP built-in server tren Termux (thay .htaccess cua Apache).
// Dung: php -S 0.0.0.0:8888 router.php  (chay trong web/htdocs)
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
// Giai ma URL de phuc vu file ten Unicode/khoang trang ("NRO_fixed.apk" ok, "Nro HanZi.jar" ok)
$path = rawurldecode($path);
$file = __DIR__ . $path;

if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // file tinh -> de server tu phuc vu
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
