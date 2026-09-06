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

$conn = SQL();
$idgiftcode = intval($_GET['id']);
$giftcode = null;

$result = $conn->query("SELECT * FROM `gift_codes` WHERE `id` = $idgiftcode");
if ($result) {
    $giftcode = $result->fetch_assoc();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $giftcode_value = $_POST['giftcode'] ?? '';
    $items = $_POST['items'] ?? '';
    $coin = $_POST['coin'] ?? '';
    $gold = $_POST['gold'] ?? '';
    $yen = $_POST['yen'] ?? '';
    $stmt = $conn->prepare("UPDATE `gift_codes` SET `code` = ?, `items` = ?, `coin` = ?, `gold` = ?, `yen` = ? WHERE `id` = ?");
    $stmt->bind_param("sssssi", $giftcode_value, $items, $coin, $gold, $yen, $idgiftcode);

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
    $message = $_SESSION['update_message'];
    unset($_SESSION['update_message']); 
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Chỉnh Sửa Mã Quà Tặng</title>
</head>
<body>
    <div class="box-nf" id="box-nf"></div>
    <script type="text/javascript" src="../JavaScript/box.js"></script>
    <script language="javascript" src="../JavaScript/jquery-2.0.0.min.js"></script>
    <div class="bg-content" style="  border-radius: 1rem; padding:10px">
        <div style="text-align:center;">
            <h4><?php echo htmlspecialchars($user['username']); ?> đang sửa mã quà tặng</h4>
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
        <h4>Chỉnh Sửa</h4>
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
                            <td role="cell"><input type="text" class="box-text" id="giftcode" name="giftcode" placeholder="Tên mã quà tặng" value="<?php echo htmlspecialchars($giftcode['code']); ?>" /></td>
                        </tr>
                        <tr role="row">
                            <td>Vật Phẩm Code:</td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm mb-2" onclick="gcPickItems()"><i class="fa-solid fa-box-open"></i> Chọn vật phẩm</button>
                                <div id="gc-item-preview" class="d-flex flex-wrap gap-2 mb-2"></div>
                                <textarea class="box-text" id="items" name="items" oninput="gcRenderPreview()" style="height:120px"><?php echo htmlspecialchars($giftcode['items']); ?></textarea>
                            </td>
                        </tr>
                        <tr role="row">
                            <td>Xu Code:</td>
                            <td><input type="text" class="box-text" id="coin" name="coin" value="<?php echo htmlspecialchars($giftcode['coin']); ?>" /></td>
                        </tr>
                        <tr role="row">
                            <td>Lượng Code:</td>
                            <td><input type="text" class="box-text" id="gold" name="gold" value="<?php echo htmlspecialchars($giftcode['gold']); ?>" /></td>
                        </tr>
                        <tr role="row">
                            <td>Yên Code:</td>
                            <td><input type="text" class="box-text" id="yen" name="yen" value="<?php echo htmlspecialchars($giftcode['yen']); ?>" /></td>
                        </tr>
                        <tr role="row">
                            <td colspan="2">
                                <input type="hidden" name="code_id" value="<?php echo htmlspecialchars($idgiftcode); ?>" />
                                <button class="btn btn-success w-100 fw-semibold" type="submit">Cập nhật</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    <script src="/static/js/item-picker.js"></script>
    <script>
        function gcRenderPreview() {
            var raw = document.getElementById('items').value || '[]';
            var arr = [];
            try { arr = JSON.parse(raw); } catch (e) { arr = []; }
            var prev = document.getElementById('gc-item-preview');
            if (!prev || !arr.length) { if (prev) prev.innerHTML = ''; return; }
            ItemPicker.ready(function () {
                prev.innerHTML = arr.map(function (it) {
                    return '<div class="border rounded p-1 text-center" style="width:96px">'
                        + ItemPicker.img(it.id, 48)
                        + '<div class="small" style="font-size:10px">' + ItemPicker.label(it.id) + '</div>'
                        + '<span class="badge bg-primary" style="font-size:10px">x' + (it.quantity || 1) + (it.upgrade ? ' +' + it.upgrade : '') + '</span></div>';
                }).join('');
            });
        }
        function gcPickItems() {
            var existing = [];
            try { existing = JSON.parse(document.getElementById('items').value || '[]'); } catch (e) { existing = []; }
            ItemPicker.open({ mode: 'giftcode', target: 'items', existing: existing, onDone: gcRenderPreview });
        }
        document.addEventListener('DOMContentLoaded', gcRenderPreview);
    </script>
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
