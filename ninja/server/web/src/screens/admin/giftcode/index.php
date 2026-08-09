<?php
require_once(__DIR__ . '/../../../../core/configs.php');

if (!isset($_SESSION['user'])) {
    header('Location: /home');
    exit;
}

$user = $_SESSION['user'];
if ($user['admin_web'] != 1) {
    header("Location: /home");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $conn = SQL();
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM `gift_codes` WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header('Location: /admin/code');
        exit;
    } else {
        echo "Có lỗi xảy ra. Không thể xóa mã quà tặng.";
    }
}

$conn = SQL();
$result = $conn->query("SELECT * FROM `gift_codes`; ");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Mã Quà Tặng</title>
</head>
<body>
    <div class="box-nf" id="box-nf"></div>
    <script type="text/javascript" src="../JavaScript/box.js"></script>
    <script language="javascript" src="../JavaScript/jquery-2.0.0.min.js"></script>
    <div class="bg-content" style="  border-radius: 1rem; padding:10px">
        <div style="text-align:center;">
            <h4><?php echo htmlspecialchars($user['username']); ?> tạo mã quà tặng</h4>
        </div>
        <div class="container mb-2">
            <div class="row text-center justify-content-center g-2 mt-1">
                <div class="col-12 col-md-4 col-lg-3">
                    <a class="btn btn-success w-100 fw-semibold" href="/admin/home">Quay lại</a>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <a class="btn btn-success w-100 fw-semibold" href="/admin/create">Tạo Mới</a>
                </div>
            </div>
        </div>
        <div>
            <h4>Tổng có: <?php echo number_format($result->num_rows); ?></h4>
        </div>

        <table class="table text-white fw-semibold mb-0" role="table">
            <thead>
                <tr class="text-start fw-bold text-uppercase gs-0">
                    <th>#</th>
                    <th>Mã</th>
                    <th>Hạn Sử Dụng</th>
                    <th>Chỉnh Sửa</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                while ($code = $result->fetch_assoc()) {
                    echo '
                        <tr id="code' . htmlspecialchars($code["id"]) . '">
                            <td>' . $i . '</td>
                            <td>' . htmlspecialchars($code["code"]) . '</td>
                            <td>' . htmlspecialchars($code["expires_at"] ?? "") . '</td>
                            <td>
                                <a class="btn btn-primary" href="/admin/edit-giftcode?id=' . htmlspecialchars($code["id"]) . '">Chi tiết</a> - 
                                <form method="POST" action="/admin/code" style="display:inline;">
                                    <input type="hidden" name="id" value="' . htmlspecialchars($code["id"]) . '">
                                    <button type="submit" class="btn btn-danger">Xóa Bỏ</button>
                                </form>
                            </td>
                        </tr>
                    ';
                    $i++;
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
