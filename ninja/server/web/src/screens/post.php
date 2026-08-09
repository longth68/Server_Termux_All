<?php
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$sql_content = __select("news_posts", [
    "slug" => $_GET["id"]
]);
$content = [];
if ($sql_content != false && $sql_content->num_rows > 0) {
    while ($row = $sql_content->fetch_assoc()) {
        $content = $row;
    }
}
try {
    __update("news_posts", ["views" => $content["views"] + 1], ["slug" => $_GET["id"]]);
} catch (Exception $e) {
}
?>

<div class="d-flex align-items-center home">
    <div class="post-image d-none d-sm-block">
        <img src="/images/avatar.png" alt="<?php echo $content["title"] ?>">
        <div class="post-author">Admin</div>
    </div>
    <div class="post-detail flex-fill">
        <div class="fw-bold text-primary"><?php echo $content["title"] ?></div>
        <div class="post-date"><?php echo $content["updated_at"] ?></div>
        <div class="post-content">
            <?php echo $content["content"] ?>
        </div>

        <?php
        $currentUrl = $_SERVER['REQUEST_URI'];
        $urlParts = explode('/', $currentUrl);
        $slug = end($urlParts);
        ?>

        <div class="post-info mt-2"><?php echo $content["views"] ?> lượt xem,
        </div>
        <br />
    </div>
</div>
<hr class="my-3 home">

<div class="<?php echo $isLogged ? 'flex-fill bangbinhluan' : 'd-none'; ?>">

    <form method="post" action="">
        <textarea class="form-control comment-input" name="comment_content" placeholder="Viết bình luận..."></textarea>
        <div class="d-flex justify-content-end">
            <button type="submit" name="comment_submit" class=" px-2 py-1 fw-semibold text-secondary bg-warning bg-opacity-25 border border-warning border-opacity-75 rounded-2 link-success cursor-pointer mt-2">
                Gửi bình luận
            </button>
        </div>
    </form>
</div>

<?php
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$isLogged = false;
$user = null;

if (isset($_SESSION['isLogged'])) {
    $isLogged = $_SESSION['isLogged'];
    if ($pageWasRefreshed && isset($_SESSION['user'])) {
        $sql = SQL()->query("select * from user where user = '" . $_SESSION['user']['user'] . "' limit 1");
        if ($sql != false && $sql->num_rows > 0) {
            while ($row = $sql->fetch_assoc()) {
                $_SESSION['user'] = $row;
            }
        }
    }
}

if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
}
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
if ($page == 'logout') {
    session_destroy();
    header("Location: /");
}
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
}
if (isset($_POST['comment_submit'])) {
    $commentContent = mysqli_real_escape_string($conn, $_POST['comment_content']);
    if (!empty($commentContent)) {
        $currentUrl = $_SERVER['REQUEST_URI'];
        $urlParts = explode('/', $currentUrl);
        $slug = end($urlParts);

        $insertQuery = "INSERT INTO binhluan (user_id, slug, name, content) VALUES ('" . $user['id'] . "', '$slug', '" . $user['username'] . "', '$commentContent')";
        if (mysqli_query($conn, $insertQuery)) {
            header("Location: {$_SERVER['REQUEST_URI']}");
            exit();
        } else {
            echo "Lỗi: " . $insertQuery . "<br>" . mysqli_error($conn);
        }
    }
}

?>


<style>
    .giuacan {
        display: flex;
        justify-content: center;
        position: relative;
    }

    .giuacan:hover {
        content: "";
        display: flex;
        justify-content: center;
        position: relative;
        font-size: 15px;
        transition: 0.5s;

    }

    .giuacan::after {
        content: "";
        display: flex;
        justify-content: center;
        width: 90px;
        height: 2px;
        background-color: black;
        position: absolute;
        bottom: -5px;
    }

    .mau {
        color: black;
    }

    .cach {
        margin-top: 15px;
    }
</style>