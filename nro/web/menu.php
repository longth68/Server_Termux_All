 <a href="profile"
     style="display: block; padding: 10px 15px; background-color: #ff7700; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 10px;">
     <i class="fas fa-user me-2"></i> Thông tin tài khoản
 </a>

 <a href="profile#tab-nap"
     style="display: block; padding: 10px 15px; background-color: #ff7700; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 10px;">
     <i class="fas fa-coins me-2"></i> Nạp tiền
 </a>

 <a href="lich-su-nap"
     style="display: block; padding: 10px 15px; background-color: #ff7700; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 10px;">
     <i class="fas fa-credit-card me-2"></i> Lịch sử nạp
 </a>

 <a href="thay-mat-khau"
     style="display: block; padding: 10px 15px; background-color: #ff7700; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 10px;">
     <i class="fas fa-sign-out-alt me-2"></i> Thay mật khẩu
 </a>

 <?php if (isset($user_arr['is_admin']) && $user_arr['is_admin'] == 1) { ?>
 <a href="/admin.php"
     style="display: block; padding: 10px 15px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 10px;">
     <i class="fas fa-shield-halved me-2"></i> Quản Trị (Admin)
 </a>
 <?php } ?>

 <a href="/?out"
     style="display: block; padding: 10px 15px; background-color: #ff7700; color: white; text-decoration: none; border-radius: 5px;">
     <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
 </a>