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

    if ($action == 'spawn') {
        $map = isset($_POST['map']) ? intval($_POST['map']) : 0;
        $count = isset($_POST['count']) ? max(1, intval($_POST['count'])) : 1;
        $level = isset($_POST['level']) ? max(1, intval($_POST['level'])) : 10;
        $hp = isset($_POST['hp']) ? max(100, intval($_POST['hp'])) : 20000;
        $damage = isset($_POST['damage']) ? max(10, intval($_POST['damage'])) : 1500;
        $speed = isset($_POST['speed']) ? max(0, intval($_POST['speed'])) : 0;

        $data = json_encode(['map' => $map, 'count' => $count, 'level' => $level, 'hp' => $hp, 'damage' => $damage, 'speed' => $speed], JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('SPAWN_BOT', ?, 0)");
        $stmt->bind_param("s", $data);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh triệu hồi ' . $count . ' bot (map ' . $map . ', lv ' . $level . ', HP ' . number_format($hp) . ', dmg ' . number_format($damage) . '). Server sẽ xử lý trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
        $stmt->close();
    } elseif ($action == 'kill') {
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('KILL_BOT', '{}', 0)");
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh xóa toàn bộ bot. Server sẽ xử lý trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
        $stmt->close();
    }
}

$maps = [];
$result = $conn->query("SELECT `id`, `name` FROM `map` ORDER BY `id` ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $maps[] = $row;
    }
}

$status = null;
$result = $conn->query("SELECT * FROM `server_status` WHERE `id` = 1 LIMIT 1");
if ($result) {
    $status = $result->fetch_assoc();
}

$history = [];
$result = $conn->query("SELECT `id`, `command`, `target_user`, `data`, `status`, `created_at` FROM `web_admin_commands` WHERE `command` IN ('SPAWN_BOT','KILL_BOT') ORDER BY `id` DESC LIMIT 30");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}
$conn->close();
?>
<div class="bg-content" style="border-radius: 1rem; padding:10px">
    <div style="text-align:center;">
        <h4>Quản lý Bot AI</h4>
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

<?php if ($status): ?>
    <div class="mt-3 text-center">
        <small class="fw-semibold">
            Bot đang hoạt động: <b><?= intval($status['bots']) ?></b> &nbsp;|&nbsp;
            Người online: <b><?= intval($status['online']) ?></b> &nbsp;|&nbsp;
            Cập nhật: <?= date('H:i:s d/m/Y', strtotime($status['updated_at'] ?: 'now')) ?>
        </small>
    </div>
<?php endif; ?>

<div class="alert alert-info mt-3" style="font-size: 0.9rem;">
    <i class="fa fa-info-circle me-1"></i> <b>Lưu ý:</b> Hệ thống <b>không lưu danh sách bot chi tiết</b> để tối ưu hóa bộ nhớ RAM cho server (do bot tự động sinh ra và biến mất liên tục khi có/không có người chơi thật). Trang Admin chỉ hiển thị tổng số lượng bot đang chạy và lịch sử các lệnh đã gọi.
</div>

<div class="mt-3">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Triệu hồi bot tự động</h5>
            <form method="POST">
                <input type="hidden" name="action" value="spawn">
                <div class="row g-2 mb-2">
                    <div class="col-12 col-md-4">
                        <label class="fw-semibold">Map</label>
                        <select name="map" class="form-select" required>
                            <?php foreach ($maps as $m): ?>
                                <option value="<?= intval($m['id']) ?>" <?= intval($m['id']) === 0 ? 'selected' : '' ?>>
                                    [<?= intval($m['id']) ?>] <?= htmlspecialchars($m['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Số lượng</label>
                        <input type="number" name="count" class="form-control" value="1" min="1" max="50">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Level</label>
                        <input type="number" name="level" class="form-control" value="10" min="1" max="200">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">HP</label>
                        <input type="number" name="hp" class="form-control" value="20000" min="100">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Sát thương</label>
                        <input type="number" name="damage" class="form-control" value="1500" min="10">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Tốc độ (Speed)</label>
                        <select name="speed" class="form-select">
                            <option value="0">Mặc định</option>
                            <option value="1">1 (Chậm)</option>
                            <option value="2">2 (Bình thường)</option>
                            <option value="3">3 (Nhanh)</option>
                            <option value="4">4 (Rất nhanh)</option>
                            <option value="5">5 (Siêu tốc)</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Triệu hồi</button>
                    <button type="submit" class="btn btn-danger" name="action" value="kill" onclick="return confirm('Xóa toàn bộ bot đang hoạt động?')">Xóa toàn bộ bot</button>
                </div>
            </form>
            <p class="text-muted mt-2 mb-0"><small>Giới hạn tối đa 3 bot / khu vực. Bot tự đánh quái, nhặt đồ, hồi phục và hồi sinh; tự biến mất sau 3 giờ.</small></p>
        </div>
    </div>
</div>

<div class="mt-4">
    <h5 class="fw-bold">Lịch sử lệnh bot</h5>
    <?php if (count($history) > 0): ?>
        <div class="table-responsive" style="border-radius: 1rem;">
            <table class="table text-white fw-semibold mb-0" role="table">
                <thead>
                    <tr class="text-start fw-bold text-uppercase gs-0">
                        <th>ID</th>
                        <th>Lệnh</th>
                        <th>Dữ liệu</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= $h['id'] ?></td>
                            <td><?= htmlspecialchars($h['command']) ?></td>
                            <td><?= htmlspecialchars($h['data']) ?></td>
                            <td><?= intval($h['status']) === 0 ? '<b class="text-warning">Chờ xử lý</b>' : '<b class="text-success">Đã xử lý</b>' ?></td>
                            <td><?= date('H:i d/m/Y', strtotime($h['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center"><small class="fw-semibold">Chưa có lệnh bot nào.</small></div>
    <?php endif; ?>
</div>
