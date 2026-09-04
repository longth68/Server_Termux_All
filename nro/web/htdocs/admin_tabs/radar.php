<?php
/* Tab: Quản lý Radar (radar) - include bởi admin.php */
$rm = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_radar'])) {
    $act = $_POST['action_radar'];
    $id = (int)($_POST['id'] ?? 0);
    $name = isset_sql(trim($_POST['name'] ?? ''));
    $icon = (int)($_POST['iconId'] ?? 0);
    $mob = (int)($_POST['mob_id'] ?? 1);
    $rank = (int)($_POST['rank'] ?? 0);
    $max = (int)($_POST['max'] ?? 60);
    $type = (int)($_POST['type'] ?? 0);
    $aura = (int)($_POST['aura_id'] ?? -1);
    $info = isset_sql(trim($_POST['info'] ?? ''));
    $body = isset_sql(trim($_POST['body'] ?? '{}'));
    $options = isset_sql(trim($_POST['options'] ?? '[]'));

    if ($act === 'add') {
        $q = "INSERT INTO radar (id, name, iconId, mob_id, rank, max, type, aura_id, info, body, options)
              VALUES ($id, '$name', $icon, $mob, $rank, $max, $type, $aura, '$info', '$body', '$options')";
        $rm = _query($q) ? "Đã thêm radar $id!" : "Lỗi thêm (ID có thể đã tồn tại)!";
    } elseif ($act === 'edit' && $id > 0) {
        $q = "UPDATE radar SET name='$name', iconId=$icon, mob_id=$mob, rank=$rank, max=$max, type=$type, aura_id=$aura, info='$info', body='$body', options='$options' WHERE id=$id";
        $rm = _query($q) ? "Đã cập nhật radar $id!" : "Lỗi cập nhật!";
    } elseif ($act === 'del' && $id > 0) {
        $rm = _query("DELETE FROM radar WHERE id=$id") ? "Đã xóa radar $id!" : "Lỗi xóa!";
    }
}

$search = trim($_POST['search_radar'] ?? ($_GET['search_radar'] ?? ''));
$r_rows = [];
$q = "SELECT * FROM radar";
if ($search != "") $q .= " WHERE id='".addslashes($search)."' OR name LIKE '%".addslashes($search)."%'";
$q .= " ORDER BY id ASC LIMIT 100";
$res = _query($q);
if ($res) { while($r = mysqli_fetch_assoc($res)) $r_rows[] = $r; }

$itemNames = []; $q = _query("SELECT id, NAME AS name FROM item_template WHERE type=33"); if($q){ while($r=mysqli_fetch_assoc($q)) $itemNames[$r['id']] = $r['name']; }
$mobNames = []; $q = _query("SELECT id, name FROM mob_template"); if($q){ while($r=mysqli_fetch_assoc($q)) $mobNames[$r['id']] = $r['name']; }
?>
<h3 class="mb-4">Quản Lý Radar</h3>
<?php if($rm): ?><div class="alert alert-<?= strpos($rm,'Lỗi')!==false?'danger':'success' ?>"><?= $rm ?></div><?php endif; ?>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Thêm mới radar</h6>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="action_radar" value="add">
        <div class="col-md-1"><label class="form-label mb-1">ID</label><input type="number" name="id" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label mb-1">Tên</label><input type="text" name="name" class="form-control"></div>
        <div class="col-md-1"><label class="form-label mb-1">Icon</label><input type="number" name="iconId" class="form-control" value="0"></div>
        <div class="col-md-1"><label class="form-label mb-1">Mob</label><input type="number" name="mob_id" class="form-control" value="1"></div>
        <div class="col-md-1"><label class="form-label mb-1">Rank</label><input type="number" name="rank" class="form-control" value="0"></div>
        <div class="col-md-1"><label class="form-label mb-1">Max</label><input type="number" name="max" class="form-control" value="60"></div>
        <div class="col-md-1"><label class="form-label mb-1">Type</label><input type="number" name="type" class="form-control" value="0"></div>
        <div class="col-md-1"><label class="form-label mb-1">Aura</label><input type="number" name="aura_id" class="form-control" value="-1"></div>
        <div class="col-md-4"><label class="form-label mb-1">Body JSON</label><input type="text" name="body" class="form-control" value="{}"></div>
        <div class="col-md-4"><label class="form-label mb-1">Options JSON</label><input type="text" name="options" class="form-control" value="[]"></div>
        <div class="col-md-6"><label class="form-label mb-1">Info</label><input type="text" name="info" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Thêm</button></div>
    </form>
</div>

<div class="card p-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Danh sách radar</h6>
    <form method="GET" class="d-flex mb-2">
        <input type="hidden" name="tab" value="radar">
        <input type="text" name="search_radar" class="form-control me-2" placeholder="Tìm theo ID/tên..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary">Tìm</button>
    </form>
    <div style="max-height:600px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Tên</th><th>Icon</th><th>Mob</th><th>Rank</th><th>Max</th><th>Hành Động</th></tr></thead>
            <tbody>
            <?php if(empty($r_rows)): ?>
                <tr><td colspan="7" class="text-center text-muted">Không có dữ liệu</td></tr>
            <?php else: foreach($r_rows as $rv): ?>
                <tr>
                    <td><?= $rv['id'] ?></td>
                    <td><strong><?= htmlspecialchars($rv['name']) ?></strong></td>
                    <td>
                        <img src="item_icon.php?id=<?= $rv['iconId'] ?>" width="32" height="32" style="image-rendering:pixelated;" class="me-1 border rounded bg-light">
                        <?= $rv['iconId'] ?>
                    </td>
                    <td><?= $rv['mob_id'] ?> <?= isset($mobNames[$rv['mob_id']]) ? '<small class="text-muted">('.$mobNames[$rv['mob_id']].')</small>' : '' ?></td>
                    <td><?= $rv['rank'] ?></td>
                    <td><?= $rv['max'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick='editRadar(<?= json_encode($rv, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Sửa</button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action_radar" value="del"><input type="hidden" name="id" value="<?= $rv['id'] ?>">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa radar #<?= $rv['id'] ?>?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editRadar(r){
    let f = prompt("Sửa radar #"+r.id+"\nNhập: Tên|Icon|Mob|Rank|Max|Type|Aura|Info|Body|Options",
        (r.name||'')+"|"+r.iconId+"|"+r.mob_id+"|"+r.rank+"|"+r.max+"|"+r.type+"|"+r.aura_id+"|"+(r.info||'')+"|"+(r.body||'{}')+"|"+(r.options||'[]'));
    if(!f) return;
    let p = f.split('|');
    if(p.length < 10){ alert("Thiếu dữ liệu, cần 10 trường!"); return; }
    let fd = new URLSearchParams();
    fd.append('action_radar','edit'); fd.append('id', r.id);
    fd.append('name', p[0]); fd.append('iconId', p[1]); fd.append('mob_id', p[2]);
    fd.append('rank', p[3]); fd.append('max', p[4]); fd.append('type', p[5]);
    fd.append('aura_id', p[6]); fd.append('info', p[7]); fd.append('body', p[8]); fd.append('options', p[9]);
    fetch(window.location.href.split('?')[0]+'?tab=radar', {method:'POST', body: fd})
    .then(()=>location.reload());
}
</script>
