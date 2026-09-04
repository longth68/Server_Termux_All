<?php
return;

include_once '../hidden/set.php';

$handle = fopen('../logs/mbbank.log', 'a') or die("cant open file");

if (!isset($_POST['secret']) || $_POST['secret'] != $post_secret || !isset($_POST['action'])) {
    echo json_encode(array("status" => "error", "message" => "Yêu cầu không hợp lệ"));
    return;
}

if (!isset($_SESSION['LAST_CHECK_MB'])) {
    $_SESSION['LAST_CHECK_MB'] = time() - 5;
}

if (time() - $_SESSION['LAST_CHECK_MB'] < 5) {
    $time_left = 5 - (time() - $_SESSION['LAST_CHECK_MB']);
    echo json_encode(array("status" => "error", "message" => "Vui lòng thử lại sau $time_left giây"));
    return;
}

$action = $_POST['action'];

function reCheck($description, $username, $vnd, $handle)
{
	$pattern = '/nrotf([A-Za-z0-9]{6})/i';
	$match = array();
	if (preg_match($pattern, $description, $match)) {
		$tranid = $match[1];
	} else {
		return;
	}	

    $query = "SELECT * FROM `naptien` WHERE type='BANK' AND uid='$username' AND vnd='$vnd' AND tranid='$tranid' AND tinhtrang='0'";
    $result = _query($query);
    if ($result->num_rows == 0) {
        return;
    }

    $ticket = $vnd / 10000;
    $query = "UPDATE `account` SET vnd = vnd + {$vnd}, tongnap = tongnap + {$vnd}, actived = 1, luotquay = luotquay + {$ticket} WHERE username = '{$username}'"; 
    _query($query);

    $query = "UPDATE `naptien` SET `tinhtrang`='1' WHERE type='BANK' AND uid='$username' AND vnd='$vnd' AND tranid='$tranid' AND tinhtrang='0'";
    fwrite($handle, $query . "\n");
    _query($query);
}

function insert($query, $handle)
{
    fwrite($handle, $query . "\n");
    _query($query);
}

$api = "https://api.sieuthicode.net/historyapitpbv3/$bank_password/$bank_account/$bank_token";

$query = $_POST['command'];
insert($query, $handle);

fclose($handle);

$_SESSION['LAST_CHECK_MB'] = time();

echo json_encode(array("status" => "success", "message" => "OK"));
exit();
