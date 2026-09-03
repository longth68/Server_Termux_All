<?php
include_once 'head.php';
?>

<head>
    <title>Giftcode - <?php echo $sv_name ?></title>
    <style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f4f7fa;
        /* Để duy trì màu nền trang */
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }


    p {
        font-size: 20px;
        /* Cải thiện kích thước font chữ */
        color: #34495e;
        margin: 20px 0;
        font-weight: bold;
        text-align: center;
        /* Căn giữa text */
        background-color: transparent;
        /* Không có nền */
        line-height: 1.6;
        /* Dễ đọc hơn */
    }

    .giftcode-container {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
    }

    .giftcode-item {
        background-color: #ff7700;
        color: white;
        padding: 10px 20px;
        border-radius: 30px;
        font-weight: bold;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-width: 120px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .copy-btn {
        background-color: #2ecc71;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 15px;
        cursor: pointer;
        font-size: 14px;
        margin-left: 10px;
        transition: background-color 0.3s;
    }

    .copy-btn:hover {
        background-color: #27ae60;
    }

    .footer {
        margin-top: 20px;
        font-size: 16px;
        color: #7f8c8d;
        text-align: center;
        /* Căn giữa footer */
    }
    </style>
</head>

<body>

    <p>!!! GIFTCODE !!!</p>
    <div class="giftcode-container">
        <div class="giftcode-item">
    base
    <button class="copy-btn" onclick="copyToClipboard('base')">Copy</button>
</div>
<div class="giftcode-item">
    base1
    <button class="copy-btn" onclick="copyToClipboard('base1')">Copy</button>
</div>
<div class="giftcode-item">
    base2
    <button class="copy-btn" onclick="copyToClipboard('base2')">Copy</button>
</div>
<div class="giftcode-item">
    base3
    <button class="copy-btn" onclick="copyToClipboard('base3')">Copy</button>
</div>
<div class="giftcode-item">
    base4
    <button class="copy-btn" onclick="copyToClipboard('base4')">Copy</button>
</div>
<div class="giftcode-item">
    base5
    <button class="copy-btn" onclick="copyToClipboard('base5')">Copy</button>
</div>
<div class="giftcode-item">
    base6
    <button class="copy-btn" onclick="copyToClipboard('base6')">Copy</button>
</div>
<div class="giftcode-item">
    base7
    <button class="copy-btn" onclick="copyToClipboard('base7')">Copy</button>
</div>
<div class="giftcode-item">
    base8
    <button class="copy-btn" onclick="copyToClipboard('base8')">Copy</button>
</div>
<div class="giftcode-item">
    do3std
    <button class="copy-btn" onclick="copyToClipboard('do3std')">Copy</button>
</div>
<div class="giftcode-item">
    do3sxd
    <button class="copy-btn" onclick="copyToClipboard('do3sxd')">Copy</button>
</div>
<div class="giftcode-item">
    do3snm
    <button class="copy-btn" onclick="copyToClipboard('do3snm')">Copy</button>
</div>

    </div>
    <div class="footer">
        Chúc anh em chơi game vui vẻ!
    </div>
    </div>

    <script>
    function copyToClipboard(giftcode) {
        var tempInput = document.createElement("input");
        tempInput.value = giftcode;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        alert("Đã sao chép giftcode: " + giftcode);
    }
    </script>
</body>