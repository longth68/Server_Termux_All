<?php
define('NP', true);
require(__DIR__ . '/../../../../../core/configs.php');
$conn = SQL(); 
$tableName = isset($_POST['table']) ? $conn->real_escape_string($_POST['table']) : '';
$recordId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($tableName && $recordId) {
    $updates = [];
    foreach ($_POST as $key => $value) {
        if ($key != 'table' && $key != 'id') {
            $value = $conn->real_escape_string($value);
            $updates[] = "`$key` = '$value'";
        }
    }

    if (!empty($updates)) {
        $updateString = implode(', ', $updates);
        $sql = "UPDATE `$tableName` SET $updateString WHERE `id` = $recordId";
        if ($conn->query($sql)) {
            header("Location: Erro.php?table=" . urlencode($tableName));
        } else {
        }
    } else {
    }
} else {
}

$conn->close();
?>
