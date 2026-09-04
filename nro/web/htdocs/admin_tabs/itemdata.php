<?php
/* Tab: Dữ liệu Vật phẩm (item_template) - include bởi admin.php */
$im = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_item'])) {
    $act = $_POST['action_item'];
    $id = (int)($_POST['id'] ?? 0);
    $name = isset_sql(trim($_POST['name'] ?? ''));
    $type = (int)($_POST['type'] ?? 0);
    $gender = (int)($_POST['gender'] ?? 0);
    $desc = isset_sql(trim($_POST['description'] ?? ''));
    $level = (int)($_POST['level'] ?? 0);
    $icon = (int)($_POST['icon_id'] ?? 0);
    $part = (int)($_POST['part'] ?? 0);
    $gold = (int)($_POST['gold'] ?? 0);
    $gold_sell = (int)($_POST['gold_sell'] ?? 0);
    $gem = (int)($_POST['gem'] ?? 0);
    $gem_sell = (int)($_POST['gem_sell'] ?? 0);
    $head = (int)($_POST['head'] ?? -1);
    $body = (int)($_POST['body'] ?? -1);
    $leg = (int)($_POST['leg'] ?? -1);
    $is_up = (int)($_POST['is_up_to_up'] ?? 0);
    $power = (int)($_POST['power_require'] ?? 0);

    if ($act === 'add' && $name != '') {
        $q = "INSERT INTO item_template (id, TYPE, gender, NAME, description, level, icon_id, part, is_up_to_up, power_require, gold, gold_sell, gem, gem_sell, head, body, leg, TypeEvent, isGender)
              VALUES ($id, $type, $gender, '$name', '$desc', $level, $icon, $part, $is_up, $power, $gold, $gold_sell, $gem, $gem_sell, $head, $body, $leg, 0, $gender)";
        $im = _query($q) ? "Đã thêm vật phẩm ID $id!" : "Lỗi thêm vật phẩm (ID có thể đã tồn tại)!";
    } elseif ($act === 'edit' && $id > 0) {
        $q = "UPDATE item_template SET TYPE=$type, gender=$gender, NAME='$name', description='$desc', level=$level, icon_id=$icon, part=$part, is_up_to_up=$is_up, power_require=$power, gold=$gold, gold_sell=$gold_sell, gem=$gem, gem_sell=$gem_sell, head=$head, body=$body, leg=$leg WHERE id=$id";
        $im = _query($q) ? "Đã cập nhật vật phẩm ID $id!" : "Lỗi cập nhật!";
    } elseif ($act === 'del' && $id > 0) {
        $im = _query("DELETE FROM item_template WHERE id=$id") ? "Đã xóa vật phẩm ID $id!" : "Lỗi xóa!";
    }
}

$search = trim($_POST['search_item'] ?? ($_GET['search_item'] ?? ''));
$it_rows = [];
$q = "SELECT * FROM item_template";
if ($search != "") $q .= " WHERE id='".addslashes($search)."' OR NAME LIKE '%".addslashes($search)."%'";
$q .= " ORDER BY id ASC LIMIT 100";
$res = _query($q);
if ($res) { while($r = mysqli_fetch_assoc($res)) $it_rows[] = $r; }
?>
<h3 class="mb-4">Dữ Liệu Vật Phẩm (item_template)</h3>
<?php if($im): ?><div class="alert alert-<?= strpos($im,'Lỗi')!==false?'danger':'success' ?>"><?= $im ?></div><?php endif; ?>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Thêm mới vật phẩm</h6>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="action_item" value="add">
        <div class="col-md-1"><input type="number" name="id" class="form-control" placeholder="ID" required></div>
        <div class="col-md-2"><input type="text" name="name" class="form-control" placeholder="Tên vật phẩm" required></div>
        <div class="col-md-1"><input type="number" name="type" class="form-control" placeholder="Type" value="0"></div>
        <div class="col-md-1"><input type="number" name="gender" class="form-control" placeholder="Gender" value="0"></div>
        <div class="col-md-2"><input type="text" name="description" class="form-control" placeholder="Mô tả"></div>
        <div class="col-md-1"><input type="number" name="level" class="form-control" placeholder="Level" value="0"></div>
        <div class="col-md-1"><input type="number" name="icon_id" id="addIconId" class="form-control" placeholder="Icon" value="0" oninput="updItemPreview()"></div>
        <div class="col-md-1"><input type="number" name="part" class="form-control" placeholder="Part" value="0"></div>
        <div class="col-md-2"><input type="number" name="gold" class="form-control" placeholder="Vàng" value="0"></div>
        <div class="col-md-2"><input type="number" name="gem" class="form-control" placeholder="Ngọc" value="0"></div>
        <div class="col-md-2"><input type="number" name="head" class="form-control" placeholder="Head" value="-1"></div>
        <div class="col-md-2"><input type="number" name="body" class="form-control" placeholder="Body" value="-1"></div>
        <div class="col-md-2"><input type="number" name="leg" class="form-control" placeholder="Leg" value="-1"></div>
        <div class="col-md-2"><input type="number" name="power_require" class="form-control" placeholder="SM yêu cầu" value="0"></div>
        <div class="col-md-2">
            <select name="is_up_to_up" class="form-select">
                <option value="0">Không UP</option><option value="1">Có UP</option>
            </select>
        </div>
        <div class="col-auto text-center">
            <img id="addIconPrev" src="item_icon.php?id=0&size=3" width="56" height="56" class="border rounded" style="image-rendering:pixelated;" onerror="this.style.visibility='hidden'" onload="this.style.visibility='visible'">
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Thêm</button></div>
    </form>
