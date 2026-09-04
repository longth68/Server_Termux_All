<?php
require_once "hidden/set.php";
if (empty($_user) || $user_arr["is_admin"] != 1) {
    die("Bạn không có quyền truy cập trang này!");
}

// Java Server API Proxy for AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == 'proxy') {
    error_reporting(0); // JSON API: khong de warning PHP lam vo JSON (PHP 8.5+)
    header('Content-Type: application/json; charset=UTF-8');
    $api_action = isset($_GET['action']) ? preg_replace('/[^a-z0-9_]/i', '', $_GET['action']) : 'info';
    // Forward all query params except 'ajax' and 'action'
    $query_str = "";
    $allowed = array('key','val','name','vnd','item','qty','opt_id','opt_param','task','subtask','msg','type','amount','val2','boss','id','keep','idx','slot','bag_index','tempid','power','gold','map','x','y',
        'head','tiemnang','hpg','mpg','dameg','defg','critg','gem','ruby',
        'pet_type','pet_gender','pet_name','pet_status','pet_power','pet_tiemnang','pet_hpg','pet_mpg','pet_dameg','pet_defg','pet_critg',
        'main_id','main_index','main_count','side_id','side_count','side_max','side_left','side_level',
        'clan_id','clan_count','clan_max','clan_left','clan_level','kol_id','kol_count',
        'badge_id','days','use','hour','minute','auto_restart',
        'q','username','password','status');
    foreach ($_GET as $k => $v) {
        if ($k == 'ajax' || $k == 'action') continue;
        if (!in_array($k, $allowed)) continue;
        if ($query_str != "") $query_str .= "&";
        $query_str .= rawurlencode($k) . "=" . rawurlencode($v);
    }

    // Termux: Java API chay port 8085 (tranh 8888 cua Web PHP). Xem start.sh
    $url = "http://127.0.0.1:8085/api/" . $api_action;
    if ($query_str != "") $url .= "?" . $query_str;

    // Call Java API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // fail nhanh neu port 8085 khong mo
    curl_setopt($ch, CURLOPT_TIMEOUT, 8); // cho toi da 8s de response cham hoan tat (tranh abort som)
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlerr = curl_error($ch);

    if ($response !== false && $response !== "" && $httpcode == 200) {
        echo $response;
    } else {
        $detail = $curlerr !== "" ? $curlerr : ("HTTP " . $httpcode);
        echo json_encode([
            "status" => "error",
            "msg" => "Không thể kết nối đến Máy Chủ Java (Port 8085): " . $detail
        ]);
    }
    exit;
}

$msg = "";
$tab = $_GET['tab'] ?? 'dashboard';

// Xử lý POST (Tài khoản)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_acc'])) {
    $checktk = isset_sql(strtolower(trim($_POST["checktk"] ?? "")));
    $money = (int)($_POST["money"] ?? 0);
    $action = $_POST['action_acc'];

    if ($action === 'tang' && $checktk && $money > 0) {
        _query("UPDATE account SET vnd = vnd + $money, tongnap = tongnap + $money WHERE username = '$checktk'");
        $msg = "Cộng $money VND cho $checktk thành công!";
    } elseif ($action === 'mtv' && $checktk) {
        _query("UPDATE account SET active = 1 WHERE username = '$checktk'");
        $msg = "Mở thành viên cho $checktk thành công!";
    } elseif ($action === 'khoa' && $checktk) {
        _query("UPDATE account SET ban = 1 WHERE username = '$checktk'");
        $msg = "Đã KHÓA tài khoản $checktk";
    } elseif ($action === 'mokhoa' && $checktk) {
        _query("UPDATE account SET ban = 0 WHERE username = '$checktk'");
        $msg = "Đã MỞ KHÓA tài khoản $checktk";
    } elseif ($action === 'pass' && $checktk) {
        $newpass = isset_sql(trim($_POST["pass"] ?? ""));
        if ($newpass != "") {
            _query("UPDATE account SET password = '$newpass' WHERE username = '$checktk'");
            $msg = "Đã đổi mật khẩu cho $checktk thành công!";
        } else {
            $msg = "Vui lòng nhập mật khẩu mới!";
        }
    } elseif ($action === 'editfull') {
        $accid = (int)($_POST['accid'] ?? 0);
        $pass = isset_sql(trim($_POST['npass'] ?? ''));
        $active = (int)($_POST['nactive'] ?? 0);
        $ban = (int)($_POST['nban'] ?? 0);
        $isadmin = (int)($_POST['nis_admin'] ?? 0);
        $vnd = (int)($_POST['nvnd'] ?? 0);
        $tongnap = (int)($_POST['ntongnap'] ?? 0);
        $srv = (int)($_POST['nserver_login'] ?? -1);
        $sql = "UPDATE account SET active=$active, ban=$ban, is_admin=$isadmin, vnd=$vnd, tongnap=$tongnap, server_login=$srv";
        if ($pass != "") $sql .= ", password='$pass'";
        $sql .= " WHERE id=$accid";
        $msg = _query($sql) ? "Đã cập nhật tài khoản #$accid!" : "Lỗi cập nhật tài khoản!";
    } elseif ($action === 'del_acc') {
        $ids = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', trim($_POST['del_ids'] ?? '')))));
        if (empty($ids)) { $msg = "Vui lòng nhập ID tài khoản!"; }
        else {
            $in = implode(',', $ids);
            _query("DELETE FROM player WHERE account_id IN ($in)");
            $n = _query("DELETE FROM account WHERE id IN ($in)");
            $msg = $n ? "Đã xóa " . mysqli_affected_rows($conn) . " tài khoản (ID: $in) và toàn bộ nhân vật liên quan!" : "Lỗi xóa tài khoản!";
        }
    }
}

