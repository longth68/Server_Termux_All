<?php
session_start(); // Khởi tạo session
if (!isset($_SESSION['account'])) { // Kiểm tra nếu người dùng chưa đăng nhập
    header("Location: /register"); // Chuyển hướng đến trang đăng ký
    exit(); // Dừng thực thi mã tiếp theo
}
?>
<?php
$_alert = null;
include_once 'head.php';
if ($_login == null) {
    header("location:/login");
}

// Random string
$_SESSION['QR_ID'] = random_transaction_id(6);
?>

<head>
    <title>Nạp tiền - <?php echo $sv_code ?></title>
</head>
<style>
body {
    background: none;
}

.overlay {
    display: none;
}
</style>

<script>
function changePayType() {
    var type = document.querySelector("select[name='pay_type']").value;
    if (type == "CARD") {
        document.getElementById("card_form").style.display = "block";
        document.getElementById("card_note").style.display = "block";

        document.getElementById("bank_form").style.display = "none";
        document.getElementById("bank_note").style.display = "none";

        document.getElementById("card_form").reset();
    } else {
        document.getElementById("card_form").style.display = "none";
        document.getElementById("card_note").style.display = "none";

        document.getElementById("bank_form").style.display = "block";
        document.getElementById("bank_note").style.display = "block";

        document.getElementById("bank_form").reset();
    }

}
</script>

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
            </div>
            <div class="card-body">
                <div>
                    <label class="mx-1 mb-1">Phương thức:</label>
                    <select class="form-control form-control-alternative" name="pay_type" required
                        onchange="changePayType()">
                        <option value="BANK" selected="selected">Ngân hàng</option>
                        <option value="CARD">Thẻ cào</option>
                    </select>
                </div>

                <div class="mt-3">
                    <label class="mx-1 mb-1">Tài Khoản: </label><br>
                    <input type="text" class="form-control form-control-alternative"
                        style="background-color: #DCDCDC; font-weight: bold; color: #696969" name="username"
                        value="<?php echo $_username; ?>" readonly required>
                </div>

                <form method="POST" action="#" id="card_form" style="display: none;">
                    <tbody>
                        <div class="mt-3">
                            <label class="mx-1 mb-1">Loại thẻ:</label>
                            <select class="form-control form-control-alternative" name="card_type" required>
                                <option value="">Chọn loại thẻ</option>
                                <option value="VIETTEL">VIETTEL</option>
                                <option value="VINAPHONE">VINAPHONE</option>
                                <option value="MOBIFONE">MOBIFONE</option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <label class="mx-1 mb-1">Mệnh giá:</label>
                            <select class="form-control form-control-alternative" name="card_amount" required>
                                <option value="">Chọn mệnh giá</option>
                                <option value="10000">10.000</option>
                                <option value="20000">20.000</option>
                                <option value="30000">30.000 </option>
                                <option value="50000">50.000</option>
                                <option value="100000">100.000</option>
                                <option value="200000">200.000</option>
                                <option value="300000">300.000</option>
                                <option value="500000">500.000</option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <label class="mx-1 mb-1">Số seri:</label>
                            <input type="number" class="form-control form-control-alternative" name="serial" required />
                        </div>

                        <div class="mt-3">
                            <label class="mx-1 mb-1">Mã thẻ:</label>
                            <input type="number" class="form-control form-control-alternative" name="pin"
                                required /><br>
                        </div>

                        <button id="napngay_card" type="submit" class="btn btn-primary w-100 mt-3" name="submit">NẠP
                            NGAY</button>
                        <div class="status">
                        </div>
                    </tbody>
                </form>

                <form method="POST" action="#" id="bank_form" style="display: block;">
                    <tbody>
                        <div class="mt-3 mb-4">
                            <label class="mx-1 mb-1">Số tiền (VNĐ):</label>
                            <select class="form-control form-control-alternative" name="pay_amount" required>
                                <option value="10000" selected>10.000</option>
                                <option value="20000">20.000</option>
                                <option value="30000">30.000 </option>
                                <option value="50000">50.000</option>
                                <option value="100000">100.000</option>
                                <option value="200000">200.000</option>
                                <option value="300000">300.000</option>
                                <option value="500000">500.000</option>
                                <option value="1000000">1.000.000</option>
                            </select>
                        </div>

                        <button id="napngay_bank" type="submit" class="btn btn-primary w-100 mt-3" name="submit">THANH
                            TOÁN</button>
                        <div class="status">
                        </div>
                    </tbody>
                </form>
                <br><br>

                <div id="bank_note" style="display: block;">
                    <div>- Hãy kiểm tra kĩ thông tin trước khi THANH TOÁN (Tài khoản, Số tiền)</div>
                    <div>- Vui lòng chụp màn hình chuyển khoản sau khi THANH TOÁN</div>
                    <div>- Vui lòng CHỜ 10-20 giây và KHÔNG ĐÓNG cửa sổ THANH TOÁN sau khi THÀNH CÔNG</div>
                    <div>- Quá 10 phút thanh toán chưa được duyệt, hãy báo ngay cho ADMIN để được hỗ trợ</div><br>
                </div>

                <div id="card_note" style="display: none;">
                    <div>- Hãy kiểm tra kĩ thông tin trước khi NẠP THẺ (Tài khoản, Loại thẻ, Mệnh giá, Seri và Mã thẻ)
                    </div>
                    <div>- Nhập sai mệnh giá hay thông tin thẻ, ADMIN sẽ không chịu trách nhiệm</div>
                    <div>- Quá 10 phút thẻ chưa được duyệt, hãy báo ngay cho ADMIN để được hỗ trợ</div><br>
                </div>
            </div>
            <script type="text/javascript">
            $(document).ready(function() {
                var lastSubmitTime = 0;
                $("#card_form").submit(function(e) {
                    $('#napngay_card').css('display', 'none');
                    $('.status').html('<img src="/assets/images/loading.gif" alt="loading" />');
                    $('.status').css('display', 'block');
                    e.preventDefault();

                    var now = new Date().getTime();
                    if (now - lastSubmitTime < 30000) {
                        Swal.fire({
                            title: "Thông báo",
                            text: "Vui lòng đợi ít nhất 30 giây trước khi nạp tiếp!",
                            icon: "error",
                            confirmButtonText: "OK",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = "nap-tien";
                            }
                        });
                        return false;
                    }
                    lastSubmitTime = now;

                    $.ajax({
                        url: "./ajax/card.php",
                        type: 'post',
                        data: $("#card_form").serialize(),
                        success: function(data) {
                            $('#napngay_card').css('display', 'block');
                            $(".status").html(data);
                            document.getElementById("card_form").reset();
                        }
                    });
                });

                $("#bank_form").submit(function(e) {
                    $('#napngay_bank').css('display', 'none');
                    $('.status').html('<img src="/assets/images/loading.gif" alt="loading" />');
                    $('.status').css('display', 'block');
                    e.preventDefault();
                });

                $("#napngay_bank").click(function() {
                    var amount = $("select[name='pay_amount']").val();
                    window.location.href = "qr-code?amount=" + amount;
                });
            });
            </script>
        </main>
    </div>
</main>