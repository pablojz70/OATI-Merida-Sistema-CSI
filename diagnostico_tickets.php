<?php
// diagnostico_tickets.php - VERSIÓN CORREGIDA PDO
echo "<h1>Diagnóstico del Sistema de Tickets</h1>";

require_once 'config/database.php';

// 1. Verificar todas las tablas existentes
echo "<h3>Tablas en la base de datos:</h3>";
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
    echo "<p>• " . $row[0] . "</p>";
}

// 2. Verificar si existen las tablas necesarias (en minúsculas)
$tablas_necesarias = ['tickets', 'usuarios', 'dependencias', 'areassoporte', 'servicios'];
echo "<h3>Verificando tablas necesarias:</h3>";

foreach ($tablas_necesarias as $tabla) {
    if (in_array($tabla, $tables)) {
        echo "<p style='color:green'>✅ Tabla '$tabla' existe</p>";
    } else {
        $encontrada = false;
        foreach ($tables as $tabla_real) {
            if (strtolower($tabla_real) == $tabla) {
                echo "<p style='color:orange'>⚠️ Tabla existe como '$tabla_real' (buscando '$tabla')</p>";
                $encontrada = true;
                break;
            }
        }
        if (!$encontrada) {
            echo "<p style='color:red'>❌ Tabla '$tabla' NO existe</p>";
        }
    }
}

// 3. Verificar contenido de tickets
echo "<h3>Contenido de tabla tickets:</h3>";

$nombre_tabla_tickets = 'tickets';
foreach ($tables as $tabla) {
    if (strtolower($tabla) == 'tickets') {
        $nombre_tabla_tickets = $tabla;
        break;
    }
}

echo "<p>Usando nombre de tabla: <strong>$nombre_tabla_tickets</strong></p>";

$sql = "SELECT COUNT(*) as total FROM `$nombre_tabla_tickets`";
$result = $conn->query($sql);

if ($result) {
    $total = $result->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Total tickets: <strong>$total</strong></p>";
    
    $sql_estados = "SELECT estado, COUNT(*) as cantidad FROM `$nombre_tabla_tickets` GROUP BY estado ORDER BY cantidad DESC";
    $result_estados = $conn->query($sql_estados);
    
    if ($result_estados && $result_estados->rowCount() > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>
                <tr><th>Estado</th><th>Cantidad</th></tr>";
        
        while ($row = $result_estados->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['estado']}</td><td>{$row['cantidad']}</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Tickets para 'aceptar_ticket.php':</h3>";
    $sql_nuevos = "SELECT 
        id, 
        numero_ticket, 
        asunto, 
        estado,
        tecnico_asignado,
        fecha_creacion
        FROM `$nombre_tabla_tickets`
        WHERE estado = 'Nuevo' 
        AND tecnico_asignado IS NULL
        ORDER BY fecha_creacion DESC";
    
    $result_nuevos = $conn->query($sql_nuevos);
    
    if ($result_nuevos && $result_nuevos->rowCount() > 0) {
        $count = $result_nuevos->rowCount();
        echo "<p style='color:green'>✅ Encontrados: {$count} tickets nuevos sin asignar</p>";
        echo "<table border='1' cellpadding='8'>
                <tr><th>ID</th><th>Número</th><th>Asunto</th><th>Estado</th><th>Técnico</th><th>Fecha</th></tr>";
        
        while ($row = $result_nuevos->fetch(PDO::FETCH_ASSOC)) {
            $fecha = date('d/m/Y H:i', strtotime($row['fecha_creacion']));
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['numero_ticket']}</td>
                    <td>" . htmlspecialchars($row['asunto']) . "</td>
                    <td>{$row['estado']}</td>
                    <td>" . ($row['tecnico_asignado'] ? 'ASIGNADO' : '<strong style="color:green">DISPONIBLE</strong>') . "</td>
                    <td>$fecha</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠️ NO se encontraron tickets con estado 'Nuevo' y sin técnico asignado</p>";
        
        echo '<form method="POST" style="margin: 20px 0;">
                <button type="submit" name="crear_prueba" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    🎫 Crear Ticket de Prueba
                </button>
              </form>';
    }
} else {
    echo "<p style='color:red'>❌ Error al consultar tickets</p>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_prueba'])) {
    $stmt_usuario = $conn->prepare("SELECT id FROM usuarios WHERE privilegio = 'usuario' LIMIT 1");
    $stmt_usuario->execute();
    $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
    
    $stmt_area = $conn->prepare("SELECT id FROM areassoporte LIMIT 1");
    $stmt_area->execute();
    $area = $stmt_area->fetch(PDO::FETCH_ASSOC);
    
    $stmt_servicio = $conn->prepare("SELECT id FROM servicios LIMIT 1");
    $stmt_servicio->execute();
    $servicio = $stmt_servicio->fetch(PDO::FETCH_ASSOC);
    
    $stmt_dep = $conn->prepare("SELECT id FROM dependencias LIMIT 1");
    $stmt_dep->execute();
    $dependencia = $stmt_dep->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario && $area && $servicio && $dependencia) {
        $numero_ticket = "TEST-" . date('Ymd-His');
        $asunto = "Ticket de prueba automático";
        $descripcion = "Ticket creado automáticamente para probar el sistema.";
        
        $sql_insert = "INSERT INTO `$nombre_tabla_tickets` 
            (numero_ticket, usuario_id, dependencia_id, area_id, servicio_id, asunto, descripcion, prioridad, estado, fecha_creacion)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'media', 'Nuevo', NOW())";
        
        $stmt = $conn->prepare($sql_insert);
        $stmt->execute([$numero_ticket, $usuario['id'], $dependencia['id'], $area['id'], $servicio['id'], $asunto, $descripcion]);
        
        echo "<p style='color:green'>✅ Ticket de prueba creado! ID: {$conn->lastInsertId()}</p>";
        echo "<meta http-equiv='refresh' content='2'>";
    }
}

echo "<hr>";
echo "<h3>Próximos pasos:</h3>";
echo "<ol>
        <li><a href='aceptar_ticket.php' target='_blank'>1. Probar aceptar_ticket.php</a></li>
        <li><a href='crear_ticket.php' target='_blank'>2. Crear ticket manualmente</a></li>
        <li><a href='tickets_asignados.php' target='_blank'>3. Ver tickets asignados</a></li>
      </ol>";
?>
