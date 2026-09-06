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
$isSelf = isset($user['username']) ? strtolower(trim($user['username'])) : '';
$classNames = [1 => 'Kiếm', 2 => 'Tiêu', 3 => 'Kunai', 4 => 'Cung', 5 => 'Đao', 6 => 'Quạt'];

// Xóa tài khoản kèm toàn bộ dữ liệu liên quan
function deleteAccountFull($conn, $uid, $username) {
    global $isSelf;
    if ($username && strtolower($username) === $isSelf) {
        return 'Không thể xóa tài khoản admin đang đăng nhập!';
    }
    // Lấy danh sách nhân vật (id + name) để xóa dữ liệu liên quan
    $charNames = [];
    $charIds = [];
    $r = $conn->query("SELECT `id`, `name` FROM `players` WHERE `user_id` = " . (int)$uid);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $charIds[] = (int)$row['id'];
            $charNames[] = $conn->real_escape_string($row['name']);
        }
    }
    $idList = implode(',', $charIds);
    $nameList = $charNames ? "'" . implode("','", $charNames) . "'" : "''";

    $tables = [];
    if ($idList !== '') {
        $tables[] = "DELETE FROM `history_tx` WHERE `player_id` IN ($idList)";
        $tables[] = "DELETE FROM `history_gift` WHERE `player_id` IN ($idList)";
        $tables[] = "DELETE FROM `history_table` WHERE `player_id` IN ($idList)";
    }
    $tables[] = "DELETE FROM `clan_member` WHERE `name` IN ($nameList)";
    $tables[] = "DELETE FROM `gift_code_histories` WHERE `user_id` = " . (int)$uid;
    $tables[] = "DELETE FROM `transactions` WHERE `user_id` = " . (int)$uid;
    $tables[] = "DELETE FROM `user_logs` WHERE `user_id` = " . (int)$uid;
    $tables[] = "DELETE FROM `user_locks` WHERE `user_id` = " . (int)$uid;
    $tables[] = "DELETE FROM `model_has_roles` WHERE `model_id` = " . (int)$uid . " AND `model_type` LIKE '%User%'";
    $tables[] = "DELETE FROM `model_has_permissions` WHERE `model_id` = " . (int)$uid . " AND `model_type` LIKE '%User%'";
    $tables[] = "DELETE FROM `personal_access_tokens` WHERE `tokenable_id` = " . (int)$uid . " AND `tokenable_type` LIKE '%User%'";
    $tables[] = "DELETE FROM `players` WHERE `user_id` = " . (int)$uid;
    $tables[] = "DELETE FROM `users` WHERE `id` = " . (int)$uid;

    foreach ($tables as $sql) {
        $conn->query($sql);
    }
    return 'OK';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action == 'acc_edit') {
        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $username = isset($_POST['username']) ? trim(strval($_POST['username'])) : '';
        if ($uid > 0) {
            $fields = [];
            $vals = [];
            if (isset($_POST['status']) && $_POST['status'] !== '') {
                $fields[] = "`status` = ?"; $vals[] = max(0, min(2, intval($_POST['status'])));
            }
            if (isset($_POST['activated']) && $_POST['activated'] !== '') {
                $fields[] = "`activated` = ?"; $vals[] = intval($_POST['activated']) ? 1 : 0;
            }
            if (isset($_POST['admin_web']) && $_POST['admin_web'] !== '') {
                $fields[] = "`admin_web` = ?"; $vals[] = intval($_POST['admin_web']) ? 1 : 0;
            }
            $banUntil = isset($_POST['ban_until']) ? trim(strval($_POST['ban_until'])) : '';
            if ($banUntil !== '') {
                $fields[] = "`ban_until` = ?"; $vals[] = $banUntil;
            }
            if ($fields) {
                $vals[] = $uid;
                $types = str_repeat('s', count($fields)) . 'i';
                $sql = "UPDATE `users` SET " . implode(', ', $fields) . " WHERE `id` = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$vals);
                $msg = $stmt->execute() ? '<div class="alert alert-success">Đã cập nhật tài khoản <b>' . htmlspecialchars($username) . '</b>.</div>' : '<div class="alert alert-danger">Lỗi cập nhật.</div>';
                $stmt->close();
            }
            // Reset mật khẩu qua lệnh server (BCrypt) - online/offline đều được
            if (isset($_POST['new_password']) && trim(strval($_POST['new_password'])) !== '') {
                $pw = trim(strval($_POST['new_password']));
                if (strlen($pw) < 6) {
                    $msg = '<div class="alert alert-warning">Mật khẩu mới phải từ 6 ký tự.</div>';
                } else {
                    $data = json_encode(['username' => $username, 'password' => $pw], JSON_UNESCAPED_UNICODE);
                    $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES ('PLAYER_RESET_PW', ?, ?, 0)");
                    $stmt->bind_param("ss", $username, $data);
                    $stmt->execute();
                    $stmt->close();
                    $msg = '<div class="alert alert-success">Đã gửi lệnh đổi mật khẩu cho <b>' . htmlspecialchars($username) . '</b>.</div>';
                }
            }
            // Cộng/set lượng - xu web qua lệnh server
            $cur = [];
            foreach (['luong', 'coin', 'tongnap'] as $f) {
                if (isset($_POST[$f]) && $_POST[$f] !== '' && is_numeric($_POST[$f])) {
                    $cur[$f] = intval($_POST[$f]);
                }
            }
            if ($cur) {
                $payload = ['username' => $username] + $cur;
                $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
                $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `target_user`, `data`, `status`) VALUES ('USER_SET_CURRENCY', ?, ?, 0)");
                $stmt->bind_param("ss", $username, $data);
                $stmt->execute();
                $stmt->close();
                $msg = '<div class="alert alert-success">Đã gửi lệnh đặt Xu/Lượng cho <b>' . htmlspecialchars($username) . '</b>.</div>';
            }
        }
        if ($uid > 0) {
            header('Location: /admin/member?id=' . $uid);
            exit;
        }
    } elseif ($action == 'acc_del') {
        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $username = isset($_POST['username']) ? trim(strval($_POST['username'])) : '';
        if ($uid > 0) {
            $res = deleteAccountFull($conn, $uid, $username);
            $msg = ($res === 'OK')
                ? '<div class="alert alert-success">Đã XÓA toàn bộ tài khoản <b>' . htmlspecialchars($username) . '</b> kèm nhân vật và dữ liệu liên quan.</div>'
                : '<div class="alert alert-danger">' . htmlspecialchars($res) . '</div>';
        }
    } elseif ($action == 'ban_until') {
        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $username = isset($_POST['username']) ? trim(strval($_POST['username'])) : '';
        $days = isset($_POST['days']) ? max(0, intval($_POST['days'])) : 0;
        if ($uid > 0) {
            if ($days > 0) {
                $dt = date('Y-m-d H:i:s', time() + $days * 86400);
                $stmt = $conn->prepare("UPDATE `users` SET `status` = 2, `ban_until` = ? WHERE `id` = ?");
                $stmt->bind_param("si", $dt, $uid);
            } else {
                $stmt = $conn->prepare("UPDATE `users` SET `status` = 1, `ban_until` = NULL WHERE `id` = ?");
                $stmt->bind_param("i", $uid);
            }
            $stmt->execute();
            $stmt->close();
            $msg = '<div class="alert alert-success">Đã ' . ($days > 0 ? 'khóa ' . $days . ' ngày' : 'mở khóa') . ' tài khoản <b>' . htmlspecialchars($username) . '</b>.</div>';
        }
        if ($uid > 0) {
            header('Location: /admin/member?id=' . $uid);
            exit;
        }
    }
}

