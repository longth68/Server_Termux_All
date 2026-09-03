<?php
/* Tab: Dữ liệu Vật phẩm (item_template) - include bởi admin.php
 * HASHIRAMA schema: id, TYPE, gender, NAME, description, icon_id, part, is_up_to_up, power_require
 * LUU Y: server chi nap item_template khi khoi dong -> sau khi sua can restart server.
 */
$im = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_item'])) {
    $act = $_POST['action_item'];
    $id = (int)($_POST['id'] ?? 0);
    $name = isset_sql(trim($_POST['name'] ?? ''));
    $type = (int)($_POST['type'] ?? 0);
    $gender = (int)($_POST['gender'] ?? 0);
    $desc = isset_sql(trim($_POST['description'] ?? ''));
    $icon = (int)($_POST['icon_id'] ?? 0);
    $part = (int)($_POST['part'] ?? -1);
    $is_up = (int)!empty($_POST['is_up_to_up']);
    $power = (int)($_POST['power_require'] ?? 0);

    if ($act === 'add' && $name != '') {
        $q = "INSERT INTO item_template (id, TYPE, gender, NAME, description, icon_id, part, is_up_to_up, power_require)
              VALUES ($id, $type, $gender, '$name', '$desc', $icon, $part, $is_up, $power)";
        $im = _query($q) ? "Đã thêm vật phẩm ID $id! (restart server để áp dụng)" : "Lỗi thêm vật phẩm (ID có thể đã tồn tại)!";
    } elseif ($act === 'edit' && $id > 0) {
        $q = "UPDATE item_template SET TYPE=$type, gender=$gender, NAME='$name', description='$desc', icon_id=$icon, part=$part, is_up_to_up=$is_up, power_require=$power WHERE id=$id";
        $im = _query($q) ? "Đã cập nhật vật phẩm ID $id! (restart server để áp dụng)" : "Lỗi cập nhật!";
    } elseif ($act === 'del' && $id > 0) {
        $im = _query("DELETE FROM item_template WHERE id=$id") ? "Đã xóa vật phẩm ID $id!" : "Lỗi xóa!";
    }
}

