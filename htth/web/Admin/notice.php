<?php
ini_set('default_charset', 'UTF-8');
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

$_Title = "Quản lý thông báo";
include __DIR__ . '/../Controllers/Header.php';

if ($_Login == null || $_Admin != 1) {
    echo "<script>window.location.href = '/';</script>";
    exit;
}

$conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS admin_notice_templates (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    type TINYINT NOT NULL DEFAULT 0,
    color TINYINT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS admin_notice_history (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    type TINYINT NOT NULL DEFAULT 0,
    color TINYINT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS admin_game_notices (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slot_key VARCHAR(32) NOT NULL UNIQUE,
    tab_title VARCHAR(80) NOT NULL,
    content TEXT NOT NULL,
    enabled TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

function seedGameNotice($conn, $key, $title, $content, $order) {
    $stmt = $conn->prepare("INSERT IGNORE INTO admin_game_notices (slot_key, tab_title, content, enabled, sort_order) VALUES (?, ?, ?, 1, ?)");
    $stmt->execute([$key, $title, $content, $order]);
}

$gameNoticeCount = intval($conn->query("SELECT COUNT(*) FROM admin_game_notices")->fetchColumn());
if ($gameNoticeCount === 0) {
seedGameNotice($conn, 'news', 'Tin đến',
    "Thông báo: UPDATE - Chức Năng Tuyệt Kỹ Lục Thức\n\n"
    . "Cùng với Haki, Trái ác quỷ và Karate Người cá thì Lục Thức là một trong những sức mạnh độc đáo của One Piece.\n\n"
    . "Chi tiết: https://haitactihon.com/forum/game/6/Huong-Dan-0.html", 1);
seedGameNotice($conn, 'race', 'Đua Top',
    "Thời gian: đang cập nhật\n"
    . "Phần thưởng đua top:\n"
    . "- TOP 1: Trang bị cao cấp, thời trang hiếm, đá khảm và Beri.\n"
    . "- TOP 2-3: Trang bị cao cấp, đá hải thạch và Beri.\n"
    . "- TOP 4-10: Trang bị +10, đá khảm và Beri.", 2);
seedGameNotice($conn, 'giftcode', 'GiftCode',
    "GiftCode:\n- TanThu\n- ThanhVien\n- htth2024\n- baotri", 3);
}

$msg = "";

function sendNoticeCommand($conn, $message, $type, $color) {
    $data = json_encode([
        'message' => $message,
        'type' => intval($type),
        'color' => intval($color),
    ], JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("INSERT INTO web_admin_commands (command, data, status) VALUES ('SEND_NOTICE', ?, 0)");
    $stmt->execute([$data]);
    $hist = $conn->prepare("INSERT INTO admin_notice_history (message, type, color) VALUES (?, ?, ?)");
    $hist->execute([$message, intval($type), intval($color)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_game_notices') {
        $ids = $_POST['notice_id'] ?? [];
        $titles = $_POST['tab_title'] ?? [];
        $contents = $_POST['content'] ?? [];
        $orders = $_POST['sort_order'] ?? [];
        $enabled = $_POST['enabled'] ?? [];
        $stmt = $conn->prepare("UPDATE admin_game_notices SET tab_title = ?, content = ?, enabled = ?, sort_order = ? WHERE id = ?");
        foreach ($ids as $idx => $id) {
            $title = trim($titles[$idx] ?? '');
            $content = trim($contents[$idx] ?? '');
            if ($title === '' || $content === '') {
                continue;
            }
            $stmt->execute([
                $title,
                $content,
                isset($enabled[$id]) ? 1 : 0,
                intval($orders[$idx] ?? 0),
                intval($id),
            ]);
        }
        $msg = "<div class='alert-success'>Đã lưu bảng thông báo trong game. Người chơi đăng nhập lại sẽ thấy nội dung mới.</div>";
    } elseif ($action === 'add_game_notice') {
        $title = trim($_POST['new_tab_title'] ?? '');
        $content = trim($_POST['new_content'] ?? '');
        if ($title !== '' && $content !== '') {
            $key = 'custom_' . time();
            $stmt = $conn->prepare("INSERT INTO admin_game_notices (slot_key, tab_title, content, enabled, sort_order) VALUES (?, ?, ?, 1, ?)");
            $stmt->execute([$key, $title, $content, 99]);
            $msg = "<div class='alert-success'>Đã thêm tab thông báo mới.</div>";
        }
    } elseif ($action === 'delete_game_notice') {
        $id = intval($_POST['delete_notice_id'] ?? ($_POST['id'] ?? 0));
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM admin_game_notices WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "<div class='alert-danger'>Đã xóa tab thông báo.</div>";
        }
    } elseif ($action === 'send_notice') {
        $message = trim($_POST['message'] ?? '');
        $type = intval($_POST['type'] ?? 0);
        $color = intval($_POST['color'] ?? 5);
        if ($message !== '') {
            sendNoticeCommand($conn, $message, $type, $color);
            $msg = "<div class='alert-success'>Đã gửi thông báo tới server.</div>";
        }
    } elseif ($action === 'save_template') {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = intval($_POST['type'] ?? 0);
        $color = intval($_POST['color'] ?? 5);
        if ($title !== '' && $message !== '') {
            $stmt = $conn->prepare("INSERT INTO admin_notice_templates (title, message, type, color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $message, $type, $color]);
            $msg = "<div class='alert-success'>Đã lưu mẫu thông báo.</div>";
        }
    } elseif ($action === 'send_template') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT message, type, color FROM admin_notice_templates WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $tpl = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($tpl) {
            sendNoticeCommand($conn, $tpl['message'], $tpl['type'], $tpl['color']);
            $msg = "<div class='alert-success'>Đã gửi lại mẫu thông báo.</div>";
        }
    } elseif ($action === 'delete_template') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM admin_notice_templates WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "<div class='alert-danger'>Đã xóa mẫu thông báo.</div>";
    } elseif ($action === 'clear_history') {
        $conn->query("TRUNCATE TABLE admin_notice_history");
        $msg = "<div class='alert-danger'>Đã xóa lịch sử gửi thông báo.</div>";
    }
}

$gameNotices = $conn->query("SELECT * FROM admin_game_notices ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$templates = $conn->query("SELECT * FROM admin_notice_templates ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$history = $conn->query("SELECT * FROM admin_notice_history ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.admin-wrap { max-width: 1100px; margin: 24px auto; padding: 0 16px; }
.admin-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:20px; }
.card { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.08); padding:22px; margin-bottom:18px; }
.grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.field label { display:block; font-weight:700; font-size:.86rem; color:#444; margin-bottom:6px; }
.field input, .field select, .field textarea { width:100%; border:1px solid #ddd; border-radius:8px; padding:9px 12px; font-size:.92rem; }
.btn { padding:9px 15px; border:0; border-radius:8px; cursor:pointer; font-weight:700; text-decoration:none; display:inline-block; }
.btn-primary { background:#2563eb; color:#fff; }
.btn-success { background:#10b981; color:#fff; }
.btn-danger { background:#ef4444; color:#fff; }
.btn-warning { background:#f59e0b; color:#fff; }
.alert-success { background:#d1fae5; color:#065f46; border-radius:8px; padding:12px; margin-bottom:14px; }
.alert-danger { background:#fee2e2; color:#991b1b; border-radius:8px; padding:12px; margin-bottom:14px; }
table { width:100%; border-collapse:collapse; }
th { background:#f8fafc; text-align:left; padding:10px; font-size:.82rem; color:#555; }
td { border-bottom:1px solid #f1f5f9; padding:10px; font-size:.86rem; vertical-align:top; }
.notice-row { border:1px solid #eef2f7; border-radius:10px; padding:14px; margin-bottom:14px; background:#fbfdff; }
@media (max-width: 760px) { .grid { grid-template-columns:1fr; } .admin-head { flex-direction:column; align-items:flex-start; } }
</style>

<div class="admin-wrap">
    <div class="admin-head">
        <h2 style="margin:0;font-size:1.5rem;font-weight:800;">Quản lý thông báo trong game</h2>
        <a href="/Admin/index.php" style="color:#2563eb;">Quay lại Admin</a>
    </div>

    <?= $msg ?>

    <div class="card">
        <h3>Bảng thông báo khi đăng nhập</h3>
        <p style="color:#666;margin-top:0;">Nội dung này là bảng trong ảnh: Tin đến, Đua Top, GiftCode. Sau khi lưu, người chơi đăng nhập lại sẽ nhận bảng mới.</p>
        <form method="POST">
            <input type="hidden" id="game_notice_action" name="action" value="save_game_notices">
            <?php foreach ($gameNotices as $n): ?>
                <div class="notice-row">
                    <input type="hidden" name="notice_id[]" value="<?= intval($n['id']) ?>">
                    <div class="grid">
                        <div class="field">
                            <label>Tên tab</label>
                            <input type="text" name="tab_title[]" value="<?= htmlspecialchars($n['tab_title'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="field">
                            <label>Thứ tự</label>
                            <input type="number" name="sort_order[]" value="<?= intval($n['sort_order']) ?>">
                        </div>
                    </div>
                    <div class="field" style="margin-top:12px;">
                        <label>Nội dung</label>
                        <textarea name="content[]" rows="7" required><?= htmlspecialchars($n['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:10px;">
                        <label style="display:inline-flex;gap:8px;align-items:center;margin:0;">
                            <input type="checkbox" name="enabled[<?= intval($n['id']) ?>]" value="1" <?= intval($n['enabled']) === 1 ? 'checked' : '' ?>>
                            Hiển thị tab này
                        </label>
                        <button type="submit" name="delete_notice_id" value="<?= intval($n['id']) ?>" class="btn btn-danger" onclick="document.getElementById('game_notice_action').value='delete_game_notice'; return confirm('Xóa tab thông báo này?');">Xóa</button>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-success" onclick="document.getElementById('game_notice_action').value='save_game_notices';">Lưu bảng thông báo</button>
        </form>

        <form method="POST" style="margin-top:18px;border-top:1px solid #eef2f7;padding-top:18px;">
            <input type="hidden" name="action" value="add_game_notice">
            <h4>Thêm tab mới</h4>
            <div class="grid">
                <div class="field">
                    <label>Tên tab mới</label>
                    <input type="text" name="new_tab_title" placeholder="Ví dụ: Sự kiện">
                </div>
                <div class="field">
                    <label>Nội dung tab mới</label>
                    <textarea name="new_content" rows="3"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:12px;">Thêm tab</button>
        </form>
    </div>

    <div class="card">
        <h3>Gửi thông báo chạy ngay</h3>
        <form method="POST">
            <input type="hidden" name="action" value="send_notice">
            <div class="field" style="margin-bottom:12px;">
                <label>Nội dung thông báo</label>
                <textarea name="message" rows="3" required placeholder="Nhập nội dung thông báo chạy trong game..."></textarea>
            </div>
            <div class="grid" style="margin-bottom:12px;">
                <div class="field">
                    <label>Kiểu thông báo</label>
                    <select name="type">
                        <option value="0">Toàn server</option>
                        <option value="1">Kênh thế giới</option>
                    </select>
                </div>
                <div class="field">
                    <label>Màu</label>
                    <select name="color">
                        <option value="5">Nổi bật</option>
                        <option value="0">Trắng</option>
                        <option value="1">Xanh</option>
                        <option value="2">Đỏ</option>
                        <option value="3">Vàng</option>
                        <option value="4">Tím</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Phát thông báo</button>
        </form>
    </div>

    <div class="card">
        <h3>Lưu mẫu thông báo có sẵn</h3>
        <form method="POST">
            <input type="hidden" name="action" value="save_template">
            <div class="grid" style="margin-bottom:12px;">
                <div class="field">
                    <label>Tên mẫu</label>
                    <input type="text" name="title" required placeholder="Ví dụ: Sự kiện cuối tuần">
                </div>
                <div class="field">
                    <label>Màu</label>
                    <select name="color">
                        <option value="5">Nổi bật</option>
                        <option value="3">Vàng</option>
                        <option value="2">Đỏ</option>
                        <option value="1">Xanh</option>
                    </select>
                </div>
            </div>
            <div class="field" style="margin-bottom:12px;">
                <label>Nội dung mẫu</label>
                <textarea name="message" rows="3" required></textarea>
            </div>
            <input type="hidden" name="type" value="0">
            <input type="hidden" name="action" value="save_template">
            <button type="submit" class="btn btn-success">Lưu mẫu thông báo</button>
        </form>
    </div>

    <div class="card">
        <h3>Mẫu thông báo</h3>
        <table>
            <thead><tr><th>Tên mẫu</th><th>Nội dung</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($templates as $tpl): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($tpl['title'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= nl2br(htmlspecialchars($tpl['message'], ENT_QUOTES, 'UTF-8')) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="send_template">
                            <input type="hidden" name="id" value="<?= intval($tpl['id']) ?>">
                            <button class="btn btn-primary" type="submit">Gửi</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa mẫu thông báo này?');">
                            <input type="hidden" name="action" value="delete_template">
                            <input type="hidden" name="id" value="<?= intval($tpl['id']) ?>">
                            <button class="btn btn-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($templates)): ?><tr><td colspan="3" style="text-align:center;color:#888;">Chưa có mẫu thông báo.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
            <h3 style="margin:0;">Lịch sử gửi gần đây</h3>
            <form method="POST" onsubmit="return confirm('Xóa lịch sử gửi thông báo?');">
                <input type="hidden" name="action" value="clear_history">
                <button type="submit" class="btn btn-warning">Xóa lịch sử</button>
            </form>
        </div>
        <table style="margin-top:12px;">
            <thead><tr><th>Thời gian</th><th>Nội dung</th><th>Màu</th></tr></thead>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= nl2br(htmlspecialchars($h['message'], ENT_QUOTES, 'UTF-8')) ?></td>
                    <td><?= intval($h['color']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($history)): ?><tr><td colspan="3" style="text-align:center;color:#888;">Chưa có lịch sử.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../Controllers/Footer.php'; ?>

