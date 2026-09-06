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

$slotNames = ['Nón', 'Vũ khí', 'Áo', 'Liên', 'Găng tay', 'Nhẫn', 'Quần', 'Ngọc bội', 'Giày', 'Phụ'];
$classNames = [1 => 'Kiếm', 2 => 'Tiêu', 3 => 'Kunai', 4 => 'Cung', 5 => 'Đao', 6 => 'Quạt'];
$taskList = [
    1 => 'NV Kiến thức', 2 => 'NV Lần đầu dùng kiếm', 3 => 'NV Diệt sen trù cổ',
    4 => 'NV Vật liệu tạo giáp', 5 => 'NV Hái thuốc cứu người', 6 => 'NV Khám phá xa lạ',
    7 => 'NV Bài học vào trường', 8 => 'NV Tìm hiểu 3 trường', 9 => 'NV Gia tăng sức mạnh',
    10 => 'NV Bài học đầu tiên', 11 => 'NV Bạn hữu tam giao', 12 => 'NV Nâng cấp trang bị',
    13 => 'NV Thách đấu', 14 => 'NV Thu thập nguyên liệu', 15 => 'NV Truyền tải tin tức',
    16 => 'NV Rèn luyện thể lực', 17 => 'NV Đưa jaian trở về', 18 => 'NV Tìm nguyên liệu làm thuốc',
    19 => 'NV Lấy nước hang sâu', 20 => 'NV Tìm lại cây rìu', 21 => 'NV Vượt qua thử thách',
    22 => 'NV Thu thập chìa khoá', 23 => 'NV Truy tìm địa đồ', 24 => 'NV Truy tìm bảo vật',
    25 => 'NV Rèn luyện', 26 => 'NV Thu thập tinh thể băng', 27 => 'NV Thu thập xác đối lửa',
    28 => 'NV Kiên trì diệt ác', 29 => 'NV Giết tinh anh', 30 => 'NV Tuần hoàn',
    31 => 'NV Dự trữ lương thực', 32 => 'NV Rèn luyện ý chí', 33 => 'NV Diệt ma',
    34 => 'NV Hải nam', 35 => 'NV Giúp đỡ dân làng', 36 => 'NV Thu thập oan hồn',
    37 => 'NV Thử thách của Guriin', 38 => 'NV Thắp sáng bản làng', 39 => 'NV Hoạt động hằng ngày',
    40 => 'NV Thử tài may mắn', 41 => 'NV Chiến trường', 42 => 'NV Bất khả thi'
];

$conn = SQL();
$msg = '';

