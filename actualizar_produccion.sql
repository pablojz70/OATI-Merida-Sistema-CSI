-- =============================================
-- ACTUALIZACIONES BASE DE DATOS - Sistema CSI
-- Fecha: Mayo 2026
-- =============================================

-- 1. Columna telegram_id en Usuarios (para notificaciones Telegram)
ALTER TABLE Usuarios ADD COLUMN telegram_id VARCHAR(50) DEFAULT NULL;

-- 2. Tabla para asignar múltiples funcionarios a un ticket
CREATE TABLE IF NOT EXISTS TicketAsignados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  usuario_id INT NOT NULL,
  fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_asignacion (ticket_id, usuario_id),
  KEY idx_ticket (ticket_id),
  KEY idx_usuario (usuario_id)
);
