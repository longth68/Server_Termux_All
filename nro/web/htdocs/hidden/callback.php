<?php
include_once 'set.php';

$cb = fopen('../logs/callback.log', 'a') or die("cant open file");

$validate = ValidateCallback($_POST);
if ($validate != false) { // Nếu xác thực callback đúng thì chạy vào đây.
    fwrite($cb, "Username: " . $result['uid'] . "|validate: true|");
    $status = $validate['status']; // Trạng thái thẻ nạp
    $serial = $validate['serial']; // Số serial của thẻ.
    $pin = $validate['code']; // Mã pin của thẻ.
    $card_type = $validate['telco']; // Loại thẻ
    $amount = $validate['amount']; // Mệnh giá của thẻ
    $declared = $validate['declared_value'] * 1; // Mệnh giá được khai báo
    $content = $validate['trans_id']; // ID transaction 
    $sign = md5($partnerKey . $pin . $serial); // Sign

    if ($sign != $validate['callback_sign']) { // Nếu sign không trùng
        fwrite($cb, "trans_id: " . $content . ", Sai chữ ký|");
        die('Sai chữ ký');
    } else {
        fwrite($cb, "trans_id: " . $content . ", Chữ ký đúng|");
    }

    $query_str = "SELECT * FROM `naptien` WHERE tinhtrang = '0' AND tranid = '{$content}' AND code = '{$pin}' AND seri = '{$serial}'";
    fwrite($cb, "query: " . $query_str . "|");
    $result = _query($query_str);
    if ($result->num_rows <= 0) {
        $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
        $result = _query($query_str);
    }

    if ($result->num_rows > 0) {
        $result = $result->fetch_array(MYSQLI_ASSOC);
        fwrite($cb, "status: " . $status . "\n\n");
        if ($status == '1') {
            // Xử lý nạp thẻ thành công tại đây.
            $ticket = $declared / 10000;

            // Nhân đôi số tiền nạp vào
            $doubleAmount = $declared * 1;

            _query("UPDATE `account` SET vnd = vnd + {$doubleAmount}, tongnap = tongnap + {$doubleAmount} WHERE username = '{$result['uid']}'");
            _query("UPDATE `naptien` SET `tinhtrang` = 1 WHERE `id` = {$result['id']}"); // chuyển cho kết quả thành công      
        } else if ($status == '2') {
            // Xử lý nạp thẻ sai mệnh giá tại đây.
            _query("UPDATE `naptien` SET `tinhtrang` = 2, `vnd` = {$amount} WHERE `id` = {$result['id']}"); // thất bại   
        } else {
            // Xử lý nạp thẻ thất bại tại đây.
            _query("UPDATE `naptien` SET `tinhtrang` = 3 WHERE `id` = {$result['id']}"); // thất bại   
        }
    } else {
        fwrite($cb, "trans_id: " . $content . ", query: " . $result->num_rows . "\n\n");
    }
} else {
    fwrite($cb, "validate: false\n");
}
fclose($cb);

function ValidateCallback($out)
{ // Hàm kiểm tra callback từ server
    $jsonData = file_get_contents('php://input');
    $jsonArray = json_decode($jsonData, true);
    if (isset(
        $jsonArray['status'],
        $jsonArray['message'],
        $jsonArray['request_id'],
        $jsonArray['declared_value'],
        $jsonArray['value'],
        $jsonArray['amount'],
        $jsonArray['code'],
        $jsonArray['serial'],
        $jsonArray['telco'],
        $jsonArray['trans_id'],
        $jsonArray['callback_sign']
    )) {
        return $jsonArray; // Xác thực thành công
    } else {
        return false; // Xác thực callback thất bại
    }
}
