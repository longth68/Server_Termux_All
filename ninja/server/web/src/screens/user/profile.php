<?php
if (!isset($_SESSION['user'])) {
   header('Location: /login');
   exit;
}
$user_id = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

if (!$user_id) {
   die('User ID is not defined.');
}

$conn = SQL();

$query = "SELECT luong, balance, tongnap FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userDetails = $result->fetch_assoc();
$stmt->close();

$query = "SELECT * FROM players WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$ninja = $result->fetch_assoc();
$stmt->close();
?>

<div class="mt-2">
   <div class="table-responsive mb-4" style="  border-radius: 1rem;">
      <table class="table text-white fw-semibold mb-0" role="table">
         <thead>
            <tr class="text-start fw-bold text-uppercase gs-0">
               <th colspan="1" role="columnheader" class="" style="cursor: pointer;">Menu</th>
               <th colspan="1" role="columnheader" class="" style="cursor: pointer;">Thông Tin</th>

            </tr>
         </thead>
         <tbody class="fw-semibold" role="rowgroup">
            <tr role="row">
               <td role="cell" class="">
                  <div class="cursor-pointer">
                     <span class="ms-2 fw-semibold">Tài khoản</span>
                  </div>
               </td>
               <td role="cell" class="">
                  <div><?php echo isset($user['username']) ? $user['username'] : 'Trống'; ?></div>
               </td>
            </tr>
            <tr role="row">
               <td role="cell" class="">
                  <div class="cursor-pointer">
                     <span class="ms-2 fw-semibold">Xu</span>
                  </div>
               </td>
            <td role="cell" class="">
               <div>
                  <?php
                  if (isset($ninja['xu']) && $ninja['xu'] !== null && $ninja['xu'] !== '') {
                        echo number_format($ninja['xu']);
                  } else {
                        echo 'Trống';
                  }
                  ?>
               </div>
            </td>
            </tr>
            <tr role="row">
               <td role="cell" class="">
                  <div class="cursor-pointer">
                     <span class="ms-2 fw-semibold">Lượng</span>
                  </div>
               </td>
               <td role="cell" class="">
               <div>
                  <?php
                  if (isset($userDetails['luong']) && $userDetails['luong'] !== null && $userDetails['luong'] !== '') {
                        echo number_format($userDetails['luong']);
                  } else {
                        echo 'Trống';
                  }
                  ?>
               </div>
            </td>
            </tr>
            <tr role="row">
               <td role="cell" class="">
                  <div class="cursor-pointer">
                     <span class="ms-2 fw-semibold">Tổng Nạp</span>
                  </div>
               </td>
               <td role="cell" class="">
                  <div>
                     <?php
                     if (isset($userDetails['tongnap']) && $userDetails['tongnap'] !== null && $userDetails['tongnap'] !== '') {
                           echo number_format($userDetails['tongnap']);
                     } else {
                           echo 'Trống';
                     }
                     ?>
                  </div>
               </td>
            </tr>
            <td role="cell" class="">
               <div class="cursor-pointer">
                  <span class="ms-2 fw-semibold">Trạng Thái</span>
               </div>
            </td>
            <td role="cell" class="">
               <div>
                  <?php 
                     if (isset($user['kh'])) {
                        echo $user['kh'] == 1 ? 'Đã kích hoạt' : 'Chưa kích hoạt';
                     } else {
                        echo 'Trống';
                     }
                  ?>
               </div>
            </td>
         </tbody>
      </table>
   </div>
</div>

