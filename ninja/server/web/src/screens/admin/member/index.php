<?php
require_once(__DIR__ . '/../../../../core/configs.php');

if (!isset($_SESSION['user'])) {
    header('Location: /home');
    exit;
}

$user = $_SESSION['user'];
if ($user['admin_web'] != 1) {
    header("Location: /home");
    exit();
}
$conn = SQL();
$search = isset($_POST['search']) ? $_POST['search'] : (isset($_GET['search']) ? $_GET['search'] : '');

?>

<div class="card">
    <div class="card-body">

        <?php
        $user_id = null;

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["search"])) {
            $search_username = $_POST["search_username"];

            $search_query = "SELECT id FROM users WHERE username = '$search_username'";
            $search_result = mysqli_query($conn, $search_query);

            if ($search_result && mysqli_num_rows($search_result) > 0) {
                $row = mysqli_fetch_assoc($search_result);
                $user_id = $row["id"];

                $info_query = "SELECT p.name, p.yen, p.xu, u.luong, u.balance FROM players p JOIN users u ON p.user_id = u.id WHERE u.username = '$search_username'";
                $info_result = mysqli_query($conn, $info_query);

                if ($info_result) {
                  $info_row = mysqli_fetch_assoc($info_result);
              
                  if ($info_row) {
                      echo "<table>";
                      echo "<tr><td>Tên nhân vật:</td><td>" . $info_row["name"] . "</td></tr>";
                      echo "<tr><td>Số dư Yên:</td><td>" . number_format($info_row["yen"]) . " Yên</td></tr>";
                      echo "<tr><td>Số dư Xu:</td><td>" . number_format($info_row["xu"]) . " Xu</td></tr>";
                      echo "<tr><td>Số dư Lượng:</td><td>" . number_format($info_row["luong"]) . " Lượng</td></tr>";
                      echo "<tr><td>Số dư Coin:</td><td>" . number_format($info_row["balance"]) . " Coin</td></tr>";
                      echo "</table>";
                      echo '
                      <form method="post" action="">
                          <label for="amount">Số tiền:</label>
                          <input type="number" name="amount" required>
                          <br>
                          <label for="currency">Loại tiền:</label>
                          <select name="currency">
                              <option value="luong">Lượng</option>
                              <option value="balance">Coin</option>
                              <option value="yen">Yên</option>
                              <option value="xu">Xu</option>
                          </select>
                          <br>
                          <input type="hidden" name="user_id" value="' . $user_id . '">
                          <input type="submit" name="submit" value="Cộng Tiền">
                      </form>';                
                    }else {
                      echo '<div style="text-align: center; color: red;">⚠️Tài khoản chưa tạo nhân vật</div>';
                      echo '
                      <form method="post" action="">
                          <label for="amount">Số tiền:</label>
                          <input type="number" name="amount" required>
                          <br>
                          <label for="currency">Loại tiền:</label>
                          <select name="currency">
                              <option value="luong">Lượng</option>
                              <option value="balance">Coin</option>
                          </select>
                          <br>
                          <input type="hidden" name="user_id" value="' . $user_id . '">
                          <input type="submit" name="submit" value="Cộng Tiền">
                      </form>';
                  } ?>
                    <?php
                } else {
                    echo "Không thể lấy thông tin người chơi! Lỗi: " . mysqli_error($conn);
                }
            } else {
              $_SESSION['error'] = "Không tìm thấy người chơi";
              header("Location: " . $_SERVER['REQUEST_URI']);
              exit(0);
            }
        }

      if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
          if (($_SESSION['user'])) {

            $user_id = $_POST["user_id"];

            $amount = $_POST["amount"];
            $currency = $_POST["currency"];

            $update_query = "";
            if ($currency == 'luong' || $currency == 'balance') {
                $update_query = "UPDATE users SET $currency = $currency + $amount WHERE id = $user_id";
            } else {
                $update_query = "UPDATE players SET $currency = $currency + $amount WHERE user_id = $user_id";
            }

            $update_result = mysqli_query($conn, $update_query);

            if ($update_result) {
              $_SESSION['success'] = "Cộng tiền thành công cho thằng " . $_SESSION['username'] . ".";
              header("Location: " . $_SERVER['REQUEST_URI']);
              exit(0);
            } else {
                echo "Không thể cộng tiền! Lỗi: " . mysqli_error($conn);
            }
        } else {
          $_SESSION['error'] = "Bạn làm đéo có quyền :))";
          header("Location: " . $_SERVER['REQUEST_URI']);
          exit(0);
        }
      }

        mysqli_close($conn);
        ?>
<form method="post" action="">
        <label for="search_username">Nhập tên người chơi:</label>
        <input type="text" name="search_username" required>
        <input type="submit" name="search" value="Tìm Kiếm">
    </form>
    </div>
</div>
<style>

form {
  text-align: center;
  margin-bottom: 20px;
}

table {
  margin-top: 20px;
  border-collapse: collapse;
  width: 100%;
}

th, td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: left;
}

th {
  background-color: #f2f2f2;
}

.input-group {
  margin-bottom: 10px;
}

.input-group label {
  display: block;
  margin-bottom: 5px;
}

.input-group input {
  margin-right: 5px;
  height: calc(1.5em + 15px);
}

.btn-group button {
  margin-right: 5px;
}

.alert {
  padding: 10px;
  margin-bottom: 20px;
  border: 1px solid transparent;
  border-radius: 4px;
}

.alert-success {
  background-color: #d4edda;
  border-color: #c3e6cb;
  color: #155724;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c6cb;
  color: #721c24;
}

.alert-warning {
  background-color: #fff3cd;
  border-color: #ffeeba;
  color: #856404;
}

@media only screen and (max-width: 600px) {
      .search-container input[type="text"] {
          width: 100%; /* Thiết lập chiều rộng là 100% trên màn hình có chiều rộng tối đa 600px */
      }
  }

  .card1 {
      width: 100%;
      overflow: hidden;
  }

  .card-body1 {
      overflow-x: auto;
  }
</style>
