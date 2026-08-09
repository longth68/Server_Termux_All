<?php

define('NP', true);
require(__DIR__ . '/../../core/configs.php');

$post = json_decode(file_get_contents('php://input'), true);

$username = strip_tags($post['username'] ?? '');
$newPass = strip_tags($post['newPass'] ?? '');
$reNewPass = strip_tags($post['reNewPass'] ?? '');
$conn = SQL();

if (empty($username) || empty($newPass) || empty($reNewPass)) {
    echo json_encode(["code" => "01", "text" => "Vui lòng điền đầy đủ thông tin."]);
    exit;
}

if ($newPass !== $reNewPass) {
    echo json_encode(["code" => "02", "text" => "Mật khẩu không trùng khớp."]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $updateStmt->bind_param("ss", $newPass, $username);

        if ($updateStmt->execute()) {
            echo json_encode(["code" => "00", "text" => "Thay đổi mật khẩu thành công."]);
        } else {
            echo json_encode(["code" => "03", "text" => "Không thể thay đổi mật khẩu. Vui lòng thử lại sau."]);
        }
        $updateStmt->close();
    } else {
        echo json_encode(["code" => "04", "text" => "Tên đăng nhập hoặc email không chính xác."]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["code" => "99", "text" => "Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ."]);
}
$conn->close();

?>
