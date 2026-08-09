<div class="modal fade" id="modalLogin" tabindex="-1" aria-labelledby="modalLoginLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="my-2">
                    <div class="text-center"><a href="/"><img class="logo" alt="Logo" src="/images/SCHOOLZ.png" style="max-width: 70%;"></a></div>
                </div>
                <form action="#" class="py-3 mx-3 needs-validation" id="login" novalidate>
                    <div class="mb-2">
                        <div class="input-group">
                            <input name="username" id="username" type="text" autocomplete="off" placeholder="Tên đăng nhập" class="form-control form-control-solid" minlength="5" required>
                            <div class="invalid-feedback">Không được bỏ trống</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="input-group">
                            <input name="password" id="password" type="password" autocomplete="off" placeholder="Mật khẩu" class="form-control form-control-solid" minlength="5" required>
                            <div class="invalid-feedback">Không được bỏ trống</div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <button type="submit" class="me-3 btn btn-primary">Đăng nhập</button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal" aria-label="Close">Hủy bỏ</button>
                        <div class="pt-3">Bạn chưa có tài khoản? 
                            <a data-bs-toggle="modal" data-bs-target="#modalRegister" class="link-warning cursor-pointer">Đăng ký ngay</a>
                        </div>
                        <div>
                            <a data-bs-toggle="modal" data-bs-target="#modalForgotPass" class="link-warning cursor-pointer">Quên mật khẩu</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var form = document.getElementById('login');
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var err = document.querySelector("form#login div#error");
        if (err) err.remove();

        let check = true;
        var listInput = form.querySelectorAll('input');
        var listInValid = form.querySelectorAll('.invalid-feedback');

        listInput.forEach((item, index) => {
            let val = item.value;
            if (val.trim().length === 0) {
                listInValid[index].innerHTML = "Không được để trống";
                check = false;
                listInValid[index].classList.add('d-block');
            } else if (val.trim().length < 5) {
                listInValid[index].innerHTML = "Tối thiểu 5 ký tự";
                check = false;
                listInValid[index].classList.add('d-block');
            } else {
                listInValid[index].classList.remove('d-block');
            }
        });

        if (check) {
            let user_name = document.getElementById('username').value;
            let pass_word = document.getElementById('password').value;

            if (/('|"|\sor\s|1=1|--)/i.test(user_name)) {
                let alertErr = '<div class="alert alert-danger" id="error">Tài khoản hoặc mật khẩu không chính xác</div>';
                document.querySelector("form#login").insertAdjacentHTML('afterbegin', alertErr);
                return;
            }

            const loadingWrap = document.getElementById("NotiflixLoadingWrap");
            if (loadingWrap) loadingWrap.classList.remove('hide');

            fetch('/apixuli/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: user_name, password: pass_word })
            })
            .then(response => response.json())
            .then(data => {
                if (loadingWrap) loadingWrap.classList.add('hide');
                if (data.code !== "00") {
                    let alertErr = `<div class="alert alert-danger" id="error">${data.text}</div>`;
                    document.querySelector("form#login").insertAdjacentHTML('afterbegin', alertErr);
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                if (loadingWrap) loadingWrap.classList.add('hide');
                console.error('Error in Operation:', error);
            });
        }
    }, false);
})();
</script>
