<?php
// Script para corregir tickets con estado inconsistente
// Si un ticket tiene técnico asignado, no debería estar en estado "Nuevo"

require_once 'config/database.php';

echo "=== Corrección de Estado de Tickets ===\n\n";

// Contar tickets inconsistentes
$sql_check = "SELECT COUNT(*) as total FROM Tickets 
              WHERE estado = 'Nuevo' AND oati_asignado IS NOT NULL";
$stmt = $conn->query($sql_check);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$inconsistentes = $result['total'];

echo "Tickets encontrados con estado 'Nuevo' pero con técnico asignado: $inconsistentes\n\n";

if ($inconsistentes > 0) {
    // Mostrar los tickets afectados
    $sql_listar = "SELECT t.id, t.numero_ticket, t.asunto, t.estado, u.nombre as tecnico 
                   FROM Tickets t 
                   LEFT JOIN Usuarios u ON t.oati_asignado = u.id
                   WHERE t.estado = 'Nuevo' AND t.oati_asignado IS NOT NULL
                   ORDER BY t.fecha_creacion DESC
                   LIMIT 20";
    $stmt_listar = $conn->query($sql_listar);
    $tickets = $stmt_listar->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Primeros 20 tickets afectados:\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($tickets as $t) {
        echo "ID: {$t['id']} | Ticket: {$t['numero_ticket']} | Técnico: {$t['tecnico']}\n";
    }
    echo str_repeat("-", 80) . "\n\n";
    
    // Corregir los tickets
    $sql_update = "UPDATE Tickets 
                   SET estado = 'Asignado' 
                   WHERE estado = 'Nuevo' AND oati_asignado IS NOT NULL";
    $stmt_update = $conn->query($sql_update);
    $afectados = $stmt_update->rowCount();
    
    echo "RESULTADO: Se corrigieron $afectados ticket(s).\n";
    echo "El estado 'Nuevo' ahora solo aplica a tickets sin técnico asignado.\n";
} else {
    echo "No se encontraron inconsistencias. Todo está correcto.\n";
}

echo "\n=== Fin del script ===\n";
?>
