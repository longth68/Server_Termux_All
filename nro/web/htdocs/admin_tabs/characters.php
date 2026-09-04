<?php
/* Tab: Quản lý Nhân vật (player) - include bởi admin.php */
$cm = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_char'])) {
    $act = $_POST['action_char'];
    if ($act === 'rename') {
        $old = isset_sql(trim($_POST['old_name'] ?? ''));
        $new = isset_sql(trim($_POST['new_name'] ?? ''));
        if ($old != '' && $new != '') {
            $ex = _fetch("SELECT id FROM player WHERE name='$new'");
            if ($ex) { $cm = "Tên '$new' đã tồn tại!"; }
            else { $cm = _query("UPDATE player SET name='$new' WHERE name='$old'") ? "Đã đổi tên '$old' → '$new'!" : "Không tìm thấy nhân vật '$old'!"; }
        } else { $cm = "Vui lòng nhập đủ tên cũ và tên mới!"; }
    } elseif ($act === 'del') {
        $ids = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', trim($_POST['ids'] ?? '')))));
        if (empty($ids)) { $cm = "Vui lòng nhập ID nhân vật!"; }
        else {
            $in = implode(',', $ids);
            $n = _query("DELETE FROM player WHERE id IN ($in)");
            $cm = $n ? "Đã xóa " . mysqli_affected_rows($conn) . " nhân vật (ID: $in)!" : "Lỗi xóa!";
        }
    }
}

$search = trim($_POST['search_char'] ?? ($_GET['search_char'] ?? ''));
$c_rows = [];
$q = "SELECT p.id, p.name, p.account_id, p.head, a.username, a.active, a.ban, a.vnd FROM player p LEFT JOIN account a ON p.account_id = a.id";
if ($search != "") $q .= " WHERE p.name LIKE '%".addslashes($search)."%' OR p.id='".addslashes($search)."' OR a.username LIKE '%".addslashes($search)."%'";
$q .= " ORDER BY p.id DESC LIMIT 100";
$res = _query($q);
if ($res) { while($r = mysqli_fetch_assoc($res)) $c_rows[] = $r; }

// ===== Template cho modal chi tiet =====
$tplMain = [];   $r2 = _query("SELECT id, name FROM task_main_template ORDER BY id");
while($r2 && $x = mysqli_fetch_assoc($r2)) $tplMain[] = ['id'=>(int)$x['id'], 'n'=>$x['name']];
$tplSide = [];   $r2 = _query("SELECT id, name FROM side_task_template ORDER BY id");
while($r2 && $x = mysqli_fetch_assoc($r2)) $tplSide[] = ['id'=>(int)$x['id'], 'n'=>$x['name']];
$tplClan = [];   $r2 = _query("SELECT id, name FROM clan_task_template ORDER BY id");
while($r2 && $x = mysqli_fetch_assoc($r2)) $tplClan[] = ['id'=>(int)$x['id'], 'n'=>$x['name']];
$tplKol = [];    $r2 = _query("SELECT id, info FROM task_kol_template ORDER BY id");
while($r2 && $x = mysqli_fetch_assoc($r2)) $tplKol[] = ['id'=>(int)$x['id'], 'n'=>$x['info']];
// Danh hieu: idBadGes trong player tham chieu data_badges.idEffect
$tplBadge = [];  $r2 = _query("SELECT b.idEffect, b.NAME, i.icon_id FROM data_badges b LEFT JOIN item_template i ON b.idItem = i.id ORDER BY b.id");
while($r2 && $x = mysqli_fetch_assoc($r2)) $tplBadge[] = ['id'=>(int)$x['idEffect'], 'n'=>$x['NAME'], 'ic'=>$x['icon_id'] !== null ? (int)$x['icon_id'] : -1];
// Head: part TYPE=0, DATA = [[icon_gender0,dx,dy],[...],[...]]
$tplHead = [];   $r2 = _query("SELECT id, DATA FROM part WHERE TYPE = 0");
while($r2 && $x = mysqli_fetch_assoc($r2)) {
    $icons = [-1,-1,-1];
    $arr = json_decode($x['DATA'], true);
    if (is_array($arr)) foreach ($arr as $gi => $sub) {
        if (is_array($sub) && isset($sub[0])) { $icons[$gi <= 2 ? $gi : 0] = (int)$sub[0]; }
    }
    $tplHead[] = ['id'=>(int)$x['id'], 'ic'=>$icons];
}
?>
<h3 class="mb-4">Quản Lý Nhân Vật</h3>
<?php if($cm): ?><div class="alert alert-<?= strpos($cm,'Lỗi')!==false||strpos($cm,'tồn tại')!==false?'danger':'success' ?>"><?= $cm ?></div><?php endif; ?>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Đổi Tên Nhân Vật</h6>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="action_char" value="rename">
        <div class="col-md-4"><label class="form-label mb-1">Tên cũ</label><input type="text" name="old_name" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label mb-1">Tên mới</label><input type="text" name="new_name" class="form-control" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Đổi Tên</button></div>
    </form>
