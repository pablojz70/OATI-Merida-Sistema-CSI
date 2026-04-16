-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: sistema_csi
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Adjuntos`
--

DROP TABLE IF EXISTS `Adjuntos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Adjuntos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tipo_archivo` varchar(100) DEFAULT NULL,
  `tamanio` int(11) DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `Adjuntos_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `Tickets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Adjuntos`
--

LOCK TABLES `Adjuntos` WRITE;
/*!40000 ALTER TABLE `Adjuntos` DISABLE KEYS */;
/*!40000 ALTER TABLE `Adjuntos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AreasSoporte`
--

DROP TABLE IF EXISTS `AreasSoporte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AreasSoporte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `todosven` tinyint(1) DEFAULT 1 COMMENT '0 = solo admin, 1 = todos ven',
  `orden` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AreasSoporte`
--

LOCK TABLES `AreasSoporte` WRITE;
/*!40000 ALTER TABLE `AreasSoporte` DISABLE KEYS */;
INSERT INTO `AreasSoporte` VALUES (1,'Apoyo Logístico DAR','Servicios de apoyo logístico para eventos y videoconferencias',1,1,1),(2,'Soporte a Servidores','Mantenimiento, configuración y respaldo de servidores',1,0,2),(3,'Gestión DAR','Gestión administrativa y elaboración de informes',1,0,3),(4,'Telefonía','Instalación, configuración y mantenimiento de sistemas telefónicos',1,1,4),(5,'RED','Configuración y mantenimiento de redes y equipos de red',1,1,5),(6,'Sistemas y Aplicaciones','Instalación y soporte de sistemas y aplicaciones informáticas',1,1,6),(7,'Soporte al usuario','Atención directa a usuarios, hardware y software',1,1,7),(8,'Soporte Taller','Reparación y mantenimiento de equipos e impresoras',1,1,8);
/*!40000 ALTER TABLE `AreasSoporte` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Dependencias`
--

DROP TABLE IF EXISTS `Dependencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Dependencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `nombre_corto` varchar(35) DEFAULT NULL,
  `responsable` varchar(100) DEFAULT NULL,
  `email_responsable` varchar(100) DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Dependencias`
--

LOCK TABLES `Dependencias` WRITE;
/*!40000 ALTER TABLE `Dependencias` DISABLE KEYS */;
INSERT INTO `Dependencias` VALUES (1,'AGRARIO MERIDA','AGRARIO MERIDA','STERLICCHI MATHEUS, JEANETTE DEL ROSARIO ',NULL,1),(24,'DIRECCION ADMINISTRATIVA REGIONAL DEL ESTADO MERIDA','DAR MERIDA','RINCON RIVAS, FREDDY ALEJANDRO',NULL,1),(37,'JUZGADO PRIMERO DE PRIMERA INSTANCIA CIVIL, MERCANTIL Y DEL TRANSITO DE LA CIRCUNSCRIPCION JUICIAL DEL ESTADO MERIDA','PRIMERO-DE-PRIMERA-INSTANCIA-MERIDA','HERNANDEZ , ROLANDO',NULL,1),(162,'CIRCUITO JUDICIAL PENAL DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','CJP MERIDA','RONDON , WENDY LOVELY',NULL,1),(163,'CIRCUITO JUDICIAL SECCION DE RESPONSABILIDAD PENAL ADOLESCENTES MERIDA','PENAL ADOLESCENTE MERIDA','ARANDA MÉNDEZ, HUMBERTO JOSE','Prueba@gmail.com',1),(164,'CIRCUITO JUDICIAL CON COMPETENCIA EN DELITOS DE VIOLENCIA CONTRA LA MUJER SEDE MERIDA','CVG MERIDA','TORRES ROSARIO, YEGNIN',NULL,1),(165,'CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, EXTENSION EL VIGIA.','CJP EL VIGIA.','MARTINEZ PARRA, MAILES ROSANGELA',NULL,1),(166,'OFICINA DE DESARROLLO INFORMATICO DE LA DEM','ODI','JOSE ESCOBAR',NULL,1),(167,'OFICINA DE PARTICIPACION CIUDADANA DAR MERIDA.','OARPC MERIDA','SUAREZ DE HULL , DARSY COROMOTO',NULL,1),(168,'SEGURIDAD REGIONAL DE LA D.A.R. DEL ESTADO MERIDA','SEGURIDAD MERIDA','VELASQUEZ PATIÑO, ASDRUBAL JOSE',NULL,1),(169,'RECTORIA MERIDA','RECTORIA MERIDA','MORY DUQUE, LUIS FERNANDO JESUS',NULL,1),(170,'JUZGADO SUPERIOR PRIMERO CIVIL, MERCANTIL Y TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','SUPERIOR-PRIMERO-MERIDA','DÁVILA OCHOA, YOSANNY CRISTINA',NULL,1),(171,'JUZGADO SUPERIOR SEGUNDO CIVIL, MERCANTIL Y DEL TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','SUPERIOR-SEGUNDO-MERIDA','MORY DUQUE, LUIS FERNANDO JESUS',NULL,1),(172,'JUZGADO SEGUNDO DE PRIMERA INSTANCIA CIVIL, MERCANTIL Y DEL TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','SEGUNDO-DE-PRIMERA-INSTANCIA-MERIDA','MONSALVE RIVAS, MIGUEL ANGEL',NULL,1),(173,'JUZGADO TERCERO DE PRIMERA INSTANCIA CIVIL, MERCANTIL Y DEL TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TERCERO-DE-PRIMERA-INSTANCIA-MERIDA','CALDERÓN, CARLOS ARTURO',NULL,1),(174,'JUZGADO CUARTO DE PRIMERA INSTANCIA CIVIL, MERCANTIL Y TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','CUARTO-DE-1RA-INSTANCIA-TOVAR','CONTRERAS GUERRERO, SANDRA LILIANA',NULL,1),(175,'JUZGADO SUPERIOR ESTADAL CONTENCIOSO ADMINISTRATIVO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, SEDE MERIDA','CONTENCIOSO','MORENO CAMACHO, SILVIA ELISA',NULL,1),(176,'JUZGADO DE PRIMERA INSTANCIA EN LO CIVIL, MERCANTIL Y TRANSITO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, EXTENSION EL VIGIA','PRIMERA-INSTANCIA-EL-VIGIA','RUIZ TORRES, LII ELENA',NULL,1),(177,'CIRCUITO JUDICIAL LABORAL SEDE MERIDA','LABORAL MERIDA','MONTOYA GUERRERO, DOUGLAS ARNOLDO',NULL,1),(178,'CIRCUITO JUDICIAL LABORAL SEDE EL VIGIA','LABORAL EL VIGIA','MONSALVE QUINTERO, FREDDY REINALDO',NULL,1),(179,'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS CAMPO ELIAS Y ARICAGUA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-2DO-DE-MUNICIPIO-EJIDO','VAZQUEZ AÑEZ, ALBA DEL CARMEN',NULL,1),(180,'TRIBUNAL PRIMERO DE MUNCIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DEL MUNICIPIO ANTONIO PINTO SALINAS DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, CON SEDE EN LA CIUDAD DE SANTA CRUZ','TRIBUNAL-1RO-MUNICIPIO-SANTA-CRUZ','UZCATEGUI UZCATEGUI, JOSLEDY ALICIA',NULL,1),(181,'TRIBUNAL SEGUNDO DE MUNCIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DEL MUNICIPIO SUCRE DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, CON SEDE EN LA CIUDAD DE LAGUNILLAS','TRIBUNAL-2DO-MUNICIPIO-LAGUNILLAS','DUGARTE CONTRERAS, JHONNY CARMELO',NULL,1),(182,'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS ALBERTO ADRIANI, ANDRES BELLO, OBISPO RAMOS DE LORA Y CARACCIOLO PARRA OLMEDO DE LA C.J. DEL EDO MERIDA','TRIBUNAL-1RO-MUNICIPIO-EL-VIGIA','ESTREMOR OSMA, MARIA EUGENIA',NULL,1),(183,'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS LIBERTADOR Y SANTOS MARQUINA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-1RO-MUNICIPIO-LIBERTADOR','MORY DUQUE, LUIS FERNANDO JESUS',NULL,1),(184,'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS JUSTO BRICEÑO, TULIO FEBRES CORDERO Y JULIO CESAR SALAS DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-1RO-MUNICIPIO-NUEV-BOLIVIA','MORENO CUBARRUBIA, MIRELIS COROMOTO',NULL,1),(185,'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS MIRANDA Y PUEBLO LLANO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-1RO-MUNICIPIO-TIMOTES','VILLARREAL LAGUNA, DEFIGENIO',NULL,1),(186,'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS RANGEL Y CARDENAL QUINTERO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-1RO-MUNICIPIO-MUCUCHIES','VILLARREAL LAGUNA, DEFIGENIO',NULL,1),(187,'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS TOVAR, ZEA, GUARAQUE Y ARZOBISPO CHACON DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-1RO-MUNICIPIO-TOVAR','BARRIOS HERNANDEZ, KARINA YUSVELY',NULL,1),(188,'CIRCUITO JUDICIAL SECCION DE RESPONSABILIDAD PENAL ADOLESCENTES SEDE EXT. EL VIGIA','PENAL-ADOLESCENTE-EL-VIGIA','MARTINEZ PARRA, MAILES ROSANGELA',NULL,1),(189,'TRIBUNAL PRIMERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS CAMPO ELIAS Y ARICAGUA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-1RO-DE-MUNICIPIO-EJIDO','OVIEDO SOTO, YORGI ALFONSO',NULL,1),(192,'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS ALBERTO ADRIANI, ANDRES BELLO, OBISPO RAMOS DE LORA Y CARACCIOLO PARRA OLMEDO DE LA C.J. DEL EDO MERIDA','TRIBUNAL-2DO-MUNICIPIO-EL-VIGIA','',NULL,1),(193,'TRIBUNAL TERCERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS CAMPO ELIAS Y ARICAGUA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-3RO-MUNICIPIO-EL-VIGIA','',NULL,1),(194,'TRIBUNAL CUARTO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS CAMPO ELIAS Y ARICAGUA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-4TO-MUNICIPIO-EL-VIGIA','',NULL,1),(195,'TRIBUNAL QUINTO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS CAMPO ELIAS Y ARICAGUA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-5TO-MUNICIPIO-EL-VIGIA','',NULL,1),(196,'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS TOVAR, ZEA, GUARAQUE Y ARZOBISPO CHACON DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-2DO-MUNICIPIO-TOVAR','',NULL,1),(197,'TRIBUNAL TERCERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS TOVAR, ZEA, GUARAQUE Y ARZOBISPO CHACON DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-3RO-MUNICIPIO-TOVAR','',NULL,1),(198,'TRIBUNAL PRIMERO DE MUNCIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DEL MUNICIPIO SUCRE DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA, CON SEDE EN LA CIUDAD DE LAGUNILLAS','TRIBUNAL-1RO-MUNCIPIO-LAGUNILLAS','',NULL,1),(199,'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS MIRANDA Y PUEBLO LLANO DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-2DO-MUNICIPIO-TIMOTES','',NULL,1),(200,'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS LIBERTADOR Y SANTOS MARQUINA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-2DO-MUNICIPIO-LIBERTADOR','',NULL,1),(201,'TRIBUNAL TERCERO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS LIBERTADOR Y SANTOS MARQUINA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-3RO-MUNICIPIO-LIBERTADOR','',NULL,1),(202,'TRIBUNAL CUARTO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS LIBERTADOR Y SANTOS MARQUINA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-4TO-MUNICIPIO-LIBERTADOR','',NULL,1),(203,'TRIBUNAL QUINTO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS LIBERTADOR Y SANTOS MARQUINA DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-5TO-MUNICIPIO-LIBERTADOR','',NULL,1),(204,'TRIBUNAL SEGUNDO DE MUNICIPIO ORDINARIO Y EJECUTOR DE MEDIDAS DE LOS MUNICIPIOS JUSTO BRICEÑO, TULIO FEBRES CORDERO Y JULIO CESAR SALAS DE LA CIRCUNSCRIPCION JUDICIAL DEL ESTADO MERIDA','TRIBUNAL-2DO-MUNICIPIO-NUEV-BOLIVIA','',NULL,1);
/*!40000 ALTER TABLE `Dependencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `HistorialTickets`
--

DROP TABLE IF EXISTS `HistorialTickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HistorialTickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `HistorialTickets_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `Tickets` (`id`),
  CONSTRAINT `HistorialTickets_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `Usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `HistorialTickets`
--

LOCK TABLES `HistorialTickets` WRITE;
/*!40000 ALTER TABLE `HistorialTickets` DISABLE KEYS */;
INSERT INTO `HistorialTickets` VALUES (30,28,53,'cierre','Ticket cerrado como: Cerrado Exitosamente','2026-02-11 15:33:55'),(31,30,50,'cambio_estado','Estado cambiado de \'Asignado\' a \'En Proceso\'','2026-02-12 17:38:12'),(32,35,53,'cierre','Ticket cerrado como: Cerrado Exitosamente','2026-02-26 14:39:17'),(33,31,34,'cierre','Ticket cerrado como: Cerrado Exitosamente','2026-03-11 19:54:47'),(34,37,79,'cierre','Ticket cerrado como: Cerrado Exitosamente','2026-03-11 20:34:48'),(35,38,38,'cierre','Ticket cerrado como: Cerrado Exitosamente','2026-03-12 16:14:18'),(36,41,38,'cambio_estado','Estado cambiado de \'Asignado\' a \'En Proceso\'','2026-03-12 16:23:47'),(37,40,53,'cambio_estado','Estado cambiado de \'Asignado\' a \'En Proceso\'','2026-03-12 17:14:59'),(38,40,53,'cierre','Ticket cerrado como: Cerrado Exitosamente','2026-03-12 17:52:13'),(39,39,53,'cierre','Ticket cerrado como: Cerrado Exitosamente','2026-03-13 14:10:54'),(40,36,53,'cierre','Ticket cerrado como: Cerrado Exitosamente','2026-03-16 13:48:57'),(41,49,38,'cambio_estado','Estado cambiado de \'Asignado\' a \'En Proceso\'','2026-03-16 15:06:10'),(42,34,79,'cambio_estado','Estado cambiado de \'Asignado\' a \'En Proceso\'','2026-03-16 15:18:23'),(45,31,7,'cierre','Ticket cerrado como: Cerrado Exitosamente','2026-03-24 15:36:34');
/*!40000 ALTER TABLE `HistorialTickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Logs`
--

DROP TABLE IF EXISTS `Logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Logs`
--

LOCK TABLES `Logs` WRITE;
/*!40000 ALTER TABLE `Logs` DISABLE KEYS */;
INSERT INTO `Logs` VALUES (1,3,'ACTUALIZAR_PERFIL','Perfil actualizado','2026-01-20 16:17:45'),(2,7,'ACTUALIZAR_PERFIL','Perfil actualizado','2026-01-21 19:21:11');
/*!40000 ALTER TABLE `Logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Servicios`
--

DROP TABLE IF EXISTS `Servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_servicio` (`area_id`,`nombre`),
  KEY `idx_servicios_area` (`area_id`),
  CONSTRAINT `Servicios_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `AreasSoporte` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Servicios`
--

LOCK TABLES `Servicios` WRITE;
/*!40000 ALTER TABLE `Servicios` DISABLE KEYS */;
INSERT INTO `Servicios` VALUES (1,1,'Servicio de Videoconferencia','Configuración y soporte para videoconferencias',1),(2,1,'Apoyo informático en eventos','Soporte técnico durante eventos especiales',1),(3,1,'Apoyo en servicios audiovisuales','Asistencia con equipos audiovisuales',1),(4,1,'Otros requerimientos','Otros servicios de apoyo logístico',1),(5,2,'Configuración, Verificación y Ejecución de respaldo en servidor','Gestión de backups de servidores',1),(6,2,'Diagnosticar, Resolver, Notificar falla o error en servidor','Diagnóstico y solución de problemas en servidores',1),(7,2,'Instalación de software en servidores','Instalación de aplicaciones en servidores',1),(8,2,'Actualización . Mantenimiento de Directorio Activo','Mantenimiento de Active Directory',1),(9,2,'Mantenimiento y Actualización de Servidores','Mantenimiento general de servidores',1),(10,2,'Otro requerimiento asociado a servidores','Otros servicios relacionados con servidores',1),(11,3,'Ejecución de Actividades de la ODI','Actividades de la Oficina de Desarrollo Informático',1),(12,3,'Elaborar Informes Técnicos','Elaboración de informes técnicos',1),(13,3,'Generar reportes de Gestión','Generación de reportes de gestión',1),(14,3,'Proyectos Regionales','Soporte a proyectos regionales',1),(15,4,'Configuración de central telefónica','Configuración de PBX y centrales telefónicas',1),(16,4,'Instalación de cableado telefónico','Instalación de infraestructura telefónica',1),(17,4,'Instalación nueva extensión','Instalación de nuevas extensiones telefónicas',1),(18,4,'Reubicación de teléfono','Reubicación de equipos telefónicos',1),(19,4,'Otro requerimiento asociado a telefónia','Otros servicios de telefonía',1),(20,5,'Instalación / Configuración de ambiente de red','Configuración de ambientes de red',1),(21,5,'Reubicación punto de red','Reubicación de puntos de red',1),(22,5,'Requerimientos vinculados la cámara de seguridad','Soporte a cámaras de seguridad',1),(23,5,'Swicht, Router, Patch panel','Mantenimiento de equipos de red',1),(24,5,'Otros requerimientos vinculados a Red','Otros servicios de red',1),(25,6,'Sistemas administrativos y Judiciales (Instalación, Configuración)','Instalación de sistemas administrativos y judiciales',1),(26,6,'Automatización de procesos administrativos internos','Automatización de procesos',1),(27,6,'Sistema JURIS2000 (Acceso, Instalación y configuración)','Soporte al sistema JURIS2000',1),(28,6,'Soporte Control de Asistencia-Gisa','Soporte al sistema de control de asistencia',1),(29,6,'Sistema Independencia (Acceso, Instalación y configuración)','Soporte al sistema Independencia',1),(30,6,'Instalación de aplicaciones informáticas','Instalación de aplicaciones generales',1),(31,6,'Otros requerimientos vinculados a Sistemas','Otros servicios de sistemas',1),(32,7,'Antivirus (Instalación, Actualización, Ejecutar Análisis)','Gestión de antivirus',1),(33,7,'Adiestramiento en software de ofimática','Capacitación en ofimática',1),(34,7,'Configuración acceso Internet','Configuración de acceso a Internet',1),(35,7,'Configuración de carpeta compartida en la RED','Configuración de carpetas compartidas',1),(36,7,'Instalación y/o Configuración de impresora','Configuración de impresoras',1),(37,7,'Instalación, Diagnostico y/o reparación de computador (hardware, software)','Reparación de computadoras',1),(38,7,'Restauración del perfil de usuario','Restauración de perfiles de usuario',1),(39,7,'Cuentas de usuarios MAGISTRATURA (Crear, Deshabilitar, Desbloquear)','Gestión de cuentas de magistratura',1),(40,7,'Cuentas de EDITORES TSJ (Solicitud, Deshabilitar, Desbloquear)','Gestión de cuentas de editores',1),(41,7,'Movimientos de equipos informáticos (reubicación, asignación, reparación, desincorporación)','Gestión de equipos informáticos',1),(42,7,'Préstamo de activos informático','Préstamo de equipos',1),(43,7,'Realizar respaldo','Realización de backups',1),(44,7,'Recuperación de información','Recuperación de datos',1),(45,7,'Otro requerimiento asociado a Soporte','Otros servicios de soporte',1),(46,8,'Diagnostico/reemplazo cartuchos de impresión','Gestión de cartuchos de impresión',1),(47,8,'Diagnostico/reemplazo toner de impresión','Gestión de toners',1),(48,8,'Mantenimiento de Impresora','Mantenimiento de impresoras',1),(49,8,'Mantenimiento de Fotocopiadora','Mantenimiento de fotocopiadoras',1),(50,8,'Reparación de Impresora','Reparación de impresoras',1),(51,8,'Reparación de Fotocopiadora','Reparación de fotocopiadoras',1),(52,8,'Reparación de Equipos Electro Mecánicos','Reparación de equipos electromecánicos',1),(53,8,'Atascos de Papel','Resolución de atascos de papel',1),(54,8,'Otros Requerimientos','Otros servicios de taller',1);
/*!40000 ALTER TABLE `Servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TicketAdjuntos`
--

DROP TABLE IF EXISTS `TicketAdjuntos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TicketAdjuntos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `tipo_archivo` varchar(100) DEFAULT NULL,
  `tamano_bytes` int(11) DEFAULT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `subido_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `subido_por` (`subido_por`),
  CONSTRAINT `TicketAdjuntos_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `Tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `TicketAdjuntos_ibfk_2` FOREIGN KEY (`subido_por`) REFERENCES `Usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TicketAdjuntos`
--

LOCK TABLES `TicketAdjuntos` WRITE;
/*!40000 ALTER TABLE `TicketAdjuntos` DISABLE KEYS */;
/*!40000 ALTER TABLE `TicketAdjuntos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Tickets`
--

DROP TABLE IF EXISTS `Tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `solucion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_ticket` (`numero_ticket`),
  KEY `dependencia_id` (`dependencia_id`),
  KEY `area_id` (`area_id`),
  KEY `servicio_id` (`servicio_id`),
  KEY `idx_tickets_usuario` (`usuario_id`),
  KEY `idx_tickets_estado` (`estado`),
  KEY `idx_tickets_fecha` (`fecha_creacion`),
  KEY `idx_tickets_tecnico` (`tecnico_asignado`),
  CONSTRAINT `Tickets_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `Usuarios` (`id`),
  CONSTRAINT `Tickets_ibfk_2` FOREIGN KEY (`dependencia_id`) REFERENCES `Dependencias` (`id`),
  CONSTRAINT `Tickets_ibfk_3` FOREIGN KEY (`area_id`) REFERENCES `AreasSoporte` (`id`),
  CONSTRAINT `Tickets_ibfk_4` FOREIGN KEY (`servicio_id`) REFERENCES `Servicios` (`id`),
  CONSTRAINT `Tickets_ibfk_5` FOREIGN KEY (`tecnico_asignado`) REFERENCES `Usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Tickets`
--

LOCK TABLES `Tickets` WRITE;
/*!40000 ALTER TABLE `Tickets` DISABLE KEYS */;
INSERT INTO `Tickets` VALUES (28,'CSI2026021190661',53,1,'Area Bienes Publicos',7,35,'conexion','el punto de red no funciona','media','Cerrado Exitosamente',53,'2026-02-11 15:30:17','2026-02-11 15:33:55','se cambia el patch cord a otro punto de red'),(29,'CSI2026021218907',34,177,'SALA DE AUDIENCIA',7,41,'CAMBIO DE EQUIPOS','EQUIPO DE LA COORIDNACION JUDICIAL A LA SALA DE JUICIO\r\n\r\nCONFIGURAR EL PERFIL DEL TECNICO AUDIOVISUAL, CONTEMPLAR RESPALDO DE DATOS Y COLOCAR QUEMADORA','media','En Proceso',50,'2026-02-12 14:17:46',NULL,NULL),(30,'CSI2026021260002',34,24,'OATI MERIDA',7,37,'INSTALACION DE TARJETA DE VIDEO','A LOS FINES RECUPERACION DE CABLE VGA PARA SER USADO EN EL AREA DE FASDEM','media','En Proceso',50,'2026-02-12 16:55:04',NULL,NULL),(31,'CSI2026021259809',34,24,'TELEMATICA',1,1,'VIDEOCONFERENCIA NACIONAL SEGURIDAD-DEM','VIDEOCONFERENCIA SEGURIDAD-DEM \r\n12/2/2026 A LAS 2:PM\r\nPARTICIPANTES 1 PERSONA (ASDRUBAL VELASQUEZ)','media','Cerrado Exitosamente',7,'2026-02-12 17:50:13','2026-03-24 15:36:34','se  atendio la video conferencia'),(32,'CSI2026021213516',34,177,'COORDINACION JUDICIAL',7,41,'INSTALACION DE NUEVO EQUIPO','INSTALACION DE NUEVO EQUIPO DOTACION 2026. PASE DE RESPALDOS','media','Asignado',50,'2026-02-12 19:09:47',NULL,NULL),(33,'CSI2026021236506',34,177,'DESPACHO JUEZ SUPERIOR',7,37,'REPERACION PC SECRETARIA JUEZ SUPERIOR','PC VIT SERIAL A000836441','media','Nuevo',52,'2026-02-12 19:13:05',NULL,NULL),(34,'CSI2026021280063',34,163,'EQUIPO MULTIDISCIPLINARIO',4,16,'INSTALACION DE EXTENSION TELEFONICA','INSTALACION DE EXTENSION TELEFONICA','media','En Proceso',79,'2026-02-12 19:36:38',NULL,NULL),(35,'CSI2026022631241',53,1,'Juzgado Superior Primero',7,35,'Carpeta compratida CJCJPIASI','no tine la carpeta compartida con el juez','media','Cerrado Exitosamente',53,'2026-02-26 14:32:24','2026-02-26 14:39:17','instalacion de carpeta compartida CJCJPIASI'),(36,'CSI2026031132351',34,173,'POOL DE ASISTENTES',7,37,'REPARACION DE CPU','NRO BIEN 8656\r\n\r\nDIAGNOSTICO REALIZADO POR PERSONAL DE BIENES PUBLICOS. PLAN DE RECUPERACION TECNOLOGICA','media','Cerrado Exitosamente',53,'2026-03-11 20:07:40','2026-03-16 13:48:57','Se reemplaza la correa SATA'),(37,'CSI2026031191930',76,1,'DAR MERIDA. AREA DE RECEPCION',7,35,'configuracion de carpeta compartida','configuracion de carpeta compartida','media','Cerrado Exitosamente',79,'2026-03-11 20:21:12','2026-03-11 20:34:48','PROCESADO EXITOSAMENTE'),(38,'CSI2026031282519',38,1,'sala audiencia',7,45,'Copiado de grabación','Copiado de video grabación de teléfono de la ocii a pendriver del tribunal','baja','Cerrado Exitosamente',38,'2026-03-12 16:11:50','2026-03-12 16:14:18','Procesada la solicitud'),(39,'CSI2026031281564',34,172,'secretaria',7,37,'PC SE APAGA SOLO','PC SE APAGA SOLO \r\n\r\nINFORMACION RECIBIDA POR DIAGNOSTICO REALIZADO EN INVENTARIO REALIZADO POR BIENES PÚBLICOS MORAIMA RANGEL Y MARIA CASTILLO','media','Cerrado Exitosamente',53,'2026-03-12 16:14:38','2026-03-13 14:10:54','Se realiza soplado interno, se le coloca pasta térmica al procesador, se hace un diagnostico con la herramienta victoria al disco duro encontrándose que los sectores se encuentran en buenas condiciones, se le hace mantenimiento a la memoria ram y se actualiza el antivirus.'),(40,'CSI2026031258933',34,172,'SECRETARIA',7,37,'PC SE APAGA SOLO','PC SE APAGA SOLO\r\n\r\nMARCA ARTECK SIN SERIAL \r\n\r\nDIAGNOSTICO REALIZADO EN INVENTARIO DE BINES PUBLICOS MORAIMA RANGEL Y MARIA CASTILLO','media','Cerrado Exitosamente',53,'2026-03-12 16:20:09','2026-03-12 17:52:13','se sustituyo el cable sata'),(41,'CSI2026031227599',34,202,'POOL DE ASISTENTES',7,39,'FALLA DE ACCESO PC USUARIO HELLEN ALBARRAN','FALLA DE ACCESO PC USUARIO HELEN ALBARRAN','media','Cerrado Exitosamente',38,'2026-03-12 16:22:54','2026-03-16 15:06:38','solucionado'),(42,'CSI2026031266634',34,201,'POOL DE ASISTENTES',7,37,'PC NO ENCIENDE Y FALLA EN SISTEMA OPERATIVO','PC NO ENCIENDE Y FALLA EN SISTEMA OPERATIVO','media','Nuevo',45,'2026-03-12 16:33:54',NULL,NULL),(43,'CSI2026031211404',34,163,'POOL DE ASISTENTES',7,41,'EQUIPO NO ENCIENDE','EQUIPO NO ENCIENDE','media','Nuevo',45,'2026-03-12 17:13:04',NULL,NULL),(44,'CSI2026031230061',34,183,'SECRETARIA',7,37,'VERIFICACION DE PC','VERIFICACION DE PC','media','Nuevo',79,'2026-03-12 17:23:57',NULL,'LA SOLICITUD FUE RECIBIDA VIA LLAMADA TELEFONICA'),(45,'CSI2026031231549',34,24,'COMEDOR',8,52,'REPARACION DE MICROHONDAS','REPARACION DE MICROHONDAS AREA DE COMEDOR','media','Nuevo',NULL,'2026-03-12 17:35:47',NULL,NULL),(46,'CSI2026031211566',34,24,'BIENES PUBLICOS',6,31,'SISTEMA INTRADAR ACTUALIZACION','Limitar la modificación del campo Número de Bien (NRO de bien) dentro del sistema Intradar, asegurando que solo los usuarios con el rol de Coordinador de Área tengan permisos de escritura sobre este dato.\r\n\r\n\r\nDetalles de la modificación:\r\n\r\nValidación de Rol: El sistema debe verificar el perfil del usuario activo.\r\n\r\nSi el usuario NO es Coordinador: El campo \"NRO de bien\" debe aparecer en modo \"Solo lectura\" NO PUEDE SER MODIFICADO.\r\n\r\nSi el usuario ES Coordinador: El campo debe estar habilitado para edición.\r\n\r\nEl resto de  campos asociados al BIEN  puede seguir siendo editable por los perfiles (BIENES TRANSCRIPTOR), la restricción aplica exclusivamente al número de identificación del bien.','media','Nuevo',44,'2026-03-12 17:51:49',NULL,NULL),(47,'CSI2026031250454',34,24,'SALA TELEMATICA',6,31,'ACTIVACION SALA TELEMATICA DAR MERIDA','ACTIVACION DE SALA TELEMATICA PARA REALIZACION DE EVALUAIONES DEL PERSONAL 1ER TRIMESTRES 2026','media','Cerrado Exitosamente',79,'2026-03-12 18:01:15','2026-03-16 14:01:21','instalación exitosa de equipos'),(48,'CSI2026031329530',34,24,'CJP MERIDA SERVIDOR',1,4,'ELABORACION DE INFORME','Sirva la presente para saludar además de solicitar se sirvan a generar:\r\n\r\nInforme técnico de Adecuación Tecnológica para la implantación sistema judicial JURIS2000 en:\r\n\r\n- Circuito de Violencia contra la Mujer   extension El Vigia\r\n- Circuito de Violencia contra la Mujer sede Merida\r\n\r\n\r\n El objetivo es certificar que ambos espacios cuenten con las condiciones de infraestructura, climatización y conectividad necesarias para el despliegue del sistema judicial.\r\n\r\nConsiderar:\r\n\r\n1. Diagnóstico de la infraestructura tecnológica área se servidores (equipamiento  especificaciones técnicas y adecuacion)\r\n2. Diagnóstico de la Infraestructura tecnológica área PC clientes (especificaciones técnicas de los clientes \r\n3. Antecedentes \r\n4. Memoria fotográfica de los espacios\r\n5. Conclusiones y recomendaciones \r\n\r\nEsto debe ser remitido a la coordinación a los fines de revisar y enviar a nivel central a través de los canales regulares \r\n\r\nResponsable de dichos informes \r\n\r\nCJP Vigía: Victor Cedeño , Marta Laguado\r\n\r\nCJP Mérida: Norbedy Rojas , Cesar Chourio','media','Nuevo',NULL,'2026-03-13 17:42:49',NULL,NULL),(49,'CSI2026031651107',38,1,'alguacilazgo',7,45,'No enciende pc','Computador encendido pero no da video','media','Cerrado Exitosamente',38,'2026-03-16 15:05:02','2026-03-16 15:07:01','solucionado, monitor bloqueado por bajon de luz'),(51,'CSI2026032472246',84,162,'en  despacho  no funciona  la  impresora',7,36,'No esta  configurada  la  Impresora','En la  MAquina  de la entrada No esta  configurada  la  Impresora','media','Cerrado Exitosamente',85,'2026-03-24 14:22:48','2026-03-24 14:47:19','EL usuario puso  la  dependencia  mal');
/*!40000 ALTER TABLE `Tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Usuarios`
--

DROP TABLE IF EXISTS `Usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `dependencia_id` int(11) DEFAULT NULL,
  `privilegio` enum('admin','tecnico','usuario') DEFAULT 'usuario',
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `idx_usuarios_dependencia` (`dependencia_id`),
  CONSTRAINT `Usuarios_ibfk_1` FOREIGN KEY (`dependencia_id`) REFERENCES `Dependencias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Usuarios`
--

LOCK TABLES `Usuarios` WRITE;
/*!40000 ALTER TABLE `Usuarios` DISABLE KEYS */;
INSERT INTO `Usuarios` VALUES (7,'pablo','$2y$10$nZu2ybHuVdoK5rF/H4iLW.DfxQ27Q1P1TkeTZN0lyolRpycGuuDHu','Pablo Zambrano','pablojz70@gmail.com',24,'admin',1),(34,'medimilm','$2y$10$oTteeY/c488mke/fPe84j.XM.s2QvlTZ2ppNoyH/Cx2ox52dgvkL.','Milana Maria Medina Ramirez','milanamedina@gmail.com',24,'admin',1),(35,'choucesh1','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Cesar Herney Chourio Ruiz',NULL,24,'tecnico',1),(36,'dtoro','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Dionny Toro',NULL,24,'tecnico',1),(37,'torredgj1','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Edgar Jesus Torrealba Uzcategui',NULL,24,'tecnico',1),(38,'torreladio','$2y$10$trKu1f6YYTvTknWrJQ40QecZLFM7NlubdXBCvDcKD3whnDNCfwyCa','Eladio Antonio Torres Peña','eladiotorres00@gmail.com',24,'tecnico',1),(39,'guevernc','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Ernesto Cherminy Guevara Vielma',NULL,24,'tecnico',1),(40,'gamegerar','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','GERARDO GAMEZ MONRIAL',NULL,24,'tecnico',1),(41,'iscahece1','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Hector Eduardo Iscala Ramirez',NULL,24,'tecnico',1),(42,'rodrivax','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Ivan Rodriguez Plaza',NULL,24,'tecnico',1),(43,'gutijeac','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Jean Carlos Gutierrez Guzman',NULL,24,'tecnico',1),(44,'torojeap','$2y$10$mDRlSDsSjg0.yQuM3iIwFeYH8nRuL3YfxPjNBru8NcqaxkZkflgYS','Jean Paul Toro Rojo','oatijptr@gmail.com',24,'tecnico',1),(45,'vergkary','$2y$10$ukEjaS7DLzwlOoUNch7Mqu.XjL0vdII5y2cq3tyR06jvQQqNEgQXm','Karen Yelena Vergara Toro','KARENYELE@GMAIL.COM',24,'tecnico',1),(46,'lagumarx','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Martha Laguado de Albarran',NULL,24,'tecnico',1),(47,'diazmaui','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Mauro Imar Diaz Dugarte',NULL,24,'tecnico',1),(48,'penamilj','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','MILJAIR JOSUE PEÑA ALAÑA',NULL,24,'tecnico',1),(49,'rojanorx','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Norbedy Rojas Lacruz',NULL,24,'tecnico',1),(50,'davioran','$2y$10$sYIxVmbAXucDpinHS4tnJeqcCtHHjzHTdEC/bYxMKX0Ney2ldDwiO','Orangel Davila','oradavila@gmail.com',24,'tecnico',1),(51,'molirafa1','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Rafael Augusto Molina Ruiz',NULL,24,'tecnico',1),(52,'davisaue1','$2y$10$yhuKi0Fe4rzyJ6l4mi3XOuF2T4Z3wnt1yfMiKmDX0mKAXIEcszZti','Saul Enrique Davila  Piña','davisaue1.oati@gmail.com',24,'tecnico',1),(53,'valetome','$2y$10$ysIHuskWXOwfF/xX.Y73oeSKnmGvlWksXIeWmLnPNTCB6J7yCZumS','Tomas Elias Valera Torrealba','valetome.oati@gmail.com',24,'tecnico',1),(54,'cedevicm','$2y$10$Ti01cRkBSS7KB2ReRD7IsOYm2hX.mxPRtuA1kyMI06NjtmYdwUZnq','Victor manuel Cedeño Vega','usuario@gmail.com',24,'tecnico',1),(55,'rincfrea','$2y$10$yDD0sVB6lRdRK4sTqAdqee8PfYZvwZDQPUauCuQsiZV2NQzlktq9e','Freddy Alejandro Rincon Rivas','rincfrea@correo.local',24,'admin',1),(56,'suardarc1','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Darsy Coromoto Suarez de Hull',NULL,24,'usuario',1),(57,'montmarm','$2y$10$5Mu7Etv3d4YnKEQCO5bdJOBADDAlQAHEK7r.V.J4Sqa.8UxJMjUTG','Maria Mercedes Montilla Labrador','montmarm@correo.com',24,'usuario',1),(58,'verglilx','$2y$10$1g/crjg/FVHCptNSNN86xueyoWhEqMLCLxtebeVFTzen2KYj.KcIu','Lilibeth Vergara Vergara','verglilx@correo.com',24,'usuario',1),(59,'albosabc','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Sabrina Coromoto Albornoz Quintero',NULL,24,'usuario',1),(60,'riermara','$2y$10$Mz6BVGcF/K3lkq1gfBvYHOsnQHaJLjXOFzCnV1KaBXguGBVgmPPUu','Marbel Andreina Riera Castillo','riermara@correo.com',24,'usuario',1),(61,'santjulc1','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Juliet Coromoto Santiago Santiago',NULL,24,'usuario',1),(62,'ferndanm','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Danny Marcelo Fernandez',NULL,24,'usuario',1),(63,'liscdory','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Doris Y. Liscano Carrillo',NULL,24,'usuario',1),(64,'molidouj','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Douglas Javier Molina',NULL,24,'usuario',1),(65,'vasqaure','$2y$10$541hgc7ETRr32y2j9LnlUOSKeP5aRZW6jrQNqj7Y1RUzpQZHJa7X6','Aura Elena Vasquez Ceballos','vasqaure@correo.com',24,'usuario',1),(66,'velaasdx','$2y$10$JwaWVWMBo1.95DasOEIcseEzB9nMA.AxAQnhfnUzQY3/YozsOIShG','Asdrubal Jose Velasquez Patiño','velaasdx@correo.com',24,'usuario',1),(67,'Leonherc','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Hermelinda C. Leon Avendaño',NULL,24,'usuario',1),(68,'chackary','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Karly Yulianny Chacon Pajaro',NULL,24,'usuario',1),(69,'lduran','$2y$10$ehiuC2SzuAaRya1UAs53GeEQAyu1R5z6CkGgV9QgnMCQScwZzuVMC','Liliana Duran','lduran@correo.com',24,'usuario',1),(70,'barrluif','$2y$10$ZgAzF6mec0Ow3wSuUuozEuRD7tFOAUbpMhjk/1z5ToncvLEOJM5m6','Luis Fernando Barrios Romero','barrluif@correo.com',24,'usuario',1),(71,'castmaro','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Maria Ofelia Castillo Ramirez',NULL,24,'usuario',1),(72,'fonsmaya','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Mayra Alejandra Fonseca Carmona',NULL,24,'usuario',1),(73,'penarafa','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Rafael Antonio Peña Rodriguez',NULL,24,'usuario',1),(74,'scarrero','$2y$10$A.q.fsVcBz.pnHnNJPcBmejnnOEUBHO9YWkPMMTix.aiFuX/2O5Iu','Sandra Carrero','scarrero@correo.com',24,'usuario',1),(75,'gonzwuij','$2y$10$yySHKzUPKYuwFS8i1R.VkOFCuTn4SEPyWawbni99l8IjHY69bolEO','Wuilson Jose Gonzalez Fernandez','gonzwuij@correo.com',24,'usuario',1),(76,'larapaug','$2y$10$3E7p.Cdyd4ybH2i6P32Sk.YzohYKGn2XEYEkx.A9BywYbehZhOduW','Paula Geraldines Lara Segovia','larapaug@gmail.com',24,'usuario',1),(77,'anguyerd','$2y$10$/TrYmBED.ZeLTEPX6umwteAiEQoNizuyoTjZm3Sx7TrhDxUl3Wx2S','Yerika Del Carmen Angulo Saavedra','anguyerd@gmail.com',24,'usuario',1),(78,'jereyurv','$2y$10$CzLJqHDMf8s6YM5Y.u/knu95QilF3stf.8Xxqhe9qOjb7oeI/Xzcu','Yuraima Vianney Jerez de Torres',NULL,24,'usuario',1),(79,'marsusa','$2y$10$U7ZV9Z8GD6v6bUuQ2ESgyec/pKxDzGfKZCO4MQL18z33T2DSoDKaG','MARQUEZ VARGAS, SUSAN ADRIANA','susan86marquez@gmail.com',24,'tecnico',1),(80,'aponmele','$2y$10$tKfsax.HBcxnv.C2OqJYh.ibosxsMLTydGzSPwfBK6mGa12CfYncS','APONTE  PUENTES, MELWIN ENRIQUE','aponmele@correo.local',24,'tecnico',1),(81,'maroa','$2y$10$.FAfR/QmoGl1UhmbEeYONOSETFm9a3rG5VHgoSaG51PLi9A1wqDeG','Mayela Roa','mroa@correo.local',24,'usuario',1),(82,'javila','$2y$10$/odyRJ.JiG3ylVeL2E.FZ.A.8h7tazAcQpUIGGdM7vwf4OqISe5Uu','Jorge Avila','javila@correo.local',24,'usuario',1),(83,'quinstrm','$2y$10$/bi86wBtffrxGZwmtOTob.LGVGz41Kn2DZayPX/6Wb59rc5RXNA4u','Marisela Quintero','quinstrm@correo.com',24,'usuario',1),(84,'usuario','$2y$10$Lfz/hYySD2UNlll9RnrN1.vbq2Oa8GMvFlJAly19GW.WdH.xypVXC','Usuario Prueba','usuario@correo.local',162,'usuario',1),(85,'tecnico','$2y$10$vyDCAjAFamgE3fXzbgP.xOml/o4T7znaDucmFFO7x/0p4eqbMGJ1O','Tecnico Prueba','tecnico@correo.local',166,'tecnico',1);
/*!40000 ALTER TABLE `Usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuraciones`
--

DROP TABLE IF EXISTS `configuraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuraciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` varchar(50) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` varchar(20) DEFAULT 'text',
  `opciones` text DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `editable` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuraciones`
--

LOCK TABLES `configuraciones` WRITE;
/*!40000 ALTER TABLE `configuraciones` DISABLE KEYS */;
INSERT INTO `configuraciones` VALUES (1,'general','nombre_sistema','Sistema CSI - Soporte Técnico','text',NULL,'Nombre del sistema que aparece en el título',1,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(2,'general','logo_url','logo.png','text',NULL,'Ruta del archivo de logo',2,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(3,'general','color_principal','#2c3e50','color',NULL,'Color principal de la interfaz',3,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(4,'general','color_secundario','#3498db','color',NULL,'Color secundario para botones',4,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(5,'general','items_por_pagina','20','number',NULL,'Número de items por página en listados',5,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(6,'general','timezone','America/Mexico_City','select',NULL,'Zona horaria del sistema',6,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(7,'general','idioma','es','select',NULL,'Idioma del sistema',7,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(8,'email','notificaciones_activas','1','boolean',NULL,'Activar/Desactivar notificaciones por email',1,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(9,'email','smtp_host','smtp.gmail.com','text',NULL,'Servidor SMTP',2,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(10,'email','smtp_port','587','number',NULL,'Puerto SMTP',3,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(11,'email','smtp_secure','tls','select',NULL,'Tipo de seguridad',4,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(12,'email','smtp_usuario','','text',NULL,'Usuario SMTP',5,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(13,'email','smtp_password','','password',NULL,'Contraseña SMTP',6,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(14,'email','email_from','notificaciones@sistema-csi.com','text',NULL,'Email remitente',7,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(15,'email','nombre_from','Sistema CSI','text',NULL,'Nombre remitente',8,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(16,'email','email_reply_to','soporte@sistema-csi.com','text',NULL,'Email para respuestas',9,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(17,'seguridad','max_intentos_login','5','number',NULL,'Máximo intentos de login antes de bloquear',1,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(18,'seguridad','tiempo_bloqueo_minutos','30','number',NULL,'Minutos de bloqueo por intentos fallidos',2,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(19,'seguridad','sesion_expiracion_horas','8','number',NULL,'Horas para expirar sesión inactiva',3,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(20,'seguridad','requerir_contrasena_fuerte','1','boolean',NULL,'Requerir contraseña fuerte (mínimo 8 caracteres, mayúscula, número)',4,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(21,'seguridad','bloquear_ip_intentos','1','boolean',NULL,'Bloquear IP por intentos fallidos',5,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(22,'archivos','max_tamano_mb','10','number',NULL,'Tamaño máximo de archivos adjuntos en MB',1,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(23,'archivos','extensiones_permitidas','jpg,jpeg,png,pdf,doc,docx,xls,xlsx','text',NULL,'Extensiones permitidas separadas por coma',2,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(24,'archivos','ruta_uploads','uploads/','text',NULL,'Ruta donde se guardan los archivos',3,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(25,'tickets','prioridad_defecto','media','select',NULL,'Prioridad por defecto para nuevos tickets',1,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(26,'tickets','dias_cierre_automatico','30','number',NULL,'Días para cierre automático de tickets inactivos',2,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(27,'tickets','notificar_usuario_siempre','1','boolean',NULL,'Notificar siempre al usuario sobre cambios',3,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(28,'tickets','permitir_reabrir_tickets','1','boolean',NULL,'Permitir reabrir tickets cerrados',4,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(29,'tickets','tiempo_max_respuesta_horas','24','number',NULL,'Tiempo máximo para respuesta inicial en horas',5,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(30,'tickets','sla_prioridad_alta','4','number',NULL,'SLA para prioridad alta (horas)',6,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(31,'tickets','sla_prioridad_media','8','number',NULL,'SLA para prioridad media (horas)',7,1,'2026-01-19 15:03:30','2026-01-19 15:03:30'),(32,'tickets','sla_prioridad_baja','24','number',NULL,'SLA para prioridad baja (horas)',8,1,'2026-01-19 15:03:30','2026-01-19 15:03:30');
/*!40000 ALTER TABLE `configuraciones` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-24 12:19:31
