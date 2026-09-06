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
$classNames = [1 => 'Kiếm', 2 => 'Tiêu', 3 => 'Kunai', 4 => 'Cung', 5 => 'Đao', 6 => 'Quạt'];

$sort = isset($_GET['sort']) ? preg_replace('/[^a-z]/', '', strtolower($_GET['sort'])) : 'level';
if (!in_array($sort, ['level', 'yen', 'xu', 'exp'])) {
    $sort = 'level';
}
$orderCol = $sort === 'exp' ? "CAST(JSON_UNQUOTE(JSON_EXTRACT(p.`data`, '$.exp')) AS UNSIGNED)" : "p.`$sort`";

$rows = [];
$sql = "SELECT p.`name`, p.`class`, p.`gender`, p.`level`, p.`yen`, p.`xu`, p.`clan`, c.`name` AS `clan_name`,
               CAST(JSON_UNQUOTE(JSON_EXTRACT(p.`data`, '$.exp')) AS UNSIGNED) AS `exp`
        FROM `players` p LEFT JOIN `clan` c ON c.`id` = p.`clan`
        ORDER BY $orderCol DESC LIMIT 100";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}
$conn->close();

$tabs = ['level' => 'Cấp độ', 'exp' => 'Kinh nghiệm', 'yen' => 'Yên', 'xu' => 'Xu'];
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
    .admin-panel .rank-1 { color: #d4af37; font-weight: bold; }
    .admin-panel .rank-2 { color: #a0a0a0; font-weight: bold; }
    .admin-panel .rank-3 { color: #cd7f32; font-weight: bold; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0 fw-bold"><i class="fa-solid fa-ranking-star text-warning"></i> Bảng Xếp Hạng</h4>
    <a class="btn btn-success btn-sm" href="/admin/home">Quay lại</a>
</div>

<div class="card p-2 mb-3">
    <ul class="nav nav-pills">
        <?php foreach ($tabs as $k => $lbl): ?>
            <li class="nav-item"><a class="nav-link <?= $sort == $k ? 'active' : '' ?>" href="/admin/top?sort=<?= $k ?>"><?= $lbl ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="card p-3">
    <h6 class="fw-bold">Top 100 theo <?= htmlspecialchars($tabs[$sort]) ?></h6>
    <div class="table-responsive" style="max-height:70vh;overflow:auto">
        <table class="table table-sm mb-0 align-middle">
            <thead><tr class="fw-bold text-uppercase"><th>#</th><th>Nhân vật</th><th>Class</th><th>Phái</th><th>Cấp</th><th>Exp</th><th>Yên</th><th>Xu</th><th>Bang hội</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td class="<?= $i < 3 ? 'rank-' . ($i + 1) : '' ?>"><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= htmlspecialchars($classNames[(int)$r['class']] ?? ('C' . (int)$r['class'])) ?></td>
                    <td><?= (int)$r['gender'] == 1 ? 'Nam' : 'Nữ' ?></td>
                    <td><?= (int)$r['level'] ?></td>
                    <td><?= number_format((int)$r['exp']) ?></td>
                    <td><?= number_format((int)$r['yen']) ?></td>
                    <td><?= number_format((int)$r['xu']) ?></td>
                    <td><?= $r['clan_name'] ? htmlspecialchars($r['clan_name']) : '<span class="text-muted">-</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!count($rows)): ?><tr><td colspan="9" class="text-muted text-center">Chưa có dữ liệu.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
