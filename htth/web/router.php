<?php
// PHP built-in server router for Termux (emulating .htaccess)

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Serve existing static files directly
if (file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    return false; // let the built-in server handle it
}

// .htaccess hardcoded routes
$routes = [
    '/Home' => '/Index.php',
    '/Auth/Login' => '/Public/Auth/Login.php',
    '/Auth/Register' => '/Public/Auth/Register.php',
    '/Auth/Logout' => '/Public/Auth/Logout.php',
    '/Auth/Forgot-Password' => '/Public/Auth/Forgot-Password.php',
    '/Users/Profile' => '/Public/Users/Profile.php',
    '/Users/Payment' => '/Public/Users/Payment.php',
    '/Users/Member' => '/Public/Users/Member.php',
    '/Users/Gold' => '/Public/Users/Gold.php',
    '/Users/ChangePassword' => '/Public/Users/ChangePassword.php',
    '/Users/Downloads' => '/Public/Other/Downloads.php',
    '/Users/Forum' => '/Public/Other/Forum.php',
    '/Users/Post' => '/Public/Other/Post.php',
    '/Users/Info' => '/Public/Other/Info.php',
    '/Users/Rankings' => '/Public/Other/Rankings.php',
    '/Api/Login' => '/Api/Auth/Login.php',
    '/Api/Register' => '/Api/Auth/Register.php',
    '/Api/Post' => '/Api/Users/Forum.php',
    '/Api/Comments' => '/Api/Users/Comments.php',
    '/Api/LoadPost' => '/Api/Users/LoadPost.php',
    '/Api/Active' => '/Api/Users/Active.php',
    '/Api/Password' => '/Api/Users/Password.php',
    '/Api/Callback' => '/Api/Users/Callback.php',
    '/Api/CronAcb' => '/Api/Bank/CronAcb.php',
    '/Api/Card' => '/Api/Users/Card.php'
];

// Helper function to include file with correct working directory
function route_to($file_path) {
    $full_path = __DIR__ . $file_path;
    chdir(dirname($full_path));
    $_SERVER['SCRIPT_NAME'] = $file_path;
    include $full_path;
    exit;
}

// Check specific routes
if (array_key_exists($path, $routes)) {
    route_to($routes[$path]);
}

// Download redirection
if (strpos($path, '/Downloads/') === 0) {
    $new_path = '/Public' . $path;
    if (file_exists(__DIR__ . $new_path)) {
        return false;
    }
}

// Auto append .php (e.g. /Admin -> /Admin.php) if not directory
$cleanPath = trim($path, '/');
if (!empty($cleanPath) && !is_dir(__DIR__ . '/' . $cleanPath) && file_exists(__DIR__ . '/' . $cleanPath . '.php')) {
    route_to('/' . $cleanPath . '.php');
}

// Default index
if ($path === '/' || $path === '') {
    route_to('/Index.php');
}

// 404 fallback
route_to('/Index.php');
