<?php
include __DIR__ . '/Controllers/Connections.php';

try {
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Create table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS `server_config` (
        `id` int NOT NULL DEFAULT 1,
        `exp_rate` int NOT NULL DEFAULT 1,
        `beri_rate` int NOT NULL DEFAULT 1,
        `drop_rate` int NOT NULL DEFAULT 1,
        `monster_drops` text NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $conn->exec($sql);

    // Insert default row if not exists
    $stmt = $conn->query("SELECT COUNT(*) FROM server_config WHERE id = 1");
    if ($stmt->fetchColumn() == 0) {
        $conn->exec("INSERT INTO server_config (id, exp_rate, beri_rate, drop_rate, monster_drops) VALUES (1, 1, 1, 1, '[]')");
    }
    echo "Success";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
