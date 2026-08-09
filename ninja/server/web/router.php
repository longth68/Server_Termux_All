<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false; // let the built-in server handle it
}

// API Routes
if (strpos($uri, '/apixuli/') === 0) {
    $base = basename($uri);
    
    // Explicit overrides
    if ($uri === '/apixuli/charge-card') {
        require __DIR__ . '/apixuli/charging/nap_the.php';
        return;
    }
    
    // Auto-map to auth or charging
    if (file_exists(__DIR__ . '/apixuli/auth/' . $base . '.php')) {
        require __DIR__ . '/apixuli/auth/' . $base . '.php';
        return;
    }
    if (file_exists(__DIR__ . '/apixuli/charging/' . $base . '.php')) {
        require __DIR__ . '/apixuli/charging/' . $base . '.php';
        return;
    }
}

// Page Routes (Simulate URL Rewriting)
$parts = explode('/', trim($uri, '/'));
if (count($parts) > 0 && $parts[0] !== '' && $parts[0] !== 'index.php') {
    $_GET['page'] = $parts[0];
    if (count($parts) > 1) {
        if ($parts[0] === 'post') {
            $_GET['id'] = $parts[1];
        } else {
            $_GET['tab'] = $parts[1];
        }
    }
} else if (!isset($_GET['page'])) {
    $_GET['page'] = 'home';
}

require __DIR__ . '/index.php';
