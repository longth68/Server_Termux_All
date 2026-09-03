<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); } // Khởi tạo session
if (!isset($_SESSION['account'])) { // Kiểm tra nếu người dùng chưa đăng nhập
    header("Location: /register"); // Chuyển hướng đến trang đăng ký
    exit(); // Dừng thực thi mã tiếp theo
}
?>
<?php
include_once 'head.php';
?>

<head>
    <title>Lịch sử nạp - <?php echo $sv_code ?></title>
</head>
<style>
body {
    background: none;
}

.overlay {
    display: none;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    /* Cải thiện cuộn trên thiết bị cảm ứng */
}

.table {
    white-space: nowrap;
    /* Ngăn dữ liệu trong ô xuống dòng */
}
</style>
<main class="flex-grow-1 flex-shrink-1">
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
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>TYPE</th>
                                    <th>STATUS</th>
                                    <th>AMOUNT</th>
                                    <th>SERI</th>
                                    <th>TIME</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // HASHIRAMA: the cao -> topup, chuyen khoan -> mb_bank
                                $u = mysqli_real_escape_string($conn, $_username);
                                $data = _query("(SELECT loaithe AS ptype, trangthai AS pstt, vnd AS pvnd, seri, code, `time` FROM topup WHERE username='$u')
                                                UNION ALL
                                                (SELECT 'BANK' AS ptype, status AS pstt, amount AS pvnd, tid AS seri, description AS code, `time` FROM mb_bank WHERE username='$u')
                                                ORDER BY `time` DESC LIMIT 15");
                                if (!$data || mysqli_num_rows($data) == 0) { ?>
                                <tr>
                                    <td colspan="6" class="text-center">Lịch sử nạp trống</td>
                                </tr>
                                <?php } else {
                                    $i = 1;
                                    while ($row = mysqli_fetch_assoc($data)) {
                                        $type = htmlspecialchars($row['ptype'] ?: 'BANK');
                                    ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo $type ?></td>
                                    <td><?php echo get_string_tinhtrangthe((int)$row['pstt']); ?></td>
                                    <td><?php echo number_format($row['pvnd']); ?></td>
                                    <td><?php echo htmlspecialchars($row['seri']); ?></td>
                                    <td><?php echo (new DateTime($row['time']))->format('H:i -d/m/y'); ?></td>
                                </tr>
                                <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="alert" class="alert alert-danger" style="display: none;"></div>
        </main>
    </div>
</main>

<script>
function check_payment() {
    $('#done_btn').css('display', 'none');
    $('.status').html('<img src="/assets/images/loading.gif" alt="loading" />');
    $('.status').css('display', 'block');

    $.ajax({
        url: "./ajax/mbbank.php",
        type: 'post',
        data: {
            action: 'check',
            secret: <?= $post_secret ?>
        },
        success: function(data) {
            console.log(data);
            data = JSON.parse(data);
            if (data.status == 'error') {
                $('#done_btn').css('display', 'block');
                $('.status').css('display', 'none');

                $('#alert').css('display', 'block');
                $('#alert').html(data.message);
            } else if (data.status == 'success') {
                window.location.href = 'lich-su-nap';
                history.replaceState(null, null, 'lich-su-nap');
            }
        }
    });
}
</script>