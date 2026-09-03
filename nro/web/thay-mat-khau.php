<?php
session_start(); // Khởi tạo session
if (!isset($_SESSION['account'])) { // Kiểm tra nếu người dùng chưa đăng nhập
    header("Location: /register"); // Chuyển hướng đến trang đăng ký
    exit(); // Dừng thực thi mã tiếp theo
}
?>
<?php
include_once 'head.php';

if ($_login == null) {
    header("location:/user");
    exit();
}


function get_user_ip()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $clientIP = $_SERVER['REMOTE_ADDR'];
    }
    return $clientIP;
}

// Xử lý thay đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $username = $_username; // Đảm bảo bạn đã có tên người dùng trong biến này

    // Lấy mật khẩu đã lưu từ cơ sở dữ liệu
    $stmt = $conn->prepare("SELECT password FROM account WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($stored_password);
    $stmt->fetch();
    $stmt->close();

    // Kiem tra mat khau hien tai
    if ($current_password !== $stored_password) {
        $error_message = "Mật khẩu hiện tại không đúng.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
    } else {
        // Cap nhat mat khau moi vao co so du lieu
        $stmt = $conn->prepare("UPDATE account SET password = ? WHERE username = ?");
        $stmt->bind_param('ss', $new_password, $username);
        $stmt->execute();
        $stmt->close();

        $success_message = "Mật khẩu đã được thay đổi thành công.";
    }
}
?>

<head>
    <title>Tài khoản - <?php echo $sv_code ?></title>
</head>
<style>
body {
    background: none;
}

.overlay {
    display: none;
}
</style>

<body>
    <div class="container-fluid">
        <main>
            <div class="menu row">
                <div class="col-md-3 pb-3 pt-2">
                    <div class="list-group d-sm-block">
                        <?php
include_once 'menu.php';
?>
                    </div>
                </div>
                <div class="col-md-9 pb-3 pt-2">

                    <!-- Form đổi mật khẩu -->
                    <div class="pt-3">
                        <h4>Đổi mật khẩu</h4>
                        <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                        <?php endif; ?>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                <input type="password" class="form-control" id="current_password"
                                    name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Mật khẩu mới</label>
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary w-100 mt-3">Đổi
                                mật khẩu</button>
                        </form>
                    </div>

                </div>
            </div>
        </main>
    </div>
</body>
<?php $conn->close(); ?>