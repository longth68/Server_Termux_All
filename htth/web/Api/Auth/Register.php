<?php
include __DIR__ . '/../../Controllers/Connections.php';
include __DIR__ . '/../../Controllers/Configs.php';
include __DIR__ . '/../../Controllers/Sessions.php';

$response = [
    'status' => 'error',
    'message' => 'An unknown error occurred.'
];

function insertAccount($conn, $User, $Pass)
{
    try {
        $stmt = $conn->prepare("INSERT INTO accounts (user, pass) VALUES (:username, :password)");
        $stmt->bindValue(':username', $User, PDO::PARAM_STR);
        $stmt->bindValue(':password', $Pass, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $User = htmlspecialchars(trim($_POST["username"]));
    $Pass = htmlspecialchars(trim($_POST["password"]));
    $RePass = htmlspecialchars(trim($_POST["repassword"]));

    if ($_AuthLog == 1) {
        $response['message'] = 'Đang bảo trì đăng nhập, vui lòng thử lại sau!';
    } elseif (!isValidInput($User) || !isValidInput($Pass)) {
        $response['message'] = 'Tên đăng nhập và mật khẩu không được chứa kí tự đặc biệt.';
    } elseif ($Pass !== $RePass) {
        $response['message'] = 'Mật khẩu nhập lại không khớp!';
    } elseif (checkExistingUsername($conn, $User)) {
        $response['message'] = 'Tài khoản đã tồn tại.';
    } else {
        if (insertAccount($conn, $User, $Pass)) {
            $response['status'] = 'success';
            $response['message'] = 'Đăng kí thành công!';
        } else {
            $response['message'] = 'Đăng ký thất bại.';
        }
    }
    echo json_encode($response);
    exit;
}