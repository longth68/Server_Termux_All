<?php
include __DIR__ . '/../Controllers/Connections.php';

if (!isset($conn)) {
    die("Database connection not found.");
}

function firstBotCharacterFix($charJson) {
    $chars = json_decode($charJson ?? '[]', true);
    if (!is_array($chars) || empty($chars[0])) {
        return null;
    }
    return $chars[0];
}

try {
    $fixed = 0;
    $accounts = $conn->query("SELECT `char` FROM accounts WHERE note = 'BOT'")->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $conn->prepare("UPDATE players SET it_body = '[]' WHERE name = ? AND it_body = '[[],[],[],[],[],[],[],[]]'");
    foreach ($accounts as $account) {
        $charName = firstBotCharacterFix($account['char']);
        if (!$charName) {
            continue;
        }
        $stmt->execute([$charName]);
        $fixed += $stmt->rowCount();
    }
    echo "Updated " . $fixed . " bot characters in database.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
