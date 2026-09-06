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

// Danh mục sự kiện (eventClass đầy đủ để server khởi tạo qua Event.init())
$events = [
    'Exe_Z.event.OFF' => ['name' => 'TẮT sự kiện (OFF)', 'icon' => 'fa-ban', 'color' => 'secondary', 'desc' => 'Không có sự kiện nào đang chạy'],
    'Exe_Z.event.TrungThu' => ['name' => 'Trung Thu', 'icon' => 'fa-moon', 'color' => 'warning', 'desc' => 'Sự kiện Trung thu: lon đèn, bánh nướng...'],
    'Exe_Z.event.Halloween' => ['name' => 'Halloween', 'icon' => 'fa-ghost', 'color' => 'dark', 'desc' => 'Sự kiện Halloween: kẹo, bí ngô...'],
    'Exe_Z.event.Noel' => ['name' => 'Noel', 'icon' => 'fa-tree', 'color' => 'success', 'desc' => 'Sự kiện Giáng sinh: tất, quà Noel...'],
    'Exe_Z.event.LunarNewYear' => ['name' => 'Tết Nguyên Đán', 'icon' => 'fa-fire', 'color' => 'danger', 'desc' => 'Sự kiện Tết: bao đỏ, pháo hoa...'],
    'Exe_Z.event.SumMer' => ['name' => 'Hè (SumMer)', 'icon' => 'fa-umbrella-beach', 'color' => 'info', 'desc' => 'Sự kiện mùa hè'],
    'Exe_Z.event.KoroKing' => ['name' => 'KoroKing', 'icon' => 'fa-crown', 'color' => 'primary', 'desc' => 'Sự kiện KoroKing'],
    'Exe_Z.event.NgayPhuNu' => ['name' => 'Ngày Phụ nữ (20/10)', 'icon' => 'fa-venus', 'color' => 'danger', 'desc' => 'Quà tặng ngày Phụ nữ Việt Nam'],
    'Exe_Z.event.VietnameseWomensDay' => ['name' => 'Phụ nữ Việt Nam', 'icon' => 'fa-heart', 'color' => 'danger', 'desc' => 'Sự kiện 20/10'],
    'Exe_Z.event.InternationalWomensDay' => ['name' => 'Phụ nữ Quốc tế (8/3)', 'icon' => 'fa-heart', 'color' => 'danger', 'desc' => 'Sự kiện 8/3'],
];

// Sự kiện đang chạy: đọc config.properties (cùng máy Termux)
$current = '';
$endStr = '';
$cfgCandidates = [
    dirname(__DIR__, 5) . '/config.properties',
    dirname(__DIR__, 6) . '/ninja/server/config.properties',
];
foreach ($cfgCandidates as $p) {
    if (is_readable($p)) {
        $raw = @file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($raw)) {
            foreach ($raw as $ln) {
                $t = trim($ln);
                if (strpos($t, 'game.event=') === 0) {
                    $current = trim(substr($t, strlen('game.event=')));
                }
                if (strpos($t, 'event.year=') === 0) $ey = trim(substr($t, 11));
                if (strpos($t, 'event.month=') === 0) $emo = trim(substr($t, 12));
                if (strpos($t, 'event.day=') === 0) $ed = trim(substr($t, 10));
                if (strpos($t, 'event.hour=') === 0) $eh = trim(substr($t, 11));
                if (strpos($t, 'event.minute=') === 0) $emi = trim(substr($t, 12));
            }
        }
        break;
    }
}
if (!empty($ey)) {
    $endStr = "{$ed}/{$emo}/{$ey} {$eh}:{$emi}";
}

// Gửi lệnh đổi sự kiện
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eventClass'])) {
    $cls = trim(strval($_POST['eventClass']));
    if (isset($events[$cls])) {
        $data = json_encode(['eventClass' => $cls], JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('EVENT_SET', ?, 0)");
        $stmt->bind_param("s", $data);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh đổi sự kiện sang <b>' . htmlspecialchars($events[$cls]['name']) . '</b>. Server áp dụng trong vài giây (chat toàn server thông báo).</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
        $stmt->close();
    } else {
        $msg = '<div class="alert alert-danger">Sự kiện không hợp lệ.</div>';
    }
}

