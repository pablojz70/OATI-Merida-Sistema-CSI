-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 10-02-2026 a las 13:43:55
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_csi`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Adjuntos`
--

CREATE TABLE `Adjuntos` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tipo_archivo` varchar(100) DEFAULT NULL,
  `tamanio` int(11) DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `AreasSoporte`
--

CREATE TABLE `AreasSoporte` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `todosven` tinyint(1) DEFAULT 1 COMMENT '0 = solo admin, 1 = todos ven',
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `AreasSoporte`
--

INSERT INTO `AreasSoporte` (`id`, `nombre`, `descripcion`, `activa`, `todosven`, `orden`) VALUES
(1, 'Apoyo Logístico DAR', 'Servicios de apoyo logístico para eventos y videoconferencias', 1, 1, 1),
(2, 'Soporte a Servidores', 'Mantenimiento, configuración y respaldo de servidores', 1, 0, 2),
(3, 'Gestión DAR', 'Gestión administrativa y elaboración de informes', 1, 0, 3),
(4, 'Telefonía', 'Instalación, configuración y mantenimiento de sistemas telefónicos', 1, 1, 4),
(5, 'RED', 'Configuración y mantenimiento de redes y equipos de red', 1, 1, 5),
(6, 'Sistemas y Aplicaciones', 'Instalación y soporte de sistemas y aplicaciones informáticas', 1, 1, 6),
(7, 'Soporte al usuario', 'Atención directa a usuarios, hardware y software', 1, 1, 7),
(8, 'Soporte Taller', 'Reparación y mantenimiento de equipos e impresoras', 1, 1, 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones`
--

