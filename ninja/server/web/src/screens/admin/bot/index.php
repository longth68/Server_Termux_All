<?php
require_once(__DIR__ . '/../../../../core/configs.php');

if (!isset($_SESSION['user'])) {
    header('Location: /home');
    exit;
}

$user = $_SESSION['user'];
if ($user['admin_web'] != 1) {
    header("Location: /home");
    exit();
}

$conn = SQL();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action == 'spawn') {
        $map = isset($_POST['map']) ? intval($_POST['map']) : 0;
        $count = isset($_POST['count']) ? max(1, intval($_POST['count'])) : 1;
        $level = isset($_POST['level']) ? max(1, intval($_POST['level'])) : 10;
        $hp = isset($_POST['hp']) ? max(100, intval($_POST['hp'])) : 20000;
        $damage = isset($_POST['damage']) ? max(10, intval($_POST['damage'])) : 1500;
        $speed = isset($_POST['speed']) ? max(0, intval($_POST['speed'])) : 0;

        $data = json_encode(['map' => $map, 'count' => $count, 'level' => $level, 'hp' => $hp, 'damage' => $damage, 'speed' => $speed], JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('SPAWN_BOT', ?, 0)");
        $stmt->bind_param("s", $data);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh triệu hồi ' . $count . ' bot (map ' . $map . ', lv ' . $level . ', HP ' . number_format($hp) . ', dmg ' . number_format($damage) . '). Server sẽ xử lý trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
        $stmt->close();
    } elseif ($action == 'kill') {
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('KILL_BOT', '{}', 0)");
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi lệnh xóa toàn bộ bot. Server sẽ xử lý trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
        }
        $stmt->close();
    } elseif ($action == 'kill_one') {
        $botName = isset($_POST['bot_name']) ? trim(strval($_POST['bot_name'])) : '';
        if ($botName !== '') {
            $data = json_encode(['name' => $botName], JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('KILL_ONE_BOT', ?, 0)");
            $stmt->bind_param("s", $data);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success">Đã gửi lệnh xóa bot <b>' . htmlspecialchars($botName) . '</b>.</div>';
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
            }
            $stmt->close();
        } else {
            $msg = '<div class="alert alert-danger">Thiếu tên bot.</div>';
        }
    } elseif ($action == 'chat_add' || $action == 'chat_del') {
        // Quản lý câu chat tùy chỉnh (mẫu Anwin VirtualChatConfig): sửa file trực tiếp,
        // server Java tự nạp lại mỗi 60 giây.
        $chatFile = null;
        foreach ([dirname(__DIR__, 5) . '/bot_chat.txt', __DIR__ . '/../../../../../../bot_chat.txt'] as $p) {
            if (is_file($p) || is_dir(dirname($p))) {
                $chatFile = $p;
                break;
            }
        }
        if ($chatFile === null) {
            $msg = '<div class="alert alert-danger">Không tìm thấy file bot_chat.txt.</div>';
        } elseif ($action == 'chat_add') {
            $line = isset($_POST['chat_line']) ? trim(strval($_POST['chat_line'])) : '';
            if ($line === '' || mb_strlen($line) > 120) {
                $msg = '<div class="alert alert-danger">Câu chat trống hoặc quá 120 ký tự.</div>';
            } else {
                $cur = is_file($chatFile) ? file($chatFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                $exists = false;
                foreach ((array)$cur as $c) {
                    if (trim($c) === $line) {
                        $exists = true;
                        break;
                    }
                }
                if ($exists) {
                    $msg = '<div class="alert alert-warning">Câu này đã có rồi.</div>';
                } elseif (@file_put_contents($chatFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
                    $msg = '<div class="alert alert-danger">Không ghi được file bot_chat.txt.</div>';
                } else {
                    $msg = '<div class="alert alert-success">Đã thêm câu chat, server áp dụng trong ~1 phút.</div>';
                }
            }
        } else {
            $idx = isset($_POST['chat_idx']) ? intval($_POST['chat_idx']) : -1;
            $cur = is_file($chatFile) ? array_values(file($chatFile, FILE_IGNORE_NEW_LINES)) : [];
            if (!isset($cur[$idx]) || trim($cur[$idx]) === '' || $cur[$idx][0] === '#' || $cur[$idx][0] === ';') {
                $msg = '<div class="alert alert-danger">Dòng không hợp lệ.</div>';
            } else {
                unset($cur[$idx]);
                if (@file_put_contents($chatFile, implode(PHP_EOL, array_values($cur)) . PHP_EOL, LOCK_EX) === false) {
                    $msg = '<div class="alert alert-danger">Không ghi được file bot_chat.txt.</div>';
                } else {
                    $msg = '<div class="alert alert-success">Đã xóa câu chat.</div>';
                }
            }
        }
    } elseif ($action == 'bot_edit') {
        $botName = isset($_POST['bot_name']) ? trim(strval($_POST['bot_name'])) : '';
        $data = json_encode([
            'name' => $botName,
            'level' => isset($_POST['level']) ? max(1, min(200, intval($_POST['level']))) : 0,
            'hp' => isset($_POST['hp']) ? max(0, intval($_POST['hp'])) : 0,
            'damage' => isset($_POST['damage']) ? max(0, intval($_POST['damage'])) : 0,
        ], JSON_UNESCAPED_UNICODE);
        if ($botName === '') {
            $msg = '<div class="alert alert-danger">Thiếu tên bot.</div>';
        } else {
            $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('BOT_EDIT', ?, 0)");
            $stmt->bind_param("s", $data);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success">Đã gửi lệnh chỉnh sửa bot <b>' . htmlspecialchars($botName) . '</b>.</div>';
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
            }
            $stmt->close();
        }
    } elseif ($action == 'gear_give' || $action == 'gear_take' || $action == 'gear_wear') {
        $botName = isset($_POST['bot_name']) ? trim(strval($_POST['bot_name'])) : '';
        $cmdMap = ['gear_give' => 'BOT_GEAR_GIVE', 'gear_take' => 'BOT_GEAR_TAKE', 'gear_wear' => 'BOT_GEAR_WEAR'];
        $payload = ['name' => $botName];
        if ($action == 'gear_give') {
            $payload['item'] = isset($_POST['item_id']) ? max(1, intval($_POST['item_id'])) : 0;
            $payload['qty'] = isset($_POST['qty']) ? max(1, min(9999, intval($_POST['qty']))) : 1;
        } else {
            $payload['slot'] = isset($_POST['slot']) ? intval($_POST['slot']) : -1;
            if ($action == 'gear_take') {
                $payload['place'] = isset($_POST['place']) ? strval($_POST['place']) : 'bag';
            }
        }
        if ($botName === '') {
            $msg = '<div class="alert alert-danger">Thiếu tên bot.</div>';
        } else {
            $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $cmd = $cmdMap[$action];
            $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES (?, ?, 0)");
            $stmt->bind_param("ss", $cmd, $data);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success">Đã gửi lệnh đồ cho bot <b>' . htmlspecialchars($botName) . '</b>.</div>';
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
            }
            $stmt->close();
        }
    } elseif ($action == 'bot_teleport' || $action == 'bot_gold' || $action == 'bot_regear') {
        $botName = isset($_POST['bot_name']) ? trim(strval($_POST['bot_name'])) : '';
        $cmdMap = ['bot_teleport' => 'BOT_TELEPORT', 'bot_gold' => 'BOT_GOLD', 'bot_regear' => 'BOT_REGEAR'];
        $payload = ['name' => $botName];
        if ($action == 'bot_teleport') {
            $payload['target'] = isset($_POST['target']) ? trim(strval($_POST['target'])) : '';
        } elseif ($action == 'bot_gold') {
            $payload['amount'] = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
        }
        if ($botName === '') {
            $msg = '<div class="alert alert-danger">Thiếu tên bot.</div>';
        } else {
            $data = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $cmd = $cmdMap[$action];
            $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES (?, ?, 0)");
            $stmt->bind_param("ss", $cmd, $data);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success">Đã gửi lệnh cho bot <b>' . htmlspecialchars($botName) . '</b>.</div>';
            } else {
                $msg = '<div class="alert alert-danger">Có lỗi khi gửi lệnh.</div>';
            }
            $stmt->close();
        }
    } elseif ($action == 'bot_config') {
        $cfg = [
            'enabled' => isset($_POST['enabled']) ? (intval($_POST['enabled']) === 1) : true,
            'population' => isset($_POST['population']) ? max(0, min(200, intval($_POST['population']))) : 20,
            'bots_per_map' => isset($_POST['bots_per_map']) ? max(1, min(8, intval($_POST['bots_per_map']))) : 3,
            'player_protection' => isset($_POST['player_protection']) ? max(0, min(500, intval($_POST['player_protection']))) : 80,
            'chat_rate' => isset($_POST['chat_rate']) ? max(0, min(10, floatval($_POST['chat_rate']))) : 1.0,
            'map_change_rate' => isset($_POST['map_change_rate']) ? max(0, min(10, floatval($_POST['map_change_rate']))) : 1.0,
            'gift_rate' => isset($_POST['gift_rate']) ? max(0, min(10, floatval($_POST['gift_rate']))) : 1.0,
            'afk_rate' => isset($_POST['afk_rate']) ? max(0, min(10, floatval($_POST['afk_rate']))) : 1.0,
            'gold_rate' => isset($_POST['gold_rate']) ? max(0, min(10, floatval($_POST['gold_rate']))) : 1.0,
            'exp_rate' => isset($_POST['exp_rate']) ? max(0, min(1, floatval($_POST['exp_rate']))) : 0.6,
            'presence_per_player' => isset($_POST['presence_per_player']) ? max(0, min(50, intval($_POST['presence_per_player']))) : 5,
            'presence_visit_seconds' => isset($_POST['presence_visit_seconds']) ? max(30, min(3600, intval($_POST['presence_visit_seconds']))) : 300,
        ];
        $data = json_encode($cfg, JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("INSERT INTO `web_admin_commands` (`command`, `data`, `status`) VALUES ('BOT_CONFIG', ?, 0)");
        $stmt->bind_param("s", $data);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">Đã gửi cấu hình BOT AI (pop ' . $cfg['population'] . ', per_map ' . $cfg['bots_per_map'] . '). Server sẽ áp dụng trong vài giây.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Có lỗi khi gửi cấu hình.</div>';
        }
        $stmt->close();
    }
}

