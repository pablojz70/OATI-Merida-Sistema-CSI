<?php
session_start();

if (!isset($_SESSION['privilegio']) || !in_array($_SESSION['privilegio'], ['admin', 'OATI'])) {
    header('Location: index.php');
    exit();
}

$ticket_id = intval($_GET['ticket_id'] ?? 0);

if (!$ticket_id) {
    die("Ticket no encontrado");
}

try {
     $conn = new PDO("mysql:host=localhost;dbname=sistema_tickets;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$stmt = $conn->prepare("SELECT t.*, d.nombre as dependencia_nombre, d.nombre_corto as dependencia_corto 
FROM Tickets t 
LEFT JOIN Dependencias d ON t.dependencia_id = d.id 
WHERE t.id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Ticket no encontrado");
}

$tecnico_nombre = $_SESSION['nombre'] ?? 'OATI';

$bien_intradar = null;
if (!empty($ticket['numero_bien'])) {
    try {
        $conn_intradar = new PDO("mysql:host=localhost;dbname=intradar;charset=utf8mb4", "root", "");
        $conn_intradar->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql_bien = "SELECT b.id_bien, b.cod_bien, b.descripcion as desc_bien, b.cod_ubi, b.fecha_ing,
                          bc.modelo, bc.serial as serial_bien, fm.fab_marca as marca
                   FROM bienes b 
                   LEFT JOIN bien_car bc ON b.id_bien = bc.id_bien 
                   LEFT JOIN fab_marca fm ON bc.id_fab_marca_bien = fm.id_fab_marca_bien
                   WHERE b.cod_bien = ?";
        $stmt_bien = $conn_intradar->prepare($sql_bien);
        $stmt_bien->execute([$ticket['numero_bien']]);
        $bien_intradar = $stmt_bien->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error consultando intradar: " . $e->getMessage());
    }
}

$codigo_organo = '21';
$nombre_organo = 'TSJ - DIRECCIÓN EJECUTIVA DE LA MAGISTRATURA (DAR MÉRIDA)';
$dependencia_adm = 'Participación Ciudadana (HDP)';

$fecha_actual = date('d-m-y');
$hora_actual = date('H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Mantenimiento</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; padding: 15px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 15px; border: 1px solid #ccc; }
        h1 { text-align: center; font-size: 13px; margin-bottom: 10px; }
        h2 { text-align: center; font-size: 11px; margin: 5px 0; }
        hr { border: none; border-top: 1px solid #333; margin: 3px 0; }
        hr.double { border-top: 2px double #333; margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin: 2px 0; }
        td, th { border: 1px solid #333; padding: 2px 4px; font-size: 9px; vertical-align: top; }
        .header-table td { text-align: center; font-weight: bold; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; height: 50px; }
        .logo { height: 45px; }
        .header-date { text-align: right; font-size: 11px; }
        .checkbox-cell { text-align: center; width: 30%; }
        .firma-row td { height: 35px; }
        
        input[type="text"] { border: none; background: transparent; width: 100%; font-size: 10px; }
        input[type="text"]:focus { outline: 1px solid #1a2980; background: #fff; }
        textarea { border: none; background: transparent; width: 100%; min-height: 30px; font-size: 10px; font-family: Arial, sans-serif; resize: none; }
        textarea:focus { outline: 1px solid #1a2980; background: #fff; }
        @media print {
            input[type="text"], textarea { border: none; }
            body { padding: 0; background: white; }
            button { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-top">
            <img src="imagen/logodem.png" alt="Logo" class="logo">
            <div class="header-date">
                Fecha: <?php echo $fecha_actual; ?>
                <br><br>
                <button onclick="window.print()" style="padding: 5px 15px; cursor: pointer;">Imprimir</button>
            </div>
        </div>
        
        <h1>FICHA DE MANTENIMIENTO DE BIENES MUEBLES</h1>
        <hr class="double">
        
        <table>
            <tr>
                <td colspan="3" class="center bold">CÓDIGO DEL ORGANO O ENTE SIGECOFF O RGBP</td>
                <td colspan="2" class="center bold">NOMBRE DEL ORGANO O ENTE</td>
                <td colspan="2" class="center bold">DEPENDENCIA ADMINISTRATIVA / JUDICIAL</td>
            </tr>
            <tr class="header-table">
                <td colspan="3"><?php echo $codigo_organo; ?></td>
                <td colspan="2"><?php echo $nombre_organo; ?></td>
                <td colspan="2"><?php echo ($ticket['dependencia_nombre'] ?? $dependencia_adm) . ' - ' . ($ticket['lugar_area'] ?? ''); ?></td>
            </tr>
        </table>
        
        <h2>DETALLES DEL MANTENIMIENTO</h2>
        
        <hr class="double">
        
        <table>
<tr>
                <td colspan="4" class="center bold">DESCRIPCIÓN DEL BIEN</td>
                <td class="center bold">NÚMERO DE BIEN</td>
                <td class="center bold">MARCA / MODELO</td>
                <td class="center bold">SERIAL</td>
            </tr>
 <tr>
                <td colspan="4"><input type="text" value="<?php echo htmlspecialchars($bien_intradar['desc_bien'] ?? $ticket['descripcion'] ?? ''); ?>"></td>
                <td class="center"><input type="text" value="<?php echo htmlspecialchars($ticket['numero_bien'] ?? ''); ?>"></td>
                <td class="center"><input type="text" value="<?php echo htmlspecialchars(($bien_intradar['marca'] ?? '') . ' ' . ($bien_intradar['modelo'] ?? '')); ?>"></td>
                <td class="center"><input type="text" value="<?php echo htmlspecialchars($bien_intradar['serial_bien'] ?? ''); ?>"></td>
            </tr>
        </table>
        
        <hr class="double">
        
<table>
            <tr>
                <td class="center bold">TIPO DE MANTENIMIENTO</td>
                <td class="center bold">RESULTADO</td>
            </tr>
            <tr>
                <td>PREVENTIVO <input type="checkbox"> &nbsp; CORRECTIVO <input type="checkbox" checked></td>
                <td>REPARADO <input type="checkbox" checked> &nbsp; NO REPARADO <input type="checkbox"> &nbsp; DESINCORPORACIÓN <input type="checkbox"></td>
            </tr>
        </table>
        
<hr class="double">
            
        <table>
            <tr>
                <td class="center bold">DESCRIPCIÓN DE LA FALLA</td>
                <td class="center bold">ACTIVIDAD / REPARACIÓN REALIZADA</td>
            </tr>
            <tr>
                <td><textarea rows="3">ASUNTO: <?php echo htmlspecialchars($ticket['asunto'] ?? ''); ?>&#10;DESCRIPCIÓN: <?php echo htmlspecialchars($ticket['descripcion'] ?? ''); ?></textarea></td>
                <td><textarea rows="3"><?php echo htmlspecialchars($ticket['solucion'] ?? ''); ?></textarea></td>
            </tr>
        </table>
        
        <table>
            <tr>
                <td class="center bold">FECHA DE ENTRADA</td>
                <td class="center bold">FECHA DE SALIDA</td>
                <td class="center bold">MATERIALES EMPLEADOS</td>
                <td class="center bold">COSTO</td>
            </tr>
            <tr>
                <td><input type="text" value="<?php echo $ticket['fecha_creacion'] ? date('d-m-y', strtotime($ticket['fecha_creacion'])) : ''; ?>"></td>
                <td><input type="text" value="<?php echo date('d-m-y'); ?>"></td>
                <td><input type="text" value=""></td>
                <td><input type="text" value=""></td>
            </tr>
        </table>
        
        <table>
            <tr>
                <td class="center bold" style="width:25%;">MANTENIMIENTO / REPARACIÓN POR</td>
                <td class="center bold" style="width:25%;">ÁREA DE BIENES PÚBLICOS</td>
                <td class="center bold" style="width:25%;">RESPONSABLE ADMINISTRATIVO</td>
                <td class="center bold" style="width:25%;">DEPENDENCIA JUDICIAL/ADMINISTRATIVA</td>
            </tr>
<tr>
                <td class="center bold" style="width:25%;">FIRMA Y SELLO</td>
                <td class="center bold" style="width:25%;">FIRMA Y SELLO</td>
                <td class="center bold" style="width:25%;">FIRMA Y SELLO</td>
                <td class="center bold" style="width:25%;">FIRMA Y SELLO</td>
            </tr>
            <tr class="firma-row">
                <td style="height:40px;"></td>
                <td style="height:40px;"></td>
                <td style="height:40px;"></td>
                <td style="height:40px;"></td>
            </tr>
        </table>
        
</div>
</body>
</html>