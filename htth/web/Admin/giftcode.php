<?php
$_Title = "Quản lý Giftcode";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

// Fetch items for dropdown
try {
    $items_3 = $conn->query("SELECT id, name FROM item3")->fetchAll(PDO::FETCH_ASSOC);
    $items_4 = $conn->query("SELECT id, name FROM item4")->fetchAll(PDO::FETCH_ASSOC);
    $items_7 = $conn->query("SELECT id, name FROM item7")->fetchAll(PDO::FETCH_ASSOC);
    $items_8 = $conn->query("SELECT id, name FROM item8 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $items_103 = $conn->query("SELECT id, name FROM itemhair ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $items_105 = $conn->query("SELECT id, name FROM fashiontemplate ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $items_3 = []; $items_4 = []; $items_7 = []; $items_8 = []; $items_103 = []; $items_105 = [];
}
$game_items = [
    '3' => $items_3,
    '4' => $items_4,
    '7' => $items_7,
    '8' => $items_8,
    '103' => $items_103,
    '105' => $items_105
];
$game_items_json = json_encode($game_items, JSON_UNESCAPED_UNICODE);

$msg = "";
// Xóa giftcode
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    $conn->query("DELETE FROM giftcode WHERE id = $id_del");
    $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Đã xóa giftcode thành công!</div>";
}

// Thêm giftcode
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['giftname'])) {
    $giftname = trim($_POST['giftname']);
    $beri = intval($_POST['beri']);
    $ruby = intval($_POST['ruby']);
    $gioihan = max(1, intval($_POST['luotnhap']));
    $notice = trim($_POST['notice']);
    
    $items = [];
    if (isset($_POST['item_type']) && is_array($_POST['item_type'])) {
        for ($i = 0; $i < count($_POST['item_type']); $i++) {
            $type = intval($_POST['item_type'][$i]);
            $id = intval($_POST['item_id'][$i]);
            $quant = intval($_POST['item_quant'][$i]);
            if ($quant > 0) {
                $items[] = [$type, $id, $quant];
            }
        }
    }
    $item_json = json_encode($items);
    
    if (!empty($giftname)) {
        $stmt = $conn->prepare("INSERT INTO giftcode (giftname, beri, ruby, item, thongbao, luotnhap, gioihan, used, special)
                                VALUES (:giftname, :beri, :ruby, :item, :thongbao, 0, :gioihan, '', '')");
        $stmt->bindParam(':giftname', $giftname);
        $stmt->bindParam(':beri', $beri);
        $stmt->bindParam(':ruby', $ruby);
        $stmt->bindParam(':item', $item_json);
        $stmt->bindParam(':thongbao', $notice);
        $stmt->bindParam(':gioihan', $gioihan);
        
        try {
            if ($stmt->execute()) {
                $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Đã thêm Giftcode mới thành công!</div>";
            }
        } catch (PDOException $e) {
            $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>Không thể thêm Giftcode. Hãy kiểm tra mã có bị trùng hay không.</div>";
        }
    }
}

$giftcodes = $conn->query("SELECT * FROM giftcode ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mt-4 p-4 bg-white rounded shadow w-full mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">🎁 Quản Lý Giftcode</h2>
        <a href="/Admin/index.php" class="text-blue-500 hover:underline">Quay lại</a>
    </div>
    
    <?= $msg ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Form thêm giftcode -->
        <div class="col-span-1 border p-4 rounded bg-gray-50">
            <h3 class="font-bold mb-3">Thêm Giftcode mới</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="block text-sm font-bold mb-1">Mã Giftcode:</label>
                    <input type="text" name="giftname" class="w-full border rounded p-1.5 uppercase" required placeholder="TANTHU2026">
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-bold mb-1">Lời nhắn khi nhận:</label>
                    <input type="text" name="notice" class="w-full border rounded p-1.5" required placeholder="Chúc mừng bạn nhận quà">
                </div>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div>
                        <label class="block text-sm font-bold mb-1">Beri:</label>
                        <input type="number" name="beri" class="w-full border rounded p-1.5" value="100000">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Ruby:</label>
                        <input type="number" name="ruby" class="w-full border rounded p-1.5" value="100">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-bold mb-1">Giới hạn lượt nhập:</label>
                    <input type="number" name="luotnhap" class="w-full border rounded p-1.5" value="999">
                </div>
                
                <div class="mb-3 p-2 border rounded bg-white">
                    <label class="block text-sm font-bold mb-1">Vật phẩm (Type/ID/SL):</label>
                    <div id="gc-item-list"></div>
                    <button type="button" onclick="addGCItem()" class="mt-1 text-xs bg-gray-200 px-2 py-1 rounded">+ Thêm VP</button>
                </div>
                
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Tạo Mã Mới</button>
            </form>
        </div>

        <!-- Danh sách giftcode -->
        <div class="col-span-1 md:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-200 p-2 text-left">ID</th>
                            <th class="border border-gray-200 p-2 text-left">Mã Code</th>
                            <th class="border border-gray-200 p-2 text-left">Phần thưởng</th>
                            <th class="border border-gray-200 p-2 text-center">Giới hạn</th>
                            <th class="border border-gray-200 p-2 text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($giftcodes as $gc): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-200 p-2"><?= $gc['id'] ?></td>
                            <td class="border border-gray-200 p-2 font-bold text-red-500 uppercase"><?= htmlspecialchars($gc['giftname']) ?></td>
                            <td class="border border-gray-200 p-2 text-sm">
                                Beri: <?= number_format((int)$gc['beri']) ?> <br>
                                Ruby: <?= number_format((int)$gc['ruby']) ?>
                            </td>
                            <td class="border border-gray-200 p-2 text-center"><?= intval($gc['luotnhap']) ?> / <?= intval($gc['gioihan']) ?></td>
                            <td class="border border-gray-200 p-2 text-center">
                                <a href="?delete=<?= $gc['id'] ?>" class="text-red-500 hover:underline text-sm" onclick="return confirm('Chắc chắn xóa?');">Xóa</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($giftcodes)): ?>
                        <tr><td colspan="5" class="p-4 text-center text-gray-500">Chưa có giftcode nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 mt-2">Lưu ý: Thêm hoặc xóa Giftcode ở đây sẽ có tác dụng lập tức trong game.</p>
        </div>
    </div>
</div>

<script>
const GameItems = <?= $game_items_json ?>;

function renderItemOptions(type) {
    let options = '<option value="">-- Chọn Vật phẩm --</option>';
    if (GameItems[type]) {
        GameItems[type].forEach(item => {
            options += `<option value="${item.id}">[${item.id}] ${item.name}</option>`;
        });
    }
    return options;
}

function updateItemList(selectElement) {
    const row = selectElement.closest('.item-row');
    const type = selectElement.value;
    const itemSelect = row.querySelector('.item-id-select');
    itemSelect.innerHTML = renderItemOptions(type);
}

function addGCItem() {
    const container = document.createElement('div');
    container.className = 'grid grid-cols-3 gap-1 mb-1 item-row';
    container.innerHTML = `
        <select name="item_type[]" onchange="updateItemList(this)" required class="border p-1 rounded bg-white text-xs">
            <option value="">-- Loại --</option>
            <option value="3">Trang Bị</option>
            <option value="4">Vật Phẩm</option>
            <option value="7">Nguyên Liệu</option>
            <option value="8">Vật Phẩm Bang</option>
            <option value="103">Kiểu Tóc</option>
            <option value="105">Thời Trang</option>
        </select>
        <select name="item_id[]" required class="border p-1 rounded bg-white item-id-select text-xs">
            <option value="">-- Tên VP --</option>
        </select>
        <input type="number" name="item_quant[]" placeholder="SL" required class="border p-1 rounded text-xs" value="1" min="1">
    `;
    document.getElementById('gc-item-list').appendChild(container);
}
</script>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
