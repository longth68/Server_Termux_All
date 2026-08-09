<?php include __DIR__ . '/../../Controllers/Header.php'; ?>
<div class="px-3">
    <div class="sc-bdvuGq BoTfp">
        <div class="flex flex-col sm:flex-row min-h-[340px] pb-6">
            <div class="flex-1 px-5">
                <h1 id="cardPaymentTitle" class="text-lg font-bold text-gray-900 text-center mt-4 mb-3">Nạp Tiền</h1>

                <div id="messageBox" class="mb-4 hidden p-3 rounded"></div>
                
                <form onsubmit="event.preventDefault(); PostCard();" id="cardPaymentForm" class="space-y-6">
                    <div>
                        <label for="amount" class="block mb-2 text-sm font-medium text-gray-900 pl-2">Chọn Số Tiền Nạp (VNĐ)</label>
                        <select name="amount" id="amount"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            required="">
                            <option value="">Chọn số tiền</option>
                            <option value="10000">10.000</option>
                            <option value="20000">20.000</option>
                            <option value="30000">30.000</option>
                            <option value="50000">50.000</option>
                            <option value="100000">100.000</option>
                            <option value="200000">200.000</option>
                            <option value="300000">300.000</option>
                            <option value="500000">500.000</option>
                            <option value="1000000">1.000.000</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-center flex-col items-center">
                        <button id="paymentSubmitBtn" type="submit"
                            class="w-4/6 px-2 py-3 text-base font-medium text-center text-white rounded-lg bg-green-600 hover:bg-green-800">Tạo Yêu Cầu Nạp</button>
                    </div>
                </form>
            </div>
            <div class="flex-1 px-5">
                <h1 class="text-lg font-bold text-gray-900 text-center mt-4 mb-3">Bảng Giá Quy Đổi Ruby</h1>
                <div class="pb-6 sm:pb-6 sm:py-3">
                    <table id="priceTable" class="w-full text-sm text-left rtl:text-right">
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include __DIR__ . '/../../Controllers/Footer.php';
?>
<script>
    function PostCard() {
        var amount = document.querySelector("[name=amount]").value;
        var username = '<?= $_User ?>';

        fetch('/Api/Card', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                telco: 'MANUAL', // Manual top-up
                amount: amount,
                serial: '',
                code: '',
                username: username
            })
        })
            .then(response => response.json())
            .then(data => {
                const messageBox = document.getElementById("messageBox");
                if (data.success) {
                    messageBox.innerHTML = `<div class="bg-green-100 text-green-700 p-2 rounded">${data.message}</div>`;
                    messageBox.classList.remove("hidden");
                    document.getElementById("cardPaymentForm").reset();
                } else {
                    messageBox.innerHTML = `<div class="bg-red-100 text-red-700 p-2 rounded">${data.message}</div>`;
                    messageBox.classList.remove("hidden");
                }
            })
            .catch(error => {
                const messageBox = document.getElementById("messageBox");
                console.error('Error:', error);
                messageBox.innerHTML = `<div class="bg-red-100 text-red-700 p-2 rounded">Có lỗi xảy ra khi tạo yêu cầu.</div>`;
                messageBox.classList.remove("hidden");
            });
    }

    // Hiển thị bảng giá quy đổi ruby
    const priceData = [
        { price: "10.000đ", ruby: "500 Ruby" },
        { price: "20.000đ", ruby: "1.200 Ruby" },
        { price: "30.000đ", ruby: "2.100 Ruby" },
        { price: "50.000đ", ruby: "4.000 Ruby" },
        { price: "100.000đ", ruby: "9.000 Ruby" },
        { price: "200.000đ", ruby: "20.000 Ruby" },
        { price: "300.000đ", ruby: "34.500 Ruby" },
        { price: "500.000đ", ruby: "65.000 Ruby" },
        { price: "1.000.000đ", ruby: "150.000 Ruby" }
    ];

    const tableBody = document.querySelector("#priceTable tbody");

    priceData.forEach(item => {
        const row = document.createElement("tr");
        row.classList.add('border-solid', 'border', 'border-transparent', 'border-b-orange-300');

        const priceCell = document.createElement("td");
        priceCell.classList.add('px-6', 'py-4', 'text-center', 'sm:text-left');
        priceCell.textContent = item.price;

        const rubyCell = document.createElement("td");
        rubyCell.classList.add('px-6', 'py-4', 'text-center', 'sm:text-left');
        rubyCell.textContent = item.ruby;

        row.appendChild(priceCell);
        row.appendChild(rubyCell);

        tableBody.appendChild(row);
    });
</script>