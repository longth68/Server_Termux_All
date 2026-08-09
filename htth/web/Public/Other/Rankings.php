<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}

include __DIR__ . '/../../Controllers/Header.php';

$rankingTabs = [
    'level' => ['label' => 'Cấp độ', 'unit' => 'cấp', 'metric' => "CASE WHEN JSON_VALID(p.level) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(p.level, '$[0]')) AS UNSIGNED) ELSE 0 END"],
    'exp' => ['label' => 'Kinh nghiệm', 'unit' => 'EXP', 'metric' => 'COALESCE(p.exp, 0)'],
    'pvp' => ['label' => 'PvP', 'unit' => 'điểm', 'metric' => 'COALESCE(p.pvppoint, 0)'],
    'wanted' => ['label' => 'Truy nã', 'unit' => 'điểm', 'metric' => 'COALESCE(p.wanted_point, 0)'],
    'beri' => ['label' => 'Beri', 'unit' => 'Beri', 'metric' => "CASE WHEN JSON_VALID(p.point_inven) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(p.point_inven, '$[0]')) AS UNSIGNED) ELSE 0 END"],
    'rebirth' => ['label' => 'Chuyển sinh', 'unit' => 'lần', 'metric' => 'COALESCE(p.chuyensinh, 0)'],
    'deposit' => ['label' => 'Tổng nạp', 'unit' => 'đ', 'metric' => null],
];

$activeTab = $_GET['tab'] ?? 'level';
if (!isset($rankingTabs[$activeTab])) {
    $activeTab = 'level';
}

$accountByCharacter = [];
$accountRows = $conn->query("SELECT user, `char`, onl, note FROM accounts")->fetchAll(PDO::FETCH_ASSOC);
foreach ($accountRows as $account) {
    $characters = json_decode($account['char'] ?? '[]', true);
    if (!is_array($characters)) {
        continue;
    }
    foreach ($characters as $characterName) {
        if (is_string($characterName) && $characterName !== '') {
            $accountByCharacter[$characterName] = [
                'online' => intval($account['onl'] ?? 0),
                'is_bot' => strtoupper(trim($account['note'] ?? '')) === 'BOT',
            ];
        }
    }
}

