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

$tab = isset($_GET['tab']) ? preg_replace('/[^a-z_]/', '', strtolower($_GET['tab'])) : 'login';
if ($tab !== 'login' && $tab !== 'tx' && $tab !== 'gift') {
    $tab = 'login';
}

$limit = 100;
$rows = [];
$cols = [];

if ($tab === 'login') {
    $res = $conn->query("SELECT l.`id`, l.`user_id`, u.`username`, l.`type`, l.`description`, l.`created_at` FROM `user_logs` l LEFT JOIN `users` u ON u.`id` = l.`user_id` ORDER BY l.`id` DESC LIMIT $limit");
    if ($res) {
        $cols = ['ID', 'User', 'Username', 'Loại', 'Mô tả', 'Thời gian'];
        while ($row = $res->fetch_assoc()) {
            $rows[] = [$row['id'], $row['user_id'], $row['username'] ?? '-', $row['type'], $row['description'], $row['created_at']];
        }
    }
} elseif ($tab === 'tx') {
    $res = $conn->query("SELECT h.`id`, h.`player_id`, h.`type`, h.`type_name`, h.`luong_truoc`, h.`luong_sau`, h.`luong_ton`, h.`time` FROM `history_tx` h ORDER BY h.`id` DESC LIMIT $limit");
    if ($res) {
        $cols = ['ID', 'Nhân vật', 'Loại', 'Tên', 'Trước', 'Sau', 'Còn', 'Thời gian'];
        while ($row = $res->fetch_assoc()) {
            $rows[] = [$row['id'], $row['player_id'], $row['type'], $row['type_name'], $row['luong_truoc'], $row['luong_sau'], $row['luong_ton'], $row['time']];
        }
    }
} else { // gift
    $res = $conn->query("SELECT g.`id`, g.`user_id`, g.`player_id`, g.`gift_code`, g.`created_at` FROM `gift_code_histories` g ORDER BY g.`id` DESC LIMIT $limit");
    if ($res) {
        $cols = ['ID', 'User', 'Nhân vật', 'Mã giftcode', 'Thời gian'];
        while ($row = $res->fetch_assoc()) {
            $rows[] = [$row['id'], $row['user_id'], $row['player_id'], $row['gift_code'], $row['created_at']];
        }
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
    .admin-panel .nav-pills .nav-link { color: #0d6efd; }
    .admin-panel .nav-pills .nav-link.active { background: #0d6efd; color: #fff; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0 fw-bold"><i class="fa-solid fa-clock-rotate-left text-secondary"></i> Lịch Sử / Log</h4>
    <a class="btn btn-success btn-sm" href="/admin/home">Quay lại</a>
</div>

<div class="card p-2 mb-3">
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link <?= $tab == 'login' ? 'active' : '' ?>" href="/admin/logs?tab=login">Đăng nhập / Hoạt động</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab == 'tx' ? 'active' : '' ?>" href="/admin/logs?tab=tx">Giao dịch (Lượng)</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab == 'gift' ? 'active' : '' ?>" href="/admin/logs?tab=gift">Giftcode đã dùng</a></li>
    </ul>
</div>

<div class="card p-3">
    <h6 class="fw-bold"><?= count($rows) ?> bản ghi gần nhất</h6>
    <?php if (count($rows) > 0): ?>
    <div class="table-responsive" style="max-height:70vh;overflow:auto">
        <table class="table table-sm mb-0 align-middle">
            <thead><tr class="fw-bold text-uppercase"><?php foreach ($cols as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr><?php foreach ($r as $cell): ?><td><?= htmlspecialchars(strval($cell)) ?></td><?php endforeach; ?></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="text-center text-muted py-3">Chưa có dữ liệu.</div>
    <?php endif; ?>
</div>
</div>
