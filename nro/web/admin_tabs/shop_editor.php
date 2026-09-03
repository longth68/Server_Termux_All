<?php
/* Tab: Shop Manager (shop, tab_shop, item_shop, item_shop_option) - include bởi admin.php
 * HASHIRAMA schema:
 *  shop(id, npc_id, shop_order)
 *  tab_shop(id, shop_id, NAME)
 *  item_shop(id, tab_id, temp_id, gold, gem, is_new, is_sell, item_exchange, quantity_exchange)
 *  item_shop_option(item_shop_id, option_id, param)
 */
$sm2 = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_shop2'])) {
    $act = $_POST['action_shop2'];
    if ($act === 'add_item') {
        $tab = (int)($_POST['tab_id'] ?? 0);
        $temp = (int)($_POST['temp_id'] ?? 0);
        $gold = (int)($_POST['gold'] ?? 0);
        $gem = (int)($_POST['gem'] ?? 0);
        $isnew = isset($_POST['is_new']) ? 1 : 0;
        $issell = isset($_POST['is_sell']) ? 1 : 0;
        $sm2 = _query("INSERT INTO item_shop (tab_id, temp_id, gold, gem, is_new, is_sell) VALUES ($tab, $temp, $gold, $gem, $isnew, $issell)") ? "Đã thêm item vào tab $tab!" : "Lỗi thêm item!";
    } elseif ($act === 'edit_item') {
        $id = (int)($_POST['id'] ?? 0);
        $temp = (int)($_POST['temp_id'] ?? 0);
        $gold = (int)($_POST['gold'] ?? 0);
        $gem = (int)($_POST['gem'] ?? 0);
        $sm2 = _query("UPDATE item_shop SET temp_id=$temp, gold=$gold, gem=$gem WHERE id=$id") ? "Đã cập nhật item #$id!" : "Lỗi cập nhật!";
    } elseif ($act === 'edit_opts') {
        $id = (int)($_POST['id'] ?? 0);
        $raw = trim($_POST['opts'] ?? '');
        _query("DELETE FROM item_shop_option WHERE item_shop_id=$id");
        $n = 0;
        foreach (explode(',', $raw) as $pair) {
            $pp = explode(':', trim($pair));
            if (count($pp) == 2 && is_numeric($pp[0]) && is_numeric($pp[1])) {
                _query("INSERT INTO item_shop_option (item_shop_id, option_id, param) VALUES ($id, " . (int)$pp[0] . ", " . (int)$pp[1] . ")");
                $n++;
            }
        }
        $sm2 = "Đã lưu $n option cho item #$id!";
    } elseif ($act === 'del_item') {
        $id = (int)($_POST['id'] ?? 0);
        _query("DELETE FROM item_shop_option WHERE item_shop_id=$id");
        $sm2 = _query("DELETE FROM item_shop WHERE id=$id") ? "Đã xóa item #$id!" : "Lỗi xóa!";
    } elseif ($act === 'save_tab') {
        $id = (int)($_POST['id'] ?? 0);
        $name = isset_sql(trim($_POST['name'] ?? ''));
        $sm2 = _query("UPDATE tab_shop SET NAME='$name' WHERE id=$id") ? "Đã cập nhật tab #$id!" : "Lỗi cập nhật tab!";
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
    if ($sm2 !== '' && strpos($sm2, 'Lỗi') === false) { $sm2 .= ' (bấm "Nạp lại Shop" để áp dụng ngay)'; }
}

$curShop = (int)($_GET['shop'] ?? 0);
$curTab = (int)($_GET['ts'] ?? 0);

