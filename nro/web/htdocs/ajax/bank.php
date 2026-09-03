<?php
include_once '../hidden/set.php';
require_once '../vendor/autoload.php';

if (!isset($_POST['pay_amount'])) {
	header("location:/error.php");
}

sleep(1);

use PayOS\PayOS;
$payOS = new PayOS($client_Id, $api_Key, $checksum_Key);

$menhgia = intval($conn->real_escape_string(strip_tags(addslashes($_POST['pay_amount']))));

$data = [
    "orderCode" => intval(substr(strval(microtime(true) * 10000), -6)),
    "amount" => $menhgia,
    "description" => "Thanh toan " . $sv_name,
    "returnUrl" => $DOMAIN . "/success.php",
    "cancelUrl" => $DOMAIN . "/cancel.php"
];

try {
    $_SESSION['orderCode'] = $data['orderCode'];
    $_SESSION['amount'] = $data['amount'];

    $response = $payOS->createPaymentLink($data);
	$checkoutUrl = $response['checkoutUrl'];
	echo '<script>window.location.href = "' . $checkoutUrl . '";</script>';
} catch (\Throwable $th) {
	echo '<script type="text/javascript">toastr.error("' . $th->getMessage() . '");</script>';
}