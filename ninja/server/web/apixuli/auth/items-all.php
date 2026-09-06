<?php

define('NP', true);
require(__DIR__ . '/../../core/configs.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=600');
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['user']['admin_web'] ?? 0) != 1) {
    echo json_encode(["status" => "error", "msg" => "Không có quyền truy cập"]);
    exit;
}

$conn = SQL();
$out = [];
$res = $conn->query("SELECT `id`, `name`, `icon`, `type`, `level`, `isUpToUp` FROM `item` ORDER BY `id`");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'id' => (int)$row['id'],
            'n' => $row['name'],
            'ic' => (int)$row['icon'],
            't' => (int)$row['type'],
            'lv' => (int)$row['level'],
            'stk' => (int)$row['isUpToUp'],
        ];
    }
}
$conn->close();

echo json_encode(["status" => "ok", "items" => $out], JSON_UNESCAPED_UNICODE);
