<?php
// cron/backup_automatico.php
require_once '../config/database.php';

// Verificar que sea ejecutado desde línea de comandos o cron
if (php_sapi_name() !== 'cli' && !isset($_GET['cron'])) {
    die('Acceso no permitido');
}

// Configuración
$config_file = '../config/backup_config.json';
$backup_dir = '../backups/';
$log_file = '../logs/backup_cron.log';

// Crear directorios si no existen
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}
if (!file_exists(dirname($log_file))) {
    mkdir(dirname($log_file), 0755, true);
}

// Función para registrar en log
function registrarLogCron($mensaje) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $linea = "[$timestamp] $mensaje\n";
    file_put_contents($log_file, $linea, FILE_APPEND);
    echo $linea;
}

// Iniciar proceso
registrarLogCron("=== INICIANDO BACKUP AUTOMÁTICO ===");

// Verificar configuración
if (!file_exists($config_file)) {
    registrarLogCron("ERROR: Archivo de configuración no encontrado");
    exit(1);
}

$config = json_decode(file_get_contents($config_file), true);
if (!$config || !$config['activo']) {
    registrarLogCron("INFO: Backups automáticos desactivados");
    exit(0);
}

// Verificar si es hora de ejecutar
$hora_actual = date('H:i');
$hora_programada = $config['hora'];

// Para pruebas, permitir ejecución manual
$ejecutar_ahora = isset($_GET['force']) || 
                  ($hora_actual == $hora_programada && 
                   verificarFrecuencia($config['frecuencia']));

if (!$ejecutar_ahora) {
    registrarLogCron("INFO: No es hora de ejecutar el backup");
    exit(0);
}

// Conectar a la base de datos
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    registrarLogCron("INFO: Conectado a la base de datos");
    
    // Crear backup
    $timestamp = date('Y-m-d_H-i-s');
    $nombre_archivo = "auto_backup_{$timestamp}.sql";
    $ruta_completa = $backup_dir . $nombre_archivo;
    
    // Obtener todas las tablas
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    registrarLogCron("INFO: Exportando " . count($tables) . " tablas");
    
    // Crear archivo SQL
    $sql = "-- Backup automático del Areas Operativas: Infraestructura - OATI\n";
    $sql .= "-- Generado automáticamente: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Frecuencia: " . $config['frecuencia'] . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    $tablas_exportadas = 0;
    $registros_exportados = 0;
    
    foreach ($tables as $table) {
        // Estructura de la tabla
        $result = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $result->fetch_row();
        $sql .= "\n-- Estructura: $table\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $row[1] . ";\n\n";
        
        // Datos de la tabla
        $result = $conn->query("SELECT * FROM `$table`");
        $registros = $result->num_rows;
        
        if ($registros > 0) {
            $sql .= "-- Datos: $table ($registros registros)\n";
            
            while ($row = $result->fetch_assoc()) {
                $escaped_values = array_map(function($value) use ($conn) {
                    if ($value === null) return 'NULL';
                    return "'" . $conn->real_escape_string($value) . "'";
                }, array_values($row));
                
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $escaped_values) . ");\n";
                $registros_exportados++;
            }
            $sql .= "\n";
        }
        
        $tablas_exportadas++;
        registrarLogCron("INFO: Tabla exportada: $table ($registros registros)");
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Guardar archivo
    if (file_put_contents($ruta_completa, $sql) !== false) {
        $tamano = filesize($ruta_completa);
        
        // Comprimir si está configurado
        if (isset($config['comprimir']) && $config['comprimir'] && function_exists('gzencode')) {
            $compressed = gzencode($sql, 9);
            $compressed_name = $ruta_completa . '.gz';
            
            if (file_put_contents($compressed_name, $compressed) !== false) {
                unlink($ruta_completa);
                $ruta_completa = $compressed_name;
                $nombre_archivo .= '.gz';
                $tamano = filesize($ruta_completa);
                registrarLogCron("INFO: Backup comprimido: " . formatBytes($tamano));
            }
        }
        
        registrarLogCron("SUCCESS: Backup creado: $nombre_archivo (" . formatBytes($tamano) . ")");
        
        // Limpiar backups antiguos
        $dias_a_retener = $config['retener_dias'] ?? 30;
        if ($dias_a_retener > 0) {
            limpiarBackupsAntiguos($backup_dir, $dias_a_retener);
        }
        
        // Actualizar configuración
        $config['ultima_ejecucion'] = date('Y-m-d H:i:s');
        $config['proxima_ejecucion'] = calcularProximaEjecucionCron($config['frecuencia'], $config['hora']);
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        
        // Registrar en la base de datos
        $detalles = "Backup automático creado: $nombre_archivo - Tablas: $tablas_exportadas, Registros: $registros_exportados";
        $stmt = $conn->prepare("INSERT INTO logs_sistema (usuario_id, accion, detalles) VALUES (0, 'backup_automatico', ?)");
        $stmt->bind_param("s", $detalles);
        $stmt->execute();
        
        // Enviar notificación por email si está configurado
        if (isset($config['notificar']) && $config['notificar']) {
            enviarNotificacionEmail($nombre_archivo, $tamano, $tablas_exportadas, $registros_exportados);
        }
        
        registrarLogCron("SUCCESS: Proceso de backup completado exitosamente");
        
    } else {
        registrarLogCron("ERROR: No se pudo guardar el archivo de backup");
        exit(1);
    }
    
} catch (Exception $e) {
    registrarLogCron("ERROR: " . $e->getMessage());
    exit(1);
}

