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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $action = $_POST['action'];
    $id = intval($_POST['id']);

    if ($action == 'approve' || $action == 'reject') {
        $stmt = $conn->prepare("SELECT * FROM `nap_the` WHERE `id` = ? AND `status` = 0");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $req = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$req) {
            $msg = '<div class="alert alert-danger">Yêu cầu không tồn tại hoặc đã được xử lý.</div>';
        } elseif ($action == 'reject') {
            $note = isset($_POST['note']) ? trim($_POST['note']) : '';
            $stmt = $conn->prepare("UPDATE `nap_the` SET `status` = 2, `note` = ?, `updated_at` = NOW() WHERE `id` = ?");
            $stmt->bind_param("si", $note, $id);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success">Đã từ chối yêu cầu nạp thẻ của ' . htmlspecialchars($req['username']) . '.</div>';
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi xảy ra khi từ chối.</div>';
            }
            $stmt->close();
        } else {
            $amount = intval($req['amount']);
            $uid = intval($req['user_id']);

            $stmt = $conn->prepare("SELECT `id`, `balance`, `tongnap` FROM `users` WHERE `id` = ? LIMIT 1");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $u = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$u) {
                $msg = '<div class="alert alert-danger">Không tìm thấy tài khoản người nạp.</div>';
            } else {
                $receivedOverride = (isset($_POST['received']) && $_POST['received'] !== '') ? intval($_POST['received']) : 0;
                $received = $receivedOverride > 0 ? $receivedOverride : (int) calMoneyForUser(intval($u['tongnap']), $amount);
                $balanceBefore = intval($u['balance']);
                $balanceAfter = $balanceBefore + $received;
                $newTongnap = intval($u['tongnap']) + $amount;
                $tranId = 'napthe' . time() . $id;
                $desc = 'Nạp thẻ cào ' . $req['network'] . ' ' . number_format($amount) . 'đ';

                $conn->begin_transaction();
                try {
                    $upd = $conn->prepare("UPDATE `nap_the` SET `status` = 1, `received` = ?, `updated_at` = NOW() WHERE `id` = ? AND `status` = 0");
                    $upd->bind_param("ii", $received, $id);
                    $upd->execute();
                    if ($upd->affected_rows !== 1) {
                        throw new Exception('Yêu cầu này đã được xử lý.');
                    }
                    $upd->close();

                    $stmtUs = $conn->prepare("UPDATE `users` SET `balance` = ?, `tongnap` = ? WHERE `id` = ?");
                    $stmtUs->bind_param("iii", $balanceAfter, $newTongnap, $uid);
                    $stmtUs->execute();
                    $stmtUs->close();

                    $stmtTx = $conn->prepare("INSERT INTO `transactions` (`user_id`, `order_id`, `order_type`, `net_amount`, `fees`, `balance_before`, `balance_change`, `balance_after`, `luong_before`, `luong_change`, `luong_after`, `description`, `status`, `checksum`, `created_at`, `updated_at`) VALUES (?, ?, 'napthe', ?, 0, ?, ?, ?, 0, 0, 0, ?, 1, '', NOW(), NOW())");
                    $stmtTx->bind_param("isiiiis", $uid, $tranId, $received, $balanceBefore, $received, $balanceAfter, $desc);
                    $stmtTx->execute();
                    $stmtTx->close();

                    $conn->commit();
                    $msg = '<div class="alert alert-success">Đã duyệt nạp ' . number_format($amount) . 'đ cho ' . htmlspecialchars($req['username']) . ' (+' . number_format($received) . ' Coin).</div>';
                } catch (Exception $e) {
                    $conn->rollback();
                    $msg = '<div class="alert alert-danger">Lỗi: ' . $e->getMessage() . '</div>';
                }
            }
        }
    }
}

$pending = [];
$processed = [];
$result = $conn->query("SELECT n.*, u.tongnap AS user_tongnap, u.balance AS user_balance FROM `nap_the` n LEFT JOIN `users` u ON u.id = n.user_id ORDER BY n.id DESC LIMIT 100");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ((int) $row['status'] === 0) {
            $pending[] = $row;
        } else {
            $processed[] = $row;
        }
    }
}
$conn->close();
?>
<div class="bg-content" style="border-radius: 1rem; padding:10px">
    <div style="text-align:center;">
        <h4>Duyệt yêu cầu nạp thẻ</h4>
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
    <h5>Đang chờ duyệt (<?= count($pending) ?>)</h5>
    <?php if (count($pending) > 0): ?>
        <div class="table-responsive mb-4" style="border-radius: 1rem;">
            <table class="table text-white fw-semibold mb-0" role="table">
                <thead>
                    <tr class="text-start fw-bold text-uppercase gs-0">
                        <th>ID</th>
                        <th>Người nạp</th>
                        <th>Nhà mạng</th>
                        <th>Mệnh giá</th>
                        <th>Serial</th>
                        <th>Mã thẻ</th>
                        <th>Nhận được</th>
                        <th>Thời gian</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['username']) ?></td>
                            <td><?= htmlspecialchars($p['network']) ?></td>
                            <td><?= number_format($p['amount']) ?> đ</td>
                            <td><?= htmlspecialchars($p['serial']) ?></td>
                            <td><?= htmlspecialchars($p['pin']) ?></td>
                            <td><?= number_format(calMoneyForUser((int) $p['user_tongnap'], (int) $p['amount'])) ?> Coin</td>
                            <td><?= date('H:i d/m/Y', strtotime($p['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline-block; margin-bottom:4px;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <input type="number" name="received" min="0" placeholder="Coin (trống = tự tính)" class="form-control form-control-sm d-inline-block" style="width:150px;">
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Duyệt nạp <?= number_format($p['amount']) ?>đ cho <?= htmlspecialchars($p['username']) ?>?')">Duyệt</button>
                                </form>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <input type="text" name="note" placeholder="Lý do (tùy chọn)" class="form-control form-control-sm d-inline-block" style="width:120px;">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Từ chối yêu cầu này?')">Từ chối</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center"><small class="fw-semibold">Hiện không có yêu cầu nạp thẻ nào đang chờ duyệt.</small></div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <h5>Đã xử lý (gần nhất)</h5>
    <?php if (count($processed) > 0): ?>
        <div class="table-responsive mb-4" style="border-radius: 1rem;">
            <table class="table text-white fw-semibold mb-0" role="table">
                <thead>
                    <tr class="text-start fw-bold text-uppercase gs-0">
                        <th>ID</th>
                        <th>Người nạp</th>
                        <th>Mệnh giá</th>
                        <th>Nhận được</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processed as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['username']) ?></td>
                            <td><?= number_format($p['amount']) ?> đ</td>
                            <td><?= $p['status'] == 1 ? number_format($p['received']) . ' Coin' : '-' ?></td>
                            <td><?= $p['status'] == 1 ? '<b class="text-success">Thành công</b>' : '<b class="text-danger">Từ chối</b>' ?></td>
                            <td><?= htmlspecialchars($p['note'] ?? '') ?></td>
                            <td><?= date('H:i d/m/Y', strtotime($p['updated_at'] ?: $p['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center"><small class="fw-semibold">Chưa có yêu cầu nào được xử lý.</small></div>
    <?php endif; ?>
</div>
