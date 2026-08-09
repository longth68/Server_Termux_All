<div class="fw-semibold text-center h5" style="color:#FFFFFF !important;">TẢI GAME</div>

<?php
$directory = "./files/";
$fileTypes = ["JAVA" => "jar", "COMVERT" => "zip", "APK" => "apk", "IPHONE" => "ipa", "PC" => "rar"];
$filesByType = [];

foreach ($fileTypes as $type => $ext) {
    $filesByType[$type] = [];
}

if (is_dir($directory)) {
    $files = scandir($directory);
    foreach ($files as $file) {
        if ($file !== "." && $file !== "..") {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            foreach ($fileTypes as $type => $expectedExt) {
                if ($ext === $expectedExt) {
                    $filesByType[$type][] = ["name" => $file, "file_path" => $directory . $file];
                }
            }
        }
    }
}
?>

<!-- Styles -->
<style>
    body {
        background-color: #f4f4f9;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
   
   .canchinh {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 60%;
      margin: auto;
   }
</style>
<div class="container mt-5">
    <div class="download-grid">
        <?php 
        $downloadOptions = [
            "JAVA" => ["label" => "Bản Jar", "icon" => "🎮"],
            "COMVERT" => ["label" => "Bản jar Convert zip", "icon" => "🗜️"],
            "APK" => ["label" => "Bản ANDROID", "icon" => "📱"],
            "IPHONE" => ["label" => "Bản IOS", "icon" => "🍏"],
            "PC" => ["label" => "Bản PC", "icon" => "💻"]
        ];
        
        foreach ($downloadOptions as $key => $option) { 
            echo '
            <div class="download-card" data-bs-toggle="modal" data-bs-target="#modal'.$key.'">
                <div class="icon">'.$option["icon"].'</div>
                <div class="label">'.$option["label"].'</div>
            </div>';
        }
        ?>
    </div>
</div>

<!-- CSS Responsive -->
<style>
    .download-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: center;
        max-width: 90%;
        margin: auto;
    }

    .download-card {
        flex: 1 1 calc(50% - 20px); /* 2 cột trên mobile */
        max-width: 180px;
        text-align: center;
        padding: 15px;
        border-radius: 12px;
        background: linear-gradient(135deg, #ff9966, #ff5e62);
        color: white;
        font-weight: bold;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }

    .download-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    }

    .icon {
        font-size: 35px;
        margin-bottom: 5px;
    }

    .label {
        font-size: 16px;
    }

    @media (min-width: 768px) {
        .download-card {
            flex: 1 1 calc(33.333% - 20px); /* 3 cột trên tablet */
        }
    }
    
    @media (min-width: 1024px) {
        .download-card {
            flex: 1 1 calc(20% - 20px); /* 5 cột trên desktop */
        }
    }
</style>



<style>
    .file-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
    }
    .file-card {
        border: 1px solid #007bff;
        border-radius: 15px;
        background: #0b6307;
        transition: background 0.3s ease, transform 0.3s ease;
        padding: 15px;
        text-align: center;
        margin: 10px 0;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        width: 200px;
    }
    .file-card:hover {
        background: #007bff;
        color: #fff;
        transform: scale(1.05);
    }
    .file-name {
        font-weight: bold;
        color: #333;
    }
	
	.custom-close {
    filter: invert(22%) sepia(96%) saturate(7471%) hue-rotate(357deg) brightness(95%) contrast(106%);
	}
	.custom-close:hover {
		filter: invert(35%) sepia(100%) saturate(2000%) hue-rotate(357deg) brightness(90%) contrast(120%);
	}
	.file-name {
		font-size: 18px; /* Tăng kích thước chữ */
		font-weight: bold; /* Chữ đậm */
		color: black; /* Màu đỏ */
	}


</style>
<!-- MODALS -->
<?php foreach ($downloadOptions as $type => $label) { ?>
<div class="modal fade" id="modal<?= $type ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content position-relative">
            <button type="button" class="btn-close custom-close position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="my-2">
                <h5 class="py-3 mx-3 needs-validation">Danh sách file <?= strtoupper($type) ?></h5>
            </div>
            <div class="file-container d-flex justify-content-center">
                <?php if (!empty($filesByType[$type])) {
                    foreach ($filesByType[$type] as $file) {
                        echo '<div class="file-card">
                                <span class="file-name">' . $file["name"] . '</span>
                                <a href="' . $file["file_path"] . '" class="btn btn-success">Tải xuống</a>
                              </div>';
                    }
                } else {
                    echo '<p class="text-center">Chưa có file nào.</p>';
                } ?>
            </div>
        </div>
    </div>
</div>

<?php } ?>

<!-- Hướng dẫn cài đặt -->
<div class="text-center post-item d-flex align-items-center my-2 canchinh">
    <span class="fw-semibold">Phiên Bản Dành Cho IOS TestFlight</span>
    <div><a href="https://testflight.apple.com/join/uWBHAncp" class="btn btn-danger me-1 mt-1"><b>TestFlight Nso 231 ( chọn nso_soxo )</b></a></div>
</div>
<div class="text-center post-item d-flex align-items-center my-2 canchinh">
    <div class="">
        <div class="h6" style="color:black !important">Hướng dẫn cách cài đặt</div>
        <div>Bước 1: Tải Microemulator: <a href="https://angelchip.net/files/share/AngelChipEmulator.jar" style="color:red !important">AngelChipEmulator.jar</a></div>
        <div>Bước 2: Tải , cài đặt JDK: <a href="https://drive.google.com/file/d/1200YW8fcvbDwf0y11x3S1goxPBGXoU9k/view?usp=sharing" style="color:red !important">jdk-8u202-windows-i586.exe</a></div>
        <div>Bước 3: Tải một trong các phiên bản bên trên (Gợi ý: bản 148)</div>
        <div>Bước 5: Mở ứng dụng AngelChipEmulator.jar</div>
        <div>Bước 6: Kéo file game có đuôi .jar vào và bấm Start</div>
        <div>Trước khi bấm Start các bạn căn chỉnh lại kích thước sao cho dễ chơi nhất.</div>
    </div>
</div>