// ===== FUNCIONES AUXILIARES =====

function verificarFrecuencia($frecuencia) {
    switch ($frecuencia) {
        case 'daily':
            return true; // Diario, siempre ejecutar si es la hora correcta
            
        case 'weekly':
            // Ejecutar los lunes
            return date('N') == 1; // 1 = lunes
            
        case 'monthly':
            // Ejecutar el primer día del mes
            return date('j') == 1;
            
        default:
            return false;
    }
}

function calcularProximaEjecucionCron($frecuencia, $hora) {
    $hora_parts = explode(':', $hora);
    $hora_num = intval($hora_parts[0]);
    $minuto_num = intval($hora_parts[1]);
    
    $proxima = new DateTime('tomorrow');
    $proxima->setTime($hora_num, $minuto_num);
    
    switch ($frecuencia) {
        case 'weekly':
            // Próximo lunes
            if (date('N') != 1) { // Si hoy no es lunes
                $proxima->modify('next monday');
            }
            break;
            
        case 'monthly':
            // Primer día del próximo mes
            if (date('j') != 1) { // Si hoy no es el primer día del mes
                $proxima->modify('first day of next month');
            }
            break;
    }
    
    return $proxima->format('Y-m-d H:i:s');
}

function limpiarBackupsAntiguos($backup_dir, $dias) {
    $archivos = scandir($backup_dir);
    $limite = strtotime("-$dias days");
    $eliminados = 0;
    
    foreach ($archivos as $archivo) {
        if ($archivo != '.' && $archivo != '..' && 
            (strpos($archivo, '.sql') !== false || strpos($archivo, '.gz') !== false)) {
            
            $ruta = $backup_dir . $archivo;
            $modificacion = filemtime($ruta);
            
            if ($modificacion < $limite) {
                if (unlink($ruta)) {
                    registrarLogCron("INFO: Backup antiguo eliminado: $archivo");
                    $eliminados++;
                }
            }
        }
    }
    
    if ($eliminados > 0) {
        registrarLogCron("INFO: Se eliminaron $eliminados backups antiguos");
    }
    
    return $eliminados;
}

function formatBytes($bytes, $decimals = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$units[$factor];
}

function enviarNotificacionEmail($nombre_archivo, $tamano, $tablas, $registros) {
    // Configuración del email
    $para = 'admin@dominio.com'; // Cambiar por email real
    $asunto = 'Backup Automático Completado - Areas Operativas: Infraestructura - OATI';
    
    $mensaje = "
    <html>
    <head>
        <title>Backup Automático Completado</title>
    </head>
    <body>
        <h2>Backup Automático del Areas Operativas: Infraestructura - OATI</h2>
        <p>Se ha completado exitosamente el backup automático de la base de datos.</p>
        
        <h3>Detalles del Backup:</h3>
        <ul>
            <li><strong>Archivo:</strong> $nombre_archivo</li>
            <li><strong>Tamaño:</strong> " . formatBytes($tamano) . "</li>
            <li><strong>Tablas exportadas:</strong> $tablas</li>
            <li><strong>Registros exportados:</strong> $registros</li>
            <li><strong>Fecha y hora:</strong> " . date('d/m/Y H:i:s') . "</li>
        </ul>
        
        <p>Este backup se ha almacenado en el directorio de backups del sistema.</p>
        
        <hr>
        <p style='color: #777; font-size: 12px;'>
            Este es un mensaje automático del Sistema de Control de Soporte Informático (CSI).<br>
            No responda a este correo.
        </p>
    </body>
    </html>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: Areas Operativas: Infraestructura - OATI <no-reply@dominio.com>',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // En producción, descomentar para enviar email real
    // mail($para, $asunto, $mensaje, implode("\r\n", $headers));
    
    registrarLogCron("INFO: Notificación por email preparada (simulada)");
}
?>
