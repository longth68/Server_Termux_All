<?php
ini_set('default_charset', 'UTF-8');
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
$_Title = "Quản trị viên";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$total_users = $conn->query("SELECT COUNT(id) FROM accounts")->fetchColumn();
$total_giftcodes = $conn->query("SELECT COUNT(id) FROM giftcode")->fetchColumn();
$total_commands = $conn->query("SELECT COUNT(id) FROM web_admin_commands")->fetchColumn();
$pending_commands = $conn->query("SELECT COUNT(id) FROM web_admin_commands WHERE status = 0")->fetchColumn();
?>

<div class="mt-4 p-4 bg-white rounded shadow">
    <h2 class="text-xl font-bold mb-4 text-center">Bảng điều khiển Admin</h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-100 p-4 rounded text-center">
            <h3 class="text-gray-500 text-sm">Tổng thành viên</h3>
            <p class="text-2xl font-bold text-blue-600"><?= $total_users ?></p>
        </div>
        <div class="bg-green-100 p-4 rounded text-center">
            <h3 class="text-gray-500 text-sm">Tổng giftcode</h3>
            <p class="text-2xl font-bold text-green-600"><?= $total_giftcodes ?></p>
        </div>
        <div class="bg-purple-100 p-4 rounded text-center">
            <h3 class="text-gray-500 text-sm">Lệnh đã xử lý</h3>
            <p class="text-2xl font-bold text-purple-600"><?= $total_commands - $pending_commands ?></p>
        </div>
        <div class="bg-orange-100 p-4 rounded text-center">
            <h3 class="text-gray-500 text-sm">Lệnh chờ xử lý</h3>
            <p class="text-2xl font-bold text-orange-600"><?= $pending_commands ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="/Admin/notice.php" class="block p-4 border rounded hover:bg-gray-50 text-center">
            <div class="font-bold text-lg mb-1">Thông báo trong game</div>
            <div class="text-sm text-gray-600">Gửi thông báo, lưu mẫu thông báo và xem lịch sử gửi.</div>
        </a>
        <a href="/Admin/mail.php" class="block p-4 border rounded hover:bg-gray-50 text-center">
            <div class="font-bold text-lg mb-1">Gửi quà / thư</div>
            <div class="text-sm text-gray-600">Tặng vật phẩm, beri, ruby trực tiếp cho người chơi.</div>
        </a>
        <a href="/Admin/giftcode.php" class="block p-4 border rounded hover:bg-gray-50 text-center">
            <div class="font-bold text-lg mb-1">Quản lý giftcode</div>
            <div class="text-sm text-gray-600">Thêm, sửa, xóa mã quà tặng.</div>
        </a>
        <a href="/Admin/users.php" class="block p-4 border rounded hover:bg-gray-50 text-center">
            <div class="font-bold text-lg mb-1">Quản lý người chơi / VIP</div>
            <div class="text-sm text-gray-600">Khóa tài khoản, mở khóa, chỉnh VIP và xem nhân vật.</div>
        </a>
        <a href="/Admin/bot.php" class="block p-4 border rounded hover:bg-gray-50 text-center">
            <div class="font-bold text-lg mb-1">Quản lý Bot AI</div>
            <div class="text-sm text-gray-600">Tạo bot, chỉnh bot theo map và bật/tắt tính năng AI.</div>
        </a>
        <a href="/Admin/boss.php" class="block p-4 border rounded hover:bg-gray-50 text-center">
            <div class="font-bold text-lg mb-1">Quản lý Boss</div>
            <div class="text-sm text-gray-600">Gọi boss ra server ngay lập tức.</div>
        </a>
        <a href="/Admin/events.php" class="block p-4 border rounded hover:bg-gray-50 text-center">
            <div class="font-bold text-lg mb-1">Quản lý sự kiện</div>
            <div class="text-sm text-gray-600">Bật/tắt săn siêu trùm, Little Garden, Tài xỉu và reset ngày.</div>
        </a>
        <a href="/Admin/server_config.php" class="block p-4 border rounded hover:bg-gray-50 text-center">
            <div class="font-bold text-lg mb-1">Cấu hình máy chủ</div>
            <div class="text-sm text-gray-600">Điều chỉnh tỉ lệ kinh nghiệm, rơi beri và rơi đồ toàn server.</div>
        </a>
        <a href="/Admin/recharge.php" class="block p-4 border rounded hover:bg-gray-50 text-center">
            <div class="font-bold text-lg mb-1">Duyệt nạp tiền</div>
            <div class="text-sm text-gray-600">Kiểm tra và duyệt yêu cầu nạp tiền của người chơi.</div>
        </a>
        <a href="/Admin/items.php" class="block p-4 border rounded hover:bg-gray-50 text-center">
            <div class="font-bold text-lg mb-1">Tra cứu vật phẩm</div>
            <div class="text-sm text-gray-600">Tìm tên và ID của trang bị, nguyên liệu, đồ dùng.</div>
        </a>
    </div>
</div>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
