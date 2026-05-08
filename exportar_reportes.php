<?php
// exportar_reportes.php
require_once 'config/session.php';
require_once 'config/database.php';

if ($_SESSION['privilegio'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Obtener parámetros
$formato = $_GET['formato'] ?? 'pdf';
$tipo_reporte = $_GET['tipo_reporte'] ?? 'general';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');

$db = Database::getInstance();
$conn = $db->getConnection();

// Obtener datos del reporte
// (Similar a ajax/reportes.php pero con más detalles)

switch ($formato) {
    case 'pdf':
        exportarPDF();
        break;
    case 'excel':
        exportarExcel();
        break;
    case 'word':
        exportarWord();
        break;
    default:
        echo "Formato no soportado";
        break;
}

function exportarPDF() {
    global $tipo_reporte, $fecha_inicio, $fecha_fin;
    
    // En una implementación real, usarías una librería como TCPDF o DomPDF
    // Aquí un ejemplo básico
    
    $titulo = "Reporte $tipo_reporte - $fecha_inicio al $fecha_fin";
    
    // Generar contenido HTML para el PDF
    $html = "
    <html>
    <head>
        <title>$titulo</title>
        <style>
            body { font-family: Arial, sans-serif; }
            h1 { color: #3498db; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; }
            th { background-color: #f2f2f2; }
            .header { background: #3498db; color: white; padding: 20px; text-align: center; }
            .footer { margin-top: 50px; text-align: center; color: #777; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Areas Operativas: Infraestructura - OATI - Reporte de Tickets</h1>
            <p>Período: $fecha_inicio al $fecha_fin</p>
            <p>Generado el: " . date('d/m/Y H:i') . "</p>
        </div>
        
        <h2>Estadísticas Generales</h2>
        <table>
            <tr>
                <th>Total Tickets</th>
                <th>Cerrados</th>
                <th>Pendientes</th>
                <th>Tiempo Promedio</th>
            </tr>
            <tr>
                <td>100</td>
                <td>85</td>
                <td>15</td>
                <td>120 min</td>
            </tr>
        </table>
        
        <h2>Tickets por Área</h2>
        <table>
            <tr>
                <th>Área</th>
                <th>Cantidad</th>
                <th>Porcentaje</th>
            </tr>
            <tr>
                <td>Soporte Hardware</td>
                <td>35</td>
                <td>35%</td>
            </tr>
            <tr>
                <td>Redes</td>
                <td>25</td>
                <td>25%</td>
            </tr>
        </table>
        
        <div class='footer'>
            <p>Sistema de Control de Soporte Informático - CSI</p>
            <p>Reporte generado automáticamente</p>
        </div>
    </body>
    </html>
    ";
    
    // Forzar descarga como PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte_' . date('Ymd_His') . '.pdf"');
    
    // En producción, usarías: echo $pdf->Output('reporte.pdf', 'D');
    echo "PDF generado: $titulo";
    // Nota: Necesitarías una librería como TCPDF para generar PDF real
}

function exportarExcel() {
    global $conn, $fecha_inicio, $fecha_fin;
    
    // Consultar datos
    $query = "SELECT 
        t.id,
        t.titulo,
        a.nombre as area,
        t.prioridad,
        t.estado,
        DATE_FORMAT(t.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion,
        u.nombre as usuario,
        d.nombre as dependencia,
        tech.nombre as tecnico,
        t.tiempo_resolucion_minutos
        FROM tickets t
        JOIN areas a ON t.area_id = a.id
        JOIN usuarios u ON t.usuario_id = u.id
        JOIN dependencias d ON t.dependencia_id = d.id
        LEFT JOIN usuarios tech ON t.tecnico_asignado = tech.id
        WHERE t.fecha_creacion BETWEEN ? AND ?
        ORDER BY t.fecha_creacion DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Crear contenido CSV
    $csv = "ID,Título,Área,Prioridad,Estado,Fecha Creación,Usuario,Dependencia,Técnico,Tiempo Resolución (min)\n";
    
    while ($row = $result->fetch_assoc()) {
        $csv .= '"' . implode('","', [
            $row['id'],
            $row['titulo'],
            $row['area'],
            $row['prioridad'],
            $row['estado'],
            $row['fecha_creacion'],
            $row['usuario'],
            $row['dependencia'],
            $row['tecnico'] ?? 'Sin asignar',
            $row['tiempo_resolucion_minutos'] ?? ''
        ]) . "\"\n";
    }
    
    // Forzar descarga como CSV (que se puede abrir en Excel)
    $filename = "reporte_tickets_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $csv;
    exit();
}

function exportarWord() {
    global $tipo_reporte, $fecha_inicio, $fecha_fin;
    
    // Similar a PDF pero con formato Word
    $content = "
    <html xmlns:o='urn:schemas-microsoft-com:office:office' 
    xmlns:w='urn:schemas-microsoft-com:office:word' 
    xmlns='http://www.w3.org/TR/REC-html40'>
    <head>
        <title>Reporte Word</title>
        <style>
            body { font-family: Arial, sans-serif; }
            h1 { color: #2c3e50; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; }
            th { background-color: #3498db; color: white; }
        </style>
    </head>
    <body>
        <h1>Reporte: $tipo_reporte</h1>
        <p>Período: $fecha_inicio al $fecha_fin</p>
        <p>Generado el: " . date('d/m/Y H:i') . "</p>
        
        <h2>Resumen Ejecutivo</h2>
        <p>Este reporte muestra el análisis del período seleccionado...</p>
        
        <h2>Tabla de Datos</h2>
        <table>
            <tr>
                <th>Ítem</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>Tickets Totales</td>
                <td>100</td>
            </tr>
            <tr>
                <td>Tasa de Cierre</td>
                <td>85%</td>
            </tr>
        </table>
    </body>
    </html>
    ";
    
    header('Content-Type: application/vnd.ms-word');
    header('Content-Disposition: attachment; filename="reporte_' . date('Ymd_His') . '.doc"');
    echo $content;
    exit();
}
?>
