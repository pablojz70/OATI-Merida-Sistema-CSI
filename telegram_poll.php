<?php
// telegram_poll.php - Obtiene mensajes del bot y responde a /start
// Ejecutar: php telegram_poll.php
// O configurar cron: * * * * * php /ruta/telegram_poll.php

require_once __DIR__ . '/config/telegram.php';

$url = TELEGRAM_API . 'getUpdates?timeout=10&offset=';
$offset_file = __DIR__ . '/temp/telegram_offset.txt';
$offset = 0;

if (file_exists($offset_file)) {
    $offset = (int)file_get_contents($offset_file);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url . $offset);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!$data || !isset($data['ok']) || !$data['ok']) {
    exit;
}

foreach ($data['result'] as $update) {
    $update_id = $update['update_id'];
    $offset = $update_id + 1;
    
    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        $text = trim($update['message']['text'] ?? '');
        $nombre = $update['message']['from']['first_name'] ?? 'Usuario';
        
        if ($text === '/start') {
            $mensaje = "Hola $nombre!\n\n"
                . "Tu ID de Telegram es:\n"
                . "<code>$chat_id</code>\n\n"
                . "Copia ese número y pégalo en tu perfil del Sistema CSI para recibir notificaciones de tickets.";
            enviarTelegram($chat_id, $mensaje);
        }
    }
}

if (!is_dir(__DIR__ . '/temp')) {
    mkdir(__DIR__ . '/temp', 0777, true);
}
file_put_contents($offset_file, $offset);
