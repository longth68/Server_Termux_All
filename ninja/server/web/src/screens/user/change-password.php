<?php
require_once(__DIR__ . '/../../../core/configs.php');
$message = '';
if (isset($_POST["submit"])) {
    $username = strip_tags($user['id']);
    $current_pass = strip_tags($_POST['current_pass']);
    $new_password = strip_tags($_POST['new_password']);
    $confirm_password = strip_tags($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $message = '<div class="alert alert-danger" role="alert">Mật khẩu mới và mật khẩu xác nhận không khớp.</div>';
    } else {
        $conn = SQL();
        try {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($stored_password);
                $stmt->fetch();
                if ($current_pass === $stored_password) {
                    $updateStmt = $conn->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
                    $updateStmt->bind_param("ss", $new_password, $username);
                    $updateResult = $updateStmt->execute();

                    if ($updateResult) {
                        $message = '<div class="alert alert-success" role="alert">Thay đổi mật khẩu thành công. Bạn cần đăng nhập lại.</div>';
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            session_unset();
                            session_destroy();
                        } else {
                            session_start();
                            session_unset();
                            session_destroy();
                        }
                    } else {
                        $message = '<div class="alert alert-danger" role="alert">Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ.</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger" role="alert">Mật khẩu hiện tại không đúng.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger" role="alert">Người dùng không tồn tại.</div>';
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            $message = '<div class="alert alert-danger" role="alert">Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ.</div>';
        }
    }
}
?>

<div class="w-100 d-flex justify-content-center">
    <form method="post" action="change-password" class="pb-3" style="width: 26rem;">
        <div class="fs-5 fw-bold text-center mb-3" style="color:#333 !important;">Đổi mật khẩu</div>
        <?php if ($message) echo $message;?>
        <div class="mb-2">
            <div class="input-group">
                <input name="current_pass" type="password" autocomplete="off" placeholder="Nhập mật khẩu hiện tại" class="form-control form-control-solid" value="">
                <div class="invalid-feedback">Không được bỏ trống</div>
            </div>
        </div>
        <div class="mb-2">
            <div class="input-group">
                <input name="new_password" type="password" autocomplete="off" placeholder="Mật khẩu mới" class="form-control form-control-solid" value="">
                <div class="invalid-feedback">Không được bỏ trống</div>
            </div>
        </div>
        <div class="mb-2">
            <div class="input-group">
                <input name="confirm_password" type="password" autocomplete="off" placeholder="Nhập lại mật khẩu mới" class="form-control form-control-solid" value="">
                <div class="invalid-feedback">Không được bỏ trống</div>
            </div>
        </div>
        <div class="text-center mt-3">
            <button type="submit" class="me-3 btn btn-primary " name="submit">Đổi mật khẩu</button>
        </div>
    </form>
</div>
