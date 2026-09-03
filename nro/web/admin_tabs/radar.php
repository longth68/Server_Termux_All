<?php
/* Tab: The Radar - QUAN LY BANG collection_book cua server HASHIRAMA
 * Server nap bang nay qua nro.card.CardManager.load():
 *   SELECT * FROM collection_book
 *   -> id, item_id, name, info, icon, rank, max_amount, type, mob_id,
 *      head, body, leg, bag, aura, options(JSON [{id,param,active_card}])
 * Sau khi sua: bam "Nap lai The" -> /api/cards_reload (khong can restart).
 */
$rm = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_radar'])) {
    $act = $_POST['action_radar'];
    // chuan hoa options: CardManager bat buoc moi phan tu co id, param, active_card
    $norm_opts = function ($json_str) {
        $arr = json_decode($json_str, true);
        if (!is_array($arr)) return null;
        $out = [];
        foreach ($arr as $e) {
            if (!isset($e['id'], $e['param'])) continue;
            $out[] = ["id" => (int)$e['id'], "param" => (int)$e['param'], "active_card" => (int)($e['active_card'] ?? 1)];
        }
        return json_encode($out);
    };
    $item_id = (int)($_POST['item_id'] ?? 0);
    $name = isset_sql(trim($_POST['name'] ?? ''));
    $info = isset_sql(trim($_POST['info'] ?? ''));
    $icon = (int)($_POST['icon'] ?? 0);
    $rank = max(0, min(9, (int)($_POST['rank'] ?? 0)));
    $max_amount = max(1, (int)($_POST['max_amount'] ?? 99));
    $type = (int)($_POST['type'] ?? 0);
    $mob_id = (int)($_POST['mob_id'] ?? -1);
    $head = (int)($_POST['head'] ?? 0);
    $body = (int)($_POST['body'] ?? 0);
    $leg = (int)($_POST['leg'] ?? 0);
    $bag = (int)($_POST['bag'] ?? 0);
    $aura = (int)($_POST['aura'] ?? -1);

    if ($act === 'add' && $name != '') {
        $opts = $norm_opts(trim($_POST['options'] ?? '[]'));
        if ($opts === null) { $rm = "Lỗi: Options không phải JSON hợp lệ!"; }
        else {
            $newid = (int)(_fetch("SELECT COALESCE(MAX(id),0)+1 AS n FROM collection_book")["n"] ?? 1);
            $q = "INSERT INTO collection_book (id, item_id, name, info, icon, rank, max_amount, type, mob_id, head, body, leg, bag, aura, options)
                  VALUES ($newid, $item_id, '$name', '$info', $icon, $rank, $max_amount, $type, $mob_id, $head, $body, $leg, $bag, $aura, '" . isset_sql($opts) . "')";
            $rm = _query($q) ? "Đã thêm thẻ radar #$newid!" : "Lỗi thêm!";
        }
    } elseif ($act === 'edit' && (int)$_POST['id'] > 0) {
        $id = (int)$_POST['id'];
        $opts = $norm_opts(trim($_POST['options'] ?? '[]'));
        if ($opts === null) { $rm = "Lỗi: Options không phải JSON hợp lệ!"; }
        else {
            $q = "UPDATE collection_book SET item_id=$item_id, name='$name', info='$info', icon=$icon, rank=$rank, max_amount=$max_amount,
                  type=$type, mob_id=$mob_id, head=$head, body=$body, leg=$leg, bag=$bag, aura=$aura, options='" . isset_sql($opts) . "' WHERE id=$id";
            $rm = _query($q) ? "Đã cập nhật thẻ #$id!" : "Lỗi cập nhật!";
        }
    } elseif ($act === 'del' && (int)$_POST['id'] > 0) {
        $id = (int)$_POST['id'];
        $rm = _query("DELETE FROM collection_book WHERE id=$id") ? "Đã xóa thẻ #$id!" : "Lỗi xóa!";
    }
}

$search = trim($_GET['search_radar'] ?? '');
$r_rows = [];
$q = "SELECT * FROM collection_book";
if ($search != "") $q .= " WHERE name LIKE '%" . addslashes($search) . "%' OR id='" . addslashes($search) . "'";
$q .= " ORDER BY id ASC LIMIT 100";
$res = _query($q);
if ($res) { while($r = mysqli_fetch_assoc($res)) $r_rows[] = $r; }

