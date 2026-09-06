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
$result = $conn->query("SELECT `name`, `level`, `map_id`, `zone_id`, `x`, `y`, `hp`, `max_hp`, `state`, `personality`, `top_need`, `updated_at` FROM `bot_status` ORDER BY `level` DESC, `name` ASC LIMIT 200");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bots[] = $row;
    }
}

$history = [];
$result = $conn->query("SELECT `id`, `command`, `target_user`, `data`, `status`, `created_at` FROM `web_admin_commands` WHERE `command` IN ('SPAWN_BOT','KILL_BOT','KILL_ONE_BOT','BOT_CONFIG') ORDER BY `id` DESC LIMIT 30");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}
$conn->close();
?>
<div class="bg-content" style="border-radius: 1rem; padding:10px">
    <div style="text-align:center;">
        <h4>Quản lý Bot AI</h4>
    </div>
    <div class="container mb-2">
        <div class="row text-center justify-content-center g-2 mt-1">
            <div class="col-12 col-md-4 col-lg-3">
                <a class="btn btn-success w-100 fw-semibold" href="/admin/home">Quay lại</a>
            </div>
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
    </div>
<?php endif; ?>

<div class="alert alert-info mt-3" style="font-size: 0.9rem;">
    <i class="fa fa-info-circle me-1"></i> <b>Lưu ý:</b> Hệ thống <b>không lưu danh sách bot chi tiết</b> để tối ưu hóa bộ nhớ RAM cho server (do bot tự động sinh ra và biến mất liên tục khi có/không có người chơi thật). Trang Admin chỉ hiển thị tổng số lượng bot đang chạy và lịch sử các lệnh đã gọi.
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
                        <label class="fw-semibold">Vàng rate</label>
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

<div class="mt-4">
    <h5 class="fw-bold">Quản lý thông tin BOT (<?= count($bots) ?> đang chạy)</h5>
    <?php if (count($bots) > 0): ?>
        <div class="table-responsive" style="border-radius: 1rem;">
            <table class="table text-white fw-semibold mb-0" role="table">
                <thead>
                    <tr class="text-start fw-bold text-uppercase gs-0">
                        <th>Tên</th>
                        <th>Lv</th>
                        <th>Map/Khu</th>
                        <th>HP</th>
                        <th>State</th>
                        <th>Personality</th>
                        <th>Need</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bots as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['name']) ?></td>
                            <td><?= intval($b['level']) ?></td>
                            <td><?= intval($b['map_id']) ?>/<?= intval($b['zone_id']) ?></td>
                            <td><?= number_format(intval($b['hp'])) ?>/<?= number_format(intval($b['max_hp'])) ?></td>
                            <td><?= htmlspecialchars($b['state']) ?></td>
                            <td><small><?= htmlspecialchars(mb_strimwidth(strval($b['personality']), 0, 40, '...')) ?></small></td>
                            <td><?= htmlspecialchars($b['top_need']) ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Xóa bot <?= htmlspecialchars($b['name']) ?>?')">
                                    <input type="hidden" name="action" value="kill_one">
                                    <input type="hidden" name="bot_name" value="<?= htmlspecialchars($b['name']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center"><small class="fw-semibold">Chưa có bot nào (bảng cập nhật mỗi ~3 giây khi server chạy).</small></div>
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
