<?php
define('TELEGRAM_TOKEN', '8994345111:AAGXVglf8RGg2p-URbjuP2Zezf08V5TJ4ws');
define('TELEGRAM_API', 'https://api.telegram.org/bot' . TELEGRAM_TOKEN . '/');

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
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
