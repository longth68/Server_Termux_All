<?php
include_once 'head.php';
$_alert = '';
if ($_login == null) {
    if (isset($_POST['username'])) {
        $user = mysqli_real_escape_string($conn, trim($_POST['username']));
        $pass = mysqli_real_escape_string($conn, trim($_POST['password']));

        $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

        $select = _fetch(_select("*", 'account', "username='$user'"));

        if ($select == null) {
            $_alert = '<div class="alert alert-danger">Thông tin đăng nhập không chính xác!</div>';
        } else {
            if ($select['ban'] < 0) {
                $_alert = '<div class="alert alert-danger">Tài khoản của bạn đã bị khóa!</div>';
            } else if ($select['password'] == $pass) {
                $_SESSION['account'] = $user;
                $_alert = '<div class="alert alert-success">Đăng nhập thành công!</div>';
                echo "<script>setTimeout(function(){ window.location.href = '/'; }, 2000);</script>"; // Chuyển hướng sau 2 giây
            } else {
                $_alert = '<div class="alert alert-danger">Thông tin đăng nhập không chính xác!</div>';
            }
        }
    }
} else {
    header("location:/");
}
?>

<head>
    <title>Đăng nhập - <?php echo $sv_code ?></title>
</head>

<main>
    <form class="form-signin mt-4" method="POST">
        <div class="text-center mb-2">
        </div>
        <img src="assets/images/ỶEWUREWURWUW.png" style="display: block;margin-left: auto;margin-right: auto;max-width: 250px;">
        <?php
                if (!empty($_alert)) {
                    echo $_alert;
                }
            ?>
        <div class="mt-3">
            <label class="sr-only">Tài khoản</label>
            <input type="text" class="p-2 form-control" placeholder="Tài khoản" required="" name="username" autofocus>
        </div>
        <div class="mt-3 mb-3">
            <label class="sr-only">Mật khẩu</label>
            <input type="password" class="p-2 form-control" placeholder="Mật khẩu" required="" name="password">
        </div>

        <button class="btn btn-primary w-100 mt-3" type="submit">Đăng nhập</button>
        <div class="text-center mt-5 text-white">
            Cư dân chưa có tài khoản? <a class="text-dark blinking-text" href="register">Đăng ký ngay</a>
        </div>
    </form>
</main>
</div>
</div>
</body>