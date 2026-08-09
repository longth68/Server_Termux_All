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

    if ($action == 'save_all') {
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('SERVER_CONTROL', '{\"do\":\"save_all\"}', 0)");
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh lưu toàn bộ dữ liệu. Server sẽ lưu trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
        $stmt->close();
    } elseif ($action == 'maintenance') {
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('SERVER_CONTROL', '{\"do\":\"maintenance\"}', 0)");
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh đưa máy chủ vào bảo trì. Server sẽ xử lý trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
        $stmt->close();
    } elseif ($action == 'update_config') {
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('UPDATE_SERVER_CONFIG', '{}', 0)");
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh tải lại cấu hình. Server sẽ tải lại config trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
        $stmt->close();
    } elseif ($action == 'set_exp') {
        $expVal = isset($_POST['exp']) ? max(1, (int)$_POST['exp']) : 1;
        $njtlVal = isset($_POST['njtl']) ? max(1, (int)$_POST['njtl']) : 1;
        // Cập nhật (nếu có dòng) hoặc thêm mới (nếu chưa có) cho từng key
        $upd1 = $conn->query("UPDATE `options` SET `value` = '$expVal' WHERE `key` = 'expserver'");
        if ($upd1 === false || $conn->affected_rows == 0) {
            $conn->query("DELETE FROM `options` WHERE `key` = 'expserver'");
            $conn->query("INSERT INTO `options` (`key`, `value`) VALUES ('expserver', '$expVal')");
        }
        $upd2 = $conn->query("UPDATE `options` SET `value` = '$njtlVal' WHERE `key` = 'levelnjtl'");
        if ($upd2 === false || $conn->affected_rows == 0) {
            $conn->query("DELETE FROM `options` WHERE `key` = 'levelnjtl'");
            $conn->query("INSERT INTO `options` (`key`, `value`) VALUES ('levelnjtl', '$njtlVal')");
        }
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('UPDATE_SERVER_CONFIG', '{}', 0)");
        $stmt->execute();
        $stmt->close();
        $msg = '<div class="alert alert-success">Đã đổi Tỉ lệ EXP = x' . $expVal . ', Năng lực = x' . $njtlVal . '. Server sẽ áp dụng trong vài giây.</div>';
    }
}

// Đọc tỉ lệ exp hiện tại
$expVal = 1;
$njtlVal = 1;
$expRes = $conn->query("SELECT `value` FROM `options` WHERE `key` = 'expserver' LIMIT 1");
if ($expRes && $row = $expRes->fetch_assoc()) $expVal = (int)$row['value'];
$njtlRes = $conn->query("SELECT `value` FROM `options` WHERE `key` = 'levelnjtl' LIMIT 1");
if ($njtlRes && $row = $njtlRes->fetch_assoc()) $njtlVal = (int)$row['value'];

$status = null;
$result = $conn->query("SELECT * FROM `server_status` WHERE `id` = 1 LIMIT 1");
if ($result) {
    $status = $result->fetch_assoc();
}

