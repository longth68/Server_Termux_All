<?php
include __DIR__ . '/../../Controllers/Connections.php';
include __DIR__ . '/../../Controllers/Configs.php';
include __DIR__ . '/../../Controllers/Sessions.php';

$limit = 10;
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($current_page - 1) * $limit;
$sql = "SELECT * FROM forum LIMIT $start, $limit";
$stmt = $conn->prepare($sql);
$stmt->execute();
$new_posts_html = '';
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $new_posts_html .= '<div class="font-normal text-[#D8BA89] outline outline-1 mt-[1px] py-1 px-3 leading-4 hover:underline hover:cursor-pointer hover:bg-[#e0ceb1]">';
    $new_posts_html .= '<a href="Post?id=' . $row["id"] . '" class="block">';
    $new_posts_html .= '<p class="text-sm text-blue-600">' . $row["title"] . '</p>';
    $new_posts_html .= '<div class="text-xs flex gap-2">';
    $new_posts_html .= '<span class="text-[#1aa837]">Tác giả:</span>';
    $new_posts_html .= '<span class="text-[#C522AC]">' . $row["name"] . '</span>';
    $new_posts_html .= '<span class="text-red-600">☆ ♥</span>';
    $new_posts_html .= '</div>';
    $new_posts_html .= '</a>';
    $new_posts_html .= '</div>';
}

echo $new_posts_html;