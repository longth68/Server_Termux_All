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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $char = isset($_POST['char_name']) ? trim($_POST['char_name']) : '';

    if ($char == '') {
        $msg = '<div class="alert alert-danger">Vui lòng nhập tên nhân vật.</div>';
    } else {
        if ($action == 'DELETE_CHAR') {
            $stmt = $conn->prepare("DELETE FROM `players` WHERE `name` = ?");
            $stmt->bind_param("s", $char);
            $stmt->execute();
            $msg = '<div class="alert alert-success">Đã xóa nhân vật <b>' . htmlspecialchars($char) . '</b> thành công! (Lưu ý: Bạn nên ĐÁ người chơi ra trước khi xóa).</div>';
            $stmt->close();
        } elseif ($action == 'DELETE_USER') {
            $stmt = $conn->prepare("SELECT `user_id` FROM `players` WHERE `name` = ?");
            $stmt->bind_param("s", $char);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $uid = $res->fetch_assoc()['user_id'];
                $conn->query("DELETE FROM `players` WHERE `user_id` = $uid");
                $conn->query("DELETE FROM `users` WHERE `id` = $uid");
                $msg = '<div class="alert alert-success">Đã xóa vĩnh viễn tài khoản của nhân vật <b>' . htmlspecialchars($char) . '</b>.</div>';
            } else {
                $msg = '<div class="alert alert-danger">Không tìm thấy nhân vật này!</div>';
            }
            $stmt->close();
        } elseif ($action == 'DELETE_ALL_USERS') {
            $conn->query("DELETE FROM `players` WHERE `user_id` NOT IN (SELECT `id` FROM `users` WHERE `admin_web` = 1)");
            $conn->query("DELETE FROM `users` WHERE `admin_web` = 0");
            $msg = '<div class="alert alert-success">Đã làm sạch toàn bộ tài khoản người chơi cũ (chỉ giữ lại tài khoản Admin).</div>';
        } elseif ($action == 'CHAR_RENAME') {
            $newName = isset($_POST['new_name']) ? trim(strval($_POST['new_name'])) : '';
            if ($newName === '') {
                $msg = '<div class="alert alert-danger">Vui lòng nhập tên mới.</div>';
            } else {
                $data = json_encode(['old' => $char, 'new' => $newName], JSON_UNESCAPED_UNICODE);
                $cmd = 'CHAR_RENAME';
                $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES (?, ?, ?, 0)");
                $stmt->bind_param("sss", $cmd, $char, $data);
                if ($stmt->execute()) {
                    $msg = '<div class="alert alert-success">Đã gửi lệnh đổi tên <b>' . htmlspecialchars($char) . '</b> thành <b>' . htmlspecialchars($newName) . '</b>.</div>';
                } else {
                    $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
                }
                $stmt->close();
            }
        } elseif ($action == 'PLAYER_GIVE') {
            $itemId = isset($_POST['item_id']) ? max(1, intval($_POST['item_id'])) : 0;
            $qty = isset($_POST['qty']) ? max(1, min(9999, intval($_POST['qty']))) : 1;
            $data = json_encode(['char' => $char, 'item' => $itemId, 'qty' => $qty], JSON_UNESCAPED_UNICODE);
            $cmd = 'PLAYER_GIVE';
            $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $cmd, $char, $data);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success">Đã gửi lệnh tặng đồ cho <b>' . htmlspecialchars($char) . '</b> (item ' . $itemId . ' x' . $qty . ', chỉ khi online).</div>';
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
            }
            $stmt->close();
        } else {
            $data = json_encode(['char' => $char], JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $action, $char, $data);
            if ($stmt->execute()) {
                $label = ($action == 'KICK') ? 'Đã gửi lệnh đá ' : 'Đã gửi lệnh khóa ';
                $msg = '<div class="alert alert-success">' . $label . '<b>' . htmlspecialchars($char) . '</b>. Server sẽ xử lý trong vài giây.</div>';
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
            }
            $stmt->close();
        }
    }
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$players = [];
$where = '';
$params = '';
$types = '';
if ($search != '') {
    $where = "WHERE (p.`name` LIKE ? OR u.`username` LIKE ? OR u.`id` = ?)";
    $like = '%' . $search . '%';
    $searchId = intval($search);
    $params = true;
}
$sql = "SELECT p.`id` AS `char_id`, p.`name`, CAST(JSON_EXTRACT(p.`data`, '$.exp') AS INT) AS `exp`, u.`id` AS `user_id`, u.`username`, u.`status`, u.`ban_until`
        FROM `players` p
        JOIN `users` u ON u.`id` = p.`user_id`
        $where
        ORDER BY CAST(JSON_EXTRACT(p.`data`, '$.exp') AS INT) DESC
        LIMIT 50";
