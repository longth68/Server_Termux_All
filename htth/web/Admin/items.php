<?php
$_Title = "Tra cứu Vật phẩm & Trang bị";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

$items = [];
$error = '';

try {
    if ($search !== '') {
        $is_numeric = is_numeric($search);
        
        // item3 (Trang bị / Vũ khí / Quần áo)
        if ($type === 'all' || $type === '3') {
            $sql = "SELECT id, name, icon, 'Trang bị (item3)' as category FROM item3 WHERE name LIKE :search";
            if ($is_numeric) $sql .= " OR id = :id";
            $sql .= " LIMIT 50";
            
            $stmt = $conn->prepare($sql);
            if ($is_numeric) {
                $stmt->execute(['search' => "%$search%", 'id' => intval($search)]);
            } else {
                $stmt->execute(['search' => "%$search%"]);
            }
            $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        // item4 (Nguyên liệu / Đá / Thuốc)
        if ($type === 'all' || $type === '4') {
            $sql = "SELECT id, name, icon, 'Nguyên liệu/Tiêu hao (item4)' as category FROM item4 WHERE name LIKE :search";
            if ($is_numeric) $sql .= " OR id = :id";
            $sql .= " LIMIT 50";
            
            $stmt = $conn->prepare($sql);
            if ($is_numeric) {
                $stmt->execute(['search' => "%$search%", 'id' => intval($search)]);
            } else {
                $stmt->execute(['search' => "%$search%"]);
            }
            $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        // item7 (Đồ thời trang / Khác)
        if ($type === 'all' || $type === '7') {
            $sql = "SELECT id, name, icon, 'Thời trang/Khác (item7)' as category FROM item7 WHERE name LIKE :search";
            if ($is_numeric) $sql .= " OR id = :id";
            $sql .= " LIMIT 50";
            
            $stmt = $conn->prepare($sql);
            if ($is_numeric) {
                $stmt->execute(['search' => "%$search%", 'id' => intval($search)]);
            } else {
                $stmt->execute(['search' => "%$search%"]);
            }
            $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    }
} catch (Exception $e) {
    $error = "Lỗi truy vấn dữ liệu: " . $e->getMessage();
}
?>

<div class="mt-4 p-4 bg-white rounded shadow w-full mx-auto max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold flex items-center gap-2">🔍 Tra Cứu ID Vật Phẩm</h2>
        <a href="/Admin/index.php" class="text-blue-500 hover:underline border border-blue-500 px-3 py-1 rounded">Quay lại Admin</a>
    </div>
    
    <div class="bg-blue-50 text-blue-800 p-3 rounded mb-6 text-sm border border-blue-200">
        Bạn có thể nhập tên một phần của vật phẩm (ví dụ: "Kiếm", "Bánh", "Đá") hoặc nhập chính xác ID (ví dụ: "105") để tìm.
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="GET" class="flex flex-col md:flex-row gap-4 mb-8 bg-gray-50 p-4 rounded border">
        <div class="flex-grow">
            <label class="block text-sm font-bold text-gray-700 mb-1">Tên hoặc ID vật phẩm</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Nhập tên hoặc ID..." class="w-full border p-2 rounded focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" autofocus>
        </div>
        <div class="w-full md:w-48">
            <label class="block text-sm font-bold text-gray-700 mb-1">Loại đồ</label>
            <select name="type" class="w-full border p-2 rounded focus:outline-none focus:border-blue-500">
                <option value="all" <?= $type == 'all' ? 'selected' : '' ?>>Tất cả</option>
                <option value="3" <?= $type == '3' ? 'selected' : '' ?>>Trang bị (item3)</option>
                <option value="4" <?= $type == '4' ? 'selected' : '' ?>>Nguyên liệu (item4)</option>
                <option value="7" <?= $type == '7' ? 'selected' : '' ?>>Thời trang (item7)</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded transition">Tìm Kiếm</button>
        </div>
    </form>

    <?php if ($search !== ''): ?>
        <h3 class="font-bold text-lg mb-2">Kết quả tìm kiếm cho: "<?= htmlspecialchars($search) ?>" (<?= count($items) ?> kết quả)</h3>
        
        <?php if (count($items) > 0): ?>
            <div class="overflow-x-auto rounded border border-gray-200">
                <table class="min-w-full bg-white text-sm text-left">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="py-3 px-4 font-semibold w-24 text-center">ID</th>
                            <th class="py-3 px-4 font-semibold w-20 text-center">Icon ID</th>
                            <th class="py-3 px-4 font-semibold">Tên Vật Phẩm</th>
                            <th class="py-3 px-4 font-semibold">Loại / Bảng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-blue-50 transition">
                                <td class="py-2 px-4 font-bold text-red-600 text-center text-lg"><?= $item['id'] ?></td>
                                <td class="py-2 px-4 text-center text-gray-500"><?= $item['icon'] ?></td>
                                <td class="py-2 px-4 font-bold text-gray-800"><?= htmlspecialchars($item['name']) ?></td>
                                <td class="py-2 px-4 text-gray-600">
                                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-semibold border">
                                        <?= htmlspecialchars($item['category']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 mt-2 italic">* Chỉ hiển thị tối đa 50 kết quả mỗi loại. Hãy nhập từ khóa chi tiết hơn nếu chưa tìm thấy.</p>
        <?php else: ?>
            <div class="p-8 text-center bg-gray-50 rounded border border-gray-200">
                <p class="text-gray-500 text-lg">Không tìm thấy vật phẩm nào khớp với từ khóa của bạn.</p>
                <p class="text-gray-400 text-sm mt-1">Hãy thử tìm với một từ khóa khác ngắn hơn.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
