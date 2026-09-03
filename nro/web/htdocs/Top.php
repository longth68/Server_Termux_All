<?php
include_once 'head.php';

$query = "SELECT a.id, a.tongnap, p.name as char_name 
          FROM account a 
          INNER JOIN player p ON p.account_id = a.id 
          WHERE p.name IS NOT NULL 
          ORDER BY a.tongnap DESC 
          LIMIT 10";
$result = mysqli_query($conn, $query);

// Get top 3 for podium display
$top3 = [];
for($i = 0; $i < 3 && $row = mysqli_fetch_assoc($result); $i++) {
    $top3[] = $row;
}
?>

<!-- Add this navigation section before the first card -->
<div class="card mb-4">
    <div class="card-body p-0">
        <nav class="nav nav-pills nav-fill ranking-nav">
            <a class="nav-item nav-link active" href="Top.php">
                <i class="fas fa-coins me-2"></i>Xếp Hạng Nạp
            </a>
            <a class="nav-item nav-link" href="TopPower.php">
                <i class="fas fa-fist-raised me-2"></i>Xếp Hạng Sức Mạnh
            </a>
			<a class="nav-item nav-link" href="TopQuest.php">
                <i class="fas fa-fist-raised me-2"></i>Xếp Hạng Nhiệm vụ
            </a>
        </nav>
    </div>
</div>

<style>
.top-player {
    position: relative;
    transition: transform 0.3s;
}
.top-player:hover {
    transform: translateY(-5px);
}
.circle-frame {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin: 0 auto;
    overflow: hidden;
    border: 4px solid;
    position: relative;
}
.rank-badge {
    position: absolute;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    top: -10px;
    right: -10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.name-banner {
    background: rgba(0,0,0,0.7);
    padding: 8px 15px;
    border-radius: 20px;
    margin-top: 10px;
    color: white;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.amount {
    font-size: 1.1em;
    font-weight: bold;
    margin-top: 5px;
    color: #ffd700;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.player-frame {
    position: relative;
    width: 160px;
    height: 160px;
    margin: 0 auto;
}

.frame-image {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 160px;
    height: 160px;
    z-index: 2;
}

.circle-frame {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1;
}

.top1-frame { width: 180px; height: 180px; }
.top2-frame { width: 140px; height: 140px; }
.top3-frame { width: 130px; height: 130px; }

.ranking-nav {
    background: linear-gradient(45deg, #1a5f7a, #2980b9);
    border-radius: 8px;
    overflow: hidden;
}

.ranking-nav .nav-link {
    color: rgba(255,255,255,0.8);
    padding: 15px;
    transition: all 0.3s ease;
    border: none;
    position: relative;
}

.ranking-nav .nav-link:hover {
    color: white;
    background-color: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.ranking-nav .nav-link.active {
    background: rgba(255,255,255,0.2);
    color: white;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.ranking-nav .nav-link i {
    margin-right: 8px;
}

</style>

<div class="card mb-4">
    <div class="card-body">
        <div class="row justify-content-center align-items-center text-center py-4">
            <!-- Top 2 -->
            <div class="col-4 top-player">
                <?php if(isset($top3[1])): ?>
                <div class="position-relative" style="margin-top: 40px;">
                    <div class="player-frame">
                        <img src="/khung/top2.png" class="frame-image top2-frame" alt="Frame">
                        <div class="circle-frame" style="border-color: #C0C0C0; width: 100px; height: 100px;">
                            <img src="/khung/top2.jpg" alt="Top 2" class="w-100 h-100 object-fit-cover">
                            <div class="rank-badge" style="background: #C0C0C0;">2</div>
                        </div>
                    </div>
                    <div class="name-banner" style="background: linear-gradient(45deg, #C0C0C0, #E8E8E8);">
                        <?php echo htmlspecialchars($top3[1]['char_name']); ?>
                    </div>
                    <div class="amount"><?php echo number_format($top3[1]['tongnap']) ?> VND</div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Top 1 -->
            <div class="col-4 top-player">
                <?php if(isset($top3[0])): ?>
                <div class="position-relative">
                    <div class="player-frame">
                        <img src="/khung/top1.png" class="frame-image top1-frame" alt="Frame">
                        <div class="circle-frame" style="border-color: #FFD700; width: 140px; height: 140px;">
                            <img src="/khung/top1.jpg" alt="Top 1" class="w-100 h-100 object-fit-cover">
                            <div class="rank-badge" style="background: #FFD700; width: 50px; height: 50px; font-size: 1.5em;">1</div>
                        </div>
                    </div>
                    <div class="name-banner" style="background: linear-gradient(45deg, #FFD700, #FFA500);">
                        <?php echo htmlspecialchars($top3[0]['char_name']); ?>
                    </div>
                    <div class="amount" style="font-size: 1.3em;"><?php echo number_format($top3[0]['tongnap']) ?> VND</div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Top 3 -->
            <div class="col-4 top-player">
                <?php if(isset($top3[2])): ?>
                <div class="position-relative" style="margin-top: 60px;">
                    <div class="player-frame">
                        <img src="/khung/top3.png" class="frame-image top3-frame" alt="Frame">
                        <div class="circle-frame" style="border-color: #CD7F32; width: 90px; height: 90px;">
                            <img src="/khung/top3.jpg" alt="Top 3" class="w-100 h-100 object-fit-cover">
                            <div class="rank-badge" style="background: #CD7F32;">3</div>
                        </div>
                    </div>
                    <div class="name-banner" style="background: linear-gradient(45deg, #CD7F32, #DBA067);">
                        <?php echo htmlspecialchars($top3[2]['char_name']); ?>
                    </div>
                    <div class="amount"><?php echo number_format($top3[2]['tongnap']) ?> VND</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0 text-center">Bảng Xếp Hạng Nạp</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Nhân vật</th>
                        <th scope="col">Tổng nạp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($result, 0);
                    $rank = 1;
                    while($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                                <th scope='row'>{$rank}</th>
                                <td>{$row['char_name']}</td>
                                <td>" . number_format($row['tongnap']) . " VND</td>
                              </tr>";
                        $rank++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.bg-silver { background-color: #C0C0C0; }
.bg-bronze { background-color: #CD7F32; }
.text-silver { color: #C0C0C0; }
.text-bronze { color: #CD7F32; }
</style>
