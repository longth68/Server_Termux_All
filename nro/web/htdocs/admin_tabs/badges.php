<?php
/* Tab: Danh Hiệu (data_badges) - include bởi admin.php */
$bm = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_badge'])) {
    $act = $_POST['action_badge'];
    $id = (int)($_POST['id'] ?? 0);
    $name = isset_sql(trim($_POST['name'] ?? ''));
    $effect = (int)($_POST['idEffect'] ?? 0);
    $item = (int)($_POST['idItem'] ?? 0);
    $options = isset_sql(trim($_POST['options'] ?? '[]'));

    if ($act === 'add' && $name != '') {
        // HASHIRAMA: data_badges khong co AUTO_INCREMENT -> tu sinh id = max+1
        $newid = (int)(_fetch("SELECT COALESCE(MAX(id),0)+1 AS n FROM data_badges")["n"] ?? 1);
        $q = "INSERT INTO data_badges (id, NAME, idEffect, idItem, Options) VALUES ($newid, '$name', $effect, $item, '$options')";
        $bm = _query($q) ? "Đã thêm danh hiệu #$newid! (restart server để nạp)" : "Lỗi thêm!";
    } elseif ($act === 'edit' && $id > 0) {
        $q = "UPDATE data_badges SET NAME='$name', idEffect=$effect, idItem=$item, Options='$options' WHERE id=$id";
        $bm = _query($q) ? "Đã cập nhật danh hiệu #$id!" : "Lỗi cập nhật!";
    } elseif ($act === 'del' && $id > 0) {
        $bm = _query("DELETE FROM data_badges WHERE id=$id") ? "Đã xóa danh hiệu #$id!" : "Lỗi xóa!";
    }
}

$search = trim($_POST['search_badge'] ?? ($_GET['search_badge'] ?? ''));
$b_rows = [];
$q = "SELECT b.*, i.icon_id FROM data_badges b LEFT JOIN item_template i ON b.idItem = i.id";
if ($search != "") $q .= " WHERE b.NAME LIKE '%".addslashes($search)."%' OR b.id='".addslashes($search)."'";
$q .= " ORDER BY b.id ASC LIMIT 100";
$res = _query($q);
if ($res) { while($r = mysqli_fetch_assoc($res)) $b_rows[] = $r; }

$optNames = []; $q = _query("SELECT id, name FROM item_option_template"); if($q){ while($r=mysqli_fetch_assoc($q)) $optNames[$r['id']] = $r['name']; }
?>
<h3 class="mb-4">Danh Hiệu (data_badges)</h3>
<?php if($bm): ?><div class="alert alert-<?= strpos($bm,'Lỗi')!==false?'danger':'success' ?>"><?= $bm ?></div><?php endif; ?>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Thêm mới danh hiệu</h6>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="action_badge" value="add">
        <div class="col-md-3"><label class="form-label mb-1">Tên danh hiệu</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label mb-1">ID Hiệu ứng</label><input type="number" name="idEffect" class="form-control" value="0"></div>
        <div class="col-md-2"><label class="form-label mb-1">ID Item</label><input type="number" name="idItem" class="form-control" value="0"></div>
        <div class="col-md-4"><label class="form-label mb-1">Options JSON (VD: [{"id":50,"param":10}])</label><input type="text" name="options" class="form-control" value="[]"></div>
        <div class="col-md-1"><button class="btn btn-primary w-100">Thêm</button></div>
    </form>
</div>

<div class="card p-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Danh sách danh hiệu</h6>
    <form method="GET" class="d-flex mb-2">
        <input type="hidden" name="tab" value="badges">
        <input type="text" name="search_badge" class="form-control me-2" placeholder="Tìm theo ID/tên..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary">Tìm</button>
    </form>
    <div style="max-height:600px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Icon</th><th>Tên</th><th>Hiệu ứng</th><th>Options</th><th>Hành Động</th></tr></thead>
            <tbody>
            <?php if(empty($b_rows)): ?>
                <tr><td colspan="6" class="text-center text-muted">Không có dữ liệu</td></tr>
            <?php else: foreach($b_rows as $b): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><img src="item_icon.php?id=<?= (int)$b['icon_id'] ?>&size=3" width="44" height="44" class="border rounded" style="image-rendering:pixelated;" loading="lazy" onerror="this.style.visibility='hidden'" onload="this.style.visibility='visible'"></td>
                    <td><strong><?= htmlspecialchars($b['NAME']) ?></strong></td>
                    <td><?= $b['idEffect'] ?> <?= isset($optNames[$b['idEffect']]) ? '<small class="text-muted">('.$optNames[$b['idEffect']].')</small>' : '' ?></td>
                    <td><small><?= htmlspecialchars($b['Options']) ?></small></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick='editBadge(<?= json_encode($b, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Sửa</button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action_badge" value="del"><input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa danh hiệu #<?= $b['id'] ?>?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editBadge(b){
    let f = prompt("Sửa danh hiệu #"+b.id+"\nNhập: Tên|IDEffect|IDItem|Options", b.NAME+"|"+b.idEffect+"|"+b.idItem+"|"+(b.Options||'[]'));
    if(!f) return;
    let p = f.split('|');
    if(p.length < 4){ alert("Thiếu dữ liệu, cần 4 trường!"); return; }
    let fd = new URLSearchParams();
    fd.append('action_badge','edit'); fd.append('id', b.id);
    fd.append('name', p[0]); fd.append('idEffect', p[1]); fd.append('idItem', p[2]); fd.append('options', p[3]);
    fetch(window.location.href.split('?')[0]+'?tab=badges', {method:'POST', body: fd})
    .then(()=>location.reload());
}
</script>
