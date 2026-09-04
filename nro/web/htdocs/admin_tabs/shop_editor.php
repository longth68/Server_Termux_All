<?php
/* Tab: Shop Manager đầy đủ (shop, tab_shop, item_shop, item_shop_option) - include bởi admin.php */
$sm2 = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_shop2'])) {
    $act = $_POST['action_shop2'];
    if ($act === 'add_item') {
        $tab = (int)($_POST['tab_id'] ?? 0);
        $temp = (int)($_POST['temp_id'] ?? 0);
        $isnew = isset($_POST['is_new']) ? 1 : 0;
        $issell = isset($_POST['is_sell']) ? 1 : 0;
        $type = (int)($_POST['type_sell'] ?? 1);
        $cost = (int)($_POST['cost'] ?? 0);
        $cg = (int)($_POST['costgold'] ?? 0);
        $icon = (int)($_POST['icon_spec'] ?? 0);
        $sm2 = _query("INSERT INTO item_shop (tab_id, temp_id, is_new, is_sell, type_sell, cost, costgold, icon_spec) VALUES ($tab, $temp, $isnew, $issell, $type, $cost, $cg, $icon)") ? "Đã thêm item vào tab $tab!" : "Lỗi thêm item!";
    } elseif ($act === 'edit_item') {
        $id = (int)($_POST['id'] ?? 0);
        $temp = (int)($_POST['temp_id'] ?? 0);
        $isnew = isset($_POST['is_new']) ? 1 : 0;
        $issell = isset($_POST['is_sell']) ? 1 : 0;
        $type = (int)($_POST['type_sell'] ?? 1);
        $cost = (int)($_POST['cost'] ?? 0);
        $cg = (int)($_POST['costgold'] ?? 0);
        $icon = (int)($_POST['icon_spec'] ?? 0);
        $sm2 = _query("UPDATE item_shop SET temp_id=$temp, is_new=$isnew, is_sell=$issell, type_sell=$type, cost=$cost, costgold=$cg, icon_spec=$icon WHERE id=$id") ? "Đã cập nhật item #$id!" : "Lỗi cập nhật!";
    } elseif ($act === 'del_item') {
        $id = (int)($_POST['id'] ?? 0);
        _query("DELETE FROM item_shop_option WHERE item_shop_id=$id");
        $sm2 = _query("DELETE FROM item_shop WHERE id=$id") ? "Đã xóa item #$id!" : "Lỗi xóa!";
    } elseif ($act === 'save_tab') {
        $id = (int)($_POST['id'] ?? 0);
        $shopid = (int)($_POST['shop_id'] ?? 0);
        $name = isset_sql(trim($_POST['name'] ?? ''));
        $sm2 = _query("UPDATE tab_shop SET shop_id=$shopid, NAME='$name' WHERE id=$id") ? "Đã cập nhật tab #$id!" : "Lỗi cập nhật tab!";
    } elseif ($act === 'add_tab') {
        $shopid = (int)($_POST['shop_id'] ?? 0);
        $name = isset_sql(trim($_POST['name'] ?? ''));
        $sm2 = _query("INSERT INTO tab_shop (shop_id, NAME) VALUES ($shopid, '$name')") ? "Đã thêm tab mới!" : "Lỗi thêm tab!";
    } elseif ($act === 'del_tab') {
        $id = (int)($_POST['id'] ?? 0);
        _query("DELETE FROM item_shop_option WHERE item_shop_id IN (SELECT id FROM item_shop WHERE tab_id=$id)");
        _query("DELETE FROM item_shop WHERE tab_id=$id");
        $sm2 = _query("DELETE FROM tab_shop WHERE id=$id") ? "Đã xóa tab #$id và toàn bộ item!" : "Lỗi xóa tab!";
    }
    if (strpos($sm2, 'Lỗi') === false) { $sm2 .= ' (bấm "Nạp lại Shop" để áp dụng)'; }
}

$curShop = (int)($_GET['shop'] ?? 0);
$curTab = (int)($_GET['ts'] ?? 0);

$shop_rows = [];
$q = _query("SELECT s.*, (SELECT COUNT(*) FROM tab_shop t WHERE t.shop_id = s.id) AS tab_count FROM shop s ORDER BY s.id ASC");
if ($q) { while($r = mysqli_fetch_assoc($q)) $shop_rows[] = $r; }

