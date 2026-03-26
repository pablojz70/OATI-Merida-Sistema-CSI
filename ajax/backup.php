<?php
// ajax/backup.php
require_once '../config/session.php';
require_once '../config/database.php';
header('Content-Type: application/json');

// Verificar que el usuario sea administrador
if (($_SESSION['privilegio'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

global $conn;

// Obtener la acción a realizar
$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

// Inicializar respuesta
$respuesta = ['success' => false, 'message' => ''];

// Directorio de backups
$backup_dir = '../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

try {
    switch ($accion) {
        case 'crear':
            crearBackup();
            break;
            
        case 'descargar':
            descargarBackup();
            break;
            
        case 'restaurar':
            restaurarBackup();
            break;
            
        case 'eliminar':
            eliminarBackup();
            break;
            
        case 'subir_y_restaurar':
            subirYRestaurarBackup();
            break;
            
        case 'programar':
            guardarProgramacion();
            break;
            
        case 'listar':
            listarBackups();
            break;
            
        default:
            $respuesta['message'] = 'Acción no reconocida';
            break;
    }
} catch (Exception $e) {
    error_log('ajax/backup.php: ' . $e->getMessage());
    $respuesta['message'] = 'Error interno del sistema';
}

// Devolver respuesta JSON
echo json_encode($respuesta);

// ===== FUNCIONES PARA BACKUP =====

function crearBackup() {
    global $conn, $backup_dir, $respuesta;
    
    // Nombre del archivo con timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $nombre_archivo = "backup_csi_{$timestamp}.sql";
    $ruta_completa = $backup_dir . $nombre_archivo;
    
    // Obtener todas las tablas
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0] ?? null;
    }
    
    if (empty($tables)) {
        $respuesta['message'] = 'No se encontraron tablas en la base de datos';
        return;
    }
    
    // Crear archivo SQL
    $sql = "-- Backup del Sistema CSI\n";
    $sql .= "-- Generado: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Base de datos: " . DB_NAME . "\n\n";
    
    // Deshabilitar claves foráneas temporalmente
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    // Iterar por cada tabla
    foreach ($tables as $table) {
        if (empty($table)) {
            continue;
        }
        // Obtener estructura de la tabla
        $result = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $result->fetch(PDO::FETCH_NUM);
        $sql .= "\n-- --------------------------------------------------------\n";
        $sql .= "-- Estructura de tabla para `$table`\n";
        $sql .= "-- --------------------------------------------------------\n\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $row[1] . ";\n\n";
        
        // Obtener datos de la tabla
        $result = $conn->query("SELECT * FROM `$table`");
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $sql .= "-- Volcado de datos para la tabla `$table`\n\n";
            
            foreach ($rows as $row) {
                // Escapar valores
                $escaped_values = array_map(function($value) use ($conn) {
                    if ($value === null) return 'NULL';
                    return $conn->quote((string)$value);
                }, array_values($row));
                
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $escaped_values) . ");\n";
            }
            $sql .= "\n";
        }
    }
    
    // Rehabilitar claves foráneas
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Guardar archivo
    if (file_put_contents($ruta_completa, $sql) !== false) {
        // Comprimir opcionalmente
        if (function_exists('gzencode')) {
            $compressed = gzencode($sql, 9);
            $compressed_name = $ruta_completa . '.gz';
            file_put_contents($compressed_name, $compressed);
            
            // Eliminar el archivo sin comprimir si se comprimió exitosamente
            unlink($ruta_completa);
            $ruta_completa = $compressed_name;
            $nombre_archivo .= '.gz';
        }
        
        // Registrar en logs
        registrarLog('backup_creado', "Backup creado: $nombre_archivo");
        
        $respuesta['success'] = true;
        $respuesta['message'] = 'Backup creado exitosamente';
        $respuesta['nombre_archivo'] = $nombre_archivo;
        $respuesta['tamano'] = filesize($ruta_completa);
        $respuesta['tablas'] = count($tables);
        $respuesta['ruta'] = $ruta_completa;
    } else {
        $respuesta['message'] = 'Error al guardar el archivo de backup';
    }
}

