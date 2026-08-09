<?php
define('NP', true);
require(__DIR__ . '/../../core/configs.php');

header('Content-Type: application/json');

$post = json_decode(file_get_contents('php://input'), true);
$conn = SQL();

session_start();

try {
    if (!isset($_SESSION['user']['username'])) {
        echo json_encode(['code' => '01', 'text' => 'Thông tin tài khoản hoặc mật khẩu không chính xác.']);
        exit;
    }
    $user = $_SESSION['user'];
    $username = $_SESSION['user']['username'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_renew = $result->fetch_assoc();

    $isOnline = $user_renew['online'];

    if ($isOnline == 1) {
        echo '{"code": "99", "text": "Bạn chưa thoát game."}';
        return;
    }

    if (!$user_renew) {
        echo json_encode(['code' => '01', 'text' => 'Thông tin tài khoản hoặc mật khẩu không chính xác.']);
        exit;
    }

    if ($user_renew['balance'] < $fees['active']) {
        echo json_encode(['code' => '05', 'text' => 'Tài khoản không đủ số dư.']);
        exit;
    }

    $new_balance = $user_renew['balance'] - $fees['active'];
    $update_stmt = $conn->prepare("UPDATE users SET activated = 1,kh = 1, balance = ? WHERE username = ?");
    $update_stmt->bind_param("is", $new_balance, $username);
    $charge_fee = $update_stmt->execute();

    if ($charge_fee) {
        echo json_encode(['code' => '00', 'text' => 'Kích hoạt tài khoản thành công.']);
    } else {
        echo json_encode(['code' => '06', 'text' => 'Kích hoạt tài khoản thất bại. Vui lòng liên hệ quản trị viên để được hỗ trợ.']);
    }
} catch (Exception $e) {
    echo json_encode(['code' => '99', 'text' => 'Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ.']);
}
?>