if ($params != '') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $like, $like, $searchId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query($sql);
}
if ($result && $result !== true) {
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
}

$history = [];
$result = $conn->query("SELECT `id`, `command`, `target_user`, `status`, `created_at` FROM `web_admin_commands` WHERE `command` IN ('KICK','BAN','CHAR_RENAME','PLAYER_GIVE') ORDER BY `id` DESC LIMIT 30");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}
$conn->close();
?>
<div class="bg-content" style="border-radius: 1rem; padding:10px">
    <div style="text-align:center;">
        <h4>Quản lý người chơi</h4>
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
            <h5 class="fw-bold">Đá / Khóa người chơi online</h5>
            <form method="POST">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label class="fw-semibold">Tên nhân vật</label>
                        <input type="text" name="char_name" class="form-control" placeholder="Nhập chính xác tên nhân vật" required>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <button type="submit" class="btn btn-warning" name="action" value="KICK">Đá (Kick)</button>
                    <button type="submit" class="btn btn-danger" name="action" value="BAN" onclick="return confirm('Khóa tài khoản này? Người chơi sẽ không đăng nhập được nữa.')">Khóa (Ban)</button>
                    <button type="submit" class="btn btn-dark" name="action" value="DELETE_CHAR" onclick="return confirm('CẢNH BÁO: Xóa vĩnh viễn nhân vật này? KHÔNG THỂ KHÔI PHỤC!\nNên ĐÁ khỏi máy chủ trước khi xóa.')">Xóa Nhân Vật</button>
                    <button type="submit" class="btn btn-dark" name="action" value="DELETE_USER" onclick="return confirm('CẢNH BÁO ĐỎ: Xóa vĩnh viễn TOÀN BỘ TÀI KHOẢN chứa nhân vật này? KHÔNG THỂ KHÔI PHỤC!')">Xóa Cả Tài Khoản</button>
                </div>
            </form>
            <p class="text-muted mt-2 mb-0"><small>Kick: đẩy người chơi ra khỏi game (chỉ áp dụng khi online). Ban: khóa tài khoản vĩnh viễn, áp dụng cả khi online lẫn offline.</small></p>
            <hr>
            <h6 class="fw-bold">Đổi tên nhân vật (mẫu NRO)</h6>
            <form method="POST">
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="fw-semibold">Tên cũ</label>
                        <input type="text" name="char_name" class="form-control" placeholder="Tên hiện tại" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="fw-semibold">Tên mới (3-12 ký tự)</label>
                        <input type="text" name="new_name" class="form-control" maxlength="12" required>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary" name="action" value="CHAR_RENAME">Đổi tên</button>
                </div>
            </form>
            <hr>
            <h6 class="fw-bold">Tặng đồ cho người online (mẫu NRO)</h6>
            <form method="POST">
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="fw-semibold">Tên nhân vật (đang online)</label>
                        <input type="text" name="char_name" class="form-control" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">ID vật phẩm</label>
                        <input type="number" name="item_id" class="form-control" min="1" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Số lượng</label>
                        <input type="number" name="qty" class="form-control" value="1" min="1" max="9999">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-success" name="action" value="PLAYER_GIVE">Tặng đồ</button>
                </div>
            </form>
            <hr>
            <form method="POST">
                <input type="hidden" name="char_name" value="ALL_USERS">
                <button type="submit" class="btn btn-danger w-100" name="action" value="DELETE_ALL_USERS" onclick="return confirm('CẢNH BÁO TỐI CAO: Hành động này sẽ XÓA SẠCH toàn bộ tài khoản và nhân vật cũ trong máy chủ (ngoại trừ Admin)!\n\nBạn có chắc chắn muốn LÀM SẠCH (Reset) Server không?')">LÀM SẠCH TOÀN BỘ USER CŨ (RESET SERVER)</button>
            </form>
        </div>
    </div>
