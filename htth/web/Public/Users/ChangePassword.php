<?php
include __DIR__ . '/../../Controllers/Header.php';
?>
<div class="px-3">
    <div class="sc-bdvuGq BoTfp">
        <div class="mx-auto max-w-[90%] sm:max-w-[65%] py-6">
            <h1 class="text-lg font-bold text-gray-900 text-center mt-4 mb-3">Đổi Mật Khẩu</h1>
            <div id="messageBox"></div>
            <form method="post" class="space-y-6" onsubmit="event.preventDefault(); ChangePass();">
                <div>
                    <label for="current_password"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white pl-2">Mật
                        Khẩu
                        Cũ</label>
                    <input type="password" name="current_password" id="current_password"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        required="">
                </div>
                <div>
                    <label for="newpassword"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white pl-2">Mật
                        Khẩu mới</label>
                    <input type="password" name="newpassword" id="newpassword"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        required="">
                </div>
                <div>
                    <label for="newpassword_confirm"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white pl-2">Nhập Lại Mật Khẩu
                        mới</label>
                    <input type="password" name="newpassword_confirm" id="newpassword_confirm"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        required="">
                </div>
                <div class="flex justify-center flex-col items-center">
                    <button type="submit"
                        class="w-4/6 px-2 py-3 text-base font-medium text-center text-white rounded-lg bg-green-600 hover:bg-green-800">Đổi
                        Mật Khẩu</button>
                    <span id="passwordMessage" class="py-3 text-red-600 text-sm text-center"></span>
                </div>
            </form>
            <script>
                function ChangePass() {
                    var current_password = document.querySelector("[name=current_password]").value;
                    var newpassword = document.querySelector("[name=newpassword]").value;
                    var newpassword_confirm = document.querySelector("[name=newpassword_confirm]").value;
                    var username = '<?= $_User ?>';

                    var isValid = passwordCheck(current_password, newpassword, newpassword_confirm);
                    if (isValid) {
                        fetch('/Api/Password', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                current_password: current_password, // Thêm trường này để gửi mật khẩu hiện tại
                                newpassword: newpassword,
                                newpassword_confirm: newpassword_confirm,
                                username: username,
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                const messageBox = document.getElementById("messageBox");
                                if (data.message === "success") {
                                    messageBox.textContent = data.successMessage;
                                    messageBox.classList.remove("error");
                                    messageBox.classList.add("success");
                                    messageBox.classList.remove("hidden");
                                } else {
                                    messageBox.textContent = data.errorMessage;
                                    messageBox.classList.remove("success");
                                    messageBox.classList.add("error");
                                    messageBox.classList.remove("hidden");
                                }
                            })
                            .catch(error => {
                                const messageBox = document.getElementById("messageBox");
                                console.error('Error:', error);
                                messageBox.textContent = "Có lỗi xảy ra khi kích hoạt tài khoản.";
                                messageBox.classList.remove("success");
                                messageBox.classList.add("error");
                                messageBox.classList.remove("hidden");
                            });
                    }
                }

                function passwordCheck(current_password, newpassword, newpassword_confirm) {
                    var hasSpecialChar = /[ `!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~]/;
                    var hasUpperCase = /[A-Z]/;
                    const messageBox = document.getElementById("messageBox");

                    if (hasSpecialChar.test(newpassword) || hasUpperCase.test(newpassword)) {
                        messageBox.textContent = "Mật khẩu không được chứa ký tự đặc biệt hoặc chữ hoa";
                        messageBox.classList.remove("success");
                        messageBox.classList.add("error");
                        messageBox.classList.remove("hidden");
                        return false;
                    } else if (newpassword !== newpassword_confirm) {
                        messageBox.textContent = "Mật khẩu mới không khớp";
                        messageBox.classList.remove("success");
                        messageBox.classList.add("error");
                        messageBox.classList.remove("hidden");
                        return false;
                    }
                    return true;
                }
            </script>
        </div>
    </div>
</div>
<?php
include __DIR__ . '/../../Controllers/Footer.php';
?>