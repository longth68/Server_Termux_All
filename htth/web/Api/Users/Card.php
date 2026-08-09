<?php
include __DIR__ . '/../../Controllers/Connections.php';
include __DIR__ . '/../../Controllers/Sessions.php';
include __DIR__ . '/../../Controllers/Configs.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postData = json_decode(file_get_contents('php://input'), true);

    if ($_TrangThai == 0) {
        $response = array(
            'success' => false,
            'message' => 'Hệ thống nạp thẻ đang bảo trì, vui lòng thử lại sau!'
        );
        echo json_encode($response);
        exit;
    }

    if (empty($postData['amount']) || !isset($postData['username'])) {
        $response = array(
            'success' => false,
            'message' => 'Vui lòng nhập đầy đủ thông tin!'
        );
    } else {
        $amount = intval($postData['amount']);
        $username = $postData['username'];
        
        if ($amount < 10000) {
            $response = array(
                'success' => false,
                'message' => 'Số tiền nạp tối thiểu là 10.000đ!'
            );
        } else {
            // Lưu yêu cầu vào database (status = 99 - Chờ duyệt)
            $insert_query = "INSERT INTO napthe (user_nap, telco, serial, code, amount, status) VALUES (:user_nap, 'MANUAL', '', '', :amount, 99)";
            
            $stmt = $conn->prepare($insert_query);
            $stmt->bindParam(':user_nap', $username);
            $stmt->bindParam(':amount', $amount);

            if ($stmt->execute()) {
                $response = array(
                    'success' => true,
                    'message' => 'Đã gửi yêu cầu nạp tiền! Vui lòng chờ Admin kiểm tra và duyệt.'
                );
            } else {
                $response = array(
                    'success' => false,
                    'message' => 'Lỗi khi lưu dữ liệu vào máy chủ!'
                );
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
}