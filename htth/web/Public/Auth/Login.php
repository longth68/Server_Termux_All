<?php
include __DIR__ . '/../../Controllers/Header.php';
$Css_Login = 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500';
?>

<div class="px-3">
    <div class="sc-bdvuGq BoTfp">
        <div class="mx-auto max-w-80 min-h-[434px]">
            <h1 class="text-lg font-bold text-gray-900 text-center mt-4 mb-3">Đăng nhập</h1>
            <div id="messageBox" class="hidden"></div>
            <form id="loginForm" class="space-y-6">
                <div>
                    <label for="username" class="block mb-2 text-sm font-medium text-gray-900 pl-2">Tài Khoản</label>
                    <input type="text" name="username" id="username" class="<?= $Css_Login ?>" minlength="2" placeholder="Nhập tài khoản" required>
                </div>
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white pl-2">Mật Khẩu</label>
                    <input type="password" name="password" id="password" minlength="2" class="<?= $Css_Login ?>" required>
                </div>
                <div class="flex justify-center flex-col items-center">
                    <button type="submit" class="w-4/6 px-2 py-3 text-base font-medium text-center text-white rounded-lg bg-green-600 hover:bg-green-800">Đăng Nhập</button>
                    <span class="py-3 text-red-600 text-sm text-center"></span>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    fetch('/Api/Login', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageBox = document.getElementById('messageBox');
        messageBox.textContent = data.message;
        if (data.status === 'success') {
            messageBox.classList.remove('error');
            messageBox.classList.add('success');
            window.location.href = '/';
        } else {
            messageBox.classList.remove('success');
            messageBox.classList.add('error');
        }
        messageBox.classList.remove('hidden');
    })
    .catch(error => {
        const messageBox = document.getElementById('messageBox');
        messageBox.textContent = 'Đã xảy ra lỗi khi gửi dữ liệu.';
        messageBox.classList.remove('success');
        messageBox.classList.add('error');
        messageBox.classList.remove('hidden');
    });
});
</script>

<?php
include __DIR__ . '/../../Controllers/Footer.php';
?>