$rows = [];
if ($activeTab === 'deposit') {
    $accounts = $conn->query("SELECT a.user, a.`char`, a.onl, a.note,
                                    GREATEST(COALESCE(a.tongnap, 0), COALESCE(n.paid_total, 0)) AS score
                             FROM accounts a
                             LEFT JOIN (
                                 SELECT user_nap, SUM(amount) AS paid_total
                                 FROM napthe
                                 WHERE status = 1
                                 GROUP BY user_nap
                             ) n ON n.user_nap = a.user
                             WHERE GREATEST(COALESCE(a.tongnap, 0), COALESCE(n.paid_total, 0)) > 0
                             ORDER BY score DESC, a.id ASC
                             LIMIT 50")
        ->fetchAll(PDO::FETCH_ASSOC);
    $playerInfoStatement = $conn->prepare("SELECT clazz, exp, CASE WHEN JSON_VALID(level) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(level, '$[0]')) AS UNSIGNED) ELSE 0 END AS level_value FROM players WHERE name = ? LIMIT 1");
    foreach ($accounts as $account) {
        $characters = json_decode($account['char'] ?? '[]', true);
        $characterName = is_array($characters) && !empty($characters[0]) ? $characters[0] : $account['user'];
        $playerInfoStatement->execute([$characterName]);
        $playerInfo = $playerInfoStatement->fetch(PDO::FETCH_ASSOC) ?: [];
        $rows[] = [
            'name' => $characterName,
            'clazz' => intval($playerInfo['clazz'] ?? 0),
            'level_value' => intval($playerInfo['level_value'] ?? 0),
            'exp' => intval($playerInfo['exp'] ?? 0),
            'score' => intval($account['score']),
            'online' => intval($account['onl'] ?? 0),
            'is_bot' => strtoupper(trim($account['note'] ?? '')) === 'BOT',
        ];
    }
} else {
    $metric = $rankingTabs[$activeTab]['metric'];
    $levelExpression = "CASE WHEN JSON_VALID(p.level) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(p.level, '$[0]')) AS UNSIGNED) ELSE 0 END";
    $sql = "SELECT p.name, p.clazz, p.exp, {$levelExpression} AS level_value, {$metric} AS score
            FROM players p
            WHERE {$metric} > 0
            ORDER BY score DESC, p.exp DESC, p.id ASC
            LIMIT 50";
    $players = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($players as $player) {
        $account = $accountByCharacter[$player['name']] ?? ['online' => 0, 'is_bot' => false];
        $player['online'] = $account['online'];
        $player['is_bot'] = $account['is_bot'];
        $rows[] = $player;
    }
}

function rankingClassName($clazz) {
    $classes = [1 => 'Võ Sĩ', 2 => 'Kiếm Khách', 3 => 'Đầu Bếp', 4 => 'Hoa Tiêu', 5 => 'Xạ Thủ'];
    return $classes[intval($clazz)] ?? 'Hải Tặc';
}

function rankingNumber($value) {
    return number_format((float) $value, 0, ',', '.');
}
?>

<style>
.ranking-wrap { max-width: 920px; margin: 18px auto 34px; padding: 0 14px; color: #172033; }
.ranking-panel { background: #fff; border: 1px solid #dbe4ef; border-radius: 8px; box-shadow: 0 3px 12px rgba(15, 23, 42, .12); overflow: hidden; }
.ranking-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; padding: 18px 20px 14px; border-bottom: 1px solid #e5eaf0; }
.ranking-head h1 { margin: 0; font-size: 22px; line-height: 1.25; color: #111827; }
.ranking-head p { margin: 5px 0 0; color: #64748b; font-size: 13px; }
.ranking-time { color: #475569; font-size: 12px; white-space: nowrap; }
.ranking-tabs { display: flex; gap: 7px; padding: 12px 14px; overflow-x: auto; background: #f8fafc; border-bottom: 1px solid #e5eaf0; }
.ranking-tabs { scrollbar-width: none; }
.ranking-tabs::-webkit-scrollbar { display: none; }
.ranking-tab { flex: 0 0 auto; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; background: #fff; text-decoration: none; font-size: 13px; font-weight: 700; }
.ranking-tab:hover { border-color: #2563eb; color: #1d4ed8; }
.ranking-tab.active { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
.ranking-table { width: 100%; border-collapse: collapse; }
.ranking-table th { padding: 11px 12px; background: #f1f5f9; color: #475569; font-size: 12px; text-align: left; }
.ranking-table td { padding: 12px; border-top: 1px solid #edf1f5; vertical-align: middle; }
.ranking-table tbody tr:hover { background: #f8fbff; }
.ranking-place { width: 58px; text-align: center !important; font-weight: 800; color: #475569; }
.ranking-place.top-1 { color: #b7791f; }
.ranking-place.top-2 { color: #64748b; }
.ranking-place.top-3 { color: #b45309; }
.ranking-name { font-weight: 800; color: #111827; word-break: break-word; }
.ranking-meta { margin-top: 3px; color: #64748b; font-size: 12px; }
.ranking-score { text-align: right !important; font-weight: 800; color: #b42318; white-space: nowrap; }
.ranking-badge { display: inline-block; margin-left: 6px; padding: 2px 6px; border-radius: 4px; background: #e0e7ff; color: #3730a3; font-size: 10px; vertical-align: 1px; }
.ranking-online, .ranking-offline { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
.ranking-online { background: #16a34a; box-shadow: 0 0 0 2px #dcfce7; }
.ranking-offline { background: #94a3b8; }
.ranking-empty { padding: 36px 18px; text-align: center; color: #64748b; }
.ranking-note { padding: 11px 14px; border-top: 1px solid #e5eaf0; color: #64748b; background: #f8fafc; font-size: 12px; text-align: center; }
@media (max-width: 620px) {
    .ranking-wrap { padding: 0 8px; }
    .ranking-head { align-items: flex-start; flex-direction: column; }
    .ranking-time { white-space: normal; }
    .ranking-table th, .ranking-table td { padding: 10px 8px; }
    .ranking-place { width: 38px; }
    .ranking-class-column { display: none; }
    .ranking-head h1 { font-size: 19px; }
}
</style>

<main class="ranking-wrap">
    <section class="ranking-panel">
        <div class="ranking-head">
            <div>
                <h1>Bảng Xếp Hạng Hải Tặc</h1>
                <p>Dữ liệu nhân vật được lấy trực tiếp từ máy chủ.</p>
            </div>
            <div class="ranking-time">Cập nhật lúc <?= date('H:i:s d/m/Y') ?></div>
        </div>

        <nav class="ranking-tabs" aria-label="Hạng mục bảng xếp hạng">
            <?php foreach ($rankingTabs as $key => $tab): ?>
                <a class="ranking-tab <?= $activeTab === $key ? 'active' : '' ?>" href="/Users/Rankings?tab=<?= urlencode($key) ?>">
                    <?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (!empty($rows)): ?>
            <div style="overflow-x:auto;">
                <table class="ranking-table">
                    <thead>
                        <tr>
                            <th class="ranking-place">Hạng</th>
                            <th>Nhân vật</th>
                            <th class="ranking-class-column">Hệ phái</th>
                            <th class="ranking-score"><?= htmlspecialchars($rankingTabs[$activeTab]['label'], ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row):
                            $rank = $index + 1;
                            $placeClass = $rank <= 3 ? ' top-' . $rank : '';
                        ?>
                            <tr>
                                <td class="ranking-place<?= $placeClass ?>">#<?= $rank ?></td>
                                <td>
                                    <div class="ranking-name">
                                        <span class="<?= !empty($row['online']) ? 'ranking-online' : 'ranking-offline' ?>"></span>
                                        <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($row['is_bot'])): ?><span class="ranking-badge">BOT</span><?php endif; ?>
                                    </div>
                                    <?php if ($activeTab !== 'level' && $activeTab !== 'deposit'): ?>
                                        <div class="ranking-meta">Cấp <?= intval($row['level_value']) ?> · <?= rankingNumber($row['exp']) ?> EXP</div>
                                    <?php elseif ($activeTab === 'level'): ?>
                                        <div class="ranking-meta"><?= rankingNumber($row['exp']) ?> EXP</div>
                                    <?php endif; ?>
                                </td>
                                <td class="ranking-class-column"><?= htmlspecialchars(rankingClassName($row['clazz']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="ranking-score"><?= rankingNumber($row['score']) ?> <?= htmlspecialchars($rankingTabs[$activeTab]['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="ranking-empty">Chưa có nhân vật đạt điểm trong hạng mục này.</div>
        <?php endif; ?>

        <div class="ranking-note">Trang tự làm mới sau 30 giây. Chấm xanh là nhân vật đang online.</div>
    </section>
</main>

<script>
window.setTimeout(function () {
    window.location.reload();
}, 30000);
</script>

<?php include __DIR__ . '/../../Controllers/Footer.php'; ?>
