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

$message = '';
$conn = SQL();
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM `users` WHERE `id` = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $lock = $_POST['status'] ?? '';
    $email = $_POST['email'] ?? '';
    $active = $_POST['activated'] ?? '';
    $yen = $_POST['yen'] ?? '';
    $xu = $_POST['xu'] ?? '';
    $xu_khoa = $_POST['xuInBox'] ?? '';
    $luong = $_POST['luong'] ?? '';
    $item = $_POST['bag'] ?? '';
    $ruongItem = $_POST['box'] ?? '';
    $ruongTB = $_POST['data'] ?? '';

    $updateQuery = "UPDATE `players` SET
                    yen = ?,
                    xu = ?,
                    xuInBox = ?,
                    Bag = ?,
                    Box = ?,
                    data = ?
                    WHERE user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("iiisssi", $yen, $xu, $xu_khoa, $item, $ruongItem, $ruongTB, $user_id);

    if ($stmt->execute()) {
        $_SESSION['update_message'] = '<div class="alert alert-success" role="alert">Cập nhật thành công.</div>';
    } else {
        $message = '<div class="alert alert-danger" role="alert">Cập nhật thất bại. Lỗi: ' . $stmt->error . '</div>';
    }
    $stmt->close();

    $stmt = $conn->prepare("UPDATE `users` SET 
                            `username` = ?, 
                            `password` = ?, 
                            `status` = ?, 
                            `activated` = ?, 
                            `email` = ?, 
                            `luong` = ? 
                            WHERE `id` = ?");
    $stmt->bind_param("ssssssi", $uname, $password, $lock, $active, $email, $luong, $user_id);

    if ($stmt->execute()) {
        $_SESSION['update_message'] = '<div class="alert alert-success" role="alert">Cập nhật thành công.</div>';
    } else {
        $message = '<div class="alert alert-danger" role="alert">Cập nhật thất bại. Lỗi: ' . $stmt->error . '</div>';
    }
    $stmt->close();

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

if (isset($_SESSION['update_message'])) {
    echo $_SESSION['update_message'];
    unset($_SESSION['update_message']);
}
?>




<body>
    <div class="box-nf" id="box-nf"></div>
    <script type="text/javascript" src="../JavaScript/box.js"></script>
    <script language="javascript" src="../JavaScript/jquery-2.0.0.min.js"></script>
    <div class="bg-content" style="  border-radius: 1rem; padding:10px">
        <div style="text-align:center;">
            <h4>Đang chỉnh sửa thành viên <?php echo htmlspecialchars($user['username']); ?></h4>
        </div>

        <div class="container mb-2">
            <div class="row text-center justify-content-center g-2 mt-1">
                <div class="col-12 col-md-4 col-lg-3">
                    <a class="btn btn-success w-100 fw-semibold" href="/admin/member">Quay lại</a>
                </div>
            </div>
        </div>
    </div>
    <div class="title">
        <h4>Chỉnh Sửa</h4>
    </div>
    <?php if ($message) echo $message; ?>
    <div class="mt-2">
        <div class="table-responsive mb-4" style="  border-radius: 1rem;">
            <form method="POST" action="">
                <?php
                if ($result->num_rows == 0) {
                    echo '<p>Tài khoản không tồn tại</p>';
                } else {
                    $result = $conn->query("SELECT * FROM `players` WHERE `user_id` = " . $user['id'] . "; ");
                    $army = $result->fetch_assoc();
                    if (!$army) {
                        $army = [
                            'xu' => 0,
                            'xuInBox' => 0,
                            'yen' => 0,
                            'bag' => '',
                            'box' => '',
                            'data' => ''
                        ];
                    }
                        if ($user['online'] != 0) {
                            echo '<p style="color:green">Tài khoản có đăng nhập nếu sửa chữa dữ liệu có thể không có tác dụng</p>';
                        }
                ?>
                        <table class="table text-white fw-semibold mb-0" role="table" width="100%">
                            <tbody class="fw-semibold" role="rowgroup">
                                <tr role="row">
                                    <td role="cell">Tài khoản:</td>
                                    <td role="cell"><input type="text" class="box-text" id="username" name="username" placeholder="Tên tài khoản" value="<?php echo htmlspecialchars($user['username']); ?>" /></td>
                                </tr>
                                <tr role="row">
                                    <td>Mật khẩu:</td>
                                    <td><input type="text" class="box-text" id="password" name="password" value="<?php echo htmlspecialchars($user['password']); ?>" placeholder="Mật khẩu" /></td>
                                </tr>
                                <tr role="row">
                                    <td>Email:</td>
                                    <td><input type="email" class="box-text" id="email" placeholder="E-mail" value="<?php echo htmlspecialchars($user['email']); ?>" name="email" /></td>
                                </tr>
                                <tr role="row">
                                    <td>Kích Hoạt:</td>
                                    <td><input type="text" class="box-text" id="activated" placeholder="Kích hoạt" value="<?php echo htmlspecialchars($user['activated']); ?>" name="activated" /></td>
                                </tr>
                                <tr role="row">
                                    <td>Khoá tài khoản:</td>
                                    <td><input type="text" class="box-text" id="status" placeholder="Khoá tài khoản" value="<?php echo htmlspecialchars($user['status']); ?>" name="status" /></td>
                                </tr>
                                <tr role="row">
                                    <td>Xu:</td>
                                    <td><input type="text" class="box-text" id="xu" placeholder="Xu" value="<?php echo htmlspecialchars($army['xu']); ?>" name="xu" /></td>
                                </tr>
                                <tr role="row">
                                    <td>Xu khoá:</td>
                                    <td><input type="text" class="box-text" id="xuInBox" placeholder="Xu khoá" value="<?php echo htmlspecialchars($army['xuInBox']); ?>" name="xuInBox" /></td>
                                </tr>
                                <tr role="row">
                                    <td>Luong:</td>
                                    <td><input type="text" class="box-text" id="luong" placeholder="Lương" value="<?php echo htmlspecialchars($user['luong']); ?>" name="luong" /></td>
                                </tr>
                                <tr role="row">
                                    <td>Yên:</td>
                                    <td><input type="text" class="box-text" id="yen" placeholder="Level" value="<?php echo htmlspecialchars($army['yen']); ?>" name="yen" /></td>
                                </tr>
                                <tr role="row">
                                    <td>ItemBag:</td>
                                    <td><textarea id="bag" name="bag"><?php echo htmlspecialchars($army['bag']); ?></textarea></td>
                                </tr>
                                <tr role="row">
                                    <td>ItemBox:</td>
                                    <td><textarea id="box" name="box"><?php echo htmlspecialchars($army['box']); ?></textarea></td>
                                </tr>
                                <tr role="row">
                                    <td>Data:</td>
                                    <td><textarea id="data" name="data"><?php echo htmlspecialchars($army['data']); ?></textarea></td>
                                </tr>
                                <tr role="row">
                                    <td colspan="2">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_id); ?>" />
                                        <button class="btn btn-success w-100 fw-semibold" type="submit" class="btn btn-primary">Cập nhật</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                <?php
                    }
                ?>
            </form>
        </div>
    </div>
</body>
</html>

<style>
    input[type="text"],
    input[type="password"],
    input[type="email"] {
        width: 100%;
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        font-size: 16px;
        margin-bottom: 10px;
    }

    textarea {
        width: 100%;
        height: 500px !important;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        font-size: 16px;
        margin-bottom: 10px;
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