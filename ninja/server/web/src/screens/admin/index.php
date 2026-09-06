<?php
require_once(__DIR__ . '/../../../core/configs.php');
if (!isset($_SESSION['user'])) {
    header('Location: /home');
    exit;
}
$user = $_SESSION['user'];
if ($user['admin_web'] != 1) {
    header("Location: /home");
    exit();
}

$conn = SQL();

$userQuery = $conn->query("SELECT `id` FROM `users`;");
$armymemQuery = $conn->query("SELECT `id` FROM `players`;");
$onlineCount = $conn->query("SELECT `id` FROM `users` WHERE `online` > 0;")->num_rows;
$activeCount = $conn->query("SELECT `id` FROM `users` WHERE `status` > 0;")->num_rows;
$inactiveCount = $conn->query("SELECT `id` FROM `users` WHERE `status` = 0;")->num_rows;
$xuCount = $conn->query("SELECT `id` FROM `players` WHERE `xu` > 0;")->num_rows;
$luongCount = $conn->query("SELECT `id` FROM `users` WHERE `luong` > 0;")->num_rows;
?>
<div class="bg-content" style="border-radius: 1rem; padding:10px">
    <div style="text-align:center;">
        <h4><?php echo isset($user['username']) ? $user['username'] : 'Trống'; ?></u> đang vào quản lý</h4>
    </div>
    <div class="shadow">
        <div class="content">
            <div style="margin-top:10px; text-align:center;">
                <h4>Chức Năng</h4>
            </div>
            <div class="container mb-2">
                <div class="row text-center justify-content-center g-2 mt-1">
				    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/member">Chỉnh Sửa Thành Viên</a>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/code">Chỉnh Sửa giftcode</a>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/recharge">Duyệt Nạp Thẻ</a>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/update-file">Up File</a>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/bot">Quản Lý Bot AI</a>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/boss">Triệu Hồi Boss</a>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/notice">Thông Báo Server</a>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/user">Quản Lý Người Chơi</a>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/server">Quản Lý Máy Chủ</a>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/shop">Shop Editor</a>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <a class="btn btn-success fw-semibold" href="/admin/logs">Lịch Sử / Log</a>
                    </div>
                </div>
                <canvas style="margin-top:10px;" class="bar-chart" id="barChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctxBar = document.getElementById('barChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Tài khoản', 'Đang chơi', 'Nick đã kích hoạt', 'Nick chưa kích hoạt'],
            datasets: [{
                label: 'Số lượng',
                data: [
                    <?php echo $userQuery->num_rows; ?>,
                    <?php echo $onlineCount; ?>,
                    <?php echo $activeCount; ?>,
                    <?php echo $inactiveCount; ?>
                ],
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB', 
                    '#FFCE56',
                    '#4BC0C0'  
                ],
                borderColor: 'rgba(0,0,0,0.1)',
                borderWidth: 1,
                barThickness: 50
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        autoSkip: false,
                    },
                    barPercentage: 0.3,
                    categoryPercentage: 0.3
                },
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return tooltipItem.label + ': ' + tooltipItem.raw;
                        }
                    }
                },
                datalabels: {
                    color: '#FFFFFF',
                    anchor: 'end',
                    align: 'top',
                    offset: 4,
                    formatter: (value, context) => {
                        return context.chart.data.labels[context.dataIndex] + ': ' + value;
                    },
                    font: {
                        weight: 'bold'
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        autoSkip: false,
                    },
                    barPercentage: 0.3,
                    categoryPercentage: 0.3
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<style>

</style>