function descargarBackup() {
    global $backup_dir, $respuesta;
    
    $archivo = $_GET['archivo'] ?? '';
    if (empty($archivo)) {
        $respuesta['message'] = 'Nombre de archivo no especificado';
        return;
    }
    
    $ruta_completa = $backup_dir . basename($archivo);
    
    if (!file_exists($ruta_completa)) {
        $respuesta['message'] = 'El archivo no existe';
        return;
    }
    
    // Forzar descarga
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($ruta_completa) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($ruta_completa));
    
    readfile($ruta_completa);
    exit();
}

function restaurarBackup() {
    global $conn, $backup_dir, $respuesta;
    
    $archivo = $_POST['archivo'] ?? '';
    if (empty($archivo)) {
        $respuesta['message'] = 'Nombre de archivo no especificado';
        return;
    }
    
    $ruta_completa = $backup_dir . basename($archivo);
    
    if (!file_exists($ruta_completa)) {
        $respuesta['message'] = 'El archivo no existe';
        return;
    }
    
    // Leer archivo SQL
    $sql = file_get_contents($ruta_completa);
    
    // Si está comprimido, descomprimir
    if (pathinfo($ruta_completa, PATHINFO_EXTENSION) == 'gz') {
        $sql = gzdecode($sql);
    }
    
    if (empty($sql)) {
        $respuesta['message'] = 'El archivo de backup está vacío o corrupto';
        return;
    }
    
    // Crear backup de seguridad antes de restaurar
    crearBackupSeguridad();
    
    // Deshabilitar claves foráneas temporalmente
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Ejecutar consultas SQL
    $queries = explode(';', $sql);
    $tablas_restauradas = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && $query != 'SET FOREIGN_KEY_CHECKS=0' && $query != 'SET FOREIGN_KEY_CHECKS=1') {
            if ($conn->query($query) === TRUE) {
                if (strpos($query, 'CREATE TABLE') !== false || strpos($query, 'INSERT INTO') !== false) {
                    $tablas_restauradas++;
                }
            }
        }
    }
    
    // Rehabilitar claves foráneas
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Registrar en logs
    registrarLog('backup_restaurado', "Backup restaurado: $archivo");
    
    $respuesta['success'] = true;
    $respuesta['message'] = 'Base de datos restaurada exitosamente';
    $respuesta['tablas'] = $tablas_restauradas;
}

function eliminarBackup() {
    global $backup_dir, $respuesta;
    
    $archivo = $_POST['archivo'] ?? '';
    if (empty($archivo)) {
        $respuesta['message'] = 'Nombre de archivo no especificado';
        return;
    }
    
    $ruta_completa = $backup_dir . basename($archivo);
    
    if (!file_exists($ruta_completa)) {
        $respuesta['message'] = 'El archivo no existe';
        return;
    }
    
    if (unlink($ruta_completa)) {
        // Registrar en logs
        registrarLog('backup_eliminado', "Backup eliminado: $archivo");
        
        $respuesta['success'] = true;
        $respuesta['message'] = 'Backup eliminado exitosamente';
    } else {
        $respuesta['message'] = 'Error al eliminar el archivo';
    }
}

