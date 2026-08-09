<?php
require_once(__DIR__ . '/../../../../core/configs.php');

if (!isset($_SESSION['user'])) {
    header('Location: /home');
    exit;
}

$user = $_SESSION['user'];
if ($user['admin_web'] != 1) {
    header("Location: /home");
    exit();
}

$conn = SQL();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($action == 'edit') {
            $sql = "SELECT id, name, type, file_path FROM files WHERE id = $id";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $editPost = $result->fetch_assoc();
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger" role="alert">File Game không tồn tại.</div>';
            }
        } elseif ($action == 'delete') {
            $sql = "DELETE FROM files WHERE id = $id";
            if ($conn->query($sql) === TRUE) {
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">File Game đã được xóa thành công!</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Có lỗi xảy ra khi xóa file game.</div>';
            }
            header('Location: /admin/update-file');
            exit();
        } elseif ($action == 'update') {
            if (isset($_POST['id'], $_POST['name'], $_POST['type'], $_POST['file_path'])) {
                $id = intval($_POST['id']);
                $name = $conn->real_escape_string($_POST['name']);
                $type = $conn->real_escape_string($_POST['type']);
                $file_path = $conn->real_escape_string($_POST['file_path']);
                $sql = "UPDATE files SET name='$name', type='$type', file_path='$file_path' WHERE id=$id";
                if ($conn->query($sql) === TRUE) {
                    $_SESSION['message'] = '<div class="alert alert-success" role="alert">File Game đã được cập nhật thành công!</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Có lỗi xảy ra khi cập nhật file game.</div>';
                }
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Dữ liệu form không đầy đủ!</div>';
            }
            header('Location: /admin/update-file');
            exit();
        } elseif ($action == 'add') {
            if (isset($_POST['name'], $_POST['type']) && isset($_FILES['file_path']) && $_FILES['file_path']['error'] == 0) {
                $name = $conn->real_escape_string($_POST['name']);
                $type = $conn->real_escape_string($_POST['type']);
                $targetDir = './files/';
                $targetFile = $targetDir . basename($_FILES['file_path']['name']);
                $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
               // $allowedTypes = ['apk', 'java', 'ipa', 'exe'];
				$allowedTypes = [];
				switch (strtoupper($type)) {
					case 'JAVA':
						$allowedTypes = ['jar', 'java'];
						break;
					case 'APK':
						$allowedTypes = ['apk'];
						break;
					case 'IPHONE':
						$allowedTypes = ['ipa'];
						break;
					case 'PC':
						$allowedTypes = ['zip', 'rar'];
						break;
					default:
						$_SESSION['message'] = '<div class="alert alert-danger">Loại dữ liệu không hợp lệ.</div>';
						header('Location: /admin/update-file');
						exit();
				}
                if (in_array($fileType, $allowedTypes)) {
                    if (move_uploaded_file($_FILES['file_path']['tmp_name'], $targetFile)) {
                        $relativePath = './files/' . basename($_FILES['file_path']['name']);
                        $sql = "INSERT INTO files (name, type, file_path) VALUES ('$name', '$type', '$relativePath')";
                        if ($conn->query($sql) === TRUE) {
                            $_SESSION['message'] = '<div class="alert alert-success" role="alert">File Game đã được thêm thành công!</div>';
                        } else {
                            $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Có lỗi xảy ra khi thêm file game.</div>';
                        }
                    } else {
                        $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Có lỗi xảy ra khi tải lên tệp.</div>';
                    }
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Loại tệp không được phép.</div>';
                }
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger" role="alert">Dữ liệu form không đầy đủ hoặc có lỗi khi tải lên tệp!</div>';
            }
            header('Location: /admin/update-file');
            exit();
        }
        
    }
}

if (isset($_SESSION['message'])) {
    echo $_SESSION['message'];
    unset($_SESSION['message']);
}

$sql = "SELECT id, name, type, file_path FROM files";
$result = $conn->query($sql);
?>

