<?php

define('NP', true);
require(__DIR__ . '/../../core/configs.php');

header('Content-Type: application/json');

$post = json_decode(file_get_contents('php://input'), true);

session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    echo json_encode(["code" => "02", "text" => "Vui lòng đăng nhập để sử dụng chức năng nạp thẻ."]);
    exit;
}

$network = "DIRECT";
$serial = "DIR" . time() . rand(100, 999);
$pin = "DIR";
$amount = isset($post['amount']) ? intval($post['amount']) : 0;

$allowed = [];
foreach ($list_recharge_price_atm as $item) {
    $allowed[] = intval($item['amount']);
}
if (!in_array($amount, $allowed)) {
    echo json_encode(["code" => "03", "text" => "Mệnh giá không hợp lệ."]);
    exit;
}

$conn = SQL();
$userId = (int) $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];

$stmt = $conn->prepare("SELECT `id` FROM `nap_the` WHERE `serial` = ? AND `pin` = ? AND `status` IN (0, 1) LIMIT 1");
$stmt->bind_param("ss", $serial, $pin);
$stmt->execute();
$dup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($dup) {
    echo json_encode(["code" => "04", "text" => "Thẻ này đã được nạp hoặc đang chờ duyệt."]);
    $conn->close();
    exit;
}

$stmt = $conn->prepare("INSERT INTO `nap_the` (`user_id`, `username`, `network`, `serial`, `pin`, `amount`, `status`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), NOW())");
$stmt->bind_param("issssi", $userId, $username, $network, $serial, $pin, $amount);
if ($stmt->execute()) {
    echo json_encode(["code" => "00", "text" => "Đã gửi yêu cầu nạp thẻ. Vui lòng chờ Admin kiểm tra và duyệt."]);
} else {
    echo json_encode(["code" => "01", "text" => "Lỗi hệ thống, vui lòng thử lại sau."]);
}
$stmt->close();
$conn->close();
