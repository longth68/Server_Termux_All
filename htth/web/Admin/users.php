<?php
ini_set('default_charset', 'UTF-8');
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
$_Title = "Quản lý người chơi";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$msg = "";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (empty($_SESSION['admin_users_csrf'])) {
    $_SESSION['admin_users_csrf'] = bin2hex(random_bytes(32));
}

function accountCharacterNames($charJson) {
    $chars = json_decode($charJson ?? '[]', true);
    if (!is_array($chars)) {
        return [];
    }
    return array_values(array_filter($chars, function ($name) {
        return is_string($name) && trim($name) !== '';
    }));
}

function firstCharacterName($charJson) {
    $chars = accountCharacterNames($charJson);
    return (is_array($chars) && !empty($chars[0])) ? $chars[0] : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete_account') {
        $id = intval($_POST['id'] ?? 0);
        $csrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

        if (!hash_equals($_SESSION['admin_users_csrf'], $csrf)) {
            $msg = "<div class='alert-danger'>Phiên xác nhận đã hết hạn. Hãy tải lại trang rồi thử lại.</div>";
        } elseif ($id <= 0) {
            $msg = "<div class='alert-danger'>Tài khoản không hợp lệ.</div>";
        } else {
            try {
                $conn->beginTransaction();
                $stmt = $conn->prepare("SELECT id, user, `char`, onl, note, admin FROM accounts WHERE id = ? LIMIT 1 FOR UPDATE");
                $stmt->execute([$id]);
                $acc = $stmt->fetch(PDO::FETCH_ASSOC);
                $deletingCurrentAccount = $id === intval($_Id ?? 0);

                if (!$acc) {
                    $conn->rollBack();
                    $msg = "<div class='alert-danger'>Không tìm thấy tài khoản cần xóa.</div>";
                } elseif (intval($acc['onl'] ?? 0) !== 0) {
                    $conn->rollBack();
                    $msg = "<div class='alert-danger'>Tài khoản đang online. Hãy cho tài khoản thoát game trước khi xóa.</div>";
                } elseif ($deletingCurrentAccount && intval($acc['admin'] ?? 0) === 1) {
                    $otherAdmin = $conn->prepare("SELECT COUNT(*) FROM accounts WHERE admin = 1 AND id <> ?");
                    $otherAdmin->execute([$id]);
                    if (intval($otherAdmin->fetchColumn()) < 1) {
                        $conn->rollBack();
                        $msg = "<div class='alert-danger'>Không thể xóa Admin cuối cùng. Hãy cấp quyền Admin cho một tài khoản khác trước.</div>";
                    } else {
                        $canDelete = true;
                    }
                } else {
                    $canDelete = true;
                }

                if (!empty($canDelete)) {
                    $characterNames = accountCharacterNames($acc['char']);
                    $deletePlayer = $conn->prepare("DELETE FROM players WHERE name = ?");
                    $deleteCommands = $conn->prepare("DELETE FROM web_admin_commands WHERE target_user = ? AND status = 0");
                    $deletedCharacters = 0;

                    $deleteCommands->execute([$acc['user']]);
                    foreach ($characterNames as $characterName) {
                        $deletePlayer->execute([$characterName]);
                        $deletedCharacters += $deletePlayer->rowCount();
                        $deleteCommands->execute([$characterName]);
                    }

                    $deleteAccount = $conn->prepare("DELETE FROM accounts WHERE id = ?");
                    $deleteAccount->execute([$id]);

                    if (strtoupper(trim($acc['note'] ?? '')) === 'BOT') {
                        $conn->exec("INSERT INTO web_admin_commands (command, data, status) VALUES ('KILL_BOT', '{}', 0)");
                        $conn->exec("INSERT INTO web_admin_commands (command, data, status) VALUES ('RELOAD_BOT', '{}', 0)");
                    }

                    $conn->commit();
                    if ($deletingCurrentAccount) {
                        $_SESSION = [];
                        if (ini_get('session.use_cookies')) {
                            $params = session_get_cookie_params();
                            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                        }
                        session_destroy();
                        echo "<script>alert('Đã xóa tài khoản đang đăng nhập.');window.location.href='/';</script>";
                        exit;
                    }
                    $msg = "<div class='alert-success'>Đã xóa vĩnh viễn tài khoản <b>" . htmlspecialchars($acc['user'], ENT_QUOTES, 'UTF-8') . "</b> và <b>$deletedCharacters</b> nhân vật.</div>";
                }
            } catch (Throwable $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log('[Admin users delete] ' . $e->getMessage());
                $msg = "<div class='alert-danger'>Không thể xóa tài khoản: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
            }
        }
    } elseif ($action === 'disconnect_account') {
        $id = intval($_POST['id'] ?? 0);
        $csrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if (!hash_equals($_SESSION['admin_users_csrf'], $csrf)) {
            $msg = "<div class='alert-danger'>Phiên xác nhận đã hết hạn. Hãy tải lại trang rồi thử lại.</div>";
        } else {
            $stmt = $conn->prepare("SELECT user, `char` FROM accounts WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $acc = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$acc) {
                $msg = "<div class='alert-danger'>Không tìm thấy tài khoản.</div>";
            } else {
                $characterNames = accountCharacterNames($acc['char']);
                $queueKick = $conn->prepare("INSERT INTO web_admin_commands (command, target_user, data, status) VALUES ('KICK', ?, '{}', 0)");
                foreach ($characterNames as $characterName) {
                    $queueKick->execute([$characterName]);
                }
                if (empty($characterNames)) {
                    $conn->prepare("UPDATE accounts SET onl = 0 WHERE id = ?")->execute([$id]);
                }
                $returnUrl = '/Admin/users.php?search=' . rawurlencode($search);
                $msg = "<div class='alert-success'>Đã yêu cầu ngắt tài khoản <b>" . htmlspecialchars($acc['user'], ENT_QUOTES, 'UTF-8') . "</b>. Trang sẽ tự kiểm tra lại sau vài giây.</div><script>setTimeout(function(){ window.location.href='" . htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') . "'; }, 4500);</script>";
            }
        }
    } elseif ($action === 'set_vip') {
        $id = intval($_POST['id'] ?? 0);
        $vip = max(0, min(7, intval($_POST['vip'] ?? 0)));
        $stmt = $conn->prepare("SELECT id, user, `char` FROM accounts WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($acc) {
            $update = $conn->prepare("UPDATE accounts SET vip = ? WHERE id = ?");
            $update->execute([$vip, $id]);
            $charName = firstCharacterName($acc['char']);
            if ($charName !== '') {
                $data = json_encode(['vip' => $vip], JSON_UNESCAPED_UNICODE);
                $cmd = $conn->prepare("INSERT INTO web_admin_commands (command, target_user, data, status) VALUES ('SET_VIP', ?, ?, 0)");
                $cmd->execute([$charName, $data]);
            }
            $msg = "<div class='alert-success'>Đã cập nhật VIP cho tài khoản <b>" . htmlspecialchars($acc['user'], ENT_QUOTES, 'UTF-8') . "</b> thành VIP $vip.</div>";
        }
    }
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $uId = intval($_GET['id']);
    if ($action === 'ban') {
        $stmt = $conn->prepare("UPDATE accounts SET active = 0 WHERE id = ?");
        $stmt->execute([$uId]);
        $msg = "<div class='alert-success'>Đã khóa tài khoản ID $uId.</div>";
    } elseif ($action === 'unban') {
        $stmt = $conn->prepare("UPDATE accounts SET active = 1 WHERE id = ?");
        $stmt->execute([$uId]);
        $msg = "<div class='alert-success'>Đã mở khóa tài khoản ID $uId.</div>";
    }
}