$shop_rows = [];
$q = _query("SELECT s.*, (SELECT COUNT(*) FROM tab_shop t WHERE t.shop_id = s.id) AS tab_count, n.NAME AS npc_name, n.head AS npc_head, n.body AS npc_body, n.leg AS npc_leg
            FROM shop s LEFT JOIN npc_template n ON s.npc_id = n.id ORDER BY s.id ASC");
if ($q) { while($r = mysqli_fetch_assoc($q)) $shop_rows[] = $r; }

// lay icon dau tien cua 1 part (de ve anh NPC)
if (!function_exists('shopPartFirstIcon')) {
    function shopPartFirstIcon($partId, $gender = 0) {
        static $cache = [];
        $partId = (int)$partId;
        if ($partId <= 0) return -1;
        if (!isset($cache[$partId])) {
            $r = _fetch("SELECT DATA FROM part WHERE id=$partId");
            $icon = -1;
            if ($r && !empty($r['DATA'])) {
                $arr = json_decode($r['DATA'], true);
                if (is_array($arr)) {
                    $g = isset($arr[$gender]) ? $arr[$gender] : (isset($arr[0]) ? $arr[0] : null);
                    if (is_array($g) && isset($g[0])) $icon = (int)$g[0];
                }
            }
            $cache[$partId] = $icon;
        }
        return $cache[$partId];
    }
}

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

// lay option cua cac item trong tab hien tai
$optMap = [];
if ($curTab > 0 && !empty($item_rows)) {
    $ids = implode(',', array_map(function($r){ return (int)$r['id']; }, $item_rows));
    $q = _query("SELECT * FROM item_shop_option WHERE item_shop_id IN ($ids)");
    if ($q) { while($r = mysqli_fetch_assoc($q)) $optMap[$r['item_shop_id']][] = $r['option_id'].':'.$r['param']; }
}
?>
<h3 class="mb-4">Shop Manager</h3>
<?php if($sm2): ?><div class="alert alert-<?= strpos($sm2,'Lỗi')!==false?'danger':'success' ?>"><?= $sm2 ?></div><?php endif; ?>
<button class="btn btn-success mb-3" onclick="callApi('update_shop')"><i class="fa-solid fa-shop"></i> Nạp lại Shop vào Server</button>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Danh sách Shop</h6>
    <div style="max-height:300px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>NPC</th><th>Thứ tự</th><th>Số Tab</th><th></th></tr></thead>
            <tbody>
            <?php if(empty($shop_rows)): ?>
                <tr><td colspan="5" class="text-center text-muted">Chưa có shop</td></tr>
            <?php else: foreach($shop_rows as $s):
                $npcHi = shopPartFirstIcon($s['npc_head'] ?? -1);
                $npcBi = shopPartFirstIcon($s['npc_body'] ?? -1);
                $npcLi = shopPartFirstIcon($s['npc_leg'] ?? -1);
            ?>
                <tr class="<?= $curShop==$s['id'] ? 'table-primary' : '' ?>">
                    <td><?= $s['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php if($npcHi >= 0): ?>
                            <img src="item_icon.php?id=<?= $npcHi ?>&size=4" height="52" class="me-2 border rounded bg-light" style="image-rendering:pixelated;" loading="lazy">
                            <?php endif; ?>
                            <div>
                                <strong><?= htmlspecialchars($s['npc_name'] ?? ('NPC #'.$s['npc_id'])) ?></strong>
                                <div class="text-muted" style="font-size:10px;">#<?= $s['npc_id'] ?></div>
                                <?php if($npcBi>=0 || $npcLi>=0): ?>
                                <div class="mt-1 d-flex gap-1">
                                    <?php if($npcBi>=0): ?><img src="item_icon.php?id=<?= $npcBi ?>&size=2" width="24" height="24" style="image-rendering:pixelated;" loading="lazy"><?php endif; ?>
                                    <?php if($npcLi>=0): ?><img src="item_icon.php?id=<?= $npcLi ?>&size=2" width="24" height="24" style="image-rendering:pixelated;" loading="lazy"><?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?= $s['shop_order'] ?></td>
                    <td><?= $s['tab_count'] ?></td>
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
    <?php if($curTab > 0): $curTabName = ''; foreach($tab_rows as $t){ if($t['id']==$curTab){ $curTabName = $t['NAME']; break; } } ?>
    <form method="POST" class="d-flex gap-1 align-items-center">
        <input type="hidden" name="action_shop2" value="save_tab">
        <input type="hidden" name="id" value="<?= $curTab ?>">
        <input type="text" name="name" class="form-control form-control-sm" style="max-width:200px" value="<?= htmlspecialchars($curTabName) ?>" placeholder="Tên tab">
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
        <div class="col-md-2"><label class="form-label mb-1">Giá vàng</label><input type="number" name="gold" class="form-control" value="0"></div>
        <div class="col-md-2"><label class="form-label mb-1">Giá ngọc</label><input type="number" name="gem" class="form-control" value="0"></div>
        <div class="col-md-2">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="is_new" checked><label class="form-check-label">Mới</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="is_sell" checked><label class="form-check-label">Bán được</label></div>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">+ Thêm Item</button></div>
    </form>
    <div style="max-height:500px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Item</th><th>Vàng</th><th>Ngọc</th><th>Options</th><th>Hành Động</th></tr></thead>
            <tbody>
            <?php if(empty($item_rows)): ?>
                <tr><td colspan="6" class="text-center text-muted">Tab trống</td></tr>
            <?php else: foreach($item_rows as $it): ?>
                <tr>
                    <td><?= $it['id'] ?></td>
                    <td><img src="item_icon.php?id=<?= (int)($itemIcons[$it['temp_id']] ?? 0) ?>&size=3" width="40" height="40" class="border rounded me-1 align-middle" style="image-rendering:pixelated;" loading="lazy" onerror="this.style.visibility='hidden'" onload="this.style.visibility='visible'"><?= $it['temp_id'] ?> <?= isset($itemNames[$it['temp_id']]) ? '<small class="text-muted">('.$itemNames[$it['temp_id']].')</small>' : '' ?></td>
                    <td><?= number_format($it['gold']) ?></td>
                    <td><?= number_format($it['gem']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars(implode(', ', $optMap[$it['id']] ?? [])) ?: '—' ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick='editShopItem(<?= json_encode(array('id'=>$it['id'],'temp_id'=>$it['temp_id'],'gold'=>$it['gold'],'gem'=>$it['gem']), JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Sửa</button>
                        <button class="btn btn-sm btn-outline-info" onclick='editShopOpts(<?= json_encode(array('id'=>$it['id'],'opts'=>implode(', ', $optMap[$it['id']] ?? []))) ?>)'>Option</button>
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
    if(ic >= 0){ img.src = 'item_icon.php?id='+ic+'&size=3'; } else { img.style.display='none'; }
}
function editShopItem(it){
    let f = prompt("Sửa item shop #"+it.id+"\nNhập: ItemID|Gold|Gem", it.temp_id+"|"+it.gold+"|"+it.gem);
    if(!f) return;
    let p = f.split('|');
    if(p.length < 3){ alert("Thiếu dữ liệu, cần 3 trường!"); return; }
    let fd = new URLSearchParams();
    fd.append('action_shop2','edit_item'); fd.append('id', it.id);
    fd.append('temp_id', p[0]); fd.append('gold', p[1]); fd.append('gem', p[2]);
    fetch(window.location.href.split('?')[0]+'?tab=shop&shop=<?= $curShop ?>&ts=<?= $curTab ?>', {method:'POST', body: fd})
    .then(()=>location.reload());
}
function editShopOpts(o){
    let f = prompt("Options cho item shop #"+o.id+"\nĐịnh dạng: optionId:param, optionId:param\n(để trống = xóa hết option)", o.opts);
    if(f === null) return;
    let fd = new URLSearchParams();
    fd.append('action_shop2','edit_opts'); fd.append('id', o.id); fd.append('opts', f);
    fetch(window.location.href.split('?')[0]+'?tab=shop&shop=<?= $curShop ?>&ts=<?= $curTab ?>', {method:'POST', body: fd})
    .then(()=>location.reload());
}
</script>
