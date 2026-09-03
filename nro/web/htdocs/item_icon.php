<?php
/**
 * Serve anh icon tu resources cua Server HASHIRAMA.
 * - item_icon.php?id={icon_id}&size={1..4}      : icon vat pham / part
 * - item_icon.php?id={item_template.id}&by=template : tra icon theo id item template
 * - item_icon.php?id={mob_template.id}&type=mob     : anh QUAI (monster)
 * - item_icon.php?id={mob_template.id}&type=mob&size={1..4}
 *
 * Nguon anh: {NRO-HASHIRAMA}/Server/resources/normal/image/{size}/icon|monster/{id}.png
 * fallback: {NRO-HASHIRAMA}/Server/data/icon/x{size}/{id}.png
 */
$type = isset($_GET['type']) ? preg_replace('/[^a-z]/', '', strtolower($_GET['type'])) : 'icon';
$id = isset($_GET['id']) ? (int)$_GET['id'] : -1;
if ($id < 0) $id = 0;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 1;
if ($size < 1 || $size > 4) $size = 1;

if ($type === 'icon' && isset($_GET['by']) && $_GET['by'] === 'template') {
    include_once __DIR__ . '/hidden/config.php';
    $cid = _fetch("SELECT icon_id FROM item_template WHERE id=" . (int)$id);
    if ($cid && isset($cid['icon_id'])) $id = (int)$cid['icon_id'];
}

$base = dirname(dirname(__DIR__)); // thu muc NRO-HASHIRAMA

$candidates = array();
switch ($type) {
    case 'mob':
        foreach (array(4, 3, 2, 1) as $s)
            $candidates[] = $base . '/Server/resources/normal/image/' . $s . '/monster/' . $id . '.png';
        foreach (array(1, 2, 3, 4) as $s)
            $candidates[] = $base . '/Server/resources/data/nro/mob/x' . $s . '/' . $id;
        break;
    case 'imgbyname':
        foreach (array($size, 1, 2, 3, 4) as $s)
            $candidates[] = $base . '/Server/resources/normal/image/' . $s . '/imgbyname/' . $id . '.png';
        break;
    default: // icon
        // Thu tu thu: size yeu cau -> 1 -> 2 -> 3 -> 4
        $order = array_values(array_unique(array_merge(array($size), array(1, 2, 3, 4))));
        foreach ($order as $s)
            $candidates[] = $base . '/Server/resources/normal/image/' . $s . '/icon/' . $id . '.png';
        foreach ($order as $s)
            $candidates[] = $base . '/Server/data/icon/x' . $s . '/' . $id . '.png';
}

foreach ($candidates as $f) {
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
