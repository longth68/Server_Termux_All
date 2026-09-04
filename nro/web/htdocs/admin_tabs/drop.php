<?php
/* Tab: Quản lý Drop Item (drop_item) - include bởi admin.php */
$dm = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_drop'])) {
    $act = $_POST['action_drop'];
    $id = (int)($_POST['id'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    $mob = (int)($_POST['mob_id'] ?? -1);
    $map = (int)($_POST['map_id'] ?? -1);
    $item = (int)($_POST['item_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 1);
    $rn = (int)($_POST['rate_num'] ?? 1);
    $rd = (int)($_POST['rate_den'] ?? 100);
    $fam = (int)($_POST['family'] ?? -1);
    $note = isset_sql(trim($_POST['note'] ?? ''));
    $options = isset_sql(trim($_POST['options'] ?? ''));
    $conds = isset_sql(trim($_POST['conditions'] ?? ''));

    if ($act === 'add') {
        $q = "INSERT INTO drop_item (active, mob_id, map_id, item_id, quantity, rate_num, rate_den, family, note, options, conditions)
              VALUES ($active, $mob, $map, $item, $qty, $rn, $rd, $fam, '$note', '$options', '$conds')";
        $dm = _query($q) ? "Đã thêm drop item!" : "Lỗi thêm!";
    } elseif ($act === 'edit' && $id > 0) {
        $q = "UPDATE drop_item SET active=$active, mob_id=$mob, map_id=$map, item_id=$item, quantity=$qty, rate_num=$rn, rate_den=$rd, family=$fam, note='$note', options='$options', conditions='$conds' WHERE id=$id";
        $dm = _query($q) ? "Đã cập nhật drop item #$id!" : "Lỗi cập nhật!";
    } elseif ($act === 'del' && $id > 0) {
        $dm = _query("DELETE FROM drop_item WHERE id=$id") ? "Đã xóa drop item #$id!" : "Lỗi xóa!";
    }
    if (strpos($dm, 'Lỗi') === false) { $dm .= ' (cần bấm "Nạp lại vào Server" để áp dụng ngay)'; }
}

$search = trim($_POST['search_drop'] ?? ($_GET['search_drop'] ?? ''));
$drop_rows = [];
$q = "SELECT * FROM drop_item";
if ($search != "") $q .= " WHERE CAST(id AS CHAR) LIKE '%".addslashes($search)."%' OR CAST(mob_id AS CHAR) LIKE '%".addslashes($search)."%' OR CAST(map_id AS CHAR) LIKE '%".addslashes($search)."%' OR CAST(item_id AS CHAR) LIKE '%".addslashes($search)."%' OR note LIKE '%".addslashes($search)."%'";
$q .= " ORDER BY id DESC LIMIT 100";
$res = _query($q);
if ($res) { while($r = mysqli_fetch_assoc($res)) $drop_rows[] = $r; }

/* Cache tên mob/map/item để hiển thị */
$mobNames = []; $q = _query("SELECT id, name FROM mob_template"); if($q){ while($r=mysqli_fetch_assoc($q)) $mobNames[$r['id']] = $r['name']; }
$mapNames = []; $q = _query("SELECT id, NAME AS name FROM map_template"); if($q){ while($r=mysqli_fetch_assoc($q)) $mapNames[$r['id']] = $r['name']; }
$itemNames = []; $itemIcons = []; $q = _query("SELECT id, NAME AS name, icon_id FROM item_template"); if($q){ while($r=mysqli_fetch_assoc($q)) { $itemNames[$r['id']] = $r['name']; $itemIcons[$r['id']] = $r['icon_id']; } }
?>
<h3 class="mb-4">Quản Lý Drop Item</h3>
<?php if($dm): ?><div class="alert alert-<?= strpos($dm,'Lỗi')!==false?'danger':'success' ?>"><?= $dm ?></div><?php endif; ?>
<button class="btn btn-success mb-3" onclick="callApi('reload_drop')"><i class="fa-solid fa-rotate"></i> Nạp lại Drop vào Server</button>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Thêm mới drop</h6>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="action_drop" value="add">
        <div class="col-md-2"><label class="form-label mb-1">Mob ID</label><input type="number" name="mob_id" class="form-control" value="-1"></div>
        <div class="col-md-2"><label class="form-label mb-1">Map ID</label><input type="number" name="map_id" class="form-control" value="-1"></div>
        <div class="col-md-2"><label class="form-label mb-1">Item ID</label><input type="number" name="item_id" class="form-control" required></div>
        <div class="col-md-1"><label class="form-label mb-1">SL</label><input type="number" name="quantity" class="form-control" value="1"></div>
        <div class="col-md-1"><label class="form-label mb-1">Tỷ lệ</label><input type="number" name="rate_num" class="form-control" value="1"></div>
        <div class="col-md-1"><label class="form-label mb-1">/ mẫu</label><input type="number" name="rate_den" class="form-control" value="100"></div>
        <div class="col-md-1"><label class="form-label mb-1">Family</label><input type="number" name="family" class="form-control" value="-1"></div>
        <div class="col-md-3"><label class="form-label mb-1">Ghi chú</label><input type="text" name="note" class="form-control"></div>
        <div class="col-md-3"><label class="form-label mb-1">Options (30:0;93:30)</label><input type="text" name="options" class="form-control"></div>
        <div class="col-md-3"><label class="form-label mb-1">Conditions (min_power=..;task_id=..)</label><input type="text" name="conditions" class="form-control"></div>
        <div class="col-md-2">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="active" id="dropActive" checked><label class="form-check-label" for="dropActive">Active</label></div>
            <button class="btn btn-primary w-100">Thêm</button>
        </div>
    </form>
</div>

<div class="card p-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Danh sách drop (tối đa 100)</h6>
    <form method="GET" class="d-flex mb-2">
        <input type="hidden" name="tab" value="drop">
        <input type="text" name="search_drop" class="form-control me-2" placeholder="Tìm theo ID/mob/map/item..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary">Tìm</button>
    </form>
    <div style="max-height:600px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Active</th><th>Mob</th><th>Map</th><th>Item</th><th>SL</th><th>Tỷ lệ</th><th>Hành Động</th></tr></thead>
            <tbody>
            <?php if(empty($drop_rows)): ?>
                <tr><td colspan="8" class="text-center text-muted">Không có dữ liệu</td></tr>
            <?php else: foreach($drop_rows as $d): ?>
                <tr>
                    <td><?= $d['id'] ?></td>
                    <td><?= $d['active'] ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>' ?></td>
                    <td><?= $d['mob_id'] ?> <?= isset($mobNames[$d['mob_id']]) ? '<small class="text-muted">('.$mobNames[$d['mob_id']].')</small>' : '' ?></td>
                    <td><?= $d['map_id'] ?> <?= isset($mapNames[$d['map_id']]) ? '<small class="text-muted">('.$mapNames[$d['map_id']].')</small>' : '' ?></td>
                    <td>
                        <?php if(isset($itemIcons[$d['item_id']])): ?>
                            <img src="item_icon.php?id=<?= $itemIcons[$d['item_id']] ?>" width="24" height="24" style="image-rendering:pixelated;" class="me-1">
                        <?php endif; ?>
                        <?= $d['item_id'] ?> <?= isset($itemNames[$d['item_id']]) ? '<small class="text-muted">('.$itemNames[$d['item_id']].')</small>' : '' ?>
                    </td>
                    <td><?= $d['quantity'] ?></td>
                    <td><?= $d['rate_num'] ?>/<?= $d['rate_den'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick='editDrop(<?= json_encode($d, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Sửa</button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action_drop" value="del"><input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa drop #<?= $d['id'] ?>?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editDrop(d){
    let f = prompt("Sửa drop #"+d.id+"\nNhập: MobID|MapID|ItemID|SL|RateNum|RateDen|Family|Note|Options|Conditions",
        d.mob_id+"|"+d.map_id+"|"+d.item_id+"|"+d.quantity+"|"+d.rate_num+"|"+d.rate_den+"|"+d.family+"|"+(d.note||'')+"|"+(d.options||'')+"|"+(d.conditions||''));
    if(!f) return;
    let p = f.split('|');
    if(p.length < 10){ alert("Thiếu dữ liệu, cần 10 trường!"); return; }
    let fd = new URLSearchParams();
    fd.append('action_drop','edit'); fd.append('id', d.id);
    fd.append('mob_id', p[0]); fd.append('map_id', p[1]); fd.append('item_id', p[2]);
    fd.append('quantity', p[3]); fd.append('rate_num', p[4]); fd.append('rate_den', p[5]);
    fd.append('family', p[6]); fd.append('note', p[7]); fd.append('options', p[8]); fd.append('conditions', p[9]);
    if(d.active == 1) fd.append('active','1');
    fetch(window.location.href.split('?')[0]+'?tab=drop', {method:'POST', body: fd})
    .then(()=>location.reload());
}
</script>