// Thống kê điểm sự kiện người chơi (event_points)
$epRows = [];
$r = $conn->query("SELECT `player_id`, `point`, `updated_at` FROM `event_points` ORDER BY `point` DESC LIMIT 20");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $epRows[] = $row;
    }
}
$playerNames = [];
if (count($epRows)) {
    $ids = implode(',', array_map(fn($x) => (int)$x['player_id'], $epRows));
    $r2 = $conn->query("SELECT `id`, `name` FROM `players` WHERE `id` IN ($ids)");
    if ($r2) {
        while ($row = $r2->fetch_assoc()) {
            $playerNames[(int)$row['id']] = $row['name'];
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
    .admin-panel .text-muted { color: #6c757d !important; }
    .admin-panel .btn-outline-secondary { color: #6c757d; border-color: #6c757d; }
    .admin-panel .btn-outline-secondary:hover { color: #fff; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0 fw-bold"><i class="fa-solid fa-calendar-days text-warning"></i> Quản Lý Sự Kiện</h4>
    <a class="btn btn-success btn-sm" href="/admin/home">Quay lại</a>
</div>
<?php if ($msg) echo $msg; ?>

<div class="card p-3 mb-3">
    <h6 class="fw-bold"><i class="fa-solid fa-circle-info text-info"></i> Trạng thái hiện tại</h6>
    <?php
    $curInfo = isset($events[$current]) ? $events[$current] : null;
    ?>
    <div class="row g-2 align-items-center">
        <div class="col-md-5">
            <?php if ($curInfo): ?>
                <span class="badge bg-<?= $curInfo['color'] ?> fs-6"><i class="fa-solid <?= $curInfo['icon'] ?> me-1"></i> <?= htmlspecialchars($curInfo['name']) ?></span>
            <?php else: ?>
                <span class="badge bg-secondary fs-6"><?= $current ? htmlspecialchars($current) : 'Chưa cấu hình' ?></span>
            <?php endif; ?>
        </div>
        <div class="col-md-4"><small class="text-muted">Kết thúc: <b><?= htmlspecialchars($endStr ?: '-') ?></b> (đổi trong <code>config.properties</code>)</small></div>
        <div class="col-md-3 text-md-end"><small class="text-muted">Cấu hình: <code><?= htmlspecialchars(basename($current)) ?></code></small></div>
    </div>
</div>

<div class="card p-3 mb-3">
    <h6 class="fw-bold"><i class="fa-solid fa-toggle-on text-success"></i> Bật / Tắt sự kiện (nhấn để áp dụng toàn server)</h6>
    <div class="row g-2">
        <?php foreach ($events as $cls => $ev): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <form method="POST">
                    <input type="hidden" name="eventClass" value="<?= htmlspecialchars($cls) ?>">
                    <div class="border rounded p-2 text-center h-100 <?= $cls === $current ? 'border-success bg-light' : '' ?>">
                        <i class="fa-solid <?= $ev['icon'] ?> mb-1 <?= $cls === $current ? 'text-success' : 'text-muted' ?>" style="font-size:22px"></i>
                        <div class="small fw-bold mb-1"><?= htmlspecialchars($ev['name']) ?></div>
                        <div class="text-muted" style="font-size:11px"><?= htmlspecialchars($ev['desc']) ?></div>
                        <?php if ($cls === $current): ?>
                            <span class="badge bg-success mt-1"><i class="fa-solid fa-circle-check"></i> Đang chạy</span>
                        <?php else: ?>
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100 mt-1" onclick="return confirm('Đổi sự kiện sang <?= htmlspecialchars($ev['name']) ?>? Server sẽ thông báo toàn server.')">Bật</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="text-muted mt-2 mb-0"><small>Lệnh <code>EVENT_SET</code> ghi <code>game.event</code> vào <code>config.properties</code> + nạp lại Event live (không cần restart server). Thời gian kết thúc sự kiện chỉnh trực tiếp file <code>event.year/month/day/hour/minute</code>.</small></p>
</div>

<div class="card p-3">
    <h6 class="fw-bold"><i class="fa-solid fa-star text-warning"></i> Điểm sự kiện người chơi (Top 20 — bảng <code>event_points</code>)</h6>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead><tr class="fw-bold text-uppercase"><th>#</th><th>Nhân vật</th><th>Điểm sự kiện</th><th>Cập nhật</th></tr></thead>
            <tbody>
            <?php foreach ($epRows as $i => $ep): ?>
                <tr>
                    <td class="<?= $i < 3 ? 'fw-bold text-warning' : '' ?>"><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($playerNames[(int)$ep['player_id']] ?? ('#' . (int)$ep['player_id'])) ?></td>
                    <td><?= number_format((int)$ep['point']) ?></td>
                    <td><?= htmlspecialchars(strval($ep['updated_at'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!count($epRows)): ?><tr><td colspan="4" class="text-center text-muted">Chưa có điểm sự kiện nào.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
