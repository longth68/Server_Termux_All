<?php
require_once(__DIR__ . '/../../../../core/configs.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['admin_web'] != 1) {
    header('Location: /home');
    exit;
}

$conn = SQL();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $giftcode = $_POST['code'] ?? '';
    $items = $_POST['items'] ?? '';
    $luong = $_POST['luong'] ?? '';
    $xu = $_POST['xu'] ?? '';
    $yen = $_POST['yen'] ?? '';
    $luot_nhap = $_POST['luot_nhap'] ?? '';
    $server_id = 1; 

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `gift_codes` WHERE `code` = ?");
    if ($stmt) {
        $stmt->bind_param("s", $giftcode);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $_SESSION['update_message'] = '<div class="alert alert-danger" role="alert">Mã quà tặng đã tồn tại. Vui lòng tạo mã khác.</div>';
        } else {
            $stmt = $conn->prepare("INSERT INTO `gift_codes` (`server_id`, `code`, `items`, `gold`, `coin`, `yen`, `type`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issssss", $server_id, $giftcode, $items, $luong, $xu, $yen, $luot_nhap);

                if ($stmt->execute()) {
                    $_SESSION['update_message'] = '<div class="alert alert-success" role="alert">Thêm mã quà tặng thành công.</div>';
                    header("Location: /admin/create"); 
                    exit();
                } else {
                    $_SESSION['update_message'] = '<div class="alert alert-danger" role="alert">Thêm mã quà tặng thất bại.</div>';
                }
                $stmt->close();
            } else {
                $_SESSION['update_message'] = '<div class="alert alert-danger" role="alert">Lỗi chuẩn bị câu lệnh SQL.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-danger" role="alert">Lỗi chuẩn bị câu lệnh SQL kiểm tra mã.</div>';
    }
}

if (isset($_SESSION['update_message'])) {
    $message = $_SESSION['update_message'];
    unset($_SESSION['update_message']); 
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Mới Mã Quà Tặng</title>
</head>
<body>
    <div class="box-nf" id="box-nf"></div>
    <script type="text/javascript" src="../JavaScript/box.js"></script>
    <script src="../JavaScript/jquery-2.0.0.min.js"></script>
    <div class="bg-content" style="  border-radius: 1rem; padding:10px">
        <div style="text-align:center;">
            <h4><?php echo htmlspecialchars($_SESSION['user']['username']); ?> tạo mã quà tặng</h4>
        </div>
        <div class="container mb-2">
            <div class="row text-center justify-content-center g-2 mt-1">
                <div class="col-12 col-md-4 col-lg-3">
                    <a class="btn btn-success w-100 fw-semibold" href="/admin/code">Quay lại</a>
                </div>
            </div>
        </div>
    </div>
    <div class="title">
        <h4>Thêm Mới</h4>
    </div>
    <?php if (isset($message)) {
        echo $message;
    } ?>
    <div class="mt-2">
        <div class="table-responsive mb-4" style="  border-radius: 1rem;">
            <form method="POST" action="">
                <table class="table text-white fw-semibold mb-0" role="table" width="100%">
                    <tbody class="fw-semibold" role="rowgroup">
                        <tr role="row">
                            <td role="cell">Mã Quà Tặng:</td>
                            <td role="cell"><input type="text" class="box-text" id="code" name="code" placeholder="Tên mã quà tặng" required /></td>
                        </tr>
                        <tr role="row">
                            <td>ID Item:</td>
                            <td><input type="text" class="box-text" id="items" name="items" value="[]" placeholder="ID Item" required /></td>
                        </tr>
                        <tr role="row">
                            <td role="cell">Lượt Nhập:</td>
                            <td role="cell">
                                <select class="box-text" id="luot_nhap" name="luot_nhap" required>
                                    <option value="0">1 Lượt nhập</option>
                                    <option value="1">Nhiều lượt nhập</option>
                                </select>
                            </td>
                        </tr>
                        <tr role="row">
                            <td>Lượng Code:</td>
                            <td><input type="text" class="box-text" id="luong" name="luong" placeholder="Nhập Lượng Cho Code" required /></td>
                        </tr>
                        <tr role="row">
                            <td>Xu Code:</td>
                            <td><input type="text" class="box-text" id="xu" name="xu" placeholder="Nhập Xu Cho Code" required /></td>
                        </tr>
                        <tr role="row">
                            <td>Yên Code:</td>
                            <td><input type="text" class="box-text" id="yen" name="yen" placeholder="Nhập Yên Cho Code" required /></td>
                        </tr>
                        <tr role="row">
                            <td colspan="2">
                                <button class="btn btn-success w-100 fw-semibold" type="submit">Thêm Mới</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</body>
</html>

<style>
    input[type="text"],
    input[type="number"],
    input[type="datetime-local"],
    textarea {
        width: 100%;
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        font-size: 16px;
        margin-bottom: 10px;
    }

    textarea {
        height: 200px;
        resize: vertical;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .label {
        display: block; 
        margin-bottom: 5px;
        font-weight: bold;
        font-size: 16px;
    }
</style>