$maps = [];
$result = $conn->query("SELECT `id`, `name` FROM `map` ORDER BY `id` ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $maps[] = $row;
    }
}

// Ánh xạ item id -> icon để hiển thị trang bị BOT có hình
$itemMeta = [];
$rm = $conn->query("SELECT `id`, `icon` FROM `item`");
if ($rm) {
    while ($row = $rm->fetch_assoc()) {
        $itemMeta[(int)$row['id']] = (int)$row['icon'];
    }
}
function botItemImg($id, $size = 40) {
    global $itemMeta;
    if (!isset($itemMeta[$id]) || $itemMeta[$id] <= 0) return '';
    return '<img src="/images/1/Small' . $itemMeta[$id] . '.png" width="' . $size . '" height="' . $size . '" style="image-rendering:pixelated;vertical-align:middle;margin-right:6px" onerror="this.style.display=\'none\'">';
}

$status = null;
$result = $conn->query("SELECT * FROM `server_status` WHERE `id` = 1 LIMIT 1");
if ($result) {
    $status = $result->fetch_assoc();
}

// Đọc cấu hình BOT AI hiện tại từ file (cùng máy Termux), fallback giá trị mặc định.
// Tự parse từng dòng key=value (không dùng parse_ini_file để tránh warning
// với dòng comment '#' của Java Properties).
$botCfg = ['enabled' => true, 'population' => 20, 'bots_per_map' => 3, 'player_protection' => 80, 'chat_rate' => 1.0, 'map_change_rate' => 1.0, 'gift_rate' => 1.0, 'afk_rate' => 1.0, 'gold_rate' => 1.0, 'exp_rate' => 0.6, 'presence_per_player' => 5, 'presence_visit_seconds' => 300];
$cfgCandidates = [
    dirname(__DIR__, 5) . '/bot_config.txt', // ninja/server/bot_config.txt
    __DIR__ . '/../../../../../../bot_config.txt',
    __DIR__ . '/../../../../../bot_config.txt',
];
foreach ($cfgCandidates as $cfgPath) {
    if (is_readable($cfgPath)) {
        $lines = @file($cfgPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                    continue;
                }
                $pos = strpos($line, '=');
                if ($pos === false) {
                    continue;
                }
                $k = trim(substr($line, 0, $pos));
                $v = trim(substr($line, $pos + 1));
                if (array_key_exists($k, $botCfg)) {
                    $d = $botCfg[$k];
                    $botCfg[$k] = is_bool($d) ? ($v !== 'false' && $v !== '0') : (is_int($d) ? intval($v) : floatval($v));
                }
            }
        }
        break;
    }
}

