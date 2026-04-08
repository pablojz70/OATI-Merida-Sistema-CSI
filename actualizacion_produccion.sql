-- ================================================
-- SQL PARA ACTUALIZAR SISTEMA CSI - Producción
-- Ejecutar este script para agregar nuevas funcionalidades
-- ================================================

-- 1. Crear tabla de evaluaciones de tickets
CREATE TABLE IF NOT EXISTS TicketEvaluaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    usuario_id INT NOT NULL,
    calificacion TINYINT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
    comentario TEXT,
    fecha_evaluacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tecnico_id INT DEFAULT NULL,
    UNIQUE KEY unique_evaluacion (ticket_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Agregar índice para optimizar consultas por técnico
CREATE INDEX IF NOT EXISTS idx_tecnico ON TicketEvaluaciones(tecnico_id);

-- 3. Modificar campo privilegio para incluir 'director'
ALTER TABLE Usuarios MODIFY COLUMN privilegio 
    ENUM('admin','tecnico','director','usuario') 
    DEFAULT 'usuario';

-- 4. Verificar que la tabla TicketAdjuntos existe
CREATE TABLE IF NOT EXISTS TicketAdjuntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tipo_archivo VARCHAR(50) DEFAULT NULL,
    tamanio BIGINT DEFAULT NULL,
    subido_por INT DEFAULT NULL,
    fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Crear tabla de configuración de backups (si no existe)
CREATE TABLE IF NOT EXISTS BackupConfig (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    tamano BIGINT DEFAULT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('manual','automatico') DEFAULT 'manual',
    notas TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Scripts de verificación (descomentar para usar)
-- ================================================

-- Verificar tablas creadas:
-- SHOW TABLES LIKE '%Evalu%';
-- SHOW TABLES LIKE '%Adjunt%';
-- SHOW TABLES LIKE '%Backup%';

-- Verificar estructura de privilegio:
-- SHOW COLUMNS FROM Usuarios WHERE Field = 'privilegio';

-- Contar evaluaciones:
-- SELECT COUNT(*) as total_evaluaciones FROM TicketEvaluaciones;
