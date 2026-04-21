<?php
// admin_backup.php - Gestión de Backups (Completamente reescrito con PDO y Bootstrap)
session_start();

// Verificar autenticación
if (!isset($_SESSION['privilegio']) || $_SESSION['privilegio'] != 'admin') {
    header('Location: index.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? null;
$privilegio = $_SESSION['privilegio'];
$usuario_nombre = $_SESSION['nombre'] ?? 'Administrador';

// Conexión PDO
try {
    $conn = new PDO("mysql:host=localhost;dbname=sistema_csi;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Incluir BackupManager
require_once __DIR__ . '/backup.php';
$backupManager = new BackupManager();

// Procesar acciones
$mensaje = '';
$tipo_mensaje = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear_backup':
                $opciones = [
                    'tipo' => $_POST['tipo_backup'] ?? 'completo',
                    'incluir_adjuntos' => isset($_POST['incluir_adjuntos']),
                    'comprimir' => true
                ];
                
                $resultado = $backupManager->hacerBackup($opciones);
                
                if ($resultado) {
                    $mensaje = "Backup creado exitosamente: " . $resultado['archivo'] . " (" . $resultado['tamano'] . " MB)";
                } else {
                    $mensaje = "Error al crear el backup";
                    $tipo_mensaje = 'error';
                }
                break;
                
            case 'guardar_config':
                $frecuencia = $_POST['frecuencia'] ?? 'diaria';
                $hora = $_POST['hora_ejecucion'] ?? '02:00';
                $dia_semana = $_POST['dia_semana'] ?? 'lunes';
                $retencion = intval($_POST['retencion_dias'] ?? 30);
                
                $sql = "UPDATE BackupConfig SET 
                        frecuencia = :frecuencia,
                        hora_ejecucion = :hora,
                        dia_semana = :dia_semana,
                        retencion_dias = :retencion,
                        activo = :activo,
                        updated_at = NOW()
                        WHERE id = 1";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':frecuencia' => $frecuencia,
                    ':hora' => $hora . ':00',
                    ':dia_semana' => $dia_semana,
                    ':retencion' => $retencion,
                    ':activo' => isset($_POST['backup_activo']) ? 1 : 0
                ]);
                
                $mensaje = "Configuración guardada exitosamente";
                break;
                
            case 'eliminar_backup':
                $archivo = $_POST['archivo'] ?? '';
                $ruta = '/opt/lampp/htdocs/sistema_csi/backups/' . $archivo;
                
                if (file_exists($ruta)) {
                    unlink($ruta);
                    $mensaje = "Backup eliminado: $archivo";
                } else {
                    $mensaje = "El archivo no existe";
                    $tipo_mensaje = 'error';
                }
                break;
        }
    }
}

// Obtener configuración actual
$config = null;
try {
    $stmt = $conn->query("SELECT * FROM BackupConfig WHERE id = 1");
    $config = $stmt->fetch();
} catch (Exception $e) {
    // Crear configuración por defecto
    $conn->exec("INSERT INTO BackupConfig (id, frecuencia, hora_ejecucion, activo) VALUES (1, 'diaria', '02:00:00', 1)");
    $config = ['frecuencia' => 'diaria', 'hora_ejecucion' => '02:00:00', 'activo' => 1, 'retencion_dias' => 30, 'dia_semana' => 'lunes'];
}

// Obtener lista de backups
$backups = $backupManager->listarBackups();
$ultimo_backup = $backupManager->obtenerUltimoBackup();

