<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
        }
        .btn-info {
            background-color: #2196F3;
        }
        .btn-danger {
            background-color: #f44336;
        }
        .search-bar {
            margin-bottom: 20px;
        }
        .search-bar input {
            padding: 10px;
            font-size: 16px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <?php
    define('NP', true);
    require(__DIR__ . '/../../../../../core/configs.php');

    $conn = SQL(); 

    $tableName = isset($_GET['table']) ? $_GET['table'] : '';
    $recordId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($tableName && $recordId) {
        $tableName = $conn->real_escape_string($tableName);
        $sql = "SELECT * FROM `$tableName` WHERE `id` = $recordId";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $record = $result->fetch_assoc();
            echo "<h1>Edit Record</h1>";
            echo "<form method='post' action='update.php'>";
            echo "<input type='hidden' name='table' value='" . htmlspecialchars($tableName) . "'>";
            echo "<input type='hidden' name='id' value='" . htmlspecialchars($recordId) . "'>";
            foreach ($record as $key => $value) {
                if ($key != 'id') {
                    echo "<div class='form-group'>";
                    echo "<label for='$key'>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ":</label>";
                    echo "<input type='text' id='$key' name='$key' value='" . htmlspecialchars($value) . "'>";
                    echo "</div>";
                }
            }
            echo "<button type='submit' class='btn'>U</button>";
            echo "<a href='index.php?table=" . urlencode($tableName) . "' class='btn btn-danger'>C</a>";
            echo "</form>";
        } else {
        }
    } else {
    }

    $conn->close();
    ?>
</body>
</html>
