<?php

class Exchange
{
    public $pcoin;
    public $luong;
    function __construct($pcoin, $luong)
    {
        $this->pcoin = $pcoin;
        $this->luong = $luong;
    }
}
$exchanges = [];
for ($i = 0; $i < count($configDoiLuong); $i++) {
    array_push($exchanges, new Exchange($configDoiLuong[$i]['pCoin'], $configDoiLuong[$i]['luong']));
}
?>
<div class="overlay"></div>
<div class="text-center fw-semibold fs-5" style="color:#333 !important;">Đổi Coin ra Lượng
    <span class="text-danger"><?php echo $bonusDoiLuong['bonus'] > 0 ? "(KM " . $bonusDoiLuong['bonus'] . "%)" : "" ?></span>
</div>
<div id="noti" style="text-align: center;"></div>
<div class="d-flex justify-content-center">
    <div class="col-md-8">
        <div class="row text-center justify-content-center row-cols-2 row-cols-lg-3 g-2 g-lg-2 my-1 mb-2">
            <?php
            foreach ($exchanges as $exc) {
                echo '<div>
              <div class="col">
                  <div class="w-100 fw-semibold cursor-pointer" onclick="handleClick(' . $exc->pcoin . ')">
                      <div id="button-' . $exc->pcoin . '" class="recharge-method-item false" style="height: 90px;">
                          <div class="text-primary" >' . number_format($exc->pcoin) . 'P</div>
                          <div class="center-text text-dark"><span >Nhận⤵️</span></div>
                          <div class="text-danger">' . number_format($exc->luong + ($exc->luong) * ($bonusDoiLuong['bonus'] / 100)) . ' lượng</div>
                      </div>
                  </div>
              </div>
          </div>';
            }
            ?>
        </div>
        <?php
            $conn = SQL();
            $user_id = $user['id'];
            $balance = $user['balance'];

            $armymem_query = "SELECT name FROM players WHERE user_id = ?";
            $armymem_stmt = $conn->prepare($armymem_query);

            if ($armymem_stmt) {
                $armymem_stmt->bind_param("i", $user_id);
                $armymem_stmt->execute();
                $armymem_result = $armymem_stmt->get_result();

                echo '<div class="text-center">';
                echo '<div class="fw-semibold fs-6" style="color:#333 !important;">NHÂN VẬT NHẬN LƯỢNG</div>';
                echo '<div class="row text-center justify-content-center row-cols-2 row-cols-lg-3 g-2 g-lg-2 my-1 mb-2 ">';

                if ($armymem_result->num_rows > 0) {
                    while ($nhanvat_row = $armymem_result->fetch_assoc()) {
                        $nhanvat_name = $nhanvat_row['name'];
                        echo '<div class="col">';
                        echo '<div class="w-100 fw-semibold cursor-pointer">';
                        echo '<div id="nhanvat" class="recharge-method-item" style="height: 50px;">';
                        echo '<span style="color: black;">' . htmlspecialchars($nhanvat_name) . '</span>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="col">Không tìm thấy tên nhân vật trong bảng ninja.</div>';
                }

                $armymem_stmt->close();
                $conn->close();

                echo '</div>';
                echo '<div class="fw-semibold fs-6" style="color:#333 !important;">Lượng Hiện Có ' . number_format($user['amount_unpaid']) . '</div>';
                echo '</div>';
                
            } else {
                echo "Lỗi trong quá trình chuẩn bị truy vấn.";
            }
            ?>


        <div class="text-center mt-4">
            <div id="message-container" class="position-relative">
                <button id="confirm" type="button" onclick="onClickExchange()" class="w-50 rounded-3 btn btn-primary btn-sm " <?php echo isset($beforeSelected) ? '' : 'disabled'; ?>>Xác Nhận</button>
                <a id="error-message" class="w-50 rounded-3 btn btn-sm bg-danger text-white" style="display: none;">
                </a>
            </div>
            <!-- <div class="mt-2"><small class="fw-semibold"><a href="/user/transactions">Lịch sử giao dịch</a></small></div> -->
        </div>


    </div>
    <div class="modal fade" id="modalConfirmExchange" tabindex="-1" aria-labelledby="modalConfirmExchangeLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="my-2">
                        <div class="text-center"><a href="/"><img class="logo" alt="Logo" src="/images/logo.png" style="max-width: 200px;"></a></div>
                    </div>
                    <div class="text-center fw-semibold" style="color: black;">
                        <div class="fs-6 mb-2">Bạn thoát game trước khi thực hiện giao dịch chưa?</div>
                        <div id="noti-active"></div>
                        <span>Bạn phải thoát game trước khi giao dịch rồi vào lại game để tránh phát sinh lỗi trong quá trình cộng tiền</span>
                        <div class="mt-2"><button type="button" id="confirmExchange" onclick="handleConfirm()" class="btn-rounded btn btn-primary btn-sm ">Xác nhận đã thoát</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    let selected;
    let beforeSelected;
    let balance = <?php echo $user['balance']; ?>;

    let configDoiLuong = <?php echo json_encode($configDoiLuong); ?>;
    let bonus = <?php echo $bonusDoiLuong['bonus']; ?>;

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function handleClick(pcoin) {
        selected = pcoin;
        $(`#button-${selected}`).css('background-color', '#ffae52');

        if (beforeSelected && beforeSelected !== selected) {
            $(`#button-${beforeSelected}`).css('background-color', '');
            $(`#nhanvat-${beforeSelected}`).css('background-color', '');
        }
        beforeSelected = selected;

        let selectedConfig = configDoiLuong.find(config => config.pCoin === selected);
        let luong = selectedConfig ? selectedConfig.luong : 0;

        let requiredAmount = luong + (luong * (bonus / 100));

        $('#confirm').prop('disabled', false);
        if (balance >= selected) {
            $("#confirm").show();
            $("#error-message").hide();
        } else {
            let amountNeeded = selected - balance;
            $("#confirm").hide();
            let formattedAmountNeeded = formatNumber(amountNeeded);
            let baseUrl = window.location.origin;
            let rechargeUrl = `${baseUrl}/recharge`;
            let alertNoti = `Cần nạp thêm ${formattedAmountNeeded}đ`;
            $("#error-message").html(`<a href="${rechargeUrl}" class="text-white">${alertNoti}</a>`).show();
        }
    }

    function onClickExchange() {
        $("#modalConfirmExchange").modal("show");
    }

    function handleConfirm() {
        if (!selected) {
            let alertNoti = `<div class="alert alert-danger" id="error">Chưa chọn số lượng</div>`;
            $("#noti").prepend(alertNoti);
            return;
        }

        let selectedConfig = configDoiLuong.find(config => config.pCoin === selected);
        let luong = selectedConfig ? selectedConfig.luong : 0;
        let requiredAmount = luong + (luong * (bonus / 100));

        if (balance >= selected) {
            $.ajax({
                url: "/apixuli/exchange-gold",
                type: "POST",
                dataType: "json",
                data: JSON.stringify({
                    pcoin: selected
                }),
                success: function(data) {
                    selected = undefined;
                    if (data.code == "00") {
                        let alertNoti = '<div class="alert alert-success " id="success">Bạn đã đổi Coin thành công</div>';
                        $("#noti").prepend(alertNoti);
                        $("#modalConfirmExchange").modal("hide");
                        setTimeout(function() {
                            $("#noti").find("#success").remove();
                            location.reload();
                        }, 5000);
                    } else {
                        let alertNoti = `<div class="alert alert-danger" id="error">${data.text}</div>`;
                        $("#noti").prepend(alertNoti);
                        $("#modalConfirmExchange").modal("hide");
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    $("#overlay").hide();
                    console.log("Error in Operation", errorThrown);
                }
            });
        } else {
            let amountNeeded = selected - balance;
            let alertNoti = `Vui lòng nạp thêm ${amountNeeded} lượng để thực hiện giao dịch.`;
            $("#error-message").text(alertNoti).show();
        }
    }
</script>
