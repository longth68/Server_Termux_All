<?php
$_Title = "Quản lý Nhân vật (Nâng cao)";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$msg = "";
$account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

function validPlayerQuestJson($json) {
    $quests = json_decode($json, true);
    if (!is_array($quests)) {
        return false;
    }
    foreach ($quests as $quest) {
        if (!is_array($quest) || count($quest) < 2 || !is_array($quest[1])) {
            return false;
        }
        foreach ($quest[1] as $progress) {
            if (!is_array($progress)) {
                return false;
            }
        }
    }
    return true;
}

if ($account_id <= 0) {
    echo "Tài khoản không hợp lệ.";
    include __DIR__ . '/../Controllers/Footer.php';
    exit;
}

// Lấy thông tin tài khoản
$stmt = $conn->prepare("SELECT id, user, `char`, onl FROM accounts WHERE id = ?");
$stmt->execute([$account_id]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    echo "Không tìm thấy tài khoản.";
    include __DIR__ . '/../Controllers/Footer.php';
    exit;
}

$account_chars = json_decode($account['char'], true);

// Xử lý yêu cầu KICK ngắt kết nối
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'disconnect_account') {
    if (is_array($account_chars)) {
        $queueKick = $conn->prepare("INSERT INTO web_admin_commands (command, target_user, data, status) VALUES ('KICK', ?, '{}', 0)");
        foreach ($account_chars as $characterName) {
            if (is_string($characterName) && trim($characterName) !== '') {
                $queueKick->execute([$characterName]);
            }
        }
        if (empty($account_chars)) {
            $conn->prepare("UPDATE accounts SET onl = 0 WHERE id = ?")->execute([$account_id]);
        }
        $msg = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Đã gửi lệnh ngắt kết nối. Vui lòng tải lại trang sau vài giây để kiểm tra.</div><script>setTimeout(function(){ window.location.reload(); }, 4000);</script>";
        // Cập nhật lại biến onl tạm thời
        $account['onl'] = 0;
    }
}
$allowed_actions = ['save_basic', 'save_items', 'save_progress'];

// Mỗi thao tác chỉ cập nhật đúng nhóm dữ liệu tương ứng.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', $allowed_actions, true)) {
    $action = $_POST['action'];
    $p_name = trim($_POST['p_name'] ?? '');
    if (intval($account['onl'] ?? 0) !== 0) {
        $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>Lỗi: Tài khoản đang online! Không thể lưu dữ liệu vì Server sẽ lưu đè làm mất dữ liệu. Hãy ấn nút Ngắt kết nối trước.</div>";
    } elseif (!is_array($account_chars) || !in_array($p_name, $account_chars, true)) {
        $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>Nhân vật không thuộc tài khoản này.</div>";
    } elseif ($action === 'save_basic') {
        $p_clazz = intval($_POST['p_clazz'] ?? 0);
        $p_level = max(1, intval($_POST['p_level'] ?? 1));
        $p_vang = max(0, intval($_POST['p_vang'] ?? 0));
        $p_ruby = max(0, intval($_POST['p_ruby'] ?? 0));
        $p_extol = max(0, intval($_POST['p_extol'] ?? 0));
        $p_stmt = $conn->prepare("SELECT `clazz`, `level`, `point_inven` FROM players WHERE `name` = ? LIMIT 1");
        $p_stmt->execute([$p_name]);
        $p_data = $p_stmt->fetch(PDO::FETCH_ASSOC);
        $level_arr = $p_data ? json_decode($p_data['level'], true) : null;
        $point_arr = $p_data ? json_decode($p_data['point_inven'], true) : null;
        if (!$p_data || !in_array($p_clazz, [1, 2, 3, 4, 5], true) || !is_array($level_arr) || !is_array($point_arr)) {
            $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>Thông số cơ bản không hợp lệ.</div>";
        } else {
            $class_changed = intval($p_data['clazz']) !== $p_clazz;
            $level_arr[0] = $p_level;
            $point_arr[0] = $p_vang;
            $point_arr[1] = $p_ruby;
            $point_arr[2] = $p_extol;
            $params = [$p_clazz, json_encode($level_arr), json_encode($point_arr)];
            $sql = "UPDATE players SET `clazz` = ?, `level` = ?, `point_inven` = ?";
            if ($class_changed) {
                $default_skills = [
                    1 => '[[0,0,0,0],[20,-1,0,0],[40,-1,0,0],[375,-1,0,0],[487,-1,0,0],[300,-1,0,0],[305,-1,0,0],[310,-1,0,0],[315,-1,0,0],[320,-1,0,0],[325,-1,0,0],[552,-1,0,0],[557,-1,0,0]]',
                    2 => '[[60,0,0,0],[80,-1,0,0],[100,-1,0,0],[395,-1,0,0],[492,-1,0,0],[300,-1,0,0],[305,-1,0,0],[310,-1,0,0],[315,-1,0,0],[320,-1,0,0],[325,-1,0,0],[552,-1,0,0],[557,-1,0,0]]',
                    3 => '[[120,0,0,0],[140,-1,0,0],[160,-1,0,0],[415,-1,0,0],[497,-1,0,0],[300,-1,0,0],[305,-1,0,0],[310,-1,0,0],[315,-1,0,0],[320,-1,0,0],[325,-1,0,0],[552,-1,0,0],[557,-1,0,0]]',
                    4 => '[[180,0,0,0],[200,-1,0,0],[220,-1,0,0],[435,-1,0,0],[502,-1,0,0],[300,-1,0,0],[305,-1,0,0],[310,-1,0,0],[315,-1,0,0],[320,-1,0,0],[325,-1,0,0],[552,-1,0,0],[557,-1,0,0]]',
                    5 => '[[240,0,0,0],[260,-1,0,0],[280,-1,0,0],[455,-1,0,0],[507,-1,0,0],[300,-1,0,0],[305,-1,0,0],[310,-1,0,0],[315,-1,0,0],[320,-1,0,0],[325,-1,0,0],[552,-1,0,0],[557,-1,0,0]]',
                ];
                $sql .= ", `skill` = ?";
                $params[] = $default_skills[$p_clazz];
            }
            $sql .= " WHERE `name` = ?";
            $params[] = $p_name;
            $conn->prepare($sql)->execute($params);
            $suffix = $class_changed ? ' Hệ phái và kỹ năng mặc định đã được cập nhật.' : '';
            $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Đã lưu thông số cơ bản của <b>".htmlspecialchars($p_name)."</b>.".$suffix."</div>";
        }
    } elseif ($action === 'save_items') {
        $p_bag3 = $_POST['p_bag3'] ?? '';
        $p_bag47 = $_POST['p_bag47'] ?? '';
        $p_it_body = $_POST['p_it_body'] ?? '';
        if (!is_array(json_decode($p_bag3, true)) || !is_array(json_decode($p_bag47, true)) || !is_array(json_decode($p_it_body, true))) {
            $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>JSON trang bị hoặc túi đồ không hợp lệ. Dữ liệu chưa được lưu.</div>";
        } else {
            $upd = $conn->prepare("UPDATE players SET `bag3` = ?, `bag47` = ?, `it_body` = ? WHERE `name` = ?");
            $upd->execute([$p_bag3, $p_bag47, $p_it_body, $p_name]);
            $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Đã lưu trang bị và túi đồ của <b>".htmlspecialchars($p_name)."</b>.</div>";
        }
    } elseif ($action === 'save_progress') {
        $p_skill = $_POST['p_skill'] ?? '';
        $p_quest = $_POST['p_quest'] ?? '';
        $p_potential = $_POST['p_potential'] ?? '';
        if (!is_array(json_decode($p_skill, true)) || !is_array(json_decode($p_potential, true)) || !validPlayerQuestJson($p_quest)) {
            $msg = "<div class='bg-red-100 text-red-700 p-2 rounded mb-4'>JSON kỹ năng, tiềm năng hoặc nhiệm vụ không hợp lệ. Dữ liệu chưa được lưu.</div>";
        } else {
            $upd = $conn->prepare("UPDATE players SET `skill` = ?, `quest` = ?, `potential` = ? WHERE `name` = ?");
            $upd->execute([$p_skill, $p_quest, $p_potential, $p_name]);
            $msg = "<div class='bg-green-100 text-green-700 p-2 rounded mb-4'>Đã lưu kỹ năng, tiềm năng và nhiệm vụ của <b>".htmlspecialchars($p_name)."</b>.</div>";
        }
    }
}

