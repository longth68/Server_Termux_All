<?php
require_once(__DIR__ . '/../../core/configs.php');
$conn = SQL();

function getExpThresholds($conn) {
    $query = 'SELECT value FROM others WHERE name = "exp"';
    $result = $conn->query($query);
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $expThresholds = json_decode($row['value'], true);
        return array_filter($expThresholds, function($threshold) {
            return $threshold > 0;
        });
    }
    return [];
}

function mask_username($username) {
    $length = mb_strlen($username, 'UTF-8');
    
    if ($length <= 2) {
        return $username; // Nếu tên quá ngắn thì giữ nguyên
    }
    
    $first = mb_substr($username, 0, 2, 'UTF-8'); // Lấy 2 ký tự đầu
    $last = mb_substr($username, -2, 2, 'UTF-8'); // Lấy 2 ký tự cuối
    $masked = str_repeat('*', $length - 4); // Thay thế phần giữa bằng dấu '*'
    
    return $first . $masked . $last;
}

function getLevel($exp, $expThresholds) {
    if (empty($expThresholds)) {
        return 0; 
    }
    sort($expThresholds);
    foreach ($expThresholds as $index => $threshold) {
        if ($exp < $threshold) {
            return $index;
        }
    }
    return count($expThresholds);
}

$expThresholds = getExpThresholds($conn);
$query = "
   SELECT 
       `name`, 
       CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.exp')) AS UNSIGNED) AS `exp`, 
       CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.levelUpTime')) AS UNSIGNED) AS `levelUpTime` 
   FROM players 
   ORDER BY `exp` DESC, `levelUpTime` ASC 
   LIMIT 10;
";

$result = $conn->query($query);
$dataExp = [];
date_default_timezone_set('Asia/Ho_Chi_Minh');

while ($row = $result->fetch_assoc()) {
    $levelUpTimeValue = (int)$row['levelUpTime'];
    if ($levelUpTimeValue === -1 || $levelUpTimeValue < 0) {
        $timestamp = time();
    } else {
        $timestamp = (int)($levelUpTimeValue / 1000);
        if ($timestamp < 0 || $timestamp > PHP_INT_MAX) {
            $timestamp = time();
        }
    }
    $formattedTime = date('h:i:s A d-m-Y', $timestamp);
    $dataExp[] = [
        'name' => $row['name'],
        'level' => getLevel($row['exp'], $expThresholds),
        'levelUpTime' => $formattedTime,
        'timestamp' => $timestamp,
        'exp' => $row['exp']
    ];
}

usort($dataExp, function($a, $b) {
    if ($b['exp'] !== $a['exp']) {
        return $b['exp'] - $a['exp'];
    }
    return $a['timestamp'] - $b['timestamp'];
});

$sql = "SELECT `players`.`name`, `players`.`id`, `players`.`xu` 
        FROM `players` 
        ORDER BY `players`.`xu` DESC 
        LIMIT 10";
$top_xu = $conn->query($sql);

$sql = "SELECT `id`, `luong`, `username`
        FROM `users`
        ORDER BY `luong` DESC
        LIMIT 10";
$top_luong = $conn->query($sql);

$sql = "SELECT `id`, `tongnap`, `username`
        FROM `users`
        ORDER BY `tongnap` DESC
        LIMIT 10";
$top_nap = $conn->query($sql);

$sql = "SELECT `name`, `level`, `exp` FROM `clan` ORDER BY `level` DESC LIMIT 10;";
$top_clan = $conn->query($sql);
?>
<div class="post-detail flex-fill">
    <div class="text-center text-danger h5 mt-3">BẢNG XẾP HẠNG</div>
    <div class="d-flex justify-content-center">
        <div id="btn-group" class="btn-group">
            <button type="button" class="btn btn-outline-success fw-semibold active" data-table="topCaoThu">Cao Thủ</button>
            <button type="button" class="btn btn-outline-success fw-semibold" data-table="topTaiPhu">Nạp Tiền</button>
            <button type="button" class="btn btn-outline-success fw-semibold" data-table="topGiaToc">Gia Tộc</button>
        </div>
    </div>

    <table id="topCaoThu" class="table table-striped table-hover table-bordered table-responsive mt-3 text-center">
        <thead>
            <tr>
                <th>TOP</th>
                <th>Nhân vật</th>
                <th>Cấp độ</th>
                <th>Thời gian</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dataExp as $index => $mem): ?>
                <?php if ($index < 10): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($mem['name']) ?></td>
                        <td><?= htmlspecialchars($mem['level']) ?></td>
                        <td><?= htmlspecialchars($mem['levelUpTime']) ?></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table id="topTaiPhu" class="table table-striped table-hover table-bordered table-responsive mt-3 text-center d-none">
        <thead>
            <tr>
                <th>TOP</th>
                <th>Nhân vật</th>
                <th>Điểm</th>
            </tr>
        </thead>
        <tbody>
            <?php $rank = 1; while ($mem = $top_nap->fetch_assoc()): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><?= mask_username(htmlspecialchars($mem['username'])) ?></td>

                    <td><?= number_format(htmlspecialchars($mem['tongnap']), 0, ',', '.') ?> RCoin</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <table id="topGiaToc" class="table table-striped table-hover table-bordered table-responsive mt-3 text-center d-none">
        <thead>
            <tr>
                <th>TOP</th>
                <th>Gia tộc</th>
                <th>Cấp</th>
            </tr>
        </thead>
        <tbody>
            <?php $rank = 1; while ($clan = $top_clan->fetch_assoc()): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><?= htmlspecialchars($clan['name']) ?></td>
                    <td><?= htmlspecialchars($clan['level']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const buttons = document.querySelectorAll("#btn-group button");
    const tables = document.querySelectorAll(".post-detail table");
    buttons.forEach(button => {
        button.addEventListener("click", () => {
            buttons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");

            const tableId = button.getAttribute("data-table");
            tables.forEach(table => table.classList.add("d-none"));
            const activeTable = document.getElementById(tableId);
            if (activeTable) {
                activeTable.classList.remove("d-none");
            }
        });
    });
});
</script>