</div>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Xóa Nhân Vật Hàng Loạt</h6>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="action_char" value="del">
        <div class="col-md-8"><label class="form-label mb-1">Danh sách ID nhân vật (cách nhau bằng dấu phẩy hoặc khoảng trắng)</label><input type="text" name="ids" class="form-control" placeholder="VD: 1, 2, 3, 4" required></div>
        <div class="col-md-4"><button class="btn btn-danger w-100" onclick="return confirm('Xác nhận xóa các nhân vật này? Hành động không thể hoàn tác!')"><i class="fa-solid fa-trash"></i> Xóa Hàng Loạt</button></div>
    </form>
    <p class="text-muted small mt-2 mb-0">Lưu ý: Chỉ xóa nhân vật, KHÔNG xóa tài khoản. Nhân vật offline sẽ bị xóa ngay; nhân vật đang online cần kick trước.</p>
</div>

<div class="card p-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Danh sách nhân vật (tối đa 100)</h6>
    <form method="GET" class="d-flex mb-2">
        <input type="hidden" name="tab" value="characters">
        <input type="text" name="search_char" class="form-control me-2" placeholder="Tìm theo tên nhân vật / ID / username..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary">Tìm</button>
    </form>
    <div style="max-height:600px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Tên Nhân Vật</th><th>Account</th><th>VNĐ</th><th>Trạng Thái</th><th>Hành Động</th></tr></thead>
            <tbody>
            <?php if(empty($c_rows)): ?>
                <tr><td colspan="6" class="text-center text-muted">Không có dữ liệu</td></tr>
            <?php else: foreach($c_rows as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                    <td><?= htmlspecialchars($c['username'] ?? 'N/A') ?> <small class="text-muted">(<?= $c['account_id'] ?>)</small></td>
                    <td><?= number_format($c['vnd'] ?? 0) ?>đ</td>
                    <td>
                        <?= $c['active'] ? '<span class="badge bg-success">Đã mở</span>' : '<span class="badge bg-secondary">Chưa mở</span>' ?>
                        <?= $c['ban'] ? '<span class="badge bg-danger">Bị khóa</span>' : '' ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="plOpen(<?= $c['id'] ?>)" title="Quản lý chi tiết"><i class="fa-solid fa-user-pen"></i> Chi tiết</button>
                        <button class="btn btn-sm btn-outline-primary" onclick='renameChar(<?= htmlspecialchars(json_encode($c['name'])) ?>)'>Đổi Tên</button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action_char" value="del">
                            <input type="hidden" name="ids" value="<?= $c['id'] ?>">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa nhân vật <?= $c['name'] ?>?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= MODAL CHI TIET NHAN VAT ================= -->
<div class="modal fade" id="plModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title"><span id="plTitle">Nhân vật</span>
          <span id="plOnline" class="badge bg-secondary ms-2">...</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-2">
        <ul class="nav nav-pills mb-3 small fw-bold" id="plTabs">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#plTabInfo"><i class="fa-solid fa-user"></i> Thông tin chung</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#plTabItems"><i class="fa-solid fa-box-open"></i> Vật phẩm</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#plTabPet"><i class="fa-solid fa-paw"></i> Đệ tử</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#plTabTask"><i class="fa-solid fa-scroll"></i> Nhiệm vụ</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#plTabBadge"><i class="fa-solid fa-medal"></i> Danh hiệu</a></li>
        </ul>
        <div class="tab-content">

          <!-- TAB THONG TIN CHUNG -->
          <div class="tab-pane fade show active" id="plTabInfo">
            <form id="plInfoForm" onsubmit="plSaveInfo(); return false;">
            <div class="row g-2">
              <div class="col-md-3"><label class="form-label mb-0 small">Tên</label><input type="text" class="form-control form-control-sm" id="piName"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Head</label>
                <input list="plHeadDL" type="number" class="form-control form-control-sm" id="piHead">
                <datalist id="plHeadDL"><?= implode('', array_map(fn($h)=>'<option value="'.$h['id'].'">', $tplHead)) ?></datalist>
              </div>
              <div class="col-md-2"><label class="form-label mb-0 small">Giới tính</label><select class="form-select form-select-sm" id="piGender" disabled>
                <option value="0">Trái Đất</option><option value="1">Namếc</option><option value="2">Xayda</option></select></div>
              <div class="col-md-5 text-end align-self-end">
                <div class="small text-muted mb-1">Xem trước đầu</div>
                <div id="piHeadWrap"><img id="piHeadImg" width="80" height="80" style="image-rendering:pixelated;" class="border rounded bg-light"></div>
              </div>
              <div class="col-md-6"><label class="form-label mb-0 small">Sức mạnh (Power)</label><input type="number" class="form-control form-control-sm" id="piPower"></div>
              <div class="col-md-6"><label class="form-label mb-0 small">Tiềm năng</label><input type="number" class="form-control form-control-sm" id="piTiem"></div>
              <div class="col-md-4"><label class="form-label mb-0 small">HP gốc (+%)</label><input type="number" class="form-control form-control-sm" id="piHpg"></div>
              <div class="col-md-4"><label class="form-label mb-0 small">KI gốc (+%)</label><input type="number" class="form-control form-control-sm" id="piMpg"></div>
              <div class="col-md-4"><label class="form-label mb-0 small">Sức đánh gốc (+%)</label><input type="number" class="form-control form-control-sm" id="piDameg"></div>
              <div class="col-md-4"><label class="form-label mb-0 small">Giáp gốc (+)</label><input type="number" class="form-control form-control-sm" id="piDefg"></div>
              <div class="col-md-4"><label class="form-label mb-0 small">Crit gốc (+%)</label><input type="number" class="form-control form-control-sm" id="piCritg"></div>
              <div class="col-md-12"><hr class="my-1"></div>
              <div class="col-md-4"><label class="form-label mb-0 small">🪙 Thỏi vàng</label><input type="number" class="form-control form-control-sm" id="piGold"></div>
              <div class="col-md-4"><label class="form-label mb-0 small">💎 Ngọc xanh</label><input type="number" class="form-control form-control-sm" id="piGem"></div>
              <div class="col-md-4"><label class="form-label mb-0 small">🔴 Hồng ngọc</label><input type="number" class="form-control form-control-sm" id="piRuby"></div>
            </div>
            <button class="btn btn-primary btn-sm mt-3"><i class="fa-solid fa-floppy-disk"></i> Lưu thông tin chung</button>
            </form>
          </div>

          <!-- TAB VAT PHAM -->
          <div class="tab-pane fade" id="plTabItems">
            <ul class="nav nav-tabs small mb-2">
              <li class="nav-item"><a class="nav-link active py-1" data-bs-toggle="tab" href="#plItBody">Đang mặc</a></li>
              <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="tab" href="#plItBag">Hành trang</a></li>
              <li class="nav-item"><a class="nav-link py-1" data-bs-toggle="tab" href="#plItBox">Rương đồ</a></li>
            </ul>
            <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
              <select id="plAddType" class="form-select form-select-sm" style="width:130px">
                <option value="bag">Hành trang</option><option value="box">Rương đồ</option><option value="body">Trang bị</option>
              </select>
              <input list="plItemDL" id="plAddTemp" class="form-control form-control-sm" placeholder="ID hoặc tên vật phẩm..." style="width:260px">
              <datalist id="plItemDL"></datalist>
              <span id="plAddPreview"></span>
              <input type="number" id="plAddQty" class="form-control form-control-sm" value="1" min="1" max="9999" style="width:90px" title="Số lượng">
              <button class="btn btn-sm btn-success" onclick="plItemAdd()"><i class="fa-solid fa-plus"></i> Thêm</button>
              <span class="text-muted small ms-auto">Click vào ô vật phẩm để sửa SL / xóa</span>
            </div>
            <div class="tab-content">
              <div class="tab-pane fade show active" id="plItBody"><div id="plGridBody" class="d-flex flex-wrap gap-1"></div></div>
              <div class="tab-pane fade" id="plItBag"><div id="plGridBag" class="d-flex flex-wrap gap-1"></div></div>
              <div class="tab-pane fade" id="plItBox"><div id="plGroupBox" class="d-flex flex-wrap gap-1"></div></div>
            </div>
          </div>

          <!-- TAB DE TU -->
          <div class="tab-pane fade" id="plTabPet">
            <div id="plPetNone" class="alert alert-secondary py-2 small d-none">Nhân vật này chưa có đệ tử.</div>
            <form id="plPetForm" onsubmit="plSavePet(); return false;" class="d-none">
            <div id="ppIcon" class="text-center mb-2"></div>
            <div class="row g-2">
              <div class="col-md-3"><label class="form-label mb-0 small">Tên đệ tử</label><input type="text" class="form-control form-control-sm" id="ppName"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Loại</label><select class="form-select form-select-sm" id="ppType">
                <option value="0">Thường</option><option value="1">Mabu</option><option value="2">Broly</option><option value="3">Pic</option><option value="4">Black</option></select></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Giới tính</label><select class="form-select form-select-sm" id="ppGender">
                <option value="0">Nam</option><option value="1">Nữ</option></select></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Trạng thái</label><select class="form-select form-select-sm" id="ppStatus">
                <option value="0">Bình thường</option><option value="1">Ghép thân</option><option value="2">Ghép xong</option></select></div>
              <div class="col-md-3"><label class="form-label mb-0 small">Sức mạnh</label><input type="number" class="form-control form-control-sm" id="ppPower"></div>
              <div class="col-md-3"><label class="form-label mb-0 small">Tiềm năng</label><input type="number" class="form-control form-control-sm" id="ppTiem"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">HP gốc</label><input type="number" class="form-control form-control-sm" id="ppHpg"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">KI gốc</label><input type="number" class="form-control form-control-sm" id="ppMpg"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Sức đánh gốc</label><input type="number" class="form-control form-control-sm" id="ppDameg"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Giáp gốc</label><input type="number" class="form-control form-control-sm" id="ppDefg"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Crit gốc</label><input type="number" class="form-control form-control-sm" id="ppCritg"></div>
            </div>
            <button class="btn btn-primary btn-sm mt-3"><i class="fa-solid fa-floppy-disk"></i> Lưu đệ tử</button>
            <span class="text-muted small ms-2">Lưu ý: đệ tử áp dụng khi nhân vật vào lại game.</span>
            </form>
          </div>

          <!-- TAB NHIEM VU -->
          <div class="tab-pane fade" id="plTabTask">
            <form onsubmit="plSaveTask(); return false;">
            <h6 class="text-primary small fw-bold mt-1"><i class="fa-solid fa-scroll"></i> Nhiệm vụ chính tuyến</h6>
            <div class="row g-2 align-items-end">
              <div class="col-md-6"><label class="form-label mb-0 small">Nhiệm vụ</label><select class="form-select form-select-sm" id="ptMain"></select></div>
              <div class="col-md-3"><label class="form-label mb-0 small">Bước (index)</label><input type="number" class="form-control form-control-sm" id="ptIndex" min="0"></div>
              <div class="col-md-3"><label class="form-label mb-0 small">Đếm hiện tại</label><input type="number" class="form-control form-control-sm" id="ptCount" min="0"></div>
            </div>
            <h6 class="text-primary small fw-bold mt-3"><i class="fa-solid fa-calendar-day"></i> Nhiệm vụ hằng ngày</h6>
            <div class="row g-2 align-items-end">
              <div class="col-md-3"><label class="form-label mb-0 small">Nhiệm vụ</label><select class="form-select form-select-sm" id="ptSide"></select></div>
              <div class="col-md-2"><label class="form-label mb-0 small">SL</label><input type="number" class="form-control form-control-sm" id="ptSideC" min="0"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Max</label><input type="number" class="form-control form-control-sm" id="ptSideM" min="0"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Lượt còn</label><input type="number" class="form-control form-control-sm" id="ptSideL" min="0"></div>
              <div class="col-md-3"><label class="form-label mb-0 small">Level</label><input type="number" class="form-control form-control-sm" id="ptSideLv" min="0"></div>
            </div>
            <h6 class="text-primary small fw-bold mt-3"><i class="fa-solid fa-flag"></i> Nhiệm vụ bang hội</h6>
            <div class="row g-2 align-items-end">
              <div class="col-md-3"><label class="form-label mb-0 small">Nhiệm vụ</label><select class="form-select form-select-sm" id="ptClan"></select></div>
              <div class="col-md-2"><label class="form-label mb-0 small">SL</label><input type="number" class="form-control form-control-sm" id="ptClanC" min="0"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Max</label><input type="number" class="form-control form-control-sm" id="ptClanM" min="0"></div>
              <div class="col-md-2"><label class="form-label mb-0 small">Lượt còn</label><input type="number" class="form-control form-control-sm" id="ptClanL" min="0"></div>
              <div class="col-md-3"><label class="form-label mb-0 small">Level</label><input type="number" class="form-control form-control-sm" id="ptClanLv" min="0"></div>
            </div>
            <h6 class="text-primary small fw-bold mt-3"><i class="fa-solid fa-trophy"></i> Nhiệm vụ KOL / Sự kiện khác</h6>
            <div class="row g-2 align-items-end">
              <div class="col-md-6"><label class="form-label mb-0 small">Nhiệm vụ</label><select class="form-select form-select-sm" id="ptKol"></select></div>
              <div class="col-md-3"><label class="form-label mb-0 small">Đếm hiện tại</label><input type="number" class="form-control form-control-sm" id="ptKolC" min="0"></div>
            </div>
            <button class="btn btn-primary btn-sm mt-3"><i class="fa-solid fa-floppy-disk"></i> Lưu toàn bộ nhiệm vụ</button>
            </form>
          </div>

          <!-- TAB DANH HIEU -->
          <div class="tab-pane fade" id="plTabBadge">
            <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
              <select id="pbNewSel" class="form-select form-select-sm" style="width:280px"></select>
              <input type="number" id="pbNewDays" class="form-control form-control-sm" value="30" min="1" style="width:110px" title="Số ngày">
              <button class="btn btn-sm btn-success" onclick="plBadgeAdd()"><i class="fa-solid fa-plus"></i> Thêm danh hiệu</button>
            </div>
            <table class="table table-bordered table-hover table-sm small">
              <thead class="table-dark"><tr><th>#</th><th>Danh hiệu</th><th>ID</th><th>Trạng thái</th><th>Hết hạn</th><th style="width:220px">Thao tác</th></tr></thead>
              <tbody id="pbRows"><tr><td colspan="6" class="text-center text-muted">Chưa tải</td></tr></tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
const TPL_MAIN = <?= json_encode($tplMain) ?>;
const TPL_SIDE = <?= json_encode($tplSide) ?>;
const TPL_CLAN = <?= json_encode($tplClan) ?>;
const TPL_KOL  = <?= json_encode($tplKol) ?>;
const TPL_BADGE = <?= json_encode($tplBadge) ?>;
const TPL_HEAD = <?= json_encode($tplHead) ?>;
const petIcons = {0:'fa-paw', 1:'fa-robot', 2:'fa-dragon', 3:'fa-cat', 4:'fa-spider'};

function renameChar(name){
    let n = prompt("Đổi tên nhân vật '"+name+"' thành:", name);
    if(n===null || n.trim()==='') return;
    let fd = new URLSearchParams();
    fd.append('action_char','rename'); fd.append('old_name', name); fd.append('new_name', n.trim());
    fetch(window.location.href.split('?')[0]+'?tab=characters', {method:'POST', body: fd}).then(()=>location.reload());
}

// ================= MODAL CHI TIET =================
let plId = null, plData = null, plModalEl = null;
function plApi(action, qs){
    return fetch('?ajax=proxy&action='+action+(qs?'&'+qs:'')).then(r=>r.json());
}
function esc(s){ return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }
function iconTag(iconId, size, full){ return '<img src="item_icon.php?id='+iconId+(full?'&size=3':'')+'" width="'+size+'" height="'+size+'" style="image-rendering:pixelated;" loading="lazy" title="Template #'+iconId+'">'; }

function plOpen(id){
    plId = id;
    if(!plModalEl) plModalEl = new bootstrap.Modal(document.getElementById('plModal'));
    document.getElementById('plTitle').textContent = 'Nhân vật #'+id+' - đang tải...';
    document.getElementById('plOnline').className = 'badge bg-secondary ms-2';
    plApi('pl_detail','id='+id).then(d=>{
        if(d.status!=='success'){ alert('Lỗi: '+(d.msg||d.status)); return; }
        plData = d;
        renderPl();
        plModalEl.show();
    });
}
function plReload(){ plOpen(plId); }

function fillSelect(sel, arr, cur){
    sel.innerHTML = arr.map(x=>'<option value="'+x.id+'"'+(Number(cur)===x.id?' selected':'')+'>'+esc(x.id+' - '+x.n)+'</option>').join('');
}
function renderPl(){
    const d = plData;
    document.getElementById('plTitle').textContent = '#'+d.info.id+' - '+esc(d.info.name);
    let ob = document.getElementById('plOnline');
    ob.textContent = d.online ? 'ONLINE' : 'OFFLINE';
    ob.className = 'badge ms-2 '+(d.online?'bg-success':'bg-dark');

    // --- info tab ---
    setV('piName', d.info.name); setV('piHead', d.info.head); setV('piGender', d.info.gender);
    setV('piPower', d.power); setV('piTiem', d.tiemnang);
    setV('piHpg', d.hpg); setV('piMpg', d.mpg); setV('piDameg', d.dameg); setV('piDefg', d.defg); setV('piCritg', d.critg);
    setV('piGold', d.gold); setV('piGem', d.gem); setV('piRuby', d.ruby);
    updateHeadImg();

    // --- items tab ---
    renderGrid('plGridBody', d.items_body, 'body');
    renderGrid('plGridBag', d.items_bag, 'bag');
    renderGrid('plGroupBox', d.items_box, 'box');

    // --- pet tab ---
    if(!d.pet || !d.pet.exists){
        document.getElementById('plPetNone').classList.remove('d-none');
        document.getElementById('plPetForm').classList.add('d-none');
    } else {
        document.getElementById('plPetNone').classList.add('d-none');
        document.getElementById('plPetForm').classList.remove('d-none');
        setV('ppName', d.pet.name); setV('ppType', d.pet.type); setV('ppGender', d.pet.gender); setV('ppStatus', d.pet.status);
        setV('ppPower', d.pet.power); setV('ppTiem', d.pet.tiemnang);
        setV('ppHpg', d.pet.hpg); setV('ppMpg', d.pet.mpg); setV('ppDameg', d.pet.dameg); setV('ppDefg', d.pet.defg); setV('ppCritg', d.pet.critg);
        document.getElementById('ppIcon').innerHTML = '<i class="fa-solid '+(petIcons[d.pet.type]||'fa-paw')+' fa-3x text-primary"></i>';
    }

    // --- tasks tab ---
    let tm = Array.isArray(d.task_main)? d.task_main : [0,0,0];
    fillSelect(document.getElementById('ptMain'), TPL_MAIN, tm[0]);
    setV('ptIndex', tm[1]); setV('ptCount', tm[2]);
    let ts = d.task_side||{};
    fillSelect(document.getElementById('ptSide'), TPL_SIDE, ts.id);
    setV('ptSideC', ts.count); setV('ptSideM', ts.max); setV('ptSideL', ts.left); setV('ptSideLv', ts.level);
    let tc = d.task_clan||{};
    fillSelect(document.getElementById('ptClan'), TPL_CLAN, tc.id);
    setV('ptClanC', tc.count); setV('ptClanM', tc.max); setV('ptClanL', tc.left); setV('ptClanLv', tc.level);
    let tk = d.task_kol||{};
    fillSelect(document.getElementById('ptKol'), TPL_KOL, tk.id);
    setV('ptKolC', tk.count);

    // --- badges tab ---
    let bs = document.getElementById('pbNewSel');
    bs.innerHTML = '<option value="">-- chọn danh hiệu --</option>' +
        TPL_BADGE.filter(b=>!(d.badges||[]).some(x=>Number(x.id)===b.id))
                 .map(b=>'<option value="'+b.id+'">'+esc(b.n)+' (#'+b.id+')</option>').join('');
    renderBadges();
}

function setV(id, v){ const e = document.getElementById(id); if(e) e.value = (v===undefined||v===null)?'':v; }
function gv(id){ const e=document.getElementById(id); return e ? e.value : ''; }
function updateHeadImg(){
    let h = TPL_HEAD.find(x=>Number(x.id)===parseInt(gv('piHead')));
    let g = parseInt(gv('piGender'))||0;
    let ic = h && h.ic && h.ic[g] !== undefined ? h.ic[g] : -1;
    let img = document.getElementById('piHeadImg');
    if(ic >= 0){ img.src = 'item_icon.php?id='+ic; img.style.display=''; }
    else { img.style.display='none'; document.getElementById('piHeadWrap').innerHTML = '<i class="fa-solid fa-user fa-3x text-secondary"></i>'; }
}

// ===== VAT PHAM =====
let plItemsCache = null;
function ensureItems(cb){
    if(plItemsCache){ cb(); return; }
    fetch('bot_items.php').then(r=>r.json()).then(list=>{
        plItemsCache = {};
        (list||[]).forEach(t=>{ plItemsCache[t.i] = t; });
        cb();
    }).catch(cb);
}
function buildItemDatalist(){
    ensureItems(()=>{
        let dl = document.getElementById('plItemDL');
        dl.innerHTML = Object.values(plItemsCache)
            .sort((a,b)=>a.i-b.i)
            .map(t=>'<option value="'+t.i+'">'+esc(t.n)+'</option>').join('');
    });
}
document.getElementById('plAddTemp').addEventListener('input', function(){
    let t = plItemsCache && plItemsCache[parseInt(this.value)];
    document.getElementById('plAddPreview').innerHTML = t ? iconTag(t.c,64,1)+' <span class="small">'+esc(t.n)+'</span>' : '';
});
function renderGrid(gridId, items, type){
    let el = document.getElementById(gridId);
    el.innerHTML = '';
    (items||[]).forEach(it=>{
        let div = document.createElement('div');
        div.className = 'border rounded bg-light text-center';
        div.style.cssText = 'width:76px;padding:2px;cursor:pointer;';
        div.title = it.name+'\n'+it.optstr.replace(/;/g,'\n');
        div.innerHTML = iconTag(it.icon,48,1)+'<div class="text-truncate small" style="font-size:10px">'+esc(it.name)+'</div>'
            + (it.qty>1 ? '<span class="badge bg-primary py-0 px-1" style="font-size:9px">x'+it.qty+'</span>' : '');
        div.onclick = ()=>plItemClick(it, type);
        el.appendChild(div);
    });
    if(!(items||[]).length) el.innerHTML = '<span class="text-muted small">Trống</span>';
}
function plItemClick(it, type){
    let act = prompt('['+it.slot+'] '+it.name+' x'+it.qty+'\nNhập số lượng mới (hoặc để trống để XÓA):', it.qty);
    if(act===null) return;
    if(act===''){ 
        if(!confirm('Xóa "'+it.name+'" khỏi ô '+it.slot+'?')) return;
        plApi('pl_item_del',{id:plId,type:type,slot:it.slot}).then(d=>{alert(d.msg);plReload();});
    } else {
        plApi('pl_item_qty',{id:plId,type:type,slot:it.slot,qty:act}).then(d=>{alert(d.msg);plReload();});
    }
}
function plItemAdd(){
    let temp = parseInt(gv('plAddTemp')), qty = parseInt(gv('plAddQty'))||1, type = gv('plAddType');
    if(isNaN(temp)){ alert('Nhập ID vật phẩm hợp lệ!'); return; }
    plApi('pl_item_add',{id:plId,type:type,tempid:temp,qty:qty}).then(d=>{alert(d.msg);plReload();});
}

// ===== DE TU =====
function plSavePet(){
    plApi('pl_pet_save',{id:plId,pet_type:gv('ppType'),pet_gender:gv('ppGender'),pet_name:gv('ppName'),pet_status:gv('ppStatus'),
        pet_power:gv('ppPower'),pet_tiemnang:gv('ppTiem'),pet_hpg:gv('ppHpg'),pet_mpg:gv('ppMpg'),
        pet_dameg:gv('ppDameg'),pet_defg:gv('ppDefg'),pet_critg:gv('ppCritg')}).then(d=>{alert(d.msg);plReload();});
}

// ===== NHIEM VU =====
function plSaveTask(){
    let q = {id:plId,
        main_id:gv('ptMain'), main_index:gv('ptIndex'), main_count:gv('ptCount'),
        side_id:gv('ptSide'), side_count:gv('ptSideC'), side_max:gv('ptSideM'), side_left:gv('ptSideL'), side_level:gv('ptSideLv'),
        clan_id:gv('ptClan'), clan_count:gv('ptClanC'), clan_max:gv('ptClanM'), clan_left:gv('ptClanL'), clan_level:gv('ptClanLv'),
        kol_id:gv('ptKol'), kol_count:gv('ptKolC')};
    plApi('pl_task_save', q).then(d=>{alert(d.msg);});
}

// ===== DANH HIEU =====
function badgeInfo(id){ return TPL_BADGE.find(b=>Number(b.id)===Number(id)); }
function fmtDate(t){
    if(!t||t<=0) return '-';
    try{ return new Date(Number(t)).toLocaleDateString('vi-VN'); }catch(e){ return '-'; }
}
function renderBadges(){
    let rows = '';
    (plData.badges||[]).forEach(b=>{
        let inf = badgeInfo(b.id);
        rows += '<tr>'
            +'<td>'+b.idx+'</td>'
            +'<td>'+(inf&&inf.ic>=0?iconTag(inf.ic,40,1)+' ':'')+esc(inf?inf.n:'? #'+b.id)+'</td>'
            +'<td>#'+b.id+'</td>'
            +'<td>'+(b.use?'<span class="badge bg-success">Đang dùng</span>':'<span class="badge bg-secondary">Tắt</span>')+'</td>'
            +'<td>'+(b.time<Date.now()?'<span class="text-danger">'+fmtDate(b.time)+' (hết hạn)</span>':fmtDate(b.time))+'</td>'
            +'<td>'
            +'<button class="btn btn-sm '+(b.use?'btn-warning':'btn-success')+' py-0 px-2 me-1" onclick="plBadgeToggle('+b.idx+','+(b.use?0:1)+')">'+(b.use?'Tắt':'Dùng')+'</button>'
            +'<button class="btn btn-sm btn-outline-primary py-0 px-2 me-1" onclick="plBadgeExtend('+b.idx+')">Gia hạn</button>'
            +'<button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="plBadgeDel('+b.idx+')"><i class="fa-solid fa-trash"></i></button>'
            +'</td></tr>';
    });
    if(!rows) rows = '<tr><td colspan="6" class="text-center text-muted">Không có danh hiệu nào</td></tr>';
    document.getElementById('pbRows').innerHTML = rows;
}
function plBadgeToggle(idx, use){
    plApi('pl_badge_toggle',{id:plId,idx:idx,use:use}).then(d=>{alert(d.msg);plReload();});
}
function plBadgeExtend(idx){
    let days = prompt('Gia hạn bao nhiêu ngày?', 30);
    if(days===null||isNaN(days)||days<1) return;
    plApi('pl_badge_toggle',{id:plId,idx:idx,days:days}).then(d=>{alert(d.msg);plReload();});
}
function plBadgeDel(idx){
    if(!confirm('Xóa danh hiệu này khỏi nhân vật?')) return;
    plApi('pl_badge_del',{id:plId,idx:idx}).then(d=>{alert(d.msg);plReload();});
}
function plBadgeAdd(){
    let bid = gv('pbNewSel'), days = parseInt(gv('pbNewDays'))||30;
    if(!bid){ alert('Chọn danh hiệu trước!'); return; }
    plApi('pl_badge_add',{id:plId,badge_id:bid,days:days}).then(d=>{alert(d.msg);plReload();});
}

// ===== LUU THONG TIN CHUNG =====
function plSaveInfo(){
    plApi('pl_save_info',{id:plId,name:gv('piName'),head:gv('piHead'),
        power:gv('piPower'),tiemnang:gv('piTiem'),hpg:gv('piHpg'),mpg:gv('piMpg'),
        dameg:gv('piDameg'),defg:gv('piDefg'),critg:gv('piCritg'),
        gold:gv('piGold'),gem:gv('piGem'),ruby:gv('piRuby')}).then(d=>{alert(d.msg);});
}

// Khoi tao datalist khi mo tab vat pham lan dau
document.querySelector('a[href="#plTabItems"]').addEventListener('shown.bs.tab', buildItemDatalist);
document.getElementById('piHead').addEventListener('input', updateHeadImg);
</script>
