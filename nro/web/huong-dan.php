<?php
include_once 'head.php';
?>

<head>
    <title>Hướng dẫn - <?php echo $sv_code ?></title>
    <style>
        #scroll-to-top {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 99;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
        }
    </style>
</head>


<main>

    <div class="top text-center">
        <h1 class="fw-bold">Hướng dẫn</h1>
        <p class="lead text-danger blinking-text"><?php echo $sv_name ?></p>
    </div>
    <div class="nav">
        <ul class="toc-list mx-5 my-3">
            <li>
                <a class="text-danger" href="#tainguyen">
                    <h5>1. Tài Nguyên</h5>
                </a>
            </li>
            <li>
                <a class="text-danger" href="#tnsm">
                    <h5>2. Sức Mạnh / Tiềm Năng</h5>
                </a>
            </li>
            <li>
                <a class="text-danger" href="#detu">
                    <h5>3. Hệ Thống Đệ Tử</h5>
                </a>
            </li>
            <li>
                <a class="text-danger" href="#trangbi">
                    <h5>4. Hệ Thống Trang Bị</h5>
                </a>
            </li>

            <li>
                <a class="text-danger" href="#kichhoat">
                    <h5>6. Kích Hoạt Thành Viên</h5>
                </a>
            </li>
        </ul>
    </div>
    <p class="text-center">----------------- ooo -----------------</p>
    <div class="text-justify mx-5">
        <div id="tainguyen">
            <h4 class="my-4 text-danger">Tài Nguyên</h4>

            <p>Base miễn phí: Ngọc xanh, Mã Code.</p>

            <p>Nên tiền tệ chính và duy nhất trong Base là <b>THỎI VÀNG (Không Khóa)</b>.</p>

            <p><b>Cách kiếm Thỏi Vàng cơ bản:</b></p>
            <ul>
                <li>Đánh Ngọc Rộng Đen 7 Sao.</li>
                <li>Nạp và quy đổi Thỏi Vàng ở NPC ở Nhà.</li>
                <li>Trao đổi, buôn bán.</li>
                <li>Còn rất nhiều sự kiện, đua top cho anh em kiếm Thỏi Vàng nữa.</li>
            </ul>

        </div>

        <div id="tnsm">
            <h4 class="my-4 text-danger">Sức Mạnh / Tiềm Năng</h4>
            <p><b>BASE - Qua Được Map Nào Cao Nhất Thì Up Map Đó, Cụ Thể Thì: Nappa--> Tương Lai---> Coler.</b>
            </p>
        </div>

        <div id="detu">
            <h4 class="my-4 text-danger">Hệ Thống Đệ Tử</h4>
            <p>Ngoài đệ truyền thống, máy chủ còn có các loại đệ tử mới, đa dạng tùy theo nhu cầu:</p>

            <ul>
                <li>
                    <b>Đệ tử Mabu:</b> Sở hữu được khi đánh bại Boss 22h tại thành phố
                    Vegita và gặp NPC Tapion.
                </li>
                <li>
                    <b>Đệ tử Vip (Bill Nhí, Bill, Goku Vô Cực):</b> Sở hữu được
                    khi có đủ 100k tiền và gặp NPC Chi Chi để mua.
                </li>
            </ul>

            <p><b>Lưu ý:</b></p>
            <ul>
                <li>Phải có đệ tử thường để có thể mở trứng Bư và mua đệ tử VIP.</li>
            </ul>

        </div>

        <div id="trangbi">
            <h4 class="my-4 text-danger">Hệ Thống Trang Bị</h4>
            <p>Ngoài các trang bị mặc định, máy chủ còn đem lại cho cư dân các trang bị <b>Kích Hoạt</b>, <b>Thiên
                    Sứ</b></p>

            <ul>
                <li>
                    <b>Trang bị Set Kích Hoạt:</b> Kiếm từ việc đập từ đồ Thần Linh lên đồ Hủy Diệt và sau đó là lên
                    Set Kích Hoạt.
                </li>
                <li>
                    <b>Trang bị Thiên Sứ:</b> Yêu cầu cư dân chăm chỉ cày cuốc, săn boss để nhận được các mảnh Thiên
                    Sứ và cần đủ x999 mảnh.
                </li>
                <li>
                    <b>Chế tạo tại:</b> NPC Bà Hạt Mít ở map Đảo Kame.
                </li>
                <li>
                    <b>Săn Boss:</b> Black, Super Black, Xên ở map Võ Đài, Cumber, Super Cumber, Cooler, Cooler 2,
                    Chill, Chill 2.
                </li>
                <li>
                    <b>Up quái:</b> Ở các map Coler sẽ có tỉ lệ rớt ra Đồ Thần Linh.
                </li>
            </ul>

            <p>Bên cạnh đó còn vô số các vật phẩm với hiệu ứng siêu đẹp, cùng chỉ số siêu VIP đang chờ cư dân khám
                phá.</p>


        </div>

        <div id="kichhoat">
            <h4 class="my-4 text-danger">Kích Hoạt Thành Viên</h4>
            <p>Để trải nghiệm trọn vẹn cũng như ủng hộ máy chủ phát triển, cư dân có thể cân nhắc <b>Kích hoạt thành
                    viên</b>.</p>

            <p><b>Cách kích hoạt thành viên:</b></p>
            <ul>
                <li>Cư dân có thể kích hoạt thành viên miễn phí khi nạp mệnh giá bất kỳ tại Trang chủ hoặc thông qua
                    ADMIN.</li>
            </ul>

            <p><b>Lưu ý:</b></p>
            <ul>
                <li>Nạp thẻ không chiết khấu tại Trang chủ (thời gian xử lý 1-5 phút).</li>
                <li>Liên hệ ADMIN qua box chat Zalo để nạp qua Banking/ATM và nhận thêm 10% giá trị nạp.</li>
            </ul>

        </div>
    </div>
    <button onclick="scrollToTop()" id="scroll-to-top"><i class="fa-solid fa-arrow-up"></i></button>

    <script>
        // JavaScript code
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        window.onscroll = function () {
            scrollFunction()
        };

        function scrollFunction() {
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                document.getElementById("scroll-to-top").style.display = "block";
            } else {
                document.getElementById("scroll-to-top").style.display = "none";
            }
        }
    </script>
    </div>
</main>
</div>