</div>

<div class="mt-4">
    <h5 class="fw-bold">Tìm kiếm người chơi</h5>
    <form method="GET" class="mb-2">
        <div class="row g-2">
            <div class="col-12 col-md-6">
                <input type="text" name="q" class="form-control" placeholder="Tìm theo tên nhân vật, username hoặc id" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Tìm</button>
            </div>
        </div>
    </form>
    <?php if (count($players) > 0): ?>
        <div class="table-responsive" style="border-radius: 1rem;">
            <table class="table text-white fw-semibold mb-0" role="table">
                <thead>
                    <tr class="text-start fw-bold text-uppercase gs-0">
                        <th>Nhân vật</th>
                        <th>Exp</th>
                        <th>Username</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($players as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= number_format(intval($p['exp'])) ?></td>
                            <td><?= htmlspecialchars($p['username']) ?></td>
                            <td>
                                <?php
                                if (intval($p['status']) === 1) {
                                    echo '<b class="text-success">Đang online</b>';
                                } elseif ($p['ban_until'] && strtotime($p['ban_until']) > time()) {
                                    echo '<b class="text-danger">Bị khóa đến ' . date('d/m/Y', strtotime($p['ban_until'])) . '</b>';
                                } else {
                                    echo '<span class="text-muted">Offline</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="char_name" value="<?= htmlspecialchars($p['name']) ?>">
                                    <button type="submit" name="action" value="KICK" class="btn btn-warning btn-sm mb-1">Kick</button>
                                    <button type="submit" name="action" value="BAN" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Khóa tài khoản <?= htmlspecialchars($p['name']) ?>?')">Ban</button>
                                    <button type="submit" name="action" value="DELETE_CHAR" class="btn btn-dark btn-sm mb-1" onclick="return confirm('Xóa nhân vật <?= htmlspecialchars($p['name']) ?>?')">Xóa NV</button>
                                    <button type="submit" name="action" value="DELETE_USER" class="btn btn-dark btn-sm fw-bold mb-1" onclick="return confirm('Xóa toàn bộ tài khoản <?= htmlspecialchars($p['name']) ?>?')">Xóa TK</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center"><small class="fw-semibold"><?= $search != '' ? 'Không tìm thấy người chơi nào.' : 'Nhập từ khóa để tìm kiếm người chơi.' ?></small></div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <h5 class="fw-bold">Lịch sử Kick / Ban</h5>
    <?php if (count($history) > 0): ?>
        <div class="table-responsive" style="border-radius: 1rem;">
            <table class="table text-white fw-semibold mb-0" role="table">
                <thead>
                    <tr class="text-start fw-bold text-uppercase gs-0">
                        <th>ID</th>
                        <th>Lệnh</th>
                        <th>Nhân vật</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= $h['id'] ?></td>
                            <td><?= htmlspecialchars($h['command']) == 'KICK' ? '<span class="text-warning">KICK</span>' : '<span class="text-danger">BAN</span>' ?></td>
                            <td><?= htmlspecialchars($h['target_user']) ?></td>
                            <td><?= intval($h['status']) === 0 ? '<b class="text-warning">Chờ xử lý</b>' : '<b class="text-success">Đã xử lý</b>' ?></td>
                            <td><?= date('H:i d/m/Y', strtotime($h['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center"><small class="fw-semibold">Chưa có lệnh nào.</small></div>
    <?php endif; ?>
</div>
