<?php

define('NP', true);
require(__DIR__ . '/../../core/configs.php');

header('Content-Type: application/json');

$post = json_decode(file_get_contents('php://input'), true);

if (!isset($post['username']) || !isset($post['selectedCharacter']) || !isset($post['selectedPrice'])) {
    echo json_encode(["code" => "02", "text" => "Thiếu dữ liệu cần thiết"]);
    exit;
}

$username = $post['username'];
$selectedCharacter = $post['selectedCharacter'];
$selectedPrice = (int) $post['selectedPrice'];

$conn = SQL(); 

session_start();

try {
    $conn->autocommit(false);

    $stmt = $conn->prepare("SELECT p.id AS player_id, p.tanthu, p.giftcode_unpaid, u.balance FROM players p JOIN users u ON p.user_id = u.id WHERE u.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $player = $result->fetch_assoc();

    if (!$player) {
        echo json_encode(["code" => "03", "text" => "Không tìm thấy người chơi."]);
        exit;
    }

    if ($player['tanthu'] == 1) {
        echo json_encode(["code" => "04", "text" => "Mỗi Người Chỉ Được Mua 1 Gói Duy Nhất."]);
        exit;
    }

    if ($player['balance'] < $selectedPrice) {
        echo json_encode(["code" => "03", "text" => "Số dư không đủ để thực hiện giao dịch."]);
        exit;
    }

    if (!empty($player['giftcode_unpaid'])) {
        echo json_encode(["code" => "05", "text" => "Vào Game Để Nhận Phần Thưởng"]);
        exit;
    }

    $newBalance = $player['balance'] - $selectedPrice;
    $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE username = ?");
    $stmt->bind_param("ii", $newBalance, $username);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE players SET giftcode_unpaid = ?, tanthu = 1 WHERE id = ?");
    $stmt->bind_param("ii", $selectedPrice, $player['player_id']);
    $stmt->execute();

    $conn->commit();
    echo json_encode(["code" => "00", "text" => "Giao dịch thành công"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["code" => "01", "text" => $e->getMessage()]);
}
?>
