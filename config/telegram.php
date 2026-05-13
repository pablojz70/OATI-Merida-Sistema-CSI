<?php
define('TELEGRAM_TOKEN', '8994345111:AAGXVglf8RGg2p-URbjuP2Zezf08V5TJ4ws');
define('TELEGRAM_API', 'https://api.telegram.org/bot' . TELEGRAM_TOKEN . '/');
define('TELEGRAM_PROXY', '172.27.160.160:8080');
define('TELEGRAM_PROXY_USER', 'zambpabj');
define('TELEGRAM_PROXY_PASS', '13804358');

// Proxy - descomentar y configurar si el servidor usa proxy
// define('TELEGRAM_PROXY', 'http://192.168.1.1:3128');
// define('TELEGRAM_PROXY_TYPE', CURLPROXY_HTTP); // CURLPROXY_HTTP, CURLPROXY_SOCKS5, etc.

function enviarTelegram($chat_id, $mensaje) {
    if (empty($chat_id)) return false;
    $url = TELEGRAM_API . 'sendMessage';
    $data = [
        'chat_id' => $chat_id,
        'text' => $mensaje,
        'parse_mode' => 'HTML',
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Proxy
    if (defined('TELEGRAM_PROXY') && !empty(TELEGRAM_PROXY)) {
        curl_setopt($ch, CURLOPT_PROXY, TELEGRAM_PROXY);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        if (defined('TELEGRAM_PROXY_USER') && !empty(TELEGRAM_PROXY_USER)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, TELEGRAM_PROXY_USER . ':' . TELEGRAM_PROXY_PASS);
        }
    }
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    if ($error) {
        error_log("Telegram curl error: $error");
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
        error_log("Telegram HTTP error: $http_code - Response: " . substr($result, 0, 200));
    }
    curl_close($ch);
    return $result;
}

function notificarTicket($conn, $usuario_id, $mensaje) {
    if (!$usuario_id) return;
    $stmt = $conn->prepare("SELECT telegram_id FROM Usuarios WHERE id = ? AND telegram_id IS NOT NULL AND telegram_id != ''");
    $stmt->execute([$usuario_id]);
    $user = $stmt->fetch();
    if ($user) {
        enviarTelegram($user['telegram_id'], $mensaje);
    }
}

function notificarRoles($conn, $roles, $mensaje) {
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $stmt = $conn->prepare("SELECT telegram_id FROM Usuarios WHERE privilegio IN ($placeholders) AND telegram_id IS NOT NULL AND telegram_id != ''");
    $stmt->execute($roles);
    $users = $stmt->fetchAll();
    foreach ($users as $user) {
        enviarTelegram($user['telegram_id'], $mensaje);
    }
}
