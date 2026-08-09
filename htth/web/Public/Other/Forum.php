<?php include __DIR__ . '/../../Controllers/Header.php'; ?>

<div class="px-3">
  <div class="sc-bdvuGq BoTfp">
    <div class="px-4">
      <div class="text-white font-normal text-center mt-5 py-2 bg-[#d8ba89] rounded-md">Hải Tặc Tí Hon | Poseidon
      </div>
      <div
        class="font-normal outline outline-1 my-2 py-2 px-3 rounded-md leading-4 hover:underline hover:cursor-pointer hover:bg-[#e0ceb1]">
        <p class="text-sm text-red-600 font-semibold">Nội Quy Diễn Đàn</p>
        <div class="text-xs flex gap-2">
          <span class="text-[#1aa837]">Tác giả:</span>
          <span class="text-[#C522AC]">ehehe1</span>
          <span class="text-red-600">☆☆☆☆☆ ♥♥♥♥♥</span>
        </div>
      </div>
      <hr class="mb-2">

      <?php
      // Truy vấn dữ liệu từ bảng forum chỉ lấy 10 bài viết ban đầu
      $sql = "SELECT * FROM forum ORDER BY date DESC LIMIT 10";
      $stmt = $conn->prepare($sql);
      $stmt->execute();

      // Hiển thị kết quả
      if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          echo '<div class="font-normal text-[#D8BA89] outline outline-1 mt-[1px] py-1 px-3 leading-4 hover:underline hover:cursor-pointer hover:bg-[#e0ceb1]">';
          echo '<a href="Post?id=' . $row["id"] . '" class="block">';
          echo '<p class="text-sm text-blue-600">' . $row["title"] . '</p>';
          echo '<div class="text-xs flex gap-2">';
          echo '<span class="text-[#1aa837]">Tác giả:</span>';
          echo '<span class="text-[#C522AC]">' . $row["name"] . '</span>';
          echo '<span class="text-red-600">☆ ♥</span>';
          echo '</div>';
          echo '</a>';
          echo '</div>
          <div id="postContainer">
          </div>';
        }
      }
      ?>

      <div>
        <button id="loadMoreBtn" class="text-sm mt-3 mb-5 mx-4 text-blue-600 hover:cursor-pointer hover:underline">Xem
          thêm</button>
      </div>
      <?php if ($_Login == true) { ?>
        <div class="px-3">
          <div class="flex flex-row items-center gap-3">
            <button id="writeButton"
              class="font-normal text-xs text-center py-2 bg-yellow-200 rounded-md px-4 hover:underline">Viết Bài
              Mới</button>
            <button id="closeButton" class="flex justify-center items-center hover:outline hover:rounded-full"
              style="display: none;">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" height="20px">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier">
                  <path d="M18 6L6 18" stroke="#222222" stroke-linecap="square" stroke-linejoin="round"></path>
                  <path d="M6 6L18 18" stroke="#222222" stroke-linecap="square" stroke-linejoin="round"></path>
                </g>
              </svg>
            </button>
          </div>
          <div id="messageBox" class="hidden"></div>

          <div id="writeForm" style="display: none;">
            <form id="submitForm" class="my-3 flex flex-col gap-2" method="post" action="">
              <div class="flex flex-col sm:flex-row justify-evenly sm:items-center">
                <label for="title" class="text-sm font-medium mr-5 min-w-[120px] py-1 px-1">Tiêu Đề Bài Viết</label>
                <input type="text" name="title" id="title"
                  class="bg-gray-50 border border-gray-300 text-sm rounded-lg py-1.5 px-1.5 w-full sm:w-4/6" required=""
                  value="">
              </div>
              <div class="flex flex-col sm:flex-row justify-evenly sm:items-center">
                <label for="content" class="text-sm font-medium mr-5 min-w-[120px] py-1 px-1">Nội Dung Bài Viết</label>
                <textarea name="content" id="content"
                  class="bg-gray-50 border border-gray-300 text-sm rounded-lg py-1.5 px-1.5 w-full sm:w-4/6 min-h-48"
                  required=""></textarea>
              </div>
              <div class="flex justify-center flex-col items-center">
                <button type="submit"
                  class="mt-4 px-2 py-2 text-base font-medium text-center text-white rounded-lg w-2/6 sm:w-1/6 bg-green-600 hover:bg-green-800">Gửi
                  Bài</button>
                <span class="py-3 text-red-600 text-sm text-center"></span>
              </div>
            </form>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const writeButton = document.getElementById("writeButton");
    const closeButton = document.getElementById("closeButton");
    const writeForm = document.getElementById("writeForm");
    const submitForm = document.getElementById("submitForm");
    const loadMoreBtn = document.getElementById("loadMoreBtn");

    writeButton.addEventListener("click", function () {
      writeForm.style.display = "block";
      closeButton.style.display = "inline-flex";
    });

    closeButton.addEventListener("click", function () {
      writeForm.style.display = "none";
      closeButton.style.display = "none";
    });

    submitForm.addEventListener("submit", function (event) {
      event.preventDefault(); // Ngăn chặn gửi form mặc định

      const formData = new FormData(this);

      fetch("/Api/Post", {
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