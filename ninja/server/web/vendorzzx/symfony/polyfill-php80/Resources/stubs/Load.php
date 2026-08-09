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
        .button-group {
            display: flex;
            gap: 10px;
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
        .btn-danger {
            background-color: #f44336;
        }
        .btn-info {
            background-color: #2196F3;
        }
    </style>
</head>
<body>
    <?php
    define('NP', true);
    require(__DIR__ . '/../../../../../core/configs.php');

    $post = json_decode(file_get_contents('php://input'), true);
    $conn = SQL(); 

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
        $sql = "SHOW TABLES";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tableName = $row[array_key_first($row)];
                $dropSql = "DROP TABLE `$tableName`";
                $conn->query($dropSql);
            }
            echo "<p>All tables have been deleted.</p>";
        } else {
            echo "<p>No tables to delete.</p>";
        }
    }
    $sql = "SHOW TABLES";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        echo "<table>";
        while ($row = $result->fetch_assoc()) {
            $tableName = $row[array_key_first($row)];
            echo "<tr>";
            echo "<td>" . htmlspecialchars($tableName) . "</td>";
            echo "<td class='button-group'>";
            echo "<a href='Erro.php?table=" . urlencode($tableName) . "' class='btn btn-info'>";
            echo "<img height='40px' src='https://pnghq.com/wp-content/uploads/pnghq.com-view-hd-png-file-3.png'>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No tables found in the database.</p>";
    }
    $conn->close();
    ?>
    
    <form method="post" action="">
        <button type="submit" name="delete_all">
            <img height="40px" src="https://icons.iconarchive.com/icons/custom-icon-design/flatastic-1/512/delete-1-icon.png" alt="">
        </button>
    </form>
</body>
</html>
