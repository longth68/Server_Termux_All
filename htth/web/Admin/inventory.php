<?php
$_Title = "Quản lý Túi đồ";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$msg = "";
$search_name = isset($_GET['name']) ? trim($_GET['name']) : '';

// Xử lý lưu túi đồ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_bag' && !empty($_POST['player_id'])) {
        $p_id = intval($_POST['player_id']);
        $new_bag3 = $_POST['bag3_json'];
        
        // Lưu vào database
        $stmt = $conn->prepare("UPDATE players SET bag3 = :bag3 WHERE id = :id");
        $stmt->bindParam(':bag3', $new_bag3);
        $stmt->bindParam(':id', $p_id);
        
        if ($stmt->execute()) {
            // Gửi lệnh KICK để người chơi phải đăng nhập lại, tải lại túi đồ
            $p_name = trim($_POST['player_name']);
            $conn->query("INSERT INTO web_admin_commands (command, target_user, data, status) VALUES ('KICK', '$p_name', '{}', 0)");
            
            $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Lưu túi đồ thành công! Đã gửi lệnh Kick để người chơi tải lại dữ liệu.</div>";
        } else {
            $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>Lỗi khi lưu túi đồ.</div>";
        }
    }
}

$player = null;
if (!empty($search_name)) {
    $stmt = $conn->prepare("SELECT id, name, level, bag3, it_body, point_inven, vang, ngoc FROM players WHERE name = :name LIMIT 1");
    $stmt->bindParam(':name', $search_name);
    $stmt->execute();
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$player) {
        $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>Không tìm thấy nhân vật: $search_name</div>";
    }
}
?>

<div class="mt-4 p-4 bg-white rounded shadow w-full mx-auto max-w-4xl">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">🎒 Quản Lý Túi Đồ Nhân Vật</h2>
        <a href="/Admin/users.php" class="text-blue-500 hover:underline">Quay lại</a>
    </div>
    
    <?= $msg ?>

    <form method="GET" class="flex gap-2 mb-6">
        <input type="text" name="name" value="<?= htmlspecialchars($search_name) ?>" placeholder="Nhập tên nhân vật trong game..." class="border p-2 rounded w-full max-w-sm" required>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tra cứu Túi Đồ</button>
    </form>

    <?php if ($player): ?>
    <div class="border rounded p-4 bg-gray-50 mb-4">
        <h3 class="font-bold text-lg text-blue-600 mb-2">Nhân vật: <?= htmlspecialchars($player['name']) ?> (Cấp <?= $player['level'] ?>)</h3>
        <p><strong>Beri:</strong> <?= number_format((int)$player['vang']) ?> | <strong>Ruby:</strong> <?= number_format((int)$player['ngoc']) ?></p>
        <p class="text-sm text-gray-500 mt-2">Dữ liệu JSON Túi Hành Trang (bag3) bên dưới. Bạn có thể xóa mảng Item bên trong để thu hồi vật phẩm. Lưu ý: Định dạng phải chuẩn JSON.</p>
        
        <form method="POST" action="?name=<?= urlencode($player['name']) ?>" class="mt-4" onsubmit="return confirm('Bạn chắc chắn muốn lưu đè túi đồ? Người chơi sẽ bị Kick khỏi game!');">
            <input type="hidden" name="action" value="save_bag">
            <input type="hidden" name="player_id" value="<?= $player['id'] ?>">
            <input type="hidden" name="player_name" value="<?= htmlspecialchars($player['name']) ?>">
            
            <label class="block font-bold mb-1">JSON Túi Đồ Trang Bị (bag3):</label>
            <textarea name="bag3_json" rows="15" class="w-full border rounded p-2 text-sm font-mono bg-white"><?= htmlspecialchars($player['bag3']) ?></textarea>
            
            <button type="submit" class="mt-3 bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 font-bold">💾 Lưu Lại & Kick Người Chơi</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