// Bảng ánh xạ item id -> [name, icon, level] để hiển thị tên + icon như NRO
$itemMeta = [];
$rm = $conn->query("SELECT `id`, `name`, `icon`, `level` FROM `item`");
if ($rm) {
    while ($row = $rm->fetch_assoc()) {
        $itemMeta[(int)$row['id']] = ['name' => $row['name'], 'icon' => (int)$row['icon'], 'level' => (int)$row['level']];
    }
}
function itemImg($meta, $size = 40) {
    if (!$meta || $meta['icon'] <= 0) {
        return '';
    }
    $url = '/images/1/Small' . (int)$meta['icon'] . '.png';
    return '<img src="' . $url . '" alt="" style="width:' . $size . 'px;height:' . $size . 'px;border-radius:4px;margin-right:6px;vertical-align:middle;" onerror="this.style.display=\'none\'">';
}
function itemLabel($itemId, $itemMeta) {
    $id = (int)$itemId;
    if (isset($itemMeta[$id])) {
        return $itemMeta[$id]['name'] . ' <small class="text-muted">(lv ' . $itemMeta[$id]['level'] . ')</small>';
    }
    return 'ID ' . $id;
}
function itemIconUrl($itemId, $itemMeta) {
    $id = (int)$itemId;
    if (isset($itemMeta[$id]) && $itemMeta[$id]['icon'] > 0) {
        return '/images/1/Small' . $itemMeta[$id]['icon'] . '.png';
    }
    return '';
}

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
        } elseif ($action == 'PLAYER_GEAR_TAKE' || $action == 'PLAYER_GEAR_WEAR') {
            $payload = ['char' => $char, 'slot' => isset($_POST['slot']) ? intval($_POST['slot']) : -1];
            if ($action == 'PLAYER_GEAR_TAKE') {
                $payload['place'] = isset($_POST['place']) ? strval($_POST['place']) : 'bag';
            }
            $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $action, $char, $data);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success">Đã gửi lệnh sửa đồ cho <b>' . htmlspecialchars($char) . '</b> (chỉ khi online).</div>';
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
            }
            $stmt->close();
        } elseif ($action == 'PLAYER_EDIT') {
            $payload = ['char' => $char];
            $editFields = ['level', 'exp', 'yen', 'xu', 'xuInBox', 'spoint', 'class', 'gender'];
            foreach ($editFields as $f) {
                if (isset($_POST[$f]) && $_POST[$f] !== '' && is_numeric($_POST[$f])) {
                    $payload[$f] = intval($_POST[$f]);
                }
            }
            if (count($payload) <= 1) {
                $msg = '<div class="alert alert-warning">Chưa nhập trường nào để sửa.</div>';
            } else {
                $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
                $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES (?, ?, ?, 0)");
                $stmt->bind_param("sss", $action, $char, $data);
                if ($stmt->execute()) {
                    $msg = '<div class="alert alert-success">Đã gửi lệnh sửa chỉ số cho <b>' . htmlspecialchars($char) . '</b>. Áp dụng cả online lẫn offline.</div>';
                } else {
                    $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
                }
                $stmt->close();
            }
        } elseif ($action == 'PLAYER_TASK_SET' || $action == 'PLAYER_TASK_FINISH' || $action == 'PLAYER_TASK_RESET' || $action == 'SKIP_TASK') {
            $payload = ['char' => $char];
            if ($action == 'PLAYER_TASK_SET') {
                $payload['taskId'] = isset($_POST['taskId']) ? max(1, min(42, intval($_POST['taskId']))) : 1;
            } elseif ($action == 'SKIP_TASK') {
                $payload['steps'] = isset($_POST['steps']) ? max(1, min(10, intval($_POST['steps']))) : 1;
            }
            $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $action, $char, $data);
            if ($stmt->execute()) {
                $labels = ['PLAYER_TASK_SET' => 'Đặt nhiệm vụ', 'PLAYER_TASK_FINISH' => 'Hoàn thành NV', 'PLAYER_TASK_RESET' => 'Reset NV', 'SKIP_TASK' => 'Bỏ qua NV'];
                $label = $labels[$action] ?? $action;
                $msg = '<div class="alert alert-success">' . $label . ' cho <b>' . htmlspecialchars($char) . '</b> (yêu cầu online).</div>';
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
            }
            $stmt->close();
        } elseif ($action == 'PLAYER_GEAR_UPGRADE') {
            $payload = ['char' => $char, 'slot' => isset($_POST['slot']) ? intval($_POST['slot']) : -1, 'upgrade' => isset($_POST['upgrade']) ? max(0, min(16, intval($_POST['upgrade']))) : 0, 'place' => isset($_POST['place']) ? strval($_POST['place']) : 'bag'];
            $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $action, $char, $data);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success">Đã gửi lệnh nâng cấp đồ cho <b>' . htmlspecialchars($char) . '</b> (yêu cầu online).</div>';
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
            }
            $stmt->close();
        } elseif ($action == 'PLAYER_RESET_PW' || $action == 'USER_SET_CURRENCY') {
            $username = isset($_POST['username']) ? trim($_POST['username']) : $char;
            $payload = ['username' => $username];
            $valid = true;
            if ($action == 'PLAYER_RESET_PW') {
                $pw = isset($_POST['password']) ? trim($_POST['password']) : '';
                if (strlen($pw) < 6) {
                    $msg = '<div class="alert alert-warning">Mật khẩu mới phải từ 6 ký tự.</div>';
                    $valid = false;
                } else {
                    $payload['password'] = $pw;
                }
            } else {
                foreach (['luong', 'coin', 'tongnap'] as $f) {
                    if (isset($_POST[$f]) && $_POST[$f] !== '' && is_numeric($_POST[$f])) {
                        $payload[$f] = intval($_POST[$f]);
                    }
                }
                if (count($payload) <= 1) {
                    $msg = '<div class="alert alert-warning">Chưa nhập trường nào để đặt.</div>';
                    $valid = false;
                }
            }
            if ($valid) {
                $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
                $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES (?, ?, ?, 0)");
                $stmt->bind_param("sss", $action, $username, $data);
                if ($stmt->execute()) {
                    $label = ($action == 'PLAYER_RESET_PW') ? 'Đặt lại mật khẩu' : 'Đặt xu/lượng';
                    $msg = '<div class="alert alert-success">' . $label . ' cho <b>' . htmlspecialchars($username) . '</b> thành công.</div>';
                } else {
                    $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
                }
                $stmt->close();
            }
        } elseif ($action == 'PLAYER_TELEPORT') {
            $payload = ['char' => $char, 'mapId' => isset($_POST['mapId']) ? max(0, intval($_POST['mapId'])) : 0, 'zoneId' => isset($_POST['zoneId']) ? max(0, intval($_POST['zoneId'])) : 0];
            $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $action, $char, $data);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success">Đã gửi lệnh dịch chuyển cho <b>' . htmlspecialchars($char) . '</b> tới map ' . intval($_POST['mapId']) . ' (yêu cầu online).</div>';
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
$sql = "SELECT p.`id` AS `char_id`, p.`name`, CAST(JSON_UNQUOTE(JSON_EXTRACT(p.`data`, '$.exp')) AS UNSIGNED) AS `exp`, p.`online` AS `p_online`, u.`id` AS `user_id`, u.`username`, u.`status`, u.`ban_until`
        FROM `players` p
        JOIN `users` u ON u.`id` = p.`user_id`
        $where
        ORDER BY CAST(JSON_UNQUOTE(JSON_EXTRACT(p.`data`, '$.exp')) AS UNSIGNED) DESC
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

