<?php
require_once(__DIR__ . '/../../../../core/configs.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['admin_web'] != 1) {
    header("Location: /home");
    exit();
}

function generateSlug($title)
{
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

$message = '';
$conn = SQL();
$editPost = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($action == 'edit') {
        $stmt = $conn->prepare("SELECT id, title, content FROM news_posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $editPost = $result->fetch_assoc();
        } else {
            $message = '<div class="alert alert-danger" role="alert">Bài viết không tồn tại.</div>';
        }
    } elseif ($action == 'delete') {
        $stmt = $conn->prepare("DELETE FROM news_posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success" role="alert">Bài viết đã được xóa thành công!</div>';
            header('Location: /admin/articles');
            exit();
        } else {
            $message = '<div class="alert alert-danger" role="alert">Có lỗi xảy ra khi xóa bài viết.</div>';
        }
    } elseif ($action == 'update') {
        if (isset($_POST['title'], $_POST['content'])) {
            $id = intval($_POST['id']);
            $title = $conn->real_escape_string($_POST['title']);
            $content = $conn->real_escape_string($_POST['content']);
            $slug = generateSlug($title);
            $stmt = $conn->prepare("UPDATE news_posts SET title = ?, content = ?, slug = ? WHERE id = ?");
            $stmt->bind_param("sssi", $title, $content, $slug, $id);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success" role="alert">Bài viết đã được cập nhật thành công!</div>';
                header('Location: articles');
                exit();
            } else {
                $message = '<div class="alert alert-danger" role="alert">Có lỗi xảy ra khi cập nhật bài viết.</div>';
            }
        }
    } elseif ($action == 'add') {
        if (isset($_POST['title'], $_POST['content'])) {
            $title = $conn->real_escape_string($_POST['title']);
            $content = $conn->real_escape_string($_POST['content']);
            $slug = generateSlug($title);
            $stmt = $conn->prepare("INSERT INTO news_posts (title, content, views, status, slug) VALUES (?, ?, 0, 0, ?)");
            $stmt->bind_param("sss", $title, $content, $slug);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success" role="alert">Bài viết đã được thêm thành công!</div>';
                header('Location: articles');
                exit();
            } else {
                $message = '<div class="alert alert-danger" role="alert">Có lỗi xảy ra khi thêm bài viết.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger" role="alert">Dữ liệu form không đầy đủ!</div>';
        }
    }
}

$sql = "SELECT id, title, content, views, status FROM news_posts";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.tiny.cloud/1/emjf0md4v5hb2rrh2g3loun3x42exzifdgqks9ftq4b7ow1j/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        function initTinyMCE() {
            tinymce.init({
                selector: '#article-content, #edit-article-content',
                plugins: [
                'advlist', 'autolink', 'link', 'image', 'lists', 'charmap', 'preview', 'anchor', 'pagebreak',
                'searchreplace', 'wordcount', 'visualblocks', 'visualchars', 'code', 'fullscreen', 'insertdatetime',
                'media', 'table', 'emoticons', 'help'
                ],
                toolbar: 'undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | ' +
                    'bullist numlist outdent indent | link image | print preview media fullscreen | ' +
                    'forecolor backcolor emoticons | help',
                menu: {
                    file: { title: 'File', items: 'newdocument restoredraft | preview | importword exportpdf exportword | print | deleteallconversations' },
                    edit: { title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall | searchreplace' },
                    view: { title: 'View', items: 'code revisionhistory | visualaid visualchars visualblocks | spellchecker | preview fullscreen | showcomments' },
                    insert: { title: 'Insert', items: 'image link media addcomment pageembed codesample inserttable | math | charmap emoticons hr | pagebreak nonbreaking anchor tableofcontents | insertdatetime' },
                    format: { title: 'Format', items: 'bold italic underline strikethrough superscript subscript codeformat | styles blocks fontfamily fontsize align lineheight | forecolor backcolor | language | removeformat' },
                    tools: { title: 'Tools', items: 'spellchecker spellcheckerlanguage | a11ycheck code wordcount' },
                    table: { title: 'Table', items: 'inserttable | cell row column | advtablesort | tableprops deletetable' },
                    help: { title: 'Help', items: 'help' }
                },
                menubar: 'favs file edit view insert format tools table help',
                height: 500
            });
        }

        function showAddArticleForm() {
            var form = document.getElementById('add-article-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            if (form.style.display === 'block') {
                initTinyMCE();
            }
        }

        function showTextareaContent() {
            var content = tinymce.get('article-content').getContent();
            document.getElementById('textarea-content').innerHTML = `<pre>Received Content: ${content}</pre>`;
        }

        function editArticle(id, title, content) {
            document.getElementById('edit-article-form').style.display = 'block';
            document.getElementById('edit-article-id').value = id;
            document.getElementById('edit-article-title').value = title;
            tinymce.get('edit-article-content').setContent(content);
        }

        document.addEventListener('DOMContentLoaded', function() {
            initTinyMCE();
        });

    </script>
    <style>
        #edit-article-form {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        #add-article-form {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        #add-article-form textarea {
            display: block;
            
        }

        #articles {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        button {
            background-color: #007BFF;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s, transform 0.2s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        button:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        button:active {
            background-color: #004494;
            transform: scale(0.98);
        }

        #add-article-form {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 1rem; padding:10px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        form label {
            margin-bottom: 8px;
            font-weight: bold;
            color: #ffffff;
        }

        form input,
        form textarea {
            width: calc(100% - 24px);
            padding: 9px;
            margin-bottom: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            background-color: #ffffff;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        form input:focus,
        form textarea:focus {
            border-color: #007BFF;
            outline: none;
            box-shadow: 0 0 4px rgba(0, 123, 255, 0.5);
        }

        #add-article-form {
            display: none;
        }
    </style>
