<?php
include_once 'head.php';

// Hàm để lấy tên người chơi từ danh sách tài khoản
function getPlayerNames($conn, $topAccounts) {
    $playerNames = [];
    if (!empty($topAccounts)) {
        $ids = implode(',', array_column($topAccounts, 'id'));
        $sqlNames = "SELECT p.account_id, p.name FROM player p WHERE p.account_id IN ($ids)";
        $playerResult = $conn->query($sqlNames);

        if ($playerResult->num_rows > 0) {
            while ($row = $playerResult->fetch_assoc()) {
                $playerNames[$row['account_id']] = $row['name'];
            }
        }
    }
    return $playerNames;
}

// Truy vấn dữ liệu xếp hạng
$sqlPower = "SELECT name, power, pet_power, (power + pet_power) AS total_power 
             FROM player 
             ORDER BY total_power DESC 
             LIMIT 10";
$resultPower = $conn->query($sqlPower);


// Truy vấn để lấy 10 tài khoản hàng đầu dựa trên cột tongnap
$sqlMoney = "SELECT id, tongnap FROM account ORDER BY tongnap DESC LIMIT 10";
$resultMoney = $conn->query($sqlMoney);
$topAccountsMoney = [];
if ($resultMoney->num_rows > 0) {
    while ($row = $resultMoney->fetch_assoc()) {
        $topAccountsMoney[] = $row;
    }
}
$playerNamesMoney = getPlayerNames($conn, $topAccountsMoney);



// Truy vấn dữ liệu nhiệm vụ
$sqlTask = "SELECT name, data_task, CAST(JSON_UNQUOTE(JSON_EXTRACT(data_task, '$[1]')) AS UNSIGNED) AS middle_value 
            FROM player 
            ORDER BY middle_value DESC 
            LIMIT 10";
$resultTask = $conn->query($sqlTask);

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Xếp Hạng</title>
    <main>

        <center>
            <p class="lead text-danger blinking-text"><?php echo $sv_name ?></p>
        </center>

        <div class="tab">
            <!-- Tab content for Power -->

            <button class="tablinks" onclick="openTab(event, 'power')" id="default">Top Sức Mạnh</button>
            <button class="tablinks" onclick="openTab(event, 'money')">Top Nạp</button>
            <button class="tablinks" onclick="openTab(event, 'task')">Top Nhiệm Vụ</button>
            

        </div>

        <!-- Tab content for Power -->
        <div id="power" class="tabcontent">
            <div class="scrollable-table">
                <table>
                    <tr>
                        <th>Hạng</th>
                        <th>Tên Nhân Vật</th>
                        <th>Sức Mạnh</th>
                        <th>Sức Mạnh Đệ Tử</th>
                        <th>Tổng Sức Mạnh</th>
                    </tr>
                    <?php if ($resultPower->num_rows > 0) {
                            $rank = 1;
                            while ($row = $resultPower->fetch_assoc()) { ?>
                    <tr>
                        <td class="text-dark"><?php echo $rank; ?></td>
                        <td class="text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="text-dark"><?php echo htmlspecialchars($row['power']); ?></td>
                        <td class="text-dark"><?php echo htmlspecialchars($row['pet_power']); ?></td>
                        <td class="text-dark"><?php echo htmlspecialchars($row['total_power']); ?></td>
                    </tr>
                    <?php $rank++; }
                        } else {
                            echo "<tr><td colspan='5'>Không có dữ liệu.</td></tr>";
                        } ?>
                </table>
            </div>
        </div>

        <!-- Tab content for Money -->
        <div id="money" class="tabcontent">
            <div class="scrollable-table">
                <table>
                    <thead>
                        <tr>
                            <th>Hạng</th>
                            <th>Tên Nhân Vật</th>
                            <th>Tổng Nạp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultMoney->num_rows > 0) {
                                $rank = 1;
                                foreach ($topAccountsMoney as $account) {
                                    $amount = $account['tongnap'];
                                    $name = isset($playerNamesMoney[$account['id']]) ? $playerNamesMoney[$account['id']] : 'N/A'; ?>
                        <tr>
                            <td class="text-dark"><?php echo $rank; ?></td>
                            <td class="text-dark"><?php echo $name; ?></td>
                            <td class="text-dark"><?php echo number_format($amount); ?></td>
                        </tr>
                        <?php $rank++;
                                }
                            } else {
                                echo "<tr><td colspan='3'>Không có dữ liệu.</td></tr>";
                            } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab content for Tasks -->
        <div id="task" class="tabcontent">
            <div class="scrollable-table">
                <table>
                    <tr>
                        <th>Hạng</th>
                        <th>Tên Nhân Vật</th>
                        <th>Nhiệm Vụ</th>
                    </tr>
                    <?php if ($resultTask->num_rows > 0) {
                            $rank = 1;
                            while ($row = $resultTask->fetch_assoc()) { ?>
                    <tr>
                        <td class="text-dark"><?php echo $rank; ?></td>
                        <td class="text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="text-dark"><?php echo htmlspecialchars($row['middle_value']); ?></td>
                    </tr>
                    <?php $rank++; }
                        } else {
                            echo "<tr><td colspan='3'>Không có dữ liệu.</td></tr>";
                        } ?>
                </table>
            </div>
        </div>

       

        <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
        document.getElementById("default").click();
        </script>
        </div>
    </main>
    </div>

    </body>

</html>