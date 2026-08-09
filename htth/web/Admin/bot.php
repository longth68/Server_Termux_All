<?php
ini_set('default_charset', 'UTF-8');
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
$_Title = "Quản lý Bot AI";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$msg = "";

$conn->query("UPDATE players SET it_body = '[]' WHERE it_body = '[[],[],[],[],[],[],[],[]]' 
    AND name IN (SELECT REPLACE(REPLACE(REPLACE(`char`, '[', ''), ']', ''), '\"', '') FROM accounts WHERE note = 'BOT')");

$conn->query("CREATE TABLE IF NOT EXISTS bot_map_config (
    map_id INT NOT NULL PRIMARY KEY,
    target_bot INT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS bot_ai_config (
    id INT NOT NULL PRIMARY KEY DEFAULT 1,
    chat_enabled TINYINT NOT NULL DEFAULT 1,
    market_enabled TINYINT NOT NULL DEFAULT 1,
    trade_enabled TINYINT NOT NULL DEFAULT 1,
    friend_enabled TINYINT NOT NULL DEFAULT 1,
    party_enabled TINYINT NOT NULL DEFAULT 1,
    shipping_enabled TINYINT NOT NULL DEFAULT 1,
    dungeon_help_enabled TINYINT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$conn->query("INSERT IGNORE INTO bot_ai_config (id) VALUES (1)");

function firstBotCharacter($charJson) {
    $chars = json_decode($charJson ?? '[]', true);
    if (!is_array($chars) || empty($chars[0])) {
        return null;
    }
    return $chars[0];
}

function defaultBotSkills($clazz) {
    $skills = [
        1 => '[[0,0,0,0],[20,-1,0,0],[40,-1,0,0],[375,-1,0,0],[487,-1,0,0],[300,-1,0,0],[305,-1,0,0],[310,-1,0,0],[315,-1,0,0],[320,-1,0,0],[325,-1,0,0],[552,-1,0,0],[557,-1,0,0]]',
        2 => '[[60,0,0,0],[80,-1,0,0],[100,-1,0,0],[395,-1,0,0],[492,-1,0,0],[300,-1,0,0],[305,-1,0,0],[310,-1,0,0],[315,-1,0,0],[320,-1,0,0],[325,-1,0,0],[552,-1,0,0],[557,-1,0,0]]',
        3 => '[[120,0,0,0],[140,-1,0,0],[160,-1,0,0],[415,-1,0,0],[497,-1,0,0],[300,-1,0,0],[305,-1,0,0],[310,-1,0,0],[315,-1,0,0],[320,-1,0,0],[325,-1,0,0],[552,-1,0,0],[557,-1,0,0]]',
        4 => '[[180,0,0,0],[200,-1,0,0],[220,-1,0,0],[435,-1,0,0],[502,-1,0,0],[300,-1,0,0],[305,-1,0,0],[310,-1,0,0],[315,-1,0,0],[320,-1,0,0],[325,-1,0,0],[552,-1,0,0],[557,-1,0,0]]',
        5 => '[[240,0,0,0],[260,-1,0,0],[280,-1,0,0],[455,-1,0,0],[507,-1,0,0],[300,-1,0,0],[305,-1,0,0],[310,-1,0,0],[315,-1,0,0],[320,-1,0,0],[325,-1,0,0],[552,-1,0,0],[557,-1,0,0]]',
    ];
    return $skills[$clazz] ?? $skills[1];
}

function defaultBotBodyWear($clazz) {
    $wear = [
        1 => '[[0,0,1,-1,0,0,0,-1,[[1,61],[4,7]],[],0,[],0],[40,0,1,-1,0,0,0,-1,[[3,4],[15,1]],[],0,[],3],[80,0,1,-1,0,0,0,-1,[[3,3],[15,3]],[],0,[],5]]',
        2 => '[[8,0,1,-1,0,1,0,-1,[[1,72]],[],0,[],0],[48,0,1,-1,0,1,0,-1,[[3,2],[15,3]],[],0,[],3],[88,0,1,-1,0,1,0,-1,[[3,1],[15,5]],[],0,[],5]]',
        3 => '[[16,0,1,-1,0,1,0,-1,[[1,73]],[],0,[],0],[56,0,1,-1,0,0,0,-1,[[3,3],[15,2]],[],0,[],3],[96,0,1,-1,0,0,0,-1,[[3,3],[15,4]],[],0,[],5]]',
        4 => '[[24,0,1,-1,0,1,0,-1,[[1,55],[23,18]],[],0,[],0],[64,0,1,-1,0,0,0,-1,[[3,2],[15,3]],[],0,[],3],[104,0,1,-1,0,1,0,-1,[[3,1],[15,5]],[],0,[],5]]',
        5 => '[[32,0,1,-1,0,1,0,-1,[[1,66],[16,1]],[],0,[],0],[72,0,1,-1,0,0,0,-1,[[3,3],[15,2]],[],0,[],3],[112,0,1,-1,0,0,0,-1,[[3,2],[15,4]],[],0,[],5]]',
    ];
    return $wear[$clazz] ?? $wear[1];
}

function defaultBotFashion($clazz) {
    $fashion = [
        1 => '[[[103,5,1,1],[108,0,0,1]],[],[[1,1],[5,1],[3,1],[7,1]]]',
        2 => '[[[103,1,24,1],[108,0,0,1]],[],[[1,1],[5,1],[3,1],[7,1]]]',
        3 => '[[[103,2,28,1],[108,0,0,1]],[],[[1,1],[5,1],[3,1],[7,1]]]',
        4 => '[[[103,3,32,1],[108,0,0,1]],[],[[1,1],[5,1],[3,1],[7,1]]]',
        5 => '[[[103,4,36,1],[108,0,0,1]],[],[[1,1],[5,1],[3,1],[7,1]]]',
    ];
    return $fashion[$clazz] ?? $fashion[1];
}

function defaultPlayerData($name, $clazz) {
    $now = date('Y-m-d') . 'T00:00:00.001+07:00';
    return [
        'name' => $name,
        'body' => '[0,36]',
        'date' => $now,
        'quest' => '[[250,[]]]',
        'level' => '[1,0,0]',
        'exp' => 0,
        'site' => '[1,0,0,1,100,0,1,0,0,' . time() . '000,0,' . time() . '000,0,' . time() . '000,1]',
        'clazz' => $clazz,
        'point_inven' => '[1000000,0,0,0,0,0,0,0,0,0,0,0]',
        'bag3' => '[]',
        'box3' => '[]',
        'it_body' => defaultBotBodyWear($clazz),
        'bag47' => '[]',
        'box47' => '[]',
        'save_it3' => '[]',
        'save_it47' => '[]',
        'hanhtrinh' => '[]',
        'potential' => '[0,1,1,1,1,1,0,[]]',
        'skill' => defaultBotSkills($clazz),
        'rms' => '[]',
        'friend' => '[]',
        'enemy' => '[]',
        'eff' => '[]',
        'fashion' => defaultBotFashion($clazz),
        'pvppoint' => 0,
        'wanted_point' => 0,
        'wanted_chest' => '[[0,0,0,0],[1,0,0,0]]',
        'mypet' => '[]',
        'diemdanh' => 0,
        'diemdanhvip' => 0,
        'lucthuc' => '[0,1,1,1,1,99999]',
        'coin' => 0,
        'chuyensinh' => 0,
    ];
}

function loadBotAccounts($conn) {
    $items = [];
    try {
        $rs = $conn->query("SELECT id, user, `char`, onl FROM accounts WHERE note = 'BOT' ORDER BY id DESC LIMIT 200");
        $playerStmt = $conn->prepare("SELECT clazz, level FROM players WHERE name = ? LIMIT 1");
        while ($account = $rs->fetch(PDO::FETCH_ASSOC)) {
            $charName = firstBotCharacter($account['char']);
            $account['char_name'] = $charName ?: '';
            $account['clazz'] = null;
            $account['level'] = null;
            $account['has_player'] = 0;
            if ($charName) {
                $playerStmt->execute([$charName]);
                $player = $playerStmt->fetch(PDO::FETCH_ASSOC);
                if ($player) {
                    $account['clazz'] = $player['clazz'];
                    $account['level'] = $player['level'];
                    $account['has_player'] = 1;
                }
            }
            $items[] = $account;
        }
    } catch (Exception $e) {}
    return $items;
}

$botNamePool = [
    'LuffyKing', 'ZoroSword', 'NamiThief', 'UsoppGod', 'SanjiCook',
    'ChopperDr', 'RobinArc', 'FrankyShip', 'BrookSoul', 'JinbeSea',
    'AceFire', 'SaboRev', 'KoalaKid', 'HancockBoa', 'ShankRed',
    'HaiTacVN', 'VuaHaiTac', 'DaoDangCap', 'TuanBienCa', 'ChienSi01',
    'TanThu001', 'QuaiVatSan', 'MayMuon99', 'XuHuong01', 'AnhHung88',
];

$maps = [];
try {
    $maps = $conn->query("SELECT id, name FROM maps ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$mapBotConfig = [];
try {
    $cfgRows = $conn->query("SELECT map_id, target_bot FROM bot_map_config")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cfgRows as $row) {
        $mapBotConfig[intval($row['map_id'])] = intval($row['target_bot']);
    }
} catch (Exception $e) {}

$botAiConfig = [
    'chat_enabled' => 1,
    'market_enabled' => 1,
    'trade_enabled' => 1,
    'friend_enabled' => 1,
    'party_enabled' => 1,
    'shipping_enabled' => 1,
    'dungeon_help_enabled' => 1,
];
try {
    $cfg = $conn->query("SELECT * FROM bot_ai_config WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($cfg) {
        foreach ($botAiConfig as $key => $val) {
            $botAiConfig[$key] = intval($cfg[$key] ?? $val);
        }
    }
} catch (Exception $e) {}

$bot_accounts = loadBotAccounts($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create_accounts') {
        $count = max(1, min(20, intval($_POST['count'] ?? 1)));
        $clazzInput = intval($_POST['clazz'] ?? 0);
        $prefix = trim($_POST['prefix'] ?? '');
        $created = 0;
        $skipped = 0;

        for ($i = 0; $i < $count; $i++) {
            $base = $prefix ?: $botNamePool[array_rand($botNamePool)];
            $botName = $base . '_' . rand(10, 9999);
            $botPass = md5('bot_' . $botName . '_htth');
            $clazz = ($clazzInput >= 1 && $clazzInput <= 5) ? $clazzInput : rand(1, 5);
            try {
                $stmt = $conn->prepare("INSERT IGNORE INTO accounts (user, pass, `char`, onl, `lock`, note, status, coin, vip)
                    VALUES (?, ?, ?, 0, 0, 'BOT', 0, 0, 0)");
                $stmt->execute([$botName, $botPass, json_encode([$botName])]);
                if ($stmt->rowCount() == 0) {
                    $skipped++;
                    continue;
                }
                $pd = defaultPlayerData($botName, $clazz);
                $stmt2 = $conn->prepare("INSERT IGNORE INTO players
                    (name,body,date,quest,level,exp,site,clazz,point_inven,bag3,box3,it_body,
                     bag47,box47,save_it3,save_it47,hanhtrinh,potential,skill,rms,friend,enemy,
                     eff,fashion,pvppoint,wanted_point,wanted_chest,mypet,diemdanh,diemdanhvip,
                     lucthuc,coin,chuyensinh)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt2->execute(array_values($pd));
                $created++;
            } catch (Exception $e) {
                $skipped++;
            }
        }
        $msg = "<div class='alert-success'>Đã tạo <b>$created</b> tài khoản bot. Bỏ qua: <b>$skipped</b>.</div>";
    } elseif ($action === 'ensure_bot_count') {
        $desired = max(1, min(5000, intval($_POST['desired_total'] ?? 100)));
        $current = intval($conn->query("SELECT COUNT(*) FROM accounts WHERE note = 'BOT'")->fetchColumn());
        $need = max(0, $desired - $current);
        $created = 0;
        $skipped = 0;
        for ($i = 0; $i < $need; $i++) {
            $base = $botNamePool[array_rand($botNamePool)];
            $botName = $base . '_' . rand(1000, 999999);
            $botPass = md5('bot_' . $botName . '_htth');
            $clazz = rand(1, 5);
            try {
                $stmt = $conn->prepare("INSERT IGNORE INTO accounts (user, pass, `char`, onl, `lock`, note, status, coin, vip)
                    VALUES (?, ?, ?, 0, 0, 'BOT', 0, 0, 0)");
                $stmt->execute([$botName, $botPass, json_encode([$botName])]);
                if ($stmt->rowCount() == 0) {
                    $skipped++;
                    continue;
                }
                $pd = defaultPlayerData($botName, $clazz);
                $stmt2 = $conn->prepare("INSERT IGNORE INTO players
                    (name,body,date,quest,level,exp,site,clazz,point_inven,bag3,box3,it_body,
                     bag47,box47,save_it3,save_it47,hanhtrinh,potential,skill,rms,friend,enemy,
                     eff,fashion,pvppoint,wanted_point,wanted_chest,mypet,diemdanh,diemdanhvip,
                     lucthuc,coin,chuyensinh)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt2->execute(array_values($pd));
                $created++;
            } catch (Exception $e) {
                $skipped++;
            }
        }
        $conn->query("INSERT INTO web_admin_commands (command, data, status) VALUES ('RELOAD_BOT', '{}', 0)");
        $msg = "<div class='alert-success'>Bot hiện có: <b>$current</b>. Đã tạo bù: <b>$created</b>. Bỏ qua: <b>$skipped</b>.</div>";
    } elseif ($action === 'spawn') {
        $botName = trim($_POST['bot_name'] ?? '');
        $mapId = intval($_POST['map_id'] ?? 1);
        if ($botName !== '') {
            $check = $conn->prepare("SELECT COUNT(*) FROM players WHERE name = ?");
            $check->execute([$botName]);
            if ($check->fetchColumn() <= 0) {
                $msg = "<div class='alert-danger'>Bot <b>" . htmlspecialchars($botName, ENT_QUOTES, 'UTF-8') . "</b> chưa có nhân vật trong bảng players.</div>";
            } else {
                $data = json_encode(['name' => $botName, 'map' => $mapId, 'from_db' => true]);
                $stmt = $conn->prepare("INSERT INTO web_admin_commands (command, data, status) VALUES ('SPAWN_BOT', ?, 0)");
                $stmt->execute([$data]);
                $msg = "<div class='alert-success'>Đã gửi lệnh thả bot <b>" . htmlspecialchars($botName, ENT_QUOTES, 'UTF-8') . "</b> vào map $mapId.</div>";
            }
        }
    } elseif ($action === 'killall') {
        $conn->query("INSERT INTO web_admin_commands (command, data, status) VALUES ('KILL_BOT', '{}', 0)");
        $msg = "<div class='alert-danger'>Đã gửi lệnh dọn toàn bộ bot khỏi game.</div>";
    } elseif ($action === 'config') {
        $max = intval($_POST['max_bot'] ?? 5);
        $min = intval($_POST['min_bot'] ?? 0);
        $total = intval($_POST['max_total'] ?? 1000);
        $data = json_encode(['max' => $max, 'min' => $min, 'total' => $total]);
        $stmt = $conn->prepare("INSERT INTO web_admin_commands (command, data, status) VALUES ('CONFIG_BOT', ?, 0)");
        $stmt->execute([$data]);
        $msg = "<div class='alert-success'>Đã lưu cấu hình bot tự động.</div>";
    } elseif ($action === 'save_map_bot') {
        $mapId = intval($_POST['map_id'] ?? 0);
        $target = max(0, min(50, intval($_POST['target_bot'] ?? 1)));
        if ($mapId > 0) {
            $stmt = $conn->prepare("INSERT INTO bot_map_config (map_id, target_bot) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE target_bot = VALUES(target_bot)");
            $stmt->execute([$mapId, $target]);
            $cmd = $conn->prepare("INSERT INTO web_admin_commands (command, data, status) VALUES ('CONFIG_BOT_MAP', ?, 0)");
            $cmd->execute([json_encode(['map' => $mapId, 'target' => $target])]);
            $mapBotConfig[$mapId] = $target;
            $msg = "<div class='alert-success'>Đã cập nhật map $mapId: <b>$target</b> bot.</div>";
        }
    } elseif ($action === 'save_all_map_bot') {
        $target = max(0, min(50, intval($_POST['target_all'] ?? 1)));
        $stmt = $conn->prepare("INSERT INTO bot_map_config (map_id, target_bot) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE target_bot = VALUES(target_bot)");
        $cmd = $conn->prepare("INSERT INTO web_admin_commands (command, data, status) VALUES ('CONFIG_BOT_MAP', ?, 0)");
        $countMaps = 0;
        foreach ($maps as $m) {
            $mapId = intval($m['id']);
            $stmt->execute([$mapId, $target]);
            $cmd->execute([json_encode(['map' => $mapId, 'target' => $target])]);
            $mapBotConfig[$mapId] = $target;
            $countMaps++;
        }
        $msg = "<div class='alert-success'>Đã đặt <b>$target</b> bot cho <b>$countMaps</b> map.</div>";
    } elseif ($action === 'save_ai_features') {
        $chat = isset($_POST['chat_enabled']) ? 1 : 0;
        $market = isset($_POST['market_enabled']) ? 1 : 0;
        $trade = isset($_POST['trade_enabled']) ? 1 : 0;
        $friend = isset($_POST['friend_enabled']) ? 1 : 0;
        $party = isset($_POST['party_enabled']) ? 1 : 0;
        $shipping = isset($_POST['shipping_enabled']) ? 1 : 0;
        $dungeon = isset($_POST['dungeon_help_enabled']) ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO bot_ai_config
            (id, chat_enabled, market_enabled, trade_enabled, friend_enabled, party_enabled, shipping_enabled, dungeon_help_enabled)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                chat_enabled = VALUES(chat_enabled),
                market_enabled = VALUES(market_enabled),
                trade_enabled = VALUES(trade_enabled),
                friend_enabled = VALUES(friend_enabled),
                party_enabled = VALUES(party_enabled),
                shipping_enabled = VALUES(shipping_enabled),
                dungeon_help_enabled = VALUES(dungeon_help_enabled)");
        $stmt->execute([$chat, $market, $trade, $friend, $party, $shipping, $dungeon]);
        $data = json_encode(['chat' => $chat, 'market' => $market, 'trade' => $trade, 'friend' => $friend, 'party' => $party, 'shipping' => $shipping, 'dungeon' => $dungeon]);
        $cmd = $conn->prepare("INSERT INTO web_admin_commands (command, data, status) VALUES ('CONFIG_BOT_AI', ?, 0)");
        $cmd->execute([$data]);
        $botAiConfig = [
            'chat_enabled' => $chat,
            'market_enabled' => $market,
            'trade_enabled' => $trade,
            'friend_enabled' => $friend,
            'party_enabled' => $party,
            'shipping_enabled' => $shipping,
            'dungeon_help_enabled' => $dungeon,
        ];
        $msg = "<div class='alert-success'>Đã cập nhật tính năng AI bot và gửi lệnh cho server đang chạy.</div>";
    } elseif ($action === 'repair_bots') {
        $fixed = 0;
        $createdPlayers = 0;
        $accounts = $conn->query("SELECT id, user, `char` FROM accounts WHERE note = 'BOT'")->fetchAll(PDO::FETCH_ASSOC);
        $findPlayer = $conn->prepare("SELECT name, clazz, body, potential, skill, lucthuc, it_body, fashion FROM players WHERE name = ? LIMIT 1");
        $updateChar = $conn->prepare("UPDATE accounts SET `char` = ? WHERE id = ?");
        $updatePlayer = $conn->prepare("UPDATE players SET clazz = ?, body = ?, potential = ?, skill = ?, lucthuc = ?, it_body = ?, fashion = ? WHERE name = ?");
        $insertPlayer = $conn->prepare("INSERT IGNORE INTO players
            (name,body,date,quest,level,exp,site,clazz,point_inven,bag3,box3,it_body,
             bag47,box47,save_it3,save_it47,hanhtrinh,potential,skill,rms,friend,enemy,
             eff,fashion,pvppoint,wanted_point,wanted_chest,mypet,diemdanh,diemdanhvip,
             lucthuc,coin,chuyensinh)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        foreach ($accounts as $acc) {
            $charName = firstBotCharacter($acc['char']);
            if (!$charName) {
                $charName = $acc['user'];
                $updateChar->execute([json_encode([$charName]), $acc['id']]);
                $fixed++;
            }
            $findPlayer->execute([$charName]);
            $player = $findPlayer->fetch(PDO::FETCH_ASSOC);
            if (!$player) {
                $clazz = rand(1, 5);
                $insertPlayer->execute(array_values(defaultPlayerData($charName, $clazz)));
                $createdPlayers++;
                continue;
            }
            $clazz = intval($player['clazz']);
            if ($clazz < 1 || $clazz > 5) {
                $clazz = rand(1, 5);
            }
            $body = json_decode($player['body'] ?? '', true);
            if (!is_array($body) || count($body) < 2) {
                $player['body'] = '[0,36]';
            }
            $potential = json_decode($player['potential'] ?? '', true);
            if (!is_array($potential) || count($potential) < 8) {
                $player['potential'] = '[0,1,1,1,1,1,0,[]]';
            }
            $skill = json_decode($player['skill'] ?? '', true);
            if (!is_array($skill) || empty($skill)) {
                $player['skill'] = defaultBotSkills($clazz);
            }
            $lucthuc = json_decode($player['lucthuc'] ?? '', true);
            if (!is_array($lucthuc) || count($lucthuc) < 6) {
                $player['lucthuc'] = '[0,1,1,1,1,99999]';
            }
            $itBody = json_decode($player['it_body'] ?? '', true);
            if (!is_array($itBody) || empty($itBody) || ($player['it_body'] ?? '') === '[[],[],[],[],[],[],[],[]]') {
                $player['it_body'] = defaultBotBodyWear($clazz);
            }
            $fashion = json_decode($player['fashion'] ?? '', true);
            if (!is_array($fashion) || count($fashion) < 3) {
                $player['fashion'] = defaultBotFashion($clazz);
            }
            $updatePlayer->execute([$clazz, $player['body'], $player['potential'], $player['skill'], $player['lucthuc'], $player['it_body'], $player['fashion'], $charName]);
            $fixed++;
        }
        $conn->query("INSERT INTO web_admin_commands (command, data, status) VALUES ('RELOAD_BOT', '{}', 0)");
        $msg = "<div class='alert-success'>Đã sửa dữ liệu bot. Cập nhật: <b>$fixed</b>, tạo nhân vật thiếu: <b>$createdPlayers</b>.</div>";
    } elseif ($action === 'reload_bots') {
        $conn->query("INSERT INTO web_admin_commands (command, data, status) VALUES ('RELOAD_BOT', '{}', 0)");
        $msg = "<div class='alert-success'>Đã gửi lệnh reload danh sách bot từ DB.</div>";
    } elseif ($action === 'delete_all_bots') {
        $deletedAccounts = 0;
        $deletedPlayers = 0;
        try {
            $conn->beginTransaction();
            $accounts = $conn->query("SELECT user, `char` FROM accounts WHERE note = 'BOT'")->fetchAll(PDO::FETCH_ASSOC);
            $deletePlayer = $conn->prepare("DELETE FROM players WHERE name = ?");
            foreach ($accounts as $acc) {
                $charName = firstBotCharacter($acc['char']);
                if ($charName) {
                    $deletePlayer->execute([$charName]);
                    $deletedPlayers += $deletePlayer->rowCount();
                }
            }
            $deletedAccounts = $conn->exec("DELETE FROM accounts WHERE note = 'BOT'");
            $conn->exec("DELETE FROM web_admin_commands WHERE status = 0 AND command IN ('SPAWN_BOT','RELOAD_BOT','CONFIG_BOT','CONFIG_BOT_MAP','CONFIG_BOT_AI','KILL_BOT')");
            $conn->exec("INSERT INTO web_admin_commands (command, data, status) VALUES ('KILL_BOT', '{}', 0)");
            $conn->exec("INSERT INTO web_admin_commands (command, data, status) VALUES ('RELOAD_BOT', '{}', 0)");
            $conn->commit();
            $msg = "<div class='alert-danger'>Đã xóa <b>$deletedAccounts</b> tài khoản bot và <b>$deletedPlayers</b> nhân vật bot.</div>";
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $msg = "<div class='alert-danger'>Không thể xóa tất cả bot: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
        }
    } elseif ($action === 'delete_bot') {
        $botUser = trim($_POST['bot_user'] ?? '');
        if ($botUser !== '') {
            $charStmt = $conn->prepare("SELECT `char` FROM accounts WHERE user = ? AND note = 'BOT' LIMIT 1");
            $charStmt->execute([$botUser]);
            $charName = firstBotCharacter($charStmt->fetchColumn());
            $stmt = $conn->prepare("DELETE FROM accounts WHERE user = ? AND note = 'BOT'");
            $stmt->execute([$botUser]);
            if ($charName) {
                $stmt2 = $conn->prepare("DELETE FROM players WHERE name = ?");
                $stmt2->execute([$charName]);
            }
            $conn->query("INSERT INTO web_admin_commands (command, data, status) VALUES ('KILL_BOT', '{}', 0)");
            $conn->query("INSERT INTO web_admin_commands (command, data, status) VALUES ('RELOAD_BOT', '{}', 0)");
            $msg = "<div class='alert-danger'>Đã xóa bot <b>" . htmlspecialchars($botUser, ENT_QUOTES, 'UTF-8') . "</b>.</div>";
        }
    }

    $bot_accounts = loadBotAccounts($conn);
}
?>

<style>
.bot-wrap { max-width: 980px; margin: 24px auto; padding: 0 16px; }
.bot-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:12px; }
.bot-header h2 { font-size:1.5rem; font-weight:800; margin:0; }
.bot-card { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.08); padding:24px; margin-bottom:20px; }
.bot-card h3 { font-size:1.1rem; font-weight:700; margin:0 0 16px; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.form-group label { font-size:.85rem; font-weight:600; color:#555; display:block; margin-bottom:6px; }
.form-group input, .form-group select { width:100%; border:1px solid #ddd; border-radius:8px; padding:8px 12px; font-size:.9rem; }
.btn { padding:10px 18px; border-radius:8px; font-weight:600; cursor:pointer; border:none; font-size:.9rem; text-decoration:none; display:inline-block; }
.btn-primary { background:#3b82f6; color:#fff; }
.btn-success { background:#10b981; color:#fff; }
.btn-warning { background:#f59e0b; color:#fff; }
.btn-danger { background:#ef4444; color:#fff; }
.btn-full { width:100%; text-align:center; }
.alert-success { background:#d1fae5; color:#065f46; border-radius:8px; padding:12px 16px; margin-bottom:16px; }
.alert-danger { background:#fee2e2; color:#991b1b; border-radius:8px; padding:12px 16px; margin-bottom:16px; }
.badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:.75rem; font-weight:600; }
.badge-bot { background:#e0e7ff; color:#4338ca; }
.badge-onl { background:#d1fae5; color:#065f46; }
.badge-off { background:#f3f4f6; color:#6b7280; }
.feature-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
.feature-toggle { border:1px solid #e5e7eb; border-radius:8px; padding:12px; display:flex; gap:10px; align-items:flex-start; background:#fff; }
.feature-toggle input { width:auto; margin-top:3px; }
.feature-toggle strong { display:block; font-size:.9rem; }
.feature-toggle span { display:block; color:#666; font-size:.78rem; margin-top:3px; line-height:1.35; }
table { width:100%; border-collapse:collapse; }
table th { background:#f8fafc; padding:10px 12px; text-align:left; font-size:.8rem; color:#666; font-weight:600; }
table td { padding:10px 12px; border-bottom:1px solid #f1f5f9; font-size:.85rem; }
table tr:hover td { background:#f8fafc; }
@media (max-width: 760px) {
    .form-row, .feature-grid { grid-template-columns:1fr; }
    .bot-header { align-items:flex-start; flex-direction:column; }
}
</style>

<div class="bot-wrap">
    <div class="bot-header">
        <h2>Quản lý Bot AI</h2>
        <a href="/Admin/index.php" style="color:#3b82f6;font-size:.9rem;">Quay lại Admin</a>
    </div>

    <?= $msg ?>

    <div class="bot-card">
        <h3>Tạo tài khoản bot trong Database</h3>
        <p style="font-size:.85rem;color:#666;margin-bottom:16px;">
            Bot sẽ có tài khoản và nhân vật thật trong DB. Dữ liệu mặc định đã có trang bị, kỹ năng, thời trang và chỉ số cơ bản.
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="create_accounts">
            <div class="form-row" style="grid-template-columns:1fr 1fr 1fr;margin-bottom:12px;">
                <div class="form-group">
                    <label>Số lượng tạo</label>
                    <input type="number" name="count" value="5" min="1" max="20">
                </div>
                <div class="form-group">
                    <label>Class nhân vật</label>
                    <select name="clazz">
                        <option value="0">Ngẫu nhiên</option>
                        <option value="1">Kiếm</option>
                        <option value="2">Súng</option>
                        <option value="3">Nắm đấm</option>
                        <option value="4">Pháp</option>
                        <option value="5">Đặc biệt</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tiền tố tên</label>
                    <input type="text" name="prefix" placeholder="Để trống sẽ chọn ngẫu nhiên">
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-full">Tạo bot mới</button>
        </form>
        <form method="POST" style="margin-top:14px;">
            <input type="hidden" name="action" value="ensure_bot_count">
            <div class="form-row" style="grid-template-columns:1fr auto;align-items:end;">
                <div class="form-group">
                    <label>Tự tạo bù đến tổng số bot</label>
                    <input type="number" name="desired_total" value="<?= max(100, count($bot_accounts)) ?>" min="1" max="5000">
                </div>
                <button type="submit" class="btn btn-warning">Tạo bù</button>
            </div>
        </form>
    </div>

    <div class="bot-card">
        <h3>Thả bot vào game</h3>
        <div class="form-row">
            <form method="POST">
                <input type="hidden" name="action" value="reload_bots">
                <p style="font-size:.85rem;color:#666;margin:0 0 14px;">
                    Hiện có <b><?= count($bot_accounts) ?></b> bot trong Database. Bấm reload sau khi tạo hoặc sửa bot để server nhận danh sách mới.
                </p>
                <button type="submit" class="btn btn-warning btn-full">Reload danh sách bot</button>
            </form>
            <form method="POST">
                <input type="hidden" name="action" value="spawn">
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Tên bot</label>
                    <input type="text" name="bot_name" list="bot-names" placeholder="Ví dụ: LuffyKing_42">
                    <datalist id="bot-names">
                        <?php foreach ($bot_accounts as $ba): ?>
                        <?php if (!empty($ba['char_name']) && !empty($ba['has_player'])): ?>
                        <option value="<?= htmlspecialchars($ba['char_name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
                    <label>Map xuất hiện</label>
                    <select name="map_id">
                        <?php foreach ($maps as $m): ?>
                        <option value="<?= intval($m['id']) ?>">Map <?= intval($m['id']) ?> - <?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($maps)): ?><option value="1">Map 1</option><?php endif; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Thả 1 bot</button>
            </form>
        </div>
    </div>

    <div class="bot-card">
        <h3>Cấu hình bot tự động</h3>
        <form method="POST">
            <input type="hidden" name="action" value="config">
            <div class="form-row" style="grid-template-columns:1fr 1fr 1fr;margin-bottom:12px;">
                <div class="form-group">
                    <label>Bot tối đa mỗi map có người</label>
                    <input type="number" name="max_bot" value="5" min="1" max="50">
                </div>
                <div class="form-group">
                    <label>Map trống</label>
                    <input type="number" name="min_bot" value="0" min="0" max="0" readonly>
                </div>
                <div class="form-group">
                    <label>Tổng bot tối đa toàn server</label>
                    <input type="number" name="max_total" value="1000" min="10" max="5000">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Lưu cấu hình</button>
        </form>
    </div>

    <div class="bot-card">
        <h3>Chỉnh bot theo từng map</h3>
        <p style="font-size:.85rem;color:#666;margin-bottom:14px;">
            Đặt số bot mục tiêu khi map có người chơi thật. Nếu map trống, server sẽ tự tạm dừng và dọn bot khỏi map đó.
        </p>
        <form method="POST" style="display:flex;gap:10px;align-items:end;margin-bottom:14px;">
            <input type="hidden" name="action" value="save_all_map_bot">
            <div class="form-group" style="max-width:220px;">
                <label>Bot khi map có người</label>
                <input type="number" name="target_all" value="1" min="0" max="50">
            </div>
            <button type="submit" class="btn btn-success">Áp dụng tất cả map</button>
        </form>
        <div style="max-height:420px;overflow:auto;border:1px solid #f1f5f9;border-radius:8px;">
        <div class="overflow-x-auto rounded border border-gray-200 mt-4">
            <table class="w-full text-sm text-left border-collapse bg-white">
                <thead>
                    <tr>
                        <th>Map ID</th>
                        <th>Tên map</th>
                        <th>Bot khi có người</th>
                        <th>Lưu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maps as $m):
                        $mid = intval($m['id']);
                        $target = $mapBotConfig[$mid] ?? 1;
                    ?>
                    <tr>
                        <td><?= $mid ?></td>
                        <td><?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form id="bot-map-<?= $mid ?>" method="POST"></form>
                            <input form="bot-map-<?= $mid ?>" type="hidden" name="action" value="save_map_bot">
                            <input form="bot-map-<?= $mid ?>" type="hidden" name="map_id" value="<?= $mid ?>">
                            <input form="bot-map-<?= $mid ?>" type="number" name="target_bot" value="<?= $target ?>" min="0" max="50" style="max-width:90px;">
                        </td>
                        <td><button form="bot-map-<?= $mid ?>" type="submit" class="btn btn-primary" style="padding:5px 12px;">Lưu</button></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($maps)): ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;">Không đọc được danh sách map.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bot-card">
        <h3>Tính năng AI bot</h3>
        <p style="font-size:.85rem;color:#666;margin-bottom:14px;">
            Bật hoặc tắt từng hành vi của bot. Sau khi lưu, server đang chạy sẽ nhận lệnh trong vài giây.
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="save_ai_features">
            <div class="feature-grid">
                <label class="feature-toggle">
                    <input type="checkbox" name="chat_enabled" value="1" <?= !empty($botAiConfig['chat_enabled']) ? 'checked' : '' ?>>
                    <div><strong>Chat tự nhiên</strong><span>Bot nói chuyện trên map và tránh lặp liên tục.</span></div>
                </label>
                <label class="feature-toggle">
                    <input type="checkbox" name="market_enabled" value="1" <?= !empty($botAiConfig['market_enabled']) ? 'checked' : '' ?>>
                    <div><strong>Đăng bán chợ</strong><span>Bot tự tạo đồ hiếm, đồ cao cấp và đăng bán bằng extol.</span></div>
                </label>
                <label class="feature-toggle">
                    <input type="checkbox" name="trade_enabled" value="1" <?= !empty($botAiConfig['trade_enabled']) ? 'checked' : '' ?>>
                    <div><strong>Giao dịch</strong><span>Bot đưa đồ hiếm vào bảng giao dịch khi người chơi mời.</span></div>
                </label>
                <label class="feature-toggle">
                    <input type="checkbox" name="friend_enabled" value="1" <?= !empty($botAiConfig['friend_enabled']) ? 'checked' : '' ?>>
                    <div><strong>Kết bạn</strong><span>Bot tự kết bạn với người chơi gần đó.</span></div>
                </label>
                <label class="feature-toggle">
                    <input type="checkbox" name="party_enabled" value="1" <?= !empty($botAiConfig['party_enabled']) ? 'checked' : '' ?>>
                    <div><strong>Tổ đội</strong><span>Bot tự vào nhóm của người chơi nếu nhóm còn chỗ.</span></div>
                </label>
                <label class="feature-toggle">
                    <input type="checkbox" name="shipping_enabled" value="1" <?= !empty($botAiConfig['shipping_enabled']) ? 'checked' : '' ?>>
                    <div><strong>Vận chuyển hàng</strong><span>Bot tự tạo hàng hoặc bảo vệ hàng cho người chơi.</span></div>
                </label>
                <label class="feature-toggle">
                    <input type="checkbox" name="dungeon_help_enabled" value="1" <?= !empty($botAiConfig['dungeon_help_enabled']) ? 'checked' : '' ?>>
                    <div><strong>Hỗ trợ phó bản</strong><span>Bot đứng gần trưởng nhóm và có chìa khóa để không chặn điều kiện vào phó bản.</span></div>
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:14px;">Lưu tính năng AI</button>
        </form>
    </div>

    <div class="bot-card" style="padding:16px 24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
            <div>
                <strong>Reload và sửa dữ liệu bot</strong>
                <p style="font-size:.8rem;color:#666;margin:4px 0 0;">
                    Dùng khi vừa tạo bot, sửa bot hoặc muốn server nhận lại dữ liệu bot trong DB.
                </p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reload_bots">
                <button type="submit" class="btn btn-warning">Reload bot DB</button>
            </form>
            <form method="POST" onsubmit="return confirm('Sửa lại dữ liệu các bot cũ trong database?');">
                <input type="hidden" name="action" value="repair_bots">
                <button type="submit" class="btn btn-success">Sửa bot cũ</button>
            </form>
        </div>
    </div>

    <div class="bot-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:10px;flex-wrap:wrap;">
            <h3 style="margin:0;">Danh sách tài khoản bot (<?= count($bot_accounts) ?>)</h3>
            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                <form method="POST" onsubmit="return confirm('Xóa toàn bộ bot đang online khỏi game? Dữ liệu DB vẫn giữ nguyên.');">
                    <input type="hidden" name="action" value="killall">
                    <button type="submit" class="btn btn-warning">Dọn bot trong game</button>
                </form>
                <form method="POST" onsubmit="return confirm('Cẩn thận: xóa sạch tất cả tài khoản BOT và nhân vật BOT trong database?');">
                    <input type="hidden" name="action" value="delete_all_bots">
                    <button type="submit" class="btn btn-danger">Xóa tất cả bot DB</button>
                </form>
            </div>
        </div>

        <?php if (empty($bot_accounts)): ?>
        <p style="color:#999;text-align:center;padding:20px;">Chưa có tài khoản bot nào.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <div class="overflow-x-auto rounded border border-gray-200">
            <table class="w-full text-sm text-left border-collapse bg-white">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tài khoản</th>
                        <th>Nhân vật</th>
                        <th>Class</th>
                        <th>Level</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bot_accounts as $i => $ba):
                        $lvArr = json_decode($ba['level'] ?? '[1,0,0]', true);
                        $lv = isset($lvArr[0]) ? $lvArr[0] : '?';
                        $classes = ['?', 'Kiếm', 'Súng', 'Nắm đấm', 'Pháp', 'Đặc biệt'];
                        $clazzName = $classes[intval($ba['clazz'])] ?? ('Class ' . $ba['clazz']);
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($ba['user'], ENT_QUOTES, 'UTF-8') ?></strong> <span class="badge badge-bot">BOT</span></td>
                        <td>
                            <?php if (!empty($ba['char_name']) && !empty($ba['has_player'])): ?>
                            <strong><?= htmlspecialchars($ba['char_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php elseif (!empty($ba['char_name'])): ?>
                            <span class="badge badge-off">Thiếu nhân vật</span> <?= htmlspecialchars($ba['char_name'], ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                            <span class="badge badge-off">Thiếu tên nhân vật</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($clazzName, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>Lv <?= htmlspecialchars(strval($lv), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (!empty($ba['onl'])): ?>
                            <span class="badge badge-onl">Online</span>
                            <?php else: ?>
                            <span class="badge badge-off">Offline</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="players.php?account_id=<?= intval($ba['id']) ?>" class="btn btn-primary" style="padding:4px 10px;font-size:.8rem;">Sửa nhân vật</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa bot <?= htmlspecialchars($ba['user'], ENT_QUOTES, 'UTF-8') ?>?');">
                                <input type="hidden" name="action" value="delete_bot">
                                <input type="hidden" name="bot_user" value="<?= htmlspecialchars($ba['user'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-danger" style="padding:4px 10px;font-size:.8rem;">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
