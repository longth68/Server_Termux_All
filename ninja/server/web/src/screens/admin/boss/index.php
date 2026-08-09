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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $key = isset($_POST['boss_group']) ? $_POST['boss_group'] : '';

    if (!isset($boss_groups[$key])) {
        $msg = '<div class="alert alert-danger">Nhóm boss không hợp lệ.</div>';
    } else {
        $data = json_encode(['key' => $key], JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('SPAWN_BOSS', ?, 0)");
        $stmt->bind_param("s", $data);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh triệu hồi nhóm boss <b>' . htmlspecialchars($boss_groups[$key]['name']) . '</b>. Server sẽ xử lý trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
        $stmt->close();
    }
}

$history = [];
$result = $conn->query("SELECT `id`, `command`, `data`, `status`, `created_at` FROM `web_admin_commands` WHERE `command` = 'SPAWN_BOSS' ORDER BY `id` DESC LIMIT 20");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}
$conn->close();
?>
<div class="bg-content" style="border-radius: 1rem; padding:10px">
    <div style="text-align:center;">
        <h4>Triệu hồi Boss</h4>
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
            <h5 class="fw-bold">Triệu hồi boss theo nhóm</h5>
            <form method="POST">
                <div class="mb-2">
                    <label class="fw-semibold">Chọn nhóm boss</label>
                    <select name="boss_group" class="form-select" required>
                        <?php foreach ($boss_groups as $key => $g): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($g['name']) ?> (<?= htmlspecialchars($g['bosses']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Triệu hồi toàn bộ boss của nhóm đã chọn?')">Triệu hồi boss</button>
            </form>
            <p class="text-muted mt-2 mb-0"><small>Boss được triệu hồi tại vị trí định sẵn trong map tương ứng. Người chơi tại map đó sẽ thấy boss xuất hiện ngay lập tức.</small></p>
        </div>
    </div>
</div>

<div class="mt-4">
    <h5 class="fw-bold">Lịch sử triệu hồi boss</h5>
    <?php if (count($history) > 0): ?>
        <div class="table-responsive" style="border-radius: 1rem;">
            <table class="table text-white fw-semibold mb-0" role="table">
                <thead>
                    <tr class="text-start fw-bold text-uppercase gs-0">
                        <th>ID</th>
                        <th>Dữ liệu</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <?php $d = json_decode($h['data'], true); ?>
                        <tr>
                            <td><?= $h['id'] ?></td>
                            <td><?= isset($boss_groups[$d['key']]['name']) ? htmlspecialchars($boss_groups[$d['key']]['name']) : htmlspecialchars($h['data']) ?></td>
                            <td><?= intval($h['status']) === 0 ? '<b class="text-warning">Chờ xử lý</b>' : '<b class="text-success">Đã xử lý</b>' ?></td>
                            <td><?= date('H:i d/m/Y', strtotime($h['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center"><small class="fw-semibold">Chưa có lệnh triệu hồi nào.</small></div>
    <?php endif; ?>
</div>
