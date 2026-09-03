<?php
/* Tab: Lịch sử giao dịch (history_transaction) - include bởi admin.php
 * HASHIRAMA: bang khong co cot id -> sap xep theo time_tran
 */
$search = trim($_POST['search_lichsu'] ?? ($_GET['search_lichsu'] ?? ''));
$h_rows = [];
$q = "SELECT player_1, player_2, item_player_1, item_player_2, time_tran FROM history_transaction";
if ($search != "") $q .= " WHERE player_1 LIKE '%".addslashes($search)."%' OR player_2 LIKE '%".addslashes($search)."%'";
$q .= " ORDER BY time_tran DESC LIMIT 200";
$res = _query($q);
if ($res) { while($r = mysqli_fetch_assoc($res)) $h_rows[] = $r; }
?>
<h3 class="mb-4">Lịch Sử Giao Dịch</h3>
<p class="text-muted">Lịch sử giao dịch giữa người chơi (chỉ xem, không sửa).</p>

<div class="card p-3">
    <form method="GET" class="d-flex mb-2">
        <input type="hidden" name="tab" value="lichsu">
        <input type="text" name="search_lichsu" class="form-control me-2" placeholder="Tìm theo tên người chơi..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary">Tìm</button>
    </form>
    <div style="max-height:600px;overflow-y:auto;">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>#</th><th>Người chơi 1</th><th>Người chơi 2</th><th>Vật phẩm 1</th><th>Vật phẩm 2</th><th>Thời gian</th></tr></thead>
            <tbody>
            <?php if(empty($h_rows)): ?>
                <tr><td colspan="6" class="text-center text-muted">Không có dữ liệu</td></tr>
            <?php else: $stt=0; foreach($h_rows as $h): $stt++; ?>
                <tr>
                    <td><?= $stt ?></td>
                    <td><strong><?= htmlspecialchars($h['player_1']) ?></strong></td>
                    <td><strong><?= htmlspecialchars($h['player_2']) ?></strong></td>
                    <td><small><?= htmlspecialchars($h['item_player_1']) ?></small></td>
                    <td><small><?= htmlspecialchars($h['item_player_2']) ?></small></td>
                    <td><?= $h['time_tran'] ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
