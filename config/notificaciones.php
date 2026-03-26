<?php
require_once 'database.php';
if (file_exists(__DIR__ . '/mailer.php')) {
    require_once __DIR__ . '/mailer.php'; // Opcional
}

class Notificaciones {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function notificarAsignacion($ticket_id, $tecnico_id) {
        // Obtener info del ticket
        $query = "SELECT t.*, u.nombre AS usuario_nombre, u.correo AS usuario_email 
                  FROM Tickets t
                  INNER JOIN Usuarios u ON t.usuario_id = u.id
                  WHERE t.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener info del técnico
        $query_tec = "SELECT nombre, correo FROM Usuarios WHERE id = ?";
        $stmt_tec = $this->conn->prepare($query_tec);
        $stmt_tec->execute([$tecnico_id]);
        $tecnico = $stmt_tec->fetch(PDO::FETCH_ASSOC);
        
        // 1. Notificar al técnico
        $this->enviarEmail(
            $tecnico['correo'] ?? '',
            'Nuevo ticket asignado',
            "Se te ha asignado el ticket #{$ticket_id}: {$ticket['asunto']}"
        );
        
        // 2. Notificar al usuario
        $this->enviarEmail(
            $ticket['usuario_email'],
            'Tu ticket ha sido asignado',
            "Tu ticket #{$ticket_id} ha sido asignado al técnico: {$tecnico['nombre']}"
        );
        
        // 3. Registrar en historial
        $this->registrarNotificacionBD($ticket_id, 'asignacion', $tecnico_id);
    }
    
    private function enviarEmail($destinatario, $asunto, $mensaje) {
        // Implementación básica (usar mail() o PHPMailer)
        // Por ahora solo registrar
        error_log("EMAIL: Para $destinatario - $asunto: $mensaje");
        
        // Descomentar cuando tengan PHPMailer configurado:
        /*
        $mail = new PHPMailer();
        $mail->setFrom('soporte@csi.com', 'Sistema CSI');
        $mail->addAddress($destinatario);
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;
        $mail->send();
        */
    }
    
    private function registrarNotificacionBD($ticket_id, $tipo, $usuario_relacionado = null) {
        $query = "INSERT INTO HistorialTickets 
                  (ticket_id, usuario_id, accion, detalles, fecha_hora)
                  VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        
        $detalles = "Notificación de $tipo enviada";
        if ($usuario_relacionado) {
            $detalles .= " al usuario ID: $usuario_relacionado";
        }
        
        $usuario_sistema = 0; // ID del sistema
        $stmt->execute([$ticket_id, $usuario_sistema, $tipo, $detalles]);
    }
}
?>
