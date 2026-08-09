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

    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $userId = intval($_GET['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $updateFields = [];
            foreach ($_POST as $key => $value) {
                if ($key !== 'submit') {
                    $updateFields[] = "`$key` = '" . $conn->real_escape_string($value) . "'";
                }
            }
            $updateSql = "UPDATE `players` SET " . implode(', ', $updateFields) . " WHERE `user_id` = $userId";

            if ($conn->query($updateSql)) {
            } else {
            }
        }

        $sql = "SELECT * FROM `players` WHERE `user_id` = $userId";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo "<form method='post' action=''>";
            foreach ($user as $key => $value) {
                if ($key !== 'id') {
                    echo "<div class='form-group'>";
                    echo "<label for='$key'>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ":</label>";
                    $type = ($key === 'email') ? 'email' : 'text';
                    echo "<input type='$type' id='$key' name='$key' value='" . htmlspecialchars($value) . "' required>";
                    echo "</div>";
                }
            }
            echo "<button type='submit' class='btn'>s</button>";
            echo "<a href='?action=view' class='btn btn-danger'>c</a>";
            echo "</form>";
        } else {
        }
    } else {
        echo "<form method='post' action=''>";
        echo "<div class='search-bar'>";
        echo "<input type='text' name='search' value='" . (isset($_POST['search']) ? htmlspecialchars($_POST['search']) : '') . "' placeholder='sS'>";
        echo "<button type='submit' class='btn'>sS</button>";
        echo "</div>";
        echo "</form>";

        $searchTerm = isset($_POST['search']) ? $_POST['search'] : '';
        $sql = "SELECT * FROM `players`";
        if (!empty($searchTerm)) {
            $searchTerm = $conn->real_escape_string($searchTerm);
            $sql .= " WHERE `name` LIKE '%$searchTerm%'";
        }

        $result = $conn->query($sql);

        if ($result) {
            if ($result->num_rows > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Name</th><th>Actions</th></tr>";

                $result->data_seek(0); 

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    foreach (['id', 'name'] as $key) {
                        echo "<td>" . htmlspecialchars($row[$key]) . "</td>";
                    }
                    echo "<td>";
                    echo "<a href='?action=edit&id=" . urlencode($row['id']) . "' class='btn btn-info'>E</a>";
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
            }
        } else {
        }
    }

    $conn->close();
    ?>
</body>
