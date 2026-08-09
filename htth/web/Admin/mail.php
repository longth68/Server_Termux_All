<?php
$_Title = "Gửi Thư / Quà";
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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['target_user'])) {
    $target_user = trim($_POST['target_user']);
    $title = trim($_POST['title']);
    $notice = trim($_POST['notice']);
    $beri = intval($_POST['beri']);
    $ruby = intval($_POST['ruby']);
    
    // Parse items array from form
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
    
    if (!empty($target_user)) {
        $data = json_encode([
            'title' => $title,
            'notice' => $notice,
            'beri' => $beri,
            'ruby' => $ruby,
            'items' => $items
        ], JSON_UNESCAPED_UNICODE);
        
        $stmt = $conn->prepare("INSERT INTO web_admin_commands (command, target_user, data, status) VALUES ('SEND_MAIL', :user, :data, 0)");
        $stmt->bindParam(':user', $target_user);
        $stmt->bindParam(':data', $data);
        if ($stmt->execute()) {
            $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Đã gửi lệnh tặng quà cho người chơi {$target_user} thành công!</div>";
        } else {
            $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>Lỗi khi gửi lệnh.</div>";
        }
    }
}
?>

<div class="mt-4 p-4 bg-white rounded shadow max-w-lg mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">✉️ Gửi Thư / Quà Trực Tiếp</h2>
        <a href="/Admin/index.php" class="text-blue-500 hover:underline">Quay lại</a>
    </div>
    
    <?= $msg ?>

    <form method="POST" action="">
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Tên nhân vật nhận:</label>
            <input type="text" name="target_user" class="w-full border rounded p-2" required placeholder="Nhập tên nhân vật trong game (không phải tài khoản)">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Tiêu đề thư:</label>
            <input type="text" name="title" class="w-full border rounded p-2" required placeholder="Ví dụ: Quà đền bù bảo trì">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Nội dung thư:</label>
            <textarea name="notice" rows="2" class="w-full border rounded p-2" placeholder="Nội dung lời nhắn..."></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Số Beri tặng:</label>
                <input type="number" name="beri" class="w-full border rounded p-2" value="0" min="0">
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">Số Ruby tặng:</label>
                <input type="number" name="ruby" class="w-full border rounded p-2" value="0" min="0">
            </div>
        </div>
        
        <div class="mb-4 p-4 border rounded bg-gray-50">
            <h3 class="font-bold mb-2">Danh sách Vật phẩm đính kèm:</h3>
            <div id="item-list"></div>
            <button type="button" onclick="addItem()" class="mt-2 text-sm bg-gray-200 px-3 py-1 rounded hover:bg-gray-300">+ Thêm Vật Phẩm</button>
        </div>
        
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Gửi Quà Ngay</button>
        <p class="text-xs text-gray-500 mt-2 text-center">Lưu ý: Người chơi phải đang online thì mới nhận được thư gửi từ Web.</p>
    </form>
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

function addItem() {
    const container = document.createElement('div');
    container.className = 'grid grid-cols-3 gap-2 mb-2 item-row';
    container.innerHTML = `
        <select name="item_type[]" onchange="updateItemList(this)" required class="border p-1 rounded bg-white">
            <option value="">-- Chọn Loại --</option>
            <option value="3">Trang Bị (3)</option>
            <option value="4">Vật Phẩm (4)</option>
            <option value="7">Nguyên Liệu (7)</option>
            <option value="8">Vật Phẩm Bang (cần có Bang)</option>
            <option value="103">Kiểu Tóc (103)</option>
            <option value="105">Thời Trang (105)</option>
        </select>
        <select name="item_id[]" required class="border p-1 rounded bg-white item-id-select">
            <option value="">-- Chọn Vật phẩm --</option>
        </select>
        <input type="number" name="item_quant[]" placeholder="Số lượng" required class="border p-1 rounded" value="1" min="1">
    `;
    document.getElementById('item-list').appendChild(container);
}
</script>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