// Xử lý POST (Giftcode)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_gc'])) {
    $action = $_POST['action_gc'];
    if ($action === 'add') {
        $code = isset_sql(trim($_POST['code']));
        $item_str = trim($_POST['item']);
        $item_arr = [];
        if(!empty($item_str)) {
            $items = explode(',', $item_str);
            foreach($items as $it) {
                $parts = explode(':', trim($it));
                if(count($parts) == 2) {
                    $item_arr[] = ["id" => (int)$parts[0], "quantity" => (int)$parts[1]];
                }
            }
        }
        $item_json = json_encode($item_arr);

        $opt_str = trim($_POST['option']);
        $opt_arr = [];
        if(!empty($opt_str)) {
            $opts = explode(',', $opt_str);
            foreach($opts as $op) {
                $parts = explode(':', trim($op));
                if(count($parts) == 2) {
                    $opt_arr[] = ["id" => (int)$parts[0], "param" => (int)$parts[1]];
                }
            }
        }
        $opt_json = json_encode($opt_arr);

        $count = (int)$_POST['count'];
        $expire = isset_sql(trim($_POST['expire']));
        
        $sql = "INSERT INTO giftcode (`code`, `item`, `option`, `listIdPlayers`, `datecreate`, `expired`, `count_left`) 
                VALUES ('$code', '$item_json', '$opt_json', '[]', NOW(), '$expire', $count)";
        if (_query($sql)) {
            $msg = "Thêm mã Giftcode '$code' thành công!";
        } else {
            $msg = "Lỗi khi thêm Giftcode!";
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $code = isset_sql(trim($_POST['code']));
        $item = isset_sql(trim($_POST['item']));
        $option = isset_sql(trim($_POST['option']));
        $count = (int)$_POST['count'];
        $expire = isset_sql(trim($_POST['expire']));
        
        $sql = "UPDATE giftcode SET `code`='$code', `item`='$item', `option`='$option', `count_left`=$count, `expired`='$expire' WHERE id=$id";
        if (_query($sql)) {
            $msg = "Đã cập nhật mã '$code'!";
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if (_query("DELETE FROM giftcode WHERE id = $id")) {
            $msg = "Xóa Giftcode thành công!";
        }
    }
}

// Lấy thống kê cho Web
$total_acc = _fetch("SELECT COUNT(*) as c FROM account")["c"];
$active_acc = _fetch("SELECT COUNT(*) as c FROM account WHERE active=1")["c"];
$total_vnd = _fetch("SELECT SUM(tongnap) as c FROM account")["c"] ?? 0;
$total_gc = _fetch("SELECT COUNT(*) as c FROM giftcode")["c"];

$list_acc = [];
$q = mysqli_query($conn, "SELECT username, vnd, tongnap, active, ban FROM account ORDER BY tongnap DESC LIMIT 20");
while($row = mysqli_fetch_assoc($q)) $list_acc[] = $row;

$list_gc = [];
$q_gc = mysqli_query($conn, "SELECT * FROM giftcode ORDER BY id DESC LIMIT 50");
while($row = mysqli_fetch_assoc($q_gc)) $list_gc[] = $row;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Server Control Panel - NRO Manager</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/fa6/css/all.min.css">
    <!-- Bootstrap JS bundle (local) needed for Modal/Tabs/Pills -->
    <script src="/assets/js/bootstrap.bundle.min.js" defer></script>
    <style>
        body { background: #f4f6f9; font-size: 14px; }
        .sidebar { min-height: 100vh; background: white; border-right: 1px solid #ddd; }
        .sidebar a { color: #555; text-decoration: none; padding: 15px 20px; display: block; border-bottom: 1px solid #eee; font-weight: 500; }
        .sidebar a:hover { background: #f8f9fa; }
        .sidebar a.active { background: #e3f2fd; color: #0d6efd; border-left: 4px solid #0d6efd; }
        .sidebar i { width: 25px; text-align: center; margin-right: 10px; }
        .sidebar .sidebar-group { padding: 12px 20px 4px; font-size: 11px; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #eee; }
        .sidebar a { padding: 12px 20px; }
        .card { box-shadow: 0 1px 3px rgba(0,0,0,.1); margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; }
        .card-header { background: white; font-weight: bold; border-bottom: 1px solid #ddd; }
        .stat-box { border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: white; text-align: center; }
        .stat-box h6 { color: #666; font-size: 13px; margin-bottom: 5px; text-align: left; }
        .stat-box h4 { font-weight: bold; font-size: 18px; margin: 0; text-align: left;}
        
        .progress-bar-custom { height: 6px; border-radius: 3px; margin-top: 10px; background: #e9ecef; }
        .progress-fill { height: 100%; border-radius: 3px; }
        
        .btn-quick { margin-right: 8px; margin-bottom: 8px; font-weight: 500; border-radius: 4px; }
        .btn-warning { background-color: #ffc107; color: #000; border: none; }
        .btn-info { background-color: #17a2b8; color: white; border: none; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; }
        .btn-success { background-color: #28a745; color: white; border: none; }
        .btn-danger { background-color: #dc3545; color: white; border: none; }
        .btn-primary { background-color: #007bff; color: white; border: none; }
        
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar flex-shrink-0" style="width: 250px;">
        <div class="text-center py-3 border-bottom">
            <h5 class="m-0"><i class="fa-solid fa-server text-primary"></i> NRO Manager</h5>
        </div>
        <a href="?tab=dashboard" class="<?= $tab=='dashboard'?'active':'' ?>"><i class="fa-solid fa-table-cells-large text-primary"></i> Bảng Điều Khiển</a>
        <a href="?tab=player" class="<?= $tab=='player'?'active':'' ?>"><i class="fa-solid fa-users text-success"></i> Quản Lý Người Chơi</a>
        <a href="?tab=characters" class="<?= $tab=='characters'?'active':'' ?>"><i class="fa-solid fa-user text-info"></i> Quản Lý Nhân Vật</a>
        <a href="?tab=account" class="<?= $tab=='account'?'active':'' ?>"><i class="fa-solid fa-id-card text-primary"></i> Quản Lý Tài Khoản</a>
        <a href="?tab=boss" class="<?= $tab=='boss'?'active':'' ?>"><i class="fa-solid fa-dragon text-danger"></i> Boss Manager</a>
        <a href="?tab=bot" class="<?= $tab=='bot'?'active':'' ?>"><i class="fa-solid fa-robot text-warning"></i> Quản Lý Bot</a>
        <a href="?tab=event" class="<?= $tab=='event'?'active':'' ?>"><i class="fa-solid fa-calendar-days text-warning"></i> Quản Lý Sự Kiện</a>
        <a href="?tab=shop" class="<?= $tab=='shop'?'active':'' ?>"><i class="fa-solid fa-shop text-info"></i> Shop Manager</a>
<a href="?tab=consign" class="<?= $tab=='consign'?'active':'' ?>"><i class="fa-solid fa-basket-shopping text-danger"></i> Chợ Ký Gửi</a>
<a href="?tab=features" class="<?= $tab=='features'?'active':'' ?>"><i class="fa-solid fa-wand-magic-sparkles text-primary"></i> Tính Năng Mới</a>
        <a href="?tab=giftcode" class="<?= $tab=='giftcode'?'active':'' ?>"><i class="fa-solid fa-gift text-danger"></i> Quản Lý Giftcode</a>
        <a href="?tab=naprequest" class="<?= $tab=='naprequest'?'active':'' ?>"><i class="fa-solid fa-credit-card text-success"></i> Duyệt Nạp Thẻ</a>
        <a href="?tab=top" class="<?= $tab=='top'?'active':'' ?>"><i class="fa-solid fa-ranking-star text-warning"></i> Bảng Xếp Hạng</a>
        <div class="sidebar-group">DỮ LIỆU GAME</div>
        <a href="?tab=itemdata" class="<?= $tab=='itemdata'?'active':'' ?>"><i class="fa-solid fa-box text-primary"></i> Dữ Liệu Vật Phẩm</a>
        <a href="?tab=mapdata" class="<?= $tab=='mapdata'?'active':'' ?>"><i class="fa-solid fa-map text-success"></i> Dữ Liệu Bản Đồ</a>
        <a href="?tab=part" class="<?= $tab=='part'?'active':'' ?>"><i class="fa-solid fa-shirt text-info"></i> Quản Lý Part</a>
        <a href="?tab=drop" class="<?= $tab=='drop'?'active':'' ?>"><i class="fa-solid fa-bug text-warning"></i> Quản Lý Drop Item</a>
        <a href="?tab=badges" class="<?= $tab=='badges'?'active':'' ?>"><i class="fa-solid fa-medal text-danger"></i> Danh Hiệu</a>
        <a href="?tab=radar" class="<?= $tab=='radar'?'active':'' ?>"><i class="fa-solid fa-satellite-dish text-primary"></i> Quản Lý Radar</a>
        <div class="sidebar-group">LỊCH SỬ</div>
        <a href="?tab=lichsu" class="<?= $tab=='lichsu'?'active':'' ?>"><i class="fa-solid fa-clock-rotate-left text-secondary"></i> Lịch Sử Giao Dịch</a>
        <a href="/"><i class="fa-solid fa-right-from-bracket text-secondary"></i> Về Game</a>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        
        <!-- THÔNG BÁO TỪ AJAX -->
        <div id="ajaxAlert" class="alert alert-success d-none" role="alert"></div>

        <?php if($tab == 'dashboard'): ?>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="text-warning m-0 fw-bold">Maintenance Scheduled</h4>
                <div class="text-secondary fw-bold">Online: <span id="lbOnline">0</span></div>
                <div class="text-secondary fw-bold" id="lbTime">00:00:00</div>
            </div>

            <!-- Stats Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-box">
                        <h6>Server CPU</h6>
                        <h4>Server CPU: <span id="lbCpu">0.0</span>%</h4>
                        <div class="progress-bar-custom"><div class="progress-fill bg-primary" id="barCpu" style="width: 0%"></div></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h6>JVM RAM (Heap)</h6>
                        <h4><span id="lbRamUsed">0</span> / <span id="lbRamMax">0</span> MB</h4>
                        <div class="progress-bar-custom"><div class="progress-fill bg-info" id="barRam" style="width: 0%"></div></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h6>Threads</h6>
                        <h4 id="lbThreads">0</h4>
                        <div class="progress-bar-custom"><div class="progress-fill bg-success" style="width: 100%"></div></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h6>Sessions</h6>
                        <h4 id="lbSessions">0</h4>
                        <div class="progress-bar-custom"><div class="progress-fill bg-warning" style="width: 100%"></div></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card p-3 mb-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Quick Actions (Tính năng Server)</h6>
                <div>
                    <button class="btn btn-sm btn-quick btn-warning" onclick="maintenanceStart()"><i class="fa-solid fa-wrench"></i> Bảo Trì</button>
                    <button class="btn btn-sm btn-quick btn-danger" onclick="if(confirm('Bảo trì ngay lập tức?')) callApi('maintenance_now')"><i class="fa-solid fa-bolt"></i> Bảo Trì Ngay</button>
                    <button class="btn btn-sm btn-quick btn-info" onclick="callApi('reload_db')"><i class="fa-solid fa-database"></i> Reload DB</button>
                    <button class="btn btn-sm btn-quick btn-dark" onclick="changeExp()"><i class="fa-solid fa-arrow-up"></i> Thay EXP</button>
                    <button class="btn btn-sm btn-quick btn-danger" onclick="if(confirm('Kick tất cả?')) callApi('kick_all')"><i class="fa-solid fa-user-slash"></i> Kick All</button>
                    <button class="btn btn-sm btn-quick btn-success" onclick="callApi('save_data')"><i class="fa-solid fa-floppy-disk"></i> Save Data</button>
                    <button class="btn btn-sm btn-quick btn-primary" onclick="callApi('update_shop')"><i class="fa-solid fa-shop"></i> Load Shop</button>
                    <button class="btn btn-sm btn-quick btn-primary" onclick="callApi('update_top')"><i class="fa-solid fa-ranking-star"></i> Load TOP</button>
                    <button class="btn btn-sm btn-quick btn-secondary" onclick="callApi('bot_toggle')" id="btnBot">BOT: OFF</button>
                    <button class="btn btn-sm btn-quick btn-success" onclick="spawnBot(0, 'Pem Quái')">Gọi Bot</button>
                    <button class="btn btn-sm btn-quick btn-danger" onclick="callApi('boss_reset')">Reset Boss</button>
                </div>
            </div>

            <!-- Maintenance & Notice -->
            <div class="card p-3 mb-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Bảo Trì & Thông Báo</h6>
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="number" id="maintenanceMin" class="form-control" value="5" min="1" placeholder="Phút">
                            <button class="btn btn-warning" onclick="maintenanceStart()">Lên lịch Bảo trì</button>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" id="noticeMsg" class="form-control" placeholder="Nội dung thông báo gửi tới toàn server...">
                            <button class="btn btn-primary" onclick="sendNotice()">Gửi Thông Báo</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Maintenance Scheduler -->
            <div class="card p-3 mb-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Lịch Bảo Trì Hàng Ngày</h6>
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <input type="number" id="schedHour" class="form-control" value="23" min="0" max="23" placeholder="Giờ (0-23)">
                    </div>
                    <div class="col-md-3">
                        <input type="number" id="schedMinute" class="form-control" value="59" min="0" max="59" placeholder="Phút (0-59)">
                    </div>
                    <div class="col-md-3">
                        <select id="schedAutoRestart" class="form-select">
                            <option value="0">Không Restart</option>
                            <option value="1" selected>Auto Restart sau bảo trì</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-1">
                            <button class="btn btn-warning fw-bold flex-grow-1" onclick="scheduleMaintenance()">Đặt Lịch</button>
                            <button class="btn btn-secondary fw-bold" onclick="callApi('maintenance_cancel')">Hủy</button>
                        </div>
                    </div>
                </div>
                <script>
                function scheduleMaintenance() {
                    let h = document.getElementById('schedHour').value;
                    let m = document.getElementById('schedMinute').value;
                    let ar = document.getElementById('schedAutoRestart').value;
                    callApi('maintenance_set_time&hour=' + h + '&minute=' + m + '&auto_restart=' + ar);
                }
                </script>
            </div>

            <!-- Boss Manager -->
            <div class="card p-3 mb-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Boss Manager (Triệu Hồi & Cài Đặt)</h6>
                <a href="?tab=boss" class="btn btn-primary w-100 mb-2 fw-bold" style="padding: 10px;">Mở Menu Triệu Hồi (Search & Call Boss)</a>
                <div class="d-flex gap-2">
                    <button class="btn btn-danger w-50 fw-bold" style="padding: 10px;" onclick="if(confirm('Reset toàn bộ Boss?')) callApi('boss_reset')">Reset All Boss</button>
                    <button class="btn btn-success w-50 fw-bold" style="padding: 10px;" onclick="callApi('boss_respawn_resting')">Hồi Sinh Boss Đang Nghỉ</button>
                </div>
            </div>
            <!-- Optimization -->
            <div class="card p-3 mb-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">System Optimization & Booster (Server Only)</h6>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <button class="btn btn-sm btn-success fw-bold" onclick="callApi('ram_optimize')">Dọn dẹp JVM RAM</button>
                    <button class="btn btn-sm btn-primary fw-bold" onclick="callApi('optimize')">Tối ưu CPU & VPS</button>
                    <button class="btn btn-sm btn-secondary fw-bold" onclick="callApi('ram_optimize')">Xóa Log Cache</button>
                </div>
            </div>

            <!-- Data switch int/long -->
            <div class="card p-3 mb-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Dữ Liệu (int / long)</h6>
                <p class="text-muted m-0">Chuyển đổi kiểu đọc dữ liệu trong game. Trạng thái hiện tại: <span id="lblDataMode" class="fw-bold">...</span></p>
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-info fw-bold" onclick="callApi('data_switch&val=0')">Chuyển sang INT</button>
                    <button class="btn btn-sm btn-warning fw-bold" onclick="callApi('data_switch&val=1')">Chuyển sang LONG</button>
                </div>
            </div>

            <script>
                // Update time clock
                setInterval(() => {
                    document.getElementById('lbTime').innerText = new Date().toLocaleTimeString('en-GB');
                }, 1000);

                // Fetch server stats every 2 seconds
                function fetchStats() {
                    fetch('?ajax=proxy&action=info')
                    .then(res => res.json())
                    .then(data => {
                        if(data.status && data.status == 'error') return; // Server off
                        
                        document.getElementById('lbOnline').innerText = data.sessions;
                        document.getElementById('lbSessions').innerText = data.sessions;
                        document.getElementById('lbThreads').innerText = data.threads;
                        
                        document.getElementById('lbCpu').innerText = data.cpu;
                        document.getElementById('barCpu').style.width = data.cpu + '%';
                        
                        document.getElementById('lbRamUsed').innerText = data.ram_used;
                        document.getElementById('lbRamMax').innerText = data.ram_max;
                        let ramPerc = (data.ram_used / data.ram_max) * 100;
                        document.getElementById('barRam').style.width = ramPerc + '%';
                        
                        document.getElementById('lbUptime').innerText = data.uptime;

                        let dm = document.getElementById('lblDataMode');
                        if(dm) dm.innerText = (data.data_mode == 1 ? 'INT' : 'LONG') + ' (x' + (data.rate_exp||'?') + ' EXP)';
                        
                        let botBtn = document.getElementById('btnBot');
                        if(data.bot_enabled) {
                            botBtn.innerText = 'BOT: ON';
                            botBtn.classList.replace('btn-secondary', 'btn-success');
                        } else {
                            botBtn.innerText = 'BOT: OFF';
                            botBtn.classList.replace('btn-success', 'btn-secondary');
                        }
                    })
                    .catch(e => console.log('Java Server is probably offline'));
                }
                
                setInterval(fetchStats, 2000);
                fetchStats();

                function changeExp() {
                    let val = prompt("Nhập Hệ Số EXP Mới (VD: 100, 200, 300...): 100, 200, 300...):", "100");
                    if (val != null && val !== "") {
                        if (isNaN(val) || val <= 0) {
                            alert("Vui lòng nhập số hợp lệ!");
                            return;
                        }
                        callApi('exp_change&val=' + val);
                    }
                }
                
                function callApi(action) {
                    fetch('?ajax=proxy&action=' + action)
                    .then(res => res.json())
                    .then(data => {
                        let alert = document.getElementById('ajaxAlert');
                        alert.classList.remove('d-none', 'alert-danger');
                        alert.classList.add(data.status == 'error' ? 'alert-danger' : 'alert-success');
                        alert.innerText = data.msg || data.status;
                        setTimeout(() => alert.classList.add('d-none'), 3000);
                    });
                }

                function spawnBot(type, name) {
                    let amount = prompt("Nhập số lượng Bot " + name + " muốn tạo (hoặc nhập 0 để xóa):", "5");
                    if (amount != null && amount !== "") {
                        if (isNaN(amount)) {
                            alert("Vui lòng nhập số!");
                            return;
                        }
                        fetch('?ajax=proxy&action=bot_spawn&type=' + type + '&amount=' + amount)
                        .then(res => res.json())
                        .then(data => {
                            let alert = document.getElementById('ajaxAlert');
                            alert.classList.remove('d-none', 'alert-danger', 'alert-success');
                            alert.classList.add(data.status == 'error' ? 'alert-danger' : 'alert-success');
                            alert.innerText = data.msg || data.status;
                            setTimeout(() => alert.classList.add('d-none'), 3000);
                        });
                    }
                }

                function maintenanceStart() {
                    let val = document.getElementById('maintenanceMin') ? document.getElementById('maintenanceMin').value : 5;
                    if (val == null || val === "" || isNaN(val) || val <= 0) val = 5;
                    callApi('maintenance_start&val=' + val);
                }

                function sendNotice() {
                    let msg = document.getElementById('noticeMsg').value;
                    if (msg.trim() === "") { alert("Vui lòng nhập nội dung thông báo!"); return; }
                    fetch('?ajax=proxy&action=send_notice&msg=' + encodeURIComponent(msg))
                    .then(res => res.json())
                    .then(data => {
                        let alert = document.getElementById('ajaxAlert');
                        alert.classList.remove('d-none', 'alert-danger', 'alert-success');
                        alert.classList.add(data.status == 'error' ? 'alert-danger' : 'alert-success');
                        alert.innerText = data.msg || data.status;
                        document.getElementById('noticeMsg').value = '';
                        setTimeout(() => alert.classList.add('d-none'), 3000);
                    });
                }
            </script>
        <?php endif; ?>

        <?php if($tab == 'player'): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="m-0">Quản Lý Người Chơi</h3>
                <div>
                    <button class="btn btn-danger" onclick="if(confirm('Kick toàn bộ người chơi?')) callApi('kick_all')"><i class="fa-solid fa-user-slash"></i> Kick Tất Cả</button>
                    <button class="btn btn-success" onclick="callApi('save_data')"><i class="fa-solid fa-floppy-disk"></i> Save Data</button>
                </div>
            </div>
            <div id="ajaxAlert" class="alert alert-success d-none" role="alert"></div>

            <div class="card p-3 mb-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Thao tác theo tên người chơi (online)</h6>
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Tên nhân vật</label>
                        <input type="text" id="pv_name" class="form-control" placeholder="Tên nhân vật online">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Số VNĐ</label>
                        <input type="number" id="pv_vnd" class="form-control" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Item ID</label>
                        <input type="number" id="pv_item" class="form-control" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Số lượng</label>
                        <input type="number" id="pv_qty" class="form-control" value="1">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="playerGiveItem()">Gửi Vật Phẩm</button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100" onclick="playerBuffVnd()"><i class="fa-solid fa-coins"></i> Buff VNĐ</button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100" onclick="playerMtv()">Mở Thành Viên</button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-secondary w-100" onclick="playerKick()">Kick</button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-danger w-100" onclick="playerBan()">Khóa Tài Khoản</button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Task ID</label>
                        <input type="number" id="pv_task" class="form-control" value="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Subtask</label>
                        <input type="number" id="pv_subtask" class="form-control" value="0">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100" onclick="playerSetTask()">Set Nhiệm Vụ</button>
                    </div>
                </div>
            </div>

            <div class="card p-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Danh sách người chơi online <span id="pvCount" class="badge bg-primary ms-1">0</span></h6>
                <div class="input-group mb-2">
                    <input type="text" id="pvSearch" class="form-control" placeholder="Lọc theo tên...">
                </div>
                <div style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr><th>ID</th><th>Tên Nhân Vật</th><th>Sức Mạnh</th><th>Loại</th><th>Hành Động</th></tr>
                        </thead>
                        <tbody id="pvTableBody"></tbody>
                    </table>
                </div>
            </div>

            <script>
                function pvName(){ return document.getElementById('pv_name').value.trim(); }
                function showAlert(data){
                    let a = document.getElementById('ajaxAlert');
                    a.classList.remove('d-none','alert-danger','alert-success');
                    a.classList.add(data.status == 'error' ? 'alert-danger' : 'alert-success');
                    a.innerText = data.msg || data.status;
                    setTimeout(() => a.classList.add('d-none'), 3000);
                }
                function playerGiveItem(){
                    let name = pvName(); if(!name){alert('Nhập tên nhân vật!');return;}
                    let item = document.getElementById('pv_item').value;
                    let qty = document.getElementById('pv_qty').value;
                    fetch('?ajax=proxy&action=player_give_item&name='+encodeURIComponent(name)+'&item='+item+'&qty='+qty).then(r=>r.json()).then(showAlert);
                }
                function playerBuffVnd(){
                    let name = pvName(); let vnd = document.getElementById('pv_vnd').value;
                    if(!name){alert('Nhập tên nhân vật!');return;}
                    fetch('?ajax=proxy&action=player_buff_vnd&name='+encodeURIComponent(name)+'&vnd='+vnd).then(r=>r.json()).then(showAlert);
                }
                function playerMtv(){
                    let name = pvName(); if(!name){alert('Nhập tên nhân vật!');return;}
                    fetch('?ajax=proxy&action=player_mtv&name='+encodeURIComponent(name)).then(r=>r.json()).then(showAlert);
                }
                function playerKick(){
                    let name = pvName(); if(!name){alert('Nhập tên nhân vật!');return;}
                    fetch('?ajax=proxy&action=player_kick&name='+encodeURIComponent(name)).then(r=>r.json()).then(showAlert);
                }
                function playerBan(){
                    let name = pvName(); if(!name){alert('Nhập tên nhân vật!');return;}
                    if(!confirm('Khóa tài khoản '+name+'?')) return;
                    fetch('?ajax=proxy&action=player_ban&name='+encodeURIComponent(name)).then(r=>r.json()).then(showAlert);
                }
                function playerSetTask(){
                    let name = pvName(); let task = document.getElementById('pv_task').value; let sub = document.getElementById('pv_subtask').value;
                    if(!name){alert('Nhập tên nhân vật!');return;}
                    fetch('?ajax=proxy&action=player_set_task&name='+encodeURIComponent(name)+'&task='+task+'&subtask='+sub).then(r=>r.json()).then(showAlert);
                }
                function loadPlayers(){
                    fetch('?ajax=proxy&action=player_list').then(r=>r.json()).then(list=>{
                        let tb = document.getElementById('pvTableBody'); tb.innerHTML='';
                        let term = document.getElementById('pvSearch').value.toLowerCase();
                        let shown = 0;
                        (Array.isArray(list)?list:[]).forEach(p=>{
                            if(term && (p.name||'').toLowerCase().indexOf(term) < 0) return;
                            shown++;
                            let tr = document.createElement('tr');
                            tr.innerHTML = '<td>'+p.id+'</td><td><strong>'+p.name+'</strong></td><td>'+numberFmt(p.power)+'</td><td>'+(p.isBot?'<span class="badge bg-secondary">Bot</span>':'<span class="badge bg-success">Player</span>')+'</td>'+
                                '<td><button class="btn btn-xs btn-outline-danger" onclick="kickName(\''+p.name+'\')">Kick</button></td>';
                            tb.appendChild(tr);
                        });
                        document.getElementById('pvCount').innerText = shown;
                    }).catch(()=>{});
                }
                function kickName(n){
                    if(!confirm('Kick '+n+'?')) return;
                    fetch('?ajax=proxy&action=player_kick&name='+encodeURIComponent(n)).then(r=>r.json()).then(showAlert);
                }
                function numberFmt(n){ n = parseInt(n)||0; return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
                document.getElementById('pvSearch').addEventListener('input', loadPlayers);
                setInterval(loadPlayers, 4000);
                loadPlayers();
            </script>
        <?php endif; ?>

        <?php if($tab == 'boss'): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="m-0">Boss Manager</h3>
                <div>
                    <button class="btn btn-danger" onclick="if(confirm('Reset toàn bộ Boss?')) callApi('boss_reset')">Reset All Boss</button>
                    <button class="btn btn-success" onclick="callApi('boss_respawn_resting')">Hồi Sinh Boss Nghỉ</button>
                </div>
            </div>
            <div id="ajaxAlert" class="alert alert-success d-none" role="alert"></div>

            <div class="card p-3 mb-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Triệu Hồi Boss</h6>
                <div class="row g-2">
                    <div class="col-md-8">
                        <input type="text" id="bossSearch" class="form-control" placeholder="Tìm kiếm boss...">
                    </div>
                    <div class="col-md-4">
                        <select id="bossSelect" class="form-select" size="1"></select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="summonBoss()"><i class="fa-solid fa-dragon"></i> Triệu Hồi Boss</button>
                    </div>
                </div>
                <div class="mt-2" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-striped">
                        <thead class="table-dark"><tr><th>ID</th><th>Tên Boss</th><th>Hành Động</th></tr></thead>
                        <tbody id="bossTableBody"></tbody>
                    </table>
                </div>
            </div>

            <script>
                let bossList = [];
                function loadBosses(){
                    fetch('?ajax=proxy&action=boss_list').then(r=>r.json()).then(list=>{
                        bossList = Array.isArray(list)?list:[];
                        renderBosses();
                    }).catch(()=>{});
                }
                function renderBosses(){
                    let term = document.getElementById('bossSearch').value.toLowerCase();
                    let tb = document.getElementById('bossTableBody'); tb.innerHTML='';
                    let sel = document.getElementById('bossSelect'); sel.innerHTML='';
                    bossList.forEach(b=>{
                        if(term && (b.name||'').toLowerCase().indexOf(term) < 0) return;
                        let opt = document.createElement('option'); opt.value=b.id; opt.textContent=b.name+' ('+b.id+')'; sel.appendChild(opt);
                        let tr = document.createElement('tr');
                        tr.innerHTML = '<td>'+b.id+'</td><td>'+b.name+'</td><td><button class="btn btn-xs btn-outline-danger" onclick="summonOne('+b.id+')">Triệu Hồi</button> '
                            + '<button class="btn btn-xs btn-outline-warning" onclick="killBoss('+b.id+')">Tắt</button></td>';
                        tb.appendChild(tr);
                    });
                }
                function summonBoss(){
                    let id = document.getElementById('bossSelect').value;
                    if(!id){alert('Chọn boss!');return;}
                    summonOne(parseInt(id));
                }
                function summonOne(id){
                    fetch('?ajax=proxy&action=boss_summon&val='+id).then(r=>r.json()).then(data=>{
                        let a = document.getElementById('ajaxAlert');
                        a.classList.remove('d-none','alert-danger','alert-success');
                        a.classList.add(data.status=='error'?'alert-danger':'alert-success');
                        a.innerText = data.msg||data.status;
                        setTimeout(()=>a.classList.add('d-none'),3000);
                    });
                }
                function killBoss(id){
                    if(!confirm('Tắt boss id '+id+'?'))return;
                    fetch('?ajax=proxy&action=boss_kill&id='+id).then(r=>r.json()).then(data=>{
                        showAlert(data);
                        loadBosses();
                    }).catch(()=>{});
                }
                document.getElementById('bossSearch').addEventListener('input', renderBosses);
                loadBosses();
            </script>
        <?php endif; ?>

        <?php if($tab == 'event'): ?>
            <h3 class="mb-4">Quản Lý Sự Kiện</h3>
            <div id="ajaxAlert" class="alert alert-success d-none" role="alert"></div>

            <div class="card p-3 mb-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-calendar-days text-primary"></i> Bật / Tắt Sự Kiện</h6>
                <p class="small text-muted mb-2">Bật/tắt từng sự kiện độc lập. Có thể bật nhiều sự kiện cùng lúc. Nhấn <b>Lưu</b> để giữ trạng thái khi khởi động lại server.</p>
                <div id="eventToggles" class="row g-2 mb-3"></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success" onclick="eventSave()"><i class="fa-solid fa-floppy-disk"></i> Lưu Cấu Hình</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadEvents()"><i class="fa-solid fa-rotate"></i> Làm Mới</button>
                </div>
            </div>

            <div class="card p-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-info-circle text-info"></i> Trạng Thái Hiện Tại</h6>
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-dark"><tr><th>ID</th><th>Sự Kiện</th><th>Trạng Thái</th></tr></thead>
                    <tbody id="eventTableBody"></tbody>
                </table>
            </div>

            <script>
                const eventNames = [
                    'Không có sự kiện','Sự kiện Tết','Sự kiện Trung Thu','Sự kiện Halloween',
                    'Sự kiện Noel','Sự kiện Vu Lan','Sự kiện Quốc tế Phụ nữ','Sự kiện Hùng Vương',
                    'Sự kiện Black Friday','Sự kiện Valentine','Sự kiện 20/10','Sự kiện Nạp Thẻ'
                ];
                const eventIcons = ['','fa-solid fa-trophy','fa-solid fa-moon','fa-solid fa-ghost',
                    'fa-solid fa-tree','fa-solid fa-heart','fa-solid fa-venus','fa-solid fa-crown',
                    'fa-solid fa-tag','fa-solid fa-heart-crack','fa-solid fa-cake-candles','fa-solid fa-coins'];

                function loadEvents(){
                    fetch('?ajax=proxy&action=event_list').then(r=>r.json()).then(list=>{
                        let tb = document.getElementById('eventTableBody'); tb.innerHTML='';
                        let tg = document.getElementById('eventToggles'); tg.innerHTML='';
                        (Array.isArray(list)?list:[]).forEach(e=>{
                            if(e.id === 0) return;
                            // Table row
                            let tr = document.createElement('tr');
                            let badge = e.active
                                ? '<span class="badge bg-success"><i class="fa-solid fa-circle-check"></i> Đang chạy</span>'
                                : '<span class="badge bg-secondary">Tắt</span>';
                            tr.innerHTML = '<td>'+e.id+'</td><td>'+e.name+'</td><td>'+badge+'</td>';
                            tb.appendChild(tr);
                            // Toggle card
                            let col = document.createElement('div');
                            col.className = 'col-md-3 col-sm-6';
                            let icon = eventIcons[e.id] || 'fa-solid fa-calendar';
                            let btnClass = e.active ? 'btn-success' : 'btn-outline-secondary';
                            let btnText = e.active ? 'ĐANG BẬT' : 'TẮT';
                            col.innerHTML = '<div class="border rounded p-2 text-center'+(e.active?' border-success bg-light':'')+'">'
                                + '<i class="'+icon+' '+(e.active?'text-success':'text-muted')+' mb-1" style="font-size:20px"></i>'
                                + '<div class="small fw-bold mb-1">'+e.name+'</div>'
                                + '<button class="btn btn-sm '+btnClass+' w-100" onclick="toggleEvent('+e.id+','+(!e.active)+')">'+btnText+'</button>'
                                + '</div>';
                            tg.appendChild(col);
                        });
                    }).catch(()=>{});
                }

                function toggleEvent(id, on){
                    fetch('?ajax=proxy&action=event_toggle&id='+id+'&val='+(on?1:0)).then(r=>r.json()).then(data=>{
                        showAlert(data);
                        loadEvents();
                    }).catch(()=>{});
                }

                function eventSave(){
                    fetch('?ajax=proxy&action=event_save').then(r=>r.json()).then(data=>{
                        showAlert(data);
                    }).catch(()=>{});
                }

                function showAlert(data){
                    let a = document.getElementById('ajaxAlert');
                    a.classList.remove('d-none','alert-danger','alert-success');
                    a.classList.add(data.status=='error'?'alert-danger':'alert-success');
                    a.innerText = data.msg||data.status;
                    setTimeout(()=>a.classList.add('d-none'),3000);
                }

                loadEvents();
            </script>
        <?php endif; ?>

        <?php if($tab == 'consign'): ?>
            <h3 class="mb-4">Chợ Ký Gửi (Chợ Bông)</h3>
            <div id="consignAlert" class="alert alert-danger d-none" role="alert"></div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card p-3 text-center border-primary">
                        <div class="fs-3 fw-bold text-primary" id="csTotal">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-list"></i> Tin đang mở</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center border-warning">
                        <div class="fs-3 fw-bold text-warning" id="csBot">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-robot"></i> Từ Bot AI</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center border-success">
                        <div class="fs-3 fw-bold text-success" id="csSold">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-check"></i> Đã bán</div>
                    </div>
                </div>
            </div>

            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted fw-bold border-bottom pb-2 mb-0 flex-grow-1"><i class="fa-solid fa-basket-shopping text-danger"></i> Danh Sách Tin Đăng Bán</h6>
                    <label class="form-check form-switch mb-0 me-2 small text-muted">
                        <input type="checkbox" class="form-check-input" id="csAuto" checked> Tự động
                    </label>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadConsign()"><i class="fa-solid fa-rotate"></i> Làm Mới</button>
                </div>
                <table class="table table-bordered table-striped table-hover table-sm">
                    <thead class="table-dark">
                        <tr><th>#</th><th>Vật Phẩm</th><th>Người Bán</th><th>Giá Thỏi Vàng</th><th>Giá Hồng Ngọc</th><th>SL</th><th>Trạng Thái</th></tr>
                    </thead>
                    <tbody id="consignTableBody"></tbody>
                </table>
            </div>

            <script>
                function fmtNum(n){
                    if(n === null || n === undefined || n < 0) return '—';
                    return Number(n).toLocaleString('vi-VN');
                }

                function loadConsign(){
                    fetch('?ajax=proxy&action=consign_list').then(r=>r.json()).then(list=>{
                        let tb = document.getElementById('consignTableBody'); tb.innerHTML='';
                        let total=0, bot=0, sold=0;
                        (Array.isArray(list)?list:[]).forEach(it=>{
                            if(it.sold) sold++; else total++;
                            if(it.is_bot && !it.sold) bot++;
                            let tr = document.createElement('tr');
                            if(it.sold) tr.classList.add('table-light','opacity-75');
                            let seller = it.is_bot
                                ? '<span class="badge bg-warning text-dark">'+it.seller+'</span>'
                                : '<span class="badge bg-info text-dark">'+it.seller+'</span>';
                            let st = it.sold
                                ? '<span class="badge bg-success">Đã bán</span>'
                                : '<span class="badge bg-primary">Đang bán</span>';
                            tr.innerHTML = '<td>'+it.id+'</td>'
                                + '<td class="fw-bold">'+it.name+'</td>'
                                + '<td>'+seller+'</td>'
                                + '<td class="text-end">'+fmtNum(it.gold)+'</td>'
                                + '<td class="text-end">'+fmtNum(it.gem)+'</td>'
                                + '<td class="text-center">'+it.qty+'</td>'
                                + '<td>'+st+'</td>';
                            tb.appendChild(tr);
                        });
                        if(total===0 && sold===0){
                            tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="fa-solid fa-basket-shopping opacity-50"></i> Chưa có tin đăng nào — Bot sẽ tự đăng sau khi farm đủ đồ hiếm</td></tr>';
                        }
                        document.getElementById('csTotal').innerText = total;
                        document.getElementById('csBot').innerText = bot;
                        document.getElementById('csSold').innerText = sold;
                    }).catch(e=>{
                        let a = document.getElementById('consignAlert');
                        a.classList.remove('d-none');
                        a.innerText = 'Không kết nối được server API!';
                        setTimeout(()=>a.classList.add('d-none'), 3000);
                    });
                }

                setInterval(()=>{ if(document.getElementById('csAuto') && document.getElementById('csAuto').checked) loadConsign(); }, 10000);
                loadConsign();
            </script>
        <?php endif; ?>

        <?php if($tab == 'features'): ?>
            <h3 class="mb-4">Tính Năng Port Từ Hashirama</h3>
            <div id="featAlert" class="alert alert-danger d-none" role="alert"></div>
            <div class="mb-3">
                <button class="btn btn-sm btn-outline-secondary" onclick="loadFeat()"><i class="fa-solid fa-rotate"></i> Làm Mới / Thử Lại</button>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card p-3 text-center border-success">
                        <div class="fs-4 fw-bold text-success" id="ftKnActive">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-gem"></i> Đang khảm ngọc</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center border-warning">
                        <div class="fs-4 fw-bold text-warning" id="ftRstActive">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-box-open"></i> Bật rương sưu tầm</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center border-info">
                        <div class="fs-4 fw-bold text-info" id="ftDanduoc">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-flask"></i> Đã ăn đan dược</div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3" id="featTabs">
                <li class="nav-item"><a class="nav-link active" href="#" data-t="kham_ngoc">Khảm Ngọc</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="phong_thi_nghiem">Phòng Thí Nghiệm</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="ruong_suu_tam">Rương Sưu Tầm</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="moc_vong_quay">Mốc Tầm Bảo</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="tambao_items">Quà Tầm Bảo</a></li>
            </ul>

            <div class="card p-3">
                <table class="table table-bordered table-striped table-hover table-sm">
                    <thead class="table-dark"><tr id="featHead"></tr></thead>
                    <tbody id="featBody"></tbody>
                </table>
            </div>

            <script>
                let featData = {};
                let curTab = 'kham_ngoc';

                function loadFeat(){
                    fetch('?ajax=proxy&action=feature_data', {cache:'no-store'})
                        .then(r=>r.text().then(t=>({status:r.status, t:t})))
                        .then(res=>{
                            let d;
                            try {
                                d = JSON.parse(res.t.trim());
                            } catch(e) {
                                throw new Error('HTTP ' + res.status + ' | Phản hồi: ' + res.t.trim().substring(0,150));
                            }
                            featData = d;
                            document.getElementById('featAlert').classList.add('d-none');
                            if(d.stats){
                                document.getElementById('ftKnActive').innerText = d.stats.kham_ngoc_active||0;
                                document.getElementById('ftRstActive').innerText = d.stats.ruong_suu_tam_active||0;
                                document.getElementById('ftDanduoc').innerText = d.stats.dan_duoc_users||0;
                            }
                            renderTab();
                        })
                        .catch(e=>{
                            let a=document.getElementById('featAlert');
                            a.classList.remove('d-none');
                            a.className='alert alert-warning';
                            a.innerText='Lỗi tải dữ liệu: '+(e.message||e);
                            setTimeout(()=>a.classList.add('d-none'),8000);
                        });
                }

                function renderTab(){
                    let rows = featData[curTab] || [];
                    let head = document.getElementById('featHead');
                    let body = document.getElementById('featBody');
                    head.innerHTML=''; body.innerHTML='';
                    if(rows.length===0){
                        body.innerHTML='<tr><td colspan="6" class="text-center text-muted py-4">Không có dữ liệu</td></tr>';
                        return;
                    }
                    Object.keys(rows[0]).forEach(k=>{
                        let th=document.createElement('th'); th.innerText=k; head.appendChild(th);
                    });
                    rows.slice(0,50).forEach(r=>{
                        let tr=document.createElement('tr');
                        Object.keys(rows[0]).forEach(k=>{
                            let td=document.createElement('td');
                            let v = r[k]==null?'':String(r[k]);
                            td.innerText = v.length>60 ? v.substring(0,60)+'…' : v;
                            if(v.length>60) td.title=v;
                            tr.appendChild(td);
                        });
                        body.appendChild(tr);
                    });
                }

                document.querySelectorAll('#featTabs a').forEach(a=>{
                    a.addEventListener('click',e=>{
                        e.preventDefault();
                        document.querySelectorAll('#featTabs a').forEach(x=>x.classList.remove('active'));
                        a.classList.add('active');
                        curTab=a.dataset.t; renderTab();
                    });
                });

                loadFeat();
            </script>
        <?php endif; ?>

        <?php if($tab == 'shop'): ?>
            <?php include __DIR__ . '/admin_tabs/shop_editor.php'; ?>
        <?php endif; ?>

        <?php if($tab == 'itemdata'): ?>
            <?php include __DIR__ . '/admin_tabs/itemdata.php'; ?>
        <?php endif; ?>

        <?php if($tab == 'mapdata'): ?>
            <?php include __DIR__ . '/admin_tabs/mapdata.php'; ?>
        <?php endif; ?>

        <?php if($tab == 'part'): ?>
            <?php include __DIR__ . '/admin_tabs/part.php'; ?>
        <?php endif; ?>

        <?php if($tab == 'drop'): ?>
            <?php include __DIR__ . '/admin_tabs/drop.php'; ?>
        <?php endif; ?>

        <?php if($tab == 'badges'): ?>
            <?php include __DIR__ . '/admin_tabs/badges.php'; ?>
        <?php endif; ?>

        <?php if($tab == 'radar'): ?>
            <?php include __DIR__ . '/admin_tabs/radar.php'; ?>
        <?php endif; ?>

        <?php if($tab == 'lichsu'): ?>
            <?php include __DIR__ . '/admin_tabs/lichsu.php'; ?>
        <?php endif; ?>

        <?php if($tab == 'bot'): ?>
            <?php include __DIR__ . '/admin_tabs/bot.php'; ?>
        <?php endif; ?>

        <?php if($tab == 'characters'): ?>
            <?php include __DIR__ . '/admin_tabs/characters.php'; ?>
        <?php endif; ?>

        <?php if($tab == 'account'): ?>
            <h3 class="mb-4">Quản Lý Tài Khoản</h3>
            <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-3">
                        <h5>Thao tác nhanh</h5>
                        <form method="POST">
                            <input type="text" class="form-control mb-2" name="checktk" placeholder="Username" required>
                            <input type="number" class="form-control mb-3" name="money" placeholder="Số VNĐ muốn cộng">
                            <button class="btn btn-success w-100 mb-2" name="action_acc" value="tang"> Cộng VNĐ</button>
                            <button class="btn btn-primary w-100 mb-2" name="action_acc" value="mtv"> Mở Thành Viên</button>
                            <div class="d-flex gap-2">
                                <button class="btn btn-danger w-50" name="action_acc" value="khoa"> Khóa</button>
                                <button class="btn btn-warning w-50" name="action_acc" value="mokhoa"> Mở Khóa</button>
                            </div>
                            <hr>
                            <input type="text" class="form-control mb-2" name="pass" placeholder="Mật khẩu mới">
                            <button class="btn btn-dark w-100" name="action_acc" value="pass"> Đổi mật khẩu</button>
                        </form>
                    </div>
                    <div class="card p-3 mt-3">
                        <h5 class="text-danger">Xóa Tài Khoản Hàng Loạt</h5>
                        <form method="POST">
                            <input type="hidden" name="action_acc" value="del_acc">
                            <input type="text" class="form-control mb-2" name="del_ids" placeholder="Danh sách ID tài khoản (1,2,3...)" required>
                            <button class="btn btn-danger w-100" onclick="return confirm('Xác nhận xóa các tài khoản này cùng toàn bộ nhân vật? Không thể hoàn tác!')"><i class="fa-solid fa-trash"></i> Xóa Tài Khoản Hàng Loạt</button>
                        </form>
                    </div>
                    <div class="card p-3 mt-3 border-danger">
                        <h5 class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Vùng Nguy Hiểm</h5>
                        <p class="small text-muted mb-2">Xóa <b>TOÀN BỘ</b> tài khoản + nhân vật người chơi trong database (kể cả đang online). Giữ lại tài khoản Admin (<b><?= $_username ?></b>). Nên tắt server game trước khi xóa để tránh dữ liệu bị ghi đè!</p>
                        <button id="btnWipeAll" class="btn btn-danger w-100 fw-bold" onclick="wipeAllAccounts()"><i class="fa-solid fa-radiation"></i> XÓA TOÀN BỘ TÀI KHOẢN NGƯỜI CHƠI</button>
                    </div>
                    <script>
                    function wipeAllAccounts(){
                        if(!confirm('!!! CẢNH BÁO !!!\nXóa TOÀN BỘ tài khoản + nhân vật người chơi trong database?\nKHÔNG THỂ HOÀN TÁC!')) return;
                        if(!confirm('Xác nhận LẦN 2: Bạn chắc chắn chứ?\n(Tài khoản Admin sẽ được giữ lại)')) return;
                        let btn = document.getElementById('btnWipeAll');
                        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xóa...';
                        fetch('?ajax=proxy&action=wipe_accounts&keep=<?= urlencode($_username) ?>')
                            .then(r=>r.json()).then(d=>{
                                alert(d.msg || d.status);
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fa-solid fa-radiation"></i> XÓA TOÀN BỘ TÀI KHOẢN NGƯỜI CHƠI';
                                window.location.reload();
                            }).catch(e=>{ alert('Lỗi kết nối: '+e); btn.disabled=false; });
                    }
                    </script>
                </div>
                <div class="col-md-8">
                    <div class="card p-3" style="max-height: 600px; overflow-y: auto;">
                        <form method="POST" class="d-flex mb-3">
                            <input type="text" class="form-control me-2" name="search_acc" placeholder="Tìm kiếm Username...">
                            <button class="btn btn-outline-primary" type="submit">Tìm</button>
                        </form>
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>VNĐ</th>
                                    <th>Tổng Nạp</th>
                                    <th>Trạng Thái</th>
                                    <th>Hành Động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $search = trim($_POST['search_acc'] ?? '');
                                $query = "SELECT * FROM account";
                                if($search != "") $query .= " WHERE username LIKE '%" . addslashes($search) . "%'";
                                $query .= " ORDER BY id DESC LIMIT 50";
                                $res = _query($query);
                                while($row = mysqli_fetch_assoc($res)):
                                ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                                    <td><span class="text-success"><?= number_format($row['vnd'] ?? 0) ?>đ</span></td>
                                    <td><span class="text-primary"><?= number_format($row['tongnap'] ?? 0) ?>đ</span></td>
                                    <td>
                                        <?= $row['active'] ? '<span class="badge bg-success">Đã mở</span>' : '<span class="badge bg-secondary">Chưa mở</span>' ?>
                                        <?= $row['ban'] ? '<span class="badge bg-danger">Bị khóa</span>' : '' ?>
                                        <?= $row['is_admin'] ? '<span class="badge bg-info">Admin</span>' : '' ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick='editAcc(<?= json_encode(array('id'=>$row['id'],'username'=>$row['username'],'password'=>$row['password'],'active'=>$row['active'],'ban'=>$row['ban'],'is_admin'=>$row['is_admin']??0,'vnd'=>$row['vnd']??0,'tongnap'=>$row['tongnap']??0,'server_login'=>$row['server_login']??-1), JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i> Sửa</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <?php if($tab == 'giftcode'): ?>
            <h3 class="mb-4">Quản Lý Giftcode</h3>
            <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-3">
                        <h5>Thêm Mã Mới</h5>
                        <form method="POST">
                            <input type="text" class="form-control mb-2" name="code" placeholder="Mã Giftcode (VD: TANTHU)" required>
                            <textarea class="form-control mb-2" name="item" id="gcItemInput" placeholder="ID Vật phẩm (Cấu trúc mảng JSON) ví dụ: [{"id":457,"quantity":10}]" oninput="gcPreview()"></textarea>
                            <div id="gcPreview" class="d-flex flex-wrap gap-1 mb-2"></div>
                            <textarea class="form-control mb-2" name="option" placeholder="Option Vật phẩm (JSON)"></textarea>
                            <input type="number" class="form-control mb-2" name="count" placeholder="Số lượng nhập tối đa" value="100" required>
                            <input type="datetime-local" class="form-control mb-3" name="expire" required>
                            <button class="btn btn-primary w-100" name="action_gc" value="add"> Thêm Giftcode</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card p-3" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Mã Code</th>
                                    <th>Lượt Nhập</th>
                                    <th>Hạn Sử Dụng</th>
                                    <th>Hành Động</th>
                                </tr>
                            </thead>
                                <tbody>
                                <?php
                                $imap = [];
                                $ires = _query("SELECT id, NAME, icon_id FROM item_template");
                                while($ir = mysqli_fetch_assoc($ires)) $imap[$ir['id']] = $ir;
                                function gcItemImgs($json, $imap){
                                    $arr = json_decode($json, true);
                                    if(!is_array($arr)) return '<span class="text-muted small">—</span>';
                                    $out = '';
                                    foreach(array_slice($arr,0,8) as $e){
                                        $id = (int)($e['id'] ?? 0); $q = (int)($e['quantity'] ?? 1);
                                        $ic = isset($imap[$id]) ? (int)$imap[$id]['icon_id'] : 0;
                                        $nm = isset($imap[$id]) ? htmlspecialchars($imap[$id]['NAME']) : ('#'.$id);
                                        $out .= '<span class="d-inline-block text-center me-1" style="width:42px;vertical-align:top;">'
                                            . '<img src="item_icon.php?id='.$ic.'&size=3" width="36" height="36" class="border rounded" '
                                            . 'style="image-rendering:pixelated;" loading="lazy" title="'.($nm).'">'
                                            . '<span class="d-block" style="font-size:9px;">x'.$q.'</span></span>';
                                    }
                                    if(count($arr) > 8) $out .= '<span class="small text-muted">+'.(count($arr)-8).'</span>';
                                    return $out ?: '<span class="text-muted small">—</span>';
                                }
                                $res = _query("SELECT * FROM giftcode ORDER BY id DESC LIMIT 50");
                                while($row = mysqli_fetch_assoc($res)):
                                ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><strong class="text-danger"><?= htmlspecialchars($row['code']) ?></strong></td>
                                    <td class="align-middle"><?= gcItemImgs($row['item'], $imap) ?></td>
                                    <td><?= $row['count_left'] ?></td>
                                    <td><?= $row['expired'] ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick='editGc(<?= json_encode(array('id'=>$row['id'],'code'=>$row['code'],'count_left'=>$row['count_left'],'expired'=>$row['expired'],'item'=>$row['item'],'option'=>$row['option']), JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i> Sửa</button>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button class="btn btn-sm btn-danger" name="action_gc" value="delete" onclick="return confirm('Xóa mã này?')"><i class="fa-solid fa-trash"></i> Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>


    </div>
</div>

        <?php if($tab == 'naprequest'): ?>
            <h3 class="mb-4"><i class="fa-solid fa-credit-card text-success"></i> Duyệt Nạp Thẻ (bảng napthe)</h3>
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="m-0 text-muted fw-bold">Yêu cầu nạp (mới nhất trước)</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadNap()"><i class="fa-solid fa-rotate"></i> Làm Mới</button>
                </div>
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-dark"><tr><th>ID</th><th>Username</th><th>Nhà mạng</th><th>Serial</th><th>Mã thẻ</th><th>Mệnh giá</th><th>Trạng thái</th><th>Ngày tạo</th><th>Duyệt</th></tr></thead>
                    <tbody id="napTableBody"><tr><td colspan="9" class="text-center text-muted">Đang tải...</td></tr></tbody>
                </table>
            </div>
            <script>
                function napStatus(s){
                    s = parseInt(s);
                    if(s === 1) return '<span class="badge bg-success">Đã duyệt</span>';
                    if(s === 0 || s === 99) return '<span class="badge bg-warning">Chờ duyệt</span>';
                    return '<span class="badge bg-danger">Từ chối</span>';
                }
                function loadNap(){
                    fetch('?ajax=proxy&action=nap_card_list').then(r=>r.json()).then(d=>{
                        let list = d.list || [];
                        let tb = document.getElementById('napTableBody'); tb.innerHTML='';
                        if(!list.length){ tb.innerHTML='<tr><td colspan="9" class="text-center text-muted">Trống</td></tr>'; return; }
                        list.forEach(n=>{
                            let st = parseInt(n.status);
                            let btn = (st === 0 || st === 99)
                                ? '<button class="btn btn-xs btn-success" onclick="napApprove('+n.id+')">Duyệt</button> '
                                + '<button class="btn btn-xs btn-danger" onclick="napReject('+n.id+')">Từ chối</button>'
                                : '<span class="text-muted">—</span>';
                            let tr = document.createElement('tr');
                            tr.innerHTML = '<td>'+n.id+'</td><td><strong>'+n.username+'</strong></td><td>'+n.card_type+'</td>'
                                + '<td><small>'+n.card_seri+'</small></td><td><small>'+n.card_code+'</small></td>'
                                + '<td class="text-success fw-bold">'+Number(n.amount).toLocaleString()+'</td>'
                                + '<td>'+napStatus(st)+'</td><td><small>'+(n.created_at||'')+'</small></td><td>'+btn+'</td>';
                            tb.appendChild(tr);
                        });
                    }).catch(()=>{});
                }
                function napApprove(id){
                    if(!confirm('Duyệt nạp thẻ #'+id+'? (cộng VNĐ + tổng nạp cho tài khoản)'))return;
                    fetch('?ajax=proxy&action=nap_card_approve&val='+id).then(r=>r.json()).then(d=>{ showAlert(d); loadNap(); }).catch(()=>{});
                }
                function napReject(id){
                    if(!confirm('Từ chối yêu cầu nạp #'+id+'?'))return;
                    fetch('?ajax=proxy&action=nap_card_reject&val='+id).then(r=>r.json()).then(d=>{ showAlert(d); loadNap(); }).catch(()=>{});
                }
                loadNap();
            </script>
        <?php endif; ?>

        <?php if($tab == 'top'): ?>
            <h3 class="mb-4"><i class="fa-solid fa-ranking-star text-warning"></i> Bảng Xếp Hạng</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="card p-3">
                        <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Top Nạp Tiền</h6>
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="table-dark"><tr><th>#</th><th>Username</th><th>Nhân vật</th><th>Tổng nạp</th></tr></thead>
                            <tbody id="topNapBody"><tr><td colspan="4" class="text-center text-muted">Đang tải...</td></tr></tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3">
                        <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Top Sức Mạnh</h6>
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="table-dark"><tr><th>#</th><th>Nhân vật</th><th>Sức mạnh</th></tr></thead>
                            <tbody id="topPowerBody"><tr><td colspan="3" class="text-center text-muted">Đang tải...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <script>
                function loadTops(){
                    fetch('?ajax=proxy&action=top_nap').then(r=>r.json()).then(d=>{
                        let tb = document.getElementById('topNapBody'); tb.innerHTML='';
                        (d.list||[]).forEach(t=>{
                            let tr = document.createElement('tr');
                            tr.innerHTML = '<td>'+t.rank+'</td><td><strong>'+t.username+'</strong></td><td>'+(t.char_name||'—')+'</td><td class="text-success fw-bold">'+Number(t.tongnap).toLocaleString()+'</td>';
                            tb.appendChild(tr);
                        });
                        if(!(d.list||[]).length) tb.innerHTML='<tr><td colspan="4" class="text-center text-muted">Trống</td></tr>';
                    }).catch(()=>{});
                    fetch('?ajax=proxy&action=top_power').then(r=>r.json()).then(d=>{
                        let tb = document.getElementById('topPowerBody'); tb.innerHTML='';
                        (d.list||[]).forEach(t=>{
                            let tr = document.createElement('tr');
                            tr.innerHTML = '<td>'+t.rank+'</td><td><strong>'+t.name+'</strong></td><td class="text-primary fw-bold">'+Number(t.power).toLocaleString()+'</td>';
                            tb.appendChild(tr);
                        });
                        if(!(d.list||[]).length) tb.innerHTML='<tr><td colspan="3" class="text-center text-muted">Trống</td></tr>';
                    }).catch(()=>{});
                }
                loadTops();
            </script>
        <?php endif; ?>

<script>
    // Global helpers usable on every tab
    function getAlert(){
        let a = document.getElementById('ajaxAlert');
        if(!a){
            a = document.createElement('div');
            a.id = 'ajaxAlert';
            a.className = 'alert alert-success d-none';
            document.querySelector('.flex-grow-1') ? document.querySelector('.flex-grow-1').prepend(a) : document.body.prepend(a);
        }
        return a;
    }
    function callApi(action){
        fetch('?ajax=proxy&action=' + action)
        .then(res => res.json())
        .then(data => {
            let alert = getAlert();
            alert.classList.remove('d-none', 'alert-danger', 'alert-success');
            alert.classList.add(data.status == 'error' ? 'alert-danger' : 'alert-success');
            alert.innerText = data.msg || data.status;
            setTimeout(() => alert.classList.add('d-none'), 3000);
        })
        .catch(() => {});
    }
    function showAlert(data){
        let alert = getAlert();
        alert.classList.remove('d-none', 'alert-danger', 'alert-success');
        alert.classList.add(data.status == 'error' ? 'alert-danger' : 'alert-success');
        alert.innerText = data.msg || data.status;
        setTimeout(() => alert.classList.add('d-none'), 3000);
    }
    function gcPreview(){
        let box = document.getElementById('gcPreview');
        if(!box) return;
        let raw = document.getElementById('gcItemInput').value.trim();
        if(!raw){ box.innerHTML = ''; return; }
        let arr;
        try { arr = JSON.parse(raw); } catch(e){ box.innerHTML = '<span class="small text-danger">JSON không hợp lệ</span>'; return; }
        if(!Array.isArray(arr)){ box.innerHTML = '<span class="small text-danger">Phải là mảng JSON</span>'; return; }
        box.innerHTML = arr.slice(0,12).map(e=>{
            let id = e.id, q = e.quantity||1;
            return '<span class="d-inline-block text-center" style="width:42px;vertical-align:top;">'
                + '<img src="item_icon.php?by=template&id='+id+'&size=3" width="36" height="36" class="border rounded" style="image-rendering:pixelated;" onerror="this.style.visibility=\'hidden\'">'
                + '<span class="d-block" style="font-size:9px;">x'+q+'</span></span>';
        }).join('') + (arr.length>12 ? '<span class="small text-muted">+'+(arr.length-12)+'</span>' : '');
    }
    function editGc(g){
        let f = prompt("Sửa giftcode #"+g.id+"\nNhập: Code|Số lượt|Hạn(YYYY-MM-DD HH:MM:SS)|ItemJSON|OptionJSON",
            g.code+"|"+g.count_left+"|"+g.expired+"|"+(g.item||'[]')+"|"+(g.option||'[]'));
        if(!f) return;
        let p = f.split('|');
        if(p.length < 5){ alert("Thiếu dữ liệu, cần 5 trường!"); return; }
        let fd = new URLSearchParams();
        fd.append('action_gc','edit'); fd.append('id', g.id);
        fd.append('code', p[0]); fd.append('count', p[1]); fd.append('expire', p[2]);
        fd.append('item', p[3]); fd.append('option', p[4]);
        fetch(window.location.href.split('?')[0]+'?tab=giftcode', {method:'POST', body: fd})
        .then(()=>location.reload());
    }
    function editAcc(a){
        let f = prompt("Sửa tài khoản #"+a.id+" ("+a.username+")\nNhập: Password|Active(1/0)|Ban(1/0)|IsAdmin(1/0)|VND|TongNap|ServerLogin",
            a.password+"|"+a.active+"|"+a.ban+"|"+(a.is_admin||0)+"|"+a.vnd+"|"+a.tongnap+"|"+(a.server_login==null?-1:a.server_login));
        if(!f) return;
        let p = f.split('|');
        if(p.length < 7){ alert("Thiếu dữ liệu, cần 7 trường!"); return; }
        let fd = new URLSearchParams();
        fd.append('action_acc','editfull'); fd.append('accid', a.id);
        fd.append('npass', p[0]); fd.append('nactive', p[1]); fd.append('nban', p[2]);
        fd.append('nis_admin', p[3]); fd.append('nvnd', p[4]); fd.append('ntongnap', p[5]); fd.append('nserver_login', p[6]);
        fetch(window.location.href.split('?')[0]+'?tab=account', {method:'POST', body: fd})
        .then(()=>location.reload());
    }
</script>

</body>
</html>
