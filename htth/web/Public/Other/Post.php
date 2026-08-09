<?php
include __DIR__ . '/../../Controllers/Header.php';

// Kiểm tra xem ID đã được truyền hay chưa
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $Id_Post = $_GET['id'];

    // Truy vấn cơ sở dữ liệu để lấy thông tin của bài viết từ bảng 'forum'
    $sql = "SELECT * FROM forum WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $Id_Post);
    $stmt->execute();

    // Kiểm tra xem có bài viết nào được tìm thấy không
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $_TitlePost = $row['title'];
        $_ContentPost = $row['content'];
        $_name = $row['name'];
        $_DatePost = $row['date'];
        if (isset($row['comments']) && !empty($row['comments'])) {
            $_Comments = json_decode($row['comments'], true);
        }
    } else {
        echo "Không tìm thấy bài viết.";
        exit;
    }
} else {
    header("Location: /Users/Forum");
    exit;
}
?>

<div class="px-3">
    <div class="sc-bdvuGq BoTfp">
        <div class="px-4">
            <div class="text-white font-normal text-center mt-5 py-2 bg-[#d8ba89] rounded-md">Hải Tặc Tí Hon | Poseidon
            </div>
            <hr class="my-3">
            <div class="sm:px-5">
                <a href="/Users/Forum">
                    <button type="button"
                        class="py-1.5 px-5 mb-2 text-sm font-medium text-gray-900 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700">Trở
                        về diễn đàn</button>
                </a>
                <span class="ml-2 font-semibold text-lg">/ <?= $_TitlePost ?></span>
                <div class="mt-2">
                    <div class="px-3 sm:px-4">
                        <div class="flex flex-row">
                            <div class="flex flex-col mr-5 my-1">
                                <div class="relative w-10 h-10 overflow-hidden bg-gray-100 rounded-full">
                                    <svg class="absolute w-12 h-12 text-gray-400 -left-1" fill="currentColor"
                                        viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd"
                                            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd">
                                        </path>
                                    </svg>
                                </div>
                                <span class="ml-1 mt-2 text-[10px] text-red-500">☆♥</span>
                                <span class="ml-1 mt-0 text-[10px] text-[#EF9012]">#0</span>
                            </div>
                            <div class="ml-2 flex flex-col">
                                <span class="text-[#EF9012]"><?= $_name ?></span>
                                <span class="text-sm px-1.5 block text-justify"><?= $_ContentPost ?></span>
                                <span class="mt-6 text-xs text-[#676767]"><?= $_DatePost ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="mb-2">
                <div id="messageBox" class="hidden"></div>
                <form method="post" id="submitForm" class="mt-3 flex flex-row justify-between px-4">
                    <input name="content" id="content"
                        class="bg-gray-50 border border-gray-300 text-sm rounded-lg py-1.5 px-1.5 h-12 w-5/6"
                        required="" placeholder="Nhập bình luận"></input>
                    <div class="flex flex-col">
                        <button type="submit"
                            class="py-2 px-2 text-xs ml-4 sm:ml-0 sm:text-sm font-medium text-center text-white rounded-lg w-[77px] bg-green-600 hover:bg-green-800">Bình
                            luận</button>
                        <span class="py-3 text-red-600 text-sm text-center"></span>
                    </div>
                    <input type="hidden" id="postId" name="postId" value="<?= $Id_Post ?>">
                </form>
            </div>
            <div class="sm:px-5"></div>
            <?php
            if (isset($row['comments']) && !empty($row['comments'])) {
                $count = 1;
                foreach ($_Comments as $Cmt): ?>
                    <div class="sm:px-5 pb-6">
                        <div class="infinite-scroll-component__outerdiv">
                            <div class="infinite-scroll-component " style="height: auto; overflow: auto;">
                                <div class="px-4">
                                    <div class="flex flex-row">
                                        <div class="flex flex-col mr-5 my-1">
                                            <div class="relative w-10 h-10 overflow-hidden bg-gray-100 rounded-full">
                                                <svg class="absolute w-12 h-12 text-gray-400 -left-1" fill="currentColor"
                                                    viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd"
                                                        d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                            <span class="ml-1 mt-2 text-[10px] text-red-500">☆♥</span>
                                            <span class="ml-1 mt-0 text-[10px] text-[#EF9012]">#<?= $count ?></span>
                                        </div>
                                        <div class="ml-2 flex flex-col items-start">
                                            <span class="text-[#EF9012]"><?= $Cmt['name'] ?></span>
                                            <span class="text-sm px-1.5">
                                                <span class="block text-justify"><?= $Cmt['comment'] ?><br></span>
                                                <span class="block text-justify"></span>
                                            </span>
                                            <span class="mt-6 text-xs text-[#676767]"><?= $Cmt['date'] ?></span>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $count++; ?>
                <?php
                endforeach;
            }
            ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const submitForm = document.getElementById("submitForm");

        submitForm.addEventListener("submit", function (event) {
            event.preventDefault(); // Ngăn chặn gửi form mặc định

            const formData = new FormData(this);

            fetch("/Api/Comments", {
                method: "POST",
                body: formData
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
                    console.error("Lỗi khi gửi AJAX request:", error);
                    const messageBox = document.getElementById("messageBox");
                    messageBox.textContent = "Đã xảy ra lỗi khi gửi bài viết.";
                    messageBox.classList.remove("success");
                    messageBox.classList.add("error");
                    messageBox.classList.remove("hidden");
                });
        });
    });
</script>

<?php
include __DIR__ . '/../../Controllers/Footer.php';
?>