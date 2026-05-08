<?php
// ajax/logs.php
require_once '../config/session.php';
require_once '../config/database.php';
header('Content-Type: application/json');

// Verificar que el usuario sea administrador
if (($_SESSION['privilegio'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

global $conn;

// Obtener la acción a realizar
$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

// Inicializar respuesta
$respuesta = ['success' => false, 'message' => ''];

try {
    switch ($accion) {
        case 'detalles':
            obtenerDetallesLog();
            break;
            
        case 'limpiar':
            limpiarLogsAntiguos();
            break;
            
        case 'estadisticas':
            obtenerEstadisticasLogs();
            break;
            
        case 'exportar':
            exportarLogs();
            break;
            
        default:
            $respuesta['message'] = 'Acción no reconocida';
            break;
    }
} catch (Exception $e) {
    error_log('ajax/logs.php: ' . $e->getMessage());
    $respuesta['message'] = 'Error interno del sistema';
}

// Devolver respuesta JSON
echo json_encode($respuesta);

// ===== FUNCIONES PARA LOGS =====

function obtenerDetallesLog() {
    global $conn, $respuesta;
    
    $log_id = intval($_GET['id'] ?? 0);
    if ($log_id <= 0) {
        $respuesta['message'] = 'ID de log no válido';
        return;
    }
    
    $query = "SELECT l.*, u.nombre as usuario_nombre, u.privilegio as usuario_privilegio 
              FROM logs_sistema l
              LEFT JOIN Usuarios u ON l.usuario_id = u.id
              WHERE l.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$log_id]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($log) {
        $respuesta['success'] = true;
        $respuesta['log'] = $log;
    } else {
        $respuesta['message'] = 'Log no encontrado';
    }
}

function limpiarLogsAntiguos() {
    global $conn, $respuesta;
    
    // Por defecto, eliminar logs con más de 90 días
    $dias = intval($_POST['dias'] ?? 90);
    $fecha_limite = date('Y-m-d H:i:s', strtotime("-$dias days"));
    
    // Primero contar cuántos se van a eliminar
    $query_count = "SELECT COUNT(*) as total FROM logs_sistema WHERE fecha < ?";
    $stmt = $conn->prepare($query_count);
    $stmt->execute([$fecha_limite]);
    $total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    
    // Eliminar logs antiguos
    $query_delete = "DELETE FROM logs_sistema WHERE fecha < ?";
    $stmt = $conn->prepare($query_delete);

    if ($stmt->execute([$fecha_limite])) {
        // Registrar esta acción en logs
        $usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? 0;
        $detalles = "Se eliminaron $total registros de logs con más de $dias días";
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt_log = $conn->prepare("INSERT INTO logs_sistema (usuario_id, accion, detalles, ip, user_agent) VALUES (?, 'limpiar_logs', ?, ?, ?)");
        $stmt_log->execute([$usuario_id, $detalles, $ip, $user_agent]);
        
        $respuesta['success'] = true;
        $respuesta['message'] = 'Logs antiguos eliminados exitosamente';
        $respuesta['eliminados'] = $total;
        $respuesta['fecha_limite'] = $fecha_limite;
    } else {
        $respuesta['message'] = 'Error al eliminar los logs';
    }
}

function obtenerEstadisticasLogs() {
    global $conn, $respuesta;
    
    $estadisticas = [];
    
    // Logs por día (últimos 7 días)
    $query_diarios = "SELECT 
        DATE(fecha) as fecha,
        COUNT(*) as total,
        SUM(CASE WHEN accion LIKE '%error%' THEN 1 ELSE 0 END) as errores,
        SUM(CASE WHEN accion LIKE '%login%' THEN 1 ELSE 0 END) as logins
        FROM logs_sistema 
        WHERE fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(fecha)
        ORDER BY fecha";
    
    $stmt = $conn->prepare($query_diarios);
    $stmt->execute();
    $estadisticas['diarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top 10 usuarios con más actividad
    $query_usuarios = "SELECT 
        u.nombre,
        COUNT(l.id) as total_logs,
        u.privilegio
        FROM logs_sistema l
        JOIN Usuarios u ON l.usuario_id = u.id
        WHERE l.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY l.usuario_id, u.nombre, u.privilegio
        ORDER BY total_logs DESC
        LIMIT 10";
    
    $stmt = $conn->prepare($query_usuarios);
    $stmt->execute();
    $estadisticas['top_usuarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Distribución por acción
    $query_acciones = "SELECT 
        accion,
        COUNT(*) as total
        FROM logs_sistema 
        WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY accion
        ORDER BY total DESC
        LIMIT 15";
    
    $stmt = $conn->prepare($query_acciones);
    $stmt->execute();
    $estadisticas['acciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $respuesta['success'] = true;
    $respuesta['estadisticas'] = $estadisticas;
}

function exportarLogs() {
    global $conn, $respuesta;
    
    $formato = $_GET['formato'] ?? 'csv';
    $filtros = obtenerFiltrosDesdeRequest();
    
    // Construir consulta con filtros
    $query = construirConsultaLogs($filtros);
    $stmt = $conn->prepare($query['sql']);
    $stmt->execute($query['params']);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    switch ($formato) {
        case 'csv':
            exportarLogsCSV($result);
            break;
        case 'pdf':
            exportarLogsPDF($result, $filtros);
            break;
        default:
            $respuesta['message'] = 'Formato no soportado';
            break;
    }
}

function construirConsultaLogs($filtros) {
    $sql = "SELECT
        l.id,
        l.fecha,
        u.nombre as usuario,
        u.privilegio,
        l.accion,
        l.detalles,
        l.ip,
        l.user_agent
        FROM logs_sistema l
        LEFT JOIN Usuarios u ON l.usuario_id = u.id
        WHERE 1=1";
    
    $params = [];
    
    if (!empty($filtros['usuario_id'])) {
        $sql .= " AND l.usuario_id = :usuario_id";
        $params[':usuario_id'] = (int)$filtros['usuario_id'];
    }
    
    if (!empty($filtros['accion'])) {
        $sql .= " AND l.accion = :accion";
        $params[':accion'] = $filtros['accion'];
    }
    
    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND DATE(l.fecha) >= :fecha_desde";
        $params[':fecha_desde'] = $filtros['fecha_desde'];
    }
    
    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND DATE(l.fecha) <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtros['fecha_hasta'];
    }
    
    if (!empty($filtros['busqueda'])) {
        $sql .= " AND (l.detalles LIKE :busqueda OR u.nombre LIKE :busqueda)";
        $busqueda = "%{$filtros['busqueda']}%";
        $params[':busqueda'] = $busqueda;
    }
    
    $sql .= " ORDER BY l.fecha DESC";
    
    return [
        'sql' => $sql,
        'params' => $params
    ];
}

function obtenerFiltrosDesdeRequest() {
    return [
        'usuario_id' => $_GET['usuario_id'] ?? '',
        'accion' => $_GET['accion'] ?? '',
        'fecha_desde' => $_GET['fecha_desde'] ?? '',
        'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
        'busqueda' => $_GET['busqueda'] ?? ''
    ];
}

function exportarLogsCSV($result) {
    // Crear contenido CSV
    $csv = "ID,Fecha,Usuario,Privilegio,Acción,Detalles,IP,User Agent\n";
    
    foreach ($result as $row) {
        $csv .= '"' . implode('","', [
            $row['id'],
            $row['fecha'],
            $row['usuario'] ?? 'Sistema',
            $row['privilegio'] ?? 'N/A',
            $row['accion'],
            str_replace('"', '""', $row['detalles']),
            $row['ip'],
            str_replace('"', '""', $row['user_agent'])
        ]) . "\"\n";
    }
    
    // Forzar descarga como CSV
    $filename = "logs_sistema_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $csv;
    exit();
}

function exportarLogsPDF($result, $filtros) {
    // En una implementación real, usarías una librería como TCPDF
    // Aquí un ejemplo básico en HTML que se puede convertir a PDF
    
    $html = "
    <html>
    <head>
        <title>Logs del Sistema - " . date('Y-m-d') . "</title>
        <style>
            body { font-family: Arial, sans-serif; }
            h1 { color: #3498db; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #3498db; color: white; padding: 10px; text-align: left; }
            td { border: 1px solid #ddd; padding: 8px; }
            .header { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Logs del Areas Operativas: Infraestructura - OATI</h1>
            <p><strong>Período:</strong> {$filtros['fecha_desde']} al {$filtros['fecha_hasta']}</p>
            <p><strong>Generado:</strong> " . date('d/m/Y H:i:s') . "</p>
        </div>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Detalles</th>
                <th>IP</th>
            </tr>
    ";
    
    foreach ($result as $row) {
        $html .= "
            <tr>
                <td>{$row['id']}</td>
                <td>" . date('d/m/Y H:i', strtotime($row['fecha'])) . "</td>
                <td>{$row['usuario']}</td>
                <td>{$row['accion']}</td>
                <td>{$row['detalles']}</td>
                <td>{$row['ip']}</td>
            </tr>
        ";
    }
    
    $html .= "
        </table>
        <p style='margin-top: 30px; color: #777; font-size: 12px;'>
            Sistema de Control de Soporte Informático - CSI<br>
            Total de registros: " . count($result) . "
        </p>
    </body>
    </html>
    ";
    
    // En producción, aquí generarías el PDF real
    // Por ahora devolvemos HTML que puede ser convertido
    echo $html;
    exit();
}
?>
