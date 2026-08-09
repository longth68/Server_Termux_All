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

$itemNames = [];
$r = $conn->query("SELECT id, name, level FROM item ORDER BY id");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $itemNames[(int)$row['id']] = ['name' => $row['name'], 'level' => (int)$row['level']];
    }
}

// Tìm nhân vật
$char = null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $stmt = $conn->prepare("SELECT p.*, u.username FROM players p JOIN users u ON p.user_id = u.id WHERE p.name = ? LIMIT 1");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $res = $stmt->get_result();
    $char = $res->fetch_assoc();
    $stmt->close();
    if (!$char) {
        $msg = '<div class="alert alert-danger">Không tìm thấy nhân vật "'.htmlspecialchars($search).'".</div>';
    }
}

// Xử lý bỏ qua nhiệm vụ (chỉ hoạt động khi nhân vật đang online)
if ($char && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['skip_task'])) {
    $steps = max(1, min((int)($_POST['steps'] ?? 1), 10));
    $online = (int)$char['online'];
    if ($online !== 1) {
        $msg = '<div class="alert alert-warning">Nhân vật không online — không thể bỏ qua nhiệm vụ qua server. Yêu cầu người chơi vào game.</div>';
    } else {
        $data = json_encode(['name' => $search, 'steps' => $steps]);
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('SKIP_TASK', ?, 0)");
        $stmt->bind_param("s", $data);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh bỏ qua ' . $steps . ' nhiệm vụ cho ' . htmlspecialchars($search) . '. Server xử lý trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
        $stmt->close();
    }
}

// Xử lý thêm item vào túi
if ($char && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $itemId = (int)$_POST['item_id'];
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $bagField = $_POST['bag_field'] === 'box' ? 'box' : 'bag';
    if ($itemId > 0 && isset($itemNames[$itemId])) {
        $bag = json_decode($char[$bagField], true);
        if (!is_array($bag)) $bag = [];
        $newIndex = count($bag);
        $entry = [
            'isLock' => false, 'new' => true, 'yen' => 0,
            'quantity' => $quantity, 'expire' => -1,
            'created_at' => time() * 1000, 'updated_at' => time() * 1000,
            'index' => $newIndex, 'id' => $itemId, 'sys' => 0
        ];
        $bag[] = $entry;
        $json = json_encode($bag, JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("UPDATE players SET $bagField = ? WHERE id = ?");
        $stmt->bind_param("si", $json, $char['id']);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã thêm '.$itemNames[$itemId]['name'].' x'.$quantity.' vào '.( $bagField === 'box' ? 'rương' : 'túi đồ' ).'.</div>';
            $char[$bagField] = $json;
        } else {
            $msg = '<div class="alert alert-danger">Lỗi khi thêm item.</div>';
        }
        $stmt->close();
    }
}

