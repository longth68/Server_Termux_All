<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/core/configs.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: /home');
    exit;
}

$conn = SQL();

// Lấy danh sách gift code từ database
$result = $conn->query("SELECT id, code, yen, coin, gold, items FROM gift_codes;");

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã Quà Tặng</title>
    <style>
        /* Giao diện tổng thể */
        body {
            font-family: Arial, sans-serif;
            
            color: white;
            text-align: center;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: auto;
            
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        h4 {
            margin-bottom: 15px;
        }

        /* Bảng dữ liệu */
        .table-container {
            width: 100%;
            overflow-x: auto; /* Hỗ trợ cuộn ngang nếu cần */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #444;
            text-align: center;
            white-space: nowrap; /* Tránh xuống dòng không cần thiết */
        }

        

        /* Cột "Vật phẩm" */
        .item-column {
            white-space: normal;
            max-width: 300px;
            word-wrap: break-word;
            text-align: left;
        }

        /* Responsive cho màn hình nhỏ */
        @media (max-width: 768px) {
            th, td {
                padding: 8px;
                font-size: 14px;
            }
            .item-column {
                max-width: 200px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h4>Tổng số Giftcode: <?php echo number_format($result->num_rows); ?></h4>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Mã</th>
                    <th>Yên</th>
                    <th>Xu</th>
                    <th>Lượng</th>
                    <th class="item-column">Vật phẩm</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                while ($code = $result->fetch_assoc()) {
                    $items = json_decode($code["items"], true);
                    $item_ids = [];

                    // Lọc danh sách ID vật phẩm
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            if (isset($item['id'])) {
                                $item_ids[] = intval($item['id']);
                            }
                        }
                    }

                    $item_names = "Không có vật phẩm";
                    if (!empty($item_ids)) {
                        $ids_str = implode(",", $item_ids);

                        // Lấy tên vật phẩm từ database
                        $item_query = $conn->query("SELECT id, name FROM item WHERE id IN ($ids_str)");
                        $item_list = [];

                        while ($item = $item_query->fetch_assoc()) {
                            $item_list[$item['id']] = $item["name"];
                        }

                        // Hiển thị danh sách vật phẩm
                        $display_items = [];
                        foreach ($item_ids as $item_id) {
                            if (isset($item_list[$item_id])) {
                                $display_items[] = $item_list[$item_id];
                            }
                        }

                        if (!empty($display_items)) {
                            $item_names = implode(", ", $display_items);
                        }
                    }

                    echo "
                        <tr>
                            <td>{$i}</td>
                            <td>" . htmlspecialchars($code["code"]) . "</td>
                            <td>" . htmlspecialchars($code["yen"]) . "</td>
                            <td>" . htmlspecialchars($code["coin"]) . "</td>
                            <td>" . htmlspecialchars($code["gold"]) . "</td>
                            <td class='item-column'>" . htmlspecialchars($item_names) . "</td>
                        </tr>";
                    $i++;
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
