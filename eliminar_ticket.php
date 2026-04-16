<?php
// eliminar_ticket.php - Eliminar ticket (solo admin)
session_start();

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'] ?? '';

if (!$id_usuario || $privilegio != 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';

$ticket_id = intval($_GET['id'] ?? 0);

if ($ticket_id <= 0) {
    header('Location: todos_tickets.php?error=ticket_no_valido');
    exit();
}

try {
    // Verificar que el ticket existe
    $stmt_check = $conn->prepare("SELECT id, numero_ticket FROM Tickets WHERE id = ?");
    $stmt_check->execute([$ticket_id]);
    $ticket = $stmt_check->fetch();
    
    if (!$ticket) {
        header('Location: todos_tickets.php?error=ticket_no_encontrado');
        exit();
    }
    
    // Eliminar archivos adjuntos físicos
    $stmt_adjuntos = $conn->prepare("SELECT ruta_archivo FROM TicketAdjuntos WHERE ticket_id = ?");
    $stmt_adjuntos->execute([$ticket_id]);
    $adjuntos = $stmt_adjuntos->fetchAll();
    
    foreach ($adjuntos as $adjunto) {
        $ruta_completa = '/opt/lampp/htdocs/sistema_csi/adjuntos/' . $adjunto['ruta_archivo'];
        if (file_exists($ruta_completa)) {
            unlink($ruta_completa);
        }
    }
    
    // Eliminar registros de adjuntos
    $stmt_del_adj = $conn->prepare("DELETE FROM TicketAdjuntos WHERE ticket_id = ?");
    $stmt_del_adj->execute([$ticket_id]);
    
    // Eliminar historial
    $stmt_del_hist = $conn->prepare("DELETE FROM HistorialTickets WHERE ticket_id = ?");
    $stmt_del_hist->execute([$ticket_id]);
    
    // Eliminar el ticket
    $stmt_del_ticket = $conn->prepare("DELETE FROM Tickets WHERE id = ?");
    $stmt_del_ticket->execute([$ticket_id]);
    
    header('Location: todos_tickets.php?mensaje=ticket_eliminado&numero=' . urlencode($ticket['numero_ticket']));
    exit();
    
} catch (PDOException $e) {
    error_log("Error eliminando ticket: " . $e->getMessage());
    header('Location: todos_tickets.php?error=error_al_eliminar');
    exit();
}
?>
