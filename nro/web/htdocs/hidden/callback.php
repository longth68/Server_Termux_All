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
    $content = isset_sql($validate['trans_id']); // ID transaction 
    $pin = isset_sql($validate['code']);
    $serial = isset_sql($validate['serial']);
    $sign = md5($partnerKey . $validate['code'] . $validate['serial']); // Sign

    if ($sign != $validate['callback_sign']) { // Nếu sign không trùng
        fwrite($cb, "trans_id: " . $content . ", Sai chữ ký|");
        die('Sai chữ ký');
    } else {
        fwrite($cb, "trans_id: " . $content . ", Chữ ký đúng|");
    }

    // HASHIRAMA: bang topup thay cho naptien
    $query_str = "SELECT * FROM `topup` WHERE trangthai = 0 AND request_id = '{$content}' AND code = '{$pin}' AND seri = '{$serial}'";
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
            $doubleAmount = $declared * 1;

            _query("UPDATE `account` SET vnd = vnd + {$doubleAmount}, tongnap = tongnap + {$doubleAmount} WHERE username = '" . isset_sql($result['username']) . "'");
            _query("UPDATE `topup` SET `trangthai` = 1 WHERE `request_id` = '{$content}'"); // chuyển cho kết quả thành công      
        } else if ($status == '2') {
            // Xử lý nạp thẻ sai mệnh giá tại đây.
            _query("UPDATE `topup` SET `trangthai` = 2, `vnd` = {$amount} WHERE `request_id` = '{$content}'"); // thất bại   
        } else {
            // Xử lý nạp thẻ thất bại tại đây.
            _query("UPDATE `topup` SET `trangthai` = 3 WHERE `request_id` = '{$content}'"); // thất bại   
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
    global $partnerKey;
    $jsonData = file_get_contents('php://input');
    $jsonArray = json_decode($jsonData, true);
    if (!isset(
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
        return false;
    }
    // Verify signature
    $expectedSign = md5($partnerKey . $jsonArray['code'] . $jsonArray['serial']);
    if ($expectedSign !== $jsonArray['callback_sign']) {
        return false;
    }
    return $jsonArray;
}
