<?php
// includes/functions.php - VERSIÓN CORREGIDA

function tableExists($conn, $tableName) {
    try {
        $stmt = $conn->prepare("SELECT 1 FROM `$tableName` LIMIT 1");
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getAdminStats($conn) {
    if (!tableExists($conn, 'Tickets') || !tableExists($conn, 'Usuarios')) {
        return [
            'total_usuarios' => '0',
            'total_tickets' => '0',
            'tickets_nuevos' => '0',
            'tickets_proceso' => '0'
        ];
    }
    
    $query = "SELECT 
        (SELECT COUNT(*) FROM Usuarios WHERE activo = 1) as total_usuarios,
        (SELECT COUNT(*) FROM Tickets) as total_tickets,
        (SELECT COUNT(*) FROM Tickets WHERE estado = 'Nuevo') as tickets_nuevos,
        (SELECT COUNT(*) FROM Tickets WHERE estado = 'Asignado') as tickets_asignados,
        (SELECT COUNT(*) FROM Tickets WHERE estado = 'Cerrado Exitosamente') as tickets_cerrados";
    
    $stmt = $conn->query($query);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getTecnicoStats($conn, $tecnico_id) {
    if (!tableExists($conn, 'Tickets')) {
        return ['tickets_asignados' => '0', 'tickets_resueltos' => '0'];
    }
    
    $query = "SELECT 
        COUNT(CASE WHEN estado IN ('Asignado', 'En Proceso') THEN 1 END) as tickets_asignados,
        COUNT(CASE WHEN estado = 'Cerrado Exitosamente' THEN 1 END) as tickets_resueltos
        FROM Tickets 
        WHERE tecnico_asignado = :tecnico_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':tecnico_id' => $tecnico_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUsuarioStats($conn, $usuario_id) {
    if (!tableExists($conn, 'Tickets')) {
        return ['mis_tickets' => '0', 'tickets_abiertos' => '0'];
    }
    
    $query = "SELECT 
        COUNT(*) as mis_tickets,
        COUNT(CASE WHEN estado NOT IN ('Cerrado Exitosamente', 'Cerrado No Exitoso') THEN 1 END) as tickets_abiertos
        FROM Tickets 
        WHERE usuario_id = :usuario_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':usuario_id' => $usuario_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getRecentTickets($conn, $user_id, $privilegio, $limit = 5) {
    if (!tableExists($conn, 'Tickets')) {
        return [];
    }
    
    try {
        if ($privilegio == 'admin') {
            $query = "SELECT t.*, u.nombre as usuario_nombre, d.nombre as dependencia_nombre
                     FROM Tickets t
                     LEFT JOIN Usuarios u ON t.usuario_id = u.id
                     LEFT JOIN Dependencias d ON t.dependencia_id = d.id
                     ORDER BY t.fecha_creacion DESC LIMIT :limit";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($privilegio == 'tecnico') {
            $query = "SELECT t.*, u.nombre as usuario_nombre, d.nombre as dependencia_nombre
                     FROM Tickets t
                     LEFT JOIN Usuarios u ON t.usuario_id = u.id
                     LEFT JOIN Dependencias d ON t.dependencia_id = d.id
                     WHERE t.tecnico_asignado = :user_id 
                     ORDER BY t.fecha_creacion DESC LIMIT :limit";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $query = "SELECT t.*, u.nombre as usuario_nombre, d.nombre as dependencia_nombre
                     FROM Tickets t
                     LEFT JOIN Usuarios u ON t.usuario_id = u.id
                     LEFT JOIN Dependencias d ON t.dependencia_id = d.id
                     WHERE t.usuario_id = :user_id 
                     ORDER BY t.fecha_creacion DESC LIMIT :limit";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en getRecentTickets: " . $e->getMessage());
        return [];
    }
}

function getEstadoColor($estado) {
    $colors = [
        'Nuevo' => '#e3f2fd',
        'Asignado' => '#fff3e0', 
        'En Proceso' => '#f3e5f5',
        'Cerrado Exitosamente' => '#e8f5e9',
        'Cerrado No Exitoso' => '#ffebee'
    ];
    return $colors[$estado] ?? '#f5f5f5';
}

function getPrioridadColor($prioridad) {
    $colors = [
        'baja' => '#d4edda',
        'media' => '#fff3cd',
        'alta' => '#ffe5d0',
        'urgente' => '#f8d7da'
    ];
    return $colors[$prioridad] ?? '#f5f5f5';
}

// Obtener nombre del área
function getAreaNombre($conn, $area_id) {
    if (!$area_id) return 'No asignada';
    
    try {
        $stmt = $conn->prepare("SELECT nombre FROM AreasSoporte WHERE id = :id");
        $stmt->execute([':id' => $area_id]);
        $area = $stmt->fetch(PDO::FETCH_ASSOC);
        return $area ? $area['nombre'] : 'Desconocida';
    } catch (Exception $e) {
        return 'Error';
    }
}

// Obtener nombre del servicio
function getServicioNombre($conn, $servicio_id) {
    if (!$servicio_id) return 'No asignado';
    
    try {
        $stmt = $conn->prepare("SELECT nombre FROM Servicios WHERE id = :id");
        $stmt->execute([':id' => $servicio_id]);
        $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
        return $servicio ? $servicio['nombre'] : 'Desconocido';
    } catch (Exception $e) {
        return 'Error';
    }
}

// Obtener nombre de dependencia
function getDependenciaNombre($conn, $dependencia_id) {
    if (!$dependencia_id) return 'No asignada';
    
    try {
        $stmt = $conn->prepare("SELECT nombre FROM Dependencias WHERE id = :id");
        $stmt->execute([':id' => $dependencia_id]);
        $dependencia = $stmt->fetch(PDO::FETCH_ASSOC);
        return $dependencia ? $dependencia['nombre'] : 'Desconocida';
    } catch (Exception $e) {
        return 'Error';
    }
}

// Obtener nombre de usuario
function getUsuarioNombre($conn, $usuario_id) {
    if (!$usuario_id) return 'No asignado';
    
    try {
        $stmt = $conn->prepare("SELECT nombre FROM Usuarios WHERE id = :id");
        $stmt->execute([':id' => $usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        return $usuario ? $usuario['nombre'] : 'Desconocido';
    } catch (Exception $e) {
        return 'Error';
    }
}

// Normalizar ID de usuario de sesión
function getUserIdFromSession() {
    // Compatibilidad con ambos nombres de variable
    if (isset($_SESSION['usuario_id'])) {
        return $_SESSION['usuario_id'];
    } elseif (isset($_SESSION['id_usuario'])) {
        return $_SESSION['id_usuario'];
    }
    return null;
}

// Verificar privilegios
function checkPrivilege($required) {
    $privilegio = $_SESSION['privilegio'] ?? 'usuario';
    $hierarchy = ['usuario' => 1, 'tecnico' => 2, 'admin' => 3];
    
    $user_level = $hierarchy[$privilegio] ?? 0;
    $required_level = $hierarchy[$required] ?? 0;
    
    return $user_level >= $required_level;
}

// Verificar estructura de la base de datos
function checkDatabaseStructure($conn) {
    $structure = [];
    $tables_to_check = [
        'usuarios', 'tickets', 'dependencias', 
        'areas_soporte', 'areassoporte', 'servicios',
        'ticketadjuntos', 'ticket_evaluaciones', 'tickettevaluaciones',
        'backupconfig', 'backup_config', 'historialtickets'
    ];
    
    try {
        $result = $conn->query("SHOW TABLES");
        $all_tables = $result->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables_to_check as $table_name) {
            foreach ($all_tables as $table) {
                if (strtolower($table) == strtolower($table_name)) {
                    try {
                        $stmt = $conn->prepare("DESCRIBE `$table`");
                        $stmt->execute();
                        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        $structure[$table] = $columns;
                    } catch (Exception $e) {
                        $structure[$table] = "Error: " . $e->getMessage();
                    }
                    break;
                }
            }
        }
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
    
    return $structure;
}
?>