$pdetail = null;
$plive = null;
$peq = [];
$pbag = [];
$peqOffline = false;
if (isset($_GET['detail']) && trim(strval($_GET['detail'])) !== '') {
    $dn = trim(strval($_GET['detail']));
    $stmt = $conn->prepare("SELECT p.*, CAST(JSON_UNQUOTE(JSON_EXTRACT(p.`data`, '$.exp')) AS UNSIGNED) AS `exp`, p.`online` AS `p_online`, u.`username`, u.`status` AS `ustatus`, u.`luong`, u.`coin`, u.`ban_until`, u.`tongnap`, u.`online` AS `uonline`, u.`created_at` AS `reg_at` FROM `players` p JOIN `users` u ON u.`id` = p.`user_id` WHERE p.`name` = ? LIMIT 1");
    $stmt->bind_param("s", $dn);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $pdetail = $res->fetch_assoc();
    }
    $stmt->close();
    if ($pdetail) {
        $stmt = $conn->prepare("SELECT * FROM `player_status` WHERE `name` = ? LIMIT 1");
        $stmt->bind_param("s", $dn);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $plive = $res->fetch_assoc();
        }
        $stmt->close();
        if ($plive && !empty($plive['gear'])) {
            $g = json_decode(strval($plive['gear']), true);
            if (is_array($g)) {
                $peq = isset($g['eq']) && is_array($g['eq']) ? $g['eq'] : [];
                $pbag = isset($g['bag']) && is_array($g['bag']) ? $g['bag'] : [];
            }
        } else {
            // Offline: đọc đồ đã lưu trong DB (chỉ xem, không sửa)
            $peqOffline = true;
            foreach (['equiped' => 'eq', 'bag' => 'bag'] as $col => $dst) {
                $arr = json_decode(strval($pdetail[$col] ?? ''), true);
                if (!is_array($arr)) {
                    continue;
                }
                foreach ($arr as $it) {
                    if (!is_array($it)) {
                        continue;
                    }
                    $iid = isset($it['id']) ? intval($it['id']) : 0;
                    $entry = [
                        'slot' => isset($it['index']) ? intval($it['index']) : -1,
                        'id' => $iid,
                        'name' => isset($itemMeta[$iid]) ? $itemMeta[$iid]['name'] : '',
                        'qty' => isset($it['quantity']) ? intval($it['quantity']) : 1,
                        'upg' => isset($it['upgrade']) ? intval($it['upgrade']) : 0,
                    ];
                    if ($dst === 'eq') {
                        $entry['slot_name'] = ($entry['slot'] >= 0 && $entry['slot'] < 10) ? $slotNames[$entry['slot']] : ('Ô ' . $entry['slot']);
                        $peq[] = $entry;
                    } else {
                        $pbag[] = $entry;
                    }
                }
            }
        }
    }
}
?>
<?php
$history = [];
$result = $conn->query("SELECT `id`, `command`, `target_user`, `status`, `created_at` FROM `web_admin_commands` WHERE `command` IN ('KICK','BAN','CHAR_RENAME','PLAYER_GIVE','PLAYER_GEAR_TAKE','PLAYER_GEAR_WEAR','PLAYER_EDIT','PLAYER_TASK_SET','PLAYER_TASK_FINISH','PLAYER_TASK_RESET','SKIP_TASK','PLAYER_GEAR_UPGRADE','PLAYER_RESET_PW','USER_SET_CURRENCY','PLAYER_TELEPORT') ORDER BY `id` DESC LIMIT 30");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}
$conn->close();
?>
<div class="admin-panel">
<style>
    .admin-panel { background: #f4f6f9; color: #212529; padding: 14px; border-radius: 8px; }
    .admin-panel .bg-content { background: #fff; color: #212529; border: 1px solid #ddd; border-radius: 8px; }
    .admin-panel .card { background: #fff; color: #212529; border: 1px solid #ddd; box-shadow: 0 1px 3px rgba(0,0,0,.08); border-radius: 6px; }
    .admin-panel .card-body { color: #212529; }
    .admin-panel .text-white { color: #212529 !important; }
    .admin-panel .table { color: #212529; }
    .admin-panel .table th, .admin-panel .table td { color: #212529; border-color: #dee2e6; }
    .admin-panel h4, .admin-panel h5, .admin-panel h6 { color: #212529; }
    .admin-panel .form-control, .admin-panel .form-select { background: #fff; color: #212529; border: 1px solid #ced4da; }
    .admin-panel .list-group-item { background: #fff; color: #212529; border-color: #dee2e6; }
    .admin-panel .text-muted { color: #6c757d !important; }
    .admin-panel .nav-tabs .nav-link { color: #0d6efd; }
    .admin-panel .nav-tabs .nav-link.active { color: #212529; }
</style>
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
                        <input type="number" name="item_id" id="give_item_id" class="form-control" min="1" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">&nbsp;</label>
                        <button type="button" class="btn btn-info w-100" onclick="ItemPicker.open({mode:'single',target:'give_item_id',onPick:function(it){var p=document.getElementById('give_preview');if(p)p.innerHTML='<span class=\'badge bg-light text-dark border\'>'+ItemPicker.img(it.id,24)+' '+ItemPicker.label(it.id)+'</span>';} })"><i class="fa-solid fa-box-open"></i> Chọn</button>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Số lượng</label>
                        <input type="number" name="qty" class="form-control" value="1" min="1" max="9999">
                    </div>
                </div>
                <div id="give_preview" class="mt-1"></div>
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
                                $isOnline = intval($p['p_online'] ?? 0) === 1;
                                $isBanned = $p['ban_until'] && strtotime($p['ban_until']) > time();
                                if ($isOnline) {
                                    echo '<b class="text-success">Đang online</b>';
                                } elseif ($isBanned) {
                                    echo '<b class="text-danger">Bị khóa đến ' . date('d/m/Y', strtotime($p['ban_until'])) . '</b>';
                                } else {
                                    echo '<span class="text-muted">Offline</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <a class="btn btn-info btn-sm mb-1 fw-semibold" href="?detail=<?= urlencode($p['name']) ?>">Chi tiết</a>
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
    <h5 class="fw-bold">Lịch sử lệnh</h5>
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
                            <td>
                                <?php
                                $cmd = strval($h['command']);
                                $badge = '<span class="text-warning">' . htmlspecialchars($cmd) . '</span>';
                                if ($cmd === 'KICK') { $badge = '<span class="text-warning">KICK</span>'; }
                                elseif ($cmd === 'BAN') { $badge = '<span class="text-danger">BAN</span>'; }
                                elseif ($cmd === 'CHAR_RENAME') { $badge = '<span class="text-info">RENAME</span>'; }
                                elseif ($cmd === 'PLAYER_GIVE') { $badge = '<span class="text-success">GIVE</span>'; }
                                elseif ($cmd === 'PLAYER_GEAR_TAKE') { $badge = '<span class="text-danger">GỠ ĐỒ</span>'; }
                                elseif ($cmd === 'PLAYER_GEAR_WEAR') { $badge = '<span class="text-success">MẶC ĐỒ</span>'; }
                                elseif ($cmd === 'PLAYER_EDIT') { $badge = '<span class="text-info">SỬA SỐ</span>'; }
                                elseif ($cmd === 'PLAYER_TASK_SET') { $badge = '<span class="text-warning">ĐẶT NV</span>'; }
                                elseif ($cmd === 'PLAYER_TASK_FINISH') { $badge = '<span class="text-success">XONG NV</span>'; }
                                elseif ($cmd === 'PLAYER_TASK_RESET') { $badge = '<span class="text-danger">RESET NV</span>'; }
                                elseif ($cmd === 'SKIP_TASK') { $badge = '<span class="text-warning">SKIP NV</span>'; }
                                elseif ($cmd === 'PLAYER_GEAR_UPGRADE') { $badge = '<span class="text-info">+UP ĐỒ</span>'; }
                                elseif ($cmd === 'PLAYER_RESET_PW') { $badge = '<span class="text-danger">ĐỔI MK</span>'; }
                                elseif ($cmd === 'USER_SET_CURRENCY') { $badge = '<span class="text-warning">XU/LƯỢNG</span>'; }
                                elseif ($cmd === 'PLAYER_TELEPORT') { $badge = '<span class="text-info">DỊCH CHUYỂN</span>'; }
                                echo $badge;
                                ?>
                            </td>
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

<?php if ($pdetail): ?>
<?php
$activeTab = isset($_GET['dtab']) ? preg_replace('/[^a-z]/', '', strtolower($_GET['dtab'])) : 'info';
$tabs = ['info' => 'Thông tin', 'gear' => 'Trang bị', 'bag' => 'Túi đồ', 'task' => 'Nhiệm vụ', 'skill' => 'Kỹ năng', 'social' => 'Bạn bè', 'actions' => 'Thao tác'];
if (!isset($tabs[$activeTab])) {
    $activeTab = 'info';
}
// Parse kỹ năng + bạn bè từ DB
$pSkills = [];
$pFriends = [];
$pEnemies = [];
if ($pdetail) {
    $skArr = json_decode(strval($pdetail['skill'] ?? '[]'), true);
    if (is_array($skArr)) { $pSkills = $skArr; }
    $frArr = json_decode(strval($pdetail['friends'] ?? '[]'), true);
    if (is_array($frArr)) { $pFriends = $frArr; }
    $enArr = json_decode(strval($pdetail['enemies'] ?? '[]'), true);
    if (is_array($enArr)) { $pEnemies = $enArr; }
}
?>
<div class="mt-4">
    <div class="card border-info">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold mb-0">Chi tiết: <?= htmlspecialchars($pdetail['name']) ?></h5>
                <a class="btn btn-secondary btn-sm" href="?q=<?= urlencode($pdetail['name']) ?>">Đóng</a>
            </div>
            <?php if ($peqOffline): ?>
                <div class="alert alert-warning py-2"><small>Nhân vật offline. Trang bị/túi chỉ xem, các thao tác Gỡ/Mặc/Nhiệm vụ cần online. Sửa chỉ số vẫn áp dụng được.</small></div>
            <?php endif; ?>

            <ul class="nav nav-tabs mb-3" role="tablist">
                <?php foreach ($tabs as $key => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab == $key ? 'active' : '' ?>" href="?detail=<?= urlencode($pdetail['name']) ?>&dtab=<?= $key ?>"><?= $label ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($activeTab == 'info'): ?>
                <div class="row g-2 mb-2">
                    <div class="col-6 col-md-3"><b>Level:</b> <?= intval($pdetail['level'] ?? 0) ?></div>
                    <div class="col-6 col-md-3"><b>Phái:</b> <?= intval($pdetail['gender'] ?? 0) == 1 ? 'Nam' : 'Nữ' ?></div>
                    <div class="col-6 col-md-3"><b>Class:</b> <?= htmlspecialchars($classNames[intval($pdetail['class'] ?? 0)] ?? ('C' . intval($pdetail['class'] ?? 0))) ?></div>
                    <div class="col-6 col-md-3"><b>Username:</b> <?= htmlspecialchars($pdetail['username'] ?? '-') ?></div>
                    <div class="col-6 col-md-3"><b>Map:</b> <?= intval($pdetail['map'] ?? 0) ?></div>
                    <div class="col-6 col-md-3"><b>Vàng:</b> <?= number_format(intval($pdetail['yen'] ?? 0)) ?></div>
                    <div class="col-6 col-md-3"><b>Xu:</b> <?= number_format(intval($pdetail['xu'] ?? 0)) ?></div>
                    <div class="col-6 col-md-3"><b>Xu hòm:</b> <?= number_format(intval($pdetail['xuInBox'] ?? 0)) ?></div>
                    <div class="col-6 col-md-3"><b>HP:</b> <?= $plive ? intval($plive['hp'] ?? 0) . '/' . intval($plive['max_hp'] ?? 0) : '-' ?></div>
                    <div class="col-6 col-md-3"><b>Clan:</b> <?= htmlspecialchars(strval($plive['clan'] ?? '-')) ?></div>
                    <div class="col-6 col-md-3"><b>Status:</b> <?= intval($pdetail['ustatus'] ?? 0) == 1 ? '<span class="text-success">Active</span>' : (intval($pdetail['ustatus'] ?? 0) === 2 ? '<span class="text-danger">Block</span>' : '<span class="text-muted">Inactive</span>') ?></div>
                    <div class="col-6 col-md-3"><b>Online:</b> <?= (intval($pdetail['p_online'] ?? 0) == 1 || $plive) ? '<span class="text-success">YES</span>' : '<span class="text-muted">NO</span>' ?></div>
                </div>
                <hr>
                <h6 class="fw-bold">Sửa nhanh chỉ số (áp dụng cả online lẫn offline)</h6>
                <form method="POST" class="row g-2">
                    <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                    <div class="col-6 col-md-2"><label class="form-label small">Level</label><input type="number" name="level" class="form-control form-control-sm" min="1" max="200" placeholder="<?= intval($pdetail['level'] ?? 0) ?>"></div>
                    <div class="col-6 col-md-2"><label class="form-label small">Exp</label><input type="number" name="exp" class="form-control form-control-sm" placeholder="<?= intval($pdetail['exp'] ?? 0) ?>"></div>
                    <div class="col-6 col-md-2"><label class="form-label small">Vàng</label><input type="number" name="yen" class="form-control form-control-sm" placeholder="<?= intval($pdetail['yen'] ?? 0) ?>"></div>
                    <div class="col-6 col-md-2"><label class="form-label small">Xu</label><input type="number" name="xu" class="form-control form-control-sm" placeholder="<?= intval($pdetail['xu'] ?? 0) ?>"></div>
                    <div class="col-6 col-md-2"><label class="form-label small">Xu hòm</label><input type="number" name="xuInBox" class="form-control form-control-sm" placeholder="<?= intval($pdetail['xuInBox'] ?? 0) ?>"></div>
                    <div class="col-6 col-md-2"><label class="form-label small">Điểm kỹ năng (spoint)</label><input type="number" name="spoint" class="form-control form-control-sm" placeholder="<?= intval($pdetail['spoint'] ?? 0) ?>"></div>
                    <div class="col-6 col-md-2"><label class="form-label small">Phái (1=Nam)</label><input type="number" name="gender" class="form-control form-control-sm" min="0" max="1" placeholder="<?= intval($pdetail['gender'] ?? 0) ?>"></div>
                    <div class="col-6 col-md-2"><label class="form-label small">Class (0-6)</label><input type="number" name="class" class="form-control form-control-sm" min="0" max="6" placeholder="<?= intval($pdetail['class'] ?? 0) ?>"></div>
                    <div class="col-12 mt-2">
                        <button type="submit" name="action" value="PLAYER_EDIT" class="btn btn-primary btn-sm" onclick="return confirm('Cập nhật chỉ số cho <?= htmlspecialchars($pdetail['name']) ?>?\nOnline sẽ được áp dụng ngay + thông báo trong game.')">Lưu chỉ số</button>
                        <span class="text-muted small ms-2">Bỏ trống = giữ nguyên. Áp dụng cho cả online/offline.</span>
                    </div>
                </form>

            <?php elseif ($activeTab == 'gear'): ?>
                <h6 class="fw-bold mt-2">Trang bị (10 ô) <?= $peqOffline ? '<small class="text-muted">(offline, chỉ xem)</small>' : '' ?></h6>
                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <table class="table table-sm text-white mb-0 align-middle">
                            <thead><tr class="fw-bold text-uppercase"><th>Ô</th><th>ID</th><th>Trang bị</th><th>+Up</th><th>Thao tác</th></tr></thead>
                            <tbody>
                            <?php for ($i = 0; $i < 10; $i++):
                                $item = null;
                                foreach ($peq as $e) {
                                    if (intval($e['slot'] ?? -1) === $i) { $item = $e; break; }
                                }
                                $slotLabel = $slotNames[$i] ?? ('Ô ' . $i);
                                $itId = $item ? intval($item['id']) : 0;
                                $meta = isset($itemMeta[$itId]) ? $itemMeta[$itId] : null;
                            ?>
                                <tr>
                                    <td><b><?= $slotLabel ?></b></td>
                                    <td><?= $item ? $itId : '<span class="text-muted">-</span>' ?></td>
                                    <td>
                                        <?php if ($item): ?>
                                            <?= itemImg($meta) ?>
                                            <span class="fw-semibold"><?= htmlspecialchars(strval($item['name'] ?? $meta['name'] ?? '')) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">trống</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $item ? '<span class="text-info fw-bold">+' . intval($item['upg'] ?? 0) . '</span>' : '' ?></td>
                                    <td>
                                        <?php if ($item && !$peqOffline): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                                            <input type="hidden" name="place" value="equip">
                                            <input type="hidden" name="slot" value="<?= $i ?>">
                                            <button type="submit" name="action" value="PLAYER_GEAR_TAKE" class="btn btn-danger btn-sm" onclick="return confirm('Gỡ <?= htmlspecialchars($slotLabel) ?> của <?= htmlspecialchars($pdetail['name']) ?>?')">Gỡ</button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                                            <input type="hidden" name="place" value="equip">
                                            <input type="hidden" name="slot" value="<?= $i ?>">
                                            <input type="number" name="upgrade" class="form-control form-control-sm d-inline-block" style="width:70px" value="<?= intval($item['upg'] ?? 0) ?>" min="0" max="16">
                                            <button type="submit" name="action" value="PLAYER_GEAR_UPGRADE" class="btn btn-warning btn-sm" onclick="return confirm('Đặt +up cho <?= htmlspecialchars($slotLabel) ?>?')">Up</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($activeTab == 'bag'): ?>
                <h6 class="fw-bold mt-2">Túi đồ (20 ô) <?= $peqOffline ? '<small class="text-muted">(offline, chỉ xem)</small>' : '' ?></h6>
                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <table class="table table-sm text-white mb-0 align-middle">
                            <thead><tr class="fw-bold text-uppercase"><th>Ô</th><th>ID</th><th>Vật phẩm</th><th>SL</th><th>+Up</th><th>Thao tác</th></tr></thead>
                            <tbody>
                            <?php for ($i = 0; $i < 20; $i++):
                                $item = null;
                                foreach ($pbag as $b) {
                                    if (intval($b['slot'] ?? -1) === $i) { $item = $b; break; }
                                }
                                $itId = $item ? intval($item['id']) : 0;
                                $meta = isset($itemMeta[$itId]) ? $itemMeta[$itId] : null;
                            ?>
                                <tr>
                                    <td><b><?= $i ?></b></td>
                                    <td><?= $item ? $itId : '<span class="text-muted">-</span>' ?></td>
                                    <td>
                                        <?php if ($item): ?>
                                            <?= itemImg($meta) ?>
                                            <span class="fw-semibold"><?= htmlspecialchars(strval($item['name'] ?? $meta['name'] ?? '')) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">trống</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $item ? intval($item['qty'] ?? 1) : '' ?></td>
                                    <td><?= $item ? '<span class="text-info fw-bold">+' . intval($item['upg'] ?? 0) . '</span>' : '' ?></td>
                                    <td>
                                        <?php if ($item && !$peqOffline): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                                            <input type="hidden" name="slot" value="<?= $i ?>">
                                            <button type="submit" name="action" value="PLAYER_GEAR_WEAR" class="btn btn-success btn-sm" onclick="return confirm('Mặc đồ ô <?= $i ?> cho <?= htmlspecialchars($pdetail['name']) ?>?')">Mặc</button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                                            <input type="hidden" name="slot" value="<?= $i ?>">
                                            <input type="number" name="upgrade" class="form-control form-control-sm d-inline-block" style="width:70px" value="<?= intval($item['upg'] ?? 0) ?>" min="0" max="16">
                                            <button type="submit" name="action" value="PLAYER_GEAR_UPGRADE" class="btn btn-warning btn-sm" onclick="return confirm('Đặt +up cho đồ ô <?= $i ?>?')">Up</button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                                            <input type="hidden" name="place" value="bag">
                                            <input type="hidden" name="slot" value="<?= $i ?>">
                                            <button type="submit" name="action" value="PLAYER_GEAR_TAKE" class="btn btn-danger btn-sm" onclick="return confirm('Gỡ đồ ô <?= $i ?> của <?= htmlspecialchars($pdetail['name']) ?>?')">Gỡ</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($activeTab == 'task'): ?>
                <div class="alert alert-info py-2"><small>Đọc <code>taskId</code> từ DB. Nếu nhân vật online, có thể thao tác nhiệm vụ ngay.</small></div>
                <div class="row g-2 mb-2">
                    <div class="col-6 col-md-3"><b>Task ID hiện tại:</b> <?= intval($pdetail['taskId'] ?? 0) ?></div>
                    <div class="col-12">
                        <?php
                        $tid = intval($pdetail['taskId'] ?? 0);
                        if ($tid > 0 && isset($taskList[$tid])) {
                            echo '<span class="text-info">→ ' . htmlspecialchars($taskList[$tid]) . '</span>';
                        } else {
                            echo '<span class="text-muted">Chưa có nhiệm vụ</span>';
                        }
                        ?>
                    </div>
                </div>
                <hr>
                <h6 class="fw-bold">Đặt nhanh nhiệm vụ (yêu cầu online)</h6>
                <form method="POST" class="row g-2">
                    <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Chọn nhiệm vụ</label>
                        <select name="taskId" class="form-select form-select-sm">
                            <?php foreach ($taskList as $tk => $tname): ?>
                                <option value="<?= $tk ?>" <?= $tk == $tid ? 'selected' : '' ?>><?= $tk ?> - <?= htmlspecialchars($tname) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" name="action" value="PLAYER_TASK_SET" class="btn btn-warning btn-sm" onclick="return confirm('Đặt NV mới cho <?= htmlspecialchars($pdetail['name']) ?>?')">Đặt nhiệm vụ</button>
                        <button type="submit" name="action" value="PLAYER_TASK_FINISH" class="btn btn-success btn-sm" onclick="return confirm('Hoàn thành NV hiện tại (nhảy NV tiếp theo) cho <?= htmlspecialchars($pdetail['name']) ?>?')">Hoàn thành NV hiện tại</button>
                        <button type="submit" name="action" value="PLAYER_TASK_RESET" class="btn btn-danger btn-sm" onclick="return confirm('Reset toàn bộ NV về NV 1 cho <?= htmlspecialchars($pdetail['name']) ?>?')">Reset NV về đầu</button>
                    </div>
                </form>
                <hr>
                <h6 class="fw-bold">Bỏ qua nhiều NV cùng lúc</h6>
                <form method="POST" class="row g-2">
                    <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                    <div class="col-6 col-md-2">
                        <label class="form-label small">Số NV bỏ qua</label>
                        <input type="number" name="steps" class="form-control form-control-sm" value="1" min="1" max="10">
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" name="action" value="SKIP_TASK" class="btn btn-info btn-sm" onclick="return confirm('Bỏ qua N NV cho <?= htmlspecialchars($pdetail['name']) ?>?')">Bỏ qua NV</button>
                    </div>
                </form>

            <?php elseif ($activeTab == 'skill'): ?>
                <h6 class="fw-bold mt-2">Kỹ năng đang học (từ cột <code>skill</code> DB)</h6>
                <div class="table-responsive">
                    <table class="table table-sm text-white mb-0 align-middle">
                        <thead><tr class="fw-bold text-uppercase"><th>#</th><th>ID kỹ năng</th><th>Điểm</th></tr></thead>
                        <tbody>
                        <?php if (count($pSkills) > 0): foreach ($pSkills as $sk): ?>
                            <tr>
                                <td><?= isset($sk['index']) ? intval($sk['index']) : '' ?></td>
                                <td><?= isset($sk['id']) ? intval($sk['id']) : '' ?></td>
                                <td><?= isset($sk['point']) ? intval($sk['point']) : '' ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" class="text-muted">Chưa có kỹ năng.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-2 mb-0"><small>Kỹ năng chỉ hiển thị (đọc DB). Muốn tăng điểm kỹ năng dùng ô <b>spoint</b> ở tab Thông tin.</small></p>

            <?php elseif ($activeTab == 'social'): ?>
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <h6 class="fw-bold mt-2">Bạn bè (<?= count($pFriends) ?>)</h6>
                        <table class="table table-sm text-white mb-0">
                            <thead><tr class="fw-bold text-uppercase"><th>#</th><th>Tên</th></tr></thead>
                            <tbody>
                            <?php if (count($pFriends) > 0): foreach ($pFriends as $fr): ?>
                                <tr><td><?= isset($fr['index']) ? intval($fr['index']) : '' ?></td><td><?= htmlspecialchars(strval($fr['name'] ?? '')) ?></td></tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="2" class="text-muted">Không có bạn bè.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-12 col-md-6">
                        <h6 class="fw-bold mt-2">Kẻ thù (<?= count($pEnemies) ?>)</h6>
                        <table class="table table-sm text-white mb-0">
                            <thead><tr class="fw-bold text-uppercase"><th>#</th><th>Tên</th></tr></thead>
                            <tbody>
                            <?php if (count($pEnemies) > 0): foreach ($pEnemies as $en): ?>
                                <tr><td><?= isset($en['index']) ? intval($en['index']) : '' ?></td><td><?= htmlspecialchars(strval($en['name'] ?? '')) ?></td></tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="2" class="text-muted">Không có kẻ thù.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="text-muted mt-2 mb-0"><small>Đọc từ cột <code>friends</code>/<code>enemies</code> trong DB.</small></p>

            <?php elseif ($activeTab == 'actions'): ?>
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <h6 class="fw-bold">Tài khoản</h6>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                            <button type="submit" name="action" value="KICK" class="btn btn-warning btn-sm mb-1">Kick (chỉ online)</button>
                            <button type="submit" name="action" value="BAN" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Khóa tài khoản?')">Ban</button>
                            <button type="submit" name="action" value="DELETE_CHAR" class="btn btn-dark btn-sm mb-1" onclick="return confirm('Xóa nhân vật này?')">Xóa NV</button>
                        </form>
                        <hr>
                        <h6 class="fw-bold">Đặt lại mật khẩu (tài khoản <?= htmlspecialchars($pdetail['username'] ?? '') ?>)</h6>
                        <form method="POST" class="row g-2">
                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                            <input type="hidden" name="username" value="<?= htmlspecialchars($pdetail['username'] ?? '') ?>">
                            <div class="col-8"><input type="text" name="password" class="form-control form-control-sm" minlength="6" placeholder="Mật khẩu mới (>= 6 ký tự)" required></div>
                            <div class="col-4"><button type="submit" name="action" value="PLAYER_RESET_PW" class="btn btn-primary btn-sm" onclick="return confirm('Đặt lại mật khẩu?')">Đặt lại</button></div>
                        </form>
                        <hr>
                        <h6 class="fw-bold">Đặt lượng / xu web</h6>
                        <form method="POST" class="row g-2">
                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                            <input type="hidden" name="username" value="<?= htmlspecialchars($pdetail['username'] ?? '') ?>">
                            <div class="col-4"><input type="number" name="luong" class="form-control form-control-sm" placeholder="Lượng"></div>
                            <div class="col-4"><input type="number" name="coin" class="form-control form-control-sm" placeholder="Coin (xu web)"></div>
                            <div class="col-4"><input type="number" name="tongnap" class="form-control form-control-sm" placeholder="Tổng nạp"></div>
                            <div class="col-12 mt-1"><button type="submit" name="action" value="USER_SET_CURRENCY" class="btn btn-primary btn-sm" onclick="return confirm('Đặt xu/lượng cho tài khoản?')">Lưu xu/lượng</button></div>
                        </form>
                        <p class="text-muted mt-2 mb-0"><small>Lượng lưu ở cột <code>users.luong</code>, xu web ở <code>users.coin</code>. Áp dụng cho cả online/offline.</small></p>
                    </div>
                    <div class="col-12 col-md-6">
                        <h6 class="fw-bold">Nhân vật</h6>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                            <div class="input-group input-group-sm mb-1">
                                <input type="text" name="new_name" class="form-control" maxlength="12" placeholder="Tên mới" required>
                                <button type="submit" name="action" value="CHAR_RENAME" class="btn btn-primary">Đổi tên</button>
                            </div>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                            <div class="input-group input-group-sm mb-1">
                                <input type="number" name="item_id" id="act_item_id" class="form-control" min="1" placeholder="Item ID" required>
                                <button type="button" class="btn btn-info" onclick="ItemPicker.open({mode:'single',target:'act_item_id'})"><i class="fa-solid fa-box-open"></i></button>
                                <input type="number" name="qty" class="form-control" value="1" min="1" max="9999">
                                <button type="submit" name="action" value="PLAYER_GIVE" class="btn btn-success">Tặng đồ (online)</button>
                            </div>
                        </form>
                        <hr>
                        <h6 class="fw-bold">Dịch chuyển (yêu cầu online)</h6>
                        <form method="POST" class="row g-2">
                            <input type="hidden" name="char_name" value="<?= htmlspecialchars($pdetail['name']) ?>">
                            <div class="col-4"><input type="number" name="mapId" class="form-control form-control-sm" placeholder="Map ID" required></div>
                            <div class="col-3"><input type="number" name="zoneId" class="form-control form-control-sm" placeholder="Khu" value="0"></div>
                            <div class="col-5"><button type="submit" name="action" value="PLAYER_TELEPORT" class="btn btn-info btn-sm" onclick="return confirm('Dịch chuyển <?= htmlspecialchars($pdetail['name']) ?>?')">Dịch chuyển</button></div>
                        </form>
                        <p class="text-muted mt-2 mb-0"><small>Đang online mới dịch chuyển được.</small></p>
                    </div>
                </div>
                <p class="text-muted mt-3 mb-0"><small>Tab <b>Thông tin</b> sửa chỉ số offline được. <b>Trang bị/Túi/Nhiệm vụ/Dịch chuyển</b> cần online. <b>Mật khẩu/Xu-Lượng</b> áp dụng kể cả offline.</small></p>
<?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="mt-3 text-center">
    <div class="form-check form-switch d-inline-block">
        <input class="form-check-input" type="checkbox" id="autoRefresh">
        <label class="form-check-label small text-muted" for="autoRefresh">Tự động làm mới (10 giây)</label>
    </div>
    <span class="small text-muted ms-2">Lần cập nhật: <span id="lastRefresh">-</span></span>
</div>
<script src="/static/js/item-picker.js"></script>
<script>
    (function () {
        var box = document.getElementById('autoRefresh');
        var timer = null;
        function tick() {
            var el = document.getElementById('lastRefresh');
            if (el) el.innerText = new Date().toLocaleTimeString('en-GB');
            if (box && box.checked) location.reload();
        }
        function apply() {
            if (timer) { clearInterval(timer); timer = null; }
            if (box && box.checked) timer = setInterval(tick, 10000);
        }
        if (box) { box.addEventListener('change', apply); apply(); }
        var el = document.getElementById('lastRefresh');
        if (el) el.innerText = new Date().toLocaleTimeString('en-GB');
    })();
</script>
</div>
