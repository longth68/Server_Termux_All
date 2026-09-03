<?php
session_start();
if (!isset($_SESSION['account'])) { header("Location: /register"); exit(); }
include_once 'head.php';

$java_api = $JAVA_API;
$api_key = $API_KEY;

function callJavaApi($endpoint) {
    global $java_api, $api_key;
    $url = $java_api . "/" . $endpoint . "?key=" . urlencode($api_key);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

function callJavaApiGet($endpoint, $params) {
    global $java_api, $api_key;
    $qs = http_build_query($params) . "&key=" . urlencode($api_key);
    $url = $java_api . "/" . $endpoint . "?" . $qs;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

$char = _fetch("SELECT * FROM player WHERE account_id='{$_uid}' LIMIT 1");
$char_name = $char ? htmlspecialchars($char['name']) : 'Chưa tạo nhân vật';
$char_power = $char ? number_format($char['power']) : '0';
$char_pet_power = $char ? number_format($char['pet_power']) : '0';
$char_id = $char ? $char['id'] : 0;

$account = _fetch("SELECT ruby, vnd, tongnap FROM account WHERE id='{$_uid}' LIMIT 1");
$account_ruby = $account ? number_format($account['ruby'] ?? 0) : '0';
$account_vnd = $account ? $account['vnd'] : 0;
$account_tongnap = $account ? $account['tongnap'] : 0;

$wheel_data = callJavaApi("lucky_wheel_list");
$lw_prizes = $wheel_data['list'] ?? [];

$newplayer_data = callJavaApi("new_player_gift_list");
$np_gifts = $newplayer_data['list'] ?? [];

$online_data = callJavaApi("online_gift_list");
$og_gifts = $online_data['list'] ?? [];

$nap_data = callJavaApi("nap_tich_luy_list");
$nl_milestones = $nap_data['list'] ?? [];

$nap_history_data = [];
if ($char_id > 0) {
    $nap_history_data = callJavaApiGet("nap_tich_luy_history", ["account_id" => $_uid]);
}
$nl_history = $nap_history_data['list'] ?? [];
$claimed_ids = array_column($nl_history, 'milestone_id') ?? [];

$top_power_data = callJavaApi("top_power");
$top_power = $top_power_data['list'] ?? [];

$top_nap_data = callJavaApi("top_nap");
$top_nap = $top_nap_data['list'] ?? [];

$pending_data = callJavaApiGet("nap_request_pending", ["account_id" => $_uid]);
$pending_requests = $pending_data['list'] ?? [];
?>
<style>
body { background: none; }
.profile-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.profile-card h5 { color: #dc3545; font-weight: bold; border-bottom: 2px solid #dc3545; padding-bottom: 8px; margin-bottom: 15px; }
.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
.info-row span:first-child { font-weight: bold; color: #555; }
.info-row span:last-child { color: #dc3545; font-weight: bold; }
.btn-feature { margin: 4px; padding: 10px 18px; border-radius: 6px; font-weight: bold; color: #fff; border: none; cursor: pointer; font-size: 14px; }
.btn-lucky { background: linear-gradient(135deg, #f093fb, #f5576c); }
.btn-newplayer { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.btn-online { background: linear-gradient(135deg, #43e97b, #38f9d7); }
.btn-nap { background: linear-gradient(135deg, #fa709a, #fee140); }
.wheel-container { text-align: center; padding: 20px; }
.wheel-spin { width: 280px; height: 280px; border-radius: 50%; border: 6px solid #dc3545; margin: 20px auto; position: relative; transition: transform 3s ease-out; overflow: hidden; }
.wheel-center { width: 60px; height: 60px; background: #dc3545; border-radius: 50%; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; font-size: 14px; cursor: pointer; z-index: 10; }
.wheel-segment { position: absolute; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; color: #fff; }
.top-table { width: 100%; border-collapse: collapse; }
.top-table th { background: #dc3545; color: #fff; padding: 8px; text-align: left; }
.top-table td { padding: 8px; border-bottom: 1px solid #eee; }
.top-table tr:hover { background: #fff5f5; }
.rank-1 { color: #FFD700; font-weight: bold; }
.rank-2 { color: #C0C0C0; font-weight: bold; }
.rank-3 { color: #CD7F32; font-weight: bold; }
.milestone-card { border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 10px; transition: all .2s; }
.milestone-card:hover { border-color: #dc3545; box-shadow: 0 2px 8px rgba(220,53,69,.2); }
.milestone-card.claimed { background: #f8f9fa; opacity: 0.7; }
.milestone-card.available { border-color: #28a745; background: #f0fff4; }
.nav-tabs-custom .nav-link { cursor: pointer; font-weight: bold; }
.nav-tabs-custom .nav-link.active { color: #dc3545; border-color: #dc3545; }
.gem-badge { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 13px; }
.prize-tag { display: inline-block; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 4px 10px; margin: 3px; font-size: 13px; }
.prize-tag.gold { background: #fff3cd; border-color: #ffc107; color: #856404; }
.prize-tag.diamond { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
.prize-tag.costume { background: #f8d7da; border-color: #dc3545; color: #721c24; }
.prize-tag.rare { background: #d4edda; border-color: #28a745; color: #155724; }
.pending-card { border-left: 4px solid #ffc107; background: #fff9e6; }
.pending-card.approved { border-left-color: #28a745; background: #f0fff4; }
.pending-card.rejected { border-left-color: #dc3545; background: #fff5f5; opacity: 0.6; }
</style>

<div class="row">
    <div class="col-md-3 pb-3 pt-2">
        <div class="list-group d-sm-block">
            <?php include_once 'menu.php'; ?>
        </div>
    </div>
    <div class="col-md-9 pb-3 pt-2">

        <div class="profile-card">
            <h5><i class="fas fa-user"></i> Thông Tin Tài Khoản</h5>
            <div class="info-row"><span>Tài khoản:</span><span><?php echo $_username; ?></span></div>
            <div class="info-row"><span>Nhân vật:</span><span><?php echo $char_name; ?></span></div>
            <div class="info-row"><span>Sức mạnh:</span><span><?php echo $char_power; ?></span></div>
            <div class="info-row"><span>Sức mạnh đệ tử:</span><span><?php echo $char_pet_power; ?></span></div>
            <div class="info-row"><span>Ngọc:</span><span class="gem-badge">💎 <?php echo $account_ruby; ?> ngọc</span></div>
            <div class="info-row"><span>Vàng:</span><span><?php echo number_format($account_vnd); ?> VND</span></div>
            <div class="info-row"><span>Tổng nạp:</span><span><?php echo number_format($account_tongnap); ?> VND</span></div>
        </div>

        <ul class="nav nav-tabs-custom mb-3" id="profileTabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-lucky"><i class="fas fa-dharmachakra"></i> Vòng Quay May Mắn</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-newplayer"><i class="fas fa-gift"></i> Quà Tân Thủ</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-online"><i class="fas fa-clock"></i> Quà Online</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-nap"><i class="fas fa-coins"></i> Nạp Tích Lũy</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-top"><i class="fas fa-trophy"></i> Bảng Xếp Hạng</a></li>
        </ul>

        <div class="tab-content">
            <!-- VONG QUAY MAY MAN -->
            <div class="tab-pane fade show active" id="tab-lucky">
                <div class="profile-card">
                    <h5><i class="fas fa-dharmachakra"></i> Vòng Quay May Mắn</h5>
                    <p class="text-muted">Quay 1 lần phí <strong>100 ngọc</strong>. Giới hạn <strong>10 lượt/ngày</strong>.</p>
                    <div id="lwAlert" class="alert d-none" role="alert"></div>
                    <div class="wheel-container">
                        <div id="lwWheel" class="wheel-spin"></div>
                        <button class="btn btn-feature btn-lucky mt-3" id="lwSpinBtn" onclick="spinWheel()">
                            <i class="fas fa-sync-alt"></i> QUAY NGAY (100 Ngọc)
                        </button>
                        <div id="lwResult" class="mt-3 fw-bold" style="font-size:18px;color:#dc3545;"></div>
                    </div>
                    <div class="mt-3">
                        <h6>🏆 Giải Thưởng:</h6>
                        <div id="lwPrizes" class="row"></div>
                    </div>
                    <div class="mt-3">
                        <h6>📋 Lịch Sử Quay (hôm nay):</h6>
                        <div id="lwHistory" style="max-height:200px;overflow-y:auto;"></div>
                    </div>
                </div>
            </div>

            <!-- QUA TAN THU -->
            <div class="tab-pane fade" id="tab-newplayer">
                <div class="profile-card">
                    <h5><i class="fas fa-gift"></i> Quà Tân Thủ</h5>
                    <p class="text-muted">Nhận quà khi mới tạo tài khoản (trong <strong>7 ngày</strong>). Chỉ nhận được <strong>1 lần</strong>.</p>
                    <div id="npAlert" class="alert d-none" role="alert"></div>
                    <h6>🎁 Phần Thưởng:</h6>
                    <div id="npContent" class="row"></div>
                    <button class="btn btn-feature btn-newplayer mt-3" onclick="claimNewPlayerGift()">
                        <i class="fas fa-gift"></i> NHẬN QUÀ TÂN THỦ
                    </button>
                </div>
            </div>

            <!-- QUA ONLINE -->
            <div class="tab-pane fade" id="tab-online">
                <div class="profile-card">
                    <h5><i class="fas fa-clock"></i> Quà Online</h5>
                    <p class="text-muted">Nhận quà khi online đủ thời gian. Mỗi mức chỉ nhận được <strong>1 lần/ngày</strong>.</p>
                    <div id="ogAlert" class="alert d-none" role="alert"></div>
                    <h6>🎁 Phần Thưởng:</h6>
                    <div id="ogContent" class="row"></div>
                </div>
            </div>

            <!-- NAP TICH LUY -->
            <div class="tab-pane fade" id="tab-nap">
                <div class="profile-card">
                    <h5><i class="fas fa-coins"></i> Nạp Tích Lũy</h5>
                    <p class="text-muted">Tích lũy nạp tiền để nhận quà. Tổng nạp hiện tại: <strong class="text-danger"><?php echo number_format($account_tongnap); ?> VND</strong></p>
                    <div id="nlAlert" class="alert d-none" role="alert"></div>
                    <div id="pendingSection" class="mb-3"></div>
                    <h6>🎁 Các Mốc Thưởng:</h6>
                    <div id="nlContent" class="row"></div>
                </div>
            </div>

            <!-- BANG XEP HANG -->
            <div class="tab-pane fade" id="tab-top">
                <div class="profile-card">
                    <h5><i class="fas fa-trophy"></i> Bảng Xếp Hạng</h5>
                    <ul class="nav nav-tabs mb-3" id="topTabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#top-power">💪 Top Sức Mạnh</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#top-nap">💰 Top Nạp</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="top-power">
                            <table class="top-table">
                                <thead><tr><th>#</th><th>Tên Nhân Vật</th><th>Sức Mạnh</th><th>SM Đệ Tử</th><th>Tổng SM</th></tr></thead>
                                <tbody id="topPowerBody"></tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="top-nap">
                            <table class="top-table">
                                <thead><tr><th>#</th><th>Tài Khoản</th><th>Nhân Vật</th><th>Tổng Nạp</th></tr></thead>
                                <tbody id="topNapBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const API = '<?php echo $java_api; ?>';
const KEY = '<?php echo $api_key; ?>';
const PLAYER_ID = <?php echo $char_id; ?>;
const ACCOUNT_ID = <?php echo $_uid; ?>;
const TONGNAP = <?php echo $account_tongnap; ?>;

// Kich hoat tab tu URL hash
(function() {
    let hash = window.location.hash;
    if (hash) {
        let tab = document.querySelector('[data-bs-toggle="tab"][href="' + hash + '"]');
        if (tab) {
            // Deactivate all tabs
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
            // Activate target tab
            tab.classList.add('active');
            let pane = document.querySelector(hash);
            if (pane) pane.classList.add('show', 'active');
        }
    }
})();

const LW_PRIZES = <?php echo json_encode($lw_prizes); ?>;
const NP_GIFTS = <?php echo json_encode($np_gifts); ?>;
const OG_GIFTS = <?php echo json_encode($og_gifts); ?>;
const NL_MILESTONES = <?php echo json_encode($nl_milestones); ?>;
const NL_CLAIMED = <?php echo json_encode($claimed_ids); ?>;
const TOP_POWER = <?php echo json_encode($top_power); ?>;
const TOP_NAP = <?php echo json_encode($top_nap); ?>;
const PENDING = <?php echo json_encode($pending_requests); ?>;

const PRIZE_NAMES = {
    '-1': 'Vang', '-2': 'Ngoc',
    '282': 'Thanh Vu Tru', '283': 'Yarirobe',
    '284': 'Tau Pay Pay', '285': 'Meo Than Meo Karin',
    '286': 'Thuong De', '287': 'PoPo',
    '288': 'Kuku', '289': 'Rambo',
    '290': 'So 2', '291': 'Ninja Ao Tim',
    '292': 'So 3',
    '740': 'Luoi Hai Than Chet', '741': 'Canh Doi Dracula',
    '745': 'Bong tuyet', '458': 'Meo Xinbato',
    '14': 'Ngoc Rong 1S', '15': 'Ngoc Rong 2S', '16': 'Ngoc Rong 3S',
    '77': 'Ngoc', '457': 'Thoi vang', '459': 'Phieu giam gia',
};

function getPrizeName(id) {
    id = String(id);
    return PRIZE_NAMES[id] || 'Item #' + id;
}

function getPrizeClass(id) {
    id = String(id);
    if (id === '-1' || id === '457') return 'gold';
    if (id === '-2' || id === '77') return 'diamond';
    if ([282,283,284,285,286,287,288,289,290,291,292,458].includes(parseInt(id))) return 'costume';
    if ([740,741,745,14,15,16,459].includes(parseInt(id))) return 'rare';
    return '';
}

function showAlert(el, msg, ok) {
    el.classList.remove('d-none','alert-danger','alert-success');
    el.classList.add(ok ? 'alert-success' : 'alert-danger');
    el.innerText = msg;
    setTimeout(() => el.classList.add('d-none'), 5000);
}

function apiCall(ep, params) {
    let qs = params || {};
    qs.key = KEY;
    qs.ep = ep;
    return fetch('api_proxy.php?' + new URLSearchParams(qs)).then(r => r.json());
}

function renderLuckyWheel() {
    let colors = ['#f5576c','#4facfe','#43e97b','#fa709a','#fee140','#f093fb','#38f9d7','#ff6b6b','#ffd93d','#6bcb77'];
    let html = '';
    LW_PRIZES.forEach((p, i) => {
        let nm = getPrizeName(p.item_id);
        html += '<div class="col-md-3 col-6 mb-2"><div class="text-center p-2 rounded" style="background:' + colors[i%colors.length] + ';color:#fff;font-size:13px;">'
            + '<div class="fw-bold">' + nm + '</div><div>x' + p.quantity.toLocaleString() + '</div></div></div>';
    });
    document.getElementById('lwPrizes').innerHTML = html;

    let wheel = document.getElementById('lwWheel');
    let segHtml = '';
    let step = 360 / Math.max(LW_PRIZES.length, 1);
    LW_PRIZES.forEach((p, i) => {
        let nm = getPrizeName(p.item_id).replace(/[\u{1F300}-\u{1F9FF}]/gu, '').trim();
        let rot = i * step;
        segHtml += '<div class="wheel-segment" style="transform:rotate(' + rot + 'deg);background:' + colors[i%colors.length] + ';">'
            + '<span style="transform:rotate(' + (step/2) + 'deg);margin-top:-55px;">' + nm + ' x' + p.quantity.toLocaleString() + '</span></div>';
    });
    wheel.innerHTML = segHtml + '<div class="wheel-center">QUAY</div>';

    if (PLAYER_ID > 0) {
        apiCall('lucky_wheel_history').then(d => {
            let list = (d.list || []).filter(x => x.player_id == PLAYER_ID);
            let h = '<table class="table table-sm"><thead><tr><th>Gio</th><th>Giai</th><th>So Luong</th></tr></thead><tbody>';
            list.slice(0, 10).forEach(r => {
                h += '<tr><td>' + r.spun_at + '</td><td>' + getPrizeName(r.item_id) + '</td><td>x' + r.quantity.toLocaleString() + '</td></tr>';
            });
            h += '</tbody></table>';
            document.getElementById('lwHistory').innerHTML = h;
        });
    }
}

let spinning = false;
function spinWheel() {
    if (spinning) return;
    if (PLAYER_ID <= 0) { alert('Ban chua tao nhan vat!'); return; }
    if (!confirm('Quay voi phi 100 ngoc?')) return;
    spinning = true;
    document.getElementById('lwSpinBtn').disabled = true;
    document.getElementById('lwResult').innerText = 'Dang quay...';
    let wheel = document.getElementById('lwWheel');
    let randomDeg = 1440 + Math.floor(Math.random() * 360);
    wheel.style.transition = 'transform 3s ease-out';
    wheel.style.transform = 'rotate(' + randomDeg + 'deg)';
    setTimeout(() => {
        apiCall('lucky_wheel_spin', {player_id: PLAYER_ID}).then(d => {
            spinning = false;
            document.getElementById('lwSpinBtn').disabled = false;
            if (d.success) {
                showAlert(document.getElementById('lwAlert'), d.message, true);
                document.getElementById('lwResult').innerText = d.message;
            } else {
                showAlert(document.getElementById('lwAlert'), d.message, false);
                document.getElementById('lwResult').innerText = d.message;
            }
        });
    }, 3200);
}

function renderNewPlayerGift() {
    let html = '';
    NP_GIFTS.forEach(g => {
        let nm = getPrizeName(g.item_id);
        let cls = getPrizeClass(g.item_id);
        html += '<div class="col-md-4 col-6 mb-3"><div class="milestone-card text-center p-3">'
            + '<div class="prize-tag ' + cls + '" style="font-size:16px;">' + nm + '</div>'
            + '<div class="text-danger fw-bold" style="font-size:22px;">x' + g.quantity.toLocaleString() + '</div>'
            + '<div class="text-muted small">Trong ' + g.max_day + ' ngay</div></div></div>';
    });
    document.getElementById('npContent').innerHTML = html;
}

function claimNewPlayerGift() {
    if (PLAYER_ID <= 0) { alert('Ban chua tao nhan vat!'); return; }
    if (!confirm('Nhan qua tan thu?')) return;
    apiCall('new_player_gift_claim', {player_id: PLAYER_ID}).then(d => {
        showAlert(document.getElementById('npAlert'), d.message || d.msg, d.success);
    });
}

function renderOnlineGift() {
    let html = '';
    OG_GIFTS.forEach(g => {
        let nm = getPrizeName(g.item_id);
        let cls = getPrizeClass(g.item_id);
        let minutes = g.minutes_required;
        let hours = Math.floor(minutes / 60);
        let mins = minutes % 60;
        let timeStr = hours > 0 ? hours + ' gio' + (mins > 0 ? ' ' + mins + ' phut' : '') : mins + ' phut';
        html += '<div class="col-md-4 col-6 mb-3"><div class="milestone-card text-center p-3">'
            + '<div class="prize-tag ' + cls + '" style="font-size:14px;">' + nm + '</div>'
            + '<div class="text-danger fw-bold" style="font-size:20px;">x' + g.quantity.toLocaleString() + '</div>'
            + '<div class="text-muted small">Online ' + timeStr + '</div>'
            + '<button class="btn btn-sm btn-success mt-2" onclick="claimOnlineGift(' + g.id + ')">Nhan</button></div></div>';
    });
    document.getElementById('ogContent').innerHTML = html;
}

function claimOnlineGift(giftId) {
    if (PLAYER_ID <= 0) { alert('Ban chua tao nhan vat!'); return; }
    apiCall('online_gift_claim', {player_id: PLAYER_ID, gift_id: giftId}).then(d => {
        showAlert(document.getElementById('ogAlert'), d.message || d.msg, d.success);
    });
}

function renderNapTichLuy() {
    let grouped = {};
    NL_MILESTONES.forEach(m => {
        if (!grouped[m.vnd_amount]) grouped[m.vnd_amount] = [];
        grouped[m.vnd_amount].push(m);
    });

    let pendingAmounts = {};
    PENDING.forEach(p => { if (p.status === 'pending') pendingAmounts[p.amount] = true; });

    let html = '';
    for (let vnd in grouped) {
        let items = grouped[vnd];
        let allClaimed = items.every(m => NL_CLAIMED.indexOf(m.id) >= 0);
        let isAvail = TONGNAP >= parseInt(vnd);
        let isPending = pendingAmounts[vnd];
        let cls = allClaimed ? 'claimed' : (isAvail ? 'available' : '');
        html += '<div class="col-md-6 mb-3"><div class="milestone-card ' + cls + ' p-3">';
        html += '<div class="d-flex justify-content-between align-items-center mb-2">';
        html += '<span class="fw-bold" style="font-size:18px;color:#dc3545;">Nap ' + parseInt(vnd).toLocaleString() + ' VNĐ</span>';
        if (allClaimed) html += '<span class="badge bg-secondary">Da nhan</span>';
        else if (isAvail) html += '<span class="badge bg-success">Du dieu kien</span>';
        else if (isPending) html += '<span class="badge bg-warning">Dang cho duyet</span>';
        else html += '<span class="badge bg-info">Chua du</span>';
        html += '</div><div class="row">';
        items.forEach(m => {
            let nm = getPrizeName(m.item_id);
            let cls2 = getPrizeClass(m.item_id);
            html += '<div class="col-6 text-center mb-2"><div class="prize-tag ' + cls2 + '" style="font-size:13px;">' + nm + '</div>'
                + '<div class="text-danger fw-bold">x' + m.quantity.toLocaleString() + '</div></div>';
        });
        html += '</div>';
        if (!allClaimed) {
            if (isAvail && !isPending) {
                html += '<button class="btn btn-sm btn-success mt-2" onclick="claimNapMilestone(' + items.map(m => m.id).join(',') + ')">Nhan Thuong</button>';
            } else if (!isPending) {
                html += '<button class="btn btn-sm btn-warning mt-2" onclick="requestNap(' + vnd + ')">Nap ' + parseInt(vnd).toLocaleString() + ' VNĐ</button>';
            } else {
                html += '<span class="text-muted small mt-2 d-block">Da gui yeu cau, cho admin duyet</span>';
            }
        }
        html += '</div></div>';
    }
    document.getElementById('nlContent').innerHTML = html;
}

function requestNap(amount) {
    if (!confirm('Gui yeu cau nap ' + parseInt(amount).toLocaleString() + ' VNĐ?\nAdmin se duyet va cong tien cho ban.')) return;
    apiCall('nap_request_create', {account_id: ACCOUNT_ID, amount: amount}).then(d => {
        showAlert(document.getElementById('nlAlert'), d.message || d.msg, d.success);
        if (d.success) setTimeout(() => location.reload(), 1000);
    });
}

function claimNapMilestone() {
    if (!confirm('Nhan qua nap tich luy?')) return;
    let ids = Array.from(arguments).join(',');
    apiCall('nap_tich_luy_claim', {account_id: ACCOUNT_ID, milestone_id: ids}).then(d => {
        showAlert(document.getElementById('nlAlert'), d.message || d.msg, d.success);
        if (d.success) setTimeout(() => location.reload(), 1000);
    });
}

function renderTopPower() {
    let html = '';
    TOP_POWER.forEach(r => {
        let rankCls = r.rank <= 3 ? ' rank-' + r.rank : '';
        let medal = r.rank == 1 ? '1st' : r.rank == 2 ? '2nd' : r.rank == 3 ? '3rd' : '';
        html += '<tr><td class="' + rankCls + '">' + medal + ' ' + r.rank + '</td>'
            + '<td>' + r.name + '</td>'
            + '<td>' + r.power.toLocaleString() + '</td>'
            + '<td>' + r.pet_power.toLocaleString() + '</td>'
            + '<td class="text-danger fw-bold">' + r.total_power.toLocaleString() + '</td></tr>';
    });
    document.getElementById('topPowerBody').innerHTML = html || '<tr><td colspan="5" class="text-center text-muted">Chua co du lieu</td></tr>';
}

function renderTopNap() {
    let html = '';
    TOP_NAP.forEach(r => {
        let rankCls = r.rank <= 3 ? ' rank-' + r.rank : '';
        let medal = r.rank == 1 ? '1st' : r.rank == 2 ? '2nd' : r.rank == 3 ? '3rd' : '';
        html += '<tr><td class="' + rankCls + '">' + medal + ' ' + r.rank + '</td>'
            + '<td>' + r.username + '</td>'
            + '<td>' + (r.char_name || 'N/A') + '</td>'
            + '<td class="text-danger fw-bold">' + r.tongnap.toLocaleString() + ' VNĐ</td></tr>';
    });
    document.getElementById('topNapBody').innerHTML = html || '<tr><td colspan="4" class="text-center text-muted">Chua co du lieu</td></tr>';
}

renderLuckyWheel();
renderNewPlayerGift();
renderOnlineGift();
renderNapTichLuy();
renderTopPower();
renderTopNap();
</script>
</div>
</div>
</div>
</div>
</body>
</html>