CREATE TABLE `configuraciones` (
  `id` int(11) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` varchar(20) DEFAULT 'text',
  `opciones` text DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `editable` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuraciones`
--

INSERT INTO `configuraciones` (`id`, `categoria`, `clave`, `valor`, `tipo`, `opciones`, `descripcion`, `orden`, `editable`, `created_at`, `updated_at`) VALUES
(1, 'general', 'nombre_sistema', 'Sistema CSI - Soporte Técnico', 'text', NULL, 'Nombre del sistema que aparece en el título', 1, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(2, 'general', 'logo_url', 'logo.png', 'text', NULL, 'Ruta del archivo de logo', 2, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(3, 'general', 'color_principal', '#2c3e50', 'color', NULL, 'Color principal de la interfaz', 3, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(4, 'general', 'color_secundario', '#3498db', 'color', NULL, 'Color secundario para botones', 4, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(5, 'general', 'items_por_pagina', '20', 'number', NULL, 'Número de items por página en listados', 5, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(6, 'general', 'timezone', 'America/Mexico_City', 'select', NULL, 'Zona horaria del sistema', 6, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(7, 'general', 'idioma', 'es', 'select', NULL, 'Idioma del sistema', 7, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(8, 'email', 'notificaciones_activas', '1', 'boolean', NULL, 'Activar/Desactivar notificaciones por email', 1, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(9, 'email', 'smtp_host', 'smtp.gmail.com', 'text', NULL, 'Servidor SMTP', 2, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(10, 'email', 'smtp_port', '587', 'number', NULL, 'Puerto SMTP', 3, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(11, 'email', 'smtp_secure', 'tls', 'select', NULL, 'Tipo de seguridad', 4, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(12, 'email', 'smtp_usuario', '', 'text', NULL, 'Usuario SMTP', 5, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(13, 'email', 'smtp_password', '', 'password', NULL, 'Contraseña SMTP', 6, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(14, 'email', 'email_from', 'notificaciones@sistema-csi.com', 'text', NULL, 'Email remitente', 7, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(15, 'email', 'nombre_from', 'Sistema CSI', 'text', NULL, 'Nombre remitente', 8, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(16, 'email', 'email_reply_to', 'soporte@sistema-csi.com', 'text', NULL, 'Email para respuestas', 9, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(17, 'seguridad', 'max_intentos_login', '5', 'number', NULL, 'Máximo intentos de login antes de bloquear', 1, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(18, 'seguridad', 'tiempo_bloqueo_minutos', '30', 'number', NULL, 'Minutos de bloqueo por intentos fallidos', 2, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(19, 'seguridad', 'sesion_expiracion_horas', '8', 'number', NULL, 'Horas para expirar sesión inactiva', 3, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(20, 'seguridad', 'requerir_contrasena_fuerte', '1', 'boolean', NULL, 'Requerir contraseña fuerte (mínimo 8 caracteres, mayúscula, número)', 4, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(21, 'seguridad', 'bloquear_ip_intentos', '1', 'boolean', NULL, 'Bloquear IP por intentos fallidos', 5, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(22, 'archivos', 'max_tamano_mb', '10', 'number', NULL, 'Tamaño máximo de archivos adjuntos en MB', 1, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(23, 'archivos', 'extensiones_permitidas', 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'text', NULL, 'Extensiones permitidas separadas por coma', 2, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(24, 'archivos', 'ruta_uploads', 'uploads/', 'text', NULL, 'Ruta donde se guardan los archivos', 3, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(25, 'tickets', 'prioridad_defecto', 'media', 'select', NULL, 'Prioridad por defecto para nuevos tickets', 1, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(26, 'tickets', 'dias_cierre_automatico', '30', 'number', NULL, 'Días para cierre automático de tickets inactivos', 2, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(27, 'tickets', 'notificar_usuario_siempre', '1', 'boolean', NULL, 'Notificar siempre al usuario sobre cambios', 3, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(28, 'tickets', 'permitir_reabrir_tickets', '1', 'boolean', NULL, 'Permitir reabrir tickets cerrados', 4, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(29, 'tickets', 'tiempo_max_respuesta_horas', '24', 'number', NULL, 'Tiempo máximo para respuesta inicial en horas', 5, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(30, 'tickets', 'sla_prioridad_alta', '4', 'number', NULL, 'SLA para prioridad alta (horas)', 6, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(31, 'tickets', 'sla_prioridad_media', '8', 'number', NULL, 'SLA para prioridad media (horas)', 7, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30'),
(32, 'tickets', 'sla_prioridad_baja', '24', 'number', NULL, 'SLA para prioridad baja (horas)', 8, 1, '2026-01-19 15:03:30', '2026-01-19 15:03:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Dependencias`
--

CREATE TABLE `Dependencias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `nombre_corto` varchar(35) DEFAULT NULL,
  `responsable` varchar(100) DEFAULT NULL,
  `email_responsable` varchar(100) DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Dependencias`
--

INSERT INTO `Dependencias` (`id`, `nombre`, `nombre_corto`, `responsable`, `email_responsable`, `activa`) VALUES
(1, 'AGRARIO MERIDA', 'AGRARIO MERIDA', 'STERLICCHI MATHEUS, JEANETTE DEL ROSARIO ', NULL, 1),
(24, 'DIRECCION ADMINISTRATIVA REGIONAL DEL ESTADO MERIDA', 'DAR MERIDA', 'RINCON RIVAS, FREDDY ALEJANDRO', NULL, 1),
(37, 'JUZGADO PRIMERO DE PRIMERA INSTANCIA CIVIL, MERCANTIL Y DEL TRANSITO DE LA CIRCUNSCRIPCION JUICIAL DEL ESTADO MERIDA', 'PRIMERO-DE-PRIMERA-INSTANCIA-MERIDA', 'HERNANDEZ , ROLANDO', NULL, 1),
(162, 'CIRCUITO JUDICIAL PENAL DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'CJP MERIDA', 'RONDON , WENDY LOVELY', NULL, 1),
(163, 'CIRCUITO JUDICIAL DE PROTECCION DE NIÑOS, NIÑAS Y ADOLESCENTES DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, SEDE MERIDA', 'PENAL ADOLESCENTE MERIDA', 'ARANDA MÉNDEZ, HUMBERTO JOSE', 'Prueba@gmail.com', 1),
(164, 'CIRCUITO JUDICIAL CON COMPETENCIA EN DELITOS DE VIOLENCIA CONTRA LA MUJER SEDE MERIDA', 'CVG MERIDA', 'TORRES ROSARIO, YEGNIN', NULL, 1),
(165, 'CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, EXTENSION EL VIGIA.', 'CJP EL VIGIA.', 'MARTINEZ PARRA, MAILES ROSANGELA', NULL, 1),
(166, 'OFICINA DE DESARROLLO INFORMATICO DE LA DEM', 'ODI', 'JOSE ESCOBAR', NULL, 1),
(167, 'OFICINA DE PARTICIPACION CIUDADANA DAR MERIDA.', 'OARPC MERIDA', 'SUAREZ DE HULL , DARSY COROMOTO', NULL, 1),
(168, 'SEGURIDAD REGIONAL DE LA D.A.R. DEL ESTADO MERIDA', 'SEGURIDAD MERIDA', 'VELASQUEZ PATIÑO, ASDRUBAL JOSE', NULL, 1),
(169, 'RECTORIA MERIDA', 'RECTORIA MERIDA', 'MORY DUQUE, LUIS FERNANDO JESUS', NULL, 1),
(170, 'JUZGADO SUPERIOR PRIMERO CIVIL, MERCANTIL Y TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'SUPERIOR-PRIMERO-MERIDA', 'DÁVILA OCHOA, YOSANNY CRISTINA', NULL, 1),
(171, 'JUZGADO SUPERIOR SEGUNDO CIVIL, MERCANTIL Y DEL TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'SUPERIOR-SEGUNDO-MERIDA', 'MORY DUQUE, LUIS FERNANDO JESUS', NULL, 1),
(172, 'JUZGADO SEGUNDO DE PRIMERA INSTANCIA CIVIL, MERCANTIL Y DEL TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'SEGUNDO-DE-PRIMERA-INSTANCIA-MERIDA', 'MONSALVE RIVAS, MIGUEL ANGEL', NULL, 1),
(173, 'JUZGADO TERCERO DE PRIMERA INSTANCIA CIVIL, MERCANTIL Y DEL TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TERCERO-DE-PRIMERA-INSTANCIA-MERIDA', 'CALDERÓN, CARLOS ARTURO', NULL, 1),
(174, 'JUZGADO CUARTO DE PRIMERA INSTANCIA CIVIL, MERCANTIL Y TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'CUARTO-DE-1RA-INSTANCIA-TOVAR', 'CONTRERAS GUERRERO, SANDRA LILIANA', NULL, 1),
(175, 'JUZGADO SUPERIOR ESTADAL CONTENCIOSO ADMINISTRATIVO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, SEDE MERIDA', 'CONTENCIOSO', 'MORENO CAMACHO, SILVIA ELISA', NULL, 1),
(176, 'JUZGADO DE PRIMERA INSTANCIA EN LO CIVIL, MERCANTIL Y TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, EXTENSION EL VIGIA', 'PRIMERA-INSTANCIA-EL-VIGIA', 'RUIZ TORRES, LII ELENA', NULL, 1),
(177, 'CIRCUITO JUDICIAL LABORAL SEDE MERIDA', 'LABORAL MERIDA', 'MONTOYA GUERRERO, DOUGLAS ARNOLDO', NULL, 1),
(178, 'CIRCUITO JUDICIAL LABORAL SEDE EL VIGIA', 'LABORAL EL VIGIA', 'MONSALVE QUINTERO, FREDDY REINALDO', NULL, 1),
(179, 'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS CAMPO ELIAS Y ARICAGUA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-2DO-DE-MUNICIPIO-EJIDO', 'VAZQUEZ AÑEZ, ALBA DEL CARMEN', NULL, 1),
(180, 'TRIBUNAL PRIMERO DE MUNCIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DEL MUNICIPIO ANTONIO PINTO SALINAS DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, CON SEDE EN LA CIUDAD DE SANTA CRUZ', 'TRIBUNAL-1RO-MUNICIPIO-SANTA-CRUZ', 'UZCATEGUI UZCATEGUI, JOSLEDY ALICIA', NULL, 1),
(181, 'TRIBUNAL SEGUNDO DE MUNCIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DEL MUNICIPIO SUCRE DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, CON SEDE EN LA CIUDAD DE LAGUNILLAS', 'TRIBUNAL-2DO-MUNICIPIO-LAGUNILLAS', 'DUGARTE CONTRERAS, JHONNY CARMELO', NULL, 1),
(182, 'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS ALBERTO ADRIANI, ANDRES BELLO, OBISPO RAMOS DE LORA Y CARACCIOLO PARRA OLMEDO DE LA C.J. DEL EDO MERIDA', 'TRIBUNAL-1RO-MUNICIPIO-EL-VIGIA', 'ESTREMOR OSMA, MARIA EUGENIA', NULL, 1),
(183, 'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS LIBERTADOR Y SANTOS MARQUINA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-1RO-MUNICIPIO-LIBERTADOR', 'MORY DUQUE, LUIS FERNANDO JESUS', NULL, 1),
(184, 'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS JUSTO BRICEÑO, TULIO FEBRES CORDERO Y JULIO CESAR SALAS DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-1RO-MUNICIPIO-NUEV-BOLIVIA', 'MORENO CUBARRUBIA, MIRELIS COROMOTO', NULL, 1),
(185, 'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS MIRANDA Y PUEBLO LLANO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-1RO-MUNICIPIO-TIMOTES', 'VILLARREAL LAGUNA, DEFIGENIO', NULL, 1),
(186, 'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS RANGEL Y CARDENAL QUINTERO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-1RO-MUNICIPIO-MUCUCHIES', 'VILLARREAL LAGUNA, DEFIGENIO', NULL, 1),
(187, 'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS TOVAR, ZEA, GUARAQUE Y ARZOBISPO CHACON DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-1RO-MUNICIPIO-TOVAR', 'BARRIOS HERNANDEZ, KARINA YUSVELY', NULL, 1),
(188, 'CIRCUITO JUDICIAL DE PROTECCION DE NIÑOS, NIÑAS Y ADOLESCENTES DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, SEDE EXT. EL VIGIA', 'PENAL-ADOLESCENTE-EL-VIGIA', 'MARTINEZ PARRA, MAILES ROSANGELA', NULL, 1),
(189, 'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS CAMPO ELIAS Y ARICAGUA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-1RO-DE-MUNICIPIO-EJIDO', 'OVIEDO SOTO, YORGI ALFONSO', NULL, 1),
(192, 'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS ALBERTO ADRIANI, ANDRES BELLO, OBISPO RAMOS DE LORA Y CARACCIOLO PARRA OLMEDO DE LA C.J. DEL EDO MERIDA', 'TRIBUNAL-2DO-MUNICIPIO-EL-VIGIA', '', NULL, 1),
(193, 'TRIBUNAL TERCERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS CAMPO ELIAS Y ARICAGUA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-3RO-MUNICIPIO-EL-VIGIA', '', NULL, 1),
(194, 'TRIBUNAL CUARTO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS CAMPO ELIAS Y ARICAGUA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-4TO-MUNICIPIO-EL-VIGIA', '', NULL, 1),
(195, 'TRIBUNAL QUINTO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS CAMPO ELIAS Y ARICAGUA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-5TO-MUNICIPIO-EL-VIGIA', '', NULL, 1),
(196, 'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS TOVAR, ZEA, GUARAQUE Y ARZOBISPO CHACON DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-2DO-MUNICIPIO-TOVAR', '', NULL, 1),
(197, 'TRIBUNAL TERCERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS TOVAR, ZEA, GUARAQUE Y ARZOBISPO CHACON DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-3RO-MUNICIPIO-TOVAR', '', NULL, 1),
(198, 'TRIBUNAL PRIMERO DE MUNCIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DEL MUNICIPIO SUCRE DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, CON SEDE EN LA CIUDAD DE LAGUNILLAS', 'TRIBUNAL-1RO-MUNCIPIO-LAGUNILLAS', '', NULL, 1),
(199, 'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS MIRANDA Y PUEBLO LLANO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-2DO-MUNICIPIO-TIMOTES', '', NULL, 1),
(200, 'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS LIBERTADOR Y SANTOS MARQUINA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-2DO-MUNICIPIO-LIBERTADOR', '', NULL, 1),
(201, 'TRIBUNAL TERCERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS LIBERTADOR Y SANTOS MARQUINA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-3RO-MUNICIPIO-LIBERTADOR', '', NULL, 1),
(202, 'TRIBUNAL CUARTO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS LIBERTADOR Y SANTOS MARQUINA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-4TO-MUNICIPIO-LIBERTADOR', '', NULL, 1),
(203, 'TRIBUNAL QUINTO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS LIBERTADOR Y SANTOS MARQUINA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-5TO-MUNICIPIO-LIBERTADOR', '', NULL, 1),
(204, 'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS JUSTO BRICEÑO, TULIO FEBRES CORDERO Y JULIO CESAR SALAS DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA', 'TRIBUNAL-2DO-MUNICIPIO-NUEV-BOLIVIA', '', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `HistorialTickets`
--

CREATE TABLE `HistorialTickets` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Logs`
--

CREATE TABLE `Logs` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Logs`
--

INSERT INTO `Logs` (`id`, `usuario_id`, `accion`, `descripcion`, `fecha`) VALUES
(1, 3, 'ACTUALIZAR_PERFIL', 'Perfil actualizado', '2026-01-20 16:17:45'),
(2, 7, 'ACTUALIZAR_PERFIL', 'Perfil actualizado', '2026-01-21 19:21:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Servicios`
--

CREATE TABLE `Servicios` (
  `id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Servicios`
--

INSERT INTO `Servicios` (`id`, `area_id`, `nombre`, `descripcion`, `activo`) VALUES
(1, 1, 'Servicio de Videoconferencia', 'Configuración y soporte para videoconferencias', 1),
(2, 1, 'Apoyo informático en eventos', 'Soporte técnico durante eventos especiales', 1),
(3, 1, 'Apoyo en servicios audiovisuales', 'Asistencia con equipos audiovisuales', 1),
(4, 1, 'Otros requerimientos', 'Otros servicios de apoyo logístico', 1),
(5, 2, 'Configuración, Verificación y Ejecución de respaldo en servidor', 'Gestión de backups de servidores', 1),
(6, 2, 'Diagnosticar, Resolver, Notificar falla o error en servidor', 'Diagnóstico y solución de problemas en servidores', 1),
(7, 2, 'Instalación de software en servidores', 'Instalación de aplicaciones en servidores', 1),
(8, 2, 'Actualización . Mantenimiento de Directorio Activo', 'Mantenimiento de Active Directory', 1),
(9, 2, 'Mantenimiento y Actualización de Servidores', 'Mantenimiento general de servidores', 1),
(10, 2, 'Otro requerimiento asociado a servidores', 'Otros servicios relacionados con servidores', 1),
(11, 3, 'Ejecución de Actividades de la ODI', 'Actividades de la Oficina de Desarrollo Informático', 1),
(12, 3, 'Elaborar Informes Técnicos', 'Elaboración de informes técnicos', 1),
(13, 3, 'Generar reportes de Gestión', 'Generación de reportes de gestión', 1),
(14, 3, 'Proyectos Regionales', 'Soporte a proyectos regionales', 1),
(15, 4, 'Configuración de central telefónica', 'Configuración de PBX y centrales telefónicas', 1),
(16, 4, 'Instalación de cableado telefónico', 'Instalación de infraestructura telefónica', 1),
(17, 4, 'Instalación nueva extensión', 'Instalación de nuevas extensiones telefónicas', 1),
(18, 4, 'Reubicación de teléfono', 'Reubicación de equipos telefónicos', 1),
(19, 4, 'Otro requerimiento asociado a telefónia', 'Otros servicios de telefonía', 1),
(20, 5, 'Instalación / Configuración de ambiente de red', 'Configuración de ambientes de red', 1),
(21, 5, 'Reubicación punto de red', 'Reubicación de puntos de red', 1),
(22, 5, 'Requerimientos vinculados la cámara de seguridad', 'Soporte a cámaras de seguridad', 1),
(23, 5, 'Swicht, Router, Patch panel', 'Mantenimiento de equipos de red', 1),
(24, 5, 'Otros requerimientos vinculados a Red', 'Otros servicios de red', 1),
(25, 6, 'Sistemas administrativos y Judiciales (Instalación, Configuración)', 'Instalación de sistemas administrativos y judiciales', 1),
(26, 6, 'Automatización de procesos administrativos internos', 'Automatización de procesos', 1),
(27, 6, 'Sistema JURIS2000 (Acceso, Instalación y configuración)', 'Soporte al sistema JURIS2000', 1),
(28, 6, 'Soporte Control de Asistencia-Gisa', 'Soporte al sistema de control de asistencia', 1),
(29, 6, 'Sistema Independencia (Acceso, Instalación y configuración)', 'Soporte al sistema Independencia', 1),
(30, 6, 'Instalación de aplicaciones informáticas', 'Instalación de aplicaciones generales', 1),
(31, 6, 'Otros requerimientos vinculados a Sistemas', 'Otros servicios de sistemas', 1),
(32, 7, 'Antivirus (Instalación, Actualización, Ejecutar Análisis)', 'Gestión de antivirus', 1),
(33, 7, 'Adiestramiento en software de ofimática', 'Capacitación en ofimática', 1),
(34, 7, 'Configuración acceso Internet', 'Configuración de acceso a Internet', 1),
(35, 7, 'Configuración de carpeta compartida en la RED', 'Configuración de carpetas compartidas', 1),
(36, 7, 'Instalación y/o Configuración de impresora', 'Configuración de impresoras', 1),
(37, 7, 'Instalación, Diagnostico y/o reparación de computador (hardware, software)', 'Reparación de computadoras', 1),
(38, 7, 'Restauración del perfil de usuario', 'Restauración de perfiles de usuario', 1),
(39, 7, 'Cuentas de usuarios MAGISTRATURA (Crear, Deshabilitar, Desbloquear)', 'Gestión de cuentas de magistratura', 1),
(40, 7, 'Cuentas de EDITORES TSJ (Solicitud, Deshabilitar, Desbloquear)', 'Gestión de cuentas de editores', 1),
(41, 7, 'Movimientos de equipos informáticos (reubicación, asignación, reparación, desincorporación)', 'Gestión de equipos informáticos', 1),
(42, 7, 'Préstamo de activos informático', 'Préstamo de equipos', 1),
(43, 7, 'Realizar respaldo', 'Realización de backups', 1),
(44, 7, 'Recuperación de información', 'Recuperación de datos', 1),
(45, 7, 'Otro requerimiento asociado a Soporte', 'Otros servicios de soporte', 1),
(46, 8, 'Diagnostico/reemplazo cartuchos de impresión', 'Gestión de cartuchos de impresión', 1),
(47, 8, 'Diagnostico/reemplazo toner de impresión', 'Gestión de toners', 1),
(48, 8, 'Mantenimiento de Impresora', 'Mantenimiento de impresoras', 1),
(49, 8, 'Mantenimiento de Fotocopiadora', 'Mantenimiento de fotocopiadoras', 1),
(50, 8, 'Reparación de Impresora', 'Reparación de impresoras', 1),
(51, 8, 'Reparación de Fotocopiadora', 'Reparación de fotocopiadoras', 1),
(52, 8, 'Reparación de Equipos Electro Mecánicos', 'Reparación de equipos electromecánicos', 1),
(53, 8, 'Atascos de Papel', 'Resolución de atascos de papel', 1),
(54, 8, 'Otros Requerimientos', 'Otros servicios de taller', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `TicketAdjuntos`
--

CREATE TABLE `TicketAdjuntos` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `tipo_archivo` varchar(100) DEFAULT NULL,
  `tamano_bytes` int(11) DEFAULT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `subido_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Tickets`
--

CREATE TABLE `Tickets` (
  `id` int(11) NOT NULL,
  `numero_ticket` varchar(20) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `dependencia_id` int(11) NOT NULL,
  `lugar_area` varchar(150) NOT NULL,
  `area_id` int(11) NOT NULL,
  `servicio_id` int(11) NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `prioridad` enum('baja','media','alta','urgente') DEFAULT 'media',
  `estado` enum('Nuevo','Asignado','En Proceso','Cerrado Exitosamente','Cerrado No Exitoso') DEFAULT 'Nuevo',
  `tecnico_asignado` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `solucion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Usuarios`
--

CREATE TABLE `Usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `dependencia_id` int(11) DEFAULT NULL,
  `privilegio` enum('admin','tecnico','usuario') DEFAULT 'usuario',
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `Usuarios`
--

INSERT INTO `Usuarios` (`id`, `usuario`, `contrasena`, `nombre`, `correo`, `dependencia_id`, `privilegio`, `activo`) VALUES
(7, 'pablo', '$2y$10$nZu2ybHuVdoK5rF/H4iLW.DfxQ27Q1P1TkeTZN0lyolRpycGuuDHu', 'Pablo Zambrano', 'pablojz70@gmail.com', 24, 'admin', 1),
(34, 'medimilm', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Milana Maria Medina Ramirez', 'medimilm@correo.local', 24, 'admin', 1),
(35, 'choucesh1', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Cesar Herney Chourio Ruiz', NULL, 24, 'tecnico', 1),
(36, 'dtoro', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Dionny Toro', NULL, 24, 'tecnico', 1),
(37, 'torredgj1', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Edgar Jesus Torrealba Uzcategui', NULL, 24, 'tecnico', 1),
(38, 'torreladio', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Eladio Antonio Torres Peña', NULL, 24, 'tecnico', 1),
(39, 'guevernc', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Ernesto Cherminy Guevara Vielma', NULL, 24, 'tecnico', 1),
(40, 'gamegerar', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'GERARDO GAMEZ MONRIAL', NULL, 24, 'tecnico', 1),
(41, 'iscahece1', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Hector Eduardo Iscala Ramirez', NULL, 24, 'tecnico', 1),
(42, 'rodrivax', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Ivan Rodriguez Plaza', NULL, 24, 'tecnico', 1),
(43, 'gutijeac', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Jean Carlos Gutierrez Guzman', NULL, 24, 'tecnico', 1),
(44, 'torojeap', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Jean Paul Toro Rojo', NULL, 24, 'tecnico', 1),
(45, 'vergkary', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Karen Yelena Vergara Toro', NULL, 24, 'tecnico', 1),
(46, 'lagumarx', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Martha Laguado de Albarran', NULL, 24, 'tecnico', 1),
(47, 'diazmaui', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Mauro Imar Diaz Dugarte', NULL, 24, 'tecnico', 1),
(48, 'penamilj', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'MILJAIR JOSUE PEÑA ALAÑA', NULL, 24, 'tecnico', 1),
(49, 'rojanorx', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Norbedy Rojas Lacruz', NULL, 24, 'tecnico', 1),
(50, 'davioran', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Orangel Davila', NULL, 24, 'tecnico', 1),
(51, 'molirafa1', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Rafael Augusto Molina Ruiz', NULL, 24, 'tecnico', 1),
(52, 'davisaue1', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Saul Enrique Davila  Piña', NULL, 24, 'tecnico', 1),
(53, 'valetome', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Tomas Elias Valera Torrealba', NULL, 24, 'tecnico', 1),
(54, 'cedevicm', '$2y$10$Ti01cRkBSS7KB2ReRD7IsOYm2hX.mxPRtuA1kyMI06NjtmYdwUZnq', 'Victor manuel Cedeño Vega', 'usuario@gmail.com', 24, 'tecnico', 1),
(55, 'rincfrea', '$2y$10$vrhEkamdkBkylXlgES2HsOuM4KUTNAjXQYfSwCSSGXz6sCQ9tPWdC', 'Freddy Alejandro Rincon Rivas', 'rincfrea@correo.local', 24, 'admin', 1),
(56, 'suardarc1', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Darsy Coromoto Suarez de Hull', NULL, 24, 'usuario', 1),
(57, 'montmarm', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Maria Mercedes Montilla Labrador', NULL, 24, 'usuario', 1),
(58, 'verglilx', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Lilibeth Vergara Vergara', NULL, 24, 'usuario', 1),
(59, 'albosabc', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Sabrina Coromoto Albornoz Quintero', NULL, 24, 'usuario', 1),
(60, 'riermara', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Marbel Andreina Riera Castillo', NULL, 24, 'usuario', 1),
(61, 'santjulc1', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Juliet Coromoto Santiago Santiago', NULL, 24, 'usuario', 1),
(62, 'ferndanm', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Danny Marcelo Fernandez', NULL, 24, 'usuario', 1),
(63, 'liscdory', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Doris Y. Liscano Carrillo', NULL, 24, 'usuario', 1),
(64, 'molidouj', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Douglas Javier Molina', NULL, 24, 'usuario', 1),
(65, 'vasqaure', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Aura Elena Vasquez Ceballos', NULL, 24, 'usuario', 1),
(66, 'velaasdx', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Asdrubal Jose Velasquez Patiño', NULL, 24, 'usuario', 1),
(67, 'Leonherc', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Hermelinda C. Leon Avendaño', NULL, 24, 'usuario', 1),
(68, 'chackary', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Karly Yulianny Chacon Pajaro', NULL, 24, 'usuario', 1),
(69, 'lduran', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Liliana Duran', NULL, 24, 'usuario', 1),
(70, 'barrluif', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Luis Fernando Barrios Romero', NULL, 24, 'usuario', 1),
(71, 'castmaro', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Maria Ofelia Castillo Ramirez', NULL, 24, 'usuario', 1),
(72, 'fonsmaya', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Mayra Alejandra Fonseca Carmona', NULL, 24, 'usuario', 1),
(73, 'penarafa', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Rafael Antonio Peña Rodriguez', NULL, 24, 'usuario', 1),
(74, 'scarrero', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Sandra Carrero', NULL, 24, 'usuario', 1),
(75, 'gonzwuij', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Wuilson Jose Gonzalez Fernandez', NULL, 24, 'usuario', 1),
(76, 'larapaug', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Paula Geraldines Lara Segovia', NULL, 24, 'usuario', 1),
(77, 'anguyerd', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Yerika Del Carmen Angulo Saavedra', NULL, 24, 'usuario', 1),
(78, 'jereyurv', '$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu', 'Yuraima Vianney Jerez de Torres', NULL, 24, 'usuario', 1),
(79, 'marsusa', '$2y$10$G7PwtA5OI6BZmlvtmhZSLuyZ4/DKU88RQG8qx.OhAKe7qCltEJryS', 'MARQUEZ VARGAS, SUSAN ADRIANA', 'marsusa@correo.local', 24, 'tecnico', 1),
(80, 'aponmele', '$2y$10$tKfsax.HBcxnv.C2OqJYh.ibosxsMLTydGzSPwfBK6mGa12CfYncS', 'APONTE  PUENTES, MELWIN ENRIQUE', 'aponmele@correo.local', 24, 'tecnico', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `Adjuntos`
--
ALTER TABLE `Adjuntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indices de la tabla `AreasSoporte`
--
ALTER TABLE `AreasSoporte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `Dependencias`
--
ALTER TABLE `Dependencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `HistorialTickets`
--
ALTER TABLE `HistorialTickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `Logs`
--
ALTER TABLE `Logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `Servicios`
--
ALTER TABLE `Servicios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `area_servicio` (`area_id`,`nombre`),
  ADD KEY `idx_servicios_area` (`area_id`);

--
-- Indices de la tabla `TicketAdjuntos`
--
ALTER TABLE `TicketAdjuntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `subido_por` (`subido_por`);

--
-- Indices de la tabla `Tickets`
--
ALTER TABLE `Tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_ticket` (`numero_ticket`),
  ADD KEY `dependencia_id` (`dependencia_id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `servicio_id` (`servicio_id`),
  ADD KEY `idx_tickets_usuario` (`usuario_id`),
  ADD KEY `idx_tickets_estado` (`estado`),
  ADD KEY `idx_tickets_fecha` (`fecha_creacion`),
  ADD KEY `idx_tickets_tecnico` (`tecnico_asignado`);

--
-- Indices de la tabla `Usuarios`
--
ALTER TABLE `Usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `idx_usuarios_dependencia` (`dependencia_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `Adjuntos`
--
ALTER TABLE `Adjuntos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `AreasSoporte`
--
ALTER TABLE `AreasSoporte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `Dependencias`
--
ALTER TABLE `Dependencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT de la tabla `HistorialTickets`
--
ALTER TABLE `HistorialTickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `Logs`
--
ALTER TABLE `Logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `Servicios`
--
ALTER TABLE `Servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `TicketAdjuntos`
--
ALTER TABLE `TicketAdjuntos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `Tickets`
--
ALTER TABLE `Tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `Usuarios`
--
ALTER TABLE `Usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `Adjuntos`
--
ALTER TABLE `Adjuntos`
  ADD CONSTRAINT `Adjuntos_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `Tickets` (`id`);

--
-- Filtros para la tabla `HistorialTickets`
--
ALTER TABLE `HistorialTickets`
  ADD CONSTRAINT `HistorialTickets_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `Tickets` (`id`),
  ADD CONSTRAINT `HistorialTickets_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `Usuarios` (`id`);

--
-- Filtros para la tabla `Servicios`
--
ALTER TABLE `Servicios`
  ADD CONSTRAINT `Servicios_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `AreasSoporte` (`id`);

--
-- Filtros para la tabla `TicketAdjuntos`
--
ALTER TABLE `TicketAdjuntos`
  ADD CONSTRAINT `TicketAdjuntos_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `Tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `TicketAdjuntos_ibfk_2` FOREIGN KEY (`subido_por`) REFERENCES `Usuarios` (`id`);

--
-- Filtros para la tabla `Tickets`
--
ALTER TABLE `Tickets`
  ADD CONSTRAINT `Tickets_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `Usuarios` (`id`),
  ADD CONSTRAINT `Tickets_ibfk_2` FOREIGN KEY (`dependencia_id`) REFERENCES `Dependencias` (`id`),
  ADD CONSTRAINT `Tickets_ibfk_3` FOREIGN KEY (`area_id`) REFERENCES `AreasSoporte` (`id`),
  ADD CONSTRAINT `Tickets_ibfk_4` FOREIGN KEY (`servicio_id`) REFERENCES `Servicios` (`id`),
  ADD CONSTRAINT `Tickets_ibfk_5` FOREIGN KEY (`tecnico_asignado`) REFERENCES `Usuarios` (`id`);

--
-- Filtros para la tabla `Usuarios`
--
ALTER TABLE `Usuarios`
  ADD CONSTRAINT `Usuarios_ibfk_1` FOREIGN KEY (`dependencia_id`) REFERENCES `Dependencias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
