<?php
ob_start();
defined('NP') or header('location: /');

require(__DIR__ . '/../vendorzzx/autoload.php');
require(__DIR__ . '/function.php');

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
$isMaintained = false;

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

$conn = new mysqli($servername, $username, $password, $dbname);

@$conn->query("CREATE TABLE IF NOT EXISTS `nap_the` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `network` varchar(20) NOT NULL DEFAULT '',
  `serial` varchar(50) NOT NULL DEFAULT '',
  `pin` varchar(50) NOT NULL DEFAULT '',
  `amount` int(11) NOT NULL DEFAULT 0,
  `received` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0: Chờ duyệt, 1: Thành công, 2: Từ chối',
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

@$conn->query("CREATE TABLE IF NOT EXISTS `web_admin_commands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `command` varchar(50) NOT NULL,
  `target_user` varchar(50) DEFAULT NULL,
  `data` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0: Chờ xử lý, 1: Đã xử lý',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

@$conn->query("CREATE TABLE IF NOT EXISTS `server_status` (
  `id` int(11) NOT NULL DEFAULT 1,
  `online` int(11) NOT NULL DEFAULT 0,
  `bots` int(11) NOT NULL DEFAULT 0,
  `memory_mb` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$bonusnap = 0;
$list_recharge_price_atm = [
    [
        "amount" => 10000,
        "bonus" => $bonusnap //25

    ],
    [
        "amount" => 50000,
        "bonus" => $bonusnap //25
    ],
    [
        "amount" => 100000,
        "bonus" => $bonusnap //25
    ],
    [
        "amount" => 200000,
        "bonus" => $bonusnap //25
    ],
    [
        "amount" => 500000,
        "bonus" => $bonusnap //25
    ],
    [
        "amount" => 1000000,
        "bonus" => $bonusnap //27
    ],
    [
        "amount" => 2000000,
        "bonus" => $bonusnap //30
    ],
    [
        "amount" => 5000000,
        "bonus" => $bonusnap //36
    ],
    [
        "amount" => 10000000,
        "bonus" => $bonusnap //45
    ],
];

$list_recharge_price_momo = [
    [
        "amount" => 10000,
        "bonus" => 100 //25

    ],
    [
        "amount" => 50000,
        "bonus" => 100 //25
    ],
    [
        "amount" => 100000,
        "bonus" => 100 //25
    ],
    [
        "amount" => 200000,
        "bonus" => 100 //25
    ],
    [
        "amount" => 500000,
        "bonus" => 100 //25
    ],
    [
        "amount" => 1000000,
        "bonus" => 100 //27
    ],
    [
        "amount" => 2000000,
        "bonus" => 100 //30
    ],
    [
        "amount" => 5000000,
        "bonus" => 100 //36
    ],
    [
        "amount" => 10000000,
        "bonus" => 100 //45
    ],
];



$configNapTien = [
    'atm' => [
        'nganhang' => 'TPBANK', //Tên Ngân Hàng
        'chutaikhoan' => 'TRAN THI PHONG', //chủ tài khoản atm mà bạn sử dụng
        'sotaikhoan' => '400896739', //số tài khoản atm bạn sử dụng
        'apikey' => '',
        'matkhau' => ''
    ],
    'momo' => [
        'nganhang' => 'MOMO', 
        'chutaikhoan' => '', //tên chủ tài khoản ví momo
        'sotaikhoan' => '', //số điện thoại momo,
        'apikey' => '' //Api key mà api.web2m.com cung cấp cho bạn,
        //config apikey mới chạy được autobank
    ]
];

$fees = [
    'active' => 10000,
];

$bonusDoiLuong = [
    'bonus' => 0
];

$configDoiLuong = [
    [
        'pCoin' => 10000,
        'luong' => 5000,
    ],
    [
        'pCoin' => 20000,
        'luong' => 10000,
    ],
    [
        'pCoin' => 50000,
        'luong' => 25000,
    ],
    [
        'pCoin' => 100000,
        'luong' => 50000,
    ],
    [
        'pCoin' => 200000,
        'luong' => 100000,
    ],
    [
        'pCoin' => 500000,
        'luong' => 250000,
    ],
    [
        'pCoin' => 1000000,
        'luong' => 500000,
    ],
    [
        'pCoin' => 2000000,
        'luong' => 1000000,
    ],
    [
        'pCoin' => 5000000,
        'luong' => 2500000,
    ],
];

$bonusDoiXu = [
    'bonus' => 0
];

$configDoiXu = [
    [
        'pCoin' => 10000,
        'xu' => 5000000,
    ],
    [
        'pCoin' => 20000,
        'xu' => 10000000,
    ],
    [
        'pCoin' => 50000,
        'xu' => 25000000,
    ],
    [
        'pCoin' => 100000,
        'xu' => 70000000,
    ],
    [
        'pCoin' => 200000,
        'xu' => 200000000,
    ]
];

// config nap the dien thoai
$configChargingCard = [
    'partnerID' => "71884690152",
    'partnerKey' => "452a939d11faebc1e370ae6d9cadcdb0",
];

// config bonus nap the
$configBonusCharge = [
    [
        'bn0' => 0,
        'bn1' => 0,
        'bn2' => 0.02,
        'bn3' => 0.02,
        'bn4' => 0.03,
        'bn5' => 0.05
    ],
    [
        'bn0' => 0,
        'bn1' => 0.02,
        'bn2' => 0.04,
        'bn3' => 0.05,
        'bn4' => 0.07,
        'bn5' => 0.1
    ],
    [
        'bn0' => 0.02,
        'bn1' => 0.03,
        'bn2' => 0.05,
        'bn3' => 0.07,
        'bn4' => 0.1,
        'bn5' => 0.13
    ],
    [
        'bn0' => 0.03,
        'bn1' => 0.05,
        'bn2' => 0.07,
        'bn3' => 0.10,
        'bn4' => 0.13,
        'bn5' => 0.15
    ],
];
