<?php
include_once 'head.php';

if (isset($_SESSION['orderCode'])) {
    $orderCode = $_SESSION['orderCode'];
    $amount = $_SESSION['amount'];

    $currentMillis = time();
    $queryString = "INSERT INTO naptien(uid, type, vnd, time, noidung, tinhtrang, tranid) VALUES ('" . $_username . "','BANK','" . $amount . "','" . $currentMillis . "','" . $_username . "','0','" . $orderCode . "')";
    _query($queryString);

    unset($_SESSION['orderCode']);
    unset($_SESSION['amount']);
}
?>

<head>
    <title>Thành công - <?php echo $sv_code ?></title>
</head>

<body>
    <div class="container py-5 mt-5">
        <div class="text-center mb-2">
            <img id="login-logo" class="img-fluid" src="/assets/images/avt/0.png" alt="" width="86" height="86">
        </div>

        <div class="alert <?php echo isset($orderCode) ? 'alert-success' : 'alert-danger'; ?> text-center" role="alert">
            <!-- If has orderCode -->
            <?php if (isset($orderCode)) : ?>
                <h4 class="alert-heading">Thanh toán thành công</h4>
                <p class="mb-3">Thanh toán sẽ được duyệt trong vòng 5 phút. Nếu không, hãy liên hệ ADMIN để được hỗ trợ!</p>
                <p id="redirect-alert" class="fw-bold fst-italic">Bạn sẽ được chuyển về trang chủ sau 5 giây.</p>
            <?php else : ?>
                <!-- If has no orderCode -->
                <h4 class="alert-heading">Phiên không hợp lệ</h4>
                <p class="mb-3">Phiên truy cập không hợp lệ. Vui lòng kiểm tra và bắt đầu lại.</p>
                <p id="redirect-alert" class="fw-bold fst-italic">Bạn sẽ được chuyển về trang chủ sau 5 giây.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

<script>
    var sec = 4;
    var timer = setInterval(function() {
        document.getElementById('redirect-alert').innerHTML = 'Bạn sẽ được chuyển về trang chủ sau ' + sec + ' giây.';
        sec--;
        if (sec < 0) {
            clearInterval(timer);
            window.location.href = '/profile';
        }
    }, 1000);
</script>