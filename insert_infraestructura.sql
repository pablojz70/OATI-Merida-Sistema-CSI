USE sistema_tickets;

-- Insert new areas for Infraestructura
INSERT INTO AreasSoporte (nombre, tipo, activa, orden) VALUES
('Apoyo Logístico', 'infraestructura', 1, 100),
('Mantenimiento Preventivo', 'infraestructura', 1, 200),
('Instalación o Mantenimiento Correctivo', 'infraestructura', 1, 300);

-- Insert services for Apoyo Logístico
INSERT INTO Servicios (area_id, nombre, activo) VALUES
((SELECT id FROM AreasSoporte WHERE nombre = 'Apoyo Logístico' LIMIT 1), 'Protocolo', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Apoyo Logístico' LIMIT 1), 'Acarreo', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Apoyo Logístico' LIMIT 1), 'Otros', 1);

-- Insert services for Mantenimiento Preventivo
INSERT INTO Servicios (area_id, nombre, activo) VALUES
((SELECT id FROM AreasSoporte WHERE nombre = 'Mantenimiento Preventivo' LIMIT 1), 'Limpieza y Aseo', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Mantenimiento Preventivo' LIMIT 1), 'Pintura', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Mantenimiento Preventivo' LIMIT 1), 'Jardinería', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Mantenimiento Preventivo' LIMIT 1), 'Fumigación', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Mantenimiento Preventivo' LIMIT 1), 'Otro Mantenimiento Preventivo', 1);

-- Insert services for Instalación o Mantenimiento Correctivo
INSERT INTO Servicios (area_id, nombre, activo) VALUES
((SELECT id FROM AreasSoporte WHERE nombre = 'Instalación o Mantenimiento Correctivo' LIMIT 1), 'Electricidad', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Instalación o Mantenimiento Correctivo' LIMIT 1), 'Plomería', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Instalación o Mantenimiento Correctivo' LIMIT 1), 'Albañilería', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Instalación o Mantenimiento Correctivo' LIMIT 1), 'Carpintería', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Instalación o Mantenimiento Correctivo' LIMIT 1), 'Herrería', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Instalación o Mantenimiento Correctivo' LIMIT 1), 'Refrigeración', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Instalación o Mantenimiento Correctivo' LIMIT 1), 'Mecánica Automotriz', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Instalación o Mantenimiento Correctivo' LIMIT 1), 'Equipos Electrónicos', 1),
((SELECT id FROM AreasSoporte WHERE nombre = 'Instalación o Mantenimiento Correctivo' LIMIT 1), 'Otra Instalación o Mantenimiento', 1);
