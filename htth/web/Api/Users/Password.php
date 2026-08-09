<?php
include __DIR__ . '/../../Controllers/Connections.php';
include __DIR__ . '/../../Controllers/Configs.php';
include __DIR__ . '/../../Controllers/Sessions.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postData = json_decode(file_get_contents('php://input'), true);

    $current_password = $postData['current_password'];
    $newpass = $postData['newpassword'];
    $newconfirm = $postData['newpassword_confirm'];
    $username = $postData['username'];

    // Kiểm tra mật khẩu cũ của người dùng
    if ($_Pass != $current_password) {
        echo json_encode(array("message" => "error", "errorMessage" => "Mật khẩu cũ không chính xác!"));
    } elseif ($current_password == $newpass) {
        echo json_encode(array("message" => "error", "errorMessage" => "Mật khẩu mới không được giống mật khẩu cũ!"));
    } elseif ($newpass != $newconfirm) {
        echo json_encode(array("message" => "error", "errorMessage" => "Mật khẩu mới và xác nhận mật khẩu không khớp!"));
    } else {
        // Cập nhật mật khẩu trong cơ sở dữ liệu
        $stmt = $conn->prepare('UPDATE accounts SET pass = :newpassword WHERE user = :username');
        $stmt->bindValue(':newpassword', $newpass);
        $stmt->bindValue(':username', $username);
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            echo json_encode(array("message" => "success", "successMessage" => "Đổi mật khẩu thành công!"));
        } else {
            echo json_encode(array("message" => "error", "errorMessage" => "Có lỗi khi đổi mật khẩu. Vui lòng thử lại sau."));
        }
    }
}
