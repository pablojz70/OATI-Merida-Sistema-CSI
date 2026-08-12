<?php
session_start();
if (!isset($_SESSION['privilegio']) || !in_array($_SESSION['privilegio'], ['admin', 'director', 'oati', 'infraestructura'])) {
    header('Location: index.php');
    exit();
}

$privilegio = $_SESSION['privilegio'];
$usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';

try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión");
}

// Fecha del reporte: si se envía fecha, usarla; si no, la de hoy
$fecha_hoy = isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$fecha_mostrar = date('d/m/Y', strtotime($fecha_hoy));

// Filtro por tipo de actividad (oati / infraestructura / todos)
$tipo_reporte = $_GET['tipo'] ?? 'todos';
$tipo_filter = '';
$asunto_reporte = 'Actividades Diarias OATI Merida';

if ($tipo_reporte == 'oati') {
    $tipo_filter = " AND t.area_tipo = 'informatica'";
    $asunto_reporte = 'Actividades Diarias OATI Merida';
} elseif ($tipo_reporte == 'infraestructura') {
    $tipo_filter = " AND t.area_tipo = 'infraestructura'";
    $asunto_reporte = 'Actividades Diarias Infraestructura Merida';
} else {
    $asunto_reporte = 'Actividades Diarias Merida';
}

// Consultar actividades del día por dependencia
$stmt = $conn->query("SELECT t.id, t.numero_ticket, t.asunto, t.descripcion, t.estado, t.area_tipo,
        d.nombre_corto as dependencia_corto, d.nombre as dependencia_nombre, d.sede,
        DATE_FORMAT(t.fecha_creacion, '%H:%i') as hora
        FROM Tickets t
        JOIN Dependencias d ON t.dependencia_id = d.id
        WHERE DATE(t.fecha_creacion) = '$fecha_hoy' $tipo_filter
        ORDER BY d.sede, d.nombre_corto, t.fecha_creacion");

$actividades = $stmt->fetchAll();

// Agrupar por sede
$por_sede = [];
foreach ($actividades as $act) {
    $sede = !empty($act['sede']) ? $act['sede'] : 'Sin sede asignada';
    if (!isset($por_sede[$sede])) $por_sede[$sede] = [];
    $por_sede[$sede][] = $act;
}

$total = count($actividades);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Diario - CSI</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; font-size:12px; color:#333; padding:20px; background:#fff; }
        .header { text-align:center; border-bottom:3px solid #1a2980; padding-bottom:12px; margin-bottom:20px; }
        .header h1 { font-size:20px; color:#1a2980; }
        .header p { font-size:13px; color:#333; margin-top:4px; }
        .header .asunto { font-size:15px; font-weight:bold; color:#0d6e6e; margin-top:8px; }
        .info-box { border:1px solid #ccc; border-radius:6px; padding:10px 14px; margin-bottom:15px; font-size:13px; }
        .info-box span { margin-right:25px; }
        .info-box b { color:#1a2980; }
        .sede { margin-bottom:20px; page-break-inside:avoid; }
        .sede-titulo { background:#1a2980; color:#fff; padding:7px 12px; border-radius:4px; font-size:14px; font-weight:bold; margin-bottom:8px; }
        table { width:100%; border-collapse:collapse; }
        th { background:#f0f2f5; color:#333; text-align:left; padding:6px 8px; border:1px solid #ddd; font-size:11px; text-transform:uppercase; }
        td { padding:6px 8px; border:1px solid #ddd; font-size:11px; text-transform:uppercase; }
        tr:nth-child(even) td { background:#f9fafc; }
        .total { text-align:right; font-size:13px; margin-top:10px; font-weight:bold; color:#1a2980; }
        .btn-print { position:fixed; top:15px; right:15px; background:#3498db; color:#fff; border:none; padding:8px 16px; border-radius:5px; cursor:pointer; font-size:12px; }
        .footer { margin-top:30px; text-align:center; font-size:10px; color:#999; border-top:1px solid #ddd; padding-top:10px; }
    </style>
</head>
<body>
    <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>

    <div style="text-align:center; margin-bottom:15px;">
        <img src="imagen/head_reportes_blanco.jpg" alt="Encabezado" style="width:100%; max-width:700px; height:auto;">
    </div>

    <div class="header">
        <h1>REPORTE DE ACTIVIDADES DIARIAS</h1>
        <p><strong>Estado:</strong> Mérida</p>
        <p><strong>Fecha:</strong> <?php echo $fecha_mostrar; ?></p>
        <div class="asunto">Asunto: <?php echo htmlspecialchars($asunto_reporte); ?></div>
    </div>

    <div class="info-box">
        <span><b>Total de actividades:</b> <?php echo $total; ?></span>
        <span><b>Generado por:</b> <?php echo htmlspecialchars($usuario_nombre); ?></span>
        <span><b>Hora:</b> <?php echo date('H:i'); ?></span>
    </div>

    <?php if (empty($por_sede)): ?>
        <div style="text-align:center; padding:40px; color:#999;">
            <p>No se registraron actividades para la fecha (<?php echo $fecha_mostrar; ?>)</p>
        </div>
    <?php else: ?>
        <?php foreach ($por_sede as $sede => $actividades_sede): ?>
        <div class="sede">
            <div class="sede-titulo"><?php echo htmlspecialchars($sede); ?></div>
            <table>
                <thead>
                    <tr>
                        <th>Dependencia</th>
                        <th>Actividad del Día</th>
                        <th>Descripción</th>
                        <?php if ($tipo_reporte == 'todos'): ?>
                        <th style="width:70px;">Tipo</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actividades_sede as $act): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($act['dependencia_corto'] ?: $act['dependencia_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($act['asunto']); ?></td>
                        <td style="text-align:justify;"><?php echo nl2br(htmlspecialchars($act['descripcion'] ?? '')); ?></td>
                        <?php if ($tipo_reporte == 'todos'): ?>
                        <td><?php echo $act['area_tipo'] == 'infraestructura' ? 'Infra' : 'OATI'; ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
        <div class="total">Total de actividades del día: <?php echo $total; ?></div>
    <?php endif; ?>

    <div class="footer">
        Sistema CSI - Centro de Soporte Informático - DAR Mérida | Generado el <?php echo date('d/m/Y H:i:s'); ?>
    </div>
</body>
</html>