// Tìm kiếm / danh sách tài khoản
$search = isset($_GET['q']) ? trim(strval($_GET['q'])) : '';
$limit = 100;
$sql = "SELECT u.`id`, u.`username`, u.`status`, u.`activated`, u.`admin_web`, u.`luong`, u.`coin`, u.`tongnap`, u.`online`, u.`ban_until`, u.`last_login_at`, u.`created_at`,
               (SELECT COUNT(*) FROM `players` p WHERE p.`user_id` = u.`id`) AS `num_char`
        FROM `users` u";
$where = '';
$params = [];
if ($search !== '') {
    $where = " WHERE u.`username` LIKE ? OR u.`id` = ?";
    $params = ['%' . $search . '%', is_numeric($search) ? intval($search) : -1];
}
$sql .= $where . " ORDER BY u.`id` DESC LIMIT $limit";

$accs = [];
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $params[0], $params[1]);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) { while ($row = $res->fetch_assoc()) { $accs[] = $row; } }
    $stmt->close();
} else {
    $res = $conn->query($sql);
    if ($res) { while ($row = $res->fetch_assoc()) { $accs[] = $row; } }
}

// Chi tiết tài khoản
$accDetail = null;
$accChars = [];
$accId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($accId > 0) {
    foreach ($accs as $a) { if ((int)$a['id'] === $accId) { $accDetail = $a; break; } }
    if (!$accDetail) {
        $stmt = $conn->prepare("SELECT `id`, `username`, `status`, `activated`, `admin_web`, `luong`, `coin`, `tongnap`, `online`, `ban_until`, `last_login_at`, `created_at` FROM `users` WHERE `id` = ? LIMIT 1");
        $stmt->bind_param("i", $accId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) { $accDetail = $res->fetch_assoc(); }
        $stmt->close();
    }
    if ($accDetail) {
        $stmt = $conn->prepare("SELECT `id`, `name`, `class`, `gender`, CAST(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.exp')) AS UNSIGNED) AS `exp`, `yen`, `xu`, `xuInBox`, `online` FROM `players` WHERE `user_id` = ? ORDER BY `id`");
        $stmt->bind_param("i", $accId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) { while ($row = $res->fetch_assoc()) { $accChars[] = $row; } }
        $stmt->close();
    }
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
    .admin-panel .list-group-item { background: #fff; color: #212529; border-color: #dee2e6; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0 fw-bold"><i class="fa-solid fa-id-card text-primary"></i> Quản Lý Tài Khoản</h4>
    <a class="btn btn-success btn-sm" href="/admin/home">Quay lại</a>
</div>
<?php if ($msg) echo $msg; ?>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="admin">
        <input type="hidden" name="tab" value="member">
        <div class="col-md-6"><label class="form-label small mb-0">Tìm theo username hoặc ID</label><input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Tìm</button></div>
    </form>
</div>

<div class="card p-3">
    <h6 class="fw-bold">Danh sách tài khoản (<?= count($accs) ?>)</h6>
    <div class="table-responsive" style="max-height:55vh;overflow:auto">
        <table class="table table-sm mb-0 align-middle">
            <thead><tr class="fw-bold text-uppercase"><th>ID</th><th>Username</th><th>Nhân vật</th><th>Lượng</th><th>Xu web</th><th>Tổng nạp</th><th>Status</th><th>Online</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($accs as $a): ?>
                <tr>
                    <td><?= (int)$a['id'] ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($a['username']) ?></td>
                    <td><?= (int)$a['num_char'] ?></td>
                    <td><?= number_format((int)$a['luong']) ?></td>
                    <td><?= number_format((int)$a['coin']) ?></td>
                    <td><?= number_format((int)$a['tongnap']) ?></td>
                    <td>
                        <?php
                        if ((int)$a['status'] === 2) { echo '<span class="badge bg-danger">Khóa</span>'; }
                        elseif ((int)$a['status'] === 1) { echo '<span class="badge bg-success">Active</span>'; }
                        else { echo '<span class="badge bg-secondary">Tắt</span>'; }
                        if ((int)$a['admin_web'] === 1) { echo ' <span class="badge bg-primary">Admin</span>'; }
                        ?>
                    </td>
                    <td><?= (int)$a['online'] ? '<span class="badge bg-success">ON</span>' : '<span class="text-muted">OFF</span>' ?></td>
                    <td>
                        <a class="btn btn-sm btn-info" href="/admin/member?id=<?= (int)$a['id'] ?>">Chi tiết</a>
                        <a class="btn btn-sm btn-danger" href="#del-<?= (int)$a['id'] ?>" data-bs-toggle="collapse">Xóa</a>
                        <div class="collapse" id="del-<?= (int)$a['id'] ?>">
                            <form method="POST" class="mt-2 p-2 border rounded bg-light">
                                <input type="hidden" name="action" value="acc_del">
                                <input type="hidden" name="uid" value="<?= (int)$a['id'] ?>">
                                <input type="hidden" name="username" value="<?= htmlspecialchars($a['username']) ?>">
                                <small class="text-danger fw-bold">XÓA VĨNH VIỄN toàn bộ tài khoản này (nhân vật, lịch sử, bang hội...)? Không thể khôi phục!</small><br>
                                <button type="submit" class="btn btn-danger btn-sm mt-1" onclick="return confirm('XÓA VĨNH VIỄN tài khoản <?= htmlspecialchars($a['username']) ?> kèm toàn bộ dữ liệu?')">Xác nhận Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!count($accs)): ?><tr><td colspan="9" class="text-center text-muted">Không có tài khoản.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($accDetail): ?>
<div class="card p-3 mt-3">
    <h6 class="fw-bold">Chi tiết tài khoản: <?= htmlspecialchars($accDetail['username']) ?> (ID <?= (int)$accDetail['id'] ?>)</h6>
    <div class="row g-2 mb-2 small">
        <div class="col-6 col-md-3"><b>Lượng:</b> <?= number_format((int)$accDetail['luong']) ?></div>
        <div class="col-6 col-md-3"><b>Xu web:</b> <?= number_format((int)$accDetail['coin']) ?></div>
        <div class="col-6 col-md-3"><b>Tổng nạp:</b> <?= number_format((int)$accDetail['tongnap']) ?></div>
        <div class="col-6 col-md-3"><b>Online:</b> <?= (int)$accDetail['online'] ? 'CÓ' : 'KHÔNG' ?></div>
        <div class="col-6 col-md-3"><b>Đăng nhập gần nhất:</b> <?= htmlspecialchars(strval($accDetail['last_login_at'] ?? '-')) ?></div>
        <div class="col-6 col-md-3"><b>Tạo lúc:</b> <?= htmlspecialchars(strval($accDetail['created_at'] ?? '-')) ?></div>
        <div class="col-6 col-md-3"><b>Khóa đến:</b> <?= $accDetail['ban_until'] ? htmlspecialchars($accDetail['ban_until']) : '-' ?></div>
    </div>

    <form method="POST" class="row g-2 align-items-end mt-1">
        <input type="hidden" name="action" value="acc_edit">
        <input type="hidden" name="uid" value="<?= (int)$accDetail['id'] ?>">
        <input type="hidden" name="username" value="<?= htmlspecialchars($accDetail['username']) ?>">
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Trạng thái (0/1/2)</label><input type="number" name="status" class="form-control form-control-sm" value="<?= (int)$accDetail['status'] ?>" min="0" max="2"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Kích hoạt (0/1)</label><input type="number" name="activated" class="form-control form-control-sm" value="<?= (int)$accDetail['activated'] ?>" min="0" max="1"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Admin Web (0/1)</label><input type="number" name="admin_web" class="form-control form-control-sm" value="<?= (int)$accDetail['admin_web'] ?>" min="0" max="1"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Lượng</label><input type="number" name="luong" class="form-control form-control-sm" placeholder="<?= (int)$accDetail['luong'] ?>"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Xu web</label><input type="number" name="coin" class="form-control form-control-sm" placeholder="<?= (int)$accDetail['coin'] ?>"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Tổng nạp</label><input type="number" name="tongnap" class="form-control form-control-sm" placeholder="<?= (int)$accDetail['tongnap'] ?>"></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-0">Mật khẩu mới (bỏ trống = giữ)</label><input type="text" name="new_password" class="form-control form-control-sm" minlength="6"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Khóa đến (Y-m-d H:i:s)</label><input type="text" name="ban_until" class="form-control form-control-sm" value="<?= htmlspecialchars(strval($accDetail['ban_until'] ?? '')) ?>"></div>
        <div class="col-6 col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100">Lưu</button></div>
    </form>

    <form method="POST" class="row g-2 align-items-end mt-2">
        <input type="hidden" name="action" value="ban_until">
        <input type="hidden" name="uid" value="<?= (int)$accDetail['id'] ?>">
        <input type="hidden" name="username" value="<?= htmlspecialchars($accDetail['username']) ?>">
        <div class="col-6 col-md-2"><input type="number" name="days" class="form-control form-control-sm" value="7" min="0" placeholder="Số ngày"></div>
        <div class="col-6 col-md-3"><button type="submit" class="btn btn-warning btn-sm">Khóa N ngày</button></div>
        <div class="col-6 col-md-3"><button type="submit" class="btn btn-success btn-sm" onclick="this.form.days.value='0'">Mở khóa</button></div>
    </form>

    <h6 class="fw-bold mt-3">Nhân vật của tài khoản (<?= count($accChars) ?>)</h6>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead><tr class="fw-bold text-uppercase"><th>ID</th><th>Tên</th><th>Class</th><th>Phái</th><th>Exp</th><th>Yên</th><th>Xu</th><th>Xu khóa</th><th>Online</th></tr></thead>
            <tbody>
            <?php foreach ($accChars as $ch): ?>
                <tr>
                    <td><?= (int)$ch['id'] ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($ch['name']) ?></td>
                    <td><?= htmlspecialchars($classNames[(int)$ch['class']] ?? ('C' . (int)$ch['class'])) ?></td>
                    <td><?= (int)$ch['gender'] == 1 ? 'Nam' : 'Nữ' ?></td>
                    <td><?= number_format((int)$ch['exp']) ?></td>
                    <td><?= number_format((int)$ch['yen']) ?></td>
                    <td><?= number_format((int)$ch['xu']) ?></td>
                    <td><?= number_format((int)$ch['xuInBox']) ?></td>
                    <td><?= (int)$ch['online'] ? '<span class="badge bg-success">ON</span>' : '<span class="text-muted">OFF</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!count($accChars)): ?><tr><td colspan="9" class="text-center text-muted">Tài khoản chưa có nhân vật.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
</div>
