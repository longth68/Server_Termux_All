<?php
define('NP', true);
require(__DIR__ . '/../../core/configs.php');
$post = json_decode(file_get_contents('php://input'), true);

$username = strip_tags($post['username']);
$email = strip_tags($post['fmail']);
$conn = SQL();

try {
    $stmt = $conn->prepare("SELECT email FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        echo json_encode(['code' => '01', 'text' => 'Tên đăng nhập không tồn tại']);
        exit;
    }
    $stmt->bind_result($db_email);
    $stmt->fetch();
    if ($email !== $db_email) {
        echo json_encode(['code' => '02', 'text' => 'Địa chỉ email không khớp']);
        exit;
    }
    echo json_encode(['code' => '00', 'text' => 'Xác thực thành công']);
    
} catch (Exception $e) {
    echo json_encode(['code' => '99', 'text' => 'Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ.']);
}

?>
