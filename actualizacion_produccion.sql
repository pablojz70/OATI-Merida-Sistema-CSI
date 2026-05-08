-- ================================================
-- SQL PARA ACTUALIZAR SISTEMA TICKETS - Producción
-- Ejecutar este script para agregar nuevas funcionalidades
-- ================================================

-- 1. Actualizar valores existentes de 'tecnico' a 'oati' en la tabla Usuarios
UPDATE Usuarios SET privilegio = 'oati' WHERE privilegio = 'tecnico';

-- 2. Modificar campo privilegio para incluir 'oati' y 'director' (asegurar que exista)
ALTER TABLE Usuarios MODIFY COLUMN privilegio 
    ENUM('admin','oati','director','usuario','bienes') 
    DEFAULT 'usuario';

-- 3. Actualizar tabla Tickets: asegurar que la columna se llame oati_asignado y que la foreign key sea correcta
--    Eliminar la foreign key existente que apunta a la columna oati_asignado (si existe)
ALTER TABLE Tickets DROP FOREIGN KEY IF EXISTS Tickets_ibfk_5;
--    Eliminar el índice antiguo en la columna oati_asignado (si existe) y crear uno nuevo
DROP INDEX IF EXISTS idx_tickets_oati ON Tickets;
CREATE INDEX IF NOT EXISTS idx_tickets_oati ON Tickets(oati_asignado);
--    Volver a crear la foreign key con el nombre de columna oati_asignado
ALTER TABLE Tickets ADD CONSTRAINT Tickets_ibfk_5 FOREIGN KEY (oati_asignado) REFERENCES Usuarios(id);

-- 4. Actualizar tabla TicketEvaluaciones: renombrar columna tecnico_id a oati_id y actualizar foreign key
--    Primero, eliminar la foreign key existente (si existe)
ALTER TABLE TicketEvaluaciones DROP FOREIGN KEY IF EXISTS TicketEvaluaciones_ibfk_1;
--    Renombrar la columna tecnico_id a oati_id
ALTER TABLE TicketEvaluaciones CHANGE tecnico_id oati_id INT(11) DEFAULT NULL;
--    Eliminar el índice antiguo (si existe) y crear uno nuevo
DROP INDEX IF EXISTS idx_oati ON TicketEvaluaciones;
CREATE INDEX IF NOT EXISTS idx_oati ON TicketEvaluaciones(oati_id);
--    Volver a crear la foreign key con el nuevo nombre de columna
ALTER TABLE TicketEvaluaciones ADD CONSTRAINT TicketEvaluaciones_ibfk_1 FOREIGN KEY (oati_id) REFERENCES Usuarios(id);

-- ================================================
-- Scripts de verificación (descomentar para usar)
-- ================================================

-- Verificar estructura de privilegio:
-- SHOW COLUMNS FROM Usuarios WHERE Field = 'privilegio';

-- Contar evaluaciones:
-- SELECT COUNT(*) as total_evaluaciones FROM TicketEvaluaciones;