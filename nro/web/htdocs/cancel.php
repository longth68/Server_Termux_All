<?php
include_once 'head.php';

if (isset($_SESSION['orderCode'])) {
    unset($_SESSION['orderCode']);
}

if (isset($_SESSION['amount'])) {
    unset($_SESSION['amount']);
}
?>

<head>
    <title>Huỷ thanh toán - <?php echo $sv_code ?></title>
</head>

<body>
    <div class="container py-5 mt-5"> 
        <div class="text-center mb-2">
            <img id="login-logo" class="img-fluid" src="/assets/images/avt/0.png" alt="" width="86" height="86">
        </div>

        <!-- Cancel payment, redirect after 5s -->
        <div class="alert alert-danger text-center" role="alert">
            <h4 class="alert-heading">Huỷ thanh toán</h4>
            <p id="redirect-alert" class="mb-0">Thanh toán đã bị huỷ. Bạn sẽ được chuyển về trang chủ sau 5 giây.</p>
        </div>

        <!-- Loop each second and update text -->
        <script>
            var sec = 4;
            var timer = setInterval(function() {
                document.getElementById('redirect-alert').innerHTML = 'Thanh toán đã bị huỷ. Bạn sẽ được chuyển về trang chủ sau ' + sec + ' giây.';
                sec--;
                if (sec < 0) {
                    clearInterval(timer);
                    window.location.href = '/profile';
                }
            }, 1000);
        </script>
    </div>
</body>