</head>

<body>
    <div id="articles" class="section" style="border-radius: 1rem; padding:10px">
        <div style="text-align:center;">
            <h4><?php echo htmlspecialchars($user['username']); ?> tạo bài viết</h4>
        </div>
        <div class="container mb-2">
            <div class="row text-center justify-content-center g-2 mt-1">
                <div class="col-12 col-md-4 col-lg-3">
                    <a class="btn btn-success w-100 fw-semibold" href="/admin/home">Quay lại</a>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <a class="btn btn-success w-100 fw-semibold" onclick="showAddArticleForm()">Thêm Bài Viết</a>
                </div>
            </div>
        </div>
        <?php if ($message) echo $message; ?>

        <div id="add-article-form">
            <h3 style="text-align: center;">Thêm Bài Viết</h3>
            <form id="add-article-form-element" method="post" onsubmit="showTextareaContent()">
                <label for="article-title">Tiêu Đề:</label>
                <input type="text" id="article-title" name="title" required>
                <label for="article-content">Nội Dung:</label>
                <textarea id="article-content" name="content"></textarea>
                <input type="hidden" name="action" value="add">
                <button class="btn-success" style="margin-top: 20px !important; margin: auto;" type="submit">Lưu</button>
            </form>
        </div>
        <div id="edit-article-form" style="display: <?php echo isset($editPost) ? 'block' : 'none'; ?>;">
            <h3 style="text-align: center;">Sửa Bài Viết</h3>
            <form id="edit-article-form-element" method="post">
                <input type="hidden" id="edit-article-id" name="id" value="<?php echo isset($editPost['id']) ? htmlspecialchars($editPost['id']) : ''; ?>">
                <label for="edit-article-title">Tiêu Đề:</label>
                <input type="text" id="edit-article-title" name="title" value="<?php echo isset($editPost['title']) ? htmlspecialchars($editPost['title']) : ''; ?>" required>
                <label for="edit-article-content">Nội Dung:</label>
                <textarea id="edit-article-content" name="content"><?php echo isset($editPost['content']) ? htmlspecialchars($editPost['content']) : ''; ?></textarea>
                <input type="hidden" name="action" value="update">
                <button class="btn-success" style="margin-top: 20px !important; margin: auto;" type="submit">Cập Nhật</button>
            </form>
        </div>
        <h3 style="text-align: center; margin: 20px;">Danh Sách Bài Viết</h3>
        <table class="table text-white fw-semibold mb-0" role="table">
            <thead>
                <tr class="text-start fw-bold text-uppercase gs-0">
                    <th>ID</th>
                    <th>Tiêu Đề</th>
                    <th>Views</th>
                    <th>Trạng Thái</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['title']}</td>
                                <td>{$row['views']}</td>
                                <td>{$row['status']}</td>
                                <td>
                                    <form method=\"post\" style=\"display:inline;\">
                                        <input type=\"hidden\" name=\"action\" value=\"edit\">
                                        <input type=\"hidden\" name=\"id\" value={$row['id']}>
                                        <button type=\"submit\" class=\"btn btn-danger btn-sm\">Chỉnh Sửa</button>
                                    </form> - 

                                    <form method=\"post\" style=\"display:inline;\">
                                        <input type=\"hidden\" name=\"action\" value=\"delete\">
                                        <input type=\"hidden\" name=\"id\" value={$row['id']}>
                                        <button type=\"submit\" class=\"btn btn-primary\">Xóa Bỏ</button>
                                    </form>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Không có bài viết nào.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>