</div>

<div class="card p-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Danh sách vật phẩm (tối đa 100)</h6>
    <form method="GET" class="d-flex mb-2">
        <input type="hidden" name="tab" value="itemdata">
        <input type="text" name="search_item" class="form-control me-2" placeholder="Tìm theo ID hoặc tên..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary" type="submit">Tìm</button>
    </form>
    <div style="max-height:600px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Ảnh (Part)</th><th>Tên</th><th>Type</th><th>Gender</th><th>Level</th><th>Icon</th><th>Gold</th><th>Gem</th><th>Hành Động</th></tr></thead>
            <tbody>
            <?php if(empty($it_rows)): ?>
                <tr><td colspan="10" class="text-center text-muted">Không có dữ liệu</td></tr>
            <?php else: foreach($it_rows as $it): ?>
                <tr>
                    <td><?= $it['id'] ?></td>
                    <td><img src="item_icon.php?id=<?= (int)$it['icon_id'] ?>&size=3" width="44" height="44" class="border rounded" style="image-rendering:pixelated;" loading="lazy" onerror="this.style.visibility='hidden'" onload="this.style.visibility='visible'"></td>
                    <td><strong><?= htmlspecialchars($it['NAME']) ?></strong></td>
                    <td><?= $it['TYPE'] ?></td>
                    <td><?= $it['gender'] ?></td>
                    <td><?= $it['level'] ?></td>
                    <td><?= $it['icon_id'] ?></td>
                    <td><?= number_format($it['gold']) ?></td>
                    <td><?= number_format($it['gem']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editItem(<?= htmlspecialchars(json_encode($it)) ?>)">Sửa</button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action_item" value="del">
                            <input type="hidden" name="id" value="<?= $it['id'] ?>">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa vật phẩm <?= $it['id'] ?>?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updItemPreview(){
    let v = document.getElementById('addIconId').value;
    document.getElementById('addIconPrev').src = 'item_icon.php?id=' + (v||0) + '&size=3';
}
function editItem(it){
    let f = prompt("Sửa vật phẩm #"+it.id+"\nNhập: Tên|Type|Gender|Level|Icon|Gold|Gem|Head|Body|Leg|Power", it.NAME+"|"+it.TYPE+"|"+it.gender+"|"+it.level+"|"+it.icon_id+"|"+it.gold+"|"+it.gem+"|"+it.head+"|"+it.body+"|"+it.leg+"|"+it.power_require);
    if(!f) return;
    let p = f.split('|');
    if(p.length < 11){ alert("Thiếu dữ liệu, cần 11 trường!"); return; }
    let fd = new URLSearchParams();
    fd.append('action_item','edit'); fd.append('id', it.id);
    fd.append('name', p[0]); fd.append('type', p[1]); fd.append('gender', p[2]);
    fd.append('level', p[3]); fd.append('icon_id', p[4]); fd.append('gold', p[5]);
    fd.append('gem', p[6]); fd.append('head', p[7]); fd.append('body', p[8]); fd.append('leg', p[9]);
    fd.append('power_require', p[10]);
    fetch(window.location.href.split('?')[0]+'?tab=itemdata', {method:'POST', body: fd})
    .then(()=>location.reload());
}
</script>
