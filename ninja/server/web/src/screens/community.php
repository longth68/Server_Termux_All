<?php
$Zalo_Link = $_ENV['LINK_ZALO_1'];
$Page_Link = $_ENV['LINK_PAGE'];
?>
<div class="card-body">
  <div class="text-center mb-4">
    <h3 style="color: #FFF;">Cộng Đồng &amp; Hỗ Trợ</h3>
  </div>

  <div class="d-flex flex-wrap justify-content-center gap-3">
    <!-- Zalo -->
    <a href="<?php echo htmlspecialchars($Zalo_Link); ?>" class="text-decoration-none">
      <div class="card text-white bg-dark rounded-4 shadow-sm transition-hover" style="width: 200px;">
        <div class="card-body d-flex align-items-center justify-content-center">
          <img src="/images/zalo.png" alt="Zalo icon" width="35" height="35">
          <span class="ms-3 fw-bold">Box Zalo 1</span>
        </div>
      </div>
    </a>

    <!-- Facebook Page -->
    <a href="<?php echo htmlspecialchars($Page_Link); ?>" class="text-decoration-none">
      <div class="card text-white bg-dark rounded-4 shadow-sm transition-hover" style="width: 200px;">
        <div class="card-body d-flex align-items-center justify-content-center">
          <img src="/images/page.png" alt="Page icon" width="35" height="35">
          <span class="ms-3 fw-bold">Page FB</span>
        </div>
      </div>
    </a>
  </div>
</div>

                    </div>
                </div>
            </a></div>
    </div>
</div>