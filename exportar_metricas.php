<?php
session_start();

// Verificar autenticación y privilegios
if (!isset($_SESSION['usuario_id']) || $_SESSION['privilegio'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'sistema_csi');
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Obtener parámetros
$periodo = $_GET['periodo'] ?? '30dias';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Determinar rango
switch ($periodo) {
    case '7dias':
        $rango = "Últimos 7 días";
        break;
    case '15dias':
        $rango = "Últimos 15 días";
        break;
    case 'mes':
        $rango = "Mes actual";
        break;
    case 'trimestre':
        $rango = "Último trimestre";
        break;
    default:
        $rango = "Últimos 30 días";
        break;
}

// Obtener métricas de técnicos
$metricas_tecnicos = $conn->query("
    SELECT 
        u.nombre as 'Técnico',
        COUNT(t.id) as 'Total Tickets',
        SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) as 'Resueltos',
        SUM(CASE WHEN t.estado = 'en_proceso' THEN 1 ELSE 0 END) as 'En Proceso',
        SUM(CASE WHEN t.estado = 'asignado' THEN 1 ELSE 0 END) as 'Asignados',
        SUM(CASE WHEN t.prioridad = 'alta' THEN 1 ELSE 0 END) as 'Alta Prioridad',
        SUM(CASE WHEN t.prioridad = 'media' THEN 1 ELSE 0 END) as 'Media Prioridad',
        SUM(CASE WHEN t.prioridad = 'baja' THEN 1 ELSE 0 END) as 'Baja Prioridad',
        ROUND(AVG(CASE WHEN t.estado = 'cerrado' THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) ELSE NULL END), 1) as 'Tiempo Promedio (h)',
        MAX(CASE WHEN t.estado != 'cerrado' THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) ELSE 0 END) as 'Máx. Espera (h)',
        ROUND((SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) / COUNT(t.id) * 100), 1) as '% Cierre',
        CASE 
            WHEN (SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) / COUNT(t.id) * 100) >= 90 THEN 'EXCELENTE'
            WHEN (SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) / COUNT(t.id) * 100) >= 75 THEN 'BUENO'
            WHEN (SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) / COUNT(t.id) * 100) >= 50 THEN 'REGULAR'
            ELSE 'DEFICIENTE'
        END as 'Desempeño'
    FROM Tickets t
    INNER JOIN Usuarios u ON t.oati_asignado = u.id
    WHERE t.fecha_creacion >= '$fecha_inicio 00:00:00'
        AND t.fecha_creacion <= '$fecha_fin 23:59:59'
        AND u.privilegio IN ('oati', 'infraestructura')
        AND u.activo = 1
    GROUP BY u.id, u.nombre
    ORDER BY COUNT(t.id) DESC
");

// Métricas generales
$metricas_generales = $conn->query("
    SELECT 
        COUNT(*) as 'Total Tickets',
        SUM(CASE WHEN estado = 'cerrado' THEN 1 ELSE 0 END) as 'Tickets Resueltos',
        ROUND((SUM(CASE WHEN estado = 'cerrado' THEN 1 ELSE 0 END) / COUNT(*) * 100), 1) as '% Resolución',
        ROUND(AVG(CASE WHEN estado = 'cerrado' THEN TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_cierre) ELSE NULL END), 1) as 'Tiempo Promedio (h)',
        SUM(CASE WHEN prioridad = 'alta' THEN 1 ELSE 0 END) as 'Tickets Alta',
        SUM(CASE WHEN prioridad = 'media' THEN 1 ELSE 0 END) as 'Tickets Media',
        SUM(CASE WHEN prioridad = 'baja' THEN 1 ELSE 0 END) as 'Tickets Baja'
    FROM Tickets
    WHERE fecha_creacion >= '$fecha_inicio 00:00:00'
        AND fecha_creacion <= '$fecha_fin 23:59:59'
")->fetch_assoc();

// Configurar headers para Excel
$filename = "metricas_desempeno_" . date('Y-m-d_H-i') . ".xls";
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
echo "<x:Name>Métricas</x:Name>";
echo "<x:WorksheetOptions>";
echo "<x:DisplayGridlines/>";
echo "</x:WorksheetOptions>";
echo "</x:ExcelWorksheet>";
echo "</x:ExcelWorksheets>";
echo "</x:ExcelWorkbook>";
echo "</xml>";
echo "<![endif]-->";
echo "</head>";
echo "<body>";

echo "<table border='1' width='100%'>";
echo "<tr>";
echo "<td colspan='12' align='center' style='background-color:#1a2980; color:white; font-size:16px; font-weight:bold; padding:10px;'>";
echo "SISTEMA CSI - MÉTRICAS DE DESEMPEÑO";
echo "</td>";
echo "</tr>";

// Información del reporte
echo "<tr>";
echo "<td colspan='12' style='padding:8px; background-color:#f2f2f2;'>";
echo "<strong>Período de Análisis:</strong> " . $rango . "<br>";
echo "<strong>Fechas:</strong> " . date('d/m/Y', strtotime($fecha_inicio)) . " - " . date('d/m/Y', strtotime($fecha_fin)) . "<br>";
echo "<strong>Fecha Generación:</strong> " . date('d/m/Y H:i:s') . "<br>";
echo "<strong>Total Técnicos:</strong> " . $metricas_tecnicos->num_rows;
echo "</td>";
echo "</tr>";

// Espacio
echo "<tr><td colspan='12' height='20'></td></tr>";

// Métricas Generales
echo "<tr>";
echo "<td colspan='7' style='background-color:#3498db; color:white; padding:8px; font-weight:bold;'>MÉTRICAS GENERALES DEL SISTEMA</td>";
echo "</tr>";

echo "<tr>";
foreach ($metricas_generales as $key => $value) {
    echo "<td style='background-color:#ecf0f1; padding:8px; font-weight:bold;'>" . $key . "</td>";
}
echo "</tr>";

echo "<tr>";
foreach ($metricas_generales as $value) {
    echo "<td style='padding:8px;'>" . ($value ?? '0') . "</td>";
}
echo "</tr>";

// Espacio
echo "<tr><td colspan='12' height='20'></td></tr>";

// Métricas por Técnico
echo "<tr>";
echo "<td colspan='12' style='background-color:#27ae60; color:white; padding:8px; font-weight:bold;'>DESEMPEÑO POR TÉCNICO</td>";
echo "</tr>";

// Encabezados
if ($metricas_tecnicos->num_rows > 0) {
    $field_info = $metricas_tecnicos->fetch_fields();
    echo "<tr>";
    foreach ($field_info as $field) {
        echo "<th style='background-color:#2c3e50; color:white; padding:8px; text-align:center; font-weight:bold;'>" . $field->name . "</th>";
    }
    echo "</tr>";
    
    // Datos
    $metricas_tecnicos->data_seek(0);
    while ($row = $metricas_tecnicos->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td style='padding:6px;'>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='12' style='padding:20px; text-align:center;'>No hay datos de técnicos para el período seleccionado</td></tr>";
}

echo "</table>";
echo "</body></html>";

$conn->close();
exit();
