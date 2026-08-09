<?php
ini_set('default_charset', 'UTF-8');
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
$_Title = "Quản lý sự kiện";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$conn->exec("CREATE TABLE IF NOT EXISTS event_config (
    event_key VARCHAR(50) NOT NULL PRIMARY KEY,
    event_name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    enabled TINYINT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$defaultEvents = [
    'daily_reset' => [
        'name' => 'Reset ngày',
        'description' => 'Làm mới lượt ngày, clan và dữ liệu hoạt động lúc 00:00.',
        'time' => '00:00 mỗi ngày',
    ],
    'super_boss' => [
        'name' => 'Săn siêu trùm',
        'description' => 'Tự mở boss lúc 18:00-19:00 và 22:00-23:00.',
        'time' => '18:00-19:00, 22:00-23:00',
    ],
    'little_garden' => [
        'name' => 'Little Garden / Clan',
        'description' => 'Ghép clan tham gia Little Garden vào 21:00 thứ 2, 4, 6.',
        'time' => '21:00 thứ 2, 4, 6',
    ],
    'tai_xiu' => [
        'name' => 'Tài xỉu',
        'description' => 'Cho phép vòng thời gian mini game Tài xỉu hoạt động.',
        'time' => 'Chạy liên tục',
    ],
];

$stmtDefault = $conn->prepare("INSERT IGNORE INTO event_config
    (event_key, event_name, description, enabled) VALUES (?, ?, ?, 1)");
foreach ($defaultEvents as $key => $event) {
    $stmtDefault->execute([$key, $event['name'], $event['description']]);
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabledKeys = $_POST['enabled'] ?? [];
    $stmtUpdate = $conn->prepare("UPDATE event_config SET enabled = ? WHERE event_key = ?");
    foreach ($defaultEvents as $key => $event) {
        $stmtUpdate->execute([isset($enabledKeys[$key]) ? 1 : 0, $key]);
    }
    $message = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Đã cập nhật cấu hình sự kiện. Server sẽ tự nhận trong khoảng 30 giây.</div>";
}

$rows = $conn->query("SELECT * FROM event_config ORDER BY FIELD(event_key, 'daily_reset', 'super_boss', 'little_garden', 'tai_xiu'), event_name")
    ->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mt-4 p-4 bg-white rounded shadow">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-blue-600">Quản lý sự kiện server</h2>
        <a href="/Admin/index.php" class="text-blue-600 hover:underline">Quay lại Admin</a>
    </div>

    <?= $message ?>

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-3 rounded mb-4">
        Nên giữ bật <b>Reset ngày</b>. Nếu tắt, một số lượt ngày, clan hoặc hoạt động có thể không tự làm mới.
    </div>

    <form method="post">
        <div class="overflow-x-auto">
            <div class="overflow-x-auto rounded border border-gray-200">
                <table class="min-w-full text-sm text-left border-collapse bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Sự kiện</th>
                        <th class="p-3 text-left">Thời gian</th>
                        <th class="p-3 text-left">Mô tả</th>
                        <th class="p-3 text-center">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row):
                        $key = $row['event_key'];
                        $time = $defaultEvents[$key]['time'] ?? 'Theo server';
                    ?>
                        <tr class="border-t">
                            <td class="p-3 font-bold"><?= htmlspecialchars($row['event_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($time) ?></td>
                            <td class="p-3 text-gray-700"><?= htmlspecialchars($row['description']) ?></td>
                            <td class="p-3 text-center">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="enabled[<?= htmlspecialchars($key) ?>]" value="1"
                                        <?= !empty($row['enabled']) ? 'checked' : '' ?>>
                                    <span><?= !empty($row['enabled']) ? 'Đang bật' : 'Đang tắt' ?></span>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
        </div>

        <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Lưu cấu hình sự kiện
        </button>
    </form>
</div>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
