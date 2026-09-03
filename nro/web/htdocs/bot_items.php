<?php
/**
 * Tra ve danh sach item_template (JSON) cho tab Bot.
 * Chi admin moi duoc goi.
 */
require_once __DIR__ . '/hidden/set.php';
if (empty($_user) || $user_arr["is_admin"] != 1) {
    die('{"status":"error","msg":"Khong co quyen"}');
}
header('Content-Type: application/json; charset=UTF-8');

$out = array();
$res = _query("SELECT id, NAME, icon_id, TYPE FROM item_template ORDER BY id");
while ($r = mysqli_fetch_assoc($res)) {
    $out[] = array(
        'id' => (int)$r['id'],
        'n'  => $r['NAME'],
        'c'  => (int)$r['icon_id'],
        't'  => (int)$r['TYPE']
    );
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
