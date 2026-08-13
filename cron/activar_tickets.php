<?php
// cron/activar_tickets.php - Activa tickets programados un día antes del evento
// Ejecutar diariamente via cron:
//   5 0 * * * /opt/lampp/bin/php /ruta/cron/activar_tickets.php >/dev/null 2>&1

date_default_timezone_set('America/Caracas');

try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    file_put_contents(__DIR__ . '/activar_tickets.log', date('Y-m-d H:i:s') . " ERROR CONEXION: " . $e->getMessage() . "\n", FILE_APPEND);
    exit(1);
}

// Activar tickets cuyo evento es hoy o mañana (un día antes)
$stmt = $conn->prepare("SELECT id, numero_ticket, fecha_evento FROM Tickets 
    WHERE estado = 'Programado' 
    AND fecha_evento IS NOT NULL 
    AND fecha_evento <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$contador = 0;
foreach ($tickets as $t) {
    $upd = $conn->prepare("UPDATE Tickets SET estado = 'Nuevo' WHERE id = ? AND estado = 'Programado'");
    $upd->execute([$t['id']]);
    if ($upd->rowCount() > 0) {
        $contador++;
        // Registrar en historial
        try {
            $hist = $conn->prepare("INSERT INTO HistorialTickets (ticket_id, usuario_id, accion, descripcion, fecha) 
                VALUES (?, NULL, 'activacion', ?, NOW())");
            $hist->execute([$t['id'], "Ticket programado activado. Fecha del evento: " . $t['fecha_evento']]);
        } catch (Exception $e) {}
    }
}

$log = date('Y-m-d H:i:s') . " - Tickets activados: $contador\n";
file_put_contents(__DIR__ . '/activar_tickets.log', $log, FILE_APPEND);