$chatLines = []; // [so_dong_trong_file => cau_chat]
foreach ([dirname(__DIR__, 5) . '/bot_chat.txt', __DIR__ . '/../../../../../../bot_chat.txt'] as $p) {
    if (is_readable($p)) {
        $raw = @file($p, FILE_IGNORE_NEW_LINES);
        if (is_array($raw)) {
            foreach ($raw as $ri => $ln) {
                $t = trim($ln);
                if ($t !== '' && $t[0] !== '#' && $t[0] !== ';') {
                    $chatLines[$ri] = $t;
                }
            }
        }
        break;
    }
}

$bots = [];
$result = $conn->query("SELECT `name`, `level`, `map_id`, `zone_id`, `x`, `y`, `hp`, `max_hp`, `state`, `personality`, `top_need`, `gold`, `gender`, `class_id`, `goal`, `damage`, `friends`, `online_min`, `updated_at` FROM `bot_status` ORDER BY `level` DESC, `name` ASC LIMIT 200");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bots[] = $row;
    }
}

$history = [];
$result = $conn->query("SELECT `id`, `command`, `target_user`, `data`, `status`, `created_at` FROM `web_admin_commands` WHERE `command` IN ('SPAWN_BOT','KILL_BOT','KILL_ONE_BOT','BOT_CONFIG','BOT_EDIT','BOT_GEAR_GIVE','BOT_GEAR_TAKE','BOT_GEAR_WEAR','BOT_TELEPORT','BOT_GOLD','BOT_REGEAR') ORDER BY `id` DESC LIMIT 30");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}
    // $conn->close(); // Đóng ở cuối file sau khi xong mọi truy vấn
