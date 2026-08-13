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

-- 3. Nuevos campos en Dependencias
ALTER TABLE Dependencias ADD COLUMN ubicacion TEXT NULL;
ALTER TABLE Dependencias ADD COLUMN telefono VARCHAR(50) NULL;
ALTER TABLE Dependencias ADD COLUMN correo VARCHAR(100) NULL;
ALTER TABLE Dependencias ADD COLUMN materia VARCHAR(50) NULL;
ALTER TABLE Dependencias ADD COLUMN sede VARCHAR(100) NULL;
ALTER TABLE Dependencias ADD COLUMN zona VARCHAR(100) NULL;

-- 4. Módulo Departamentos
CREATE TABLE IF NOT EXISTS Departamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  area_tipo ENUM('informatica','infraestructura') NOT NULL DEFAULT 'informatica',
  activa TINYINT(1) DEFAULT 1
);

ALTER TABLE Dependencias ADD COLUMN departamento_informatica_id INT NULL;
ALTER TABLE Dependencias ADD COLUMN departamento_infraestructura_id INT NULL;

CREATE TABLE IF NOT EXISTS DepartamentoTecnicos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  departamento_id INT NOT NULL,
  usuario_id INT NOT NULL,
  UNIQUE KEY (departamento_id, usuario_id)
);

INSERT INTO Departamentos (nombre, area_tipo) VALUES
('Oficina Principal', 'informatica'),
('Oficina CJP', 'informatica'),
('Oficina El Vigia', 'informatica'),
('Oficina Principal', 'infraestructura'),
('Oficina CJP', 'infraestructura'),
('Oficina El Vigia', 'infraestructura'),
('Oficina Tovar', 'infraestructura');

-- 5. Módulo Calendario: tickets programados
ALTER TABLE Tickets ADD COLUMN fecha_evento DATETIME NULL;
ALTER TABLE Tickets ADD COLUMN tipo_evento ENUM('audiencia','evento','mantenimiento') NULL;
ALTER TABLE Tickets MODIFY estado ENUM('Nuevo','Asignado','En Proceso','Programado','Cerrado Exitosamente','Cerrado No Exitoso') DEFAULT 'Nuevo';
