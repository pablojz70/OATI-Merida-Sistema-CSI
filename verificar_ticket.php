<?php
// verificar_tickets.php - VERSIÓN CORREGIDA PDO
require_once 'config/database.php';

echo "<h1>Verificación de Tickets en Base de Datos</h1>";

// 1. Total de tickets
$sql_total = "SELECT COUNT(*) as total FROM tickets";
$result = $conn->query($sql_total);
$total = $result->fetch(PDO::FETCH_ASSOC)['total'];
echo "<p><strong>Total tickets en sistema:</strong> $total</p>";

// 2. Tickets por estado
$sql_estados = "SELECT estado, COUNT(*) as cantidad FROM tickets GROUP BY estado";
$result = $conn->query($sql_estados);
echo "<h3>Tickets por estado:</h3>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>Estado</th><th>Cantidad</th></tr>";
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr><td>{$row['estado']}</td><td>{$row['cantidad']}</td></tr>";
}
echo "</table>";

// 3. Tickets nuevos sin asignar
$sql_nuevos = "SELECT 
    t.id, 
    t.numero_ticket, 
    t.asunto, 
    t.estado,
    t.tecnico_asignado,
    u.nombre as usuario_nombre
    FROM tickets t
    LEFT JOIN usuarios u ON t.usuario_id = u.id
    WHERE t.estado = 'Nuevo' 
    AND t.tecnico_asignado IS NULL";

echo "<h3>Tickets NUEVOS sin asignar:</h3>";
$result = $conn->query($sql_nuevos);

if ($result && $result->rowCount() > 0) {
    echo "<p><strong>Encontrados:</strong> {$result->rowCount()} tickets</p>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Número</th><th>Asunto</th><th>Estado</th><th>Técnico</th><th>Solicitante</th></tr>";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['numero_ticket']}</td>
                <td>{$row['asunto']}</td>
                <td>{$row['estado']}</td>
                <td>" . ($row['tecnico_asignado'] ? 'ASIGNADO' : 'NULL') . "</td>
                <td>{$row['usuario_nombre']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'><strong>❌ NO se encontraron tickets nuevos sin asignar</strong></p>";
    
    $sql_otros = "SELECT 
        t.id, 
        t.numero_ticket, 
        t.asunto, 
        t.estado,
        t.tecnico_asignado
        FROM tickets t
        WHERE t.tecnico_asignado IS NULL
        ORDER BY t.estado";
    
    $result2 = $conn->query($sql_otros);
    if ($result2 && $result2->rowCount() > 0) {
        echo "<h4>Tickets sin asignar (otros estados):</h4>";
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Número</th><th>Asunto</th><th>Estado</th></tr>";
        
        while ($row = $result2->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['numero_ticket']}</td>
                    <td>{$row['asunto']}</td>
                    <td>{$row['estado']}</td>
                  </tr>";
        }
        echo "</table>";
    }
}

// 4. Verificar estructura de la tabla tickets
echo "<h3>Estructura de tabla tickets:</h3>";
$sql_structure = "DESCRIBE tickets";
$result = $conn->query($sql_structure);
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>
            <td>{$row['Field']}</td>
            <td>{$row['Type']}</td>
            <td>{$row['Null']}</td>
            <td>{$row['Default']}</td>
          </tr>";
}
echo "</table>";

// 5. Crear un ticket de prueba si no hay
if ($total == 0) {
    echo "<hr><h3>Crear ticket de prueba:</h3>";
    echo '<form method="POST">
        <button type="submit" name="crear_prueba">Crear Ticket de Prueba</button>
        </form>';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_prueba'])) {
        $stmt_usuario = $conn->prepare("SELECT id FROM usuarios LIMIT 1");
        $stmt_usuario->execute();
        $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
        $usuario_id = $usuario['id'] ?? 1;
        
        $stmt_area = $conn->prepare("SELECT id FROM areassoporte LIMIT 1");
        $stmt_area->execute();
        $area = $stmt_area->fetch(PDO::FETCH_ASSOC);
        $area_id = $area['id'] ?? 1;
        
        $stmt_servicio = $conn->prepare("SELECT id FROM servicios LIMIT 1");
        $stmt_servicio->execute();
        $servicio = $stmt_servicio->fetch(PDO::FETCH_ASSOC);
        $servicio_id = $servicio['id'] ?? 1;
        
        $stmt_dep = $conn->prepare("SELECT id FROM dependencias LIMIT 1");
        $stmt_dep->execute();
        $dependencia = $stmt_dep->fetch(PDO::FETCH_ASSOC);
        $dependencia_id = $dependencia['id'] ?? 1;
        
        $numero_ticket = "TICK-" . date('Ymd-His');
        $asunto = "Ticket de prueba automático";
        $descripcion = "Este es un ticket creado automáticamente para pruebas del sistema.";
        
        $sql_insert = "INSERT INTO tickets 
            (numero_ticket, usuario_id, dependencia_id, area_id, servicio_id, asunto, descripcion, prioridad, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'media', 'Nuevo')";
        
        $stmt = $conn->prepare($sql_insert);
        $stmt->execute([$numero_ticket, $usuario_id, $dependencia_id, $area_id, $servicio_id, $asunto, $descripcion]);
        
        echo "<p style='color:green'>✅ Ticket de prueba creado exitosamente!</p>";
        echo "<meta http-equiv='refresh' content='2'>";
    }
}
?>
