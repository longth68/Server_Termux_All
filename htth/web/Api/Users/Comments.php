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
} else {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $comments = $_POST['content'];
        $postId = $_POST['postId'];
        $createdAt = date("Y-m-d H:i:s");
        $content = filterBadWords($comments, $badWords);

        // Tạo một mảng chứa thông tin bình luận
        $sql = "SELECT comments FROM forum WHERE id = :postId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':postId', $postId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $comments = array();
        if ($row && !empty($row['comments'])) {
            $comments = json_decode($row['comments'], true);
        }

        $newComment = array(
            'stt' => count($comments) + 1,
            'date' => $createdAt,
            'name' => $_User,
            'comment' => $content
        );

        $comments[] = $newComment;
        $updatedComments = json_encode($comments);

        $sql = "UPDATE forum SET comments = :comments WHERE id = :postId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':comments', $updatedComments);
        $stmt->bindParam(':postId', $postId);
        $stmt->execute();

        echo json_encode(array("message" => "success", "successMessage" => "Bình luận đã được lưu thành công."));
    } else {
        echo json_encode(array("message" => "error", "errorMessage" => "Phương thức yêu cầu không hợp lệ."));
    }
}