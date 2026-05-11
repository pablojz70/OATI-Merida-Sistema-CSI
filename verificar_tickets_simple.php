<?php
// verificar_tickets_simple.php - VERSIÓN CORREGIDA
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Verificar Tickets en Base de Datos</h1>";

try {
     $pdo = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ATTR_ERRMODE_EXCEPTION);
    
    echo "<p style='color:green'>✅ Conexión exitosa</p>";
    
    // 1. Total tickets nuevos sin asignar
    $sql = "SELECT COUNT(*) as total FROM Tickets WHERE estado = 'Nuevo' AND oati_asignado IS NULL";
    $stmt = $pdo->query($sql);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<h3>Tickets nuevos sin asignar: <strong>$total</strong></h3>";
    
    if ($total > 0) {
        // Mostrar tickets
        $sql_tickets = "SELECT 
            t.id, 
            t.numero_ticket, 
            t.asunto, 
            t.estado,
            t.prioridad,
            u.nombre as usuario_nombre,
            DATE_FORMAT(t.fecha_creacion, '%d/%m/%Y %H:%i') as fecha
            FROM Tickets t
            LEFT JOIN Usuarios u ON t.usuario_id = u.id
            WHERE t.estado = 'Nuevo' 
            AND t.oati_asignado IS NULL
            ORDER BY t.fecha_creacion DESC";
        
        $stmt = $pdo->query($sql_tickets);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 20px 0;'>
                <tr>
                    <th>ID</th>
                    <th>Ticket</th>
                    <th>Asunto</th>
                    <th>Prioridad</th>
                    <th>Solicitante</th>
                    <th>Fecha</th>
                </tr>";
        
        foreach ($tickets as $row) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['numero_ticket']}</td>
                    <td>" . htmlspecialchars($row['asunto']) . "</td>
                    <td>" . ucfirst($row['prioridad']) . "</td>
                    <td>" . htmlspecialchars($row['usuario_nombre']) . "</td>
                    <td>{$row['fecha']}</td>
                  </tr>";
        }
        echo "</table>";
        
        echo "<p><a href='aceptar_ticket.php' style='background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;'>
                <i class='fas fa-hand-paper'></i> Ir a Aceptar Tickets
              </a></p>";
    } else {
        echo "<p style='color:orange'>⚠️ No hay tickets nuevos sin asignar.</p>";
        
        // Mostrar todos los tickets para diagnóstico
        echo "<h4>Todos los tickets en el sistema:</h4>";
        $sql_all = "SELECT 
            id, 
            numero_ticket, 
            asunto, 
            estado,
            oati_asignado,
            prioridad,
            DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha
            FROM Tickets 
            ORDER BY fecha_creacion DESC 
            LIMIT 10";
        
        $stmt = $pdo->query($sql_all);
        $all_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($all_tickets) > 0) {
            echo "<table border='1' cellpadding='8'>
                    <tr>
                        <th>ID</th>
                        <th>Ticket</th>
                        <th>Asunto</th>
                        <th>Estado</th>
                        <th>Técnico</th>
                        <th>Prioridad</th>
                        <th>Fecha</th>
                    </tr>";
            
            foreach ($all_tickets as $row) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['numero_ticket']}</td>
                        <td>" . htmlspecialchars($row['asunto']) . "</td>
                        <td>{$row['estado']}</td>
                        <td>" . ($row['oati_asignado'] ? 'Asignado' : 'Libre') . "</td>
                        <td>{$row['prioridad']}</td>
                        <td>{$row['fecha']}</td>
                      </tr>";
            }
            echo "</table>";
        }
        
        // Formulario para crear ticket de prueba
        echo '<form method="POST" style="margin: 20px 0;">
                <button type="submit" name="crear_ticket" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-plus-circle"></i> Crear Ticket de Prueba
                </button>
              </form>';
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_ticket'])) {
            try {
                // Crear ticket de prueba
                $sql_insert = "INSERT INTO Tickets 
                    (numero_ticket, usuario_id, dependencia_id, area_id, servicio_id, asunto, descripcion, prioridad, estado, fecha_creacion)
                    SELECT 
                        CONCAT('TEST-', DATE_FORMAT(NOW(), '%Y%m%d-%H%i%s')),
                        (SELECT id FROM Usuarios WHERE privilegio = 'usuario' LIMIT 1),
                        (SELECT id FROM Dependencias LIMIT 1),
                        (SELECT id FROM AreasSoporte LIMIT 1),
                        (SELECT id FROM Servicios LIMIT 1),
                        'Solicitud de prueba automática',
                        'Este ticket fue creado automáticamente para probar el sistema de aceptación de tickets.',
                        'media',
                        'Nuevo',
                        NOW()";
                
                $pdo->exec($sql_insert);
                $ticket_id = $pdo->lastInsertId();
                
                echo "<p style='color:green'>✅ Ticket creado exitosamente! ID: $ticket_id</p>";
                echo "<meta http-equiv='refresh' content='2'>";
            } catch (PDOException $e) {
                echo "<p style='color:red'>❌ Error al crear ticket: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}
?>
