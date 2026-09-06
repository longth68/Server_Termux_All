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
$msg = '';

// Danh sách cửa hàng
$stores = [];
$r = $conn->query("SELECT `id`, `name` FROM `stores` ORDER BY `id` ASC");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $stores[(int)$row['id']] = $row['name'];
    }
}

// Ánh xạ item id -> name/icon để hiển thị
$itemMeta = [];
$rm = $conn->query("SELECT `id`, `name`, `icon` FROM `item`");
if ($rm) {
    while ($row = $rm->fetch_assoc()) {
        $itemMeta[(int)$row['id']] = ['name' => $row['name'], 'icon' => (int)$row['icon']];
    }
}
function shopImg($id, $size = 40) {
    global $itemMeta;
    if (!isset($itemMeta[$id]) || $itemMeta[$id]['icon'] <= 0) return '';
    return '<img src="/images/1/Small' . $itemMeta[$id]['icon'] . '.png" width="' . $size . '" height="' . $size . '" style="image-rendering:pixelated;vertical-align:middle;margin-right:6px" onerror="this.style.display=\'none\'">';
}
function shopName($id) {
    global $itemMeta;
    return isset($itemMeta[$id]) ? $itemMeta[$id]['name'] : ('#' . $id);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $store = isset($_POST['store']) ? intval($_POST['store']) : 0;

    if ($action == 'shop_add') {
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $coin = isset($_POST['coin']) ? intval($_POST['coin']) : 0;
        $gold = isset($_POST['gold']) ? intval($_POST['gold']) : 0;
        $yen = isset($_POST['yen']) ? intval($_POST['yen']) : 0;
        $lock = isset($_POST['lock']) ? 1 : 0;
        $expire = isset($_POST['expire']) ? intval($_POST['expire']) : 0;
        $sys = isset($_POST['sys']) ? intval($_POST['sys']) : 0;
        if ($item_id > 0 && $store > 0) {
            $stmt = $conn->prepare("INSERT INTO `store_data` (`item_id`, `sys`, `store`, `lock`, `coin`, `gold`, `yen`, `expire`, `options`) VALUES (?,?,?,?,?,?,?,?,?)");
            $opt = '[]';
            $stmt->bind_param("iiiiiiiss", $item_id, $sys, $store, $lock, $coin, $gold, $yen, $expire, $opt);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success">Đã thêm "' . htmlspecialchars(shopName($item_id)) . '" vào cửa hàng. Bấm "Tải lại Cửa hàng" để áp dụng trong game.</div>';
            } else {
                $msg = '<div class="alert alert-danger">Lỗi thêm item.</div>';
            }
            $stmt->close();
        } else {
            $msg = '<div class="alert alert-warning">Chọn cửa hàng + ID vật phẩm hợp lệ.</div>';
        }
    } elseif ($action == 'shop_del') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM `store_data` WHERE `id` = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $msg = '<div class="alert alert-success">Đã xóa khỏi DB. Bấm "Tải lại Cửa hàng" để áp dụng.</div>';
        }
    } elseif ($action == 'shop_price') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $coin = isset($_POST['coin']) ? intval($_POST['coin']) : 0;
        $gold = isset($_POST['gold']) ? intval($_POST['gold']) : 0;
        $yen = isset($_POST['yen']) ? intval($_POST['yen']) : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE `store_data` SET `coin` = ?, `gold` = ?, `yen` = ? WHERE `id` = ?");
            $stmt->bind_param("iiii", $coin, $gold, $yen, $id);
            $stmt->execute();
            $stmt->close();
            $msg = '<div class="alert alert-success">Đã cập nhật giá. Bấm "Tải lại Cửa hàng" để áp dụng.</div>';
        }
    } elseif ($action == 'shop_reload') {
        $cmd = 'SHOP_RELOAD';
        $data = '{}';
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES (?, ?, 0)");
        $stmt->bind_param("ss", $cmd, $data);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh tải lại Cửa hàng. Server áp dụng trong vài giây.</div>';
        }
        $stmt->close();
    }
    if ($store > 0) {
        header('Location: /admin/shop?store=' . $store);
        exit;
    }
}

