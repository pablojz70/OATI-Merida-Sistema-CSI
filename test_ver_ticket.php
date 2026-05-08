<?php
session_start();
$_SESSION['id_usuario'] = 1;
$_SESSION['privilegio'] = 'admin';
$_SESSION['nombre'] = 'Admin Test';

require_once 'config/database.php';

try {
    $ticket_id = 28; // Use the actual ticket ID from the database
    $sql = "SELECT t.*, 
                   a.nombre as area_nombre, 
                   s.nombre as servicio_nombre,
                   d.nombre as dependencia_nombre,
                   d.nombre_corto as dependencia_corto,
                   u.nombre as usuario_nombre,
                   u.dependencia_id as usuario_dependencia_id,
                   du.nombre as usuario_dependencia_nombre,
                   du.nombre_corto as usuario_dependencia_corto,
                   tech.nombre as oati_nombre
            FROM Tickets t
            JOIN AreasSoporte a ON t.area_id = a.id
            JOIN Servicios s ON t.servicio_id = s.id
            JOIN Dependencias d ON t.dependencia_id = d.id
            JOIN Usuarios u ON t.usuario_id = u.id
            LEFT JOIN Dependencias du ON u.dependencia_id = du.id
            LEFT JOIN Usuarios tech ON t.oati_asignado = tech.id
            WHERE t.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if ($ticket) {
        echo "Ticket found successfully!<br>";
        echo "Ticket ID: " . $ticket['id'] . "<br>";
        echo "Ticket Number: " . $ticket['numero_ticket'] . "<br>";
        echo "OATI Assigned: " . ($ticket['oati_asignado'] ?? 'None') . "<br>";
        echo "OATI Name: " . ($ticket['oati_nombre'] ?? 'None') . "<br>";
        echo "OATI Assigned ID: " . ($ticket['oati_asignado'] ?? 'None') . "<br>";
    } else {
        echo "No ticket found with ID: " . $ticket_id . "<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>