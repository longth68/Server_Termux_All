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

$status = null;
$res = $conn->query("SELECT * FROM `server_status` WHERE `id` = 1 LIMIT 1");
if ($res) {
    $status = $res->fetch_assoc();
}

// Parse bot_diag: "enabled=true,pop=20,count=5,maps=177,zones=149"
$diag = [];
if ($status && !empty($status['bot_diag'])) {
    foreach (explode(',', $status['bot_diag']) as $kv) {
        $p = explode('=', $kv, 2);
        if (count($p) == 2) {
            $diag[trim($p[0])] = trim($p[1]);
        }
    }
}

$list = [];
$res = $conn->query("SELECT `name`, `level`, `map_id`, `zone_id`, `x`, `y`, `hp`, `max_hp`, `state`, `personality`, `top_need`, `gold`, `gender`, `class_id`, `goal`, `damage`, `friends`, `online_min` FROM `bot_status` ORDER BY `level` DESC, `name` ASC LIMIT 200");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $list[] = $row;
    }
}
$conn->close();

echo json_encode([
    "status" => "ok",
    "online" => intval($status['online'] ?? 0),
    "bots" => intval($status['bots'] ?? 0),
    "pop" => intval($diag['pop'] ?? 0),
    "maps" => intval($diag['maps'] ?? 0),
    "zones" => intval($diag['zones'] ?? 0),
    "enabled" => ($diag['enabled'] ?? 'false'),
    "bot_diag" => $status['bot_diag'] ?? '',
    "updated_at" => $status['updated_at'] ?? '',
    "list" => $list
], JSON_UNESCAPED_UNICODE);