$search = trim($_POST['search_item'] ?? ($_GET['search_item'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$it_rows = [];
$total_rows = 0;
$cnt = _fetch("SELECT COUNT(*) AS c FROM item_template" . ($search != "" ? " WHERE id='".addslashes($search)."' OR NAME LIKE '%".addslashes($search)."%'" : ""));
$total_rows = (int)($cnt['c'] ?? 0);
$totalPages = max(1, (int)ceil($total_rows / $perPage));
$page = min($page, $totalPages);
$q = "SELECT * FROM item_template";
if ($search != "") $q .= " WHERE id='".addslashes($search)."' OR NAME LIKE '%".addslashes($search)."%'";
$q .= " ORDER BY id ASC LIMIT $perPage OFFSET " . (($page-1)*$perPage);
$res = _query($q);
if ($res) { while($r = mysqli_fetch_assoc($res)) $it_rows[] = $r; }
?>
<h3 class="mb-4">Dữ Liệu Vật Phẩm (item_template)</h3>
<?php if($im): ?><div class="alert alert-<?= strpos($im,'Lỗi')!==false?'danger':'success' ?>"><?= $im ?></div><?php endif; ?>
<p class="text-muted">Server HASHIRAMA chỉ nạp item_template lúc khởi động — sửa xong cần <b>restart server</b>.</p>
<div class="d-flex justify-content-between align-items-center mb-2">
    <span class="text-muted small">Tổng: <b><?= number_format($total_rows) ?></b> vật phẩm · Trang <?= $page ?>/<?= $totalPages ?></span>
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="?tab=itemdata&page=1&search_item=<?= urlencode($search) ?>">««</a></li>
            <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="?tab=itemdata&page=<?= $page-1 ?>&search_item=<?= urlencode($search) ?>">«</a></li>
            <?php for ($p = max(1,$page-3); $p <= min($totalPages,$page+3); $p++): ?>
            <li class="page-item <?= $p==$page?'active':'' ?>"><a class="page-link" href="?tab=itemdata&page=<?= $p ?>&search_item=<?= urlencode($search) ?>"><?= $p ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="?tab=itemdata&page=<?= $page+1 ?>&search_item=<?= urlencode($search) ?>">»</a></li>
            <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="?tab=itemdata&page=<?= $totalPages ?>&search_item=<?= urlencode($search) ?>">»»</a></li>
        </ul>
    </nav>
</div>

<div class="card p-3 mb-3">
    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3">Thêm mới vật phẩm</h6>
    <form method="POST" class="row g-2 align-items-end">
        <input type="hidden" name="action_item" value="add">
        <div class="col-md-1"><input type="number" name="id" class="form-control" placeholder="ID" required></div>
        <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Tên vật phẩm" required></div>
        <div class="col-md-1"><input type="number" name="type" class="form-control" placeholder="Type" value="0"></div>
        <div class="col-md-1"><input type="number" name="gender" class="form-control" placeholder="Gender (-1 all)" value="-1"></div>
        <div class="col-md-2"><input type="text" name="description" class="form-control" placeholder="Mô tả"></div>
        <div class="col-md-1"><input type="number" name="icon_id" id="addIconId" class="form-control" placeholder="Icon" value="0" oninput="updItemPreview()"></div>
        <div class="col-md-1"><input type="number" name="part" class="form-control" placeholder="Part (-1)" value="-1"></div>
        <div class="col-md-1"><input type="number" name="power_require" class="form-control" placeholder="SM yêu cầu" value="0"></div>
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
            <thead class="table-dark"><tr><th>ID</th><th>Ảnh (Part)</th><th>Tên</th><th>Type</th><th>Gender</th><th>Icon</th><th>Part</th><th>SM yêu cầu</th><th>Hành Động</th></tr></thead>
            <tbody>
            <?php if(empty($it_rows)): ?>
                <tr><td colspan="9" class="text-center text-muted">Không có dữ liệu</td></tr>
            <?php else: foreach($it_rows as $it): ?>
                <tr>
                    <td><?= $it['id'] ?></td>
                    <td><img src="item_icon.php?id=<?= (int)$it['icon_id'] ?>&size=3" width="44" height="44" class="border rounded" style="image-rendering:pixelated;" loading="lazy" onerror="this.style.visibility='hidden'" onload="this.style.visibility='visible'"></td>
                    <td><strong><?= htmlspecialchars($it['NAME']) ?></strong></td>
                    <td><?= $it['TYPE'] ?></td>
                    <td><?= $it['gender'] ?></td>
                    <td><?= $it['icon_id'] ?></td>
                    <td><?= $it['part'] ?></td>
                    <td><?= number_format($it['power_require']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editItem(<?= htmlspecialchars(json_encode(array(
                            'id'=>$it['id'],'NAME'=>$it['NAME'],'TYPE'=>(int)$it['TYPE'],'gender'=>(int)$it['gender'],
                            'description'=>$it['description'],'icon_id'=>(int)$it['icon_id'],'part'=>(int)$it['part'],
                            'is_up_to_up'=>(int)$it['is_up_to_up'],'power_require'=>(int)$it['power_require']
                        ))) ?>)">Sửa</button>
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
    let f = prompt("Sửa vật phẩm #"+it.id+"\nNhập: Tên|Type|Gender|Description|Icon|Part|IsUp(0/1)|Power",
        it.NAME+"|"+it.TYPE+"|"+it.gender+"|"+(it.description||'')+"|"+it.icon_id+"|"+it.part+"|"+it.is_up_to_up+"|"+it.power_require);
    if(!f) return;
    let p = f.split('|');
    if(p.length < 8){ alert("Thiếu dữ liệu, cần 8 trường!"); return; }
    let fd = new URLSearchParams();
    fd.append('action_item','edit'); fd.append('id', it.id);
    fd.append('name', p[0]); fd.append('type', p[1]); fd.append('gender', p[2]);
    fd.append('description', p[3]); fd.append('icon_id', p[4]); fd.append('part', p[5]);
    fd.append('is_up_to_up', p[6]); fd.append('power_require', p[7]);
    fetch(window.location.href.split('?')[0]+'?tab=itemdata', {method:'POST', body: fd})
    .then(()=>location.reload());
}
</script>
