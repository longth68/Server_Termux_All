<?php
$content = file_get_contents('admin.php');

if (preg_match('/die\("([^"]+)"\);/', $content, $m)) {
    echo "Original: " . $m[1] . "\n";
    $fixed = mb_convert_encoding($m[1], 'cp1252', 'utf-8');
    echo "Fixed: " . $fixed . "\n";
}
