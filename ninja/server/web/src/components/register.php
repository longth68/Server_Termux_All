<div class="modal fade" id="modalRegister" tabindex="-1" aria-labelledby="modalRegisterLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="my-2">
                    <div class="text-center"><a href="/"><img class="logo" alt="Logo" src="/images/SCHOOLZ.png" style="max-width: 70%;"></a></div>
                </div>
                <form action="#" class="py-3 mx-3 needs-validation" id="register">
                    <div class="mb-2">
                        <label class="fw-semibold" style="color:white">Tên đăng nhập</label>
                        <div class="input-group">
                            <input name="rusername" id="rusername" type="text" autocomplete="off" placeholder="Nhập tên đăng nhập" class="form-control form-control-solid" value="">
                        </div>
                        <div class="invalid-feedback">Không được bỏ trống</div>
                    </div>
                    <div class="mb-2">
                        <label class="fw-semibold" style="color:white">Mật khẩu</label>
                        <div class="input-group"><input name="rpassword" id="rpassword" type="password" autocomplete="off" placeholder="Nhập mật khẩu" class="form-control form-control-solid" value=""></div>
                        <div class="invalid-feedback">Không được bỏ trống</div>
                    </div>
                    <div class="mb-2">
                        <label class="fw-semibold" style="color:white">Nhập lại mật khẩu</label>
                        <div class="input-group"><input name="confirm_password" id="confirm_password" type="password" autocomplete="off" placeholder="Nhập lại mật khẩu" class="form-control form-control-solid" value=""></div>
                        <div class="invalid-feedback">Không được bỏ trống</div>
                    </div>
                    <div class="text-center mt-3">
                        <button type="submit" class="me-3 btn btn-primary">Đăng ký</button><button type="button" class="btn btn-danger" data-bs-dismiss="modal" aria-label="Close">Hủy bỏ</button>
                        <div class="pt-3" style="color:white !important">Bạn đã có tài khoản? <a data-bs-toggle="modal" data-bs-target="#modalLogin" class="link-warning cursor-pointer" >Đăng nhập ngay</a></div>
                        <div><a data-bs-toggle="modal" data-bs-target="#modalForgotPass" class="link-warning cursor-pointer" >Quên mật khẩu</a></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation#register');
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        var err = $("form#register div#error").first();
                        if (err) err.remove();

                        var listInput = form.querySelectorAll('input');
                        var listInValid = form.querySelectorAll('.invalid-feedback');

                        event.preventDefault();
                        event.stopPropagation();
                        let check = true;

                        listInput.forEach((item, index) => {
                            let val = item.value.trim();
                            if (val.length == 0) {
                                listInValid[index].innerHTML = "Không được để trống.";
                                check = false;
                                listInValid[index].classList.add('d-block');
                            } else if (val.length < 5) {
                                listInValid[index].innerHTML = "Tối thiểu 5 ký tự.";
                                check = false;
                                listInValid[index].classList.add('d-block');
                            } else if (index == 4 && (val != listInput[index - 1].value.trim())) {
                                listInValid[index].innerHTML = "Mật khẩu nhập lại không chính xác.";
                                check = false;
                                listInValid[index].classList.add('d-block');
                            } else if (index == 0 && /[A-Z]/.test(val)) {
                                listInValid[index].innerHTML = "Tên đăng nhập không được chứa ký tự viết hoa.";
                                check = false;
                                listInValid[index].classList.add('d-block');
                            } else {
                                listInValid[index].classList.remove('d-block');
                            }
                        });

                        if (check) {
                            let user_name = $('input#rusername').val();
                            let pass_word = $('input#rpassword').val();
                            $("#NotiflixLoadingWrap").removeClass('hide');
                            $.ajax({
                                url: '/apixuli/register',
                                type: 'POST',
                                dataType: 'json',
                                data: JSON.stringify({
                                    "username": user_name,
                                    "password": pass_word,
                                }),
                                success: function(data, textStatus, xhr) {
                                    $("#NotiflixLoadingWrap").addClass('hide');
                                    if (data.code == "02") {
                                        let alertErr = '<div class="alert alert-danger" id="error">Tên đăng nhập đã tồn tại trên hệ thống.</div>';
                                        $("form#register").prepend(alertErr);
                                    } else if (data.code != "00") {
                                        let alertErr = '<div class="alert alert-danger" id="error">Sai định dạng, vui lòng thử lại.</div>';
                                        $("form#register").prepend(alertErr);
                                    } else {
                                        localStorage.setItem('username', user_name);
                                        localStorage.setItem('password', pass_word);

                                        let alertErr = '<div class="alert alert-success" id="error">Tạo tài khoản thành công.</div>';
                                        $("form#register").prepend(alertErr);

                                        setTimeout(() => {
                                            autoLogin(user_name, pass_word);
                                        }, 1000);

                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 3000);
                                    }
                                },
                                error: function(xhr, textStatus, errorThrown) {
                                    $("#NotiflixLoadingWrap").addClass('hide');
                                    console.log('Error in Operation');
                                }
                            });
                        }
                    }, false);
                });

            function autoLogin(tk, mk) {
                fetch('/apixuli/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            username: tk,
                            password: mk
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.querySelector("#NotiflixLoadingWrap").classList.add('hide');
                        if (data.code !== "00") {
                            let alertErr = `<div class="alert alert-danger" id="error">${data.text}</div>`;
                            document.querySelector("form#login").insertAdjacentHTML('afterbegin', alertErr);
                        } else {
                            window.location.reload();
                            window.location.href = '/home';
                            window.location.reload();
                        }
                    })
				
            }
        })();
    </script>