-- ================================================
-- OPTIMIZAR BASE DE DATOS PARA CONEXIONES LENTAS
-- Ejecutar en phpMyAdmin o MySQL Workbench
-- ================================================

-- 1. Crear índices en Servicios para acelerar consultas AJAX
ALTER TABLE Servicios ADD INDEX IF NOT EXISTS idx_area_id (area_id);
ALTER TABLE Servicios ADD INDEX IF NOT EXISTS idx_activo (activo);
ALTER TABLE Servicios ADD INDEX IF NOT EXISTS idx_area_activo (area_id, activo);

-- 2. Crear índices en Usuarios para加速 user queries
ALTER TABLE Usuarios ADD INDEX IF NOT EXISTS idx_privilegio (privilegio);
ALTER TABLE Usuarios ADD INDEX IF NOT EXISTS idx_activo (activo);

-- 3. Crear índices en Dependencias
ALTER TABLE Dependencias ADD INDEX IF NOT EXISTS idx_activo (activa);

-- 4. Verificar que existen los índices
SHOW INDEX FROM Servicios;
SHOW INDEX FROM Usuarios;

-- 5. OPTIMIZAR TABLAS (desfragmentar)
OPTIMIZE TABLE Servicios;
OPTIMIZE TABLE Usuarios;
OPTIMIZE TABLE Dependencias;
OPTIMIZE TABLE AreasSoporte;
