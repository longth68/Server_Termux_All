<?php
define('NP', true);
require(__DIR__ . '/../../core/configs.php');
$post = json_decode(file_get_contents('php://input'), true);

$username = strip_tags($post['username']);
$current_pass = strip_tags($post['password']);
$new_password = strip_tags($post['new_password']);
$conn = SQL();

try {
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($stored_password);
        $stmt->fetch();
        if ($current_pass === $stored_password) {
            $updateStmt = $conn->prepare("UPDATE `users` SET `password` = ? WHERE `username` = ?");
            $updateStmt->bind_param("ss", $new_password, $username);
            $updateResult = $updateStmt->execute();
            if ($updateResult) {
                echo json_encode(['code' => '00', 'text' => 'Thay đổi mật khẩu thành công. Bạn cần đăng nhập lại.']);
                session_start();
                session_unset();
                session_destroy();
            } else {
                echo json_encode(['code' => '99', 'text' => 'Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ.']);
            }
        } else {
            echo json_encode(['code' => '04', 'text' => 'Mật khẩu không đúng.']);
        }
    } else {
        echo json_encode(['code' => '04', 'text' => 'Mật khẩu không đúng.']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['code' => '99', 'text' => 'Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ.']);
}
?>
