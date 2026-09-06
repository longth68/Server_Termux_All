<?php

require(__DIR__ . '/../../core/configs.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || $_SESSION['user']['admin_web'] != 1) {
    echo json_encode(["code" => "01", "text" => "Không có quyền truy cập"]);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? max(1, min(200, intval($_GET['limit']))) : 80;

$conn = SQL();
$items = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    if (is_numeric($q)) {
        $id = intval($q);
        $stmt = $conn->prepare("SELECT `id`, `name`, `icon`, `level`, `type`, `gender` FROM `item` WHERE `id` = ? OR `name` LIKE ? ORDER BY `id` LIMIT ?");
        $stmt->bind_param("isi", $id, $like, $limit);
    } else {
        $stmt = $conn->prepare("SELECT `id`, `name`, `icon`, `level`, `type`, `gender` FROM `item` WHERE `name` LIKE ? ORDER BY `id` LIMIT ?");
        $stmt->bind_param("si", $like, $limit);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
    }
    $stmt->close();
} else {
    $res = $conn->query("SELECT `id`, `name`, `icon`, `level`, `type`, `gender` FROM `item` ORDER BY `id` LIMIT $limit");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
    }
}
$conn->close();

echo json_encode(["code" => "00", "items" => $items], JSON_UNESCAPED_UNICODE);
