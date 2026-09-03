<?php
error_reporting(0); // JSON API: khong de warning PHP lam vo JSON (PHP 8.5+)
include_once 'hidden/set.php';
$ep = $_GET['ep'] ?? '';
if (!$ep) { echo json_encode(['success' => false, 'message' => 'Missing endpoint']); exit; }
$key = $_GET['key'] ?? '';
$params = $_GET;
unset($params['ep'], $params['key']);
$url = $JAVA_API . '/' . $ep . '?' . http_build_query($params) . '&key=' . urlencode($key);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($data === false) {
    echo json_encode(['success' => false, 'message' => 'Proxy error: ' . curl_error($ch)]);
} else {
    header('Content-Type: application/json');
    echo $data;
}