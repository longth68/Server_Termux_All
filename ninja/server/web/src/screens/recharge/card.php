<?php
$conn = SQL();
$userId = isset($user['id']) ? (int) $user['id'] : 0;
$history = [];
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT `network`, `amount`, `received`, `serial`, `status`, `note`, `created_at` FROM `nap_the` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 20");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
}
$conn->close();

$cardNetworks = ['VIETTEL' => 'Viettel', 'MOBIFONE' => 'Mobifone', 'VINAPHONE' => 'Vinaphone'];

function getCardStatusLabel($status)
{
    switch ((int) $status) {
        case 1:
            return '<b class="text-success">Thành công</b>';
        case 2:
            return '<b class="text-danger">Từ chối</b>';
        default:
            return '<b class="text-warning">Chờ duyệt</b>';
    }
}
?>

<div class="d-flex justify-content-center">
   <div class="col-md-8 mt-3">
      <div class="fs-5 fw-semibold text-center">Nạp Thẻ Cào</div>
      <div class="mt-2">
         <div id="card_info"></div>

         <!-- Ẩn chọn nhà mạng -->
         <div class="text-center mt-3 d-none">
            <div class="fw-semibold">Chọn nhà mạng</div>
         </div>

         <div class="text-center mt-2">
            <div class="fw-semibold">Chọn mệnh giá</div>
            <div id="list_amount" class="row text-center justify-content-center row-cols-2 row-cols-lg-3 g-2 g-lg-2 my-1 mb-2">
               <?php foreach ($list_recharge_price_atm as $item): ?>
                  <div class="col">
                     <div class="w-100 fw-semibold cursor-pointer" data-amount="<?= $item['amount'] ?>">
                        <div class="recharge-method-item false" style="height: 60px;">
                           <div class="text-primary"><?= number_format($item['amount']) ?> đ</div>
                        </div>
                     </div>
                  </div>
               <?php endforeach; ?>
            </div>
         </div>

         <!-- Bỏ nhập Serial và PIN -->

         <div class="text-center mt-3 card-btn">
            <button type="button" id="btn_submit_card" class="w-50 rounded-3 btn btn-success btn-sm">Nạp Thẻ</button>
         </div>
      </div>

      <div class="mt-4">
         <div class="fs-5 fw-semibold text-center">Lịch Sử Nạp Thẻ</div>
         <?php if (count($history) > 0): ?>
            <div class="table-responsive mb-4" style="border-radius: 1rem;">
               <table class="table text-white fw-semibold mb-0" role="table">
                  <thead>
                     <tr class="text-start fw-bold text-uppercase gs-0">
                        <th>Thời gian</th>
                        <th>Nhà mạng</th>
                        <th>Mệnh giá</th>
                        <th>Serial</th>
                        <th>Nhận được</th>
                        <th>Trạng thái</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($history as $h): ?>
                        <tr>
                           <td><?= date('H:i d/m/Y', strtotime($h['created_at'])) ?></td>
                           <td><?= htmlspecialchars($h['network']) ?></td>
                           <td><?= number_format($h['amount']) ?> đ</td>
                           <td><?= htmlspecialchars($h['serial']) ?></td>
                           <td><?= $h['status'] == 1 ? number_format($h['received']) . ' P' : '-' ?></td>
                           <td>
                              <?= getCardStatusLabel($h['status']) ?>
                              <?php if ($h['status'] == 2 && !empty($h['note'])): ?>
                                 <br><small class="text-danger"><?= htmlspecialchars($h['note']) ?></small>
                              <?php endif; ?>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         <?php else: ?>
            <div class="text-center"><small class="fw-semibold">Bạn chưa có lịch sử nạp thẻ nào.</small></div>
         <?php endif; ?>
         <div class="text-center mt-2"><small class="fw-semibold"><a href="/user/transactions">Lịch sử giao dịch</a></small></div>
      </div>
   </div>
</div>

<script>
   (function () {
      'use strict'
      var network = '';
      var amount = 0;

      $("[data-network]").each(function () {
         var item = this;
         item.addEventListener("click", function () {
            network = $(item).data("network");
            $("[data-network] .recharge-method-item").removeClass("active").addClass("false");
            $(item).find(".recharge-method-item").removeClass("false").addClass("active");
         })
      })

      $("[data-amount]").each(function () {
         var item = this;
         item.addEventListener("click", function () {
            amount = parseInt($(item).data("amount"));
            $("[data-amount] .recharge-method-item").removeClass("active").addClass("false");
            $(item).find(".recharge-method-item").removeClass("false").addClass("active");
         })
      })

      $("#btn_submit_card").click(function () {
         var err = $("div.card-btn div#error").first()
         if (err) err.remove()

         if (!amount) {
            prependError("Vui lòng chọn mệnh giá.");
            return;
         }

         $("#NotiflixLoadingWrap").removeClass('hide');
         $.ajax({
            url: "/apixuli/charge-card",
            type: "POST",
            dataType: "json",
            data: JSON.stringify({
               network: 'DIRECT',
               serial: 'DIRECT',
               pin: 'DIRECT',
               amount: amount
            }),
            success: function (data) {
               $("#NotiflixLoadingWrap").addClass('hide');
               if (data.code == "00") {
                  prependSuccess(data.text);
                  setTimeout(function () {
                     location.reload();
                  }, 1500);
               } else {
                  prependError(data.text);
               }
            },
            error: function (xhr, textStatus, errorThrown) {
               $("#NotiflixLoadingWrap").addClass('hide');
               prependError("Có lỗi xảy ra, vui lòng thử lại.");
            },
         });
      })

      function prependError(text) {
         var box = $('<div class="alert alert-danger" id="error" role="alert"></div>');
         box.text(text);
         $("div.card-btn").prepend(box);
      }

      function prependSuccess(text) {
         var box = $('<div class="alert alert-success" id="error" role="alert"></div>');
         box.text(text);
         $("div.card-btn").prepend(box);
      }
   })()
</script>