$mobNames = []; $q = _query("SELECT id, NAME FROM mob_template"); if($q){ while($r=mysqli_fetch_assoc($q)) $mobNames[$r['id']] = $r['NAME']; }
?>
<h3 class="mb-4">Thẻ Radar <small class="text-muted fs-6">(bảng collection_book - CardManager của HASHIRAMA)</small></h3>
<?php if($rm): ?><div class="alert alert-<?= strpos($rm,'Lỗi')!==false?'danger':'success' ?>"><?= $rm ?></div><?php endif; ?>
<button class="btn btn-success mb-3" onclick="callApi('cards_reload')"><i class="fa-solid fa-rotate"></i> Nạp lại Thẻ vào Server (cards_reload)</button>
<p class="text-muted small">Thẻ radar là vật phẩm chỉ điểm quái/boss. Cột <code>icon</code> là icon id hiển thị; <code>mob_id</code> là mục tiêu radar; <code>options</code> là JSON <code>[{"id":optionId,"param":giatri,"active_card":1}]</code>.</p>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Thêm thẻ mới</h6>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="action_radar" value="add">
        <div class="col-md-2"><label class="form-label mb-1">Tên</label><input type="text" name="name" class="form-control" required oninput="rdIconPrev(this.value,'rdAddPrev')"></div>
        <div class="col-md-2"><label class="form-label mb-1">Info</label><input type="text" name="info" class="form-control"></div>
        <div class="col-md-1"><label class="form-label mb-1">Icon</label><input type="number" name="icon" class="form-control" value="0"></div>
        <div class="col-md-1"><label class="form-label mb-1">Item ID</label><input type="number" name="item_id" class="form-control" value="-1"></div>
        <div class="col-md-1"><label class="form-label mb-1">Rank</label><input type="number" name="rank" class="form-control" value="1" min="0" max="9"></div>
        <div class="col-md-1"><label class="form-label mb-1">Max</label><input type="number" name="max_amount" class="form-control" value="99"></div>
        <div class="col-md-1"><label class="form-label mb-1">Type</label><input type="number" name="type" class="form-control" value="0"></div>
        <div class="col-md-1"><label class="form-label mb-1">Mob ID</label><input type="number" name="mob_id" class="form-control" value="-1"></div>
        <div class="col-md-1"><label class="form-label mb-1">Aura</label><input type="number" name="aura" class="form-control" value="-1"></div>
        <div class="col-md-4"><label class="form-label mb-1">Options JSON</label><input type="text" name="options" class="form-control" value='[]'></div>
        <div class="col-md-1"><button class="btn btn-primary w-100">Thêm</button></div>
    </form>
</div>

<div class="card p-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Danh sách thẻ</h6>
    <form method="GET" class="d-flex mb-2" style="max-width:420px;">
        <input type="hidden" name="tab" value="radar">
        <input type="text" name="search_radar" class="form-control me-2" placeholder="Tìm theo ID/tên..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary">Tìm</button>
    </form>
    <div style="max-height:600px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Icon</th><th>Tên</th><th>Rank/Max</th><th>Mục tiêu (mob)</th><th>Type</th><th>Options</th><th>Hành Động</th></tr></thead>
            <tbody>
            <?php if(empty($r_rows)): ?>
                <tr><td colspan="8" class="text-center text-muted">Không có dữ liệu</td></tr>
            <?php else: foreach($r_rows as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><img src="item_icon.php?id=<?= (int)$r['icon'] ?>&size=3" width="44" height="44" class="border rounded" style="image-rendering:pixelated;" loading="lazy" onerror="this.style.visibility='hidden'" onload="this.style.visibility='visible'"></td>
                    <td><strong><?= htmlspecialchars($r['name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars(mb_substr((string)$r['info'],0,40)) ?></small></td>
                    <td><?= $r['rank'] ?>/<?= $r['max_amount'] ?></td>
                    <td>#<?= $r['mob_id'] ?> <?= isset($mobNames[$r['mob_id']]) ? '<small class="text-muted">('.$mobNames[$r['mob_id']].')</small>' : '' ?></td>
                    <td><?= $r['type'] ?></td>
                    <td><small><?= htmlspecialchars((string)$r['options']) ?></small></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick='editRadar(<?= json_encode(array(
                            'id'=>$r['id'],'item_id'=>$r['item_id'],'name'=>$r['name'],'info'=>$r['info'],'icon'=>$r['icon'],
                            'rank'=>$r['rank'],'max_amount'=>$r['max_amount'],'type'=>$r['type'],'mob_id'=>$r['mob_id'],
                            'head'=>$r['head'],'body'=>$r['body'],'leg'=>$r['leg'],'bag'=>$r['bag'],'aura'=>$r['aura'],'options'=>$r['options']
                        ), JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Sửa</button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action_radar" value="del"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa thẻ #<?= $r['id'] ?>?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function rdIconPrev(iconId, imgId){
    let img = document.getElementById(imgId);
    if(!img) return;
    img.src = 'item_icon.php?id=' + (parseInt(iconId)||0) + '&size=3';
}
function editRadar(r){
    let f = prompt("Sửa thẻ #"+r.id+"\nNhập: Tên|Info|Icon|ItemID|Rank|Max|Type|MobID|Head|Body|Leg|Bag|Aura|Options",
        r.name+"|"+(r.info||'')+"|"+r.icon+"|"+r.item_id+"|"+r.rank+"|"+r.max_amount+"|"+r.type+"|"+r.mob_id+"|"+r.head+"|"+r.body+"|"+r.leg+"|"+r.bag+"|"+r.aura+"|"+(r.options||'[]'));
    if(!f) return;
    let p = f.split('|');
    if(p.length < 14){ alert("Thiếu dữ liệu, cần 14 trường!"); return; }
    let fd = new URLSearchParams();
    fd.append('action_radar','edit'); fd.append('id', r.id);
    fd.append('name', p[0]); fd.append('info', p[1]); fd.append('icon', p[2]); fd.append('item_id', p[3]);
    fd.append('rank', p[4]); fd.append('max_amount', p[5]); fd.append('type', p[6]); fd.append('mob_id', p[7]);
    fd.append('head', p[8]); fd.append('body', p[9]); fd.append('leg', p[10]); fd.append('bag', p[11]);
    fd.append('aura', p[12]); fd.append('options', p[13]);
    fetch(window.location.href.split('?')[0]+'?tab=radar', {method:'POST', body: fd})
    .then(()=>location.reload());
}
</script>
