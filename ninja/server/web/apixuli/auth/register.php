<?php

define('NP', true);
require(__DIR__ . '/../../core/configs.php');
$post = json_decode(file_get_contents('php://input'), true);


if (empty($post['username']) || empty($post['password'])) {
    echo '{"code": "01", "text": "Vui lòng nhập đầy đủ thông tin đăng ký."}';
    exit;
}
$username = $post['username'];
$password = $post['password'];
if (!preg_match('/^[a-z0-9]+$/', $username) || !preg_match('/^[a-z0-9]+$/', $password)) {
    echo json_encode(['code' => '01', 'text' => 'Tên đăng nhập và mật khẩu chỉ được chứa chữ thường và số']);
    exit;
}
$conn = SQL();

try {
    $stmt = $conn->prepare("SELECT username FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["code" => "02", "text" => "Tên đăng nhập đã tồn tại trên hệ thống."]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {
        echo json_encode(["code" => "00", "text" => "Tạo tài khoản thành công."]);
    } else {
        echo json_encode(["code" => "99", "text" => "Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ."]);
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(["code" => "99", "text" => "Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ."]);
}

?>
