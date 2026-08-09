<?php

define('NP', true);
require(__DIR__ . '/../../core/configs.php');

header('Content-Type: application/json');

$post = json_decode(file_get_contents('php://input'), true);
$conn = SQL();

session_start();

try {
    if (!isset($_SESSION['user']['username'])) {
        error_log("Lỗi: Không có thông tin đăng nhập.");
        echo json_encode(['code' => '01', 'text' => 'Thông tin tài khoản hoặc mật khẩu không chính xác.']);
        exit;
    }

    if (!isset($post['pcoin']) || !is_numeric($post['pcoin']) || intval($post['pcoin']) <= 0) {
        error_log("Lỗi: Giá trị PCoin không hợp lệ - " . json_encode($post));
        echo json_encode(["code" => "01", "text" => "Invalid PCoin value."]);
        exit;
    }

    $pCoin = intval($post['pcoin']);
    $username = $_SESSION['user']['username'];

    error_log("User: $username đang đổi $pCoin PCoin");

    $conn->begin_transaction();

    $sqlUs = 'SELECT id, balance, amount_unpaid FROM users WHERE username = ? LIMIT 1';
    $stmt = $conn->prepare($sqlUs);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultUs = $stmt->get_result();
    $userDB = $resultUs->fetch_assoc();
    $stmt->close();

    if (!$userDB) {
        throw new Exception("Không tìm thấy dữ liệu user.");
    }

    error_log("UserID: {$userDB['id']}, Balance: {$userDB['balance']}, Lượng: {$userDB['amount_unpaid']}");

    $balanceBefore = $userDB['balance'];
    $luongBefore = $userDB['amount_unpaid'];
    $userId = $userDB['id'];

    if ($balanceBefore < $pCoin) {
        throw new Exception("Không đủ PCoin.");
    }

    $luongAfter = $luongBefore;
    $luongChange = 0;

    error_log("Tìm tỷ lệ đổi trong cấu hình...");

    foreach ($configDoiLuong as $item) {
        if ($item['pCoin'] == $pCoin) {
            $bonus = isset($bonusDoiLuong['bonus']) ? $bonusDoiLuong['bonus'] : 0;
            $luongChange = $item['luong'] + ($item['luong'] * $bonus / 100);
            $luongAfter += $luongChange;
            break;
        }
    }

    if ($luongChange === 0) {
        throw new Exception("Không tìm thấy tỷ lệ đổi PCoin.");
    }

    error_log("Sẽ nhận: $luongChange lượng, Tổng sau đổi: $luongAfter");

    $balanceAfter = $balanceBefore - $pCoin;

    $sqlUpdate = 'UPDATE users SET balance = ? WHERE username = ?';
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("is", $balanceAfter, $username);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi cập nhật số dư tài khoản.");
    }
    $stmt->close();

    $sqlUpdate1 = 'UPDATE users SET amount_unpaid = ? WHERE username = ?';
    $stmt1 = $conn->prepare($sqlUpdate1);
    $stmt1->bind_param("is", $luongAfter, $username);
    if (!$stmt1->execute()) {
        throw new Exception("Lỗi cập nhật lượng.");
    }
    $stmt1->close();

    $conn->commit();

    error_log("Đổi PCoin thành công!");

    echo json_encode(["code" => "00", "text" => "Bạn đã đổi PCoin thành công."]);

} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }

    error_log("Lỗi: " . $e->getMessage());

    echo json_encode(["code" => "01", "text" => "Lỗi: " . $e->getMessage()]);
}

?>
