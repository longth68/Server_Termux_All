<?php
include_once 'head.php';

if ($_login == null) {
	header("location:/");
}
$_alert = null;

if (!isset($_POST['loaiquydoi'], $_POST['vnd'])) {
	header("location:/error.php");
}

$vnd = $_POST['vnd'];
$loaiquydoi = $_POST['loaiquydoi'];

if (is_numeric($vnd)) {
	$tien = floor($vnd / 1000);
	$tile = 0;
	$nhan = 0;

	if ($vnd < 10000) {
		echo '
		<script type="text/javascript">
		$(document).ready(function(){
			Swal.fire({
				title: "Thất bại",
				text: "Số tiền quy đổi ít nhất 10.000 VNĐ !",
				icon: "error",
				confirmButtonText: "OK",
			}).then((result) => {
				if (result.isConfirmed) {
					window.location.href = "quydoi";
				}
			});
		});
		</script>
		';
	} else if ($_vnd < $vnd) {
		echo '
		<script type="text/javascript">
		$(document).ready(function(){
			Swal.fire({
				title: "Thất bại",
				text: "Cư dân không đủ tiền để thực hiện quy đổi !",
				icon: "error",
				confirmButtonText: "OK",
				}).then((result) => {
				if (result.isConfirmed) {
					window.location.href = "quydoi";
				}
			});
		});
		</script>
		';
	} else {
		$nhan = floor($tien * 60);
		if ($nhan != 0) {
			// HASHIRAMA: khong co cot coin -> quy doi vao thoi_vang (thoi vang)
			$query = _query(_update('account', "vnd = vnd - $vnd, thoi_vang = thoi_vang + $nhan", "username='$_username' AND vnd >= $vnd"));

			if ($query) {
				# Lưu log quy đổi 
				$file = "logs/exchange.log";
				$fh = fopen($file, 'a') or die("cant open file");
				fwrite($fh, "User: " . $_username . " quy doi " . $vnd . " VND nhan duoc " . $nhan . " coin");
				fwrite($fh, "\r\n");
				fclose($fh);

				echo '
				<script type="text/javascript">
				$(document).ready(function(){
					Swal.fire({
							title: "Thành công",
							text: "Quy đổi thành công! Gặp NPC Siêu Nhân tại Siêu thị để nhận vật phẩm",
							icon: "success",
							confirmButtonText: "OK",
					})
					.then((result) => {
						if (result.isConfirmed) {
							window.location.href = "quydoi";
						}
					});
				});
				</script>
				';
			} else {
				echo '
				<script type="text/javascript">
				$(document).ready(function(){
					Swal.fire({
						title: "Thất bại",
						text: "Quy đổi thất bại, vui lòng thử lại !",
						icon: "error",
						confirmButtonText: "OK",
					}).then((result) => {
						if (result.isConfirmed) {
							window.location.href = "quydoi";
						}
					});
				});
				</script>
				';
			}
		} else {
			echo '
			<script type="text/javascript">
			$(document).ready(function(){
				Swal.fire({
					title: "Thất bại",
					text: "Quy đổi thất bại, vui lòng thử lại !",
					icon: "error",
					confirmButtonText: "OK",
				}).then((result) => {
					if (result.isConfirmed) {
						window.location.href = "quydoi";
					}
				});
			});
			</script>
			';
		}
	}
} else {
	echo '
	<script type="text/javascript">
	$(document).ready(function(){
		Swal.fire({
			title: "Sai thông tin",
			text: "Vui lòng nhập đúng thông tin !",
			icon: "error",
			confirmButtonText: "OK",
		}).then((result) => {
			if (result.isConfirmed) {
				window.location.href = "quydoi";
			}
		});
	});
	</script>
	';
}
include_once 'quydoi.php';
