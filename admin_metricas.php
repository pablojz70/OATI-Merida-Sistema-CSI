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

// Periodo de análisis (últimos 30 días por defecto)
$periodo = $_GET['periodo'] ?? '30dias';
$fecha_inicio = date('Y-m-d');
$fecha_fin = date('Y-m-d');

switch ($periodo) {
    case '7dias':
        $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
        $rango = "Últimos 7 días";
        break;
    case '15dias':
        $fecha_inicio = date('Y-m-d', strtotime('-15 days'));
        $rango = "Últimos 15 días";
        break;
    case 'mes':
        $fecha_inicio = date('Y-m-01');
        $rango = "Mes actual";
        break;
    case 'trimestre':
        $fecha_inicio = date('Y-m-d', strtotime('-3 months'));
        $rango = "Último trimestre";
        break;
    default: // 30dias
        $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
        $rango = "Últimos 30 días";
        break;
}

// Obtener métricas de técnicos
$metricas_tecnicos = $conn->query("
    SELECT 
        u.id,
        u.nombre as tecnico_nombre,
        COUNT(t.id) as total_tickets,
        SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) as tickets_cerrados,
        SUM(CASE WHEN t.estado = 'en_proceso' THEN 1 ELSE 0 END) as tickets_en_proceso,
        SUM(CASE WHEN t.estado = 'asignado' THEN 1 ELSE 0 END) as tickets_asignados,
        SUM(CASE WHEN t.prioridad = 'alta' THEN 1 ELSE 0 END) as tickets_alta,
        SUM(CASE WHEN t.prioridad = 'media' THEN 1 ELSE 0 END) as tickets_media,
        SUM(CASE WHEN t.prioridad = 'baja' THEN 1 ELSE 0 END) as tickets_baja,
        AVG(CASE WHEN t.estado = 'cerrado' THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) ELSE NULL END) as tiempo_promedio_horas,
        MAX(CASE WHEN t.estado != 'cerrado' THEN TIMESTAMPDIFF(HOUR, t.fecha_creacion, NOW()) ELSE 0 END) as max_tiempo_espera,
        (SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) / COUNT(t.id) * 100) as porcentaje_cierre
    FROM Tickets t
    INNER JOIN Usuarios u ON t.tecnico_asignado = u.id
    WHERE t.fecha_creacion >= '$fecha_inicio 00:00:00'
        AND t.fecha_creacion <= '$fecha_fin 23:59:59'
        AND u.privilegio = 'tecnico'
        AND u.activo = 1
    GROUP BY u.id, u.nombre
    ORDER BY total_tickets DESC
");

// Métricas generales del sistema
$metricas_generales = $conn->query("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN estado = 'cerrado' THEN 1 ELSE 0 END) as tickets_cerrados,
        AVG(CASE WHEN estado = 'cerrado' THEN TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_cierre) ELSE NULL END) as tiempo_promedio,
        SUM(CASE WHEN prioridad = 'alta' THEN 1 ELSE 0 END) as tickets_alta
    FROM Tickets
    WHERE fecha_creacion >= '$fecha_inicio 00:00:00'
        AND fecha_creacion <= '$fecha_fin 23:59:59'
")->fetch_assoc();

