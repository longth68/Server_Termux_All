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
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($title == '' || $content == '') {
        $msg = '<div class="alert alert-danger">Vui lòng nhập đầy đủ tiêu đề và nội dung.</div>';
    } else {
        $data = json_encode(['title' => $title, 'content' => $content], JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('SEND_NOTICE', ?, 0)");
        $stmt->bind_param("s", $data);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi thông báo. Người chơi online sẽ nhận được trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi thông báo.</div>';
        }
        $stmt->close();
    }
}

$history = [];
$result = $conn->query("SELECT `id`, `command`, `data`, `status`, `created_at` FROM `web_admin_commands` WHERE `command` = 'SEND_NOTICE' ORDER BY `id` DESC LIMIT 30");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}
$conn->close();
?>
<div class="bg-content" style="border-radius: 1rem; padding:10px">
    <div style="text-align:center;">
        <h4>Thông báo toàn máy chủ</h4>
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
            <h5 class="fw-bold">Gửi thông báo</h5>
            <form method="POST">
                <div class="mb-2">
                    <label class="fw-semibold">Tiêu đề</label>
                    <input type="text" name="title" class="form-control" placeholder="VD: Bảo trì server" required>
                </div>
                <div class="mb-2">
                    <label class="fw-semibold">Nội dung</label>
                    <textarea name="content" class="form-control" rows="3" placeholder="Nội dung thông báo hiển thị cho tất cả người chơi online" required></textarea>
                </div>
                <button type="submit" class="btn btn-success">Gửi thông báo</button>
            </form>
            <p class="text-muted mt-2 mb-0"><small>Thông báo hiển thị dạng khung thông báo (alert) cho mọi người chơi đang online.</small></p>
        </div>
    </div>
</div>

<div class="mt-4">
    <h5 class="fw-bold">Lịch sử thông báo</h5>
    <?php if (count($history) > 0): ?>
        <div class="table-responsive" style="border-radius: 1rem;">
            <table class="table text-white fw-semibold mb-0" role="table">
                <thead>
                    <tr class="text-start fw-bold text-uppercase gs-0">
                        <th>ID</th>
                        <th>Nội dung</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <?php $d = json_decode($h['data'], true); ?>
                        <tr>
                            <td><?= $h['id'] ?></td>
                            <td><b><?= htmlspecialchars($d['title'] ?? '') ?></b><br><small><?= htmlspecialchars($d['content'] ?? '') ?></small></td>
                            <td><?= intval($h['status']) === 0 ? '<b class="text-warning">Chờ xử lý</b>' : '<b class="text-success">Đã gửi</b>' ?></td>
                            <td><?= date('H:i d/m/Y', strtotime($h['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center"><small class="fw-semibold">Chưa có thông báo nào.</small></div>
    <?php endif; ?>
</div>
