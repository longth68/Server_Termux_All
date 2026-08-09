<?php

define('NP', true);
require(__DIR__ . '/../../core/configs.php');

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    echo json_encode([]);
    return;
}

$userId = (int) $_SESSION['user']['id'];

$conn = SQL();
$stmt = $conn->prepare("SELECT `net_amount`, `balance_before`, `balance_after`, `description`, `created_at` FROM `transactions` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 100");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = [
        $row['net_amount'],
        $row['balance_before'],
        $row['balance_after'],
        $row['description'],
        isset($row['created_at']) ? date('H:i:s d/m/Y', strtotime($row['created_at'])) : ''
    ];
}
$stmt->close();
$conn->close();

echo json_encode($rows);
