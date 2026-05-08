<?php
session_start();

// Verificar autenticación y privilegios
if (!isset($_SESSION['usuario_id']) || $_SESSION['privilegio'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'sistema_tickets');
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Obtener parámetros de filtro
$filtros = [
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'area_id' => $_GET['area_id'] ?? '',
    'tecnico_id' => $_GET['tecnico_id'] ?? '',
    'tipo_reporte' => $_GET['tipo_reporte'] ?? 'general'
];

// Construir condiciones WHERE
$where_conditions = [];
$params = [];
$types = "";

if (!empty($filtros['fecha_desde'])) {
    $where_conditions[] = "t.fecha_creacion >= ?";
    $params[] = $filtros['fecha_desde'] . " 00:00:00";
    $types .= "s";
}

if (!empty($filtros['fecha_hasta'])) {
    $where_conditions[] = "t.fecha_creacion <= ?";
    $params[] = $filtros['fecha_hasta'] . " 23:59:59";
    $types .= "s";
}

if (!empty($filtros['area_id'])) {
    $where_conditions[] = "t.area_id = ?";
    $params[] = $filtros['area_id'];
    $types .= "i";
}

if (!empty($filtros['tecnico_id'])) {
    $where_conditions[] = "t.tecnico_asignado = ?";
    $params[] = $filtros['tecnico_id'];
    $types .= "i";
}

$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Determinar consulta según tipo de reporte
if ($filtros['tipo_reporte'] == 'por_tecnico') {
    $sql = "SELECT 
        tec.nombre as 'Técnico',
        COUNT(t.id) as 'Total Tickets',
        SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) as 'Resueltos',
        SUM(CASE WHEN t.estado != 'cerrado' THEN 1 ELSE 0 END) as 'Pendientes',
        ROUND(AVG(CASE WHEN t.estado = 'cerrado' THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) ELSE NULL END), 1) as 'Tiempo Promedio (h)',
        MAX(TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW())) as 'Máx. Espera (h)',
        CONCAT(ROUND((SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100, 1), '%') as 'Eficiencia'
    FROM Tickets t
    LEFT JOIN Usuarios tec ON t.tecnico_asignado = tec.id
    $where_sql
    GROUP BY t.tecnico_asignado, tec.nombre
    ORDER BY COUNT(t.id) DESC";
    
    $filename = "reporte_tecnicos_" . date('Y-m-d_H-i') . ".xls";
    
} elseif ($filtros['tipo_reporte'] == 'por_area') {
    $sql = "SELECT 
        a.nombre as 'Área',
        COUNT(t.id) as 'Total Tickets',
        SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) as 'Resueltos',
        SUM(CASE WHEN t.estado != 'cerrado' THEN 1 ELSE 0 END) as 'Pendientes',
        ROUND(AVG(CASE WHEN t.estado = 'cerrado' THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) ELSE NULL END), 1) as 'Tiempo Promedio (h)',
        CONCAT(ROUND((SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100, 1), '%') as '% Resolución'
    FROM Tickets t
    LEFT JOIN AreasSoporte a ON t.area_id = a.id
    $where_sql
    GROUP BY t.area_id, a.nombre
    ORDER BY COUNT(t.id) DESC";
    
    $filename = "reporte_areas_" . date('Y-m-d_H-i') . ".xls";
    
} else {
    // Reporte general
    $sql = "SELECT 
        t.numero_ticket as 'Ticket #',
        t.asunto as 'Asunto',
        a.nombre as 'Área',
        UPPER(t.prioridad) as 'Prioridad',
        UPPER(REPLACE(t.estado, '_', ' ')) as 'Estado',
        tec.nombre as 'Técnico Asignado',
        u.nombre as 'Usuario Solicitante',
        DATE_FORMAT(t.fecha_creacion, '%d/%m/%Y %H:%i') as 'Fecha Creación',
        CASE 
            WHEN t.fecha_cierre IS NOT NULL THEN DATE_FORMAT(t.fecha_cierre, '%d/%m/%Y %H:%i')
            ELSE 'Pendiente'
        END as 'Fecha Cierre',
        TIMESTAMPDIFF(HOUR, t.fecha_creacion, COALESCE(t.fecha_cierre, NOW())) as 'Tiempo (h)',
        CASE 
            WHEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, COALESCE(t.fecha_cierre, NOW())) > 48 THEN 'CRÍTICO'
            WHEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, COALESCE(t.fecha_cierre, NOW())) > 24 THEN 'ALTO'
            ELSE 'NORMAL'
        END as 'Nivel Tiempo'
    FROM Tickets t
    LEFT JOIN AreasSoporte a ON t.area_id = a.id
    LEFT JOIN Usuarios u ON t.usuario_id = u.id
    LEFT JOIN Usuarios tec ON t.tecnico_asignado = tec.id
    $where_sql
    ORDER BY t.fecha_creacion DESC";
    
    $filename = "reporte_general_" . date('Y-m-d_H-i') . ".xls";
}

