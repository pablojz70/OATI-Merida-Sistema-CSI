<?php
/**
 * backup.php - Script de Backup Automático para Areas Operativas: Infraestructura - OATI
 * 
 * Uso desde línea de comandos:
   *   php /opt/lampp/htdocs/sistema_csi/backup.php
 * 
 * Para configurar cron (Linux):
   *   0 2 * * * php /opt/lampp/htdocs/sistema_csi/backup.php >> /var/log/csi_backup.log 2>&1
 * 
 * Para configurar Tarea Programada (Windows):
   *   path\to\php.exe path\to\sistema_csi\backup.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Caracas');

// Configuración
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_csi');
define('BACKUP_DIR', '/opt/lampp/htdocs/sistema_csi/backups');
define('LOG_FILE', '/opt/lampp/htdocs/sistema_csi/backups/backup.log');


// Incluir database si existe
$db_file = __DIR__ . '/config/database.php';
if (file_exists($db_file)) {
    require_once $db_file;
}

class BackupManager {
    private $backup_dir;
    private $log_file;
    private $conn;
    
    public function __construct() {
        $this->backup_dir = BACKUP_DIR;
        $this->log_file = LOG_FILE;
        $this->crearDirectorioBackup();
    }
    
    private function crearDirectorioBackup() {
        if (!file_exists($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }
    
    public function conectarDB() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (PDOException $e) {
            $this->log("ERROR: No se pudo conectar a MySQL - " . $e->getMessage());
            return false;
        }
    }
    
    public function log($mensaje) {
        $fecha = date('Y-m-d H:i:s');
        $log_mensaje = "[$fecha] $mensaje\n";
        file_put_contents($this->log_file, $log_mensaje, FILE_APPEND);
    }
    
    public function hacerBackup($opciones = []) {
        set_time_limit(300);
        $defaults = [
            'tipo' => 'completo',
            'incluir_adjuntos' => false,
            'comprimir' => true
        ];
        $config = array_merge($defaults, $opciones);
        
        $this->log("=== INICIANDO BACKUP ===");
        
        // Verificar conexión
        if (!$this->conectarDB()) {
            return false;
        }
        
        // Seleccionar base de datos
        try {
            $this->conn->exec("USE " . DB_NAME);
        } catch (PDOException $e) {
            $this->log("ERROR: No se puede usar la base de datos - " . $e->getMessage());
            return false;
        }
        
        // Generar nombre del archivo
        $fecha = date('Y-m-d_H-i-s');
        $tipo_nombre = $config['tipo'] === 'completo' ? 'FULL' : 'STRUCT';
        $extension = $config['comprimir'] ? '.sql.gz' : '.sql';
        $nombre_archivo = "backup_{$tipo_nombre}_{$fecha}{$extension}";
        $ruta_completa = $this->backup_dir . '/' . $nombre_archivo;
        
        $this->log("Base de datos: " . DB_NAME);
        $this->log("Tipo de backup: " . $config['tipo']);
        $this->log("Archivo: " . $nombre_archivo);
        
        try {
            // Obtener todas las tablas
            $tablas = [];
            $stmt = $this->conn->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tablas[] = $row[0];
            }
            
            $this->log("Tablas encontradas: " . count($tablas));
            
            // Iniciar captura del backup
            $output = "";
            $output .= "-- ============================================\n";
            $output .= "-- RESPALDO BASE DE DATOS: " . DB_NAME . "\n";
            $output .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- Tipo: " . $config['tipo'] . "\n";
            $output .= "-- ============================================\n\n";
            $output .= "SET NAMES utf8mb4;\n";
            $output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
            
            foreach ($tablas as $tabla) {
                $this->log("Procesando tabla: $tabla");
                
                // Obtener estructura
                $output .= "-- --------------------------------------------------\n";
                $output .= "-- Estructura de la tabla: $tabla\n";
                $output .= "-- --------------------------------------------------\n";
                
                $stmt_create = $this->conn->query("SHOW CREATE TABLE `$tabla`");
                $create = $stmt_create->fetch(PDO::FETCH_NUM);
                $output .= "DROP TABLE IF EXISTS `$tabla`;\n";
                $output .= $create[1] . ";\n\n";
                
                // Obtener datos si es backup completo
                if ($config['tipo'] === 'completo') {
                    $stmt_data = $this->conn->query("SELECT * FROM `$tabla`");
                    $columnas = $stmt_data->columnCount();
                    
                    if ($stmt_data->rowCount() > 0) {
                        $output .= "-- Datos de la tabla: $tabla\n";
                        
                        while ($fila = $stmt_data->fetch(PDO::FETCH_NUM)) {
                            $valores = [];
                            for ($i = 0; $i < $columnas; $i++) {
                                if ($fila[$i] === null) {
                                    $valores[] = "NULL";
                                } else {
                                    $valores[] = "'" . str_replace("'", "''", $fila[$i]) . "'";
                                }
                            }
                            $output .= "INSERT INTO `$tabla` VALUES (" . implode(", ", $valores) . ");\n";
                        }
                        $output .= "\n";
                    }
                }
            }
            
            $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            
            // Comprimir si está habilitado
            if ($config['comprimir']) {
                $this->log("Comprimiendo archivo...");
                $archivo_comprimido = gzopen($ruta_completa, 'w9');
                gzwrite($archivo_comprimido, $output);
                gzclose($archivo_comprimido);
            } else {
                file_put_contents($ruta_completa, $output);
            }
            
            // Incluir adjuntos si está habilitado
            if ($config['incluir_adjuntos']) {
                $this->log("Incluyendo archivos adjuntos...");
                $this->incluirAdjuntos($fecha);
            }
            
            // Obtener tamaño del archivo
            $tamano = filesize($ruta_completa);
            $tamano_mb = round($tamano / 1048576, 2);
            
            $this->log("Backup creado exitosamente!");
            $this->log("Tamaño: {$tamano_mb} MB");
            $this->log("Ruta: {$ruta_completa}");
            
            // Limpiar backups antiguos
            $this->limpiarBackupsAntiguos(30);
            
            $this->log("=== BACKUP COMPLETADO ===\n");
            
            // Limpiar backups antiguos, mantener solo últimos 10
            $this->limpiarBackupsPorCantidad(10);
            
            return [
                'success' => true,
                'archivo' => $nombre_archivo,
                'ruta' => $ruta_completa,
                'tamano' => $tamano_mb
            ];
            
        } catch (PDOException $e) {
            $this->log("ERROR: " . $e->getMessage());
            return false;
        }
    }
    
    private function incluirAdjuntos($fecha) {
        $adjuntos_dir = '/opt/lampp/htdocs/sistema_csi/adjuntos';
        $backup_adjuntos_dir = $this->backup_dir . '/adjuntos_' . $fecha;
        
        if (!file_exists($adjuntos_dir)) {
            $this->log("No se encontró directorio de adjuntos");
            return;
        }
        
        if (!file_exists($backup_adjuntos_dir)) {
            mkdir($backup_adjuntos_dir, 0755, true);
        }
        
        $this->copiarDirectorio($adjuntos_dir, $backup_adjuntos_dir);
        $this->log("Adjuntos copiados a: $backup_adjuntos_dir");
    }
    
    private function copiarDirectorio($origen, $destino) {
        if (!file_exists($destino)) {
            mkdir($destino, 0755, true);
        }
        
        $dir = opendir($origen);
        while ($archivo = readdir($dir)) {
            if ($archivo != '.' && $archivo != '..') {
                $ruta_origen = "$origen/$archivo";
                $ruta_destino = "$destino/$archivo";
                
                if (is_dir($ruta_origen)) {
                    $this->copiarDirectorio($ruta_origen, $ruta_destino);
                } else {
                    copy($ruta_origen, $ruta_destino);
                }
            }
        }
        closedir($dir);
    }
    
    public function limpiarBackupsAntiguos($dias = 30) {
        $this->log("Limpiando backups antiguos (más de $dias días)...");
        
        $archivos = glob($this->backup_dir . '/backup_*');
        $fecha_limite = strtotime("-{$dias} days");
        $eliminados = 0;
        
        foreach ($archivos as $archivo) {
            if (is_file($archivo)) {
                if (filemtime($archivo) < $fecha_limite) {
                    unlink($archivo);
                    $eliminados++;
                }
            }
        }
        
        // Limpiar carpetas de adjuntos antiguos
        $carpetas = glob($this->backup_dir . '/adjuntos_*');
        foreach ($carpetas as $carpeta) {
            if (is_dir($carpeta)) {
                if (filemtime($carpeta) < $fecha_limite) {
                    $this->eliminarDirectorio($carpeta);
                    $eliminados++;
                }
            }
        }
        
        $this->log("Backups eliminados: $eliminados");
    }
    
    public function limpiarBackupsPorCantidad($max = 10) {
        $backups = $this->listarBackups();
        if (count($backups) <= $max) return;
        
        $eliminados = 0;
        for ($i = $max; $i < count($backups); $i++) {
            if (file_exists($backups[$i]['ruta'])) {
                unlink($backups[$i]['ruta']);
                $eliminados++;
            }
        }
        $this->log("Backups eliminados por límite de $max: $eliminados");
    }
    
    private function eliminarDirectorio($dir) {
        if (!file_exists($dir)) return;
        
        $archivos = array_diff(scandir($dir), ['.', '..']);
        foreach ($archivos as $archivo) {
            $ruta = "$dir/$archivo";
            is_dir($ruta) ? $this->eliminarDirectorio($ruta) : unlink($ruta);
        }
        rmdir($dir);
    }
    
    public function listarBackups() {
        $archivos = glob($this->backup_dir . '/backup_*');
        $backups = [];
        
        foreach ($archivos as $archivo) {
            if (is_file($archivo)) {
                $backups[] = [
                    'nombre' => basename($archivo),
                    'ruta' => $archivo,
                    'tamano' => round(filesize($archivo) / 1048576, 2),
                    'fecha' => date('Y-m-d H:i:s', filemtime($archivo))
                ];
            }
        }
        
        usort($backups, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });
        
        return array_slice($backups, 0, 10);
    }
    
    public function obtenerUltimoBackup() {
        $backups = $this->listarBackups();
        return !empty($backups) ? $backups[0] : null;
    }
}

// Ejecutar si se llama directamente desde CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    $backup = new BackupManager();
    
    $opciones = [];
    if (isset($argv[1])) {
        if ($argv[1] === '--completo') {
            $opciones['tipo'] = 'completo';
        } elseif ($argv[1] === '--estructura') {
            $opciones['tipo'] = 'estructura';
        }
        
        if (in_array('--sin-comprimir', $argv)) {
            $opciones['comprimir'] = false;
        }
        
        if (in_array('--con-adjuntos', $argv)) {
            $opciones['incluir_adjuntos'] = true;
        }
    }
    
    $resultado = $backup->hacerBackup($opciones);
    
    if ($resultado) {
        exit(0);
    } else {
        exit(1);
    }
}
?>