$cur = isset($_GET['store']) ? intval($_GET['store']) : (empty($stores) ? 0 : intval(array_key_first($stores)));
$items = [];
if ($cur > 0) {
    $stmt = $conn->prepare("SELECT `id`, `item_id`, `sys`, `lock`, `coin`, `gold`, `yen`, `expire` FROM `store_data` WHERE `store` = ? ORDER BY `id` ASC");
    $stmt->bind_param("i", $cur);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
    }
    $stmt->close();
}
$conn->close();
?>
<div class="admin-panel">
<style>
    .admin-panel { background: #f4f6f9; color: #212529; padding: 14px; border-radius: 8px; }
    .admin-panel .card { background: #fff; color: #212529; border: 1px solid #ddd; box-shadow: 0 1px 3px rgba(0,0,0,.08); border-radius: 6px; }
    .admin-panel .text-white { color: #212529 !important; }
    .admin-panel .table { color: #212529; }
    .admin-panel .table th, .admin-panel .table td { color: #212529; border-color: #dee2e6; }
    .admin-panel h4, .admin-panel h5, .admin-panel h6 { color: #212529; }
    .admin-panel .form-control, .admin-panel .form-select { background: #fff; color: #212529; border: 1px solid #ced4da; }
    .admin-panel .text-muted { color: #6c757d !important; }
    .admin-panel .nav-pills .nav-link { color: #0d6efd; }
    .admin-panel .nav-pills .nav-link.active { background: #0d6efd; color: #fff; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0 fw-bold"><i class="fa-solid fa-shop text-info"></i> Shop Editor</h4>
    <div class="d-flex gap-2">
        <a class="btn btn-success btn-sm" href="/admin/home">Quay lại</a>
        <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="shop_reload">
            <input type="hidden" name="store" value="<?= $cur ?>">
            <button type="submit" class="btn btn-warning btn-sm"><i class="fa-solid fa-rotate"></i> Tải lại Cửa hàng</button>
        </form>
    </div>
</div>
<?php if ($msg) echo $msg; ?>

<div class="card p-2 mb-3">
    <ul class="nav nav-pills flex-wrap">
        <?php foreach ($stores as $sid => $sname): ?>
            <li class="nav-item"><a class="nav-link <?= $sid == $cur ? 'active' : '' ?>" href="/admin/shop?store=<?= $sid ?>"><?= htmlspecialchars($sname) ?> (<?= $sid ?>)</a></li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="card p-3 mb-3">
    <h6 class="fw-bold">Thêm vật phẩm vào cửa hàng: <?= htmlspecialchars($stores[$cur] ?? ('#' . $cur)) ?></h6>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="action" value="shop_add">
        <input type="hidden" name="store" value="<?= $cur ?>">
        <div class="col-6 col-md-2"><label class="form-label small mb-0">ID vật phẩm</label><input type="number" name="item_id" id="sh_item_id" class="form-control form-control-sm" min="1" required></div>
        <div class="col-6 col-md-1"><button type="button" class="btn btn-info btn-sm w-100" onclick="ItemPicker.open({mode:'single',target:'sh_item_id'})"><i class="fa-solid fa-box-open"></i></button></div>
        <div class="col-6 col-md-1"><label class="form-label small mb-0">Xu</label><input type="number" name="coin" class="form-control form-control-sm" value="0"></div>
        <div class="col-6 col-md-1"><label class="form-label small mb-0">Lượng</label><input type="number" name="gold" class="form-control form-control-sm" value="0"></div>
        <div class="col-6 col-md-1"><label class="form-label small mb-0">Yên</label><input type="number" name="yen" class="form-control form-control-sm" value="0"></div>
        <div class="col-6 col-md-1"><label class="form-label small mb-0">sys</label><input type="number" name="sys" class="form-control form-control-sm" value="0"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Hạn (giây, 0=vĩnh viễn)</label><input type="number" name="expire" class="form-control form-control-sm" value="0"></div>
        <div class="col-6 col-md-1"><label class="form-check-label small">Khóa</label><input type="checkbox" name="lock" class="form-check-input"></div>
        <div class="col-6 col-md-2"><button type="submit" class="btn btn-success btn-sm w-100">Thêm</button></div>
    </form>
</div>

<div class="card p-3">
    <h6 class="fw-bold">Vật phẩm trong cửa hàng (<?= count($items) ?>)</h6>
    <?php if (count($items) > 0): ?>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead><tr class="fw-bold text-uppercase"><th>ID</th><th>Vật phẩm</th><th>Xu</th><th>Lượng</th><th>Yên</th><th>sys</th><th>Khóa</th><th>Hạn</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): $iid = (int)$it['item_id']; ?>
                <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <td><?= shopImg($iid) ?><span class="fw-semibold"><?= htmlspecialchars(shopName($iid)) ?></span> <small class="text-muted">#<?= $iid ?></small></td>
                    <td><?= number_format((int)$it['coin']) ?></td>
                    <td><?= number_format((int)$it['gold']) ?></td>
                    <td><?= number_format((int)$it['yen']) ?></td>
                    <td><?= (int)$it['sys'] ?></td>
                    <td><?= (int)$it['lock'] ? '🔒' : '' ?></td>
                    <td><?= (int)$it['expire'] > 0 ? (int)$it['expire'] : '∞' ?></td>
                    <td>
                        <form method="POST" class="d-inline-flex gap-1 align-items-center">
                            <input type="hidden" name="action" value="shop_price">
                            <input type="hidden" name="store" value="<?= $cur ?>">
                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                            <input type="number" name="coin" value="<?= (int)$it['coin'] ?>" class="form-control form-control-sm" style="width:80px">
                            <input type="number" name="gold" value="<?= (int)$it['gold'] ?>" class="form-control form-control-sm" style="width:80px">
                            <input type="number" name="yen" value="<?= (int)$it['yen'] ?>" class="form-control form-control-sm" style="width:80px">
                            <button type="submit" class="btn btn-primary btn-sm">Lưu giá</button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="shop_del">
                            <input type="hidden" name="store" value="<?= $cur ?>">
                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa <?= htmlspecialchars(shopName($iid)) ?> khỏi cửa hàng?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="text-center text-muted py-3">Cửa hàng trống.</div>
    <?php endif; ?>
</div>
</div>
<script src="/static/js/item-picker.js"></script>
