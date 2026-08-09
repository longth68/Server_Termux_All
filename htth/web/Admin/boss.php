<?php
$_Title = "Quản lý Boss Server";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action == 'spawn') {
        $boss_id = intval($_POST['boss_id']);
        $data = json_encode(['boss_id' => $boss_id]);
        $stmt = $conn->prepare("INSERT INTO web_admin_commands (command, data, status) VALUES ('SPAWN_BOSS', ?, 0)");
        $stmt->execute([$data]);
        $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Đã gửi lệnh gọi Boss ID $boss_id thành công!</div>";
    }
}

try {
    $conn->query("ALTER TABLE boss ADD COLUMN rewards TEXT NULL");
    $conn->query("UPDATE boss SET rewards = '[]' WHERE rewards IS NULL");
} catch (Exception $e) {}

$bosses = [];
try {
    $stmt = $conn->query("
        SELECT b.id, b.mob_id, b.hp, b.level, m.name 
        FROM boss b 
        LEFT JOIN mobs m ON b.mob_id = m.id 
        ORDER BY b.id ASC
    ");
    $bosses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

?>

<div class="mt-4 p-4 bg-white rounded shadow w-full mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">👹 Quản Lý Boss Server</h2>
        <a href="/Admin/index.php" class="text-blue-500 hover:underline">Quay lại</a>
    </div>
    
    <?= $msg ?>

    <p class="text-sm text-gray-600 mb-4">Gọi Boss xuất hiện ngay lập tức trong Server. Dữ liệu HP và Cấp độ hiển thị ở đây là số liệu cấu hình cơ bản.</p>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm border-collapse bg-white">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 p-2 text-center w-12">ID</th>
                    <th class="border border-gray-200 p-2 text-left">Tên Boss</th>
                    <th class="border border-gray-200 p-2 text-center">Cấp độ</th>
                    <th class="border border-gray-200 p-2 text-center">HP Cơ bản</th>
                    <th class="border border-gray-200 p-2 text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($bosses as $b): ?>
                <tr class="hover:bg-gray-50">
                    <td class="border border-gray-200 p-2 text-center"><?= $b['id'] ?></td>
                    <td class="border border-gray-200 p-2 font-bold text-red-600">
                        <?= htmlspecialchars($b['name'] ? $b['name'] : 'Unknown Mob ' . $b['mob_id']) ?>
                    </td>
                    <td class="border border-gray-200 p-2 text-center">Lv <?= $b['level'] ?></td>
                    <td class="border border-gray-200 p-2 text-center"><?= number_format((int)$b['hp']) ?></td>
                    <td class="border border-gray-200 p-2 text-center">
                        <div class="flex justify-center gap-2">
                            <form method="POST" action="" onsubmit="return confirm('Gọi Boss này ra Server ngay bây giờ?');">
                                <input type="hidden" name="action" value="spawn">
                                <input type="hidden" name="boss_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">Gọi Boss</button>
                            </form>
                            <a href="/Admin/boss_edit.php?id=<?= $b['id'] ?>" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-sm">Chỉnh Sửa</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($bosses)): ?>
                <tr><td colspan="5" class="p-4 text-center text-gray-500">Chưa có Boss nào được cấu hình trong CSDL.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