?>
<div class="admin-panel">
<style>
    .admin-panel { background: #f4f6f9; color: #212529; padding: 14px; border-radius: 8px; }
    .admin-panel .bg-content { background: #fff; color: #212529; border: 1px solid #ddd; border-radius: 8px; }
    .admin-panel .card { background: #fff; color: #212529; border: 1px solid #ddd; box-shadow: 0 1px 3px rgba(0,0,0,.08); border-radius: 6px; }
    .admin-panel .card-body { color: #212529; }
    .admin-panel .text-white { color: #212529 !important; }
    .admin-panel .table { color: #212529; }
    .admin-panel .table th, .admin-panel .table td { color: #212529; border-color: #dee2e6; }
    .admin-panel h4, .admin-panel h5, .admin-panel h6 { color: #212529; }
    .admin-panel .stat-box { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 14px; }
    .admin-panel .stat-box h6 { color: #666; font-size: 13px; margin-bottom: 5px; }
    .admin-panel .stat-box h4 { font-weight: bold; font-size: 18px; margin: 0; }
    .admin-panel .progress-bar-custom { height: 6px; border-radius: 3px; margin-top: 10px; background: #e9ecef; }
    .admin-panel .progress-fill { height: 100%; border-radius: 3px; }
    .admin-panel .form-control, .admin-panel .form-select { background: #fff; color: #212529; border: 1px solid #ced4da; }
    .admin-panel .list-group-item { background: #fff; color: #212529; border-color: #dee2e6; }
    .admin-panel .text-muted { color: #6c757d !important; }
    .admin-panel .text-secondary { color: #6c757d !important; }
</style>
        <!-- THÔNG BÁO TỪ AJAX -->
        <div id="ajaxAlert" class="alert alert-success d-none" role="alert"></div>

        <!-- Stats Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="text-warning m-0 fw-bold">Quản Lý Bot AI</h4>
            <div class="text-secondary fw-bold">Online: <span id="lbOnline">0</span></div>
            <div class="text-secondary fw-bold" id="lbTime">00:00:00</div>
        </div>

        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-box">
                    <h6>Bot Online</h6>
                    <h4 id="lbBots">0</h4>
                    <div class="progress-bar-custom"><div class="progress-fill bg-warning" id="barBots" style="width: 0%"></div></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <h6>Người Online</h6>
                    <h4 id="lbOnlineUsers">0</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <h6>Map Loaded</h6>
                    <h4 id="lbMaps">0</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <h6>Zone Loaded</h6>
                    <h4 id="lbZones">0</h4>
                </div>
            </div>
        </div>

        <?php if ($msg) echo $msg; ?>

        <?php if ($status): ?>
        <div class="mt-3 text-center">
            <small class="fw-semibold">
                Bot đang hoạt động: <b><?= intval($status['bots']) ?></b> &nbsp;|&nbsp;
                Người online: <b><?= intval($status['online']) ?></b> &nbsp;|&nbsp;
                Cập nhật: <?= date('H:i:s d/m/Y', strtotime($status['updated_at'] ?: 'now')) ?>
            </small>
            <?php if (!empty($status['bot_diag'])): ?>
                <div class="mt-1"><small class="text-info">Chẩn đoán: <code id="lbDiag"><?= htmlspecialchars($status['bot_diag']) ?></code></small></div>
            <?php else: ?>
                <div class="mt-1"><small class="text-info">Chẩn đoán: <code id="lbDiag"></code></small></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="alert alert-info mt-3" style="font-size: 0.9rem;">
            <i class="fa fa-info-circle me-1"></i> <b>Lưu ý:</b> Server tự ghi danh sách bot chi tiết vào bảng <code>bot_status</code> mỗi ~3 giây (Tên/Lv/Map-Khu/HP/State/Personality/Need) nên trang này hiển thị được từng bot để quản lý. Bot vẫn tự sinh ra/biến mất theo người chơi và tự xóa sau 3 giờ nên RAM không bị phình.
        </div>

<div class="mt-3">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Triệu hồi bot tự động</h5>
            <form method="POST">
                <input type="hidden" name="action" value="spawn">
                <div class="row g-2 mb-2">
                    <div class="col-12 col-md-4">
                        <label class="fw-semibold">Map</label>
                        <select name="map" class="form-select" required>
                            <?php foreach ($maps as $m): ?>
                                <option value="<?= intval($m['id']) ?>" <?= intval($m['id']) === 0 ? 'selected' : '' ?>>
                                    [<?= intval($m['id']) ?>] <?= htmlspecialchars($m['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Số lượng</label>
                        <input type="number" name="count" class="form-control" value="1" min="1" max="50">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Level</label>
                        <input type="number" name="level" class="form-control" value="10" min="1" max="200">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">HP</label>
                        <input type="number" name="hp" class="form-control" value="20000" min="100">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Sát thương</label>
                        <input type="number" name="damage" class="form-control" value="1500" min="10">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Tốc độ (Speed)</label>
                        <select name="speed" class="form-select">
                            <option value="0">Mặc định</option>
                            <option value="1">1 (Chậm)</option>
                            <option value="2">2 (Bình thường)</option>
                            <option value="3">3 (Nhanh)</option>
                            <option value="4">4 (Rất nhanh)</option>
                            <option value="5">5 (Siêu tốc)</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Triệu hồi</button>
                    <button type="submit" class="btn btn-danger" name="action" value="kill" onclick="return confirm('Xóa toàn bộ bot đang hoạt động?')">Xóa toàn bộ bot</button>
                </div>
            </form>
            <p class="text-muted mt-2 mb-0"><small>Giới hạn tối đa 3 bot / khu vực. Bot tự đánh quái, nhặt đồ, hồi phục và hồi sinh; tự biến mất sau 3 giờ.</small></p>
        </div>
    </div>
</div>

<div class="mt-3">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Cấu hình BOT AI (NRO-style)</h5>
            <form method="POST">
                <input type="hidden" name="action" value="bot_config">
                <div class="row g-2 mb-2">
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Bật BOT</label>
                        <select name="enabled" class="form-select">
                            <option value="1" <?= !empty($botCfg['enabled']) ? 'selected' : '' ?>>Bật</option>
                            <option value="0" <?= empty($botCfg['enabled']) ? 'selected' : '' ?>>Tắt</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Tổng số (population)</label>
                        <input type="number" name="population" class="form-control" value="<?= intval($botCfg['population']) ?>" min="0" max="200">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Bot / khu (1-8)</label>
                        <input type="number" name="bots_per_map" class="form-control" value="<?= intval($botCfg['bots_per_map']) ?>" min="1" max="8">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Nhường quái (px)</label>
                        <input type="number" name="player_protection" class="form-control" value="<?= intval($botCfg['player_protection']) ?>" min="0" max="500">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Chat rate</label>
                        <input type="number" step="0.1" name="chat_rate" class="form-control" value="<?= floatval($botCfg['chat_rate']) ?>" min="0" max="10">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Đổi map rate</label>
                        <input type="number" step="0.1" name="map_change_rate" class="form-control" value="<?= floatval($botCfg['map_change_rate']) ?>" min="0" max="10">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="fw-semibold">Tặng đồ rate</label>
                        <input type="number" step="0.1" name="gift_rate" class="form-control" value="<?= floatval($botCfg['gift_rate']) ?>" min="0" max="10">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">AFK rate</label>
                        <input type="number" step="0.1" name="afk_rate" class="form-control" value="<?= floatval($botCfg['afk_rate']) ?>" min="0" max="10">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Lượng rate</label>
                        <input type="number" step="0.1" name="gold_rate" class="form-control" value="<?= floatval($botCfg['gold_rate']) ?>" min="0" max="10">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">EXP rate (0-1)</label>
                        <input type="number" step="0.1" name="exp_rate" class="form-control" value="<?= floatval($botCfg['exp_rate']) ?>" min="0" max="1">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Bot quanh mỗi người (0-50)</label>
                        <input type="number" name="presence_per_player" class="form-control" value="<?= intval($botCfg['presence_per_player']) ?>" min="0" max="50">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Thời gian bám theo (giây)</label>
                        <input type="number" name="presence_visit_seconds" class="form-control" value="<?= intval($botCfg['presence_visit_seconds']) ?>" min="30" max="3600">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Lưu cấu hình BOT AI</button>
            </form>
            <p class="text-muted mt-2 mb-0"><small>Gửi lệnh <code>BOT_CONFIG</code> qua <code>web_admin_commands</code>, server áp dụng trong vài giây và lưu <code>bot_config.txt</code>. Tắt BOT để chỉ giữ logic farm cũ.</small></p>
        </div>
    </div>
</div>

<?php
$classNames = [1 => 'Kiếm', 2 => 'Tiêu', 3 => 'Kunai', 4 => 'Cung', 5 => 'Đao', 6 => 'Quạt'];
$detail = null;
if (isset($_GET['detail']) && trim(strval($_GET['detail'])) !== '') {
    $dn = trim(strval($_GET['detail']));
    $stmt = $conn->prepare("SELECT `name`, `level`, `map_id`, `zone_id`, `x`, `y`, `hp`, `max_hp`, `state`, `personality`, `top_need`, `gold`, `gender`, `class_id`, `goal`, `damage`, `friends`, `online_min`, `gear`, `needs`, `profile`, `near`, `updated_at` FROM `bot_status` WHERE `name` = ? LIMIT 1");
    $stmt->bind_param("s", $dn);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $detail = $res->fetch_assoc();
    }
    $stmt->close();
}
?>
<?php if ($detail): ?>
<div class="mt-4">
    <div class="card">
        <div class="card-body">
            <h5 class="fw-bold">Chi tiết BOT: <?= htmlspecialchars($detail['name']) ?></h5>
            <div class="row g-2 mb-2">
                <div class="col-6 col-md-3"><b>Level:</b> <?= intval($detail['level']) ?></div>
                <div class="col-6 col-md-3"><b>Phái:</b> <?= intval($detail['gender']) == 1 ? 'Nam' : 'Nữ' ?></div>
                <div class="col-6 col-md-3"><b>Class:</b> <?= htmlspecialchars($classNames[intval($detail['class_id'])] ?? ('C' . intval($detail['class_id']))) ?></div>
                <div class="col-6 col-md-3"><b>Map/Khu:</b> <?= intval($detail['map_id']) ?>/<?= intval($detail['zone_id']) ?> (<?= intval($detail['x']) ?>,<?= intval($detail['y']) ?>)</div>
                <div class="col-6 col-md-3"><b>HP:</b> <?= number_format(intval($detail['hp'])) ?>/<?= number_format(intval($detail['max_hp'])) ?></div>
                <div class="col-6 col-md-3"><b>Dame:</b> <?= number_format(intval($detail['damage'])) ?></div>
                <div class="col-6 col-md-3"><b>Lượng:</b> <?= number_format(intval($detail['gold'])) ?></div>
                <div class="col-6 col-md-3"><b>State:</b> <?= htmlspecialchars($detail['state']) ?></div>
                <div class="col-6 col-md-3"><b>Mục tiêu:</b> <?= htmlspecialchars($detail['goal']) ?></div>
                <div class="col-6 col-md-3"><b>Need:</b> <?= htmlspecialchars($detail['top_need']) ?></div>
                <div class="col-6 col-md-3"><b>Bạn:</b> <?= intval($detail['friends']) ?></div>
                <div class="col-6 col-md-3"><b>Online:</b> <?= intval($detail['online_min']) ?> phút</div>
                <div class="col-12"><b>Personality:</b> <small><?= htmlspecialchars(strval($detail['personality'])) ?></small></div>
                <div class="col-12"><b>Gần nhất:</b> <?= htmlspecialchars(strval($detail['near'] ?? '')) ?: '<i>không có người chơi quanh</i>' ?></div>
                <?php
                $needsArr = json_decode(strval($detail['needs'] ?? ''), true);
                $profArr = json_decode(strval($detail['profile'] ?? ''), true);
                ?>
                <?php if (is_array($needsArr) && count($needsArr)): ?>
                <div class="col-12"><b>Needs:</b> <small><?= htmlspecialchars(implode(' | ', array_map(fn($k, $v) => "$k=$v", array_keys($needsArr), $needsArr))) ?></small></div>
                <?php endif; ?>
                <?php if (is_array($profArr) && count($profArr)): ?>
                <div class="col-12"><b>Chỉ số AI:</b> <small><?= htmlspecialchars(implode(' | ', array_map(fn($k, $v) => "$k=$v", array_keys($profArr), $profArr))) ?></small></div>
                <?php endif; ?>
            </div>
            <?php
            $gear = json_decode(strval($detail['gear'] ?? ''), true);
            $geq = (is_array($gear) && isset($gear['eq']) && is_array($gear['eq'])) ? $gear['eq'] : [];
            $gbag = (is_array($gear) && isset($gear['bag']) && is_array($gear['bag'])) ? $gear['bag'] : [];
            ?>
            <?php if (count($geq) || count($gbag)): ?>
            <h6 class="fw-bold mt-2"><i class="fa-solid fa-shield-halved text-primary"></i> Trang bị</h6>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <?php foreach ($geq as $e): ?>
                    <div class="border rounded p-1 text-center" style="width:100px;background:#fff">
                        <?= botItemImg(intval($e['id'] ?? 0), 44) ?>
                        <div class="small" style="font-size:10px"><?= htmlspecialchars(strval($e['name'] ?? ('#' . intval($e['id'] ?? 0)))) ?></div>
                        <span class="badge bg-info" style="font-size:9px"><?= htmlspecialchars(strval($e['slot_name'] ?? ('Ô ' . intval($e['slot'] ?? 0)))) ?><?= (intval($e['upg'] ?? 0) > 0) ? ' +' . intval($e['upg']) : '' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($gbag)): ?>
            <h6 class="fw-bold"><i class="fa-solid fa-bag-shopping text-success"></i> Túi đồ</h6>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <?php foreach ($gbag as $b): ?>
                    <div class="border rounded p-1 text-center" style="width:100px;background:#fff">
                        <?= botItemImg(intval($b['id'] ?? 0), 44) ?>
                        <div class="small" style="font-size:10px"><?= htmlspecialchars(strval($b['name'] ?? ('#' . intval($b['id'] ?? 0)))) ?></div>
                        <span class="badge bg-primary" style="font-size:9px">x<?= intval($b['qty'] ?? 1) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <h6 class="fw-bold mt-2">Thao tác nhanh (mẫu NRO)</h6>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="action" value="bot_teleport">
                    <input type="hidden" name="bot_name" value="<?= htmlspecialchars($detail['name']) ?>">
                    <input type="text" name="target" class="form-control" placeholder="Tên người chơi..." required>
                    <button type="submit" class="btn btn-warning">Dịch chuyển tới</button>
                </form>
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="action" value="bot_gold">
                    <input type="hidden" name="bot_name" value="<?= htmlspecialchars($detail['name']) ?>">
                    <input type="number" name="amount" class="form-control" placeholder="Lượng..." required>
                    <button type="submit" class="btn btn-warning">Cộng vàng</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="bot_regear">
                    <input type="hidden" name="bot_name" value="<?= htmlspecialchars($detail['name']) ?>">
                    <button type="submit" class="btn btn-warning">Mặc lại đồ</button>
                </form>
            </div>
            <h6 class="fw-bold">Chỉnh sửa BOT đang chạy</h6>
            <form method="POST">
                <input type="hidden" name="action" value="bot_edit">
                <input type="hidden" name="bot_name" value="<?= htmlspecialchars($detail['name']) ?>">
                <div class="row g-2 mb-2">
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Level (1-200)</label>
                        <input type="number" name="level" class="form-control" value="<?= intval($detail['level']) ?>" min="1" max="200">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">HP tối đa</label>
                        <input type="number" name="hp" class="form-control" value="<?= intval($detail['max_hp']) ?>" min="100">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="fw-semibold">Sát thương</label>
                        <input type="number" name="damage" class="form-control" value="<?= intval($detail['damage']) ?>" min="10">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Lưu chỉnh sửa</button>
                <a class="btn btn-secondary" href="/admin/bot">Đóng</a>
            </form>
            <p class="text-muted mt-2 mb-0"><small>Gửi lệnh <code>BOT_EDIT</code>, server tính lại chỉ số ngay. Level vượt người mạnh nhất sẽ bị đồng bộ lại về đúng tầm.</small></p>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="mt-4">
    <h5 class="fw-bold">Quản lý thông tin BOT (<span id="botCount">0</span> đang chạy)</h5>
    <div class="table-responsive" style="border-radius: 1rem;">
        <table class="table text-white fw-semibold mb-0" role="table">
            <thead>
                <tr class="text-start fw-bold text-uppercase gs-0">
                    <th>Tên</th>
                    <th>Lv</th>
                    <th>Phái/Class</th>
                    <th>Map/Khu</th>
                    <th>HP</th>
                    <th>Lượng</th>
                    <th>State</th>
                    <th>Mục tiêu</th>
                    <th>Need</th>
                    <th>Bạn</th>
                    <th>Online</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="botTableBody">
            </tbody>
        </table>
    </div>
    <?php if (count($bots) == 0): ?>
    <div class="text-center"><small class="fw-semibold">Chưa có bot nào (bảng cập nhật mỗi ~2 giây khi server chạy).</small></div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <h5 class="fw-bold">Câu chat tùy chỉnh (<?= count($chatLines) ?> câu)</h5>
    <form method="POST" class="d-flex gap-2 mb-2">
        <input type="hidden" name="action" value="chat_add">
        <input type="text" name="chat_line" class="form-control" maxlength="120" placeholder="Nhập câu chat mới cho BOT..." required>
        <button type="submit" class="btn btn-success">Thêm</button>
    </form>
    <?php if (count($chatLines) > 0): ?>
        <ul class="list-group">
            <?php foreach ($chatLines as $ri => $ln): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($ln) ?></span>
                    <form method="POST" onsubmit="return confirm('Xóa câu này?')">
                        <input type="hidden" name="action" value="chat_del">
                        <input type="hidden" name="chat_idx" value="<?= intval($ri) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="text-center"><small class="fw-semibold">Chưa có câu tùy chỉnh (BOT dùng câu mặc định).</small></div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <h5 class="fw-bold">Lịch sử lệnh bot</h5>
    <?php if (count($history) > 0): ?>
        <div class="table-responsive" style="border-radius: 1rem;">
            <table class="table text-white fw-semibold mb-0" role="table">
                <thead>
                    <tr class="text-start fw-bold text-uppercase gs-0">
                        <th>ID</th>
                        <th>Lệnh</th>
                        <th>Dữ liệu</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= $h['id'] ?></td>
                            <td><?= htmlspecialchars($h['command']) ?></td>
                            <td><?= htmlspecialchars($h['data']) ?></td>
                            <td><?= intval($h['status']) === 0 ? '<b class="text-warning">Chờ xử lý</b>' : '<b class="text-success">Đã xử lý</b>' ?></td>
                            <td><?= date('H:i d/m/Y', strtotime($h['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
</tbody>
        </table>
    </div>
<?php else: ?>
    <div class="text-center"><small class="fw-semibold">Chưa có lệnh bot nào.</small></div>
<?php endif; ?>
</div>

</div>

<script>
    function setText(id, v) { var el = document.getElementById(id); if (el) el.innerText = v; }
    function fmt(n) { n = parseInt(n) || 0; return n.toLocaleString('vi-VN'); }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }

    // Đồng hồ thời gian thực
    setInterval(function () { setText('lbTime', new Date().toLocaleTimeString('en-GB')); }, 1000);

    // Theo dõi thời gian thực: gọi endpoint PHP đọc bot_status + server_status (Java ghi mỗi ~3s)
    function refreshBot() {
        fetch('/apixuli/bot-status', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d || d.status === 'error') return;
                setText('lbOnline', d.online);
                setText('lbOnlineUsers', d.online);
                setText('lbBots', d.bots);
                setText('lbMaps', d.maps);
                setText('lbZones', d.zones);
                setText('botCount', d.bots);
                var bar = document.getElementById('barBots');
                if (bar) bar.style.width = (d.pop > 0 ? Math.min(100, (d.bots / d.pop) * 100) : 0) + '%';
                var diag = document.getElementById('lbDiag');
                if (diag) diag.innerText = d.bot_diag || '';

                var tbody = document.getElementById('botTableBody');
                if (tbody) {
                    var list = Array.isArray(d.list) ? d.list : [];
                    var html = '';
                    list.forEach(function (b) {
                        html += '<tr title="' + esc(b.personality) + '">'
                            + '<td>' + esc(b.name) + '</td>'
                            + '<td>' + (b.level | 0) + '</td>'
                            + '<td>' + (b.gender == 1 ? 'Nam' : 'Nữ') + '/C' + (b.class_id | 0) + '</td>'
                            + '<td>' + (b.map_id | 0) + '/' + (b.zone_id | 0) + '</td>'
                            + '<td>' + fmt(b.hp) + '/' + fmt(b.max_hp) + '</td>'
                            + '<td>' + fmt(b.gold) + '</td>'
                            + '<td>' + esc(b.state) + '</td>'
                            + '<td>' + esc(b.goal) + '</td>'
                            + '<td>' + esc(b.top_need) + '</td>'
                            + '<td>' + (b.friends | 0) + '</td>'
                            + '<td>' + (b.online_min | 0) + 'p</td>'
                            + '<td><a class="btn btn-sm btn-info" href="/admin/bot?detail=' + encodeURIComponent(b.name) + '">Chi tiết</a></td>'
                            + '<td><form method="POST" onsubmit="return confirm(\'Xóa bot ' + esc(b.name) + '?\')">'
                            + '<input type="hidden" name="action" value="kill_one">'
                            + '<input type="hidden" name="bot_name" value="' + esc(b.name) + '">'
                            + '<button type="submit" class="btn btn-sm btn-danger">Xóa</button></form></td>'
                            + '</tr>';
                    });
                    tbody.innerHTML = html || '<tr><td colspan="13" class="text-center text-muted">Chưa có bot nào.</td></tr>';
                }
            })
            .catch(function () { /* server offline */ });
    }

    setInterval(refreshBot, 2000);
    refreshBot();
</script>

<?php
$conn->close();
?>
