<?php
require_once "hidden/set.php";
// Chua dang nhap -> dua ve trang dang nhap
if (empty($_user)) {
    header("location:/login");
    exit;
}
// Dang nhap nhung khong phai admin
if ($user_arr["is_admin"] != 1) {
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Truy cập bị từ chối</title>';
    echo '<link rel="stylesheet" href="/assets/css/bootstrap.min.css"></head><body class="bg-light">';
    echo '<div class="container mt-5"><div class="alert alert-danger text-center"><h4>Bạn không có quyền truy cập trang này!</h4>';
    echo '<p>Tài khoản <b>' . $_username . '</b> không phải quản trị viên (is_admin = 0).</p>';
    echo '<a href="/" class="btn btn-primary">Về trang chủ</a></div></div></body></html>';
    exit;
}

// Java Server API Proxy for AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == 'proxy') {
    header('Content-Type: application/json; charset=UTF-8');
    $api_action = isset($_GET['action']) ? preg_replace('/[^a-z0-9_]/i', '', $_GET['action']) : 'info';
    
    if ($api_action === 'wipe_accounts') {
        $keep_user = isset_sql(trim($_GET['keep'] ?? $_username));
        _query("DELETE FROM player WHERE account_id IN (SELECT id FROM account WHERE is_admin != 1 AND username != '$keep_user')");
        _query("DELETE FROM account WHERE is_admin != 1 AND username != '$keep_user'");
        $count = mysqli_affected_rows($conn);
        echo json_encode([
            "status" => "success",
            "msg" => "Đã xóa $count tài khoản người chơi (đã giữ lại tài khoản Admin '$keep_user')!"
        ]);
        exit;
    }

    // Forward all query params except 'ajax' and 'action'
    $query_str = "";
    $allowed = array('key','val','name','name2','vnd','item','qty','opt_id','opt_param','task','subtask','msg','type','amount','val2','boss','id','keep','idx','slot','bag_index','tempid','power','gold','map','x','y',
        'head','level','tiemnang','hpg','mpg','dameg','defg','critg','gem','ruby',
        'pet_type','pet_gender','pet_name','pet_status','pet_power','pet_tiemnang','pet_hpg','pet_mpg','pet_dameg','pet_defg','pet_critg',
        'status','damg','gender',
        'main_id','main_index','main_count','side_id','side_count','side_max','side_left','side_level',
        'clan_id','clan_count','clan_max','clan_left','clan_level','kol_id','kol_count',
        'badge_id','days','use','hour','minute','auto_restart','h','m','on',
        'cfg','fk','table','sv',
        // cot du lieu cho tab Tinh nang (feat_save / phucloi_save)
        'options','items','name_tab','name_binh','thoi_gian','item_nhan','info','color',
        'id_item','option_id','param','key_item_id','item_options','tile_trung_thuong','des','enabled','start_at','end_at',
        'max_value','count','rank','max_amount','mob_id','leg','aura','icon','detail',
        'max_tab','id_tab','info_phucloi','feat_action','tich_luy','tab_id','max_count','active','list_item',
        // Lucky Wheel / New Player Gift / Online Gift params
        'player_id','prize_id','gem_cost','probability','minutes_required','max_day','gift_id',
        'account_id','milestone_id','vnd_amount','item_id','quantity');
    foreach ($_GET as $k => $v) {
        if ($k == 'ajax' || $k == 'action') continue;
        if (!in_array($k, $allowed)) continue;
        if ($query_str != "") $query_str .= "&";
        $query_str .= rawurlencode($k) . "=" . rawurlencode($v);
    }

    $url = $JAVA_API . "/" . $api_action;
    if ($query_str != "") $url .= "?" . $query_str; else $url .= "?key=" . rawurlencode($API_KEY);
    if ($query_str != "") $url .= "&key=" . rawurlencode($API_KEY);

    // Call Java API (HASHIRAMA WebAdminAPI - port 8080, auth bang api.key)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // fail nhanh neu port 8080 khong mo
    curl_setopt($ch, CURLOPT_TIMEOUT, 8); // cho toi da 8s de response cham hoan tat (tranh abort som)
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlerr = curl_error($ch);
    curl_close($ch);

    if ($response !== false && $response !== "" && $httpcode == 200) {
        echo $response;
    } else {
        $detail = $curlerr !== "" ? $curlerr : ("HTTP " . $httpcode);
        echo json_encode([
            "status" => "error",
            "msg" => "Không thể kết nối đến Máy Chủ Java (Port 8080): " . $detail
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

// Xử lý POST (Giftcode) - HASHIRAMA: bang gift_codes
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_gc'])) {
    $action = $_POST['action_gc'];
    // chuan hoa items: dam bao moi phan tu co "options" (GiftService bat buoc)
    $normalize_items = function ($json_str) {
        $arr = json_decode($json_str, true);
        if (!is_array($arr)) return null;
        $out = [];
        foreach ($arr as $e) {
            if (!isset($e['id'], $e['quantity'])) continue;
            $opts = isset($e['options']) && is_array($e['options']) ? array_values($e['options']) : [];
            $out[] = ["id" => (int)$e['id'], "quantity" => (int)$e['quantity'], "options" => $opts];
        }
        return json_encode($out);
    };
    if ($action === 'add') {
        $code = strtolower(isset_sql(trim($_POST['code'])));
        $item_json = $normalize_items(trim($_POST['item']));
        if ($item_json === null) {
            $msg = "JSON vật phẩm không hợp lệ!";
        } else {
            $gold = (int)($_POST['gold'] ?? 0);
            $gem = (int)($_POST['gem'] ?? 0);
            $ruby = (int)($_POST['ruby'] ?? 0);
            $type = (int)($_POST['gctype'] ?? 1);       // 0 = ca nhan (1 lan), 1 = tat ca moi nguoi
            $active = (int)!empty($_POST['gcactive']);  // 1 = can kich hoat tai khoan
            $expire = trim($_POST['expire']);
            $expire_sql = ($expire !== "") ? "'" . isset_sql($expire) . "'" : "NULL";
            $sql = "INSERT INTO gift_codes (`type`,`code`,`gold`,`gem`,`ruby`,`items`,`status`,`active`,`expires_at`,`created_at`,`updated_at`)
                    VALUES ($type,'$code',$gold,$gem,$ruby,'" . isset_sql($item_json) . "',0,$active,$expire_sql,NOW(),NOW())";
            $msg = _query($sql) ? "Thêm mã Giftcode '$code' thành công!" : "Lỗi khi thêm Giftcode (có thể trùng mã)!";
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $code = strtolower(isset_sql(trim($_POST['code'])));
        $item_json = $normalize_items(trim($_POST['item']));
        if ($item_json === null) {
            $msg = "JSON vật phẩm không hợp lệ!";
        } else {
            $gold = (int)($_POST['gold'] ?? 0);
            $gem = (int)($_POST['gem'] ?? 0);
            $ruby = (int)($_POST['ruby'] ?? 0);
            $active = (int)!empty($_POST['gcactive']);
            $expire = trim($_POST['expire']);
            $expire_sql = ($expire !== "") ? "'" . isset_sql($expire) . "'" : "NULL";
            $sql = "UPDATE gift_codes SET `code`='$code',`gold`=$gold,`gem`=$gem,`ruby`=$ruby,`items`='" . isset_sql($item_json) . "',`active`=$active,`expires_at`=$expire_sql,`updated_at`=NOW() WHERE id=$id";
            $msg = _query($sql) ? "Đã cập nhật mã '$code'!" : "Lỗi cập nhật!";
        }
    } elseif ($action === 'reset') {
        $id = (int)$_POST['id'];
        _query("DELETE FROM gift_code_histories WHERE gift_code_id = $id");
        _query("UPDATE gift_codes SET status = 0 WHERE id = $id");
        $msg = "Đã reset trạng thái + lịch sử sử dụng mã #$id!";
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        _query("DELETE FROM gift_code_histories WHERE gift_code_id = $id");
        if (_query("DELETE FROM gift_codes WHERE id = $id")) {
            $msg = "Xóa Giftcode thành công!";
        }
    }
}

// Lấy thống kê cho Web
$total_acc = _fetch("SELECT COUNT(*) as c FROM account")["c"];
$active_acc = _fetch("SELECT COUNT(*) as c FROM account WHERE active=1")["c"];
$total_vnd = _fetch("SELECT SUM(tongnap) as c FROM account")["c"] ?? 0;
$total_gc = _fetch("SELECT COUNT(*) as c FROM gift_codes")["c"];

$list_acc = [];
$q = mysqli_query($conn, "SELECT username, vnd, tongnap, active, ban FROM account ORDER BY tongnap DESC LIMIT 20");
while($row = mysqli_fetch_assoc($q)) $list_acc[] = $row;

$list_gc = [];
$q_gc = mysqli_query($conn, "SELECT * FROM gift_codes ORDER BY id DESC LIMIT 50");
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
        <a href="?tab=luckywheel" class="<?= $tab=='luckywheel'?'active':'' ?>"><i class="fa-solid fa-dharmachakra text-warning"></i> Vòng Quay May Mắn</a>
        <a href="?tab=newplayer" class="<?= $tab=='newplayer'?'active':'' ?>"><i class="fa-solid fa-gift text-success"></i> Quà Tân Thủ</a>
        <a href="?tab=onlinegift" class="<?= $tab=='onlinegift'?'active':'' ?>"><i class="fa-solid fa-clock text-info"></i> Quà Online</a>
        <a href="?tab=giftcode" class="<?= $tab=='giftcode'?'active':'' ?>"><i class="fa-solid fa-gift text-danger"></i> Quản Lý Giftcode</a>
        <a href="?tab=naprequest" class="<?= $tab=='naprequest'?'active':'' ?>"><i class="fa-solid fa-money-bill-wave text-success"></i> Yêu Cầu Nạp Tiền</a>
        <div class="sidebar-group">DỮ LIỆU GAME</div>
        <a href="?tab=itemdata" class="<?= $tab=='itemdata'?'active':'' ?>"><i class="fa-solid fa-box text-primary"></i> Dữ Liệu Vật Phẩm</a>
        <a href="?tab=mapdata" class="<?= $tab=='mapdata'?'active':'' ?>"><i class="fa-solid fa-map text-success"></i> Dữ Liệu Bản Đồ</a>
        <a href="?tab=part" class="<?= $tab=='part'?'active':'' ?>"><i class="fa-solid fa-shirt text-info"></i> Quản Lý Part</a>
        <a href="?tab=assets" class="<?= $tab=='assets'?'active':'' ?>"><i class="fa-solid fa-images text-success"></i> Xem Asset (NPC/Quái)</a>
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
                    callApi('maintenance_set_time&h=' + h + '&m=' + m + '&on=' + ar);
                }
                </script>
            </div>

            <!-- Boss Manager -->
            <div class="card p-3 mb-3">
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Boss Manager (Triệu Hồi & Cài Đặt)</h6>
                <a href="?tab=boss" class="btn btn-primary w-100 mb-2 fw-bold" style="padding: 10px;">Mở Menu Triệu Hồi (Search & Call Boss)</a>
                <div class="d-flex gap-2">
                    <button class="btn btn-danger w-33 fw-bold" style="padding: 10px;" onclick="if(confirm('Reset toàn bộ Boss?')) callApi('boss_reset')">Reset All Boss</button>
                    <button class="btn btn-success w-33 fw-bold" style="padding: 10px;" onclick="callApi('boss_respawn_resting')">Hồi Sinh Boss Nghỉ</button>
                    <button class="btn btn-warning w-33 fw-bold" style="padding: 10px;" onclick="killSelectedBoss()"><i class="fa-solid fa-skull"></i> Tắt Boss</button>
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
                <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Dữ Liệu</h6>
                <p class="text-muted m-0">Chức năng chuyển đổi kiểu dữ liệu chưa được kích hoạt.</p>
            </div>

            <script>
                // Update time clock
                setInterval(() => {
                    document.getElementById('lbTime').innerText = new Date().toLocaleTimeString('en-GB');
                }, 1000);

                // Fetch server stats every 2 seconds
                function fmtUptime(s){
                    s = parseInt(s)||0;
                    let h = Math.floor(s/3600), m = Math.floor((s%3600)/60), sec = s%60;
                    return (h>0? h+'h ' : '') + m + 'm ' + sec + 's';
                }
                function fetchStats() {
                    fetch('?ajax=proxy&action=info')
                    .then(res => res.json())
                    .then(data => {
                        if(data.status && data.status == 'error') return; // Server off

                        // HASHIRAMA: players_online / ram_used_mb / ram_max_mb / uptime_s
                        document.getElementById('lbOnline').innerText = data.players_online ?? 0;
                        document.getElementById('lbSessions').innerText = data.players_online ?? 0;
                        document.getElementById('lbThreads').innerText = data.threads ?? 0;

                        document.getElementById('lbCpu').innerText = data.cpu ?? 0;
                        document.getElementById('barCpu').style.width = (data.cpu ?? 0) + '%';

                        document.getElementById('lbRamUsed').innerText = data.ram_used_mb ?? 0;
                        document.getElementById('lbRamMax').innerText = data.ram_max_mb ?? 0;
                        let ramPerc = ((data.ram_used_mb||0) / (data.ram_max_mb||1)) * 100;
                        document.getElementById('barRam').style.width = ramPerc.toFixed(1) + '%';

                        document.getElementById('lbUptime').innerText = fmtUptime(data.uptime_s);

                        let dm = document.getElementById('lblDataMode');
                        if(dm) dm.innerText = 'x' + (data.exp_rate||'?') + ' EXP' + (data.event ? (' · Event #' + data.event) : '');

                        let botBtn = document.getElementById('btnBot');
                        if(botBtn) {
                            botBtn.innerText = 'BOT AI: ' + (data.bots_online !== undefined ? data.bots_online : '—');
                            botBtn.classList.remove('btn-secondary','btn-success');
                            botBtn.classList.add((data.bots_online||0) > 0 ? 'btn-success' : 'btn-secondary');
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
                        alert.classList.add((data.status == 'error' || data.success === false) ? 'alert-danger' : 'alert-success');
                        alert.innerText = data.message || data.msg || data.status || 'Xong';
                        setTimeout(() => alert.classList.add('d-none'), 3000);
                    });
                }

                function spawnBot(type, name) {
                    let amount = prompt("Nhập số lượng Bot " + name + " muốn tạo:", "5");
                    if (amount != null && amount !== "") {
                        if (isNaN(amount)) {
                            alert("Vui lòng nhập số!");
                            return;
                        }
                        // HASHIRAMA: bot_spawn chi nhan tham so val (so luong 1-50, bot he thong cu)
                        fetch('?ajax=proxy&action=' + (amount > 0 ? 'bot_spawn&val=' + amount : 'bot_remove_all'))
                        .then(res => res.json())
                        .then(data => {
                            let alert = document.getElementById('ajaxAlert');
                            alert.classList.remove('d-none', 'alert-danger', 'alert-success');
                            alert.classList.add((data.status == 'error' || data.success === false) ? 'alert-danger' : 'alert-success');
                            alert.innerText = data.message || data.msg || data.status || 'Xong';
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
                        alert.classList.add((data.status == 'error' || data.success === false) ? 'alert-danger' : 'alert-success');
                        alert.innerText = data.message || data.msg || data.status || 'Xong';
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
                    <table class="table table-bordered table-striped table-hover small">
                        <thead class="table-dark">
                            <tr><th>ID</th><th>Tên Nhân Vật</th><th>Sức Mạnh</th><th>Map</th><th>Loại</th><th>Hành Động</th></tr>
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
                    a.classList.add((data.status == 'error' || data.success === false) ? 'alert-danger' : 'alert-success');
                    a.innerText = data.message || data.msg || data.status || 'Xong';
                    setTimeout(() => a.classList.add('d-none'), 3000);
                }
                function playerGiveItem(){
                    let name = pvName(); if(!name){alert('Nhập tên nhân vật!');return;}
                    let item = document.getElementById('pv_item').value;
                    let qty = document.getElementById('pv_qty').value;
                    // HASHIRAMA: tham so la tempid (id item template)
                    fetch('?ajax=proxy&action=player_give_item&name='+encodeURIComponent(name)+'&tempid='+item+'&qty='+qty).then(r=>r.json()).then(showAlert);
                }
                function playerBuffVnd(){
                    let name = pvName(); let vnd = document.getElementById('pv_vnd').value;
                    if(!name){alert('Nhập tên nhân vật!');return;}
                    // HASHIRAMA: tham so la val
                    fetch('?ajax=proxy&action=player_buff_vnd&name='+encodeURIComponent(name)+'&val='+vnd).then(r=>r.json()).then(showAlert);
                }
                function playerMtv(){
                    let name = pvName(); if(!name){alert('Nhập tên nhân vật!');return;}
                    let lv = prompt('Cấp hội viên VIP (0-10):', '1');
                    if (lv === null) return;
                    fetch('?ajax=proxy&action=player_mtv&name='+encodeURIComponent(name)+'&level='+lv).then(r=>r.json()).then(showAlert);
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
                    // HASHIRAMA tra ve {players:[...]}
                    fetch('?ajax=proxy&action=player_list').then(r=>r.json()).then(data=>{
                        let tb = document.getElementById('pvTableBody'); tb.innerHTML='';
                        let term = document.getElementById('pvSearch').value.toLowerCase();
                        let shown = 0;
                        (Array.isArray(data)?data:(data.players||[])).forEach(p=>{
                            if(term && (p.name||'').toLowerCase().indexOf(term) < 0) return;
                            shown++;
                            let tr = document.createElement('tr');
                            let isBot = p.is_bot || p.isBot;
                            if(isBot) tr.className = 'table-light';
                            let mapStr = p.map || '?';
                            if(p.zone_id !== undefined && p.zone_id >= 0) mapStr += ' (#'+p.zone_id+')';
                            tr.innerHTML = '<td>'+p.id+'</td>'
                                +'<td><strong>'+esc(p.name)+'</strong></td>'
                                +'<td>'+numberFmt(p.power)+'</td>'
                                +'<td><small>'+esc(mapStr)+'</small></td>'
                                +'<td>'+(isBot?'<span class="badge bg-warning text-dark">BOT</span>':'<span class="badge bg-success">Player</span>')+'</td>'
                                +'<td>'
                                +'<button class="btn btn-xs btn-outline-primary me-1" onclick="plOpen('+p.id+')"><i class="fa-solid fa-user-pen"></i></button> '
                                +'<button class="btn btn-xs btn-outline-danger" onclick="kickName(\''+esc(p.name)+'\')">Kick</button>'
                                +'</td>';
                            tb.appendChild(tr);
                        });
                        document.getElementById('pvCount').innerText = shown;
                    }).catch(()=>{});
                }
                function esc(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
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
                        <thead class="table-dark"><tr><th>ID</th><th>Tên Boss</th><th>Map</th><th>Trạng Thái</th><th>Hành Động</th></tr></thead>
                        <tbody id="bossTableBody"></tbody>
                    </table>
                </div>
            </div>

            <script>
                let bossList = [];
                function loadBosses(){
                    // HASHIRAMA tra ve {alive:[...]}
                    fetch('?ajax=proxy&action=boss_list').then(r=>r.json()).then(data=>{
                        bossList = Array.isArray(data)?data:(data.alive||data.bosses||[]);
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
                        let stBadge = b.die
                            ? '<span class="badge bg-secondary">Chết</span>'
                            : '<span class="badge bg-success">Sống</span>';
                        let tr = document.createElement('tr');
                        tr.innerHTML = '<td>'+b.id+'</td><td>'+b.name+'</td>'
                            + '<td>'+(b.map||'?')+'</td>'
                            + '<td>'+stBadge+(b.state!==undefined?' <small class="text-muted">#'+b.state+'</small>':'')+'</td>'
                            + '<td><button class="btn btn-xs btn-outline-primary" onclick="summonOne('+b.id+')">Triệu Hồi</button> '
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
                        a.classList.add((data.status=='error'||data.success===false)?'alert-danger':'alert-success');
                        a.innerText = data.message||data.msg||data.status||'Xong';
                        setTimeout(()=>a.classList.add('d-none'),3000);
                    });
                }
                function killSelectedBoss(){
                    let id = document.getElementById('bossSelect').value;
                    if(!id){alert('Chon boss can tat!');return;}
                    if(!confirm('Tat boss id '+id+'?'))return;
                    fetch('?ajax=proxy&action=boss_kill&id='+id).then(r=>r.json()).then(data=>{
                        let a = document.getElementById('ajaxAlert');
                        a.classList.remove('d-none','alert-danger','alert-success');
                        a.classList.add((data.status=='error'||data.success===false)?'alert-danger':'alert-success');
                        a.innerText = data.message||data.msg||data.status||'Xong';
                        setTimeout(()=>a.classList.add('d-none'),3000);
                        loadBosses();
                    });
                }
                function killBoss(id){
                    if(!confirm('Tat boss id '+id+'?'))return;
                    fetch('?ajax=proxy&action=boss_kill&id='+id).then(r=>r.json()).then(data=>{
                        let a = document.getElementById('ajaxAlert');
                        a.classList.remove('d-none','alert-danger','alert-success');
                        a.classList.add((data.status=='error'||data.success===false)?'alert-danger':'alert-success');
                        a.innerText = data.message||data.msg||data.status||'Xong';
                        setTimeout(()=>a.classList.add('d-none'),3000);
                        loadBosses();
                    });
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
                <p class="small text-muted mb-2">Server HASHIRAMA chạy <b>1 sự kiện duy nhất</b> tại một thời điểm — bấm BẬT để chuyển sang sự kiện đó (server tự lưu vào <code>server.event</code>, không cần Lưu).</p>
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
                    // HASHIRAMA tra ve {current, success, events:[...]} - model 1 su kien active duy nhat
                    fetch('?ajax=proxy&action=event_list').then(r=>r.json()).then(data=>{
                        let list = data.events||[];
                        let cur = data.current;
                        let tb = document.getElementById('eventTableBody'); tb.innerHTML='';
                        let tg = document.getElementById('eventToggles'); tg.innerHTML='';
                        list.forEach(e=>{
                            if(e.id === 0 && !e.active) return;
                            e.active = e.active || (cur !== undefined && e.id === cur);
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
                            let btnText = e.active ? 'ĐANG BẬT' : 'BẬT';
                            col.innerHTML = '<div class="border rounded p-2 text-center'+(e.active?' border-success bg-light':'')+'">'
                                + '<i class="'+icon+' '+(e.active?'text-success':'text-muted')+' mb-1" style="font-size:20px"></i>'
                                + '<div class="small fw-bold mb-1">'+e.name+'</div>'
                                + '<button class="btn btn-sm '+btnClass+' w-100" onclick="toggleEvent('+e.id+')"'+(e.active?' disabled':'')+'>'+btnText+'</button>'
                                + '</div>';
                            tg.appendChild(col);
                        });
                    }).catch(()=>{});
                }

                function toggleEvent(id){
                    // HASHIRAMA: event_toggle&id=X -> dat event hien tai (server tu luu vao server.event)
                    fetch('?ajax=proxy&action=event_toggle&id='+id+'&val=1').then(r=>r.json()).then(data=>{
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
                    a.classList.add((data.status=='error'||data.success===false)?'alert-danger':'alert-success');
                    a.innerText = data.message||data.msg||data.status||'Xong';
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
                    // HASHIRAMA tra ve {success, consignments:[], message}
                    fetch('?ajax=proxy&action=consign_list').then(r=>r.json()).then(data=>{
                        let tb = document.getElementById('consignTableBody'); tb.innerHTML='';
                        let total=0, bot=0, sold=0;
                        let list = Array.isArray(data)?data:(data.consignments||[]);
                        list.forEach(it=>{
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
                            tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="fa-solid fa-basket-shopping opacity-50"></i> Chưa có tin đăng ký gửi nào</td></tr>';
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

        <?php if($tab == 'features'):
            // ban do icon: item_template.id -> icon_id (de hien anh trong cac bang)
            $FEAT_ICONS = [];
            $q = _query("SELECT id, icon_id FROM item_template");
            while($r = mysqli_fetch_assoc($q)) $FEAT_ICONS[(int)$r['id']] = (int)$r['icon_id'];
        ?>
            <h3 class="mb-4">Tính Năng Server (HASHIRAMA) <small class="text-muted fs-6">— sửa xong tự reload vào game ngay</small></h3>
            <div id="featAlert" class="alert alert-danger d-none" role="alert"></div>

            <ul class="nav nav-tabs mb-3" id="featTabs">
                <li class="nav-item"><a class="nav-link active" href="#" data-t="kng">Khảm Ngọc</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="ptn">Phòng Thí Nghiệm</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="rst">Rương Sưu Tầm</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="tb">Quà Tầm Bảo</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="vq">Mốc Vòng Quay</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="clt">Nhiệm Vụ Bang</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="cards">Thẻ Sưu Tầm</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-t="pl">Phúc Lợi</a></li>
            </ul>

            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="m-0 text-muted fw-bold" id="featTitle"></h6>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="loadFeat()"><i class="fa-solid fa-rotate"></i> Làm Mới</button>
                        <button class="btn btn-sm btn-primary" onclick="featAdd()">+ Thêm dòng</button>
                    </div>
                </div>
                <div style="max-height:600px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead class="table-dark"><tr id="featHead"></tr></thead>
                        <tbody id="featBody"></tbody>
                    </table>
                </div>
            </div>

            <script>
                const FEAT_ICONS = <?= json_encode($FEAT_ICONS) ?>;
                // cau hinh tung muc: fk cua API feat_*, cot duoc phep sua, cot chua item template id
                const FEAT_CFG = {
                    kng:  {name:'Khảm Ngọc',        fk:'kng',  cols:['options'],                                              itemCols:[]},
                    ptn:  {name:'Phòng Thí Nghiệm', fk:'ptn',  cols:['name_tab','name_binh','items','thoi_gian','item_nhan','info','color'], itemCols:['item_nhan']},
                    rst:  {name:'Rương Sưu Tầm',    fk:'rst',  cols:['id_item','option_id','param'],                          itemCols:['id_item']},
                    tb:   {name:'Quà Tầm Bảo',      fk:'tb',   cols:['key_item_id','item_id','quantity','item_options','tile_trung_thuong','des','enabled','start_at','end_at'], itemCols:['key_item_id','item_id']},
                    vq:   {name:'Mốc Vòng Quay',    fk:'vq',   cols:['max_value','item_id','quantity','item_options'],        itemCols:['item_id']},
                    clt:  {name:'Nhiệm Vụ Bang',    fk:'clt',  cols:['name','count'],                                         itemCols:[]},
                    cards:{name:'Thẻ Sưu Tầm',      fk:'cards',cols:['item_id','name','info','icon','rank','max_amount','type','mob_id','head','body','leg','bag','options','aura'], itemCols:['item_id'], directIcon:'icon'},
                    pl:   {name:'Phúc Lợi',         fk:null}
                };
                let featData = {};
                let curTab = 'kng';
                // feature_data tra ve theo ten bang -> map tu fk sang key json
                const FEAT_DATA_KEY = {kng:'kham_ngoc', ptn:'phong_thi_nghiem', rst:'ruong_suu_tam', tb:'tambao_items'};

                function featIconImg(tempId){
                    let ic = FEAT_ICONS[parseInt(tempId)];
                    if (ic === undefined || tempId === null || parseInt(tempId) <= 0) return '';
                    return '<img src="item_icon.php?id='+ic+'&size=3" width="36" height="36" class="border rounded me-1 align-middle" style="image-rendering:pixelated;" loading="lazy">';
                }

                function featAlert(msg, ok){
                    let a = document.getElementById('featAlert');
                    a.classList.remove('d-none','alert-danger','alert-success','alert-warning');
                    a.classList.add(ok ? 'alert-success' : 'alert-warning');
                    a.innerText = msg;
                    setTimeout(()=>a.classList.add('d-none'), 6000);
                }

                function loadFeat(){
                    let cfg = FEAT_CFG[curTab];
                    document.getElementById('featTitle').innerText = cfg.name;
                    if (curTab === 'pl') {
                        fetch('?ajax=proxy&action=phucloi_list').then(r=>r.json()).then(d=>{
                            featData.pl = d.phucloi || d['phuc_loi'] || [];
                            featData.pl_tabs = d.tabs || d.phuc_loi_tab || [];
                            renderTab();
                        }).catch(e=>featAlert('Lỗi tải phúc lợi: '+(e.message||e)));
                        return;
                    }
                    let ep = (curTab === 'kng' || curTab === 'ptn' || curTab === 'rst' || curTab === 'tb')
                        ? 'feature_data'
                        : 'feat_list&fk=' + cfg.fk;
                    fetch('?ajax=proxy&action='+ep, {cache:'no-store'}).then(r=>r.json()).then(d=>{
                        let key = FEAT_DATA_KEY[curTab];
                        let rows = Array.isArray(d) ? d : ((key && d[key]) || d.rows || d.list || []);
                        featData.rows = rows;
                        renderTab();
                    }).catch(e=>featAlert('Lỗi tải dữ liệu: '+(e.message||e)));
                }

                function renderTab(){
                    let head = document.getElementById('featHead');
                    let body = document.getElementById('featBody');
                    head.innerHTML=''; body.innerHTML='';
                    let rows = (curTab === 'pl') ? featData.pl || [] : (featData.rows || []);
                    let cfg = FEAT_CFG[curTab];
                    if(!rows || rows.length===0){
                        body.innerHTML='<tr><td colspan="6" class="text-center text-muted py-4">Không có dữ liệu</td></tr>';
                        return;
                    }
                    let keys = Object.keys(rows[0]);
                    let th = '<th>#</th>';
                    keys.forEach(k=>{ th += '<th>'+k+'</th>'; });
                    th += '<th>Hành Động</th>';
                    head.innerHTML = th;
                    rows.forEach((r,i)=>{
                        let tr = document.createElement('tr');
                        let html = '<td>'+(i+1)+'</td>';
                        keys.forEach(k=>{
                            let v = r[k]==null?'':String(r[k]);
                            if (v.length > 50) { html += '<td title="'+v.replace(/"/g,'&quot;')+'" class="small">'+v.substring(0,50)+'…</td>'; }
                            else {
                                let cell = v;
                                if (cfg.itemCols && cfg.itemCols.indexOf(k) >= 0) cell = featIconImg(v) + v;
                                if (cfg.directIcon === k) cell = '<img src="item_icon.php?id='+v+'&size=3" width="36" height="36" class="border rounded align-middle" style="image-rendering:pixelated;" loading="lazy"> ' + v;
                                html += '<td>'+cell+'</td>';
                            }
                        });
                        html += '<td><button class="btn btn-sm btn-outline-primary" onclick=\'featEdit('+JSON.stringify(r).replace(/'/g,"&#39;")+')\'>Sửa</button> '
                              + '<button class="btn btn-sm btn-outline-danger" onclick="featDel('+(r.id!==undefined?r.id:i)+')">Xóa</button></td>';
                        tr.innerHTML = html;
                        body.appendChild(tr);
                    });
                }

                function featEdit(row){
                    let cfg = FEAT_CFG[curTab];
                    if (curTab === 'pl') {
                        let f = prompt("Sửa phúc lợi #"+row.id+"\nNhập: Tên|MaxTab|IdTab|Info|Action|TichLuy",
                            [row.name,row.max_tab,row.id_tab,(row.info_phucloi||''),row.action,(row.tich_luy||'')].join('|'));
                        if(!f) return;
                        let p = f.split('|');
                        let fd = new URLSearchParams();
                        fd.append('action','phucloi_save'); fd.append('table','phuc_loi'); fd.append('id',row.id);
                        fd.append('name',p[0]); fd.append('max_tab',p[1]); fd.append('id_tab',p[2]);
                        fd.append('info_phucloi',p[3]); fd.append('feat_action',p[4]); fd.append('tich_luy',p[5]);
                        proxyPost(fd); return;
                    }
                    let pairs = cfg.cols.map(c=>c+'='+((row[c]!==undefined&&row[c]!==null)?row[c]:'')).join('|');
                    let f = prompt("Sửa "+cfg.name+" #"+(row.id!==undefined?row.id:'')+"\nĐịnh dạng: cot1=giatri|cot2=giatri\nCột được phép: "+cfg.cols.join(', '), pairs);
                    if(!f) return;
                    let fd = new URLSearchParams();
                    fd.append('action','feat_save'); fd.append('fk',cfg.fk);
                    if (row.id !== undefined) fd.append('id',row.id);
                    f.split('|').forEach(pr=>{
                        let eq = pr.indexOf('=');
                        if (eq > 0) {
                            let k = pr.substring(0,eq).trim(), v = pr.substring(eq+1);
                            if (cfg.cols.indexOf(k) >= 0) fd.append(k, v);
                        }
                    });
                    proxyPost(fd);
                }

                function featAdd(){
                    let cfg = FEAT_CFG[curTab];
                    if (!cfg.fk) { featAlert('Phúc lợi: hãy sửa các dòng có sẵn (thêm dòng mới cần thêm trong DB)', false); return; }
                    let f = prompt("Thêm dòng "+cfg.name+"\nĐịnh dạng: cot1=giatri|cot2=giatri\nCột được phép: "+cfg.cols.join(', '));
                    if(!f) return;
                    let fd = new URLSearchParams();
                    fd.append('action','feat_add'); fd.append('fk',cfg.fk);
                    f.split('|').forEach(pr=>{
                        let eq = pr.indexOf('=');
                        if (eq > 0) {
                            let k = pr.substring(0,eq).trim(), v = pr.substring(eq+1);
                            if (cfg.cols.indexOf(k) >= 0) fd.append(k, v);
                        }
                    });
                    proxyPost(fd);
                }

                function featDel(id){
                    let cfg = FEAT_CFG[curTab];
                    if (!cfg.fk || !confirm('Xóa dòng id '+id+' của '+cfg.name+'?')) return;
                    let fd = new URLSearchParams();
                    fd.append('action','feat_del'); fd.append('fk',cfg.fk); fd.append('id',id);
                    proxyPost(fd);
                }

                function proxyPost(fd){
                    // proxy chi nhan GET -> chuyen form thanh query string
                    let qs = new URLSearchParams();
                    for (const [k,v] of fd) qs.append(k,v);
                    fetch('?ajax=proxy&' + qs.toString())
                    .then(r=>r.text().then(t=>{
                        let a = document.getElementById('featAlert');
                        try {
                            let d = JSON.parse(t.trim());
                            featAlert(d.msg || d.message || d.status || 'Xong', !(d.status === 'error'));
                        } catch(e2){ featAlert(t.trim().substring(0,150), false); }
                        loadFeat();
                    })).catch(e=>featAlert('Lỗi kết nối: '+(e.message||e), false));
                }

                document.querySelectorAll('#featTabs a').forEach(a=>{
                    a.addEventListener('click',e=>{
                        e.preventDefault();
                        document.querySelectorAll('#featTabs a').forEach(x=>x.classList.remove('active'));
                        a.classList.add('active');
                        curTab=a.dataset.t;
                        featData.rows = null; featData.pl = null;
                        renderTab(); loadFeat();
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

        <?php if($tab == 'assets'): ?>
            <?php include __DIR__ . '/admin_tabs/assets.php'; ?>
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
                                alert(d.msg || d.message || d.status || 'Hoàn tất!');
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fa-solid fa-radiation"></i> XÓA TOÀN BỘ TÀI KHOẢN NGƯỜI CHƠI';
                                window.location.reload();
                            }).catch(e=>{ alert('Lỗi kết nối: '+e); btn.disabled=false; btn.innerHTML = '<i class="fa-solid fa-radiation"></i> XÓA TOÀN BỘ TÀI KHOẢN NGƯỜI CHƠI'; });
                    }
                    function editAcc(data) {
                        document.getElementById('ea_id').value = data.id;
                        document.getElementById('ea_username').innerText = data.username;
                        document.getElementById('ea_vnd').value = data.vnd || 0;
                        document.getElementById('ea_tongnap').value = data.tongnap || 0;
                        document.getElementById('ea_active').value = data.active || 0;
                        document.getElementById('ea_ban').value = data.ban || 0;
                        document.getElementById('ea_isadmin').value = data.is_admin || 0;
                        let m = new bootstrap.Modal(document.getElementById('modalEditAcc'));
                        m.show();
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
                                        <button class="btn btn-sm btn-outline-primary" onclick='editAcc(<?= json_encode(array("id"=>$row["id"],"username"=>$row["username"],"password"=>$row["password"],"active"=>$row["active"],"ban"=>$row["ban"],"is_admin"=>$row["is_admin"]??0,"vnd"=>$row["vnd"]??0,"tongnap"=>$row["tongnap"]??0,"server_login"=>$row["server_login"]??-1), JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i> Sửa</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Sửa Tài Khoản -->
            <div class="modal fade" id="modalEditAcc" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <input type="hidden" name="action_acc" value="editfull">
                            <input type="hidden" name="accid" id="ea_id">
                            <div class="modal-header">
                                <h5 class="modal-title">Sửa Tài Khoản: <span id="ea_username" class="text-primary fw-bold"></span></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label class="form-label mb-1">Mật Khẩu Mới (để trống nếu không đổi)</label>
                                    <input type="text" class="form-control" name="npass" placeholder="Nhập mật khẩu mới...">
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">Số VNĐ</label>
                                        <input type="number" class="form-control" name="nvnd" id="ea_vnd">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">Tổng Nạp</label>
                                        <input type="number" class="form-control" name="ntongnap" id="ea_tongnap">
                                    </div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Thành Viên</label>
                                        <select class="form-select" name="nactive" id="ea_active">
                                            <option value="0">Chưa mở</option>
                                            <option value="1">Đã mở</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Khóa TK</label>
                                        <select class="form-select" name="nban" id="ea_ban">
                                            <option value="0">Không khóa</option>
                                            <option value="1">Khóa</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-1">Quyền Admin</label>
                                        <select class="form-select" name="nis_admin" id="ea_isadmin">
                                            <option value="0">Người chơi</option>
                                            <option value="1">Admin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-primary">Lưu Cập Nhật</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if($tab == 'luckywheel'): ?>
            <h3 class="mb-4"><i class="fa-solid fa-dharmachakra text-warning"></i> Vòng Quay May Mắn</h3>
            <div id="lwAlert" class="alert alert-success d-none" role="alert"></div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card p-3 text-center border-warning">
                        <div class="fs-3 fw-bold text-warning" id="lwTotal">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-gift"></i> Tổng giải thưởng</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center border-info">
                        <div class="fs-3 fw-bold text-info" id="lwSpins">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-rotate"></i> Lượt quay hôm nay</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center border-primary">
                        <div class="fs-3 fw-bold text-primary" id="lwCost">100</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-gem"></i> Ngọc/lượt quay</div>
                    </div>
                </div>
            </div>

            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="m-0 text-muted fw-bold">Danh Sách Giải Thưởng</h6>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="loadLW()"><i class="fa-solid fa-rotate"></i> Làm Mới</button>
                        <button class="btn btn-sm btn-primary" onclick="lwAdd()">+ Thêm Giải</button>
                    </div>
                </div>
                <div style="max-height:400px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead class="table-dark"><tr><th>ID</th><th>Item ID</th><th>Số Lượng</th><th>Tỷ Lệ</th><th>Ngọc</th><th>Bật/Tắt</th><th>Hành Động</th></tr></thead>
                        <tbody id="lwBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="card p-3">
                <h6 class="m-0 text-muted fw-bold border-bottom pb-2 mb-2">Lịch Sử Quay (50 gần nhất)</h6>
                <div style="max-height:300px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="table-dark"><tr><th>Thời Gian</th><th>Nickname</th><th>Item</th><th>Số Lượng</th></tr></thead>
                        <tbody id="lwHistory"></tbody>
                    </table>
                </div>
            </div>

            <script>
                function lwShow(a){let e=document.getElementById('lwAlert');e.classList.remove('d-none','alert-danger','alert-success');e.classList.add(a.success?'alert-success':'alert-danger');e.innerText=a.message||a.msg||'Xong';setTimeout(()=>e.classList.add('d-none'),3000);}
                function loadLW(){
                    fetch('?ajax=proxy&action=lucky_wheel_list').then(r=>r.json()).then(d=>{
                        let list=d.list||[];document.getElementById('lwTotal').innerText=list.length;
                        let tb=document.getElementById('lwBody');tb.innerHTML='';
                        list.forEach(r=>{
                            let bg=r.item_id==-1?'text-warning':r.item_id==-2?'text-info':'';
                            let nm=r.item_id==-1?'Vàng':r.item_id==-2?'Ngọc':'Item #'+r.item_id;
                            tb.innerHTML+='<tr><td>'+r.id+'</td><td class="'+bg+'">'+nm+'</td><td>'+r.quantity+'</td><td>'+r.probability+'</td><td>'+r.gem_cost+'</td>'
                                +'<td>'+(r.enabled?'<span class="badge bg-success">Bật</span>':'<span class="badge bg-secondary">Tắt</span>')+'</td>'
                                +'<td><button class="btn btn-xs btn-outline-primary" onclick="lwEdit('+r.id+','+r.item_id+','+r.quantity+','+r.probability+','+r.gem_cost+','+r.enabled+')">Sửa</button> '
                                +'<button class="btn btn-xs btn-outline-danger" onclick="lwDel('+r.id+')">Xóa</button></td></tr>';
                        });
                    });
                    fetch('?ajax=proxy&action=lucky_wheel_history').then(r=>r.json()).then(d=>{
                        let list=d.list||[];document.getElementById('lwSpins').innerText=list.length;
                        let tb=document.getElementById('lwHistory');tb.innerHTML='';
                        list.forEach(r=>{
                            let nm=r.item_id==-1?'Vàng':r.item_id==-2?'Ngọc':'Item #'+r.item_id;
                            tb.innerHTML+='<tr><td>'+r.spun_at+'</td><td>'+r.player_name+'</td><td>'+nm+'</td><td>'+r.quantity+'</td></tr>';
                        });
                    });
                }
                function lwAdd(){
                    let f=prompt("Thêm giải thưởng mới\nĐịnh dạng: item_id|quantity|probability|gem_cost|enabled\n(-1=Vàng, -2=Ngọc)","-1|100000|1000|100|1");
                    if(!f)return;let p=f.split('|');
                    fetch('?ajax=proxy&action=lucky_wheel_save&item_id='+p[0]+'&quantity='+p[1]+'&probability='+p[2]+'&gem_cost='+p[3]+'&enabled='+(p[4]||1)).then(r=>r.json()).then(d=>{lwShow(d);loadLW();});
                }
                function lwEdit(id,iid,qty,prob,cost,en){
                    let f=prompt("Sửa giải #"+id+"\nitem_id|quantity|probability|gem_cost|enabled",iid+'|'+qty+'|'+prob+'|'+cost+'|'+en);
                    if(!f)return;let p=f.split('|');
                    fetch('?ajax=proxy&action=lucky_wheel_save&id='+id+'&item_id='+p[0]+'&quantity='+p[1]+'&probability='+p[2]+'&gem_cost='+p[3]+'&enabled='+(p[4]||1)).then(r=>r.json()).then(d=>{lwShow(d);loadLW();});
                }
                function lwDel(id){if(!confirm('Xóa giải #'+id+'?'))return;fetch('?ajax=proxy&action=lucky_wheel_delete&id='+id).then(r=>r.json()).then(d=>{lwShow(d);loadLW();});}
                loadLW();
            </script>
        <?php endif; ?>

        <?php if($tab == 'newplayer'): ?>
            <h3 class="mb-4"><i class="fa-solid fa-gift text-success"></i> Quản Lý Quà Tân Thủ</h3>
            <div id="npAlert" class="alert alert-success d-none" role="alert"></div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="card p-3 text-center border-success">
                        <div class="fs-3 fw-bold text-success" id="npTotal">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-gift"></i> Tổng phần quà</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 text-center border-info">
                        <div class="fs-3 fw-bold text-info" id="npClaims">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-check"></i> Đã nhận hôm nay</div>
                    </div>
                </div>
            </div>

            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="m-0 text-muted fw-bold">Danh Sách Quà</h6>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="loadNP()"><i class="fa-solid fa-rotate"></i> Làm Mới</button>
                        <button class="btn btn-sm btn-primary" onclick="npAdd()">+ Thêm Quà</button>
                    </div>
                </div>
                <div style="max-height:400px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead class="table-dark"><tr><th>ID</th><th>Item ID</th><th>Số Lượng</th><th>Ngày Tối Đa</th><th>Bật/Tắt</th><th>Hành Động</th></tr></thead>
                        <tbody id="npBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="card p-3">
                <h6 class="m-0 text-muted fw-bold border-bottom pb-2 mb-2">Lịch Sử Nhận (50 gần nhất)</h6>
                <div style="max-height:300px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="table-dark"><tr><th>Thời Gian</th><th>Nickname</th><th>Player ID</th></tr></thead>
                        <tbody id="npHistory"></tbody>
                    </table>
                </div>
            </div>

            <script>
                function npShow(a){let e=document.getElementById('npAlert');e.classList.remove('d-none','alert-danger','alert-success');e.classList.add(a.success?'alert-success':'alert-danger');e.innerText=a.message||a.msg||'Xong';setTimeout(()=>e.classList.add('d-none'),3000);}
                function loadNP(){
                    fetch('?ajax=proxy&action=new_player_gift_list').then(r=>r.json()).then(d=>{
                        let list=d.list||[];document.getElementById('npTotal').innerText=list.length;
                        let tb=document.getElementById('npBody');tb.innerHTML='';
                        list.forEach(r=>{
                            let bg=r.item_id==-1?'text-warning':r.item_id==-2?'text-info':'';
                            let nm=r.item_id==-1?'Vàng':r.item_id==-2?'Ngọc':'Item #'+r.item_id;
                            tb.innerHTML+='<tr><td>'+r.id+'</td><td class="'+bg+'">'+nm+'</td><td>'+r.quantity+'</td><td>'+r.max_day+' ngày</td>'
                                +'<td>'+(r.enabled?'<span class="badge bg-success">Bật</span>':'<span class="badge bg-secondary">Tắt</span>')+'</td>'
                                +'<td><button class="btn btn-xs btn-outline-primary" onclick="npEdit('+r.id+','+r.item_id+','+r.quantity+','+r.max_day+','+r.enabled+')">Sửa</button> '
                                +'<button class="btn btn-xs btn-outline-danger" onclick="npDel('+r.id+')">Xóa</button></td></tr>';
                        });
                    });
                    fetch('?ajax=proxy&action=new_player_gift_history').then(r=>r.json()).then(d=>{
                        let list=d.list||[];document.getElementById('npClaims').innerText=list.length;
                        let tb=document.getElementById('npHistory');tb.innerHTML='';
                        list.forEach(r=>{tb.innerHTML+='<tr><td>'+r.claimed_at+'</td><td>'+r.player_name+'</td><td>'+r.player_id+'</td></tr>';});
                    });
                }
                function npAdd(){
                    let f=prompt("Thêm quà tân thủ\nĐịnh dạng: item_id|quantity|max_day|enabled\n(-1=Vàng, -2=Ngọc)","-1|10000000|7|1");
                    if(!f)return;let p=f.split('|');
                    fetch('?ajax=proxy&action=new_player_gift_save&item_id='+p[0]+'&quantity='+p[1]+'&max_day='+p[2]+'&enabled='+(p[3]||1)).then(r=>r.json()).then(d=>{npShow(d);loadNP();});
                }
                function npEdit(id,iid,qty,md,en){
                    let f=prompt("Sửa quà #"+id+"\nitem_id|quantity|max_day|enabled",iid+'|'+qty+'|'+md+'|'+en);
                    if(!f)return;let p=f.split('|');
                    fetch('?ajax=proxy&action=new_player_gift_save&id='+id+'&item_id='+p[0]+'&quantity='+p[1]+'&max_day='+p[2]+'&enabled='+(p[3]||1)).then(r=>r.json()).then(d=>{npShow(d);loadNP();});
                }
                function npDel(id){if(!confirm('Xóa quà #'+id+'?'))return;fetch('?ajax=proxy&action=new_player_gift_delete&id='+id).then(r=>r.json()).then(d=>{npShow(d);loadNP();});}
                loadNP();
            </script>
        <?php endif; ?>

        <?php if($tab == 'onlinegift'): ?>
            <h3 class="mb-4"><i class="fa-solid fa-clock text-info"></i> Quản Lý Quà Online</h3>
            <div id="ogAlert" class="alert alert-success d-none" role="alert"></div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card p-3 text-center border-info">
                        <div class="fs-3 fw-bold text-info" id="ogTotal">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-clock"></i> Tổng mốc quà</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center border-success">
                        <div class="fs-3 fw-bold text-success" id="ogClaims">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-check"></i> Đã nhận hôm nay</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center border-warning">
                        <div class="fs-3 fw-bold text-warning" id="ogPlayers">0</div>
                        <div class="text-muted small fw-bold"><i class="fa-solid fa-users"></i> Player online</div>
                    </div>
                </div>
            </div>

            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="m-0 text-muted fw-bold">Danh Sách Mốc Quà</h6>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="loadOG()"><i class="fa-solid fa-rotate"></i> Làm Mới</button>
                        <button class="btn btn-sm btn-primary" onclick="ogAdd()">+ Thêm Mốc</button>
                    </div>
                </div>
                <div style="max-height:400px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead class="table-dark"><tr><th>ID</th><th>Item ID</th><th>Số Lượng</th><th>Phút Online</th><th>Bật/Tắt</th><th>Hành Động</th></tr></thead>
                        <tbody id="ogBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="card p-3">
                <h6 class="m-0 text-muted fw-bold border-bottom pb-2 mb-2">Lịch Sử Nhận (50 gần nhất)</h6>
                <div style="max-height:300px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="table-dark"><tr><th>Thời Gian</th><th>Nickname</th><th>Mốc</th><th>Player ID</th></tr></thead>
                        <tbody id="ogHistory"></tbody>
                    </table>
                </div>
            </div>

            <script>
                function ogShow(a){let e=document.getElementById('ogAlert');e.classList.remove('d-none','alert-danger','alert-success');e.classList.add(a.success?'alert-success':'alert-danger');e.innerText=a.message||a.msg||'Xong';setTimeout(()=>e.classList.add('d-none'),3000);}
                function loadOG(){
                    fetch('?ajax=proxy&action=online_gift_list').then(r=>r.json()).then(d=>{
                        let list=d.list||[];document.getElementById('ogTotal').innerText=list.length;
                        let tb=document.getElementById('ogBody');tb.innerHTML='';
                        list.forEach(r=>{
                            let bg=r.item_id==-1?'text-warning':r.item_id==-2?'text-info':'';
                            let nm=r.item_id==-1?'Vàng':r.item_id==-2?'Ngọc':'Item #'+r.item_id;
                            tb.innerHTML+='<tr><td>'+r.id+'</td><td class="'+bg+'">'+nm+'</td><td>'+r.quantity+'</td><td>'+r.minutes_required+' phút</td>'
                                +'<td>'+(r.enabled?'<span class="badge bg-success">Bật</span>':'<span class="badge bg-secondary">Tắt</span>')+'</td>'
                                +'<td><button class="btn btn-xs btn-outline-primary" onclick="ogEdit('+r.id+','+r.item_id+','+r.quantity+','+r.minutes_required+','+r.enabled+')">Sửa</button> '
                                +'<button class="btn btn-xs btn-outline-danger" onclick="ogDel('+r.id+')">Xóa</button></td></tr>';
                        });
                    });
                    fetch('?ajax=proxy&action=online_gift_history').then(r=>r.json()).then(d=>{
                        let list=d.list||[];document.getElementById('ogClaims').innerText=list.length;
                        let tb=document.getElementById('ogHistory');tb.innerHTML='';
                        list.forEach(r=>{tb.innerHTML+='<tr><td>'+r.claimed_at+'</td><td>'+r.player_name+'</td><td>#'+r.gift_id+'</td><td>'+r.player_id+'</td></tr>';});
                    });
                    fetch('?ajax=proxy&action=info').then(r=>r.json()).then(d=>{document.getElementById('ogPlayers').innerText=d.players_online||0;});
                }
                function ogAdd(){
                    let f=prompt("Thêm mốc quà online\nĐịnh dạng: item_id|quantity|minutes_required|enabled\n(-1=Vàng, -2=Ngọc)","-1|500000|30|1");
                    if(!f)return;let p=f.split('|');
                    fetch('?ajax=proxy&action=online_gift_save&item_id='+p[0]+'&quantity='+p[1]+'&minutes_required='+p[2]+'&enabled='+(p[3]||1)).then(r=>r.json()).then(d=>{ogShow(d);loadOG();});
                }
                function ogEdit(id,iid,qty,min,en){
                    let f=prompt("Sửa mốc #"+id+"\nitem_id|quantity|minutes_required|enabled",iid+'|'+qty+'|'+min+'|'+en);
                    if(!f)return;let p=f.split('|');
                    fetch('?ajax=proxy&action=online_gift_save&id='+id+'&item_id='+p[0]+'&quantity='+p[1]+'&minutes_required='+p[2]+'&enabled='+(p[3]||1)).then(r=>r.json()).then(d=>{ogShow(d);loadOG();});
                }
                function ogDel(id){if(!confirm('Xóa mốc #'+id+'?'))return;fetch('?ajax=proxy&action=online_gift_delete&id='+id).then(r=>r.json()).then(d=>{ogShow(d);loadOG();});}
                loadOG();
            </script>
        <?php endif; ?>

        <?php if($tab == 'giftcode'): ?>
            <h3 class="mb-4">Quản Lý Giftcode <small class="text-muted fs-6">(bảng gift_codes - server đọc trực tiếp, không cần reload)</small></h3>
            <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card p-3">
                        <h5>Thêm Mã Mới</h5>
                        <form method="POST">
                            <input type="text" class="form-control mb-2" name="code" placeholder="Mã Giftcode (chữ+số)" required>
                            <textarea class="form-control mb-2" name="item" id="gcItemInput" placeholder='Vật phẩm JSON: [{"id":457,"quantity":10,"options":[{"id":73,"param":10}]}]' oninput="gcPreview()"></textarea>
                            <div id="gcPreview" class="d-flex flex-wrap gap-1 mb-2"></div>
                            <div class="row mb-2">
                                <div class="col"><input type="number" class="form-control" name="gold" placeholder="Vàng" value="0"></div>
                                <div class="col"><input type="number" class="form-control" name="gem" placeholder="Ngọc xanh" value="0"></div>
                                <div class="col"><input type="number" class="form-control" name="ruby" placeholder="Hồng ngọc" value="0"></div>
                            </div>
                            <select class="form-control mb-2" name="gctype">
                                <option value="1">Mọi người dùng được (mỗi người 1 lần)</option>
                                <option value="0">Cá nhân - dùng 1 lần rồi hết</option>
                            </select>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="gcactive" id="gcactive">
                                <label class="form-check-label" for="gcactive">Yêu cầu tài khoản đã kích hoạt</label>
                            </div>
                            <input type="datetime-local" class="form-control mb-3" name="expire">
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
                                    <th>Quà</th>
                                    <th>Loại</th>
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
                                $res = _query("SELECT * FROM gift_codes ORDER BY id DESC LIMIT 50");
                                while($row = mysqli_fetch_assoc($res)):
                                    $used = $row['status'] == 1;
                                ?>
                                <tr class="<?= $used ? 'table-secondary' : '' ?>">
                                    <td><?= $row['id'] ?></td>
                                    <td><strong class="text-danger"><?= htmlspecialchars($row['code']) ?></strong><?= $used ? '<br><span class="badge bg-secondary">Đã dùng</span>' : '' ?></td>
                                    <td class="align-middle">
                                        <?= gcItemImgs($row['items'], $imap) ?>
                                        <?php if(($row['gold']??0)>0): ?><span class="badge bg-warning text-dark">+<?= number_format($row['gold']) ?> vàng</span><?php endif; ?>
                                        <?php if(($row['gem']??0)>0): ?><span class="badge bg-info">+<?= number_format($row['gem']) ?> ngọc</span><?php endif; ?>
                                        <?php if(($row['ruby']??0)>0): ?><span class="badge bg-danger">+<?= number_format($row['ruby']) ?> hồng</span><?php endif; ?>
                                    </td>
                                    <td><?= $row['type']==0 ? '<span class="badge bg-dark">Cá nhân</span>' : '<span class="badge bg-primary">Mọi người</span>' ?><?= $row['active'] ? '<br><span class="badge bg-warning text-dark">Cần KT</span>' : '' ?></td>
                                    <td><?= $row['expires_at'] ?? 'Không hạn' ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick='editGc(<?= json_encode(array('id'=>$row['id'],'code'=>$row['code'],'expired'=>$row['expires_at'],'items'=>$row['items'],'gold'=>(int)$row['gold'],'gem'=>(int)$row['gem'],'ruby'=>(int)$row['ruby'],'active'=>(int)$row['active']), JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button class="btn btn-sm btn-outline-warning" name="action_gc" value="reset" title="Reset lượt dùng + lịch sử" onclick="return confirm('Reset mã này?')"><i class="fa-solid fa-arrow-rotate-left"></i></button>
                                        </form>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button class="btn btn-sm btn-danger" name="action_gc" value="delete" onclick="return confirm('Xóa mã này?')"><i class="fa-solid fa-trash"></i></button>
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

        <?php if($tab == 'naprequest'): ?>
            <h3 class="mb-4"><i class="fa-solid fa-money-bill-wave text-success"></i> Yêu Cầu Nạp Tiền</h3>
            <div id="nrAlert" class="alert alert-success d-none" role="alert"></div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="card p-3 text-center border-warning">
                        <div class="fs-3 fw-bold text-warning" id="nrPending">0</div>
                        <div class="text-muted small fw-bold">Chờ duyệt</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center border-success">
                        <div class="fs-3 fw-bold text-success" id="nrApproved">0</div>
                        <div class="text-muted small fw-bold">Đã duyệt</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center border-danger">
                        <div class="fs-3 fw-bold text-danger" id="nrRejected">0</div>
                        <div class="text-muted small fw-bold">Từ chối</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card p-3 text-center border-primary">
                        <div class="fs-3 fw-bold text-primary" id="nrTotal">0</div>
                        <div class="text-muted small fw-bold">Tổng yêu cầu</div>
                    </div>
                </div>
            </div>

            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="m-0 text-muted fw-bold">Danh Sách Yêu Cầu</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadNR()"><i class="fa-solid fa-rotate"></i> Làm Mới</button>
                </div>
                <div style="max-height:600px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-hover table-sm">
                        <thead class="table-dark"><tr><th>ID</th><th>Tài Khoản</th><th>Số Tiền</th><th>Trạng Thái</th><th>Ngày Gửi</th><th>Hành Động</th></tr></thead>
                        <tbody id="nrBody"></tbody>
                    </table>
                </div>
            </div>

            <script>
                function nrShow(a){let e=document.getElementById('nrAlert');e.classList.remove('d-none','alert-danger','alert-success');e.classList.add(a.success?'alert-success':'alert-danger');e.innerText=a.message||a.msg||'Xong';setTimeout(()=>e.classList.add('d-none'),3000);}
                function loadNR(){
                    fetch('?ajax=proxy&action=nap_request_list').then(r=>r.json()).then(d=>{
                        let list=d.list||[];
                        let pending=list.filter(r=>r.status==='pending').length;
                        let approved=list.filter(r=>r.status==='approved').length;
                        let rejected=list.filter(r=>r.status==='rejected').length;
                        document.getElementById('nrPending').innerText=pending;
                        document.getElementById('nrApproved').innerText=approved;
                        document.getElementById('nrRejected').innerText=rejected;
                        document.getElementById('nrTotal').innerText=list.length;
                        let tb=document.getElementById('nrBody');tb.innerHTML='';
                        list.forEach(r=>{
                            let statusBadge=r.status==='pending'?'<span class="badge bg-warning text-dark">Chờ duyệt</span>'
                                :r.status==='approved'?'<span class="badge bg-success">Đã duyệt</span>'
                                :'<span class="badge bg-danger">Từ chối</span>';
                            let action='';
                            if(r.status==='pending'){
                                action='<button class="btn btn-xs btn-success" onclick="nrApprove('+r.id+')">✅ Duyệt</button> '
                                    +'<button class="btn btn-xs btn-danger" onclick="nrReject('+r.id+')">❌ Từ chối</button>';
                            }
                            tb.innerHTML+='<tr><td>'+r.id+'</td><td>'+r.username+' (#'+r.account_id+')</td>'
                                +'<td class="fw-bold text-danger">'+Number(r.amount).toLocaleString()+' VNĐ</td>'
                                +'<td>'+statusBadge+'</td><td>'+r.created_at+'</td><td>'+action+'</td></tr>';
                        });
                    });
                }
                function nrApprove(id){if(!confirm('Duyệt yêu cầu #'+id+'?\nTiền sẽ được cộng vào tài khoản.'))return;fetch('?ajax=proxy&action=nap_request_approve&val='+id).then(r=>r.json()).then(d=>{nrShow(d);loadNR();});}
                function nrReject(id){if(!confirm('Từ chối yêu cầu #'+id+'?'))return;fetch('?ajax=proxy&action=nap_request_reject&val='+id).then(r=>r.json()).then(d=>{nrShow(d);loadNR();});}
                loadNR();
            </script>
        <?php endif; ?>


    </div>
</div>

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
            alert.classList.add((data.status == 'error' || data.success === false) ? 'alert-danger' : 'alert-success');
            alert.innerText = data.message || data.msg || data.status || 'Xong';
            setTimeout(() => alert.classList.add('d-none'), 3000);
        })
        .catch(() => {});
    }
    function showAlert(data){
        let alert = getAlert();
        alert.classList.remove('d-none', 'alert-danger', 'alert-success');
        alert.classList.add((data.status == 'error' || data.success === false) ? 'alert-danger' : 'alert-success');
        alert.innerText = data.message || data.msg || data.status || 'Xong';
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
        let f = prompt("Sửa giftcode #"+g.id+"\nNhập: Code|Gold|Gem|Ruby|Active(0/1)|Hạn(YYYY-MM-DD HH:MM:SS hoặc rỗng)|ItemsJSON",
            g.code+"|"+g.gold+"|"+g.gem+"|"+g.ruby+"|"+g.active+"|"+(g.expired||'')+"|"+(g.items||'[]'));
        if(!f) return;
        let p = f.split('|');
        if(p.length < 7){ alert("Thiếu dữ liệu, cần 7 trường!"); return; }
        let fd = new URLSearchParams();
        fd.append('action_gc','edit'); fd.append('id', g.id);
        fd.append('code', p[0]); fd.append('gold', p[1]); fd.append('gem', p[2]); fd.append('ruby', p[3]);
        fd.append('gcactive', p[4]); fd.append('expire', p[5]); fd.append('item', p[6]);
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
