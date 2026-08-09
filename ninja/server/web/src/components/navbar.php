<?php
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

if (isset($_SESSION['user'])) {
   $user = $_SESSION['user'];
} else {
   $user = []; 
}

$Zalo_Link = $_ENV['LINK_ZALO_1'];

?>
<div class="mb-2">
   <div class="row text-center justify-content-center row-cols-2 row-cols-lg-6 g-2 g-lg-2 mt-1">
      <div class="col">
         <div class="px-2"><a class="btn btn-menu btn-success w-100 fw-semibold <?php echo ($page == "home") ? "active" : "false"; ?>" href="/home">Trang chủ</a></div>
      </div>
      <div class="col">
         <div class="px-2"><a class="btn btn-menu btn-success w-100 fw-semibold <?php echo ($page == "recharge") ? "active" : "false"; ?>" href="javascript:void(0)" onclick="onClickNav('/recharge'); return;">Nạp tiền</a></div>
      </div>
      <div class="col">
         <div class="px-2"><a class="btn btn-menu btn-success w-100 fw-semibold <?php echo ($page == "exchange") ? "active" : "false"; ?>" href="javascript:void(0)" onclick="onClickNav('/exchange'); return;">Đổi lượng</a></div>
      </div>
      <div class="col">
         <div class="px-2"><a class="btn btn-menu btn-success w-100 fw-semibold false" href="/community">Zalo & Page FB</a></div>
      </div>
	  <div class="px-2">
            <a href="/giftcode" class="btn btn-menu btn-success w-100 fw-semibold">Mã Quà Tặng</a>
         </div>
       <div class="col">
         <div class="px-2">
            <a href="/ranking" class="btn btn-menu btn-success w-100 fw-semibold">Ranking</a>
         </div>
				 
      </div> 
      <?php if (isset($user['admin_web']) && $user['admin_web'] == 1): ?>
         <div class="col">
            <div class="px-2"><a class="btn btn-menu btn-success w-100 fw-semibold false" href="/admin/home">Admin</a></div>
         </div>
      <?php endif; ?>
   </div>
</div>
<script>
   function onClickNav(goto) {
      let isLogged = <?php echo isset($isLogged) && $isLogged ? 'true' : 'false'; ?>;
      if (!isLogged) {
         $("#modalLogin").modal("show");
      } else {
         window.location.href = goto;
      }
   }
</script>