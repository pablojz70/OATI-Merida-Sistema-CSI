<?php
// config/funciones.php - VERSIÓN CORREGIDA PARA PDO

/**
 * Obtener una fila de la base de datos
 */
function obtenerFila($sql, $params = []) {
    global $conn;
    
    if (!$conn) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obtenerFila: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener múltiples filas
 */
function obtenerFilas($sql, $params = []) {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obtenerFilas: " . $e->getMessage());
        return [];
    }
}

/**
 * Ejecutar consulta (INSERT, UPDATE, DELETE)
 */
function ejecutarConsulta($sql, $params = []) {
    global $conn;
    
    if (!$conn) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error ejecutarConsulta: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener todas las dependencias activas
 */
function obtenerDependencias() {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    try {
        $sql = "SELECT id, nombre FROM dependencias WHERE activa = 1 ORDER BY nombre";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obtenerDependencias: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener áreas de soporte activas
 */
function obtenerAreasSoporte() {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    try {
        $sql = "SELECT id, nombre FROM areas_soporte WHERE activa = 1 ORDER BY nombre";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obtenerAreasSoporte: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener servicios por área
 */
function obtenerServiciosPorArea($area_id) {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    try {
        $sql = "SELECT id, nombre FROM servicios WHERE area_id = ? AND activo = 1 ORDER BY nombre";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$area_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obtenerServiciosPorArea: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener nombre de dependencia por ID
 */
function obtenerNombreDependencia($dependencia_id) {
    global $conn;
    
    if (!$dependencia_id) {
        return 'No asignada';
    }
    
    try {
        $sql = "SELECT nombre FROM dependencias WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$dependencia_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['nombre'] : 'Desconocida';
    } catch (PDOException $e) {
        return 'Error';
    }
}

/**
 * Obtener nombre de área por ID
 */
function obtenerNombreArea($area_id) {
    global $conn;
    
    if (!$area_id) {
        return 'No asignada';
    }
    
    try {
        $sql = "SELECT nombre FROM areas_soporte WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$area_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['nombre'] : 'Desconocida';
    } catch (PDOException $e) {
        return 'Error';
    }
}

/**
 * Obtener nombre de servicio por ID
 */
function obtenerNombreServicio($servicio_id) {
    global $conn;
    
    if (!$servicio_id) {
        return 'No asignado';
    }
    
    try {
        $sql = "SELECT nombre FROM servicios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$servicio_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['nombre'] : 'Desconocido';
    } catch (PDOException $e) {
        return 'Error';
    }
}

/**
 * Obtener nombre de usuario por ID
 */
function obtenerNombreUsuario($usuario_id) {
    global $conn;
    
    if (!$usuario_id) {
        return 'Desconocido';
    }
    
    try {
        $sql = "SELECT nombre FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$usuario_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['nombre'] : 'Desconocido';
    } catch (PDOException $e) {
        return 'Error';
    }
}

/**
 * Registrar log de actividad del sistema
 * 
 * @param int|null $usuario_id ID del usuario (null para logs del sistema)
 * @param string $accion Tipo de acción (LOGIN, LOGOUT, CREAR_TICKET, CERRAR_TICKET, etc.)
 * @param string $descripcion Descripción detallada de la acción
 * @param int|null $ticket_id ID del ticket relacionado (opcional)
 */
function registrarLog($usuario_id, $accion, $descripcion, $ticket_id = NULL) {
    global $conn;
    
    // Obtener IP del cliente
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    
    // Obtener User Agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
    if (strlen($user_agent) > 500) {
        $user_agent = substr($user_agent, 0, 500);
    }
    
    try {
        // Usar la tabla Logs con las columnas correctas
        $sql_logs = "INSERT INTO Logs (usuario_id, accion, descripcion, ip, user_agent, ticket_id, fecha) 
                     VALUES (:usuario_id, :accion, :descripcion, :ip, :user_agent, :ticket_id, NOW())";
        $stmt_logs = $conn->prepare($sql_logs);
        $stmt_logs->execute([
            ':usuario_id' => $usuario_id,
            ':accion' => $accion,
            ':descripcion' => $descripcion,
            ':ip' => $ip,
            ':user_agent' => $user_agent,
            ':ticket_id' => $ticket_id
        ]);
    } catch (PDOException $e) {
        error_log("Error registrarLog: " . $e->getMessage());
    }
    
    // También registrar en historial del ticket si aplica
    if ($ticket_id !== NULL && $ticket_id > 0) {
        try {
            $sql_verificar = "SELECT id FROM Tickets WHERE id = ?";
            $stmt_verificar = $conn->prepare($sql_verificar);
            $stmt_verificar->execute([$ticket_id]);
            
            if ($stmt_verificar->rowCount() > 0) {
                $sql_historial = "INSERT INTO HistorialTickets (ticket_id, usuario_id, accion, detalle, fecha_accion) 
                                  VALUES (:ticket_id, :usuario_id, :accion, :detalle, NOW())";
                $stmt_historial = $conn->prepare($sql_historial);
                $stmt_historial->execute([
                    ':ticket_id' => $ticket_id,
                    ':usuario_id' => $usuario_id,
                    ':accion' => $accion,
                    ':detalle' => $descripcion
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error registrarLog historial: " . $e->getMessage());
        }
    }
}

/**
 * Validar y sanitizar entrada
 */
function limpiarInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Formatear fecha para mostrar
 */
function formatearFecha($fecha, $formato = 'd/m/Y H:i') {
    if (empty($fecha)) {
        return '';
    }
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return $fecha;
    }
    
    return date($formato, $timestamp);
}

/**
 * Generar código único para ticket
 */
function generarCodigoTicket() {
    return 'TICK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Verificar si el usuario tiene permiso
 */
function tienePermiso($privilegio, $permiso_requerido) {
    $permisos = [
        'admin' => ['acceso_total', 'gestion_usuarios', 'gestion_tickets', 'ver_reportes', 'crear_tickets', 'ver_tickets'],
        'tecnico' => ['gestion_tickets', 'ver_tickets', 'crear_tickets'],
        'usuario' => ['crear_tickets', 'ver_mis_tickets']
    ];
    
    return isset($permisos[$privilegio]) && in_array($permiso_requerido, $permisos[$privilegio]);
}

/**
 * Mostrar mensajes de error
 */
function mostrarErrores() {
    if (isset($_SESSION['errores']) && !empty($_SESSION['errores'])) {
        echo '<div class="alert alert-error">';
        foreach ($_SESSION['errores'] as $error) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
        }
        echo '</div>';
        unset($_SESSION['errores']);
    }
}

/**
 * Mostrar mensajes de éxito
 */
function mostrarMensajes() {
    if (isset($_SESSION['mensaje']) && !empty($_SESSION['mensaje'])) {
        echo '<div class="alert alert-success">';
        echo htmlspecialchars($_SESSION['mensaje']);
        echo '</div>';
        unset($_SESSION['mensaje']);
    }
}

/**
 * Obtener valor de configuración del sistema
 */
function obtenerConfig($clave, $valorDefecto = '') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT valor FROM configuraciones WHERE clave = ?");
        $stmt->execute([$clave]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['valor'];
        }
    } catch (PDOException $e) {
        // Tabla no existe, usar valores por defecto
    }
    
    $configs = [
        'nombre_sistema' => 'Sistema CSI - Soporte Técnico',
        'color_principal' => '#2c3e50',
        'color_secundario' => '#3498db',
        'items_por_pagina' => '20',
        'logo_url' => 'logo.png',
        'timezone' => 'America/Mexico_City',
        'idioma' => 'es',
        'email_notificaciones' => '1',
        'max_tamano_mb' => '10'
    ];
    
    return isset($configs[$clave]) ? $configs[$clave] : $valorDefecto;
}

/**
 * Obtener estadísticas de tickets
 */
function obtenerEstadisticasTickets($usuario_id, $privilegio = 'usuario') {
    global $conn;
    
    $estadisticas = [
        'total' => 0,
        'estados' => [
            'Nuevo' => 0,
            'Asignado' => 0,
            'En Proceso' => 0,
            'Cerrado Exitosamente' => 0,
            'Cerrado No Exitoso' => 0
        ]
    ];
    
    try {
        if ($privilegio === 'usuario') {
            $sql = "SELECT estado, COUNT(*) as cantidad FROM Tickets WHERE usuario_id = ? GROUP BY estado";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$usuario_id]);
        } elseif ($privilegio === 'tecnico') {
            $sql = "SELECT estado, COUNT(*) as cantidad FROM Tickets WHERE tecnico_asignado = ? GROUP BY estado";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$usuario_id]);
        } else {
            $sql = "SELECT estado, COUNT(*) as cantidad FROM Tickets GROUP BY estado";
            $stmt = $conn->query($sql);
        }
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $estado = $row['estado'];
            $cantidad = $row['cantidad'];
            
            if (isset($estadisticas['estados'][$estado])) {
                $estadisticas['estados'][$estado] = $cantidad;
            }
            $estadisticas['total'] += $cantidad;
        }
    } catch (PDOException $e) {
        error_log("Error obtenerEstadisticasTickets: " . $e->getMessage());
    }
    
    return $estadisticas;
}

/**
 * Obtener áreas de soporte filtradas
 */
function obtenerAreasSoporteFiltradas() {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    try {
        $sql = "SELECT id, nombre FROM AreasSoporte 
                WHERE activa = 1 
                AND nombre NOT LIKE '%DAR%' 
                AND nombre NOT LIKE '%Gestión DAR%' 
                ORDER BY nombre";
        
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obtenerAreasSoporteFiltradas: " . $e->getMessage());
        return [];
    }
}

/**
 * Función para debug (solo desarrollo)
 */
function debug($data) {
    if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
        return;
    }
    
    echo '<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';
    print_r($data);
    echo '</pre>';
}
?>