<div class="bg-content" style="  border-radius: 1rem; padding:10px">
    <div style="text-align:center;">
        <h4><?php echo htmlspecialchars($user['username']); ?> đang vào upFile</h4>
    </div>
    <div class="container mb-2">
        <div class="row text-center justify-content-center g-2 mt-1">
            <div class="col-12 col-md-4 col-lg-3">
                <a class="btn btn-menu btn-success w-100 fw-semibold" href="/admin/home">Quay lại</a>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <button class="btn btn-menu btn-success w-100 fw-semibold" onclick="showAddFileForm()">Thêm File Game</button>
            </div>
        </div>
    </div>
    <h3 style="text-align: center; margin: 20px;">Danh File Game</h3>
    <table class="table text-white fw-semibold mb-0" role="table">
        <thead>
            <tr class="text-start fw-bold text-uppercase gs-0">
                <th>ID</th>
                <th>Tên Phiên Bản</th>
                <th>Kiểu Dữ Liệu</th>
                <th>Link Phiên Bản</th>
                <th>Tùy Chỉnh</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['name']}</td>
                                <td>{$row['type']}</td>
                                <td>{$row['file_path']}</td>
                                <td>
                                    <form  method=\"post\" style=\"display:inline;\">
                                        <input type=\"hidden\" name=\"action\" value=\"edit\">
                                        <input type=\"hidden\" name=\"id\" value=\"{$row['id']}\">
                                        <button type=\"submit\" class=\"btn btn-danger btn-sm\">Chỉnh Sửa</button>
                                    </form> - 
                                    <form method=\"post\" style=\"display:inline;\">
                                        <input type=\"hidden\" name=\"action\" value=\"delete\">
                                        <input type=\"hidden\" name=\"id\" value=\"{$row['id']}\">
                                        <button type=\"submit\" class=\"btn btn-primary btn-sm\">Xóa Bỏ</button>
                                    </form>
                                </td>
                            </tr>";
                }
            } else {
                echo "<tr><td colspan='5'>Không có file game nào.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<div id="addEditForm" style="display:none; border-radius: 1rem; padding:10px; margin-top:10px;">
    <h3 style="text-align:center;">Thêm/Chỉnh Sửa File Game</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add" id="formAction">
        <input type="hidden" name="id" id="fileId">
        <div class="mb-3">
            <label for="name" class="form-label">Tên Phiên Bản</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="type" class="form-label">Kiểu Dữ Liệu</label>
            <select class="form-control" id="type" name="type" required>
                <option value="JAVA" <?php echo (isset($editPost) && $editPost['type'] == 'JAVA') ? 'selected' : ''; ?>>JAVA</option>
                <option value="IPHONE" <?php echo (isset($editPost) && $editPost['type'] == 'IPHONE') ? 'selected' : ''; ?>>IPHONE</option>
                <option value="APK" <?php echo (isset($editPost) && $editPost['type'] == 'APK') ? 'selected' : ''; ?>>APK</option>
                <option value="PC" <?php echo (isset($editPost) && $editPost['type'] == 'PC') ? 'selected' : ''; ?>>PC</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="file_path" class="form-label">Chọn Tệp Phiên Bản</label>
            <!-- <input type="file" class="form-control" id="file_path" name="file_path" accept=".apk,.java,.ipa,.exe" required> -->
			<input type="file" class="form-control" id="file_path" name="file_path" accept=".apk,.java,.jar,.ipa,.zip,.rar">

        </div>
        <button type="submit" class="btn btn-primary">Lưu Lại</button>
        <button type="button" class="btn btn-secondary" onclick="hideAddEditForm()">Hủy</button>
    </form>
</div>

<script>
function showAddFileForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('fileId').value = '';
    document.getElementById('name').value = '';
    document.getElementById('type').value = '';
    document.getElementById('file_path').value = '';
    document.getElementById('addEditForm').style.display = 'block';
}

function showEditFileForm(id, name, type, file_path) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('fileId').value = id;
    document.getElementById('name').value = name;
    document.getElementById('type').value = type;
    document.getElementById('file_path').value = file_path;
    document.getElementById('addEditForm').style.display = 'block';
}

function hideAddEditForm() {
    document.getElementById('addEditForm').style.display = 'none';
}
</script>

<?php if (isset($editPost)) : ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        showEditFileForm(
            "<?php echo $editPost['id']; ?>",
            "<?php echo htmlspecialchars($editPost['name']); ?>",
            "<?php echo htmlspecialchars($editPost['type']); ?>",
            "<?php echo htmlspecialchars($editPost['file_path']); ?>"
        );
    });
    </script>
<?php endif; ?>

<?php if (isset($message)) {
    echo $message;
} ?>
