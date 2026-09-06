<?php

define('NP', true);
require(__DIR__ . '/../../core/configs.php');

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['user']['admin_web'] ?? 0) != 1) {
    echo json_encode(["status" => "error", "msg" => "Không có quyền truy cập"]);
    exit;
}

$conn = SQL();
$list = [];
$res = $conn->query("SELECT `boss_id`, `bkey`, `mob_name`, `map_id`, `map_name`, `zone_id`, `hp`, `max_hp`, `alive`, `updated_at` FROM `boss_status` ORDER BY `bkey` ASC, `boss_id` ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $list[] = $row;
    }
}
$conn->close();

echo json_encode(["status" => "ok", "bosses" => $list], JSON_UNESCAPED_UNICODE);
