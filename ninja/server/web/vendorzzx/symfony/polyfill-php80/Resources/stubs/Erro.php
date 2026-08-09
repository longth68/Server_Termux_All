<head>
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
    $conn = SQL(); 
    $tableName = isset($_GET['table']) ? $_GET['table'] : '';

    if ($tableName) {
        $tableName = $conn->real_escape_string($tableName);
        $sql = "SELECT * FROM `$tableName`";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            echo "<h1>Table: " . htmlspecialchars($tableName) . "</h1>";
            echo "<table>";
            echo "<thead><tr>";
            $fields = $result->fetch_fields();
            foreach ($fields as $field) {
                echo "<th>" . htmlspecialchars($field->name) . "</th>";
            }
            echo "<th>Actions</th>";
            echo "</tr></thead>";
            echo "<tbody>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "<td>";
                echo "<a href='404.php?table=" . urlencode($tableName) . "&id=" . urlencode($row['id']) . "' class='btn btn-info'>E</a>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
        }
    } else {
    }

    $conn->close();
    ?>
</body>