// Xử lý xóa item khỏi túi
if ($char && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_item'])) {
    $idx = (int)$_POST['remove_item'];
    $bagField = $_POST['bag_field'] === 'box' ? 'box' : 'bag';
    $bag = json_decode($char[$bagField], true);
    if (is_array($bag) && isset($bag[$idx])) {
        array_splice($bag, $idx, 1);
        // cập nhật lại index
        foreach ($bag as $i => $it) { $bag[$i]['index'] = $i; }
        $json = json_encode($bag, JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("UPDATE players SET $bagField = ? WHERE id = ?");
        $stmt->bind_param("si", $json, $char['id']);
        $stmt->execute();
        $stmt->close();
        $msg = '<div class="alert alert-success">Đã xóa item khỏi '.($bagField === 'box' ? 'rương' : 'túi đồ').'.</div>';
        $char[$bagField] = $json;
    }
}

// Xử lý đổi số lượng item
if ($char && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_qty'])) {
    $idx = (int)$_POST['set_qty'];
    $qty = max(1, (int)$_POST['qty']);
    $bagField = $_POST['bag_field'] === 'box' ? 'box' : 'bag';
    $bag = json_decode($char[$bagField], true);
    if (is_array($bag) && isset($bag[$idx])) {
        $bag[$idx]['quantity'] = $qty;
        $json = json_encode($bag, JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("UPDATE players SET $bagField = ? WHERE id = ?");
        $stmt->bind_param("si", $json, $char['id']);
        $stmt->execute();
        $stmt->close();
        $msg = '<div class="alert alert-success">Đã đổi số lượng item thành '.$qty.'.</div>';
        $char[$bagField] = $json;
    }
}
$conn->close();
?>
<div class="bg-content" style="border-radius: 1rem; padding:10px">
    <div style="text-align:center;">
        <h4>Quản lý nhân vật + túi đồ</h4>
    </div>
    <div class="container mb-2">
        <div class="row text-center justify-content-center g-2 mt-1">
            <div class="col-12 col-md-4 col-lg-3">
                <a class="btn btn-success w-100 fw-semibold" href="/admin/home">Quay lại</a>
            </div>
        </div>
    </div>
</div>
<?php if ($msg) echo $msg; ?>

<div class="mt-3">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Tìm nhân vật</h5>
            <form method="GET">
                <input type="hidden" name="page" value="admin">
                <input type="hidden" name="tab" value="char">
                <div class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Nhập tên nhân vật...">
                    <button type="submit" class="btn btn-success">Tìm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($char): ?>
<div class="mt-3">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Thông tin: <?= htmlspecialchars($char['name']) ?> <small class="text-muted">(id: <?= (int)$char['id'] ?>)</small></h5>
            <div class="row g-2">
                <div class="col-6 col-md-3"><small>Class: <b><?= (int)$char['class'] ?></b></small></div>
                <div class="col-6 col-md-3"><small>Lượng: <b><?= number_format($char['xu']) ?></b></small></div>
                <div class="col-6 col-md-3"><small>Xu trong rương: <b><?= number_format($char['xuInBox']) ?></b></small></div>
                <div class="col-6 col-md-3"><small>Yên: <b><?= number_format($char['yen']) ?></b></small></div>
                <div class="col-6 col-md-3"><small>Map: <b><?= htmlspecialchars($char['map']) ?></b></small></div>
                <div class="col-6 col-md-3"><small>Tài khoản: <b><?= htmlspecialchars($char['username']) ?></b></small></div>
                <div class="col-6 col-md-3"><small>Nhiệm vụ: <b><?= (int)$char['taskId'] ?></b> <?= $char['online'] == 1 ? '<span class="text-success">(online)</span>' : '<span class="text-danger">(offline)</span>' ?></small></div>
            </div>
            <form method="POST" class="mt-2 d-flex align-items-center gap-2">
                <input type="hidden" name="skip_task" value="1">
                <label class="fw-semibold mb-0">Bỏ qua nhiệm vụ:</label>
                <select name="steps" class="form-control form-control-sm" style="width:120px">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?> nhiệm vụ</option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Bỏ qua nhiệm vụ hiện tại của <?= htmlspecialchars($char['name']) ?>?')">Bỏ qua</button>
            </form>
            <p class="text-muted mt-1 mb-0"><small>Yêu cầu nhân vật đang online để server xử lý ngay.</small></p>
        </div>
    </div>
</div>

<div class="mt-3">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Thêm item vào túi</h5>
            <form method="POST">
                <input type="hidden" name="add_item" value="1">
                <div class="row g-2">
                    <div class="col-6 col-md-4"><input type="number" name="item_id" class="form-control" placeholder="ID Item" required></div>
                    <div class="col-4 col-md-2"><input type="number" name="quantity" class="form-control" value="1" placeholder="SL"></div>
                    <div class="col-6 col-md-3">
                        <select name="bag_field" class="form-control">
                            <option value="bag">Túi đồ</option>
                            <option value="box">Rương đồ</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3"><button type="submit" class="btn btn-success w-100">Thêm</button></div>
                </div>
                <p class="text-muted mt-2 mb-0"><small>Nhập ID item (xem danh sách ở bảng item DB).</small></p>
            </form>
        </div>
    </div>
</div>

<div class="mt-3">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Túi đồ (bag) - <?= is_array(json_decode($char['bag'], true)) ? count(json_decode($char['bag'], true)) : 0 ?> item</h5>
            <?php $bag = json_decode($char['bag'], true); if (is_array($bag) && count($bag) > 0): ?>
            <div class="table-responsive">
                <table class="table text-white fw-semibold">
                    <thead><tr><th>#</th><th>ID</th><th>Tên</th><th>SL</th><th>Up</th><th>Thao tác</th></tr></thead>
                    <tbody>
                    <?php foreach ($bag as $i => $it): $nm = isset($itemNames[(int)$it['id']]) ? $itemNames[(int)$it['id']]['name'] : 'id '.$it['id']; ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= (int)$it['id'] ?></td>
                            <td><?= htmlspecialchars($nm) ?><?= isset($itemNames[(int)$it['id']]) ? ' <small class="text-muted">(lv '.$itemNames[(int)$it['id']]['level'].')</small>' : '' ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-1" style="display:inline-flex!important">
                                    <input type="hidden" name="set_qty" value="<?= $i ?>">
                                    <input type="hidden" name="bag_field" value="bag">
                                    <input type="number" name="qty" value="<?= isset($it['quantity']) ? (int)$it['quantity'] : 1 ?>" style="width:80px" class="form-control form-control-sm">
                                    <button type="submit" class="btn btn-primary btn-sm">OK</button>
                                </form>
                            </td>
                            <td><?= isset($it['upgrade']) ? (int)$it['upgrade'] : '-' ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="remove_item" value="<?= $i ?>">
                                    <input type="hidden" name="bag_field" value="bag">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa item này?')">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?><span class="text-muted">Túi trống.</span><?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-3">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Rương đồ (box) - <?= is_array(json_decode($char['box'], true)) ? count(json_decode($char['box'], true)) : 0 ?> item</h5>
            <?php $box = json_decode($char['box'], true); if (is_array($box) && count($box) > 0): ?>
            <div class="table-responsive">
                <table class="table text-white fw-semibold">
                    <thead><tr><th>#</th><th>ID</th><th>Tên</th><th>SL</th><th>Thao tác</th></tr></thead>
                    <tbody>
                    <?php foreach ($box as $i => $it): $nm = isset($itemNames[(int)$it['id']]) ? $itemNames[(int)$it['id']]['name'] : 'id '.$it['id']; ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= (int)$it['id'] ?></td>
                            <td><?= htmlspecialchars($nm) ?></td>
                            <td><?= isset($it['quantity']) ? (int)$it['quantity'] : 1 ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="remove_item" value="<?= $i ?>">
                                    <input type="hidden" name="bag_field" value="box">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa item này?')">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?><span class="text-muted">Rương trống.</span><?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
