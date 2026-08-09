<?php
$_Title = "Quản lý Nạp tiền";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$msg = "";

// Xử lý Duyệt/Từ chối nạp tiền
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = intval($_POST['id']);

    if ($action == 'approve' || $action == 'reject') {
        $stmt = $conn->prepare("SELECT * FROM napthe WHERE id = ? AND status = 99");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_nap = $row['user_nap'];
            $amount = $row['amount'];
            
            if ($action == 'approve') {
                $price = $amount * $_GiaTri;
                
                try {
                    $conn->beginTransaction();
                    
                    // Cập nhật trạng thái thành công
                    $upd = $conn->prepare("UPDATE napthe SET status = 1 WHERE id = ? AND status = 99");
                    $upd->execute([$id]);
                    if ($upd->rowCount() !== 1) {
                        throw new RuntimeException('Yeu cau nay da duoc xu ly.');
                    }

                    $accountUpdate = $conn->prepare("UPDATE accounts SET tongnap = COALESCE(tongnap, 0) + ? WHERE user = ?");
                    $accountUpdate->execute([$amount, $user_nap]);
                    if ($accountUpdate->rowCount() !== 1) {
                        throw new RuntimeException('Khong tim thay tai khoan nap tien.');
                    }
                    
                    // Cộng tiền cho user thông qua command
                    $action_data = json_encode(['amount' => $price]);
                    $cmd = $conn->prepare("INSERT INTO web_admin_commands (command, target_user, data, status) VALUES ('RECHARGE', ?, ?, 0)");
                    $cmd->execute([$user_nap, $action_data]);
                    
                    $conn->commit();
                    $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Đã duyệt thành công yêu cầu nạp " . number_format($amount) . " Extol cho tài khoản $user_nap!</div>";
                } catch (Exception $e) {
                    $conn->rollBack();
                    $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>Lỗi: " . $e->getMessage() . "</div>";
                }
            } else {
                // Từ chối
                $upd = $conn->prepare("UPDATE napthe SET status = 2 WHERE id = ?");
                $upd->execute([$id]);
                $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>Đã từ chối yêu cầu nạp tiền của $user_nap.</div>";
            }
        }
    }
}

// Lấy danh sách đang chờ duyệt
$stmt = $conn->query("SELECT * FROM napthe WHERE status = 99 ORDER BY created_at DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mx-auto p-4 max-w-5xl">
    <h2 class="text-2xl font-bold mb-4">Duyệt Yêu Cầu Nạp Tiền</h2>
    <?= $msg ?>

    <div class="bg-white rounded shadow p-4">
        <div class="overflow-x-auto">
        <table class="w-full text-sm text-left min-w-[600px]">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Người chơi</th>
                    <th class="px-4 py-3">Số tiền</th>
                    <th class="px-4 py-3">Nhận được</th>
                    <th class="px-4 py-3">Thời gian</th>
                    <th class="px-4 py-3 text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($requests) > 0): ?>
                    <?php foreach ($requests as $req): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3"><?= $req['id'] ?></td>
                            <td class="px-4 py-3 font-bold text-blue-600"><?= htmlspecialchars($req['user_nap']) ?></td>
                            <td class="px-4 py-3 font-bold text-green-600"><?= number_format($req['amount']) ?>đ</td>
                            <td class="px-4 py-3"><?= number_format($req['amount']) ?> Extol</td>
                            <td class="px-4 py-3"><?= $req['created_at'] ?></td>
                            <td class="px-4 py-3 flex gap-2 justify-center">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                    <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded" onclick="return confirm('Bạn có chắc chắn muốn duyệt nạp tiền cho người chơi này?')">Duyệt</button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                    <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded" onclick="return confirm('Từ chối nạp tiền?')">Từ chối</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            Hiện không có yêu cầu nạp tiền nào đang chờ duyệt.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
