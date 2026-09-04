<?php
/* Tab: Quản lý Part (part, head_avatar, array_head_2_frames) - include bởi admin.php */
$pm = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_part'])) {
    $act = $_POST['action_part'];
    $id = (int)($_POST['id'] ?? 0);
    if ($act === 'save_part') {
        $type = (int)($_POST['type'] ?? 0);
        $data = isset_sql(trim($_POST['data'] ?? '[]'));
        $check = _fetch("SELECT id FROM part WHERE id=$id");
        if ($check) $pm = _query("UPDATE part SET TYPE=$type, DATA='$data' WHERE id=$id") ? "Đã cập nhật part $id!" : "Lỗi cập nhật part!";
        else $pm = _query("INSERT INTO part (id, TYPE, DATA) VALUES ($id, $type, '$data')") ? "Đã thêm part $id!" : "Lỗi thêm part!";
    } elseif ($act === 'del_part') {
        $pm = _query("DELETE FROM part WHERE id=$id") ? "Đã xóa part $id!" : "Lỗi xóa part!";
    } elseif ($act === 'save_avatar') {
        $avatar = (int)($_POST['avatar_id'] ?? 0);
        $check = _fetch("SELECT head_id FROM head_avatar WHERE head_id=$id");
        if ($check) $pm = _query("UPDATE head_avatar SET avatar_id=$avatar WHERE head_id=$id") ? "Đã cập nhật head_avatar $id!" : "Lỗi cập nhật!";
        else $pm = _query("INSERT INTO head_avatar (head_id, avatar_id) VALUES ($id, $avatar)") ? "Đã thêm head_avatar $id!" : "Lỗi thêm!";
    } elseif ($act === 'del_avatar') {
        $pm = _query("DELETE FROM head_avatar WHERE head_id=$id") ? "Đã xóa head_avatar $id!" : "Lỗi xóa!";
    } elseif ($act === 'save_frames') {
        $data = isset_sql(trim($_POST['data'] ?? '[]'));
        $check = _fetch("SELECT id FROM array_head_2_frames WHERE id=$id");
        if ($check) $pm = _query("UPDATE array_head_2_frames SET data='$data' WHERE id=$id") ? "Đã cập nhật frames $id!" : "Lỗi cập nhật!";
        else $pm = _query("INSERT INTO array_head_2_frames (id, data) VALUES ($id, '$data')") ? "Đã thêm frames $id!" : "Lỗi thêm!";
    } elseif ($act === 'del_frames') {
        $pm = _query("DELETE FROM array_head_2_frames WHERE id=$id") ? "Đã xóa frames $id!" : "Lỗi xóa!";
    }
}

$search = trim($_POST['search_part'] ?? ($_GET['search_part'] ?? ''));
$part_rows = [];
$q = "SELECT * FROM part WHERE 1=1";
if ($search != "") $q .= " AND id LIKE '%".addslashes($search)."%'";
$q .= " ORDER BY id ASC LIMIT 100";
$res = _query($q);
if ($res) { while($r = mysqli_fetch_assoc($res)) $part_rows[] = $r; }

$av_rows = [];
$q = _query("SELECT * FROM head_avatar ORDER BY head_id ASC LIMIT 100");
if ($q) { while($r = mysqli_fetch_assoc($q)) $av_rows[] = $r; }

$fr_rows = [];
$q = _query("SELECT * FROM array_head_2_frames ORDER BY id ASC LIMIT 100");
if ($q) { while($r = mysqli_fetch_assoc($q)) $fr_rows[] = $r; }
?>
<h3 class="mb-4">Quản Lý Part</h3>
<?php if($pm): ?><div class="alert alert-<?= strpos($pm,'Lỗi')!==false?'danger':'success' ?>"><?= $pm ?></div><?php endif; ?>

