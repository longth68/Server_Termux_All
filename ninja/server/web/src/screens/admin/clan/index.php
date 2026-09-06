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

$classNames = [1 => 'Kiếm', 2 => 'Tiêu', 3 => 'Kunai', 4 => 'Cung', 5 => 'Đao', 6 => 'Quạt'];
$typeNames = [0 => 'Thành viên', 1 => 'Phó bang', 2 => 'Bang chủ'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $cid = isset($_POST['clan_id']) ? intval($_POST['clan_id']) : 0;
    if ($action == 'clan_edit' && $cid > 0) {
        $coin = isset($_POST['coin']) ? intval($_POST['coin']) : 0;
        $level = isset($_POST['level']) ? intval($_POST['level']) : 1;
        $exp = isset($_POST['exp']) ? intval($_POST['exp']) : 0;
        $alert = isset($_POST['alert']) ? trim(strval($_POST['alert'])) : '';
        $stmt = $conn->prepare("UPDATE `clan` SET `coin` = ?, `level` = ?, `exp` = ?, `alert` = ? WHERE `id` = ?");
        $stmt->bind_param("iiiis", $coin, $level, $exp, $alert, $cid);
        $msg = $stmt->execute()
            ? '<div class="alert alert-success">Đã cập nhật bang hội #' . $cid . '. Lưu ý: thay đổi áp dụng khi bang hội được nạp lại (restart server hoặc thành viên vào lại).</div>'
            : '<div class="alert alert-danger">Lỗi cập nhật.</div>';
        $stmt->close();
    } elseif ($action == 'member_del') {
        $mid = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
        if ($mid > 0) {
            $stmt = $conn->prepare("DELETE FROM `clan_member` WHERE `id` = ?");
            $stmt->bind_param("i", $mid);
            $stmt->execute();
            $stmt->close();
            $msg = '<div class="alert alert-success">Đã xóa thành viên khỏi DB (kick offline).</div>';
        }
    }
    if ($cid > 0) {
        header('Location: /admin/clan?clan=' . $cid);
        exit;
    }
}

$clans = [];
$res = $conn->query("SELECT c.`id`, c.`name`, c.`main_name`, c.`level`, c.`coin`, c.`exp`, c.`alert`, (SELECT COUNT(*) FROM `clan_member` m WHERE m.`clan` = c.`id`) AS `members` FROM `clan` c ORDER BY c.`level` DESC, c.`id` ASC LIMIT 200");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $clans[] = $row;
    }
}

$cur = isset($_GET['clan']) ? intval($_GET['clan']) : (count($clans) ? (int)$clans[0]['id'] : 0);
$members = [];
$curClan = null;
if ($cur > 0) {
    foreach ($clans as $c) {
        if ((int)$c['id'] === $cur) { $curClan = $c; break; }
    }
    $stmt = $conn->prepare("SELECT `id`, `name`, `class_id`, `level`, `point_clan`, `point_clan_week`, `type` FROM `clan_member` WHERE `clan` = ? ORDER BY `type` DESC, `level` DESC");
    $stmt->bind_param("i", $cur);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $members[] = $row;
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
    .admin-panel .list-group-item { background: #fff; color: #212529; border-color: #dee2e6; }
    .admin-panel .list-group-item.active { background: #0d6efd; border-color: #0d6efd; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0 fw-bold"><i class="fa-solid fa-shield-halved text-primary"></i> Quản Lý Bang Hội</h4>
    <a class="btn btn-success btn-sm" href="/admin/home">Quay lại</a>
</div>
<?php if ($msg) echo $msg; ?>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card p-2">
            <h6 class="fw-bold px-2">Danh sách bang (<?= count($clans) ?>)</h6>
            <div class="list-group list-group-flush" style="max-height:60vh;overflow:auto">
                <?php foreach ($clans as $c): ?>
                    <a href="/admin/clan?clan=<?= (int)$c['id'] ?>" class="list-group-item list-group-item-action <?= (int)$c['id'] === $cur ? 'active' : '' ?>">
                        <b><?= htmlspecialchars($c['name']) ?></b> <small class="text-muted">#<?= (int)$c['id'] ?></small>
                        <div class="small">Lv <?= (int)$c['level'] ?> · <?= (int)$c['members'] ?> thành viên · <?= number_format((int)$c['coin']) ?> xu</div>
                    </a>
                <?php endforeach; ?>
                <?php if (!count($clans)): ?><div class="text-muted p-2">Chưa có bang hội.</div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <?php if ($curClan): ?>
        <div class="card p-3 mb-3">
            <h6 class="fw-bold">Sửa bang: <?= htmlspecialchars($curClan['name']) ?> (<?= htmlspecialchars($curClan['main_name']) ?>)</h6>
            <form method="POST" class="row g-2 align-items-end">
                <input type="hidden" name="action" value="clan_edit">
                <input type="hidden" name="clan_id" value="<?= (int)$curClan['id'] ?>">
                <div class="col-6 col-md-2"><label class="form-label small mb-0">Xu bang</label><input type="number" name="coin" class="form-control form-control-sm" value="<?= (int)$curClan['coin'] ?>"></div>
                <div class="col-6 col-md-2"><label class="form-label small mb-0">Level</label><input type="number" name="level" class="form-control form-control-sm" value="<?= (int)$curClan['level'] ?>" min="1"></div>
                <div class="col-6 col-md-2"><label class="form-label small mb-0">Exp</label><input type="number" name="exp" class="form-control form-control-sm" value="<?= (int)$curClan['exp'] ?>"></div>
                <div class="col-12 col-md-4"><label class="form-label small mb-0">Thông báo (alert)</label><input type="text" name="alert" class="form-control form-control-sm" value="<?= htmlspecialchars(strval($curClan['alert'] ?? '')) ?>"></div>
                <div class="col-12 col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100">Lưu bang</button></div>
            </form>
        </div>
        <div class="card p-3">
            <h6 class="fw-bold">Thành viên (<?= count($members) ?>)</h6>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead><tr class="fw-bold text-uppercase"><th>Tên</th><th>Class</th><th>Lv</th><th>Điểm bang</th><th>Tuần</th><th>Chức vụ</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($m['name']) ?></td>
                            <td><?= htmlspecialchars($classNames[(int)$m['class_id']] ?? ('C' . (int)$m['class_id'])) ?></td>
                            <td><?= (int)$m['level'] ?></td>
                            <td><?= number_format((int)$m['point_clan']) ?></td>
                            <td><?= number_format((int)$m['point_clan_week']) ?></td>
                            <td><span class="badge bg-<?= (int)$m['type'] >= 2 ? 'danger' : ((int)$m['type'] == 1 ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($typeNames[(int)$m['type']] ?? ('type ' . (int)$m['type'])) ?></span></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="member_del">
                                    <input type="hidden" name="clan_id" value="<?= $cur ?>">
                                    <input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa <?= htmlspecialchars($m['name']) ?> khỏi bang?')">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!count($members)): ?><tr><td colspan="7" class="text-muted text-center">Bang trống.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="card p-3"><div class="text-muted">Chọn một bang hội để xem chi tiết.</div></div>
        <?php endif; ?>
    </div>
</div>
</div>
