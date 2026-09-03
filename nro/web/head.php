<?php
include_once 'hidden/set.php';
error_reporting(0);
?>
<!-- Code by VMN -->
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <link rel="icon" href="ỶEWUREWURWUW.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <meta name="title" content="<?php echo htmlspecialchars($title); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($author); ?>">

    <!-- Open Graph Meta Tags for Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($DOMAIN); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($description); ?>">
    <meta property="og:image" content="/cover.png">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($sv_name); ?>">


    <!-- Title -->
    <title><?php echo htmlspecialchars($sv_name); ?></title>

    <!-- External JavaScript and CSS -->
    <link rel="shortcut icon" type="img/fav.png" href="assets/images/favicon-32x32.png" />
    <link rel="stylesheet" href="/assets/css/all.min.css" />
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <script src="/assets/js/jquery.js"></script>
    <script src="/assets/js/sweetalert2.js"></script>
    <!-- <script src="https://kit.fontawesome.com/c79383dd6c.js" crossorigin="anonymous"></script> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script src="bktne/static/js/bootstrap.min.js" defer></script>
    <script src="bktne/static/js/notiflix-aio.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12" defer></script>
    <script src="bktne/static/js/TiMi_v%3D1.1.js" defer></script>

    <!-- External CSS -->
    <link href="bktne/static/css/main.a238f600_v%3D1.1.css" rel="stylesheet">

    <!-- JavaScript to block copying -->
    <script>
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        alert('BktNe');
    });
    document.onkeydown = function(e) {
        if (e.ctrlKey && e.keyCode == 85) {
            alert('BktNe');
            return false;
        }

        if (e.ctrlKey && e.keyCode == 67) {
            alert('BktNe');
            return false;
        }
    };
    </script>
</head>

<body>
    <div id="root" style="margin-top: 0; display: flex; flex-direction: column; height: 100vh;">
        <div class="background" style="transform: translate(0, 0);"></div>

        <div id="hello-container"></div>

        <div id="container" class="container" style="flex: 1; margin-top: 0;">
            <div class="main" style="flex: 1;">
                <!-- Card chính -->
                <div class="text-center card" style="margin-top: 0;">
                    <div class="card-body" style="padding-top: 0;">
                         <div>
                            <a href="/"><img class="logo" alt="Logo" src="assets/images/ỶEWUREWURWUW.png"style="width: 100%; max-width: 380px; height: auto;"></a>
                        </div>
                        <div class="mt-3">
                            <?php if ($_login == null) { ?>
                            <span class="btn btn-success px-3 py-1" onclick="window.location.href='/login.php';">Đăng
                                nhập</span>
                            <span class="btn btn-success px-3 py-1" onclick="window.location.href='/register.php';">Đăng
                                ký</span>
                            <?php } else { ?>
                            <a class="btn btn-success" href="/profile">
                                <span><?php echo $_username; ?> - <?php echo number_format($_vnd); ?> VND</span>
                            </a>
                            <?php } ?>
                        </div>
                        <div class="mt-3">
						<a class=""
                                href="../Downloads/Nro HanZi.jar">
                                <img class="m-2" height="48" src="../assets/images/button/java.png" alt="APK">
                            </a>
                            <a class=""
                                href="../Downloads/Nro HanZi.apk">
                                <img class="m-2" height="48" src="../assets/images/button/UfEcaeH.png" alt="APK">
                            </a>
                            <a class="" href="../Downloads/Nro HanZi.ipa">
                                <img class="m-2" height="48" src="../assets/images/button/ufy0Wg0.png" alt="IOS">
                            </a>
                            <a class="" href="https://testflight.apple.com/join/M7FH1r7V">
                                <img class="m-2" height="48" src="../assets/images/button/tf.png" alt="IOS">
                            </a>
                            <a class=""
                                href="../Downloads/Nro HanZi.rar">
                                <img class="m-2" height="48" src="../assets/images/button/20wNXlA.png" alt="Windows">
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="row text-center justify-content-center row-cols-2 row-cols-lg-6 g-2 g-lg-2 mt-1">
                        <div class="col">
                            <div class="px-2">
                                <a class="btn btn-menu btn-success w-100 fw-semibold active" href="/">Trang chủ</a>
                            </div>
                        </div>
                        <div class="col">
                            <div class="px-2">
                                <a class="btn btn-menu btn-success w-100 fw-semibold false"
                                    href="https://zalo.me/g/aspnqs256">Box Zalo</a>
                            </div>
                        </div>
                        <?php if ($_login != null) { ?>
                        <div class="col">
                            <div class="px-2">
                                <a class="btn btn-menu btn-success w-100 fw-semibold false" href="/profile">Profile</a>
                            </div>
                        </div>
                        <?php if (isset($user_arr['is_admin']) && $user_arr['is_admin'] == 1) { ?>
                        <div class="col">
                            <div class="px-2">
                                <a class="btn btn-menu btn-success w-100 fw-semibold false" href="/admin.php">Admin Panel</a>
                            </div>
                        </div>
                        <?php } ?>
                        <?php } ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body"></div>