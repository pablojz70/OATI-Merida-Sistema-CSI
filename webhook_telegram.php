<?php
// webhook_telegram.php - Responde a /start con el Chat ID
require_once __DIR__ . '/config/telegram.php';

$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (!$update || !isset($update['message'])) exit;

$chat_id = $update['message']['chat']['id'];
$text = trim($update['message']['text'] ?? '');
$nombre = $update['message']['from']['first_name'] ?? 'Usuario';

if ($text === '/start') {
    $mensaje = "Hola $nombre!\n\n"
        . "Tu ID de Telegram es:\n"
        . "<code>$chat_id</code>\n\n"
        . "Copia ese número y pégalo en tu perfil del Sistema CSI para recibir notificaciones.";
    enviarTelegram($chat_id, $mensaje);
}
