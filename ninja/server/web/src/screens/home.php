<div class="card-title h5">Bài viết mới</div>
<hr>
<div>
   <?php
   $posts = __select("news_posts");
   if ($posts != false && $posts->num_rows > 0) {
      while ($item = $posts->fetch_assoc()) {
         $conn = new mysqli($servername, $username, $password, $dbname);
         if ($conn->connect_error) {
            die("Kết nối thất bại: " . $conn->connect_error);
         }
         $currentUrl = $_SERVER['REQUEST_URI'];
         $urlParts = explode('/', $currentUrl);
         $slug = $item['slug'];
         $item_url = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]/post/" . $item['slug'];
         echo '
        <div class="post-item d-flex align-items-center my-2">
           <div class="post-image"><img src="/images/small/icon.png" alt="' . $item['title'] . '"></div>
           <div >
              <a style="color: rgb(0, 126, 112);" class="fw-bold" href="/post/' . $item['slug'] . '">' . $item['title'] . '</a>

              <div style="color:#6c757d!important;" class="text-muted font-weight-bold">Lượt xem: ' . $item['views'] . '<span class="comments-count" data-href="' . $item_url . '"></span></div>
           </div>
        </div>
        ';
      }
   }
   ?>

</div>

<div class="mt-4">
   <div class="card-title h5">Giới thiệu</div>
   <hr>
  <div style="text-align: center; padding: 10px;">
    <img alt="Thời Gian Boss Xuất Hiện" src="/images/SCHOOLZ.png" style="max-width: 100%; height: auto;">
</div>
   <div class="mx-2 fs-6">Ninja School là một game thế giới mở với chủ đề trường học ninja, nơi người chơi sẽ được trải nghiệm cuộc sống của một ninja thực thụ. Trong game, người chơi có thể tham gia vào các hoạt động giải trí như săn bắn quái vật, khám phá khu rừng bí ẩn, hoặc tham gia đấu trường PvP để thử thách và cạnh tranh với những ninja khác. Ngoài ra, game còn có nhiều nhiệm vụ và thử thách khác nhau cho người chơi hoàn thành, từ đó thu thập được điểm kinh nghiệm và trang bị vũ khí, trang phục mới. Với đồ họa đẹp mắt, âm thanh sống động và nội dung đa dạng, Ninja School sẽ đem đến cho người chơi những trải nghiệm tuyệt vời và thỏa mãn niềm đam mê với văn hóa ninja.<div></div>
   </div>
</div>
<div class="mt-4">
<div class="card-title h5">Hình ảnh về game</div>
<hr>
<div style="text-align: center; padding-top: 10px; padding-bottom: 10px;">
            <img alt="" src="/images/gif/0705.gif" style="height: 90px; width: 90px;">&nbsp; <img alt="" src="/images/gif/0705_1_.gif" style="height: 90px; width: 90px;">&nbsp; <img alt="" src="/images/gif/0705_2_.gif" style="height: 90px; width: 90px;">&nbsp; <img alt="" src="/images/gif/0705_3_.gif" style="height: 90px; width: 90px;">
          </div>
 </div>