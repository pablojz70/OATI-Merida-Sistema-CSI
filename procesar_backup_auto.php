<?php
session_start();

// Verificar autenticación y privilegios
if (!isset($_SESSION['usuario_id']) || $_SESSION['privilegio'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Procesar acción de backup automático
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
    if ($accion === 'activar_auto') {
        // Crear directorio de backups si no existe
        $backup_dir = 'backups/';
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Crear archivo de configuración para cron
        $config_content = "<?php
// Configuración de Backups Automáticos - Sistema CSI
return [
    'frecuencia' => 'semanal',
    'hora' => '02:00',
    'dias_guardar' => 30,
    'ultimo_backup' => null,
    'activado' => true
];
?>";
        
        file_put_contents($backup_dir . 'config_backup.php', $config_content);
        
        // Crear script de backup automático
        $script_content = '#!/bin/bash
# Script de Backup Automático - Sistema CSI
FECHA=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILE="' . realpath($backup_dir) . '/backup_auto_${FECHA}.sql"

# Crear backup
mysqldump --user=root --password= --host=localhost sistema_csi > "${BACKUP_FILE}"

# Comprimir backup
gzip "${BACKUP_FILE}"

# Eliminar backups antiguos (más de 30 días)
find ' . realpath($backup_dir) . ' -name "backup_auto_*.sql.gz" -mtime +30 -delete

echo "Backup automático completado: ${BACKUP_FILE}.gz"
';
        
        file_put_contents($backup_dir . 'backup_auto.sh', $script_content);
        chmod($backup_dir . 'backup_auto.sh', 0755);
        
        echo json_encode(['success' => true, 'message' => 'Backup automático configurado']);
    }
}
?>