// Calcular porcentajes
$total_tickets = $metricas_generales['total_tickets'] ?? 0;
$tickets_cerrados = $metricas_generales['tickets_cerrados'] ?? 0;
$porcentaje_cierre = $total_tickets > 0 ? ($tickets_cerrados / $total_tickets) * 100 : 0;
$tickets_alta = $metricas_generales['tickets_alta'] ?? 0;
$porcentaje_alta = $total_tickets > 0 ? ($tickets_alta / $total_tickets) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas y Desempeño - Sistema CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <!-- jQuery y DataTables en el header -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <style>
        /* ESTILOS GENERALES - SIMPLIFICADOS */
        .metricas-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px;
        }
        
        .metricas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eef2f7;
        }
        
        .metricas-header h1 {
            color: #1a2980;
            font-size: 24px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .periodo-selector {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .btn-periodo {
            padding: 6px 12px;
            border: 1px solid #3498db;
            background: white;
            color: #3498db;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-periodo.active {
            background: #3498db;
            color: white;
        }
        
        .btn-periodo:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(52, 152, 219, 0.2);
            text-decoration: none;
        }
        
        /* ESTADÍSTICAS GENERALES */
        .stats-metricas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-metrica {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-top: 3px solid;
            transition: transform 0.2s;
        }
        
        .stat-metrica:hover {
            transform: translateY(-3px);
        }
        
        .stat-metrica.total { border-color: #1a2980; }
        .stat-metrica.cerrados { border-color: #27ae60; }
        .stat-metrica.tiempo { border-color: #3498db; }
        .stat-metrica.criticos { border-color: #e74c3c; }
        
        .stat-numero {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            display: block;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-sub {
            font-size: 11px;
            color: #95a5a6;
            margin-top: 4px;
        }
        
        /* GRÁFICOS SIMPLIFICADOS - SIEMPRE VISIBLES */
        .graficos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .graficos-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .grafico-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            height: 250px;
            display: flex;
            flex-direction: column;
        }
        
        .grafico-header {
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .grafico-header h4 {
            color: #2c3e50;
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .grafico-canvas-container {
            flex: 1;
            position: relative;
        }
        
        .grafico-canvas {
            width: 100% !important;
            height: 180px !important;
        }
        
        /* TABLA */
        .tabla-metricas {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow-x: auto;
        }
        
        .tabla-metricas h3 {
            color: #2c3e50;
            margin: 0 0 15px 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        #tablaMetricas {
            font-size: 12px;
            width: 100%;
        }
        
        #tablaMetricas th {
            padding: 8px 10px !important;
            font-size: 12px;
        }
        
        #tablaMetricas td {
            padding: 6px 10px !important;
            font-size: 12px;
        }
        
        .badge-desempeno {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-excelente { background: #d4edda; color: #155724; }
        .badge-bueno { background: #d1ecf1; color: #0c5460; }
        .badge-regular { background: #fff3cd; color: #856404; }
        .badge-deficiente { background: #f8d7da; color: #721c24; }
        
        /* BOTONES DE ACCIÓN */
        .acciones-metricas {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 15px;
        }
        
        .btn-exportar, .btn-imprimir {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
            font-size: 13px;
        }
        
        .btn-exportar {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
            color: white;
        }
        
        .btn-exportar:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(39, 174, 96, 0.3);
        }
        
        .btn-imprimir {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .btn-imprimir:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
        }
        
        /* INFO RANGO */
        .info-rango {
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            color: white;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="sidebar">
            <?php include 'includes/menu_admin.php'; ?>
        </div>
        
        <div class="main-content">
            <div class="metricas-container">
                <!-- Header -->
                <div class="metricas-header">
                    <h1><i class="fas fa-chart-line"></i> Métricas de Desempeño</h1>
                    <div class="periodo-selector">
                        <a href="?periodo=7dias" class="btn-periodo <?php echo $periodo == '7dias' ? 'active' : ''; ?>">
                            7 Días
                        </a>
                        <a href="?periodo=15dias" class="btn-periodo <?php echo $periodo == '15dias' ? 'active' : ''; ?>">
                            15 Días
                        </a>
                        <a href="?periodo=30dias" class="btn-periodo <?php echo ($periodo == '30dias' || !isset($_GET['periodo'])) ? 'active' : ''; ?>">
                            30 Días
                        </a>
                        <a href="?periodo=mes" class="btn-periodo <?php echo $periodo == 'mes' ? 'active' : ''; ?>">
                            Mes
                        </a>
                        <a href="?periodo=trimestre" class="btn-periodo <?php echo $periodo == 'trimestre' ? 'active' : ''; ?>">
                            Trimestre
                        </a>
                    </div>
                </div>
                
                <!-- Información del rango -->
                <div class="info-rango">
                    <div class="rango-texto">
                        <i class="fas fa-calendar-alt"></i> Período: <?php echo $rango; ?>
                    </div>
                    <div class="fechas">
                        <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                    </div>
                </div>
                
                <!-- Estadísticas Generales -->
                <div class="stats-metricas">
                    <div class="stat-metrica total">
                        <span class="stat-numero"><?php echo $total_tickets; ?></span>
                        <span class="stat-label">Total Tickets</span>
                    </div>
                    
                    <div class="stat-metrica cerrados">
                        <span class="stat-numero"><?php echo $tickets_cerrados; ?></span>
                        <span class="stat-label">Resueltos</span>
                        <span class="stat-sub"><?php echo round($porcentaje_cierre, 1); ?>%</span>
                    </div>
                    
                    <div class="stat-metrica tiempo">
                        <span class="stat-numero"><?php echo round($metricas_generales['tiempo_promedio'] ?? 0, 1); ?></span>
                        <span class="stat-label">Horas Prom.</span>
                        <span class="stat-sub">Resolución</span>
                    </div>
                    
                    <div class="stat-metrica criticos">
                        <span class="stat-numero"><?php echo $tickets_alta; ?></span>
                        <span class="stat-label">Críticos</span>
                        <span class="stat-sub"><?php echo round($porcentaje_alta, 1); ?>%</span>
                    </div>
                </div>
                
                <!-- Tabla de Desempeño de Técnicos -->
                <div class="tabla-metricas">
                    <h3><i class="fas fa-medal"></i> Desempeño por Técnico</h3>
                    <table id="tablaMetricas" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>Técnico</th>
                                <th>Total</th>
                                <th>Resueltos</th>
                                <th>En Proc.</th>
                                <th>Pend.</th>
                                <th>Alta</th>
                                <th>Tiempo (h)</th>
                                <th>Espera (h)</th>
                                <th>% Cierre</th>
                                <th>Desem.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($metricas_tecnicos) {
                                while ($tecnico = $metricas_tecnicos->fetch_assoc()): 
                                    $porcentaje = $tecnico['porcentaje_cierre'] ?? 0;
                                    
                                    // Determinar desempeño
                                    $desempeno = '';
                                    $clase_desempeno = '';
                                    if ($porcentaje >= 90) {
                                        $desempeno = 'Excel';
                                        $clase_desempeno = 'badge-excelente';
                                    } elseif ($porcentaje >= 75) {
                                        $desempeno = 'Bueno';
                                        $clase_desempeno = 'badge-bueno';
                                    } elseif ($porcentaje >= 50) {
                                        $desempeno = 'Regular';
                                        $clase_desempeno = 'badge-regular';
                                    } else {
                                        $desempeno = 'Defic';
                                        $clase_desempeno = 'badge-deficiente';
                                    }
                            ?>
                            <tr>
                                <td><strong title="<?php echo htmlspecialchars($tecnico['tecnico_nombre'] ?? 'No asignado'); ?>">
                                    <?php echo substr($tecnico['tecnico_nombre'] ?? 'N/A', 0, 15); ?>
                                </strong></td>
                                <td><span style="font-weight:600;"><?php echo $tecnico['total_tickets'] ?? 0; ?></span></td>
                                <td><span style="color:#27ae60; font-weight:600;"><?php echo $tecnico['tickets_cerrados'] ?? 0; ?></span></td>
                                <td><span style="color:#f39c12; font-weight:600;"><?php echo $tecnico['tickets_en_proceso'] ?? 0; ?></span></td>
                                <td><span style="color:#e74c3c; font-weight:600;"><?php echo $tecnico['tickets_asignados'] ?? 0; ?></span></td>
                                <td><span style="color:#c0392b; font-weight:600;"><?php echo $tecnico['tickets_alta'] ?? 0; ?></span></td>
                                <td style="<?php echo ($tecnico['tiempo_promedio_horas'] ?? 0) > 24 ? 'color:#e74c3c; font-weight:bold;' : 'color:#27ae60; font-weight:bold;'; ?>">
                                    <?php echo round($tecnico['tiempo_promedio_horas'] ?? 0, 1); ?>
                                </td>
                                <td style="<?php echo ($tecnico['max_tiempo_espera'] ?? 0) > 48 ? 'color:#e74c3c; font-weight:bold;' : 'color:#27ae60; font-weight:bold;'; ?>">
                                    <?php echo $tecnico['max_tiempo_espera'] ?? 0; ?>
                                </td>
                                <td><span style="font-weight:600;"><?php echo round($porcentaje, 0); ?>%</span></td>
                                <td>
                                    <span class="badge-desempeno <?php echo $clase_desempeno; ?>" title="<?php echo $desempeno . ' (' . round($porcentaje, 1) . '%)'; ?>">
                                        <?php echo $desempeno; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Botones de Acción -->
                <div class="acciones-metricas">
                    <button class="btn-imprimir" onclick="imprimirPagina()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <button class="btn-exportar" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SCRIPTS SIMPLIFICADOS -->
    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            $('#tablaMetricas').DataTable({
                "pageLength": 10,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                },
                "order": [[1, "desc"]],
                "responsive": true
            });
        });
        
        // FUNCIÓN PARA IMPRIMIR - SIMPLE Y FUNCIONAL
        function imprimirPagina() {
            if (confirm('¿Desea imprimir el reporte de métricas?')) {
                // Guardar el HTML original
                const originalHTML = document.body.innerHTML;
                
                // Obtener solo el contenido que queremos imprimir
                const printContent = document.querySelector('.metricas-container').innerHTML;
                
                // Reemplazar todo el body con el contenido a imprimir
                document.body.innerHTML = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Métricas de Desempeño - Sistema CSI</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .print-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                            .print-header h1 { color: #1a2980; margin: 0; }
                            .print-info { background: #f5f5f5; padding: 10px; margin-bottom: 15px; border-radius: 5px; }
                            .print-date { text-align: right; font-size: 12px; color: #666; margin-bottom: 20px; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                            th { background: #2c3e50; color: white; padding: 8px; text-align: left; }
                            td { padding: 6px; border-bottom: 1px solid #ddd; }
                            .badge-excelente { background: #d4edda; color: #155724; padding: 2px 6px; border-radius: 10px; }
                            .badge-bueno { background: #d1ecf1; color: #0c5460; padding: 2px 6px; border-radius: 10px; }
                            .badge-regular { background: #fff3cd; color: #856404; padding: 2px 6px; border-radius: 10px; }
                            .badge-deficiente { background: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 10px; }
                            @media print {
                                .no-print { display: none; }
                                @page { margin: 0.5cm; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="print-header">
                            <h1>Métricas de Desempeño - Sistema CSI</h1>
                            <p>Reporte generado el <?php echo date('d/m/Y H:i'); ?></p>
                        </div>
                        <div class="print-info">
                            <p><strong>Período:</strong> <?php echo $rango; ?></p>
                            <p><strong>Fechas:</strong> <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></p>
                        </div>
                        <div class="print-date">
                            Página 1 de 1
                        </div>
                        ${printContent}
                        <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
                            <p>Reporte generado por el Sistema CSI - <?php echo date('d/m/Y H:i:s'); ?></p>
                        </div>
                    </body>
                    </html>
                `;
                
                // Imprimir
                window.print();
                
                // Restaurar el HTML original
                document.body.innerHTML = originalHTML;
                
                // Re-inicializar DataTable después de restaurar
                $('#tablaMetricas').DataTable({
                    "pageLength": 10,
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                    },
                    "order": [[1, "desc"]],
                    "responsive": true
                });
                
                alert('Reporte enviado a impresión');
            }
        }
        
        // FUNCIÓN PARA EXPORTAR EXCEL - SIMPLE Y FUNCIONAL
        function exportarExcel() {
            if (confirm('¿Desea exportar las métricas a Excel?')) {
                // Crear un formulario temporal
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = 'exportar_metricas_simple.php';
                form.target = '_blank';
                form.style.display = 'none';
                
                // Agregar parámetros
                const periodoInput = document.createElement('input');
                periodoInput.type = 'hidden';
                periodoInput.name = 'periodo';
                periodoInput.value = '<?php echo $periodo; ?>';
                form.appendChild(periodoInput);
                
                const fechaInicioInput = document.createElement('input');
                fechaInicioInput.type = 'hidden';
                fechaInicioInput.name = 'fecha_inicio';
                fechaInicioInput.value = '<?php echo $fecha_inicio; ?>';
                form.appendChild(fechaInicioInput);
                
                const fechaFinInput = document.createElement('input');
                fechaFinInput.type = 'hidden';
                fechaFinInput.name = 'fecha_fin';
                fechaFinInput.value = '<?php echo $fecha_fin; ?>';
                form.appendChild(fechaFinInput);
                
                // Agregar formulario al body y enviar
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
                
                alert('El archivo Excel se está descargando...');
            }
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