if ($search !== '') {
    $stmt = $conn->prepare("SELECT id, user, email, active, vnd, tongnap, vip, `char`, onl, note, admin FROM accounts WHERE user LIKE :s OR id = :id ORDER BY id DESC LIMIT 80");
    $stmt->bindValue(':s', "%$search%");
    $stmt->bindValue(':id', intval($search));
} else {
    $stmt = $conn->prepare("SELECT id, user, email, active, vnd, tongnap, vip, `char`, onl, note, admin FROM accounts ORDER BY id DESC LIMIT 80");
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$adminCount = intval($conn->query("SELECT COUNT(*) FROM accounts WHERE admin = 1")->fetchColumn());
?>

<style>
.admin-wrap { max-width: 1120px; margin:24px auto; padding:0 16px; }
.admin-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
.card { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.08); padding:20px; }
.btn { padding:7px 12px; border-radius:8px; border:0; cursor:pointer; font-weight:700; text-decoration:none; display:inline-block; }
.btn-primary { background:#2563eb; color:#fff; }
.btn-danger { background:#ef4444; color:#fff; }
.btn-success { background:#10b981; color:#fff; }
.alert-success { background:#d1fae5; color:#065f46; border-radius:8px; padding:12px; margin-bottom:14px; }
.alert-danger { background:#fee2e2; color:#991b1b; border-radius:8px; padding:12px; margin-bottom:14px; }
.action-buttons { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
.action-buttons form { margin:0; }
table { width:100%; border-collapse:collapse; }
th { background:#f8fafc; padding:10px; text-align:left; font-size:.82rem; color:#555; }
td { border-bottom:1px solid #f1f5f9; padding:10px; font-size:.86rem; vertical-align:middle; }
.vip-select { border:1px solid #ddd; border-radius:8px; padding:6px; }
.badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:.75rem; font-weight:700; }
.badge-ok { background:#d1fae5; color:#065f46; }
.badge-lock { background:#fee2e2; color:#991b1b; }
.badge-vip { background:#fef3c7; color:#92400e; }
</style>

<div class="admin-wrap">
    <div class="admin-head">
        <h2 style="margin:0;font-size:1.5rem;font-weight:800;">Quản lý người chơi</h2>
        <a href="/Admin/index.php" style="color:#2563eb;">Quay lại Admin</a>
    </div>

    <?= $msg ?>

    <div class="card">
        <form method="GET" style="display:flex;gap:8px;margin-bottom:16px;">
            <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Tìm tài khoản hoặc ID..." style="border:1px solid #ddd;border-radius:8px;padding:9px 12px;max-width:360px;width:100%;">
            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
        </form>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tài khoản</th>
                        <th>Nhân vật</th>
                        <th>Tiền</th>
                        <th>VIP</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u):
                    $charName = firstCharacterName($u['char']);
                    $vip = intval($u['vip'] ?? 0);
                ?>
                    <tr>
                        <td><?= intval($u['id']) ?></td>
                        <td><strong><?= htmlspecialchars($u['user'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= $charName !== '' ? htmlspecialchars($charName, ENT_QUOTES, 'UTF-8') : '<span style="color:#999;">Chưa có</span>' ?></td>
                        <td>
                            VND: <?= number_format((int)$u['vnd']) ?><br>
                            Tổng nạp: <?= number_format((int)$u['tongnap']) ?>
                        </td>
                        <td>
                            <form method="POST" style="display:flex;gap:6px;align-items:center;">
                                <input type="hidden" name="action" value="set_vip">
                                <input type="hidden" name="id" value="<?= intval($u['id']) ?>">
                                <select name="vip" class="vip-select">
                                    <?php for ($i = 0; $i <= 7; $i++): ?>
                                    <option value="<?= $i ?>" <?= $vip === $i ? 'selected' : '' ?>>VIP <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                                <button type="submit" class="btn btn-success">Lưu</button>
                            </form>
                        </td>
                        <td>
                            <?php if (intval($u['active']) === 1): ?>
                            <span class="badge badge-ok">Bình thường</span>
                            <?php else: ?>
                            <span class="badge badge-lock">Bị khóa / chưa kích hoạt</span>
                            <?php endif; ?>
                            <?php if (intval($u['onl'] ?? 0) !== 0): ?>
                            <span class="badge" style="background:#dbeafe;color:#1d4ed8;">Đang online</span>
                            <?php endif; ?>
                            <span class="badge badge-vip">VIP <?= $vip ?></span>
                        </td>
                        <td><div class="action-buttons">
                            <?php if (intval($u['active']) === 1): ?>
                            <a href="?action=ban&id=<?= intval($u['id']) ?>&search=<?= urlencode($search) ?>" class="btn btn-danger" onclick="return confirm('Khóa tài khoản này?');">Khóa</a>
                            <?php else: ?>
                            <a href="?action=unban&id=<?= intval($u['id']) ?>&search=<?= urlencode($search) ?>" class="btn btn-success" onclick="return confirm('Mở khóa tài khoản này?');">Mở khóa</a>
                            <?php endif; ?>
                            <a href="players.php?account_id=<?= intval($u['id']) ?>" class="btn btn-primary">Nhân vật</a>
                            <?php if (intval($u['id']) === intval($_Id ?? 0) && intval($u['admin'] ?? 0) === 1 && $adminCount <= 1): ?>
                                <button type="button" class="btn btn-danger" disabled title="Đây là Admin cuối cùng" style="opacity:.45;cursor:not-allowed;">Xóa</button>
                                <small style="display:block;color:#991b1b;max-width:130px;">Admin cuối cùng</small>
                            <?php elseif (intval($u['onl'] ?? 0) !== 0): ?>
                                <form method="POST" action="/Admin/users.php?search=<?= urlencode($search) ?>" onsubmit="return window.confirm('Tài khoản đang online. Ngắt kết nối để chuẩn bị xóa?');">
                                    <input type="hidden" name="id" value="<?= intval($u['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_users_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" name="action" value="disconnect_account" class="btn btn-danger">Ngắt game để xóa</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="/Admin/users.php?search=<?= urlencode($search) ?>" onsubmit="return window.confirm('Xóa vĩnh viễn tài khoản này và toàn bộ nhân vật? Thao tác này không thể hoàn tác.');">
                                    <input type="hidden" name="id" value="<?= intval($u['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_users_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" name="action" value="delete_account" class="btn btn-danger">Xóa</button>
                                </form>
                            <?php endif; ?>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#888;padding:18px;">Không tìm thấy tài khoản nào.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>
