<?php
include __DIR__ . '/../../Controllers/Header.php';
?>
<div class="px-3">
    <div class="sc-bdvuGq BoTfp">
        <div class="mx-auto max-w-[90%] sm:max-w-[65%] py-6">
            <div id="messageBox"></div>
            <table class="w-full text-sm text-left rtl:text-righ">
                <tbody>
                    <tr class="border-solid border border-transparent border-b-orange-300">
                        <th scope="row" class="px-6 py-4 font-medium">Tài Khoản</th>
                        <td class="px-6 py-4">
                            <div class="flex flex-row">
                                <span><?= $_User ?></span>
                                <a href="/Users/ChangePassword"
                                    class="ml-7 -mt-1 py-1 px-1 sm:py-2 sm:-mt-2 sm:ml-16 text-white bg-orange-400 hover:bg-orange-600 hover:underline rounded-lg">Đổi
                                    Mật Khẩu</a>
                            </div>
                        </td>
                    </tr>
                    <tr class="border-solid border border-transparent border-b-orange-300">
                        <th scope="row" class="px-6 py-4 font-medium">Trạng Thái</th>
                        <td class="px-6 py-4">
                            <?= ($_Status == 1 ? 'Đã kích hoạt' : 'Chưa kích hoạt'); ?>
                            <?php if ($_Status != 1): ?>
                                <a onclick="activateAccount();"
                                    class="ml-7-mt-1 py-1 px-1 sm:py-2 sm:-mt-2 text-white bg-orange-400 hover:bg-orange-600 hover:underline rounded-lg">
                                    Kích hoạt
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <script>
                        function activateAccount() {
                            var status = '<?php echo $_Status; ?>';
                            var coins = '<?php echo $_Coins; ?>';
                            var username = '<?php echo $_User; ?>';

                            fetch('/Api/Active', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    status: status,
                                    coins: coins,
                                    username: username
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    const messageBox = document.getElementById("messageBox");
                                    if (data.success) {
                                        messageBox.textContent = data.message;
                                        messageBox.classList.remove("error");
                                        messageBox.classList.add("success");
                                        messageBox.classList.remove("hidden");
                                        // Load lại trang sau khi kích hoạt thành công
                                        if (data.successMessage === 'Kích hoạt tài khoản thành công.') {
                                            window.location.reload();
                                        }
                                    } else {
                                        messageBox.textContent = data.message;
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
                    </script>
                    <tr class="border-solid border border-transparent border-b-orange-300">
                        <th scope="row" class="px-6 py-4 font-medium">Số Dư</th>
                        <td class="px-6 py-4"><?= formatMoney($_Coins) ?></td>
                    </tr>
                    <tr class="border-solid border border-transparent border-b-orange-300">
                        <th scope="row" class="px-6 py-4 font-medium">Tổng Nạp</th>
                        <td class="px-6 py-4"><?= formatMoney($_TCoins) ?></td>
                    </tr>
                    <!-- <tr class="border-solid border border-transparent border-b-orange-300">
                        <th scope="row" class="px-6 py-4 font-medium">Nhân Vật</th>
                        <td class="px-6 py-4">
                            <?= ($_Char != '[]' ? $_Char : 'Chưa tạo nhân vật'); ?>
                        </td>
                    </tr> -->
                    <tr class="border-solid border border-transparent border-b-orange-300">
                        <th scope="row" class="px-6 py-4 font-medium">Email</th>
                        <td class="px-6 py-4"><?= $_Email ?></td>
                    </tr>
                </tbody>
            </table>
            <h1 class="w-full text-lg mt-8 ml-1 underline font-bold text-gray-900 text-center mb-2">Lịch Sử Nạp</h1>
            <?php
            if (isset($_User)) {
                $stmt = $conn->prepare("SELECT * FROM `napthe` WHERE user_nap = :username ORDER BY created_at DESC LIMIT 15");
                $stmt->bindParam(":username", $_User);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($result) > 0) {
                    echo '<table class="w-full text-sm text-left rtl:text-righ table-fixed">';
                    $addCount = count($result);
                    foreach ($result as $index => $row) {
                        $count = $addCount - $index;
                        $status = match ($row['status']) {
                            1 => 'Thẻ đúng',
                            2 => 'Thẻ sai',
                            3 => 'Thẻ lỗi',
                            default => 'Chờ Duyệt'
                        };

                        $formattedDate = date('H:i d/m/Y', strtotime($row['created_at']));

                        echo '<tr class="border-solid border border-transparent border-b-orange-300">
            <td class="px-6 py-4 font-medium break-words">
                <span class="text-red-600 font-semibold text-base">' . $count . '. </span> ' . $formattedDate . '
                <span class="block">Nạp thẻ cào: ' . formatMoney($row['amount']) . '</span>
            </td>
            <td class="px-6 py-4 break-words">' . $status . '</td>
            </tr>';
                    }
                    echo '</table>';
                }
            }
            ?>
        </div>
    </div>
</div>
<?php
include __DIR__ . '/../../Controllers/Footer.php';
?>