// Preparar y ejecutar consulta
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Preparar datos para Excel
$data = [];
$headers = [];

if ($result->num_rows > 0) {
    // Obtener nombres de columnas
    $field_info = $result->fetch_fields();
    foreach ($field_info as $field) {
        $headers[] = $field->name;
    }
    
    // Obtener datos
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Configurar headers para Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Generar contenido Excel
echo "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">";
echo "<head>";
echo "<meta charset=\"UTF-8\">";
echo "<!--[if gte mso 9]>";
echo "<xml>";
echo "<x:ExcelWorkbook>";
echo "<x:ExcelWorksheets>";
echo "<x:ExcelWorksheet>";
echo "<x:Name>Reporte</x:Name>";
echo "<x:WorksheetOptions>";
echo "<x:DisplayGridlines/>";
echo "</x:WorksheetOptions>";
echo "</x:ExcelWorksheet>";
echo "</x:ExcelWorksheets>";
echo "</x:ExcelWorkbook>";
echo "</xml>";
echo "<![endif]-->";
echo "<style>";
echo "td { mso-number-format:\\@; }";
echo ".text { mso-number-format:\\@; }";
echo ".number { mso-number-format: \"#,##0.00\"; }";
echo "</style>";
echo "</head>";
echo "<body>";

// Encabezado del reporte
echo "<table border='1' width='100%'>";
echo "<tr>";
echo "<td colspan='" . count($headers) . "' align='center' style='background-color:#1a2980; color:white; font-size:16px; font-weight:bold; padding:10px;'>";
echo "SISTEMA CSI - REPORTE DE TICKETS";
echo "</td>";
echo "</tr>";

// Información del reporte
echo "<tr>";
echo "<td colspan='" . count($headers) . "' style='padding:8px; background-color:#f2f2f2;'>";
echo "<strong>Tipo de Reporte:</strong> " . strtoupper(str_replace('_', ' ', $filtros['tipo_reporte'])) . "<br>";
echo "<strong>Fecha Generación:</strong> " . date('d/m/Y H:i:s') . "<br>";

if (!empty($filtros['fecha_desde']) || !empty($filtros['fecha_hasta'])) {
    echo "<strong>Período:</strong> ";
    if (!empty($filtros['fecha_desde'])) {
        echo "Desde: " . date('d/m/Y', strtotime($filtros['fecha_desde']));
    }
    if (!empty($filtros['fecha_hasta'])) {
        echo " Hasta: " . date('d/m/Y', strtotime($filtros['fecha_hasta']));
    }
    echo "<br>";
}

if (!empty($filtros['area_id'])) {
    $area_nombre = $conn->query("SELECT nombre FROM AreasSoporte WHERE id = " . intval($filtros['area_id']))->fetch_assoc()['nombre'] ?? '';
    if ($area_nombre) {
        echo "<strong>Área:</strong> " . $area_nombre . "<br>";
    }
}

if (!empty($filtros['tecnico_id'])) {
    $tecnico_nombre = $conn->query("SELECT nombre FROM Usuarios WHERE id = " . intval($filtros['tecnico_id']))->fetch_assoc()['nombre'] ?? '';
    if ($tecnico_nombre) {
        echo "<strong>Técnico:</strong> " . $tecnico_nombre . "<br>";
    }
}

echo "<strong>Total Registros:</strong> " . count($data);
echo "</td>";
echo "</tr>";

// Espacio
echo "<tr><td colspan='" . count($headers) . "' height='10'></td></tr>";

// Encabezados de columnas
echo "<tr>";
foreach ($headers as $header) {
    echo "<th style='background-color:#3498db; color:white; padding:8px; text-align:center; font-weight:bold;'>" . $header . "</th>";
}
echo "</tr>";

// Datos
if (count($data) > 0) {
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            // Detectar si es número para formato adecuado
            if (is_numeric($cell) && !in_array($cell, array_keys($row))) {
                echo "<td class='number' style='padding:6px;'>" . $cell . "</td>";
            } else {
                echo "<td class='text' style='padding:6px;'>" . htmlspecialchars($cell) . "</td>";
            }
        }
        echo "</tr>";
    }
} else {
    echo "<tr>";
    echo "<td colspan='" . count($headers) . "' align='center' style='padding:20px;'>No hay datos para mostrar</td>";
    echo "</tr>";
}

echo "</table>";
echo "</body></html>";

$conn->close();
exit();