$history = [];
$result = $conn->query("SELECT `id`, `command`, `data`, `status`, `created_at` FROM `web_admin_commands` WHERE `command` IN ('SERVER_CONTROL','UPDATE_SERVER_CONFIG') ORDER BY `id` DESC LIMIT 20");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}
$conn->close();
?>
<div class="bg-content" style="border-radius: 1rem; padding:10px">
    <div style="text-align:center;">
        <h4>Quản lý máy chủ</h4>
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
    <?php if ($status): ?>
        <div class="card">
            <div class="card-body">
                <h5 class="fw-bold">Trạng thái máy chủ</h5>
                <div class="row text-center g-2 mt-1">
                    <div class="col-12 col-md-4">
                        <div class="p-3" style="border-radius: 1rem; background: rgba(255,255,255,.08);">
                            <div class="fw-bold">
                                <?php
                                    $is_online = false;
                                    if ($status && !empty($status['updated_at'])) {
                                        $diff = time() - strtotime($status['updated_at']);
                                        if ($diff <= 60) {
                                            $is_online = true;
                                        }
                                    }
                                ?>
                                <?= $is_online ? '<span class="text-success">ONLINE</span>' : '<span class="text-danger">OFFLINE</span>' ?>
                            </div>
                            <small class="text-muted">Trạng thái</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3" style="border-radius: 1rem; background: rgba(255,255,255,.08);">
                            <div class="fw-bold"><?= intval($status['online']) ?></div>
                            <small class="text-muted">Người online</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3" style="border-radius: 1rem; background: rgba(255,255,255,.08);">
                            <div class="fw-bold"><?= intval($status['bots']) ?></div>
                            <small class="text-muted">Bot hoạt động</small>
                        </div>
                    </div>
                </div>
                <div class="row text-center g-2 mt-2">
                    <div class="col-12 col-md-4">
                        <div class="p-3" style="border-radius: 1rem; background: rgba(255,255,255,.08);">
                            <div class="fw-bold"><?= number_format(intval($status['memory_mb'])) ?> MB</div>
                            <small class="text-muted">Bộ nhớ Java</small>
                        </div>
                    </div>
                    <div class="col-12 col-md-8">
                        <div class="p-3" style="border-radius: 1rem; background: rgba(255,255,255,.08);">
                            <div class="fw-bold"><?= date('H:i:s d/m/Y', strtotime($status['updated_at'] ?: 'now')) ?></div>
                            <small class="text-muted">Cập nhật lần cuối</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center"><small class="fw-semibold">Chưa có dữ liệu trạng thái. Nếu máy chủ đang chạy, dữ liệu sẽ được cập nhật tự động.</small></div>
    <?php endif; ?>
</div>

<div class="mt-3">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Thao tác máy chủ</h5>
            <form method="POST">
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success" name="action" value="save_all">Lưu toàn bộ dữ liệu</button>
                    <button type="submit" class="btn btn-danger" name="action" value="maintenance" onclick="return confirm('Đưa máy chủ vào chế độ bảo trì? Người chơi sẽ bị đá ra khỏi game.')">Vào bảo trì</button>
                    <button type="submit" class="btn btn-warning" name="action" value="update_config">Tải lại cấu hình</button>
                </div>
            </form>
            <p class="text-muted mt-2 mb-0"><small>Lưu dữ liệu: lưu nhanh toàn bộ nhân vật & tài khoản. Bảo trì: đưa server về trạng thái bảo trì, đá toàn bộ người chơi. Tải lại cấu hình: áp dụng thay đổi tỉ lệ exp/beri/drop trong file config server.</small></p>
        </div>
    </div>
</div>

<div class="mt-3">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Tỉ lệ EXP & Năng lực</h5>
            <p class="text-muted"><small>Hiện tại: EXP = x<strong><?= $expVal ?></strong>, Năng lực = x<strong><?= $njtlVal ?></strong></small></p>
            <form method="POST">
                <div class="row g-2 mb-2">
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Tỉ lệ EXP</label>
                        <input type="number" min="1" name="exp" class="form-control" value="<?= $expVal ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Tỉ lệ Năng lực</label>
                        <input type="number" min="1" name="njtl" class="form-control" value="<?= $njtlVal ?>">
                    </div>
                    <div class="col-12 col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100" name="action" value="set_exp">Áp dụng</button>
                    </div>
                </div>
                <p class="text-muted mb-0"><small>Ví dụ: đặt EXP = 10 để người chơi nhận gấp 10 lần kinh nghiệm khi đánh quái.</small></p>
            </form>
        </div>
    </div>
</div>

<div class="mt-4">
    <h5 class="fw-bold">Lịch sử lệnh máy chủ</h5>
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
        <div class="text-center"><small class="fw-semibold">Chưa có lệnh nào.</small></div>
    <?php endif; ?>
</div>
