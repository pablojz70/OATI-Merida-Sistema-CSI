<?php
session_start();
if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    header('Location: index.php');
    exit();
}

header('Content-Type: application/json');

$token = defined('TELEGRAM_TOKEN') ? TELEGRAM_TOKEN : '8994345111:AAGXVglf8RGg2p-URbjuP2Zezf08V5TJ4ws';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot$token/getMe");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

if (defined('TELEGRAM_PROXY') && !empty(TELEGRAM_PROXY)) {
    $proxy_host = str_replace(['http://','https://'], '', TELEGRAM_PROXY);
    $parts = explode(':', $proxy_host);
    $fp = @fsockopen($parts[0], $parts[1] ?? 8080, $errno, $errstr, 2);
    if ($fp) {
        fclose($fp);
        curl_setopt($ch, CURLOPT_PROXY, TELEGRAM_PROXY);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        if (defined('TELEGRAM_PROXY_USER') && !empty(TELEGRAM_PROXY_USER)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, TELEGRAM_PROXY_USER . ':' . TELEGRAM_PROXY_PASS);
        }
    }
}

$result = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($http == 200 && $result) {
    $data = json_decode($result, true);
    echo json_encode([
        'ok' => true,
        'bot' => $data['result']['first_name'] ?? 'Bot',
        'username' => '@' . ($data['result']['username'] ?? 'desconocido')
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'error' => $error ?: 'HTTP ' . $http
    ]);
}
