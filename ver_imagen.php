<?php
// ver_imagen.php - Muestra imágenes en el navegador sin descargar
session_start();

// Verificar sesión
if (!isset($_SESSION['privilegio'])) {
    header('HTTP/1.0 403 Forbidden');
    die('Acceso denegado');
}

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];

if (!$id_usuario) {
    header('HTTP/1.0 403 Forbidden');
    die('Acceso denegado');
}

// Conexión a base de datos
try {
     $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    die("Error de conexión a la base de datos");
}

$adjunto_id = intval($_GET['id'] ?? 0);

if ($adjunto_id <= 0) {
    header('HTTP/1.0 400 Bad Request');
    die("ID de archivo no válido");
}

// Consultar información del archivo adjunto
try {
$sql = "SELECT ta.*, t.usuario_id as ticket_usuario_id, t.oati_asignado 
             FROM Adjuntos ta
             JOIN Tickets t ON ta.ticket_id = t.id
             WHERE ta.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$adjunto_id]);
    $adjunto = $stmt->fetch();
    
    if (!$adjunto) {
        header('HTTP/1.0 404 Not Found');
        die("Archivo no encontrado");
    }
    
    // Verificar permisos
    $puede_ver = false;
    
    if ($privilegio == 'admin') {
        $puede_ver = true;
} elseif ($privilegio == 'oati') {
         $puede_ver = ($adjunto['oati_asignado'] == $id_usuario);
    } elseif ($privilegio == 'usuario') {
        $puede_ver = ($adjunto['ticket_usuario_id'] == $id_usuario);
    }
    
    // También puede ver quien subió el archivo
    if (isset($adjunto['subido_por']) && $adjunto['subido_por'] == $id_usuario) {
        $puede_ver = true;
    }
    
    if (!$puede_ver) {
        header('HTTP/1.0 403 Forbidden');
        die("No tiene permisos para ver este archivo");
    }
    
    // Verificar si es una imagen (por extensión)
    $extension = strtolower(pathinfo($adjunto['nombre_archivo'], PATHINFO_EXTENSION));
    $extensiones_imagen = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
    
    if (!in_array($extension, $extensiones_imagen)) {
        // Si no es imagen, redirigir a descarga
        header('Location: descargar_adjunto.php?id=' . $adjunto_id);
        exit();
    }
    
    // Ruta completa del archivo
     $ruta_base_adjuntos = "/opt/lampp/htdocs/sistema_csi/adjuntos/";
    $ruta_completa_archivo = $ruta_base_adjuntos . $adjunto['ruta_archivo'];
    
    // Verificar que el archivo existe
    if (!file_exists($ruta_completa_archivo)) {
        header('HTTP/1.0 404 Not Found');
        die("El archivo no existe en el servidor");
    }
    
    // Determinar el Content-Type apropiado
    $content_type = $adjunto['tipo_archivo'];
    
    // Si el tipo MIME es genético, determinar por extensión
    if ($content_type == 'application/octet-stream') {
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml'
        ];
        
        if (isset($mime_types[$extension])) {
            $content_type = $mime_types[$extension];
        }
    }
    
    // Configurar headers para visualización
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . filesize($ruta_completa_archivo));
    header('Cache-Control: public, max-age=86400'); // Cache por 24 horas
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
    
    // Enviar imagen
    readfile($ruta_completa_archivo);
    
    exit();
    
} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    die("Error al procesar la imagen");
}
?>
