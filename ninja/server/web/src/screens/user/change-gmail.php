<?php
require_once(__DIR__ . '/../../../core/configs.php');

$message = '';
$showAddEmailForm = false;
$showChangeEmailForm = false;

if (isset($user['username'])) {
    $username = strip_tags($user['id']);
    $conn = SQL();
    try {
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($stored_email);
            $stmt->fetch();
            if (empty($stored_email)) {
                $showAddEmailForm = true;
            } else {
                $showChangeEmailForm = true;
            }
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Người dùng không tồn tại.</div>';
            header('Location: change-gmail');
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ.</div>';
        header('Location: change-gmail');
        exit();
    }
}

if (isset($_POST["submit"])) {
    $current_email = isset($_POST['current_email']) ? strip_tags($_POST['current_email']) : '';
    $new_email = isset($_POST['new_email']) ? strip_tags($_POST['new_email']) : '';

    if (empty($new_email)) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Email mới không được bỏ trống.</div>';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Email mới không hợp lệ.</div>';
    } else {
        try {
            if ($showChangeEmailForm) {
                $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($stored_email);
                    $stmt->fetch();
                    if ($current_email === $stored_email) {
                        $updateStmt = $conn->prepare("UPDATE `users` SET `email` = ? WHERE `id` = ?");
                        $updateStmt->bind_param("ss", $new_email, $username);
                        $updateResult = $updateStmt->execute();
                        if ($updateResult) {
                            $_SESSION['message'] = '<div class="alert alert-success" role="alert">Thay đổi email thành công.</div>';
                        } else {
                            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ.</div>';
                        }
                        header('Location: change-gmail');
                        exit();
                    } else {
                        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Email hiện tại không đúng.</div>';
                        header('Location: change-gmail');
                        exit();
                    }
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Người dùng không tồn tại.</div>';
                    header('Location: change-gmail');
                    exit();
                }
            } elseif ($showAddEmailForm) {
                $updateStmt = $conn->prepare("UPDATE `users` SET `email` = ? WHERE `id` = ?");
                $updateStmt->bind_param("ss", $new_email, $username);
                $updateResult = $updateStmt->execute();
                if ($updateResult) {
                    $_SESSION['message'] = '<div class="alert alert-success" role="alert">Thêm email thành công.</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ.</div>';
                }
                header('Location: change-gmail');
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Hệ thống gặp lỗi. Vui lòng liên hệ quản trị viên để được hỗ trợ.</div>';
            header('Location: change-gmail');
            exit();
        }
    }
}
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<?php if ($showAddEmailForm): ?>
<div class="w-100 d-flex justify-content-center">
    <form method="post" action="change-gmail" class="pb-3" style="width: 26rem;">
        <div class="fs-5 fw-bold text-center mb-3" style="color:#333 !important;">Thêm Email</div>
        <?php if ($message) echo $message;?>
        <div class="mb-2">
            <div class="input-group">
                <input name="new_email" type="email" autocomplete="off" placeholder="Nhập email mới" class="form-control form-control-solid" value="">
                <div class="invalid-feedback">Không được bỏ trống</div>
            </div>
        </div>
        <div class="text-center mt-3">
            <button type="submit" class="me-3 btn btn-primary " name="submit">Thêm Email</button>
        </div>
    </form>
</div>
<?php elseif ($showChangeEmailForm): ?>
<div class="w-100 d-flex justify-content-center">
    <form method="post" action="change-gmail" class="pb-3" style="width: 26rem;">
        <div class="fs-5 fw-bold text-center mb-3" style="color:#333 !important;">Đổi Email</div>
        <?php if ($message) echo $message;?>
        <div class="mb-2">
            <div class="input-group">
                <input name="current_email" type="email" autocomplete="off" placeholder="Nhập email hiện tại" class="form-control form-control-solid" value="">
                <div class="invalid-feedback">Không được bỏ trống</div>
            </div>
        </div>
        <div class="mb-2">
            <div class="input-group">
                <input name="new_email" type="email" autocomplete="off" placeholder="Email mới" class="form-control form-control-solid" value="">
                <div class="invalid-feedback">Không được bỏ trống</div>
            </div>
        </div>
        <div class="text-center mt-3">
            <button type="submit" class="me-3 btn btn-primary " name="submit">Đổi Email</button>
        </div>
    </form>
</div>
<?php endif; ?>
