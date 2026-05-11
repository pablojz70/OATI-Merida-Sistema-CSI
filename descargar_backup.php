<?php
// descargar_backup.php - Descargar archivo de backup
session_start();

if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    header('Location: index.php');
    exit();
}

$archivo = $_GET['archivo'] ?? '';

if (empty($archivo)) {
    header('Location: admin_backup.php?error=archivo_no_especificado');
    exit();
}

// Sanitizar nombre de archivo
$archivo = basename($archivo);
$ruta = '/opt/lampp/htdocs/sistema_csi/backups/' . $archivo;

if (!file_exists($ruta)) {
    header('Location: admin_backup.php?error=archivo_no_encontrado');
    exit();
}

// Determinar tipo de contenido
$extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
if ($extension === 'gz') {
    $content_type = 'application/gzip';
} else {
    $content_type = 'application/octet-stream';
}

// Enviar headers para descarga
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $archivo . '"');
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: no-cache, must-revalidate');

// Enviar archivo
readfile($ruta);
exit();
?>
