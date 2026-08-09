<?php
include __DIR__ . '/Controllers/Header.php';
?>
<div class="px-3">
  <div class="sc-bdvuGq BoTfp">
    <div class="pt-2 pb-10">
      <div>
        <div class="w-5/6 mx-auto">
          <tbody>
            <?php
            $currentMonth = date('m');
            $currentYear = date('Y');

            $vietnameseMonths = array(
              1 => 'Tháng 1',
              2 => 'Tháng 2',
              3 => 'Tháng 3',
              4 => 'Tháng 4',
              5 => 'Tháng 5',
              6 => 'Tháng 6',
              7 => 'Tháng 7',
              8 => 'Tháng 8',
              9 => 'Tháng 9',
              10 => 'Tháng 10',
              11 => 'Tháng 11',
              12 => 'Tháng 12'
            );

            $query = "SELECT name, SUM(amount) AS tongnap
            FROM (
                SELECT user_nap AS name, amount
                FROM napthe
                WHERE status = 1 AND MONTH(created_at) = $currentMonth

                UNION ALL

                SELECT user_nap AS name, sotien AS amount
                FROM atm_lichsu
                WHERE status = 1 AND MONTH(STR_TO_DATE(thoigian, '%d/%m/%Y %H:%i:%s')) = $currentMonth

                UNION ALL

                SELECT user_nap AS name, amount
                FROM atm_acb
                WHERE MONTH(date) = $currentMonth
            ) AS total_amount
            GROUP BY name
            ORDER BY tongnap DESC
            LIMIT 10";

            $stmt = $conn->prepare($query);
            $stmt->execute();

            $stt = 1;
            $monthName = $vietnameseMonths[intval($currentMonth)];

            echo '<div class="mx-auto flex justify-center py-3">
            <img src="/Assets/Images/Top.png" alt="iconTop" class="inline-block h-8">
            <h1 class="inline-block text-xl">BẢNG XẾP HẠNG ĐUA TOP NẠP - ' . $monthName . '</h1><img src="/Assets/Images/Top.png"
            alt="iconTop" class="inline-block h-8">
</div>
<div class="manage-body">
    <div class="alert" style="display: flex;">
    </div>';
            if ($stmt->rowCount() > 0) {

              echo '
              <div class="overflow-x-auto">
                <table class="w-full border-solid border-2 min-w-[300px]">
                <thead>
                  <tr class="border-solid border-2">
                    <th class="border-solid border-2 w-1/12">STT</th>
                    <th class="border-solid border-2 w-2/6">Nhân vật</th>
                    <th class="border-solid border-2 w-2/6">Điểm</th>
                  </tr>
                </thead>
                <tbody>';

              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '
        <tr class="border-solid border-2 bg-orange-200">
            <th class="border-solid border-2">' . $stt . '</th>
            <th class="border-solid border-2">' . $row['name'] . '</th>
            <th class="border-solid border-2">' . number_format($row['tongnap'], 0, ',') . 'đ</th>
        </tr>';
                $stt++;
              }

              echo '</tbody></table>
              </div>';
            } else {
              echo '<center><h6>Chưa có thống kê bảng xếp hạng top nạp tháng này!</h6></center>';
            }
            ?>
        </div>
      </div>
    </div>
  </div>
  <?php
  include __DIR__ . '/Controllers/Footer.php';
  ?>