$chars = $account_chars;
$players = [];
if (is_array($chars) && count($chars) > 0) {
    $in = str_repeat('?,', count($chars) - 1) . '?';
    $p_stmt = $conn->prepare("SELECT * FROM players WHERE `name` IN ($in)");
    $p_stmt->execute($chars);
    $players = $p_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="mt-4 p-4 bg-white rounded shadow w-full mx-auto max-w-5xl">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">👤 Quản Lý Nhân Vật của: <span class="text-blue-600"><?= htmlspecialchars($account['user']) ?></span></h2>
        <a href="/Admin/users.php" class="text-blue-500 hover:underline border border-blue-500 px-3 py-1 rounded">Quay lại</a>
    </div>
    
    <?= $msg ?>
    <div class="bg-yellow-100 text-yellow-800 p-3 rounded mb-4 text-sm border border-yellow-300">
        <b>Lưu ý:</b> Hãy đảm bảo người chơi đang <b>OFFLINE</b> trước khi lưu thông số! Nếu người chơi đang trong game, dữ liệu bạn sửa sẽ bị đè lại khi họ đăng xuất!
        Việc sửa mảng JSON không hợp lệ có thể gây lỗi nạp nhân vật khi vào game. Hãy cẩn thận!
    </div>
    
    <?php 
    $is_online = intval($account['onl'] ?? 0) !== 0; 
    $disabled_attr = $is_online ? 'disabled' : '';
    $disabled_class = $is_online ? 'opacity-50 cursor-not-allowed' : '';
    ?>
    
    <?php if ($is_online): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 shadow-sm" role="alert">
        <strong class="font-bold">Cảnh báo nghiêm trọng:</strong>
        <span class="block sm:inline"> Tài khoản này hiện đang Online trong game! Toàn bộ tính năng lưu đã bị vô hiệu hóa để tránh mất dữ liệu do Server tự động ghi đè.</span>
        <form method="POST" action="" class="mt-3" onsubmit="return confirm('Bạn có chắc muốn ngắt kết nối tài khoản này để chỉnh sửa không?');">
            <input type="hidden" name="action" value="disconnect_account">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow">
                ⚠️ Ngắt kết nối (KICK) ngay
            </button>
        </form>
    </div>
    <?php endif; ?>

    <div class="flex flex-col gap-6">
        <?php foreach($players as $p): 
            $level_arr = json_decode($p['level'], true);
            $point_arr = json_decode($p['point_inven'], true);
            $level = isset($level_arr[0]) ? $level_arr[0] : 0;
            $vang = isset($point_arr[0]) ? $point_arr[0] : 0;
            $ruby = isset($point_arr[1]) ? $point_arr[1] : 0;
            $extol = isset($point_arr[2]) ? $point_arr[2] : 0;
            
            // Format JSON cho dễ nhìn
            $f_bag3 = json_encode(json_decode($p['bag3']), JSON_PRETTY_PRINT);
            $f_bag47 = json_encode(json_decode($p['bag47']), JSON_PRETTY_PRINT);
            $f_it_body = json_encode(json_decode($p['it_body']), JSON_PRETTY_PRINT);
            $f_skill = json_encode(json_decode($p['skill']), JSON_PRETTY_PRINT);
            $f_quest = json_encode(json_decode($p['quest']), JSON_PRETTY_PRINT);
            $f_potential = json_encode(json_decode($p['potential']), JSON_PRETTY_PRINT);
        ?>
        <form method="POST" class="border border-gray-300 p-4 rounded bg-gray-50 shadow-sm">
            <input type="hidden" name="p_name" value="<?= htmlspecialchars($p['name']) ?>">
            
            <h3 class="text-lg font-bold text-red-600 mb-4 pb-2 border-b border-gray-300">⚔️ Nhân vật: <?= htmlspecialchars($p['name']) ?></h3>
            
            <!-- Hướng dẫn an toàn -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded shadow-sm">
                <div class="flex items-center mb-2">
                    <span class="text-xl mr-2">⚠️</span>
                    <h3 class="text-yellow-800 font-bold text-lg">Hướng Dẫn Chỉnh Sửa & Cảnh Báo An Toàn</h3>
                </div>
                <div class="text-yellow-800 text-sm space-y-2 pl-7">
                    <p><strong>1. Đổi Class (Hệ Phái):</strong> Nếu bạn đổi Class, bạn <strong>PHẢI</strong> cập nhật lại bộ Kỹ năng (skill) bên dưới sao cho khớp với hệ phái mới. Nếu kỹ năng không khớp hệ phái, nhân vật sẽ bị lỗi đòn đánh hoặc kẹt game.</p>
                    <p><strong>2. ID Vật phẩm & Kỹ năng:</strong> Chỉ nhập những ID có thật (tên màu xanh dương sẽ xuất hiện nếu ID hợp lệ). Nhập sai ID có thể làm hỏng túi đồ.</p>
                    <p><strong>3. Cột Khóa & Độ bền:</strong> Khóa chỉ nhận giá trị <strong>0 (Không khóa)</strong> hoặc <strong>1 (Khóa)</strong>. Độ bền không được bỏ trống.</p>
                    <p><strong>4. Chỉ số Trang bị (Options):</strong> Bảng lồng bên trong giúp bạn thêm/sửa chỉ số dễ dàng. Nếu muốn xóa sạch chỉ số, cứ bấm [x] hết các dòng, không được để trống ô ID hay Giá trị.</p>
                    <p><strong>5. Bảng Nhiệm Vụ (quest):</strong> Nhiệm vụ nào phải ở đúng bước của nhiệm vụ đó. <strong>KHÔNG ĐƯỢC</strong> tự ý tăng ID nhiệm vụ (chỉnh nhảy cóc) hay tự ý thêm bước bừa bãi. Việc chỉnh quá giới hạn sẽ làm kẹt nhân vật vĩnh viễn.</p>
                    <p class="font-bold text-red-600 mt-2">❗ Lời khuyên: Nên tạo một nhân vật test (phụ) để thử nghiệm các thao tác thêm đồ, đổi class trước khi áp dụng cho người chơi thật!</p>
                </div>
            </div>

            <!-- Thông số cơ bản -->
            <h4 class="font-bold text-gray-700 mb-2">1. Thông số cơ bản</h4>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Class (Hệ phái)</label>
                    <select name="p_clazz" class="border p-2 rounded w-full <?= $disabled_class ?>" <?= $disabled_attr ?>>
                        <option value="1" <?= $p['clazz'] == 1 ? 'selected' : '' ?>>Võ Sĩ</option>
                        <option value="2" <?= $p['clazz'] == 2 ? 'selected' : '' ?>>Kiếm Khách</option>
                        <option value="3" <?= $p['clazz'] == 3 ? 'selected' : '' ?>>Đầu Bếp</option>
                        <option value="4" <?= $p['clazz'] == 4 ? 'selected' : '' ?>>Hoa Tiêu</option>
                        <option value="5" <?= $p['clazz'] == 5 ? 'selected' : '' ?>>Xạ Thủ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Level</label>
                    <input type="number" name="p_level" value="<?= $level ?>" class="border p-2 rounded w-full <?= $disabled_class ?>" <?= $disabled_attr ?>>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Vàng</label>
                    <input type="number" name="p_vang" value="<?= $vang ?>" class="border p-2 rounded w-full <?= $disabled_class ?>" <?= $disabled_attr ?>>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Ruby</label>
                    <input type="number" name="p_ruby" value="<?= $ruby ?>" class="border p-2 rounded w-full <?= $disabled_class ?>" <?= $disabled_attr ?>>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Extol</label>
                    <input type="number" name="p_extol" value="<?= $extol ?>" class="border p-2 rounded w-full <?= $disabled_class ?>" <?= $disabled_attr ?>>
                </div>
            </div>
            <div class="mb-6 text-right">
                <button type="submit" name="action" value="save_basic" class="bg-blue-600 text-white font-bold px-4 py-2 rounded hover:bg-blue-700 <?= $disabled_class ?>" onclick="return confirm('Lưu riêng thông số cơ bản?');" <?= $disabled_attr ?>>Lưu thông số cơ bản</button>
            </div>
            
            <!-- Trang bị và Túi đồ -->
            <h4 class="font-bold text-gray-700 mb-2">2. Trang bị & Túi đồ (JSON)</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Trang bị đang mặc (it_body)</label>
                    <textarea name="p_it_body" class="border p-2 rounded w-full text-xs font-mono <?= $disabled_class ?>" rows="8" <?= $disabled_attr ?>><?= $f_it_body ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Túi đồ Trang Bị (bag3)</label>
                    <textarea name="p_bag3" class="border p-2 rounded w-full text-xs font-mono <?= $disabled_class ?>" rows="8" <?= $disabled_attr ?>><?= $f_bag3 ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Túi Nguyên Liệu (bag47)</label>
                    <textarea name="p_bag47" class="border p-2 rounded w-full text-xs font-mono <?= $disabled_class ?>" rows="8" <?= $disabled_attr ?>><?= $f_bag47 ?></textarea>
                </div>
            </div>
            <div class="mb-6 text-right">
                <button type="submit" name="action" value="save_items" class="bg-green-600 text-white font-bold px-4 py-2 rounded hover:bg-green-700 <?= $disabled_class ?>" onclick="return confirm('Lưu riêng trang bị và túi đồ?');" <?= $disabled_attr ?>>Lưu trang bị và túi đồ</button>
            </div>
            
            <!-- Kỹ năng & Khác -->
            <h4 class="font-bold text-gray-700 mb-2">3. Kỹ năng, Tiềm năng & Nhiệm vụ (JSON)</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Kỹ năng (skill)</label>
                    <textarea name="p_skill" class="border p-2 rounded w-full text-xs font-mono <?= $disabled_class ?>" rows="8" <?= $disabled_attr ?>><?= $f_skill ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Điểm tiềm năng (potential)</label>
                    <textarea name="p_potential" class="border p-2 rounded w-full text-xs font-mono <?= $disabled_class ?>" rows="8" <?= $disabled_attr ?>><?= $f_potential ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nhiệm vụ (quest)</label>
                    <textarea name="p_quest" class="border p-2 rounded w-full text-xs font-mono <?= $disabled_class ?>" rows="8" <?= $disabled_attr ?>><?= $f_quest ?></textarea>
                </div>
            </div>
            
            <div class="text-right border-t border-gray-300 pt-4">
                <button type="submit" name="action" value="save_progress" class="bg-purple-600 text-white font-bold px-4 py-2 rounded hover:bg-purple-700 <?= $disabled_class ?>" onclick="return confirm('Lưu riêng kỹ năng, tiềm năng và nhiệm vụ?');" <?= $disabled_attr ?>>Lưu kỹ năng, tiềm năng và nhiệm vụ</button>
            </div>
        </form>
        <?php endforeach; ?>
        
        <?php if(empty($players)): ?>
        <div class="p-4 text-center text-gray-500 border border-gray-200 rounded">Tài khoản này chưa có nhân vật nào.</div>
        <?php endif; ?>
    </div>
</div>

<?php
// Lấy danh sách tên vật phẩm, kỹ năng, nhiệm vụ, chỉ số để tra cứu nhanh trong JS
$item3_names = [];
$item4_names = [];
$item7_names = [];
$skill_names = [];
$quest_names = [];
$option_names = [];
$quest_metadata = [];
try {
    // Tách riêng item3 (trang bị) và item4, item7 (nguyên liệu, vật phẩm) để tránh trùng ID
    $res3 = $conn->query("SELECT id, name FROM item3");
    while ($row = $res3->fetch(PDO::FETCH_ASSOC)) {
        $item3_names[$row['id']] = $row['name'];
    }
    
    $res4 = $conn->query("SELECT id, name FROM item4");
    while ($row = $res4->fetch(PDO::FETCH_ASSOC)) {
        $item4_names[$row['id']] = $row['name'];
    }

    $res7 = $conn->query("SELECT id, name FROM item7");
    while ($row = $res7->fetch(PDO::FETCH_ASSOC)) {
        $item7_names[$row['id']] = $row['name'];
    }
    
    $res2 = $conn->query("SELECT id_index, name FROM skill");
    while ($row = $res2->fetch(PDO::FETCH_ASSOC)) {
        $skill_names[$row['id_index']] = $row['name'];
    }
    
    $res3 = $conn->query("SELECT index_server, statusQuest, name, data_quest FROM quests");
    while ($row = $res3->fetch(PDO::FETCH_ASSOC)) {
        $quest_names[$row['index_server']] = $row['name'];
        
        $idx = $row['index_server'];
        if (!isset($quest_metadata[$idx])) {
            $quest_metadata[$idx] = ['steps' => []];
        }
        
        // Save the step name
        $quest_metadata[$idx]['steps'][$row['statusQuest']] = $row['name'];
        
        // Parse data_quest if statusQuest == 1 to get max progress
        if ($row['statusQuest'] == 1 && !empty($row['data_quest'])) {
            $dq = json_decode($row['data_quest'], true);
            $quest_metadata[$idx]['data_quest'] = $dq;
        }
    }
    
    $res4 = $conn->query("SELECT * FROM itemoption");
    while ($row = $res4->fetch(PDO::FETCH_ASSOC)) {
        $option_names[$row['id']] = [
            'name' => $row['name'],
            'percent' => isset($row['percent']) ? $row['percent'] : 0
        ];
    }
} catch(Exception $e) {}
?>
<script>
const ITEM3_NAMES = <?= json_encode($item3_names, JSON_UNESCAPED_UNICODE) ?>;
const ITEM4_NAMES = <?= json_encode($item4_names, JSON_UNESCAPED_UNICODE) ?>;
const ITEM7_NAMES = <?= json_encode($item7_names, JSON_UNESCAPED_UNICODE) ?>;
const SKILL_NAMES = <?= json_encode($skill_names) ?>;
const QUEST_NAMES = <?= json_encode($quest_names) ?>;
const OPTION_NAMES = <?= json_encode($option_names) ?>;
const QUEST_METADATA = <?= json_encode($quest_metadata ?? []) ?>;

function getOptionNames(valArr) {
    if (!valArr || !Array.isArray(valArr) || valArr.length === 0) return 'Không có chỉ số';
    let strs = [];
    valArr.forEach(op => {
        if (Array.isArray(op) && op.length >= 2) {
            let opId = op[0];
            let opVal = op[1];
            let opt = OPTION_NAMES[opId];
            if (opt) {
                let tName = typeof opt === 'object' ? opt.name : opt;
                let tPercent = typeof opt === 'object' ? opt.percent : 0;
                let displayValStr = opVal !== undefined ? opVal.toString() : '0';
                if (tPercent == 1) displayValStr = (parseInt(opVal) / 10).toFixed(1).replace('.', ',') + '%';
                else if (tPercent == 2) displayValStr = (parseInt(opVal) / 100).toFixed(2).replace('.', ',') + '%';
                strs.push(tName.includes('#') ? tName.replace('#', displayValStr) : `${tName} ${displayValStr}`);
            } else {
                strs.push(`ID ${opId}: ${opVal}`);
            }
        }
    });
    return strs.join(' | ');
}

function getName(val, fieldName, rowData = null) {

    if (fieldName === 'p_it_body' || fieldName === 'p_bag3') {
        return ITEM3_NAMES[val] || 'Không rõ (ID Rác?)';
    } else if (fieldName === 'p_bag47') {
        let cat = rowData ? rowData[0] : -1;
        if (cat == 4) return ITEM4_NAMES[val] || 'Không rõ (ID Rác?)';
        if (cat == 7) return ITEM7_NAMES[val] || 'Không rõ (ID Rác?)';
        return 'Không rõ (ID Rác?)';
    } else if (fieldName === 'p_skill') {
        return SKILL_NAMES[val] || 'Không rõ (ID Rác?)';
    } else if (fieldName === 'p_quest') {
        return QUEST_NAMES[val] || 'Không rõ (ID Rác?)';
    }
    return '';
}

document.addEventListener('DOMContentLoaded', function() {
    const jsonFields = ['p_it_body', 'p_bag3', 'p_bag47', 'p_skill', 'p_potential', 'p_quest'];
    
    document.querySelectorAll('form').forEach((form, formIndex) => {
        jsonFields.forEach(fieldName => {
            const textareas = form.querySelectorAll(`textarea[name="${fieldName}"]`);
            if (textareas.length === 0) return;
            
            const textarea = textareas[0];
            const container = document.createElement('div');
            container.className = 'json-editor-container overflow-x-auto bg-white border border-gray-300 rounded p-2';
            textarea.parentNode.insertBefore(container, textarea);
            
            textarea.style.display = 'none';
            
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'text-xs bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded mb-2 font-bold';
            toggleBtn.innerText = 'Chuyển đổi Bảng / Raw JSON';
            toggleBtn.onclick = function() {
                if (textarea.style.display === 'none') {
                    textarea.style.display = 'block';
                    container.style.display = 'none';
                } else {
                    textarea.style.display = 'none';
                    container.style.display = 'block';
                    renderTable();
                }
            };
            textarea.parentNode.insertBefore(toggleBtn, container);

            let data;
            let hiddenCols = [];
            
            // Định nghĩa tên cột cho dễ hiểu và các cột cần ẩn
            let headers = [];
            let potentialNames = ['Tiềm năng (dư)', 'Sức mạnh', 'Phòng thủ', 'Thể lực', 'Tinh thần', 'Chỉ số 5', 'Thông thạo', 'Khác'];
            
            if (fieldName === 'p_it_body' || fieldName === 'p_bag3') {
                headers = ['ID Trang Bị', 'Khóa', 'Cấp (+) ', 'Khóa 2', 'tt4', 'tt5', 'Độ Bền', 'tt7', 'Chỉ số (Options)', 'tt9', 'tt10'];
                hiddenCols = [3, 4, 5, 7, 9, 10]; // Chỉ giữ lại ID (0), Khóa (1), Cấp cường hóa (2), Độ bền (6) và Options (8)
            } else if (fieldName === 'p_bag47') {
                headers = ['Loại (Category)', 'ID Vật Phẩm', 'Số Lượng', 'tt3', 'tt4'];
                hiddenCols = [3, 4]; // Loại (0), ID (1), Số Lượng (2)
            } else if (fieldName === 'p_skill') {
                headers = ['ID Kỹ Năng', 'Điểm KN', 'tt2', 'tt3'];
                hiddenCols = [2, 3]; // Chỉ giữ lại ID (0) và Điểm KN (1)
            } else if (fieldName === 'p_quest') {
                headers = ['ID Nhiệm Vụ', 'Dữ liệu nhiệm vụ (Bước)', 'Số lượng'];
            }

            function renderTable() {
                try {
                    data = JSON.parse(textarea.value);
                } catch (e) {
                    container.innerHTML = '<div class="text-red-500 text-xs">Lỗi JSON! Hãy dùng chế độ Raw JSON để sửa.</div>';
                    return;
                }

                if (!Array.isArray(data)) {
                    container.innerHTML = '<div class="text-red-500 text-xs">Chỉ hỗ trợ mảng (Array).</div>';
                    return;
                }

                let is2D = data.length > 0 && Array.isArray(data[0]);
                let html = '<table class="w-full text-xs text-center border-collapse border border-gray-300">';
                
                if (data.length === 0) {
                    html += '<tr><td class="p-2 text-gray-500">Trống</td></tr>';
                } else {
                    if (is2D) {
                        let maxCols = 0;
                        data.forEach(row => { if (Array.isArray(row) && row.length > maxCols) maxCols = row.length; });
                        
                        html += '<tr class="bg-gray-100">';
                        html += '<th class="border border-gray-300 p-1 w-8">#</th>';
                        for (let i = 0; i < maxCols; i++) {
                            if (hiddenCols.includes(i)) continue;
                            let hName = headers[i] || `Cột ${i}`;
                            html += `<th class="border border-gray-300 p-1 w-20 whitespace-nowrap">${hName}</th>`;
                        }
                        html += '<th class="border border-gray-300 p-1 w-10">Xóa</th></tr>';

                        data.forEach((row, rIdx) => {
                            html += `<tr class="hover:bg-yellow-50">`;
                            html += `<td class="border border-gray-300 p-1 font-bold bg-gray-100">${rIdx}</td>`;
                            for (let cIdx = 0; cIdx < maxCols; cIdx++) {
                                if (hiddenCols.includes(cIdx)) continue;
                                let val = row[cIdx];
                                let valStr = val !== undefined ? (typeof val === 'object' && val !== null ? JSON.stringify(val) : val) : '';
                                
                                // Nếu là cột Chỉ số (Options) của trang bị, hiển thị dạng bảng con
                                if (cIdx === 8 && (fieldName === 'p_it_body' || fieldName === 'p_bag3')) {
                                    let opsArr = [];
                                    try { opsArr = typeof val === 'string' ? JSON.parse(val) : val; } catch(e){}
                                    if (!Array.isArray(opsArr)) opsArr = [];
                                    
                                    html += `<td class="border border-gray-300 p-1 relative text-left bg-white align-top min-w-[200px]">`;
                                    html += `<div class="ops-container" data-r="${rIdx}">`;
                                    opsArr.forEach((op, opIdx) => {
                                        let opId = op[0] !== undefined ? op[0] : '';
                                        let opVal = op[1] !== undefined ? op[1] : '';
                                        let opName = 'Không rõ';
                                        if (opId !== '' && OPTION_NAMES[opId]) {
                                            let opt = OPTION_NAMES[opId];
                                            let tName = typeof opt === 'object' ? opt.name : opt;
                                            let tPercent = typeof opt === 'object' ? opt.percent : 0;
                                            
                                            let displayValStr = opVal !== '' ? opVal : '0';
                                            if (tPercent == 1 && opVal !== '') {
                                                displayValStr = (parseInt(opVal) / 10).toString().replace('.', ',') + '%';
                                            } else if (tPercent == 2 && opVal !== '') {
                                                displayValStr = (parseInt(opVal) / 100).toString().replace('.', ',') + '%';
                                            }
                                            
                                            if (tName.includes('#')) {
                                                opName = tName.replace('#', displayValStr);
                                            } else {
                                                opName = `${tName} ${displayValStr}`;
                                            }
                                        } else if (opId !== '') {
                                            opName = `ID ${opId}`;
                                        }
                                        
                                        html += `<div class="flex items-center space-x-1 mb-1 border-b border-gray-100 pb-1">
                                            <input type="number" class="w-10 border border-gray-300 rounded text-[10px] p-0.5 text-center op-id" data-r="${rIdx}" data-opidx="${opIdx}" value="${opId}" placeholder="ID">
                                            <input type="number" class="w-14 border border-gray-300 rounded text-[10px] p-0.5 text-center op-val" data-r="${rIdx}" data-opidx="${opIdx}" value="${opVal}" placeholder="Val">
                                            <span class="text-[10px] text-purple-600 truncate w-32 op-name" id="opname-${rIdx}-${opIdx}" title="${opName}">${opName}</span>
                                            <button type="button" class="text-red-500 font-bold px-1 op-del-btn hover:bg-red-100 rounded" data-r="${rIdx}" data-opidx="${opIdx}">x</button>
                                        </div>`;
                                    });
                                    html += `<button type="button" class="text-[10px] bg-green-500 text-white px-2 py-0.5 rounded op-add-btn hover:bg-green-600" data-r="${rIdx}">+ Thêm chỉ số</button>`;
                                    html += `</div></td>`;
                                } else if (cIdx === 1 && fieldName === 'p_quest') {
                                    let opsArr = [];
                                    try { opsArr = typeof val === 'string' ? JSON.parse(val) : val; } catch(e){}
                                    if (!Array.isArray(opsArr)) opsArr = [];
                                    
                                    let questId = data[rIdx][0];
                                    let questInfo = QUEST_METADATA[questId] || {steps: {}};
                                    let questSteps = questInfo.steps || {};
                                    let dataQuest = questInfo.data_quest || [];
                                    let maxSteps = dataQuest.length > 0 ? dataQuest.length : 1; // Default to 1 if no data_quest
                                    
                                    html += `<td class="border border-gray-300 p-1 relative text-left bg-white align-top min-w-[200px]">`;
                                    html += `<div class="quest-container" data-r="${rIdx}">`;
                                    opsArr.forEach((op, opIdx) => {
                                        let val1 = Array.isArray(op) && op.length > 3 ? op[3] : 0;
                                        let stepName = questSteps[0] ? `[Tiến độ ${opIdx}] ${questSteps[0]}` : `Tiến độ ${opIdx}:`;
                                        
                                        // Find max progress limit for this step
                                        let maxProgress = '';
                                        let maxProgressStr = '/?';
                                        if (dataQuest[opIdx] && (dataQuest[opIdx][0] === 1 || dataQuest[opIdx][0] === 2)) {
                                            maxProgress = `max="${dataQuest[opIdx][2]}"`;
                                            maxProgressStr = `/${dataQuest[opIdx][2]}`;
                                        }
                                        
                                        html += `<div class="flex items-center justify-between mb-1 border-b border-gray-100 pb-1">
                                            <span class="text-[10px] font-semibold text-blue-700 whitespace-nowrap overflow-hidden text-ellipsis mr-1" style="max-width: 120px;" title="${stepName}">${stepName}</span>
                                            <div class="flex items-center space-x-1 shrink-0">
                                                <input type="number" class="w-12 border border-gray-300 rounded text-[10px] p-0.5 text-center q-val" data-r="${rIdx}" data-opidx="${opIdx}" value="${val1}" min="0" ${maxProgress}>
                                                <span class="text-[10px] font-bold text-gray-500 w-6">${maxProgressStr}</span>
                                                <button type="button" class="text-red-500 font-bold px-1 q-del-btn hover:bg-red-100 rounded" data-r="${rIdx}" data-opidx="${opIdx}">x</button>
                                            </div>
                                        </div>`;
                                    });
                                    if (opsArr.length < maxSteps) {
                                        html += `<button type="button" class="text-[10px] bg-green-500 text-white px-2 py-0.5 rounded q-add-btn hover:bg-green-600 mt-1 w-full" data-r="${rIdx}">+ Thêm tiến độ</button>`;
                                    } else {
                                        html += `<div class="text-[10px] text-center text-red-500 mt-1 font-bold italic">Đã đạt tối đa ${maxSteps} tiến độ</div>`;
                                    }
                                    html += `</div></td>`;
                                } else {
                                    // Cột bình thường
                                    html += `<td class="border border-gray-300 p-0 relative">`;
                                    
                                    // Xác định cột nào chứa ID
                                    let isIdCol = (cIdx === 0);
                                    if (fieldName === 'p_bag47') isIdCol = (cIdx === 1);
                                    
                                    // Validate ID
                                    let isInvalid = false;
                                    if (isIdCol && fieldName !== 'p_potential') {
                                        let nameCheck = getName(val, fieldName, row);
                                        if (nameCheck.includes('Không rõ') || nameCheck.includes('ID Rác?')) isInvalid = true;
                                    }
                                    
                                    let inputClass = isInvalid ? "w-full h-full border-none bg-red-100 text-red-600 font-bold text-center focus:bg-red-200 p-1" : "w-full h-full border-none bg-transparent text-center focus:bg-white focus:ring-1 p-1 font-bold";
                                    
                                    html += `<input type="text" class="${inputClass}" data-r="${rIdx}" data-c="${cIdx}" value='${valStr.toString().replace(/'/g, "&#39;")}'>`;
                                    
                                    // Hiển thị tên vật phẩm/kỹ năng/nhiệm vụ ở cột ID
                                    if (isIdCol && fieldName !== 'p_potential') {
                                        let displayName = getName(val, fieldName, row);
                                        let displayClass = isInvalid ? "text-red-600 font-bold" : "text-blue-600";
                                        html += `<div class="text-[10px] ${displayClass} truncate px-1 item-name-display" id="name-${fieldName}-${rIdx}">${displayName}</div>`;
                                    }
                                    html += `</td>`;
                                }
                            }
                            html += `<td class="border border-gray-300 p-1"><button type="button" class="text-red-500 font-bold del-btn" data-r="${rIdx}">X</button></td></tr>`;
                        });
                    } else {
                        html += '<tr class="bg-gray-100"><th class="border border-gray-300 p-1 w-24">Thuộc tính</th><th class="border border-gray-300 p-1">Giá trị</th><th class="border border-gray-300 p-1 w-10">Xóa</th></tr>';
                        data.forEach((val, rIdx) => {
                            if (hiddenCols.includes(rIdx)) return;
                            let valStr = typeof val === 'object' && val !== null ? JSON.stringify(val) : val;
                            let hName = headers[rIdx] || `Cột ${rIdx}`;
                            if (fieldName === 'p_potential') {
                                hName = potentialNames[rIdx] || `Index ${rIdx}`;
                            }
                            
                            html += `<tr class="hover:bg-yellow-50">`;
                            html += `<td class="border border-gray-300 p-1 font-bold bg-gray-100 whitespace-nowrap">${hName}</td>`;
                            
                            html += `<td class="border border-gray-300 p-0 relative">`;
                            html += `<input type="text" class="w-full h-full border-none bg-transparent text-center focus:bg-white focus:ring-1 p-1 font-bold text-blue-600" data-r="${rIdx}" data-c="none" value='${valStr.toString().replace(/'/g, "&#39;")}'>`;
                            
                            // Nếu là quest (mảng 1D) và index là 0 thì hiện tên quest
                            if (rIdx === 0 && fieldName === 'p_quest') {
                                let displayName = getName(val, fieldName, data);
                                html += `<div class="text-[10px] text-blue-600 truncate px-1 item-name-display" id="name-${fieldName}-${rIdx}">${displayName}</div>`;
                            }
                            html += `</td>`;
                            
                            html += `<td class="border border-gray-300 p-1"><button type="button" class="text-red-500 font-bold del-btn" data-r="${rIdx}">X</button></td></tr>`;
                        });
                    }
                }
                html += '</table>';
                

                if (fieldName !== 'p_it_body' && fieldName !== 'p_potential') {
                    html += `<div class="mt-1 text-right"><button type="button" class="bg-blue-500 text-white px-2 py-1 rounded text-xs add-btn" data-field="${fieldName}">+ Thêm dòng mới</button></div>`;
                }
                container.innerHTML = html;

                // Xử lý sự kiện cho bảng con Options
                container.querySelectorAll('.op-id, .op-val').forEach(inp => {
                    inp.addEventListener('input', (e) => {
                        let opidx = e.target.getAttribute('data-opidx');
                        let containerRow = e.target.closest('.ops-container');
                        let idInp = containerRow.querySelectorAll('.op-id')[opidx];
                        let valInp = containerRow.querySelectorAll('.op-val')[opidx];
                        let nameSpan = containerRow.querySelectorAll('.op-name')[opidx];
                        
                        let id = parseInt(idInp.value);
                        let v = parseInt(valInp.value);
                        
                        if (!isNaN(id)) {
                            let opt = OPTION_NAMES[id];
                            let opNameStr = `ID ${id}`;
                            if (opt) {
                                let tName = typeof opt === 'object' ? opt.name : opt;
                                let tPercent = typeof opt === 'object' ? opt.percent : 0;
                                let displayValStr = !isNaN(v) ? v.toString() : '0';
                                if (tPercent == 1 && !isNaN(v)) displayValStr = (v / 10).toFixed(1).replace('.', ',') + '%';
                                else if (tPercent == 2 && !isNaN(v)) displayValStr = (v / 100).toFixed(2).replace('.', ',') + '%';
                                opNameStr = tName.includes('#') ? tName.replace('#', displayValStr) : `${tName} ${displayValStr}`;
                            }
                            nameSpan.innerText = opNameStr;
                        }
                        
                        // Save immediately on input
                        let r = e.target.getAttribute('data-r');
                        let allIds = containerRow.querySelectorAll('.op-id');
                        let allVals = containerRow.querySelectorAll('.op-val');
                        
                        let newOps = [];
                        for(let i=0; i<allIds.length; i++) {
                            let currId = parseInt(allIds[i].value);
                            let currV = parseInt(allVals[i].value);
                            if (!isNaN(currId) && !isNaN(currV)) {
                                newOps.push([currId, currV]);
                            }
                        }
                        data[r][8] = newOps;
                        textarea.value = JSON.stringify(data, null, 4);
                    });
                });

                container.querySelectorAll('.op-del-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        let r = e.target.getAttribute('data-r');
                        let opidx = e.target.getAttribute('data-opidx');
                        if (Array.isArray(data[r][8])) {
                            data[r][8].splice(opidx, 1);
                            textarea.value = JSON.stringify(data, null, 4);
                            renderTable();
                        }
                    });
                });

                container.querySelectorAll('.op-add-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        let r = e.target.getAttribute('data-r');
                        if (!Array.isArray(data[r][8])) data[r][8] = [];
                        data[r][8].push([0, 0]); // Mặc định option ID = 0, Value = 0
                        textarea.value = JSON.stringify(data, null, 4);
                        renderTable();
                    });
                });

                // Xử lý sự kiện cho bảng con Nhiệm vụ
                container.querySelectorAll('.q-val').forEach(inp => {
                    inp.addEventListener('input', (e) => {
                        let r = e.target.getAttribute('data-r');
                        let containerRow = e.target.closest('.quest-container');
                        let allVals = containerRow.querySelectorAll('.q-val');
                        
                        let newQuestData = [];
                        let questId = data[r][0];
                        let questInfo = QUEST_METADATA[questId] || {};
                        let dataQuest = questInfo.data_quest || [];
                        for(let i=0; i<allVals.length; i++) {
                            let v = parseInt(allVals[i].value);
                            let progress = Array.isArray(data[r][1][i])
                                ? data[r][1][i].slice()
                                : (Array.isArray(dataQuest[i]) ? dataQuest[i].slice() : [0, 0, 0, 0]);
                            while (progress.length < 4) progress.push(0);
                            progress[3] = !isNaN(v) ? v : 0;
                            newQuestData.push(progress);
                        }
                        data[r][1] = newQuestData;
                        textarea.value = JSON.stringify(data, null, 4);
                    });
                });

                container.querySelectorAll('.q-del-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        let r = e.target.getAttribute('data-r');
                        let opidx = parseInt(e.target.getAttribute('data-opidx'));
                        if (Array.isArray(data[r][1])) {
                            data[r][1].splice(opidx, 1);
                            textarea.value = JSON.stringify(data, null, 4);
                            renderTable();
                        }
                    });
                });

                container.querySelectorAll('.q-add-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        let r = e.target.getAttribute('data-r');
                        if (!Array.isArray(data[r][1])) data[r][1] = [];
                        let questId = data[r][0];
                        let questInfo = QUEST_METADATA[questId] || {};
                        let templateRows = questInfo.data_quest || [];
                        let nextIndex = data[r][1].length;
                        let progress = Array.isArray(templateRows[nextIndex])
                            ? templateRows[nextIndex].slice()
                            : [0, 0, 0, 0];
                        data[r][1].push(progress);
                        textarea.value = JSON.stringify(data, null, 4);
                        renderTable();
                    });
                });

                container.querySelectorAll('input[data-c]').forEach(inp => {
                    inp.addEventListener('input', (e) => {
                        let r = e.target.getAttribute('data-r');
                        let c = e.target.getAttribute('data-c');
                        let val = e.target.value;
                        
                        // Update item name display
                        let isIdCol = (c === '0');
                        if (fieldName === 'p_bag47') isIdCol = (c === '1');
                        if (c === 'none' && r === '0') isIdCol = true;
                        
                        if (isIdCol && fieldName !== 'p_potential') {
                            let nameDiv = document.getElementById(`name-${fieldName}-${r}`);
                            if (nameDiv) {
                                let displayName = getName(val, fieldName, data[parseInt(r)]);
                                nameDiv.innerText = displayName;
                                
                                let isInvalid = displayName.includes('Không rõ') || displayName.includes('ID Rác?');
                                if (isInvalid) {
                                    nameDiv.className = "text-[10px] truncate px-1 item-name-display text-white bg-red-500 rounded p-0.5";
                                    e.target.className = "w-full h-full border-none bg-red-100 text-red-600 font-bold text-center focus:bg-red-200 p-1";
                                } else {
                                    nameDiv.className = "text-[10px] truncate px-1 item-name-display text-blue-600";
                                    e.target.className = "w-full h-full border-none bg-transparent text-center focus:bg-white focus:ring-1 p-1 font-bold";
                                }
                            }
                        }
                        
                        // Update options display
                        if (c === '8' && (fieldName === 'p_it_body' || fieldName === 'p_bag3')) {
                            let opsDiv = document.getElementById(`ops-${fieldName}-${r}`);
                            if (opsDiv) {
                                try {
                                    let parsedOps = JSON.parse(val);
                                    opsDiv.innerText = getOptionNames(parsedOps);
                                } catch (e) {
                                    opsDiv.innerText = 'Lỗi JSON';
                                }
                            }
                        }
                        
                        // Save to JSON immediately on input (fixes mobile submit-without-blur issue)
                        let parsedVal = val;
                        if (!isNaN(val) && val.trim() !== '') {
                            parsedVal = Number(val);
                        } else if (val.startsWith('[') || val.startsWith('{')) {
                            try { parsedVal = JSON.parse(val); } catch (err) {}
                        }

                        if (c === 'none') {
                            data[parseInt(r)] = parsedVal;
                        } else {
                            let ri = parseInt(r), ci = parseInt(c);
                            if (!data[ri]) data[ri] = [];
                            data[ri][ci] = parsedVal;
                        }
                        textarea.value = JSON.stringify(data, null, 4);
                    });
                    
                    // Re-render only on change if ID changed (to format options nicely if needed)
                    inp.addEventListener('change', (e) => {
                        let c = e.target.getAttribute('data-c');
                        if (c == 0) {
                            renderTable();
                        }
                    });
                });

                container.querySelectorAll('.del-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        let r = parseInt(e.target.getAttribute('data-r'));
                        data.splice(r, 1);
                        textarea.value = JSON.stringify(data, null, 4);
                        renderTable();
                    });
                });

                let addBtn = container.querySelector('.add-btn');
                if (addBtn) {
                    addBtn.addEventListener('click', () => {
                        if (is2D) {
                            let maxCols = 0;
                            data.forEach(row => { if (Array.isArray(row) && row.length > maxCols) maxCols = row.length; });
                            
                            let newRow = new Array(Math.max(1, maxCols)).fill(0);
                            // Add some safe defaults for item body
                            if (fieldName === 'p_it_body' || fieldName === 'p_bag3') {
                                if (newRow.length > 8) newRow[8] = [];
                                if (newRow.length > 2) newRow[2] = 1; // Cấp cường hóa
                                if (newRow.length > 3) newRow[3] = -1;
                                if (newRow.length > 5) newRow[5] = 1;
                                if (newRow.length > 7) newRow[7] = -1;
                            } else if (fieldName === 'p_quest') {
                                newRow = [0, []];
                            }
                            data.push(newRow);
                        } else {
                            data.push(0);
                        }
                        textarea.value = JSON.stringify(data, null, 4);
                        renderTable();
                    });
                }
            }
            renderTable();
        });
    });
});
</script>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
