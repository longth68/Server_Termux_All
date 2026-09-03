<?php
$_alert = null;
include_once 'head.php';
if ($_login == null) {
    header("location:/login");
}
?>

<head>
    <title>Quy đổi - <?php echo $sv_code ?></title>
</head>
<style>
    body {
        background: none;
    }
    .overlay {
        display: none;
    }
</style>
<main class="flex-grow-1 flex-shrink-1">
    <div class="container pt-5 mt-5">
        <main>
            <div class="menu row">
                <div class="col-md-3 pb-3 pt-2">
                    <div class="list-group d-sm-block">
                        <a href="profile" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Thông tin tài khoản
                        </a>
                        <a href="quydoi" class="list-group-item list-group-item-action active">
                            <i class="fas fa-exchange-alt me-2"></i> Quy đổi
                        </a>
                        <a href="nap-tien" class="list-group-item list-group-item-action ">
                            <i class="fas fa-coins me-2"></i> Nạp tiền
                        </a>
                        <a href="lich-su-nap" class="list-group-item list-group-item-action">
                            <i class="fas fa-credit-card me-2"></i> Lịch sử nạp
                        </a>
                        <a href="/?out" class="list-group-item list-group-item-action">
                            <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form action="exchange" method="POST" id="myform">
                    <tbody>
                        <div>
                            <label class="mx-1 mb-1">Tài Khoản: </label><br>
                            <input type="text" class="form-control form-control-alternative" style="background-color: #DCDCDC; font-weight: bold; color: #696969" name="username" value="<?php echo $_username; ?>" readonly required>
                        </div>
                        <div class="mt-2">
                            <label class="mx-1 mb-1">Loại quy đổi:</label>
                            <select class="form-control form-control-alternative" name="loaiquydoi" required>
                                <option value="tv">Thỏi vàng</option>
                            </select>
                        </div>
                        <div class="mt-2">
                            <label class="mx-1 mb-1">Số tiền:</label>
                            <input type="number" class="form-control form-control-alternative" name="vnd" placeholder="VD: 20000" required />
                        </div>
                        <div class="mt-2">
                            <label class="mx-1 mb-1">Nhận được:</label>
                            <input type="text" class="form-control form-control-alternative" style="background-color: #DCDCDC; font-weight: bold; color: #696969" name="nhanduoc" readonly required>
                        </div><br>
                        <button type="submit" class="btn btn-outline-primary btn-primary-or" name="submit">ĐỔI NGAY</button>
                    </tbody>
                </form><br><br>
                <div>- Sau khi quy đổi, thỏi vàng sẽ được chuyển về NPC Siêu Nhân tại Đảo Kame</div>
                <div>- Mọi hành vi bug / lợi dụng lỗi game đều sẽ bị khoá tài khoản vĩnh viễn</div><br>
                </form>
            </div>
    </div>
    </div>

    <div id="status"></div>

    <script>
        $(document).ready(function() {
            const vnd = document.querySelector("input[name='vnd']");
            const loaiquydoi = document.querySelector("select[name='loaiquydoi']");

            loaiquydoi.addEventListener("blur", function(event) {
                tinhTiLe(loaiquydoi.selectedIndex, vnd.value)
            });

            vnd.addEventListener("blur", function(event) {
                tinhTiLe(loaiquydoi.selectedIndex, vnd.value)
            });

            function tinhTiLe(index, vnd) {
                const tien = Math.floor(vnd / 1000);

                var nhan = 0;

                if (index == 0) {
                    if (tien > 0) {
                        nhan = Math.floor(tien * 60);
                    }
                } else {
                    nhan = 0;
                }

                document.querySelector("input[name='nhanduoc']").value = nhan;
            }
        });
    </script>
    </div>
</main>