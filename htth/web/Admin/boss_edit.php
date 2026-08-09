<?php
$_Title = "Chỉnh Sửa Boss Server";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

if (!isset($_GET['id'])) {
    echo "<script>window.location.href = '/Admin/boss.php';</script>";
    exit;
}
$boss_id = intval($_GET['id']);

// Fetch items for dropdown
try {
    $items_3 = $conn->query("SELECT id, name FROM item3")->fetchAll(PDO::FETCH_ASSOC);
    $items_4 = $conn->query("SELECT id, name FROM item4")->fetchAll(PDO::FETCH_ASSOC);
    $items_7 = $conn->query("SELECT id, name FROM item7")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $items_3 = []; $items_4 = []; $items_7 = [];
}
$game_items = [
    3 => $items_3,
    4 => $items_4,
    7 => $items_7
];

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $hp = intval($_POST['hp']);
    $rewards = [];
    
    if (isset($_POST['item_type']) && is_array($_POST['item_type'])) {
        for ($i = 0; $i < count($_POST['item_type']); $i++) {
            $type = intval($_POST['item_type'][$i]);
            $id = intval($_POST['item_id'][$i]);
            $quant = intval($_POST['item_quant'][$i]);
            $rate = intval($_POST['item_rate'][$i]);
            if ($quant > 0 && $rate > 0) {
                $rewards[] = [
                    'type' => $type,
                    'id' => $id,
                    'quant' => $quant,
                    'rate' => $rate
                ];
            }
        }
    }
    
    $rewards_json = json_encode($rewards);
    
    try {
        $stmt = $conn->prepare("UPDATE boss SET hp = ?, rewards = ? WHERE id = ?");
        $stmt->execute([$hp, $rewards_json, $boss_id]);
        $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Lưu cấu hình thành công! Hãy Khởi động lại Server Game để áp dụng thay đổi.</div>";
    } catch (Exception $e) {
        $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>Lỗi: " . $e->getMessage() . "</div>";
    }
}

// Fetch current boss
$boss = null;
try {
    $stmt = $conn->prepare("SELECT b.*, m.name as mob_name FROM boss b LEFT JOIN mobs m ON b.mob_id = m.id WHERE b.id = ?");
    $stmt->execute([$boss_id]);
    $boss = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

if (!$boss) {
    echo "Boss không tồn tại.";
    exit;
}

$current_rewards = json_decode($boss['rewards'], true);
if (!is_array($current_rewards)) $current_rewards = [];

?>

<div class="mt-4 p-4 bg-white rounded shadow w-full mx-auto max-w-4xl">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-blue-600">🛠 Chỉnh Sửa Boss: <?= htmlspecialchars($boss['mob_name'] ?: 'Unknown') ?> (Lv <?= $boss['level'] ?>)</h2>
        <a href="/Admin/boss.php" class="text-blue-500 hover:underline">Quay lại danh sách</a>
    </div>
    
    <?= $msg ?>
    
    <div class="bg-yellow-50 text-yellow-800 p-3 rounded mb-4 text-sm border border-yellow-200">
        <strong>Lưu ý quan trọng:</strong> Vì lý do an toàn bộ nhớ của Server Game, sau khi bạn Lưu cấu hình tại đây, vui lòng <b>Khởi động lại Server Game</b> để Boss cập nhật Lượng máu và Danh sách phần thưởng mới nhất!
    </div>

    <form method="POST" action="">
        <input type="hidden" name="action" value="save">
        
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Lượng HP Cơ Bản:</label>
            <input type="number" name="hp" value="<?= $boss['hp'] ?>" required class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:border-blue-500">
        </div>
        
        <hr class="my-6 border-gray-200">
        
        <div class="flex justify-between items-center mb-2">
            <label class="block text-gray-700 font-bold">Danh sách Phần thưởng khi Boss chết:</label>
            <button type="button" onclick="addReward()" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm font-bold">+ Thêm Phần Thưởng</button>
        </div>
        <p class="text-sm text-gray-500 mb-4">Mỗi vật phẩm sẽ được quay random tỷ lệ độc lập với nhau. Max tỷ lệ là 120.</p>
        
        <div id="reward-list" class="space-y-3">
            <!-- Rewards will be injected here -->
        </div>
        
        <div class="mt-6 text-right">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700 shadow">💾 LƯU CẤU HÌNH</button>
        </div>
    </form>
</div>

<script>
const GameItems = <?= json_encode($game_items) ?>;
const CurrentRewards = <?= json_encode($current_rewards) ?>;

function addReward(type = 4, id = 0, quant = 1, rate = 15) {
    const list = document.getElementById('reward-list');
    const row = document.createElement('div');
    row.className = "flex gap-2 items-center bg-gray-50 p-2 border rounded";
    
    const typeSelect = document.createElement('select');
    typeSelect.name = "item_type[]";
    typeSelect.className = "border p-2 rounded bg-white w-32";
    typeSelect.innerHTML = `
        <option value="3" ${type == 3 ? 'selected' : ''}>Trang bị (3)</option>
        <option value="4" ${type == 4 ? 'selected' : ''}>Vật phẩm (4)</option>
        <option value="7" ${type == 7 ? 'selected' : ''}>Nguyên liệu (7)</option>
    `;
    
    const idSelect = document.createElement('select');
    idSelect.name = "item_id[]";
    idSelect.className = "border p-2 rounded bg-white flex-1";
    
    const updateItems = () => {
        idSelect.innerHTML = '';
        const t = typeSelect.value;
        const items = GameItems[t] || [];
        items.forEach(it => {
            const opt = document.createElement('option');
            opt.value = it.id;
            opt.textContent = `[ID: ${it.id}] ${it.name}`;
            if (it.id == id && t == type) opt.selected = true;
            idSelect.appendChild(opt);
        });
    };
    
    typeSelect.onchange = updateItems;
    updateItems(); // Init
    
    row.innerHTML = `
        <div class="w-32"><span class="text-sm text-gray-500 block mb-1">Số lượng:</span><input type="number" name="item_quant[]" value="${quant}" min="1" class="border p-2 rounded w-full"></div>
        <div class="w-32"><span class="text-sm text-gray-500 block mb-1">Tỷ lệ ( /120):</span><input type="number" name="item_rate[]" value="${rate}" min="1" max="120" class="border p-2 rounded w-full"></div>
        <div class="w-10 pt-6"><button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-500 hover:text-red-700 font-bold p-2 text-xl">×</button></div>
    `;
    
    row.prepend(idSelect);
    row.prepend(typeSelect);
    
    // Add labels
    const tWrap = document.createElement('div'); tWrap.className = 'w-32';
    tWrap.innerHTML = '<span class="text-sm text-gray-500 block mb-1">Loại:</span>'; tWrap.appendChild(typeSelect);
    
    const iWrap = document.createElement('div'); iWrap.className = 'flex-1';
    iWrap.innerHTML = '<span class="text-sm text-gray-500 block mb-1">Vật phẩm:</span>'; iWrap.appendChild(idSelect);
    
    row.replaceChild(tWrap, typeSelect);
    row.replaceChild(iWrap, idSelect);
    
    list.appendChild(row);
}

// Init current rewards
if (CurrentRewards.length > 0) {
    CurrentRewards.forEach(r => addReward(r.type, r.id, r.quant, r.rate));
} else {
    // Add an empty row if no rewards
    addReward();
}

</script>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
