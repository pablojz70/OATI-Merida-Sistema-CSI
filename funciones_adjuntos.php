<?php
// ============================================
// FUNCIONES PARA MANEJO DE ARCHIVOS ADJUNTOS - VERSIÓN SIMPLIFICADA
// ============================================

/**
 * Sube un archivo adjunto a un ticket (versión simplificada)
 */
function subirArchivoTicket($conn, $ticket_id, $nombre_original, $tamano, $tipo, $usuario_id, $file_index = 0) {
    try {
        // Crear estructura de carpetas
        $anio = date('Y');
        $mes = date('m');
        $carpeta_base = __DIR__ . "/../adjuntos/tickets/{$anio}/{$mes}/";
        
        if(!file_exists($carpeta_base)) {
            if(!mkdir($carpeta_base, 0755, true)) {
                error_log("❌ No se pudo crear la carpeta: $carpeta_base");
                return false;
            }
        }
        
        // Verificar permisos de escritura
        if(!is_writable($carpeta_base)) {
            error_log("❌ Carpeta sin permisos de escritura: $carpeta_base");
            return false;
        }
        
        // Generar nombre único seguro
        $nombre_sanitizado = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombre_original);
        $nombre_guardado = uniqid() . '_' . $ticket_id . '_' . $nombre_sanitizado;
        $ruta_completa = $carpeta_base . $nombre_guardado;
        $ruta_relativa = "tickets/{$anio}/{$mes}/{$nombre_guardado}";
        
        // Verificar archivo temporal
        $tmp_name = $_FILES['archivos']['tmp_name'][$file_index];
        
        if(!file_exists($tmp_name)) {
            error_log("❌ Archivo temporal no existe: $tmp_name");
            return false;
        }
        
        // Mover archivo temporal (usa copy() como alternativa)
        if(copy($tmp_name, $ruta_completa)) {
            error_log("✅ Archivo copiado exitosamente: $nombre_original");
            
            // Guardar en BD
            $sql = "INSERT INTO TicketAdjuntos 
                    (ticket_id, nombre_original, nombre_guardado, tipo_mime, tamano_bytes, ruta_relativa, subido_por) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(1, $ticket_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $nombre_original);
            $stmt->bindParam(3, $nombre_guardado);
            $stmt->bindParam(4, $tipo);
            $stmt->bindParam(5, $tamano, PDO::PARAM_INT);
            $stmt->bindParam(6, $ruta_relativa);
            $stmt->bindParam(7, $usuario_id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                $adjunto_id = $conn->lastInsertId();
                error_log("✅ Registro en BD exitoso. ID: $adjunto_id");
                return true;
            } else {
                // Si falla BD, eliminar archivo
                if(file_exists($ruta_completa)) {
                    unlink($ruta_completa);
                }
                error_log("❌ Error insertando en BD");
                return false;
            }
        } else {
            error_log("❌ Error copiando archivo: $nombre_original");
            return false;
        }
    } catch (Exception $e) {
        error_log("❌ Excepción en subirArchivoTicket: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene los adjuntos de un ticket
 */
function obtenerAdjuntosTicket($conn, $ticket_id) {
    try {
        $sql = "SELECT * FROM TicketAdjuntos WHERE ticket_id = ? ORDER BY fecha_subida DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$ticket_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error obteniendo adjuntos: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene el icono según tipo de archivo
 */
function obtenerIconoArchivo($tipo_mime) {
    if(strpos($tipo_mime, 'image/') !== false) return 'fas fa-file-image text-primary';
    if(strpos($tipo_mime, 'pdf') !== false) return 'fas fa-file-pdf text-danger';
    if(strpos($tipo_mime, 'word') !== false || strpos($tipo_mime, 'document') !== false) return 'fas fa-file-word text-primary';
    if(strpos($tipo_mime, 'excel') !== false || strpos($tipo_mime, 'spreadsheet') !== false) return 'fas fa-file-excel text-success';
    if(strpos($tipo_mime, 'text/') !== false) return 'fas fa-file-alt text-secondary';
    return 'fas fa-file text-muted';
}

/**
 * Formatea bytes a tamaño legible
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
