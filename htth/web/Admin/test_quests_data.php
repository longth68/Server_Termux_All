<?php
require_once __DIR__ . '/../Controllers/Connections.php';
$res = $conn->query('SHOW COLUMNS FROM quests');
$data = [];
while($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $data[] = $row['Field'];
}
echo json_encode($data);
?>
