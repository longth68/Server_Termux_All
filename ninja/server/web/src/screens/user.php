<?php
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

$user = $_SESSION['user'];

?>
<div class="mb-3">
    <div class="row text-center justify-content-center row-cols-3 row-cols-lg-6 g-1 g-lg-1">
        <div class="col">
            <a class="btn btn-sm py-1 btn-success w-100 fw-semibold active <?php echo $tab == "profile" ? "active" : "false"; ?>"
                href="/user/profile" style="background-color: rgb(255, 180, 115);">Tài khoản
            </a>
        </div>
        <div class="col">
            <a class="btn btn-sm py-1 btn-success w-100 fw-semibold active <?php echo $tab == "change-password" ? "active" : "false"; ?>"
                href="/user/change-password" style="background-color: rgb(255, 180, 115);">Đổi Mật Khẩu
            </a>
        </div>
    </div>
</div>
<hr>
<?php
switch ($tab) {
    case "profile":
        include_once('user/profile.php');
        break;
    case "squad":
        include_once('squad/views.php');
        break;
    case "change-password":
        include_once('user/change-password.php');
        break;
    case "change-gmail":
        include_once('user/change-gmail.php');
        break;
    case "change-password-two":
        include_once('user/change-password-two.php');
        break;
    case "add-point":
        include_once('user/add-point.php');
        break;
    default:
        include_once('user/profile.php'); 
        break;
}
?>