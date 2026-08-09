<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

$_Login = null;
$_Users = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$_Ip = $_SERVER['REMOTE_ADDR'];

function fetchUserData($conn, $User)
{
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE user = :username");
    $stmt->bindParam(":username", $User);
    $stmt->execute();
    $user_arr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_arr) {
        return null;
    }

    $user_data = [];
    foreach ($user_arr as $key => $value) {
        $user_data[$key] = ($value !== null) ? htmlspecialchars($value) : '';
    }

    return [
        "_id" => $user_data['id'],
        "_user" => $user_data['user'],
        "_Pass" => $user_data['pass'],
        "_gmail" => $user_data['email'],
        "_admin" => $user_data['admin'],
        "_coin" => $user_data['vnd'],
        "_tcoin" => $user_data['tongnap'],
        "_status" => $user_data['active'],
    ];
}

function sanitizeUserData($data)
{
    if (is_array($data)) {
        return array_map('sanitizeUserData', $data);
    } else {
        return htmlspecialchars($data);
    }
}

if ($_Users !== null) {
    $_Login = "on";
    $user_data = fetchUserData($conn, $_Users);
    $user_sanitized = sanitizeUserData($user_data);

    $_Id = $user_sanitized['_id'];
    $_User = $user_sanitized["_user"];
    $_Pass = $user_sanitized["_Pass"];
    $_Email = $user_sanitized["_gmail"];
    $_Admin = $user_sanitized["_admin"];
    $_Coins = $user_sanitized["_coin"];
    $_TCoins = $user_sanitized["_tcoin"];
    $_Status = $user_sanitized["_status"];
}


function formatMoney($number)
{
    if (!is_numeric($number) || $number === null) {
        return '0 VNĐ';
    }

    $suffix = '';
    if ($number >= 1000000000000) {
        $number /= 1000000000000;
        $suffix = ' Tỷ';
    } elseif ($number >= 1000000000) {
        $number /= 1000000000;
        $suffix = ' Tỷ';
    } elseif ($number >= 1000000) {
        $number /= 1000000;
        $suffix = ' Triệu';
    } elseif ($number >= 1000) {
        $number /= 1000;
        $suffix = ' K';
    }

    return number_format($number) . $suffix;
}

function isValidInput($input)
{
    return preg_match('/^[a-zA-Z0-9_]+$/', $input) && strlen($input) <= 255;
}

function validateCaptcha($input, $captchaText)
{
    return strtoupper($input) === strtoupper($captchaText);
}

function generateCaptcha($length = 6)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $captcha = '';
    for ($i = 0; $i < $length; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captcha;
}

if (!isset($_POST["captcha"])) {
    $_SESSION['captcha'] = generateCaptcha(6);
}

function checkExistingUsername($conn, $User)
{
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM accounts WHERE user = :username");
        $stmt->bindValue(':username', $User, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

function checkExistingEmail($conn, $Email)
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM accounts WHERE email = :email");
    $stmt->bindValue(':email', $Email, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}