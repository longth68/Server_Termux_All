<?php
session_start(); // Khởi tạo session
if (!isset($_SESSION['account'])) { // Kiểm tra nếu người dùng chưa đăng nhập
    header("Location: /register"); // Chuyển hướng đến trang đăng ký
    exit(); // Dừng thực thi mã tiếp theo
}
?>
<?php
include_once 'head.php';


if (!isset($_GET['amount'])) {
	header("location:/");
}

if (!isset($_SESSION['QR_ID'])) {
	header("location:/nap-tien");
} else {
	unset($_SESSION['QR_ID']);
}

if (!isset($_SESSION['LAST_GET_QR'])) {
	$_SESSION['LAST_GET_QR'] = time() - 3;
}

function can_create_qr($username)
{
	$count = 0;
	$result = _query("SELECT COUNT(*) as count FROM naptien WHERE type='BANK' AND uid='$username' AND tinhtrang='0'");
	if ($row = mysqli_fetch_assoc($result)) {
		$count = $row['count'];
	}
	return $count < 3;
}

if (!can_create_qr($_username)) {
	echo '
		<script type="text/javascript">
			$(document).ready(function(){
				Swal.fire({
					title: "Thông báo",
					text: "Bạn còn quá nhiều giao dịch chưa hoàn thành! Vui lòng liên hệ ADMIN.",
					icon: "error",
					confirmButtonText: "OK",
				}).then((result) => {
					if (result.isConfirmed) {
						window.location.href = "/";
					}
				});
			});
		</script>
	';
	return;
}

$vnd = $_GET['amount'];
$tranId = random_transaction_id(6);
$description = 'BASE ' . $_username . ' ' . $tranId;

if (time() - $_SESSION['LAST_GET_QR'] < 3) {
	echo '
		<script type="text/javascript">
			$(document).ready(function(){
				Swal.fire({
					title: "Thông báo",
					text: "Bạn đang thao tác quá nhanh! Vui lòng thử lại sau 3 giây.",
					icon: "error",
					confirmButtonText: "OK",
				}).then((result) => {
					if (result.isConfirmed) {
						window.location.href = "/";
					}
				});
			});
		</script>
	';
} else {
	echo '
		<script type="text/javascript">
			$(document).ready(function(){
				setTimeout(function(){
					$("#qr-code").attr("src", "' . get_link_qr($bank_type, $bank_account, $bank_owner, $vnd, $sv_code, $_username, $tranId) . '");
					$(".action").show();
				}, 500);
			});
		</script>
	';
}

function get_link_qr($bank_type, $bank_account, $account_name, $vnd, $sv_code, $_username, $tranId)
{
	$_SESSION['LAST_GET_QR'] = time();
	$description = 'BASE ' . $_username . ' ' . $tranId;
	$query = "INSERT INTO `naptien` (`noidung`, `uid`, `vnd`, `tinhtrang`, `type`) VALUES ('$tranId', '$_username', '$vnd', 0, 'BANK')";

	_query($query);

	return "https://img.vietqr.io/image/$bank_type-$bank_account-compact2.png?amount=$vnd&addInfo=$description&accountName=$account_name";
}
?>

<head>
    <title>Thanh toán - <?php echo $sv_code ?></title>
</head>

<style>
#qr-code {
    width: 80%;
    max-width: 360px;
}

#bank-info {
    display: flex;
    align-items: center;
    flex-direction: row;
    justify-content: space-evenly;
}

.copy-button {
    background: none;
    border: none;
    cursor: pointer;
}

.copy-button:hover {
    color: blue;
}

.action {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: flex-end;
}

.btn-action {
    width: 50%;
    max-width: 150px;
    margin: 0 20px;
}

@media screen and (max-width: 768px) {
    #bank-info {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
}
</style>

<div class="container-fluid">
    <main>

        <div id="bank-info">
            <img id="qr-code" class="mb-3" src="./assets/images/ỶEWUREWURWUW.png" alt="QReCode">
            <div class="text-center">
                <p style="color: black; font-weight: bold;">Quét mã QR để thanh toán</p>
                <p style="color: black; font-weight: bold;">hoặc</p>
                <p style="color: black; font-weight: bold;">Chuyển khoản qua số tài khoản</p>
                <p class="text-primary" style="color: black; font-weight: bold;">
                    <?= $bank_account ?>
                    <button class="copy-button" onclick="copyToClipboard(this, '<?= $bank_account ?>')">
                        <i class="fa-regular fa-clipboard"></i>
                    </button>
                </p>
                <p style="color: black; font-weight: bold;">Số tiền: <?= number_format($vnd) ?> VNĐ</p>
                <p style="color: black; font-weight: bold;">Nội dung:</p>
                <p style="color: black; font-weight: bold;"></p>

                <span class="text-primary"><?= $description ?></span>
                <button class="copy-button" onclick="copyToClipboard(this, '<?= $description ?>')">
                    <i class="fa-regular fa-clipboard"></i>
                </button>
                </p>
            </div>
        </div>
        <div class="action" style="display: none;">
            <button id="done_btn" class="btn-action btn btn-primary mt-3" onclick="donePayment()">Hoàn
                thành</button>
            <div class="status">
            </div>
        </div>
    </main>
</div>

<script>
function copyToClipboard(el, text) {
    navigator.clipboard.writeText(text).then(function() {
        // remove all i elements
        var iElements = $(el).find('i');
        iElements.remove();

        // add checkmark icon <i class="fa-solid fa-check"></i>
        var checkmarkIcon = document.createElement('i');
        checkmarkIcon.classList.add('fa-solid', 'fa-check');
        el.appendChild(checkmarkIcon);
    });
}

function donePayment() {
    // redirect to nap-tien page and remove history
    $('#done_btn').css('display', 'none');
    $('.status').html('<img src="/assets/images/loading.gif" alt="loading" />');
    $('.status').css('display', 'block');

    window.location.href = 'lich-su-nap';
    history.replaceState(null, null, 'lich-su-nap');

    return;
}

function cancelPayment() {
    window.location.href = 'nap-tien';
    history.replaceState(null, null, 'nap-tien');
}
</script>