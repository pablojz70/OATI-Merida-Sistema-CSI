<?php
// calendario.php - Calendario de tickets programados
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

// Mes y año seleccionados (por defecto el actual)
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('m'));
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));
if ($mes < 1) { $mes = 12; $anio--; }
if ($mes > 12) { $mes = 1; $anio++; }

// Navegación
$mes_ant = $mes - 1; $anio_ant = $anio;
$mes_sig = $mes + 1; $anio_sig = $anio;
if ($mes_ant < 1) { $mes_ant = 12; $anio_ant--; }
if ($mes_sig > 12) { $mes_sig = 1; $anio_sig++; }

// Consultar tickets con fecha_evento en el mes
$primer_dia = "$anio-$mes-01";
$ultimo_dia = date('Y-m-t', strtotime($primer_dia));
$stmt = $conn->prepare("SELECT t.id, t.numero_ticket, t.asunto, t.tipo_evento, t.fecha_evento, t.estado,
        d.nombre_corto as dep
        FROM Tickets t
        JOIN Dependencias d ON t.dependencia_id = d.id
        WHERE t.fecha_evento IS NOT NULL
        AND t.fecha_evento BETWEEN ? AND ?
        ORDER BY t.fecha_evento");
$stmt->execute([$primer_dia . ' 00:00:00', $ultimo_dia . ' 23:59:59']);
$tickets = $stmt->fetchAll();

// Agrupar por día
$por_dia = [];
foreach ($tickets as $t) {
    $dia = intval(date('j', strtotime($t['fecha_evento'])));
    if (!isset($por_dia[$dia])) $por_dia[$dia] = [];
    $por_dia[$dia][] = $t;
}

// Colores por tipo
$colores = [
    'audiencia' => '#e74c3c',
    'evento' => '#27ae60',
    'mantenimiento' => '#3498db'
];
$etiquetas = [
    'audiencia' => 'Audiencia Telemática',
    'evento' => 'Evento',
    'mantenimiento' => 'Mantenimiento Preventivo'
];

// Construir la cuadrícula del calendario
$dias_semana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$primer_dow = intval(date('N', strtotime($primer_dia))); // 1=Lun ... 7=Dom
$total_dias = intval(date('t', strtotime($primer_dia)));
$nombre_mes = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][$mes-1];
$hoy = intval(date('j'));
$mes_actual = intval(date('m'));
$anio_actual = intval(date('Y'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario de Actividades - CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="vendor/font-awesome/all.min.css">
    <style>
        body { font-family:'Segoe UI',Arial,sans-serif; background:#f0f2f5; }
        .calendar-container { margin-left:190px; padding:15px; min-height:calc(100vh - 70px); }
        @media (max-width:768px) { .calendar-container { margin-left:0; } }
        .cal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px; }
        .cal-header h2 { color:#1a2980; margin:0; }
        .cal-nav { display:flex; gap:8px; align-items:center; }
        .cal-nav a, .cal-nav button { padding:7px 14px; border-radius:5px; text-decoration:none; color:#fff; border:none; cursor:pointer; font-size:13px; }
        .cal-nav .ant { background:#6c757d; }
        .cal-nav .sig { background:#6c757d; }
        .cal-nav .hoy { background:#27ae60; }
        .cal-nav .mes-titulo { font-size:18px; font-weight:bold; color:#1a2980; min-width:180px; text-align:center; }
        .leyenda { display:flex; gap:15px; margin-bottom:15px; flex-wrap:wrap; background:#fff; padding:10px 15px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,.05); }
        .leyenda span { display:flex; align-items:center; gap:6px; font-size:12px; }
        .dot { width:12px; height:12px; border-radius:50%; display:inline-block; }
        .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:5px; }
        .cal-dow { text-align:center; font-weight:bold; font-size:12px; color:#666; padding:5px; background:#f0f2f5; border-radius:4px; }
        .cal-day { background:#fff; min-height:90px; border-radius:6px; padding:4px; box-shadow:0 1px 3px rgba(0,0,0,.05); position:relative; }
        .cal-day .num { font-size:12px; font-weight:bold; color:#333; }
        .cal-day.empty { background:transparent; box-shadow:none; }
        .cal-day.today { border:2px solid #1a2980; }
        .cal-day .tickets { margin-top:3px; }
        .ticket-marca { display:flex; align-items:center; gap:4px; font-size:9px; color:#fff; padding:2px 5px; border-radius:3px; margin-bottom:2px; cursor:pointer; text-decoration:none; }
        .ticket-marca:hover { filter:brightness(1.1); }
        .total-dia { font-size:10px; color:#999; margin-top:2px; }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/logo2.png" alt="Logo" class="logo-oati-img">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte</h1>
                <p class="system-sub-custom">Calendario de Actividades</p>
            </div>
        </div>
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="user-role-custom"><?php echo htmlspecialchars(ucfirst($privilegio)); ?></span>
            </div>
            <a href="logout.php" class="logout-btn-custom"><img src="imagen/Salir.png" alt="Salir" class="logout-img"><span class="logout-text">Salir</span></a>
        </div>
    </header>
    <div class="main-wrapper">
        <?php
        $menu_archivo = "includes/menu_$privilegio.php";
        if (file_exists($menu_archivo)) include $menu_archivo;
        else include 'includes/menu_usuario.php';
        ?>
        <main class="calendar-container">
            <div class="cal-header">
                <h2><i class="fas fa-calendar-alt"></i> Calendario de Actividades Programadas</h2>
                <div class="cal-nav">
                    <a class="ant" href="calendario.php?mes=<?php echo $mes_ant; ?>&anio=<?php echo $anio_ant; ?>"><i class="fas fa-chevron-left"></i> Anterior</a>
                    <span class="mes-titulo"><?php echo $nombre_mes; ?> <?php echo $anio; ?></span>
                    <a class="sig" href="calendario.php?mes=<?php echo $mes_sig; ?>&anio=<?php echo $anio_sig; ?>">Siguiente <i class="fas fa-chevron-right"></i></a>
                    <a class="hoy" href="calendario.php">Hoy</a>
                </div>
            </div>

            <div class="leyenda">
                <span><span class="dot" style="background:#e74c3c;"></span> Audiencia Telemática</span>
                <span><span class="dot" style="background:#27ae60;"></span> Evento</span>
                <span><span class="dot" style="background:#3498db;"></span> Mantenimiento Preventivo</span>
            </div>

            <div class="cal-grid">
                <?php foreach ($dias_semana as $ds): ?>
                <div class="cal-dow"><?php echo $ds; ?></div>
                <?php endforeach; ?>

                <?php for ($i = 1; $i < $primer_dow; $i++): ?>
                <div class="cal-day empty"></div>
                <?php endfor; ?>

                <?php for ($dia = 1; $dia <= $total_dias; $dia++): ?>
                <div class="cal-day <?php echo ($mes == $mes_actual && $anio == $anio_actual && $dia == $hoy) ? 'today' : ''; ?>">
                    <div class="num"><?php echo $dia; ?></div>
                    <div class="tickets">
                        <?php if (isset($por_dia[$dia])): ?>
                            <?php foreach ($por_dia[$dia] as $t): ?>
                            <a href="ver_ticket.php?id=<?php echo $t['id']; ?>" class="ticket-marca" 
                               style="background:<?php echo $colores[$t['tipo_evento']] ?? '#999'; ?>;"
                               title="<?php echo htmlspecialchars($t['numero_ticket'] . ' - ' . $t['asunto']); ?>">
                                <?php echo htmlspecialchars(substr($t['asunto'], 0, 18)); ?>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </main>
    </div>
</body>
</html>
