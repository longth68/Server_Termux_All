<?php
/* Tab: Xem Asset - NPC, Quai (mob) - hien thi day du hinh anh tu resources cua server (co phan trang) */
$typeView = $_GET['asset'] ?? 'npc';
$search = trim($_GET['search_asset'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 48;

// helper: lay icon dau tien cua 1 part theo gioi tinh
function partFirstIcon($partId, $gender = 0) {
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
?>
<h3 class="mb-4">Xem Asset Server <small class="text-muted fs-6">(đọc trực tiếp từ DB + resources)</small></h3>
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $typeView=='npc'?'active':'' ?>" href="?tab=assets&asset=npc">NPC</a></li>
    <li class="nav-item"><a class="nav-link <?= $typeView=='mob'?'active':'' ?>" href="?tab=assets&asset=mob">Quái (Mob)</a></li>
</ul>

<?php if ($typeView == 'npc'):
    $w = "";
    if ($search != "") $w = " WHERE id='" . addslashes($search) . "' OR NAME LIKE '%" . addslashes($search) . "%' OR id_name LIKE '%" . addslashes($search) . "%'";
    $cnt = _fetch("SELECT COUNT(*) AS c FROM npc_template$w");
    $totalPages = max(1, (int)ceil(((int)($cnt['c'] ?? 0)) / $perPage));
    $page = min($page, $totalPages);
    $res = _query("SELECT * FROM npc_template$w ORDER BY id ASC LIMIT $perPage OFFSET " . (($page-1)*$perPage));
?>
<form method="GET" class="d-flex mb-2" style="max-width:420px;">
    <input type="hidden" name="tab" value="assets"><input type="hidden" name="asset" value="npc">
    <input type="text" name="search_asset" class="form-control me-2" placeholder="Tìm NPC theo ID/tên..." value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-outline-primary">Tìm</button>
</form>
<nav class="mb-2"><ul class="pagination pagination-sm mb-0">
    <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="?tab=assets&asset=npc&page=<?= max(1,$page-1) ?>">« Trước</a></li>
    <li class="page-item disabled"><span class="page-link">Trang <?= $page ?>/<?= $totalPages ?></span></li>
    <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="?tab=assets&asset=npc&page=<?= min($totalPages,$page+1) ?>">Sau »</a></li>
</ul></nav>
<div class="row g-2">
<?php while($n = mysqli_fetch_assoc($res)):
    $hi = partFirstIcon($n['head']); $bi = partFirstIcon($n['body']); $li = partFirstIcon($n['leg']);
    if ($hi < 0 && $bi < 0 && $li < 0) continue; ?>
    <div class="col-md-2 col-sm-3 col-4 text-center border rounded p-2">
        <div class="mb-1" style="height:96px;display:flex;align-items:center;justify-content:center;">
            <?php if($hi>=0): ?><img src="item_icon.php?id=<?= $hi ?>&size=4" height="90" style="image-rendering:pixelated;" loading="lazy"><?php endif; ?>
        </div>
        <div class="small fw-bold text-truncate" title="<?= htmlspecialchars($n['NAME']) ?>">#<?= $n['id'] ?> <?= htmlspecialchars($n['NAME']) ?></div>
        <div class="text-muted" style="font-size:10px;">head=<?= $n['head'] ?> body=<?= $n['body'] ?> leg=<?= $n['leg'] ?></div>
        <div class="mt-1 d-flex justify-content-center gap-1">
            <?php if($bi>=0): ?><img src="item_icon.php?id=<?= $bi ?>&size=2" width="28" height="28" style="image-rendering:pixelated;" loading="lazy" title="Body"><?php endif; ?>
            <?php if($li>=0): ?><img src="item_icon.php?id=<?= $li ?>&size=2" width="28" height="28" style="image-rendering:pixelated;" loading="lazy" title="Leg"><?php endif; ?>
        </div>
    </div>
<?php endwhile; ?>
</div>

<?php else:
    $w = "";
    if ($search != "") $w = " WHERE m.id='" . addslashes($search) . "' OR m.NAME LIKE '%" . addslashes($search) . "%'";
    $cnt = _fetch("SELECT COUNT(*) AS c FROM mob_template m$w");
    $totalPages = max(1, (int)ceil(((int)($cnt['c'] ?? 0)) / $perPage));
    $page = min($page, $totalPages);
    $res = _query("SELECT m.*, (SELECT COUNT(*) FROM map_template WHERE mobs LIKE CONCAT('%\"', m.id, '\"%')) AS map_count FROM mob_template m$w ORDER BY m.id ASC LIMIT $perPage OFFSET " . (($page-1)*$perPage));
?>
<form method="GET" class="d-flex mb-2" style="max-width:420px;">
    <input type="hidden" name="tab" value="assets"><input type="hidden" name="asset" value="mob">
    <input type="text" name="search_asset" class="form-control me-2" placeholder="Tìm quái theo ID/tên..." value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-outline-primary">Tìm</button>
</form>
<p class="text-muted small">Ảnh quái đọc từ <code>Server\resources\normal\image\{1..4}\monster\{id}.png</code>. Số map có mặt quái được đếm từ cột <code>mobs</code> của map_template.</p>
<nav class="mb-2"><ul class="pagination pagination-sm mb-0">
    <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="?tab=assets&asset=mob&page=<?= max(1,$page-1) ?>">« Trước</a></li>
    <li class="page-item disabled"><span class="page-link">Trang <?= $page ?>/<?= $totalPages ?></span></li>
    <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="?tab=assets&asset=mob&page=<?= min($totalPages,$page+1) ?>">Sau »</a></li>
</ul></nav>
<div class="row g-2">
<?php while($mo = mysqli_fetch_assoc($res)): ?>
    <div class="col-md-2 col-sm-3 col-4 text-center border rounded p-2">
        <div class="mb-1" style="height:96px;display:flex;align-items:center;justify-content:center;">
            <img src="item_icon.php?id=<?= $mo['id'] ?>&type=mob&size=4" height="90" style="image-rendering:pixelated;" loading="lazy" onerror="this.src='item_icon.php?id=0'">
        </div>
        <div class="small fw-bold text-truncate" title="<?= htmlspecialchars($mo['NAME']) ?>">#<?= $mo['id'] ?> <?= htmlspecialchars($mo['NAME']) ?></div>
        <div class="text-muted" style="font-size:10px;">HP: <?= number_format($mo['hp']) ?> · <?= (int)$mo['map_count'] ?> map</div>
    </div>
<?php endwhile; ?>
</div>
<?php endif; ?>
