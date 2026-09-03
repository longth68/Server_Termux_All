<?php
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}

include_once 'config.php';
include_once 'server_config.php';

$_user = isset($_SESSION['account']) ? $_SESSION['account'] : null;
if ($_user != null) {
	$_login = "on";
	$safe_user = isset_sql($_user);
	$user_arr = _fetch("SELECT * FROM account Where username='$safe_user'");
	if (!$user_arr) {
		header("location:/?out");
	}
	$_uid = $user_arr['id'];
	$_username = htmlspecialchars($user_arr['username']);
	$_coin = $user_arr['thoi_vang']; // HASHIRAMA: thoi_vang = thoi vang (coin)
	$_vnd = $user_arr['vnd'];
	$_tvnd = htmlspecialchars($user_arr['tongnap']);
	$_magioithieu = $user_arr['gioithieu'];
	$_status = $user_arr['active'];
	$_ticket = 0; // hashirama khong co cot luotquay
} else {
	$_login = null;
}

if (isset($_GET['out'])) {
	session_destroy();
	header("location:/");
}