<div id="noti" style="text-align: center;"></div>
<div class="d-flex justify-content-center">
    <div class="col-md-8">
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
                echo '<div class="fw-semibold fs-5">Chọn nhân vật</div>';
                echo '<div class="row text-center justify-content-center row-cols-2 row-cols-lg-3 g-2 g-lg-2 my-1 mb-2 ">';
                if ($armymem_result->num_rows > 0) {
                    while ($nhanvat_row = $armymem_result->fetch_assoc()) {
                        $nhanvat_name = $nhanvat_row['name'];
                        echo '<div class="col">';
                        echo '<div class="w-100 fw-semibold cursor-pointer">';
                        echo '<div id="nhanvat-' . htmlspecialchars($nhanvat_name) . '" class="recharge-method-item" style="height: 50px;" onclick="handleCharacter(\'' . htmlspecialchars($nhanvat_name) . '\')">';
                        echo '<span style="color: black;">' . htmlspecialchars($nhanvat_name) . '</span>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="text-danger text-center fw-semibold mt-3 mb-2">Tài khoản chưa có nhân vật nào</div>';
                }

                $armymem_stmt->close();
                $conn->close();

                echo '</div>';
                echo '</div>';
                
            } else {
                echo "Lỗi trong quá trình chuẩn bị truy vấn.";
            }
            ?>

            <?php
            $conn = SQL(); 
            $user_id = $user['id'];
            $tanthu_query = "SELECT * FROM tanthu";
            $tanthu_result = $conn->query($tanthu_query);

            if ($tanthu_result && $tanthu_result->num_rows > 0) {
                echo '<div class="text-center">';
                echo '<div class="fw-semibold fs-5">Chọn gói quà tân thủ</div>';
                echo '</div>';
                echo '<div class="row text-center justify-content-center row-cols-1 row-cols-lg-2 g-2 g-lg-2 my-1 mb-2">';
                while ($row = $tanthu_result->fetch_assoc()) {
                    $gold = $row['gold'];
                    $coin = $row['coin'];
                    $yen = $row['yen'];
                    $price = $row['status'];
                    $code = $row['code'];
                    $items = json_decode($row['items'], true);
                    $item_html = '';
                    foreach ($items as $item) {
                        $item_id = $item['id'];
                        $item_query = "SELECT name, icon FROM item WHERE id = ?";
                        $item_stmt = $conn->prepare($item_query);
                        $item_stmt->bind_param("i", $item_id);
                        $item_stmt->execute();
                        $item_result = $item_stmt->get_result();

                        if ($item_result && $item_result->num_rows > 0) {
                            $item_data = $item_result->fetch_assoc();
                            $item_name = $item_data['name'];
                            $item_icon = $item_data['icon'];
                            $item_html .= '<div><img src="/images/1/Small' . htmlspecialchars($item_icon) . '.png" alt="' . htmlspecialchars($item_name) . '" class="img-fluid">';
                            $item_html .= '<span class="ms-1 text-primary">' . htmlspecialchars($item_name) . '</span></div>';
                        }
                    }

                    echo '<div class="col">';
                    echo '<div class="w-100 fw-semibold cursor-pointer" onclick="handleClick(' . htmlspecialchars($code) . ')">';
                    echo '<div id="button-' . htmlspecialchars($code) . '" class="recharge-method-item false">';
                    echo '<div class="text-danger">' . htmlspecialchars(number_format($price, 0, '.', '.')) . ' Coin</div>';
                    echo '<div class="center-text text-danger"><span>Nhận</span></div>';
                    echo '<div>';
                    
                    if ($gold > 0) {
                        echo '<div><img src="/images/1/luong.png" alt="Lượng" class="img-fluid"><span class="ms-1 text-primary">Lượng <b>x</b> ' . htmlspecialchars($gold) . '</span></div>';
                    }

                    if ($coin > 0) {
                        echo '<div><img src="/images/1/coin.png" alt="Xu" class="img-fluid"><span class="ms-1 text-primary">Xu <b>x</b> ' . htmlspecialchars($coin) . '</span></div>';
                    }

                    if ($yen > 0) {
                        echo '<div><img src="/images/1/yen.png" alt="Yên" class="img-fluid"><span class="ms-1 text-primary">Yên <b>x</b> ' . htmlspecialchars($yen) . '</span></div>';
                    }

                    echo $item_html;
                    echo '</div>'; 
                    echo '</div>'; 
                    echo '</div>'; 
                    echo '</div>'; 
                }
                echo '</div>';
            } else {
                echo '<div class="text-danger text-center fw-semibold mt-3 mb-2">Không có gói quà nào khả dụng</div>';
            }

            $conn->close();
            ?>


        <div class="text-center mt-4">
            <div id="message-container" class="position-relative">
                <button id="confirm" type="button" onclick="handleConfirm()" class="w-50 rounded-3 btn btn-primary btn-sm" <?php echo isset($beforeSelected) ? '' : 'disabled'; ?>>Xác Nhận</button>
                <a id="error-message" class="w-50 rounded-3 btn btn-sm bg-danger text-white" style="display: none;">
                </a>
            </div>
        </div>


    </div>
</div>


<script>
    let selectedPrice = null; 
    let selectedCharacter = null; 
    let previousSelectedCharacter = null; 
    let beforeSelected = null; 
    let balance = <?php echo $user['balance']; ?>;

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function handleClick(price) {
        selectedPrice = price;
        if (beforeSelected && beforeSelected !== selectedPrice) {
            $(`#button-${beforeSelected}`).css('background-color', '');
        }
        $(`#button-${selectedPrice}`).css('background-color', '#ffae52');
        beforeSelected = selectedPrice; 

        updateButtonState();
    }

    function handleCharacter(character) {
        selectedCharacter = character;
        if (previousSelectedCharacter && previousSelectedCharacter !== selectedCharacter) {
            $(`#nhanvat-${previousSelectedCharacter}`).css('background-color', '');
        }
        $(`#nhanvat-${selectedCharacter}`).css('background-color', '#ffae52');
        previousSelectedCharacter = selectedCharacter; 

        updateButtonState();
    }

    function updateButtonState() {
        if (selectedCharacter && selectedPrice) {
            $('#confirm').prop('disabled', false);
        } else {
            $('#confirm').prop('disabled', true);
        }
    }

    function handleConfirm() {
        if (!selectedCharacter || !selectedPrice) {
            let alertNoti = `<div class="alert alert-danger" id="error">Chưa chọn nhân vật và gói quà</div>`;
            $("#noti").prepend(alertNoti);
            return;
        }

        if (balance >= selectedPrice) {
            $.ajax({
                url: "/apixuli/gift",
                type: "POST",
                dataType: "json",
                data: JSON.stringify({
                    username: "<?php echo $user['username']; ?>",
                    selectedCharacter: selectedCharacter,
                    selectedPrice: selectedPrice,
                }),
                success: function(data) {
                    selectedPrice = null; 
                    if (data.code == "00") {
                        let alertNoti = '<div class="alert alert-success " id="success">Bạn Đã Mua Thành Công</div>';
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
            let alertNoti = `<div class="alert alert-danger" id="error">Số dư không đủ</div>`;
            $("#noti").prepend(alertNoti);
        }
    }

    $(document).ready(function() {
        $('#confirm').prop('disabled', true);
    });

</script>
