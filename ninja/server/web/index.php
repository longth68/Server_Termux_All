<?php
    define('NP', true);
    require(__DIR__.'/core/configs.php');

    if ($isMaintained) {
        include("maintenance.php");
        return;
    }

    session_start();

    $isLogged = false;
    $user = null;
    if (isset($_SESSION['isLogged'])) {
        $isLogged = $_SESSION['isLogged'];
        if ($isLogged) {
            $pageWasRefreshed = isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
            if ($pageWasRefreshed && isset($_SESSION['user']) && is_array($_SESSION['user'])) {
                $sql = SQL()->query("SELECT * FROM users WHERE username = '".$_SESSION['user']['username']."' LIMIT 1");
                if ($sql !== false && $sql->num_rows > 0) {
                    $row = $sql->fetch_assoc();
                    $_SESSION['user'] = $row;
                }
            }
        }
    }
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
    }
    

    $page = isset($_GET['page']) ? $_GET['page'] : 'home';
    if (isset($page) && $page == 'logout') {
        session_destroy();
        header("Location: /");
        exit();
    }

    $tab = isset($_GET['tab']) ? $_GET['tab'] : '';
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($page == 'logout') {
        session_destroy();
        header("Location: /");
        exit;
    }
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/images/SCHOOLZ.png" type="image/x-icon" />
    <title>SCHOOLZ - Trải Nghiệm Ngay</title>
    <meta name="description" content="SCHOOLZ - Ninja SChool Online">

    <meta property="og:title" content="SCHOOLZ - Ninja SChool Online" />
    <meta property="og:description" content="SCHOOLZ - Ninja SChool Online" />
    <meta property="og:image" content="/images/SCHOOLZ.png" />
    <meta property="og:url" content="" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="SCHOOLZ - Ninja SChool Online" />
    
    <meta name="twitter:card" content="SCHOOLZ">
    <meta name="twitter:title" content="SCHOOLZ - Ninja SChool Online">
    <meta name="twitter:description" content="SCHOOLZ - Ninja SChool Online">
    <meta name="twitter:image" content="/images/SCHOOLZ.png">
    <meta name="twitter:site" content="SCHOOLZ - Ninja SChool Online">

    <link href="/static/css/bootstrap.min.css" rel="stylesheet">
    <script src="/static/js/jquery.min.js"></script>
    <link href="/static/css/main.css" rel="stylesheet">
    <script src="/static/js/toastr.min.js"></script>
    <script src="/static/js/index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.5/dist/notiflix-aio-3.2.5.min.js"></script>
    <link href="/static/css/toastr.min.css" rel="stylesheet" /> 

</head>

<body>
    <div id="root">
    <div class="background" style="transform: translate(-5%, -32%);"></div>

    <div id="hello-container"></div>
        <div id="container" class="container d-none">
            <div class="main">
                <?php include_once('./src/components/header.php'); ?>
                <?php include_once('./src/components/navbar.php'); ?>
                <div class="card">
                    <div class="card-body">
                        <?php include_once('./src/routers.php'); ?>
                    </div>
                </div>
                <?php include_once('./src/components/footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include_once('./src/components/active.php'); ?>
    <?php include_once('./src/components/forgotpassword.php'); ?>
    <?php include_once('./src/components/login.php'); ?>
    <?php include_once('./src/components/register.php'); ?>
    <script>
    $('[data-bs-dismiss=modal]').on('click', function(e) {
        var $t = $(this),
            target = $t[0].href || $t.data("target") || $t.parents('.modal') || [];

        $(target)
            .find("input,textarea,select")
            .val('')
            .end()
            .find("input[type=checkbox], input[type=radio]")
            .prop("checked", "")
            .end();
    });
    </script>

    <script src="/static/js/popper.min.js"></script>
    <script src="/static/js/bootstrap.min.js"></script>
</body>

</html>

<script>
    $(document).ready(function () {
        // $('#serverModal').modal('show');
        $(document).click(function (event) {
            if ($(event.target).hasClass('modal')) {
                $('#serverModal').modal('hide');
            }
        });

        $('#serverModal a').click(function () {
            $('#serverModal').modal('hide');
        });
    });
</script>