<ul class="nav nav-tabs mb-3" id="partTabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-part">Part</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-avatar">Head Avatar</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-frames">Head Frames</a></li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-part">
        <div class="card p-3">
            <form method="GET" class="d-flex mb-2">
                <input type="hidden" name="tab" value="part">
                <input type="text" name="search_part" class="form-control me-2" placeholder="Tìm theo ID..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-primary">Tìm</button>
            </form>
            <div style="max-height:500px;overflow-y:auto;">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-dark"><tr><th>ID</th><th>Type</th><th>Data</th><th>Hành Động</th></tr></thead>
                    <tbody>
                    <?php if(empty($part_rows)): ?>
                        <tr><td colspan="4" class="text-center text-muted">Không có dữ liệu</td></tr>
                    <?php else: foreach($part_rows as $pt): ?>
                        <tr>
                            <td><?= $pt['id'] ?></td>
                            <td><?= $pt['TYPE'] ?></td>
                            <td>
                                <?php
                                $pt_data_arr = json_decode($pt['DATA'], true);
                                $pt_icon = -1;
                                if(is_array($pt_data_arr) && isset($pt_data_arr[0]) && is_array($pt_data_arr[0])) {
                                    $pt_icon = (int)$pt_data_arr[0][0];
                                }
                                if($pt_icon >= 0): ?>
                                    <img src="item_icon.php?id=<?= $pt_icon ?>" width="32" height="32" style="image-rendering:pixelated;" class="me-1 border rounded bg-light">
                                <?php endif; ?>
                                <small class="text-truncate d-inline-block align-middle" style="max-width:250px;"><?= htmlspecialchars(mb_substr($pt['DATA'],0,120)) ?></small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick='editPart(<?= json_encode($pt, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Sửa</button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action_part" value="del_part"><input type="hidden" name="id" value="<?= $pt['id'] ?>">
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa part #<?= $pt['id'] ?>?')">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-primary mt-2" onclick="addPart()">+ Thêm Part mới</button>
        </div>
    </div>
    <div class="tab-pane fade" id="tab-avatar">
        <div class="card p-3">
            <table class="table table-bordered table-striped table-sm">
                <thead class="table-dark"><tr><th>Head ID</th><th>Avatar ID</th><th>Hành Động</th></tr></thead>
                <tbody>
                <?php if(empty($av_rows)): ?>
                    <tr><td colspan="3" class="text-center text-muted">Không có dữ liệu</td></tr>
                <?php else: foreach($av_rows as $av): ?>
                    <tr>
                        <td><?= $av['head_id'] ?></td><td><?= $av['avatar_id'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editAvatar(<?= $av['head_id'] ?>,<?= $av['avatar_id'] ?>)">Sửa</button>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action_part" value="del_avatar"><input type="hidden" name="id" value="<?= $av['head_id'] ?>">
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa?')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <button class="btn btn-primary mt-2" onclick="addAvatar()">+ Thêm Head Avatar</button>
        </div>
    </div>
    <div class="tab-pane fade" id="tab-frames">
        <div class="card p-3">
            <table class="table table-bordered table-striped table-sm">
                <thead class="table-dark"><tr><th>ID</th><th>Data</th><th>Hành Động</th></tr></thead>
                <tbody>
                <?php if(empty($fr_rows)): ?>
                    <tr><td colspan="3" class="text-center text-muted">Không có dữ liệu</td></tr>
                <?php else: foreach($fr_rows as $fr): ?>
                    <tr>
                        <td><?= $fr['id'] ?></td><td><small><?= htmlspecialchars(mb_substr($fr['data'],0,120)) ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick='editFrames(<?= json_encode($fr, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Sửa</button>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action_part" value="del_frames"><input type="hidden" name="id" value="<?= $fr['id'] ?>">
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa?')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <button class="btn btn-primary mt-2" onclick="addFrames()">+ Thêm Frames</button>
        </div>
    </div>
</div>

<script>
function partSubmit(fd){
    fetch(window.location.href.split('?')[0]+'?tab=part', {method:'POST', body: fd}).then(()=>location.reload());
}
function addPart(){
    let id = prompt("ID Part mới:");
    if(id===null) return;
    let type = prompt("Type (0=đầu, 1=áo, 2=quần):", "0");
    let data = prompt("Data (VD: [[iconId,dx,dy],...]):", "[]");
    if(type===null||data===null) return;
    let fd = new URLSearchParams();
    fd.append('action_part','save_part'); fd.append('id',id); fd.append('type',type); fd.append('data',data);
    partSubmit(fd);
}
function editPart(p){
    let f = prompt("Sửa part #"+p.id+"\nNhập: Type|Data", p.TYPE+"|"+p.DATA);
    if(!f) return;
    let i = f.indexOf('|');
    let fd = new URLSearchParams();
    fd.append('action_part','save_part'); fd.append('id',p.id); fd.append('type',i<0?'0':f.substring(0,i)); fd.append('data',i<0?f:f.substring(i+1));
    partSubmit(fd);
}
function addAvatar(){
    let head = prompt("Head ID:"); if(head===null) return;
    let av = prompt("Avatar ID:"); if(av===null) return;
    let fd = new URLSearchParams();
    fd.append('action_part','save_avatar'); fd.append('id',head); fd.append('avatar_id',av);
    partSubmit(fd);
}
function editAvatar(head, av){
    let n = prompt("Sửa head_avatar "+head+"\nAvatar ID mới:", av); if(n===null) return;
    let fd = new URLSearchParams();
    fd.append('action_part','save_avatar'); fd.append('id',head); fd.append('avatar_id',n);
    partSubmit(fd);
}
function addFrames(){
    let id = prompt("ID Frames:"); if(id===null) return;
    let data = prompt("Data:", "[]"); if(data===null) return;
    let fd = new URLSearchParams();
    fd.append('action_part','save_frames'); fd.append('id',id); fd.append('data',data);
    partSubmit(fd);
}
function editFrames(fr){
    let data = prompt("Sửa frames #"+fr.id+"\nData:", fr.data); if(data===null) return;
    let fd = new URLSearchParams();
    fd.append('action_part','save_frames'); fd.append('id',fr.id); fd.append('data',data);
    partSubmit(fd);
}
</script>
