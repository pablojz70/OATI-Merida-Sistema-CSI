<?php
// descargar_adjunto.php - Descarga segura de archivos adjuntos
session_start();

// Verificar sesión
if (!isset($_SESSION['privilegio'])) {
    header('Location: index.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];

if (!$id_usuario) {
    header('Location: index.php');
    exit();
}

// Conexión a base de datos
try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

$adjunto_id = intval($_GET['id'] ?? 0);

if ($adjunto_id <= 0) {
    die("Archivo no especificado");
}

// Consultar información del archivo adjunto
try {
    $sql = "SELECT ta.*, t.usuario_id as ticket_usuario_id, t.tecnico_asignado 
            FROM TicketAdjuntos ta
            JOIN Tickets t ON ta.ticket_id = t.id
            WHERE ta.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$adjunto_id]);
    $adjunto = $stmt->fetch();
    
    if (!$adjunto) {
        die("Archivo no encontrado");
    }
    
    // Verificar permisos
    $puede_descargar = false;
    
    if ($privilegio == 'admin') {
        $puede_descargar = true;
    } elseif ($privilegio == 'tecnico') {
        $puede_descargar = ($adjunto['tecnico_asignado'] == $id_usuario);
    } elseif ($privilegio == 'usuario') {
        $puede_descargar = ($adjunto['ticket_usuario_id'] == $id_usuario);
    }
    
    // También puede descargar quien subió el archivo
    if ($adjunto['subido_por'] == $id_usuario) {
        $puede_descargar = true;
    }
    
    if (!$puede_descargar) {
        die("No tiene permisos para descargar este archivo");
    }
    
    // Ruta completa del archivo
    $ruta_base_adjuntos = "/opt/lampp/htdocs/sistema_csi/adjuntos/";
    $ruta_completa_archivo = $ruta_base_adjuntos . $adjunto['ruta_archivo'];
    
    // Verificar que el archivo existe
    if (!file_exists($ruta_completa_archivo)) {
        die("El archivo no existe en el servidor");
    }
    
    // Configurar headers para descarga
    header('Content-Type: ' . $adjunto['tipo_archivo']);
    header('Content-Disposition: attachment; filename="' . basename($adjunto['nombre_archivo']) . '"');
    header('Content-Length: ' . filesize($ruta_completa_archivo));
    header('Cache-Control: private');
    header('Pragma: private');
    header('Expires: 0');
    
    // Enviar archivo
    readfile($ruta_completa_archivo);
    
    // Registrar descarga en historial (opcional)
    try {
        $detalle = "Archivo descargado: " . $adjunto['nombre_archivo'];
        $sql_historial = "INSERT INTO historialtickets 
                         (ticket_id, usuario_id, accion, detalle, fecha_accion)
                         VALUES (?, ?, 'archivo_descargado', ?, NOW())";
        $stmt_hist = $conn->prepare($sql_historial);
        $stmt_hist->execute([$adjunto['ticket_id'], $id_usuario, $detalle]);
    } catch (Exception $e) {
        // Silenciar errores del historial
    }
    
    exit();
    
} catch (PDOException $e) {
    die("Error al procesar la descarga: " . $e->getMessage());
}
?>