$tab_rows = [];
$item_rows = [];
if ($curShop > 0) {
    $q = _query("SELECT * FROM tab_shop WHERE shop_id=$curShop ORDER BY id ASC");
    if ($q) { while($r = mysqli_fetch_assoc($q)) $tab_rows[] = $r; }
    if ($curTab > 0) {
        $q = _query("SELECT * FROM item_shop WHERE tab_id=$curTab ORDER BY id ASC");
        if ($q) { while($r = mysqli_fetch_assoc($q)) $item_rows[] = $r; }
    }
}

$itemNames = []; $q = _query("SELECT id, NAME AS name FROM item_template"); if($q){ while($r=mysqli_fetch_assoc($q)) $itemNames[$r['id']] = $r['name']; }
$itemIcons = []; $q = _query("SELECT id, icon_id FROM item_template"); if($q){ while($r=mysqli_fetch_assoc($q)) $itemIcons[$r['id']] = $r['icon_id']; }
?>
<h3 class="mb-4">Shop Manager</h3>
<?php if($sm2): ?><div class="alert alert-<?= strpos($sm2,'Lỗi')!==false?'danger':'success' ?>"><?= $sm2 ?></div><?php endif; ?>
<button class="btn btn-success mb-3" onclick="callApi('update_shop')"><i class="fa-solid fa-shop"></i> Nạp lại Shop vào Server</button>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Danh sách Shop</h6>
    <div style="max-height:300px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Tag Name</th><th>NPC</th><th>Type</th><th>Số Tab</th><th></th></tr></thead>
            <tbody>
            <?php if(empty($shop_rows)): ?>
                <tr><td colspan="6" class="text-center text-muted">Chưa có shop</td></tr>
            <?php else: foreach($shop_rows as $s): ?>
                <tr class="<?= $curShop==$s['id'] ? 'table-primary' : '' ?>">
                    <td><?= $s['id'] ?></td><td><strong><?= htmlspecialchars($s['tag_name']) ?></strong></td>
                    <td><?= $s['npc_id'] ?></td><td><?= $s['type_shop'] ?></td><td><?= $s['tab_count'] ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="?tab=shop&shop=<?= $s['id'] ?>">Quản lý</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($curShop > 0): ?>