// Obtener estadísticas de la base de datos
$stats = [];
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM Tickets");
    $stats['tickets'] = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM Usuarios");
    $stats['usuarios'] = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM TicketAdjuntos");
    $stats['adjuntos'] = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT SUM(tamano_bytes) as total FROM TicketAdjuntos");
    $stats['tamano_adjuntos'] = round(($stmt->fetch()['total'] ?? 0) / 1048576, 2);
} catch (Exception $e) {
    $stats = ['tickets' => 0, 'usuarios' => 0, 'adjuntos' => 0, 'tamano_adjuntos' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup - Sistema CSI</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/estilos2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .backup-container {
            margin-left: 190px;
            padding: 15px;
            min-height: calc(100vh - 70px);
            background: #f8fafc;
        }
        
        @media (max-width: 768px) {
            .backup-container {
                margin-left: 0 !important;
                padding: 10px !important;
            }
        }
        
        .page-header {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .page-header h1 {
            font-size: 20px;
            color: #1a2980;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-header h1 i {
            color: #3498db;
        }
        
        .page-header p {
            color: #666;
            margin: 5px 0 0 34px;
            font-size: 13px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eef2f7;
        }
        
        .card-header {
            font-size: 15px;
            font-weight: 600;
            color: #1a2980;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header i {
            color: #3498db;
        }
        
        /* ESTADÍSTICAS UNIFORMES */
        .stats-usuarios {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .stat-usuario {
            background: white;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-top: 3px solid;
            transition: transform 0.2s;
        }
        
        .stat-usuario:hover {
            transform: translateY(-3px);
        }
        
        .stat-usuario.total { border-color: #1a2980; }
        .stat-usuario.green { border-color: #27ae60; }
        .stat-usuario.orange { border-color: #f39c12; }
        .stat-usuario.blue { border-color: #3498db; }
        
        .stat-numero {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            display: block;
        }
        
        .stat-label {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .stat-link:hover .stat-usuario {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        
        .stat-link .stat-usuario {
            transition: all 0.2s ease;
        }
        
        .btn-backup {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-backup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(17, 153, 142, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-group input {
            width: 18px;
            height: 18px;
        }
        
        .table-backups {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-backups th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
        }
        
        .table-backups td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .table-backups tr:hover {
            background: #f8f9fa;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .ultimo-backup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .ultimo-backup h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .ultimo-backup .info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .ultimo-backup .info-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .cron-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
            font-family: monospace;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .dos-columnas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 900px) {
            .dos-columnas {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="top-header">
        <div class="logo-oati">
            <img src="imagen/oati.png" alt="Logo OATI" class="logo-oati-img">
            <div class="system-titles-custom">
                <h1 class="system-name-custom">Centro de Soporte Informático</h1>
                <p class="system-sub-custom">Administración - Backup</p>
            </div>
        </div>
        
        <div class="user-header-info-custom">
            <div class="user-details-custom">
                <span class="user-name-custom"><?php echo htmlspecialchars($usuario_nombre); ?></span>
                <span class="user-role-custom">Administrador</span>
            </div>
            <a href="logout.php" class="logout-btn-custom">
                <img src="imagen/Salir.png" alt="Salir" class="logout-img">
                <span class="logout-text">Salir</span>
            </a>
        </div>
    </header>
    
    <div class="main-wrapper">
        <!-- MENÚ -->
        <?php include 'includes/menu_admin.php'; ?>
        
        <!-- CONTENIDO PRINCIPAL -->
        <main class="backup-container">
            <!-- ENCABEZADO -->
            <div class="page-header">
                <h1><i class="fas fa-database"></i> Gestión de Backups</h1>
                <p>Realiza y programa copias de seguridad de la base de datos</p>
            </div>
            
            <!-- MENSAJE -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>
            
            <!-- ÚLTIMO BACKUP -->
            <?php if ($ultimo_backup): ?>
            <div class="ultimo-backup">
                <h4><i class="fas fa-clock"></i> Último Backup Realizado</h4>
                <div class="info">
                    <div class="info-item">
                        <i class="fas fa-file"></i>
                        <span><?php echo $ultimo_backup['nombre']; ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('d/m/Y H:i', strtotime($ultimo_backup['fecha'])); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-hdd"></i>
                        <span><?php echo $ultimo_backup['tamano']; ?> MB</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ESTADÍSTICAS -->
            <div class="stats-usuarios">
                <a href="todos_tickets.php?estado=todos" class="stat-link">
                    <div class="stat-usuario total">
                        <span class="stat-numero"><?php echo number_format($stats['tickets']); ?></span>
                        <span class="stat-label">Tickets Totales</span>
                    </div>
                </a>
                <a href="admin_usuarios.php" class="stat-link">
                    <div class="stat-usuario green">
                        <span class="stat-numero"><?php echo number_format($stats['usuarios']); ?></span>
                        <span class="stat-label">Usuarios</span>
                    </div>
                </a>
                <div class="stat-usuario orange">
                    <span class="stat-numero"><?php echo number_format($stats['adjuntos']); ?></span>
                    <span class="stat-label">Archivos Adjuntos</span>
                </div>
                <div class="stat-usuario blue">
                    <span class="stat-numero"><?php echo $stats['tamano_adjuntos']; ?> MB</span>
                    <span class="stat-label">Tamaño Adjuntos</span>
                </div>
            </div>
            
            <div class="dos-columnas">
                <!-- CREAR BACKUP MANUAL -->
                <div class="card">
                    <div class="card-header">
                        <img src="imagen/descarga.png" alt="Backup" style="width:18px;height:18px;"> Crear Backup Manual
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="accion" value="crear_backup">
                        
                        <div class="form-group">
                            <label>Tipo de Backup:</label>
                            <select name="tipo_backup" class="form-control">
                                <option value="completo">Completo (con datos)</option>
                                <option value="estructura">Solo Estructura</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="incluir_adjuntos" id="incluir_adjuntos" value="1">
                                <label for="incluir_adjuntos">Incluir archivos adjuntos</label>
                            </div>
                            <small style="color: #666; font-size: 11px;">Nota: Los adjuntos aumentan significativamente el tamaño del backup</small>
                        </div>
                        
                        <button type="submit" class="btn-backup">
                            <i class="fas fa-save"></i> Crear Backup Ahora
                        </button>
                    </form>
                </div>
                
                <!-- CONFIGURACIÓN PROGRAMADA -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-cog"></i> Configuración Programada
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="accion" value="guardar_config">
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="backup_activo" id="backup_activo" value="1" 
                                       <?php echo (isset($config['activo']) && $config['activo']) ? 'checked' : ''; ?>>
                                <label for="backup_activo">Habilitar backup automático</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Frecuencia:</label>
                            <select name="frecuencia" class="form-control" id="frecuencia-select">
                                <option value="diaria" <?php echo ($config['frecuencia'] ?? '') === 'diaria' ? 'selected' : ''; ?>>Diaria</option>
                                <option value="semanal" <?php echo ($config['frecuencia'] ?? '') === 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                                <option value="mensual" <?php echo ($config['frecuencia'] ?? '') === 'mensual' ? 'selected' : ''; ?>>Mensual</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="dia-semana-group" style="display: <?php echo ($config['frecuencia'] ?? '') === 'semanal' ? 'block' : 'none'; ?>;">
                            <label>Día de la semana:</label>
                            <select name="dia_semana" class="form-control">
                                <option value="lunes" <?php echo ($config['dia_semana'] ?? '') === 'lunes' ? 'selected' : ''; ?>>Lunes</option>
                                <option value="martes" <?php echo ($config['dia_semana'] ?? '') === 'martes' ? 'selected' : ''; ?>>Martes</option>
                                <option value="miercoles" <?php echo ($config['dia_semana'] ?? '') === 'miercoles' ? 'selected' : ''; ?>>Miércoles</option>
                                <option value="jueves" <?php echo ($config['dia_semana'] ?? '') === 'jueves' ? 'selected' : ''; ?>>Jueves</option>
                                <option value="viernes" <?php echo ($config['dia_semana'] ?? '') === 'viernes' ? 'selected' : ''; ?>>Viernes</option>
                                <option value="sabado" <?php echo ($config['dia_semana'] ?? '') === 'sabado' ? 'selected' : ''; ?>>Sábado</option>
                                <option value="domingo" <?php echo ($config['dia_semana'] ?? '') === 'domingo' ? 'selected' : ''; ?>>Domingo</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Hora de ejecución:</label>
                            <input type="time" name="hora_ejecucion" class="form-control" 
                                   value="<?php echo substr($config['hora_ejecucion'] ?? '02:00', 0, 5); ?>">
                            <small style="color: #666; font-size: 11px;">Se recomienda ejecutar en horas de baja actividad</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Retención de backups (días):</label>
                            <input type="number" name="retencion_dias" class="form-control" 
                                   value="<?php echo $config['retencion_dias'] ?? 30; ?>" min="1" max="365">
                            <small style="color: #666; font-size: 11px;">Los backups más antiguos se eliminarán automáticamente</small>
                        </div>
                        
                        <button type="submit" class="btn-backup">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- LISTA DE BACKUPS -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Historial de Backups
                </div>
                
                <?php if (empty($backups)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">
                        <i class="fas fa-folder-open" style="font-size: 32px; opacity: 0.3;"></i><br><br>
                        No hay backups realizados aún
                    </p>
                <?php else: ?>
                    <table class="table-backups">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th>Fecha</th>
                                <th>Tamaño</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-file-archive" style="color: #f39c12;"></i>
                                    <?php echo $backup['nombre']; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($backup['fecha'])); ?></td>
                                <td><?php echo $backup['tamano']; ?> MB</td>
                                <td style="white-space: nowrap;">
                                    <a href="descargar_backup.php?archivo=<?php echo urlencode($backup['nombre']); ?>" 
                                       class="btn-secondary" style="text-decoration: none; height: 28px; line-height: 16px; display: inline-flex; align-items: center; justify-content: center;" title="Descargar backup">
                                        <img src="imagen/descarga.png" alt="Descargar" style="width:18px;height:18px;">
                                    </a>
                                    <form method="POST" style="display: inline; margin-left: 5px;" 
                                          onsubmit="return confirm('¿Eliminar este backup?');">
                                        <input type="hidden" name="accion" value="eliminar_backup">
                                        <input type="hidden" name="archivo" value="<?php echo htmlspecialchars($backup['nombre']); ?>">
                                        <button type="submit" class="btn-danger" style="height: 28px; line-height: 1; display: inline-flex; align-items: center; justify-content: center;" title="Eliminar backup">
                                            <img src="imagen/borrar.png" alt="Eliminar" style="width:18px;height:18px;">
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Mostrar/ocultar selector de día según frecuencia
        document.getElementById('frecuencia-select').addEventListener('change', function() {
            var diaGroup = document.getElementById('dia-semana-group');
            if (this.value === 'semanal') {
                diaGroup.style.display = 'block';
            } else {
                diaGroup.style.display = 'none';
            }
        });
    </script>
</body>
</html>
