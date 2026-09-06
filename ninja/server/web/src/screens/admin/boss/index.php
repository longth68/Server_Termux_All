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

$boss_groups = array(
    'vdmq' => array('name' => 'Vùng đất ma quỷ', 'bosses' => 'Kên Kên Vương, U Minh Khuyển, Đại Lực Sĩ'),
    'normal' => array('name' => 'Boss thường', 'bosses' => 'Xích Phiến Thiên Long, Hỏa Ngưu Vương, Samurai Chiến Tướng, Thần Thổ'),
    'ltt' => array('name' => 'Làng truyền thuyết', 'bosses' => 'Tự Hạ Mã Thần, Mỵ Hầu Vương, Tướng Giặc'),
    'lc' => array('name' => 'Làng cổ', 'bosses' => 'Tử Lôi Điểu Thiên Long, Phù Thủy Bí Ngô, Hỏa Kỳ Lân, Băng Đế'),
    'hangvithu' => array('name' => 'Hang vĩ thú', 'bosses' => 'Juubi Shinju'),
);

function bossSendCmd($conn, $cmd, $data) {
    $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES (?, ?, 0)");
    $stmt->bind_param("ss", $cmd, $data);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $key = isset($_POST['boss_group']) ? $_POST['boss_group'] : '';
    $bossId = isset($_POST['boss_id']) ? intval($_POST['boss_id']) : -1;

    if ($action == 'spawn_group') {
        if (!isset($boss_groups[$key])) {
            $msg = '<div class="alert alert-danger">Nhóm boss không hợp lệ.</div>';
        } else {
            $data = json_encode(['key' => $key], JSON_UNESCAPED_UNICODE);
            $msg = bossSendCmd($conn, 'SPAWN_BOSS', $data)
                ? '<div class="alert alert-success">Đã gửi lệnh triệu hồi TOÀN BỘ boss nhóm <b>' . htmlspecialchars($boss_groups[$key]['name']) . '</b>.</div>'
                : '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
    } elseif ($action == 'spawn_one') {
        if (!isset($boss_groups[$key]) || $bossId < 0) {
            $msg = '<div class="alert alert-danger">Boss không hợp lệ.</div>';
        } else {
            $data = json_encode(['key' => $key, 'bossId' => $bossId], JSON_UNESCAPED_UNICODE);
            $msg = bossSendCmd($conn, 'SPAWN_BOSS', $data)
                ? '<div class="alert alert-success">Đã gửi lệnh triệu hồi boss #' . $bossId . ' (nhóm ' . htmlspecialchars($boss_groups[$key]['name']) . ').</div>'
                : '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
    } elseif ($action == 'kill_one') {
        $data = json_encode(['key' => $key, 'bossId' => $bossId], JSON_UNESCAPED_UNICODE);
        $msg = bossSendCmd($conn, 'KILL_BOSS', $data)
            ? '<div class="alert alert-success">Đã gửi lệnh TẮT boss #' . $bossId . '.</div>'
            : '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
    } elseif ($action == 'kill_all') {
        $data = '{}';
        $msg = bossSendCmd($conn, 'KILL_BOSS', $data)
            ? '<div class="alert alert-success">Đã gửi lệnh TẮT toàn bộ boss đang sống.</div>'
            : '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
    }
    header('Location: /admin/boss');
    exit;
}

// Danh sách boss (config + trạng thái live) từ bảng boss_status do server ghi mỗi ~3s
$bosses = [];
$res = $conn->query("SELECT `boss_id`, `bkey`, `mob_name`, `map_id`, `map_name`, `zone_id`, `hp`, `max_hp`, `alive`, `updated_at` FROM `boss_status` ORDER BY `bkey` ASC, `boss_id` ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $bosses[] = $row;
    }
}

$history = [];
$result = $conn->query("SELECT `id`, `command`, `data`, `status`, `created_at` FROM `web_admin_commands` WHERE `command` IN ('SPAWN_BOSS','KILL_BOSS') ORDER BY `id` DESC LIMIT 20");
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
    <h4 class="m-0 fw-bold"><i class="fa-solid fa-dragon text-danger"></i> Quản Lý Boss</h4>
    <div class="d-flex gap-2">
        <a class="btn btn-success btn-sm" href="/admin/home">Quay lại</a>
        <form method="POST" class="d-inline">
            <button type="submit" class="btn btn-danger btn-sm" name="action" value="kill_all" onclick="return confirm('TẮT toàn bộ boss đang sống?')"><i class="fa-solid fa-skull"></i> Tắt tất cả boss</button>
        </form>
    </div>
</div>
<?php if ($msg) echo $msg; ?>

<div class="alert alert-info mt-1" style="font-size:0.9rem;">
    <i class="fa fa-info-circle me-1"></i> Danh sách boss tự cập nhật mỗi ~3 giây (server ghi bảng <code>boss_status</code>). Boss <b class="text-success">ĐANG SỐNG</b> xuất hiện trên map, boss <b class="text-muted">Chưa xuất hiện</b> có thể triệu hồi riêng từng con.
</div>

<div class="card p-3 mb-3">
    <h6 class="fw-bold">Triệu hồi theo nhóm (toàn bộ boss trong nhóm)</h6>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="action" value="spawn_group">
        <div class="col-md-6">
            <select name="boss_group" class="form-select" required>
                <?php foreach ($boss_groups as $key => $g): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($g['name']) ?> — <?= htmlspecialchars($g['bosses']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button type="submit" class="btn btn-danger w-100" onclick="return confirm('Triệu hồi toàn bộ boss của nhóm?')"><i class="fa-solid fa-dragon"></i> Triệu hồi nhóm</button></div>
    </form>
</div>

<div class="card p-3">
    <h6 class="fw-bold"><i class="fa-solid fa-list text-primary"></i> Danh sách boss (<span id="bossCount"><?= count($bosses) ?></span>) — cập nhật live</h6>
    <div class="table-responsive" style="max-height:60vh;overflow:auto">
        <table class="table table-sm mb-0 align-middle">
            <thead><tr class="fw-bold text-uppercase"><th>#</th><th>Nhóm</th><th>Boss</th><th>Map</th><th>Khu</th><th>HP</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody id="bossTableBody">
            <?php foreach ($bosses as $b): ?>
                <tr id="boss-row-<?= (int)$b['boss_id'] ?>">
                    <td><?= (int)$b['boss_id'] ?></td>
                    <td><?= htmlspecialchars($boss_groups[$b['bkey']]['name'] ?? $b['bkey']) ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($b['mob_name'] ?: ('Boss #' . $b['boss_id'])) ?></td>
                    <td>[<?= (int)$b['map_id'] ?>] <?= htmlspecialchars($b['map_name']) ?></td>
                    <td class="boss-zone"><?= (int)$b['zone_id'] >= 0 ? (int)$b['zone_id'] : '-' ?></td>
                    <td class="boss-hp"><?= (int)$b['alive'] ? number_format((int)$b['hp']) . '/' . number_format((int)$b['max_hp']) : '-' ?></td>
                    <td class="boss-state"><?= (int)$b['alive'] ? '<span class="badge bg-success">ĐANG SỐNG</span>' : '<span class="badge bg-secondary">Chưa xuất hiện</span>' ?></td>
                    <td>
                        <?php if ((int)$b['alive']): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="kill_one">
                            <input type="hidden" name="boss_group" value="<?= htmlspecialchars($b['bkey']) ?>">
                            <input type="hidden" name="boss_id" value="<?= (int)$b['boss_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tắt boss này?')">Tắt</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="spawn_one">
                            <input type="hidden" name="boss_group" value="<?= htmlspecialchars($b['bkey']) ?>">
                            <input type="hidden" name="boss_id" value="<?= (int)$b['boss_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Triệu hồi boss này?')">Triệu hồi</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!count($bosses)): ?><tr><td colspan="8" class="text-center text-muted">Chưa có dữ liệu boss (server cần chạy để ghi bảng <code>boss_status</code>).</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    <h5 class="fw-bold">Lịch sử lệnh boss</h5>
    <?php if (count($history) > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr class="fw-bold text-uppercase"><th>ID</th><th>Lệnh</th><th>Dữ liệu</th><th>Trạng thái</th><th>Thời gian</th></tr></thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <?php $d = json_decode($h['data'], true); ?>
                        <tr>
                            <td><?= $h['id'] ?></td>
                            <td><?= $h['command'] === 'SPAWN_BOSS' ? '<span class="text-danger">TRIỆU HỒI</span>' : '<span class="text-warning">TẮT BOSS</span>' ?></td>
                            <td><?= isset($d['bossId']) && $d['bossId'] >= 0 ? ('boss #' . (int)$d['bossId'] . ' (' . htmlspecialchars($d['key'] ?? '') . ')') : htmlspecialchars($boss_groups[$d['key'] ?? '']['name'] ?? ($d['key'] ?? 'TẤT CẢ')) ?></td>
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
</div>

<script src="/static/js/item-picker.js"></script>
<script>
    // Theo dõi thời gian thực: cập nhật trạng thái/HP từng boss theo boss_id (không reload trang)
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    setInterval(function () {
        fetch('/apixuli/boss-status', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d || d.status !== 'ok' || !Array.isArray(d.bosses)) return;
                var cnt = document.getElementById('bossCount');
                if (cnt) cnt.innerText = d.bosses.length;
                d.bosses.forEach(function (b) {
                    var row = document.getElementById('boss-row-' + b.boss_id);
                    if (!row) return;
                    var st = row.querySelector('.boss-state');
                    var hp = row.querySelector('.boss-hp');
                    var zone = row.querySelector('.boss-zone');
                    var alive = parseInt(b.alive) === 1;
                    if (st) st.innerHTML = alive ? '<span class="badge bg-success">ĐANG SỐNG</span>' : '<span class="badge bg-secondary">Chưa xuất hiện</span>';
                    if (hp) hp.innerText = alive ? Number(b.hp).toLocaleString('vi-VN') + '/' + Number(b.max_hp).toLocaleString('vi-VN') : '-';
                    if (zone) zone.innerText = parseInt(b.zone_id) >= 0 ? b.zone_id : '-';
                });
            })
            .catch(function () {});
    }, 3000);
</script>
