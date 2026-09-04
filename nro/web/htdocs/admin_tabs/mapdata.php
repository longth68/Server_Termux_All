<?php
/* Tab: Dữ liệu Bản đồ (map_template) - include bởi admin.php */
$mm = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_map'])) {
    $act = $_POST['action_map'];
    $id = (int)($_POST['id'] ?? 0);
    if ($act === 'quick') {
        $zones = (int)($_POST['zones'] ?? 1);
        $maxp = (int)($_POST['max_player'] ?? 15);
        $q = "UPDATE map_template SET zones=$zones, max_player=$maxp WHERE id=$id";
        $mm = _query($q) ? "Đã cập nhật zones/max_player map $id!" : "Lỗi cập nhật!";
    } elseif ($act === 'edit' && $id > 0) {
        $name = isset_sql(trim($_POST['name'] ?? ''));
        $type = (int)($_POST['type'] ?? 1);
        $planet = (int)($_POST['planet_id'] ?? 1);
        $zones = (int)($_POST['zones'] ?? 1);
        $maxp = (int)($_POST['max_player'] ?? 15);
        $bg = (int)($_POST['bg_id'] ?? 1);
        $tile = (int)($_POST['tile_id'] ?? 1);
        $waypoints = isset_sql(trim($_POST['waypoints'] ?? ''));
        $mobs = isset_sql(trim($_POST['mobs'] ?? ''));
        $npcs = isset_sql(trim($_POST['npcs'] ?? ''));
        $q = "UPDATE map_template SET NAME='$name', type=$type, planet_id=$planet, zones=$zones, max_player=$maxp, bg_id=$bg, tile_id=$tile, waypoints='$waypoints', mobs='$mobs', npcs='$npcs' WHERE id=$id";
        $mm = _query($q) ? "Đã cập nhật map $id!" : "Lỗi cập nhật!";
    }
}

$search = trim($_POST['search_map'] ?? ($_GET['search_map'] ?? ''));
$map_rows = [];
$q = "SELECT * FROM map_template";
if ($search != "") $q .= " WHERE id='".addslashes($search)."' OR NAME LIKE '%".addslashes($search)."%'";
$q .= " ORDER BY id ASC LIMIT 200";
$res = _query($q);
if ($res) { while($r = mysqli_fetch_assoc($res)) $map_rows[] = $r; }
?>
<h3 class="mb-4">Dữ Liệu Bản Đồ (map_template)</h3>
<?php if($mm): ?><div class="alert alert-<?= strpos($mm,'Lỗi')!==false?'danger':'success' ?>"><?= $mm ?></div><?php endif; ?>
<p class="text-muted">Mobs/NPCs của map là chuỗi JSON. Thay đổi sẽ có hiệu lực khi server nạp lại dữ liệu (thường là khởi động lại).</p>

<div class="card p-3">
    <form method="GET" class="d-flex mb-2">
        <input type="hidden" name="tab" value="mapdata">
        <input type="text" name="search_map" class="form-control me-2" placeholder="Tìm theo ID hoặc tên..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary" type="submit">Tìm</button>
    </form>
    <div style="max-height:600px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Tên</th><th>Type</th><th>Hành Tinh</th><th>Zones</th><th>Max Player</th><th>Hành Động</th></tr></thead>
            <tbody>
            <?php if(empty($map_rows)): ?>
                <tr><td colspan="7" class="text-center text-muted">Không có dữ liệu</td></tr>
            <?php else: foreach($map_rows as $m): ?>
                <tr>
                    <td><?= $m['id'] ?></td>
                    <td><strong><?= htmlspecialchars($m['NAME']) ?></strong></td>
                    <td><?= $m['type'] ?></td>
                    <td><?= $m['planet_id'] ?></td>
                    <td>
                        <form method="POST" class="d-flex gap-1">
                            <input type="hidden" name="action_map" value="quick">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <input type="number" name="zones" class="form-control form-control-sm" style="width:70px" value="<?= $m['zones'] ?>">
                            <input type="number" name="max_player" class="form-control form-control-sm" style="width:70px" value="<?= $m['max_player'] ?>">
                            <button class="btn btn-sm btn-success">Lưu</button>
                        </form>
                    </td>
                    <td><?= $m['max_player'] ?></td>
                    <td><button class="btn btn-sm btn-outline-primary" onclick="editMap(<?= htmlspecialchars(json_encode($m)) ?>)">Sửa</button></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editMap(m){
    let f = prompt("Sửa map #"+m.id+"\nNhập: Tên|Type|HànhTinh|BG|Tile|Waypoints|Mobs|NPCs",
        m.NAME+"|"+m.type+"|"+m.planet_id+"|"+m.bg_id+"|"+m.tile_id+"|"+(m.waypoints||'')+"|"+(m.mobs||'')+"|"+(m.npcs||''));
    if(!f) return;
    let p = f.split('|');
    if(p.length < 8){ alert("Thiếu dữ liệu, cần 8 trường!"); return; }
    let fd = new URLSearchParams();
    fd.append('action_map','edit'); fd.append('id', m.id);
    fd.append('name', p[0]); fd.append('type', p[1]); fd.append('planet_id', p[2]);
    fd.append('bg_id', p[3]); fd.append('tile_id', p[4]);
    fd.append('waypoints', p[5]); fd.append('mobs', p[6]); fd.append('npcs', p[7]);
    fd.append('zones', m.zones); fd.append('max_player', m.max_player);
    fetch(window.location.href.split('?')[0]+'?tab=mapdata', {method:'POST', body: fd})
    .then(()=>location.reload());
}
</script>
