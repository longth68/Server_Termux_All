<?php
/**
 * Serve anh icon vat pham tu thu muc data cua Server.
 * Dung: item_icon.php?id={icon_id}&size={1..4}  (size mac dinh = 1, size lon hon = anh Part day du)
 * icon_id lay tu cot icon_id bang item_template; size tuong ung voi data/icon/x{size}/{id}.png
 */
$id = isset($_GET['id']) ? (int)$_GET['id'] : -1;
if ($id < 0) $id = 0;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 1;
if ($size < 1 || $size > 4) $size = 1;

// Neu by=template: id la item_template.id -> tra icon_id tu DB
if (isset($_GET['by']) && $_GET['by'] === 'template') {
    include_once __DIR__ . '/hidden/config.php';
    $cid = _fetch("SELECT icon_id FROM item_template WHERE id=" . (int)$id);
    if ($cid && isset($cid['icon_id'])) $id = (int)$cid['icon_id'];
}

$base = dirname(dirname(__DIR__)); // .../NRO-LOCAL/Server/data/icon

// Thu tu thu: size yeu cau -> x1 -> x2 -> x3 -> x4
$order = array_values(array_unique(array_merge(array($size), array(1, 2, 3, 4))));
foreach ($order as $s) {
    $f = $base . '/Server/data/icon/x' . $s . '/' . $id . '.png';
    if (is_file($f)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($f);
        exit;
    }
}

// Khong tim thay -> tra ve anh 1x1 trong suot
header('Content-Type: image/png');
echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
