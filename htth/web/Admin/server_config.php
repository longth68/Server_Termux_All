<?php
$_Title = "Cấu hình Máy chủ";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$msg = "";

// Ensure table exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS `server_config` (
        `id` int NOT NULL DEFAULT 1,
        `exp_rate` int NOT NULL DEFAULT 1,
        `beri_rate` int NOT NULL DEFAULT 1,
        `drop_rate` int NOT NULL DEFAULT 1,
        `monster_drops` text NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    $stmt = $conn->query("SELECT COUNT(*) FROM server_config WHERE id = 1");
    if ($stmt->fetchColumn() == 0) {
        $conn->exec("INSERT INTO server_config (id, exp_rate, beri_rate, drop_rate, monster_drops) VALUES (1, 1, 1, 1, '[]')");
    }
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_config') {
        $exp_rate = intval($_POST['exp_rate']);
        $beri_rate = intval($_POST['beri_rate']);
        $drop_rate = intval($_POST['drop_rate']);
        
        $monster_drops = "[]";
        if (isset($_POST['drops'])) {
            $drops = $_POST['drops'];
            $formatted_drops = [];
            foreach ($drops as $drop) {
                if (!empty($drop['id']) && !empty($drop['quant']) && !empty($drop['rate'])) {
                    $formatted_drops[] = [
                        'type' => intval($drop['type']),
                        'id' => intval($drop['id']),
                        'quant' => intval($drop['quant']),
                        'rate' => intval($drop['rate'])
                    ];
                }
            }
            $monster_drops = json_encode($formatted_drops, JSON_UNESCAPED_UNICODE);
        }

        $stmt = $conn->prepare("UPDATE server_config SET exp_rate = ?, beri_rate = ?, drop_rate = ?, monster_drops = ? WHERE id = 1");
        $stmt->execute([$exp_rate, $beri_rate, $drop_rate, $monster_drops]);

        $conn->query("INSERT INTO web_admin_commands (command, data, status) VALUES ('UPDATE_SERVER_CONFIG', '{}', 0)");

        $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Đã lưu cấu hình máy chủ thành công! Máy chủ đang được cập nhật...</div>";
    }
}

$stmt = $conn->query("SELECT * FROM server_config WHERE id = 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$current_drops = json_decode($config['monster_drops'] ?? '[]', true);
if (!is_array($current_drops)) $current_drops = [];

// Fetch items for dropdown
try {
    $items_3 = $conn->query("SELECT id, name FROM item3")->fetchAll(PDO::FETCH_ASSOC);
    $items_4 = $conn->query("SELECT id, name FROM item4")->fetchAll(PDO::FETCH_ASSOC);
    $items_7 = $conn->query("SELECT id, name FROM item7")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $items_3 = []; $items_4 = []; $items_7 = [];
}
?>

<div class="container mx-auto p-4 max-w-4xl">
    <h2 class="text-2xl font-bold mb-4">Cấu hình Tỉ lệ Máy chủ</h2>
    <?= $msg ?>

    <form method="POST" class="bg-white p-6 rounded shadow" id="configForm">
        <input type="hidden" name="action" value="update_config">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block font-bold mb-2">Tỉ lệ Kinh nghiệm (EXP)</label>
                <input type="number" name="exp_rate" class="border p-2 w-full rounded" value="<?= $config['exp_rate'] ?? 1 ?>" min="1" required>
            </div>
            <div>
                <label class="block font-bold mb-2">Tỉ lệ rớt Beri</label>
                <input type="number" name="beri_rate" class="border p-2 w-full rounded" value="<?= $config['beri_rate'] ?? 1 ?>" min="1" required>
            </div>
            <div>
                <label class="block font-bold mb-2">Tỉ lệ rớt Đồ (%)</label>
                <input type="number" name="drop_rate" class="border p-2 w-full rounded" value="<?= $config['drop_rate'] ?? 1 ?>" min="1" required>
                <p class="text-xs text-gray-500 mt-1">Gấp bao nhiêu lần tỉ lệ gốc (nhập 1 là mặc định)</p>
            </div>
        </div>

        <h3 class="text-xl font-bold mb-4 border-t pt-4">Sự kiện / Vật phẩm rớt từ Quái thường Toàn Server</h3>
        <p class="text-sm text-gray-600 mb-4">Danh sách các vật phẩm sẽ rớt thêm khi tiêu diệt quái thường trên bản đồ (áp dụng chung cho toàn máy chủ).</p>
        
        <div id="dropList"></div>
        
        <button type="button" id="btnAddDrop" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-4 rounded mb-4">
            + Thêm Vật Phẩm Rớt
        </button>

        <div class="mt-4 border-t pt-4 flex justify-end">
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-6 rounded">
                Lưu Cấu Hình
            </button>
        </div>
    </form>
</div>

<script>
const GameItems = {
    3: <?= json_encode($items_3) ?>,
    4: <?= json_encode($items_4) ?>,
    7: <?= json_encode($items_7) ?>
};

let dropIndex = 0;
const currentDrops = <?= json_encode($current_drops) ?>;
const list = document.getElementById('dropList');

function addDropItem(id = "", type = 4, quant = 1, rate = 10) {
    const row = document.createElement('div');
    row.className = "drop-item flex flex-wrap gap-2 mb-2 items-center border p-2 rounded bg-gray-50";
    
    const typeSelect = document.createElement('select');
    typeSelect.name = `drops[${dropIndex}][type]`;
    typeSelect.className = "border p-1 rounded bg-white w-32";
    typeSelect.innerHTML = `
        <option value="3" ${type == 3 ? 'selected' : ''}>Trang bị (3)</option>
        <option value="4" ${type == 4 ? 'selected' : ''}>Vật phẩm (4)</option>
        <option value="7" ${type == 7 ? 'selected' : ''}>Nguyên liệu (7)</option>
    `;
    
    const idSelect = document.createElement('select');
    idSelect.name = `drops[${dropIndex}][id]`;
    idSelect.className = "border p-1 rounded bg-white flex-1";
    
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
        <div class="flex items-center gap-1">
            <span class="text-sm text-gray-500 font-bold">SL:</span>
            <input type="number" name="drops[${dropIndex}][quant]" class="border p-1 w-20 rounded" placeholder="SL" value="${quant}" required>
        </div>
        <div class="flex items-center gap-1">
            <span class="text-sm text-gray-500 font-bold">Tỉ lệ (/120):</span>
            <input type="number" name="drops[${dropIndex}][rate]" class="border p-1 w-20 rounded" placeholder="Tỉ lệ" value="${rate}" required title="Tỉ lệ rớt (X/120)">
        </div>
        <button type="button" class="btn-remove bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded ml-2" onclick="this.parentElement.remove()">Xoá</button>
    `;

    row.prepend(idSelect);
    row.prepend(typeSelect);
    
    list.appendChild(row);
    dropIndex++;
}

// Load existing drops
currentDrops.forEach(d => addDropItem(d.id, d.type, d.quant, d.rate));

document.getElementById('btnAddDrop').addEventListener('click', () => addDropItem());
</script>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