<div class="card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="m-0 text-muted fw-bold">Tab của Shop #<?= $curShop ?></h6>
        <form method="POST" class="d-flex gap-1">
            <input type="hidden" name="action_shop2" value="add_tab">
            <input type="hidden" name="shop_id" value="<?= $curShop ?>">
            <input type="text" name="name" class="form-control form-control-sm" placeholder="Tên tab mới">
            <button class="btn btn-sm btn-primary">+ Thêm Tab</button>
        </form>
    </div>
    <div class="d-flex flex-wrap gap-1 mb-2">
        <?php foreach($tab_rows as $t): ?>
            <a class="btn btn-sm <?= $curTab==$t['id'] ? 'btn-primary' : 'btn-outline-primary' ?>" href="?tab=shop&shop=<?= $curShop ?>&ts=<?= $t['id'] ?>"><?= htmlspecialchars($t['NAME']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php if($curTab > 0): ?>
    <form method="POST" class="d-flex gap-1 align-items-center">
        <input type="hidden" name="action_shop2" value="save_tab">
        <input type="hidden" name="id" value="<?= $curTab ?>">
        <input type="hidden" name="shop_id" value="<?= $curShop ?>">
        <input type="text" name="name" class="form-control form-control-sm" style="max-width:200px" value="<?= htmlspecialchars($tab_rows[array_search($curTab, array_column($tab_rows,'id'))]['NAME'] ?? '') ?>" placeholder="Tên tab">
        <button class="btn btn-sm btn-success">Đổi tên</button>
        <button class="btn btn-sm btn-danger" name="action_shop2" value="del_tab" formaction="<?= '' ?>?tab=shop&shop=<?= $curShop ?>" onclick="return confirm('Xóa tab và toàn bộ item?')" style="margin-left:8px;">Xóa Tab</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if($curTab > 0): ?>
<div class="card p-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Item trong Tab #<?= $curTab ?></h6>
    <form method="POST" class="row g-2 align-items-end mb-2">
        <input type="hidden" name="action_shop2" value="add_item">
        <input type="hidden" name="tab_id" value="<?= $curTab ?>">
        <div class="col-md-2"><label class="form-label mb-1">Item ID</label><input type="number" name="temp_id" class="form-control" required oninput="shopPrev()"><img id="shopAddPrev" width="40" height="40" class="border rounded ms-2 align-middle" style="image-rendering:pixelated;display:none" onerror="this.style.display='none'" onload="this.style.display='inline-block'"></div>
        <div class="col-md-1"><label class="form-label mb-1">Type</label><input type="number" name="type_sell" class="form-control" value="1"></div>
        <div class="col-md-2"><label class="form-label mb-1">Giá vàng</label><input type="number" name="cost" class="form-control" value="0"></div>
        <div class="col-md-2"><label class="form-label mb-1">Giá ngọc</label><input type="number" name="costgold" class="form-control" value="0"></div>
        <div class="col-md-1"><label class="form-label mb-1">Icon</label><input type="number" name="icon_spec" class="form-control" value="0"></div>
        <div class="col-md-2">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="is_new" checked><label class="form-check-label">Mới</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="is_sell" checked><label class="form-check-label">Bán được</label></div>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">+ Thêm Item</button></div>
    </form>
    <div style="max-height:500px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Item</th><th>Type</th><th>Vàng</th><th>Ngọc</th><th>Hành Động</th></tr></thead>
            <tbody>
            <?php if(empty($item_rows)): ?>
                <tr><td colspan="6" class="text-center text-muted">Tab trống</td></tr>
            <?php else: foreach($item_rows as $it): ?>
                <tr>
                    <td><?= $it['id'] ?></td>
                    <td><img src="item_icon.php?id=<?= (int)($itemIcons[$it['temp_id']] ?? 0) ?>&size=3" width="40" height="40" class="border rounded me-1 align-middle" style="image-rendering:pixelated;" loading="lazy" onerror="this.style.visibility='hidden'" onload="this.style.visibility='visible'"><?= $it['temp_id'] ?> <?= isset($itemNames[$it['temp_id']]) ? '<small class="text-muted">('.$itemNames[$it['temp_id']].')</small>' : '' ?></td>
                    <td><?= $it['type_sell'] ?></td>
                    <td><?= number_format($it['cost']) ?></td>
                    <td><?= number_format($it['costgold']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick='editShopItem(<?= json_encode($it, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Sửa</button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action_shop2" value="del_item"><input type="hidden" name="id" value="<?= $it['id'] ?>">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa item #<?= $it['id'] ?>?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
const SHOP_ICON = <?= json_encode($itemIcons) ?>;
function shopPrev(){
    let id = document.querySelector('input[name="temp_id"]').value;
    let ic = SHOP_ICON[id] || 0;
    let img = document.getElementById('shopAddPrev');
    if(ic > 0){ img.src = 'item_icon.php?id='+ic+'&size=3'; } else { img.style.display='none'; }
}
function editShopItem(it){
    let f = prompt("Sửa item shop #"+it.id+"\nNhập: ItemID|Type|Gold|GoldGem|Icon",
        it.temp_id+"|"+it.type_sell+"|"+it.cost+"|"+it.costgold+"|"+it.icon_spec);
    if(!f) return;
    let p = f.split('|');
    if(p.length < 5){ alert("Thiếu dữ liệu, cần 5 trường!"); return; }
    let fd = new URLSearchParams();
    fd.append('action_shop2','edit_item'); fd.append('id', it.id);
    fd.append('temp_id', p[0]); fd.append('type_sell', p[1]); fd.append('cost', p[2]); fd.append('costgold', p[3]); fd.append('icon_spec', p[4]);
    if(it.is_new == 1) fd.append('is_new','1');
    if(it.is_sell == 1) fd.append('is_sell','1');
    fetch(window.location.href.split('?')[0]+'?tab=shop&shop=<?= $curShop ?>&ts=<?= $curTab ?>', {method:'POST', body: fd})
    .then(()=>location.reload());
}
</script>
