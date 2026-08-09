<?php
include __DIR__ . '/../../Controllers/Connections.php';
include __DIR__ . '/../../Controllers/Configs.php';
include __DIR__ . '/../../Controllers/Sessions.php';

$badWords = array('dmm', 'cặc', 'địt', 'lồn', 'sex', 'địt', 'súc vật', 'sv', 'fuck', 'loz', 'lozz', 'lozzz', 'óc chó', 'ngu lồn', 'nguu lồn', 'nguu lồn', 'ngulon', 'nguu lonn', 'ngu lon', 'occho', 'ditmemay', 'dmm', 'dcm', 'địt cụ mày', 'địt con mẹ mày', 'fuck you', 'chịch', 'chịt', 'sẽ gầy');

function filterBadWords($content, $badWords)
{
    foreach ($badWords as $word) {
        $content = str_ireplace($word, "****", $content);
    }
    return $content;
}

function validateInput($data)
{
    $data = trim($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_Login == null) {
    echo json_encode(array("message" => "error", "errorMessage" => "Bạn chưa đăng nhập!"));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = validateInput($_POST['title']);
    $content = validateInput($_POST['content']);
    $comments = json_encode([]);

    if (!empty($title) && !empty($content) && !empty($_User)) {
        $filteredContent = filterBadWords($content, $badWords);
        $stmt = $conn->prepare("INSERT INTO forum (title, content, name, comments) VALUES (:title, :content, :name, :comments)");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $filteredContent);
        $stmt->bindParam(':name', $_User);
        $stmt->bindParam(':comments', $comments);
        $stmt->execute();
        echo json_encode(array("message" => "success", "successMessage" => "Bài viết đã được gửi thành công!"));
    } else {
        echo json_encode(array("message" => "error", "errorMessage" => "Dữ liệu không hợp lệ"));
    }
} else {
    echo json_encode(array("message" => "error", "errorMessage" => "Phương thức yêu cầu không hợp lệ"));
}