function subirYRestaurarBackup() {
    global $conn, $backup_dir, $respuesta;
    
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $respuesta['message'] = 'Error al subir el archivo';
        return;
    }
    
    $archivo_temporal = $_FILES['archivo']['tmp_name'];
    $nombre_archivo = $_FILES['archivo']['name'];
    
    // Verificar extensión
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    if ($extension !== 'sql' && $extension !== 'gz') {
        $respuesta['message'] = 'Solo se permiten archivos SQL o SQL comprimidos (gz)';
        return;
    }
    
    // Mover archivo al directorio de backups
    $nuevo_nombre = 'uploaded_' . date('Y-m-d_H-i-s') . '_' . $nombre_archivo;
    $ruta_destino = $backup_dir . $nuevo_nombre;
    
    if (move_uploaded_file($archivo_temporal, $ruta_destino)) {
        // Restaurar el backup
        $sql = file_get_contents($ruta_destino);
        
        // Si está comprimido, descomprimir
        if ($extension == 'gz') {
            $sql = gzdecode($sql);
        }
        
        // Crear backup de seguridad
        crearBackupSeguridad();
        
        // Ejecutar SQL
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $queries = explode(';', $sql);
        $tablas_restauradas = 0;
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if ($conn->query($query)) {
                    if (strpos($query, 'CREATE TABLE') !== false || strpos($query, 'INSERT INTO') !== false) {
                        $tablas_restauradas++;
                    }
                }
            }
        }
        
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        // Registrar en logs
        registrarLog('backup_subido_restaurado', "Backup subido y restaurado: $nombre_archivo");
        
        $respuesta['success'] = true;
        $respuesta['message'] = 'Backup subido y restaurado exitosamente';
        $respuesta['tablas'] = $tablas_restauradas;
    } else {
        $respuesta['message'] = 'Error al mover el archivo subido';
    }
}

function guardarProgramacion() {
    global $respuesta;
    
    $frecuencia = $_POST['frecuencia'] ?? 'daily';
    $hora = $_POST['hora'] ?? '02:00';
    $retener = intval($_POST['retener'] ?? 30);
    
    // Crear configuración
    $config = [
        'frecuencia' => $frecuencia,
        'hora' => $hora,
        'retener_dias' => $retener,
        'activo' => true,
        'ultima_ejecucion' => null,
        'proxima_ejecucion' => calcularProximaEjecucion($frecuencia, $hora)
    ];
    
    // Guardar en archivo de configuración
    $config_file = '../config/backup_config.json';
    if (file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT))) {
        // Registrar en logs
        registrarLog('backup_programado', "Backup programado: $frecuencia a las $hora");
        
        $respuesta['success'] = true;
        $respuesta['message'] = 'Programación guardada exitosamente';
        $respuesta['config'] = $config;
    } else {
        $respuesta['message'] = 'Error al guardar la configuración';
    }
}

function listarBackups() {
    global $backup_dir, $respuesta;
    
    $backups = [];
    $archivos = scandir($backup_dir);
    
    foreach ($archivos as $archivo) {
        if ($archivo != '.' && $archivo != '..') {
            $ruta = $backup_dir . $archivo;
            $backups[] = [
                'nombre' => $archivo,
                'tamano' => filesize($ruta),
                'fecha' => date('Y-m-d H:i:s', filemtime($ruta)),
                'ruta' => $ruta
            ];
        }
    }
    
    // Ordenar por fecha (más reciente primero)
    usort($backups, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
    
    $respuesta['success'] = true;
    $respuesta['backups'] = $backups;
    $respuesta['total'] = count($backups);
}

// ===== FUNCIONES AUXILIARES =====

function crearBackupSeguridad() {
    global $backup_dir;
    
    // Crear un backup rápido antes de restaurar
    $timestamp = date('Y-m-d_H-i-s');
    $nombre_seguridad = "seguridad_pre_restauracion_{$timestamp}.sql";
    crearBackup(); // Reutilizar función principal
}

function calcularProximaEjecucion($frecuencia, $hora) {
    $hora_parts = explode(':', $hora);
    $hora_num = intval($hora_parts[0]);
    $minuto_num = intval($hora_parts[1]);
    
    $proxima = new DateTime('tomorrow');
    $proxima->setTime($hora_num, $minuto_num);
    
    switch ($frecuencia) {
        case 'weekly':
            // Próximo lunes
            $proxima->modify('next monday');
            break;
        case 'monthly':
            // Primer día del próximo mes
            $proxima->modify('first day of next month');
            break;
        // daily ya está configurado para mañana
    }
    
    return $proxima->format('Y-m-d H:i:s');
}

function registrarLog($accion, $detalles) {
    global $conn;
    
    $usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO logs_sistema (usuario_id, accion, detalles, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $accion, $detalles, $ip, $user_agent]);
}
?>
