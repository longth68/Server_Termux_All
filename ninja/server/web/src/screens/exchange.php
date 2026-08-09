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

// Khởi tạo danh sách đổi lượng
$exchanges = [];
foreach ($configDoiLuong as $config) {
    $exchanges[] = new Exchange($config['pCoin'], $config['luong']);
}

?>
<div class="overlay"></div>

<!-- Tiêu đề -->
<div class="text-center fw-semibold fs-5" style="color:#333 !important;">
    Đổi Coin ra Lượng
    <span class="text-danger"><?php echo $bonusDoiLuong['bonus'] > 0 ? "(KM " . $bonusDoiLuong['bonus'] . "%)" : "" ?></span>
</div>

<div id="noti" style="text-align: center;"></div>

<div class="d-flex justify-content-center">
    <div class="col-md-8">

        <!-- Danh sách số lượng Coin đổi ra Lượng -->
        <div class="row text-center justify-content-center row-cols-2 row-cols-lg-3 g-2 g-lg-2 my-1 mb-2">
            <?php foreach ($exchanges as $exc) : ?>
                <div>
                    <div class="col">
                        <div class="w-100 fw-semibold cursor-pointer" onclick="handleClick(<?= $exc->pcoin ?>)">
                            <div id="button-<?= $exc->pcoin ?>" class="recharge-method-item false" style="height: 90px;">
                                <div class="text-primary"><?= number_format($exc->pcoin) ?>P</div>
                                <div class="center-text text-dark"><span>Nhận⤵️</span></div>
                                <div class="text-danger">
                                    <?= number_format($exc->luong + ($exc->luong * ($bonusDoiLuong['bonus'] / 100))) ?> lượng
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Lấy danh sách nhân vật -->
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
                    echo '<div class="w-100 fw-semibold cursor-pointer" onclick="selectCharacter(\'' . htmlspecialchars($nhanvat_name) . '\')">';
                    echo '<div id="nhanvat-' . htmlspecialchars($nhanvat_name) . '" class="recharge-method-item" style="height: 50px;">';
                    echo '<span style="color: black;">' . htmlspecialchars($nhanvat_name) . '</span>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="col">Không tìm thấy tên nhân vật.</div>';
            }

            $armymem_stmt->close();
            $conn->close();

            echo '</div>';
            echo '<div class="fw-semibold fs-6" style="color:#333 !important;">Lượng Hiện Có ' . number_format($user['amount_unpaid']) . '</div>';
            echo '</div>';
        }
        ?>

        <!-- Nút xác nhận -->
        <div class="text-center mt-4">
            <div id="message-container" class="position-relative">
                <button id="confirm" type="button" onclick="handleConfirm()" class="w-50 rounded-3 btn btn-primary btn-sm" disabled>Xác Nhận</button>
                <a id="error-message" class="w-50 rounded-3 btn btn-sm bg-danger text-white" style="display: none;"></a>
            </div>
        </div>

    </div>
</div>

<script>
    let selected;
    let beforeSelected;
    let selectedCharacter = null;
    let balance = <?= $user['balance']; ?>;
    let configDoiLuong = <?= json_encode($configDoiLuong); ?>;
    let bonus = <?= $bonusDoiLuong['bonus']; ?>;

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function handleClick(pcoin) {
        selected = pcoin;
        document.querySelectorAll('.recharge-method-item').forEach(item => item.style.backgroundColor = '');
        document.getElementById(`button-${selected}`).style.backgroundColor = '#ffae52';

        beforeSelected = selected;

        let selectedConfig = configDoiLuong.find(config => config.pCoin === selected);
        let requiredAmount = selectedConfig ? selectedConfig.luong + (selectedConfig.luong * (bonus / 100)) : 0;

        document.getElementById("confirm").disabled = !selected || !selectedCharacter;

        if (balance >= selected) {
            document.getElementById("confirm").style.display = "inline-block";
            document.getElementById("error-message").style.display = "none";
        } else {
            let amountNeeded = selected - balance;
            document.getElementById("confirm").style.display = "none";
            let rechargeUrl = `/recharge`;
            document.getElementById("error-message").innerHTML = `<a href="${rechargeUrl}" class="text-white">Cần nạp thêm ${formatNumber(amountNeeded)}đ</a>`;
            document.getElementById("error-message").style.display = "inline-block";
        }
    }

    function selectCharacter(characterName) {
        if (selectedCharacter) {
            document.getElementById(`nhanvat-${selectedCharacter}`).style.backgroundColor = "";
        }

        selectedCharacter = characterName;
        document.getElementById(`nhanvat-${selectedCharacter}`).style.backgroundColor = "#ffae52";

        document.getElementById("confirm").disabled = !selected || !selectedCharacter;
    }

    function handleConfirm() {
        if (!selected) {
            document.getElementById("noti").innerHTML = '<div class="alert alert-danger">Chưa chọn số lượng</div>';
            return;
        }
        if (!selectedCharacter) {
            document.getElementById("noti").innerHTML = '<div class="alert alert-danger">Chưa chọn nhân vật nhận lượng</div>';
            return;
        }

        $.ajax({
            url: "/apixuli/exchange-gold",
            type: "POST",
            dataType: "json",
            data: JSON.stringify({
                pcoin: selected,
                character: selectedCharacter
            }),
            success: function(data) {
                if (data.code === "00") {
                    alert("Bạn đã đổi Coin thành công!");
                    location.reload();
                } else {
                    alert(data.text);
                }
            },
            error: function(xhr) {
                console.error("Lỗi khi thực hiện giao dịch", xhr);
            }
        });
    }
</script>
