<?php
include __DIR__ . '/../../Controllers/Connections.php';
include __DIR__ . '/../../Controllers/Configs.php';
include __DIR__ . '/../../Controllers/Sessions.php';
function isValidUsername($User)
{
    return ctype_alnum($User);
}

function loginUser($User, $Pass, $conn)
{
    global $_AuthLog;

    if ($_AuthLog == 1) {
        return [
            'status' => 'error',
            'message' => 'Đang bảo trì đăng nhập, vui lòng thử lại sau!'
        ];
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE user = :username");
        $stmt->bindParam(':username', $User, PDO::PARAM_STR);
        $stmt->execute();
        $select = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($select !== false && $select['pass'] == $Pass) {
            $_SESSION['username'] = htmlspecialchars($User, ENT_QUOTES, 'UTF-8');
            $_SESSION['id'] = $select['id'];
            return [
                'status' => 'success',
                'message' => 'Đăng nhập thành công!'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Tên đăng nhập hoặc mật khẩu không hợp lệ, vui lòng kiểm tra lại!'
            ];
        }
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'Có lỗi xảy ra trong quá trình xử lý. Vui lòng thử lại sau!'
        ];
    }
}

$response = [
    'status' => 'error',
    'message' => 'An unknown error occurred.'
];

if (isset($_POST['username']) && isset($_POST['password'])) {
    $User = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
    $Pass = htmlspecialchars(trim($_POST['password']), ENT_QUOTES, 'UTF-8');

    if (!isValidUsername($User)) {
        $response['message'] = 'Tên đăng nhập chỉ được chứa kí tự và số!';
    } else {
        $response = loginUser($User, $Pass, $conn);
    }
} else {
    $response['message'] = 'Vui lòng nhập tên đăng nhập và mật khẩu!';
}

echo json_encode($response);
exit;