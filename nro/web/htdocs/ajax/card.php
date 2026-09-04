<?php
include_once '../hidden/set.php';

if (!isset($_POST['pin'], $_POST['serial'], $_POST['card_type'], $_POST['card_amount'])) {
	header("location:/error.php");
}

sleep(1);

$loaithe = $conn->real_escape_string(strip_tags(addslashes($_POST['card_type'])));
$pin = $conn->real_escape_string(strip_tags(addslashes($_POST['pin'])));
$seri = $conn->real_escape_string(strip_tags(addslashes($_POST['serial'])));
$menhgia = $conn->real_escape_string(strip_tags(addslashes($_POST['card_amount'])));
$requestId = rand(1000000, 9999999);
$sign = md5($partnerKey . $_POST['pin'] . $_POST['serial']);
$command = 'charging';

$url = "https://thesieure.com/chargingws/v2";

$data = array(
	'telco' => $loaithe,
	'code' => $pin,
	'serial' => $seri,
	'amount' => $menhgia,
	'request_id' => $requestId,
	'partner_id' => $partnerId,
	'sign' => $sign,
	'command' => $command
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$jsonData = json_decode($response);
$http_code = 0;
if (isset($jsonData->status)) {
	$http_code = 200;
}
curl_close($ch);

if ($http_code == 200) {
	if ($jsonData->status == '1') {
		echo '<script type="text/javascript">swal("Thành công", "Nạp thành công mệnh giá: "' . $jsonData->declared_value . '"! Refresh trang để cập nhật số tiền, "success");</script>';
	} else if ($jsonData->status == '2') {
		echo '<script type="text/javascript">toastr.error("Nạp thành công nhưng sai mệnh giá. Cư dân sẽ không được cộng tiền! Liên hệ ADMIN để được hỗ trợ");</script>';
	} else if ($jsonData->status == '3') {
		echo '<script type="text/javascript">toastr.error("Thẻ lỗi hoặc nhập sai giá trị ' . $sign . '");</script>';
	} else if ($jsonData->status == '4') {
		echo '<script type="text/javascript">toastr.error("Hệ thống nạp đang bảo trì!");</script>';
	} else if ($jsonData->status == '99') {
		$currentMillis = time();
		_query("INSERT INTO naptien(uid, vnd, seri, code, loaithe, noidung, tinhtrang, tranid, magioithieu) VALUES ('" . $_username . "','" . $menhgia . "','" . $seri . "','" . $pin . "','" . $loaithe . "','" . $_username . "','0','" . $jsonData->trans_id . "','0')");
		echo '<script type="text/javascript">toastr.success("Gửi thẻ thành công và đang chờ xử lý! Liên hệ ADMIN nếu không được cộng tiền sau 10 phút");</script>';
	} else {
		echo '<script type="text/javascript">toastr.error("' . $jsonData->message . '");</script>';
	}
} else {
	echo '<script type="text/javascript">toastr.error("Có lỗi máy chủ vui lòng thử lại sau!");</script>';
}
