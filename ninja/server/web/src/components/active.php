<div class="modal fade" id="modalActive" tabindex="-1" aria-labelledby="modalActiveLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="my-2">
                    <div class="text-center">
                        <a href="/"><img class="logo" alt="Logo" src="/images/SCHOOLZ.png" style="max-width: 200px;"></a>
                    </div>
                </div>
                <div class="text-center fw-semibold">
                    <div class="fs-6 mb-2" style="color:black !important">Xác nhận kích hoạt tài khoản</div>
                    <div id="noti-active"></div>
                    <span style="color:black !important">Vui lòng thoát game trước khi xác nhận kích hoạt</span>
                    <span style="color:black !important">Sau khi kích hoạt, bạn sẽ mở khóa các tính năng giao dịch</span>
                    <div class="text-success fw-bold" style="color:black !important">Phí kích hoạt: <?php echo $fees["active"]; ?> Coin</div>
                    <div class="mt-2">
                        <button type="button" id="active" class="btn-rounded btn btn-primary btn-sm" style="color:black !important">Kích hoạt ngay</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    document.addEventListener("DOMContentLoaded", function() {
        const btnActive = document.querySelector("#active");
        const notiActive = document.querySelector("#noti-active");
        
        btnActive.addEventListener("click", () => {
            notiActive.innerHTML = '';
            btnActive.disabled = true;
            btnActive.textContent = "Đang kích hoạt...";

            const loadingWrap = document.querySelector("#NotiflixLoadingWrap");
            if (loadingWrap) loadingWrap.classList.remove('hide');

            fetch('/apixuli/active', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (loadingWrap) loadingWrap.classList.add('hide');

                let alertMsg;
                if (data.code === "00") {
                    alertMsg = `<div class="alert alert-success" id="error">${data.text}</div>`;
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    alertMsg = `<div class="alert alert-danger" id="error">${data.text}</div>`;
                    btnActive.disabled = false;  // Re-enable button on error
                    btnActive.textContent = "Kích hoạt ngay";
                }
                notiActive.insertAdjacentHTML('afterbegin', alertMsg);
            })
            .catch(error => {
                if (loadingWrap) loadingWrap.classList.add('hide');
                const alertMsg = `<div class="alert alert-danger" id="error">Có lỗi xảy ra trong quá trình kết nối. Vui lòng thử lại sau. (Error: ${error.message})</div>`;
                notiActive.insertAdjacentHTML('afterbegin', alertMsg);

                btnActive.disabled = false;
                btnActive.textContent = "Kích hoạt ngay";
            });
        });
    });
})();
</script>
