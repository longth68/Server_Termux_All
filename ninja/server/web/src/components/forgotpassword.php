<div z-index="1" class="modal fade" id="modalForgotPass" tabindex="-1" aria-labelledby="modalForgotPassLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-body">
            <div class="my-2">
               <div class="text-center"><a href="/home"><img class="logo" alt="Logo" src="/images/logo.png" style="max-width: 150px;"></a></div>
            </div>
            <form action="#" class="py-3 mx-3 needs-validation" id="forgotpass">
               <div class="mb-2">
                  <label class="fw-semibold" style="color:black !important">Tên đăng nhập</label>
                  <div class="input-group"><input name="fusername" id="fusername" type="text" autocomplete="off" placeholder="Nhập tên đăng nhập" class="form-control form-control-solid" value=""></div>
               </div>
               <div class="mb-2">
                  <label class="fw-semibold" style="color:black !important">Địa chỉ mail đăng ký</label>
                  <div class="input-group"><input name="fmail" id="fmail" type="email" autocomplete="off" placeholder="Nhập địa chỉ mail đăng ký" class="form-control form-control-solid" value=""></div>
               </div>
               <div class="text-center mt-3">
                  <button type="submit" class="me-3 btn btn-primary">Xác nhận</button>
                  <button type="button" class="btn btn-danger" data-bs-dismiss="modal" aria-label="Close">Hủy bỏ</button>
                  <div class="pt-3" style="color:black !important">Bạn đã có tài khoản? <a data-bs-toggle="modal" data-bs-target="#modalLogin" class="link-primary cursor-pointer">Đăng nhập ngay</a></div>
               </div>
            </form>
            <form action="#" class="py-3 mx-3 needs-validation" id="changePass">
               <div class="mb-2">
                  <label class="fw-semibold" style="color:black !important">Mật khẩu mới</label>
                  <div class="input-group"><input name="newPass" id="newPass" type="password" autocomplete="off" placeholder="Nhập mật khẩu mới" class="form-control form-control-solid" value=""></div>
               </div>
               <div class="mb-2">
                  <label class="fw-semibold" style="color:black !important">Nhập lại mật khẩu mới</label>
                  <div class="input-group"><input name="reNewPass" id="reNewPass" type="password" autocomplete="off" placeholder="Nhập lại mật khẩu mới" class="form-control form-control-solid" value=""></div>
               </div>
               <div class="text-center mt-3">
                  <button type="submit" class="me-3 btn btn-primary" style="color:#0d6efd !important;">Đổi mật khẩu</button>
                  <button type="button" class="btn btn-danger" data-bs-dismiss="modal" aria-label="Close">Hủy bỏ</button>
               </div>
            </form>
         </div>
      </div>
   </div>
</div>

<script type="text/javascript">
   window.onload = function() {
      $('form#changePass').css('display', 'none');
   };

   $('#modalForgotPass').on('hide.bs.modal', function() {
      $('#repassContent').children('div').remove();
   });

   $('#modalForgotPass').on('show.bs.modal', function() {
      $('#repassContent').children('div').remove();
      $('form#forgotpass').css('display', 'block');
      $('form#changePass').css('display', 'none');
      $("#fusername").val('');
      $("#fmail").val('');
   });

   function verifyUser() {
      let user_name = $("#fusername").val();
      let email = $("#fmail").val();

      $.ajax({
         url: '/apixuli/verifyUser',
         type: 'POST',
         contentType: 'application/json',
         dataType: 'json',
         data: JSON.stringify({
            "username": user_name,
            "fmail": email
         }),
         success: function(data) {
            let alertErr;
            if (data.code !== "00") {
               alertErr = `<div class="alert alert-danger" id="error">${data.message || "Tên đăng nhập hoặc email không đúng"}</div>`;
               $("form#forgotpass").prepend(alertErr);
            } else {
               $('form#forgotpass').css('display', 'none');
               $('form#changePass').css('display', 'block');
            }
         },
         error: function(xhr, status, error) {
            let alertErr = `<div class="alert alert-danger" id="error">Lỗi: ${xhr.status} - ${xhr.statusText}</div>`;
            $("form#forgotpass").prepend(alertErr);
         }
      });
   }


   function verifyChangePass() {
      let user_name = $("#fusername").val();
      let newPass = $('#newPass').val();
      let rePass = $('#reNewPass').val();
      let alertErr;

      if (newPass !== rePass) {
         alertErr = '<div class="alert alert-danger" id="error">Mật khẩu không trùng khớp</div>';
         $("form#changePass").prepend(alertErr);
         return;
      } else if (newPass.length < 6) {
         alertErr = '<div class="alert alert-danger" id="error">Mật khẩu quá ngắn. Tối thiểu 6 ký tự.</div>';
         $("form#changePass").prepend(alertErr);
         return;
      } else {
         $.ajax({
            url: '/apixuli/forgotpassword',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
               "reNewPass": rePass,
               "newPass": newPass,
               "username": user_name
            }),
            success: function(data) {
               if (data.code !== "00") {
                  alertErr = `<div class="alert alert-danger" id="error">${data.message || "Có lỗi trong quá trình reset mật khẩu"}</div>`;
                  $("form#changePass").prepend(alertErr);
               } else {
                  alertErr = `<div class="alert alert-success" id="error">${data.message || "Thay đổi mật khẩu thành công. Bạn sẽ được chuyển hướng sau 3s."}</div>`;
                  $("form#changePass").prepend(alertErr);
                  setTimeout(() => {
                     window.location.reload();
                  }, 3000);
               }
            },
            error: function(xhr, status, error) {
               let alertErr = `<div class="alert alert-danger" id="error">Lỗi: ${xhr.status} - ${xhr.statusText}</div>`;
               $("form#changePass").prepend(alertErr);
            }
         });
      }
   }



   (function() {
      'use strict';
      var forms = document.querySelectorAll('.needs-validation#forgotpass');
      Array.prototype.slice.call(forms).forEach(function(form) {
         form.addEventListener('submit', function(event) {
            var err = $("form#forgotpass div#error").first();
            if (err) err.remove();
            event.preventDefault();
            event.stopPropagation();
            verifyUser();
         }, false);
      });
   })();

   (function() {
      'use strict';
      var forms = document.querySelectorAll('.needs-validation#changePass');
      Array.prototype.slice.call(forms).forEach(function(form) {
         form.addEventListener('submit', function(event) {
            var err = $("form#changePass div#error").first();
            if (err) err.remove();
            event.preventDefault();
            event.stopPropagation();
            verifyChangePass();
         }, false);
      });
   })();
</script>

</script>