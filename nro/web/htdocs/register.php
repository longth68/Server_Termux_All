<?php
include_once 'head.php';

$_alert = '';

// Giới hạn số lượng tài khoản đăng ký từ cùng một địa chỉ IP
// $max_accounts_per_ip = 3;
// $num_accounts = count_accounts_by_ip($_SERVER['REMOTE_ADDR']);
// if ($num_accounts >= $max_accounts_per_ip) {
//     $_alert = '<div class="alert alert-danger">Cư dân đã đăng ký quá số lượng tài khoản cho phép từ cùng địa chỉ IP!</div>';
// }

if ($_login == null && isset($_POST['username'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $repassword = mysqli_real_escape_string($conn, trim($_POST['repassword']));
    $magioithieu = mysqli_real_escape_string($conn, trim($_POST['magioithieu']));
    $captcha_response = $_POST['g-recaptcha-response'];

    // Kiểm tra CAPTCHA (Đã bỏ qua cho Localhost)
    if (false) {
        $_alert = '<div class="alert alert-danger">Vui lòng xác minh CAPTCHA!</div>';
    } else {
      //  if ($num_accounts < $max_accounts_per_ip) {
            if (strcmp($password, $repassword) == 0) {
                // Kiểm tra xem username này đã tồn tại hay chưa
                $read = _select("*", 'account', "username='$username'");
                $existing_account = _fetch($read);
                if (is_array($existing_account)) {
                    $_alert = '<div class="alert alert-danger">Tài khoản này đã tồn tại, vui lòng chọn tài khoản khác!</div>';
                } else {
                    // Thuc hien INSERT tai khoan vao CSDL (hashirama: cot email NOT NULL)
                    $txt = _insert('account', 'username,password,email,ip_address', "'$username','$password','$username@localhost','{$_SERVER['REMOTE_ADDR']}'");
                    $kiemtra = _query($txt);
                    if ($kiemtra) {
                        $_alert = '<div class="alert alert-success">Đăng kí thành công!! <a href="login">Đăng nhập ngay</a></div>';
                    }
                }
            } else {
                $_alert = '<div class="alert alert-danger">Hai mật khẩu không khớp nhau, vui lòng kiểm tra lại!</div>';
            }
        // } else {
        //     $_alert = '<div class="alert alert-danger">Cư dân đã đăng ký quá số lượng tài khoản cho phép từ cùng địa chỉ IP!</div>';
        // }
    }
}


// function count_accounts_by_ip($ip_address)
// {
//     $count = 0;
//     $result = _select("COUNT(*) as count", "account", "ip_address='$ip_address'");
//     if ($row = _fetch($result)) {
//         $count = $row['count'];
//     }
//     return $count;
// }
?>

<head>
    <title>Đăng ký - <?php echo $sv_code ?></title>
    <!-- Thêm script reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>

    <main>
        <form class="form-signin mt-4" method="POST">
            <div class="text-center mb-2">

            </div>
            <img src="assets/images/ỶEWUREWURWUW.png"
                style="display: block;margin-left: auto;margin-right: auto;max-width: 250px;">
            </h1>
            <input type="hidden" name="_token" value="JEGpj36vMoqzUAPDoHWTY8Y4jJiy4t0mhPST6nds">
            <?php
                if (!empty($_alert)) {
                    echo $_alert;
                }
                ?>
            <div class="form-group mt-3">
                <label class="sr-only">Tài khoản</label>
                <input type="text" class="form-control" placeholder="Tài khoản" required="" name="username" autofocus>
            </div>

            <div class="form-group mt-3">
                <label class="sr-only">Mật khẩu</label>
                <input type="password" class="form-control" placeholder="Mật khẩu" required="" name="password">
            </div>
            <div class="form-group mt-3">
                <label class="sr-only">Nhập lại mật khẩu</label>
                <input type="password" class="form-control" placeholder="Nhập lại mật khẩu" required=""
                    name="repassword">
            </div>

            <!-- Thêm widget reCAPTCHA (Đã ẩn) -->

            <button class="btn btn-primary w-100 mt-3" type="submit">Đăng ký</button>
            <div class="text-center mt-5 text-white">
                Cư dân chưa có tài khoản? <a class="text-dark blinking-text" href="login">Đăng nhập ngay</a>
            </div>
        </form>
        <style>
        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: 0 auto;
        }

        .form-signin .form-control {
            position: relative;
            box-sizing: border-box;
            height: auto;
            padding: 10px;
            font-size: 16px;
        }

        .form-signin .form-control:focus {
            z-index: 2;
        }

        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
        </style>
    </main>
</body>