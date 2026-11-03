
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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admi_municipal` (
  `Id` int(11) NOT NULL,
  `Id_Municipio` int(11) NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `Id_Municipio` (`Id_Municipio`),
  CONSTRAINT `1` FOREIGN KEY (`Id`) REFERENCES `empleado` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`Id_Municipio`) REFERENCES `municipio` (`Id_Municipio`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asigna` (
  `Id_Ruta` int(11) NOT NULL,
  `Id` int(11) NOT NULL,
  PRIMARY KEY (`Id_Ruta`,`Id`),
  KEY `Id` (`Id`),
  CONSTRAINT `fk_asigna_admi` FOREIGN KEY (`Id`) REFERENCES `admi_municipal` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asigna_ruta` FOREIGN KEY (`Id_Ruta`) REFERENCES `ruta` (`Id_Ruta`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `camion` (
  `Id_Camion` int(11) NOT NULL AUTO_INCREMENT,
  `Matricula` varchar(30) NOT NULL,
  `Capacidad` decimal(10,2) DEFAULT NULL,
  `Estado` varchar(50) NOT NULL DEFAULT 'disponible',
  `Fecha_Baja` datetime DEFAULT NULL,
  `Motivo_Baja` varchar(500) DEFAULT NULL,
  `Id_Municipio` int(11) NOT NULL,
  `Id_Ruta` int(11) DEFAULT NULL,
  `Latitud` decimal(10,7) DEFAULT NULL,
  `Longitud` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`Id_Camion`),
  UNIQUE KEY `Matricula` (`Matricula`),
  KEY `Id_Municipio` (`Id_Municipio`),
  KEY `idx_camion_id_ruta` (`Id_Ruta`),
  CONSTRAINT `fk_camion_ruta` FOREIGN KEY (`Id_Ruta`) REFERENCES `ruta` (`Id_Ruta`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `centro_acopio` (
  `Id_Centro` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(30) NOT NULL,
  `Tipo` varchar(30) NOT NULL DEFAULT 'centro_acopio',
  `Ubicacion` varchar(255) DEFAULT NULL,
  `Capacidad` decimal(10,2) NOT NULL CHECK (`Capacidad` > 0),
  `Cantidad_Almacenada` decimal(10,2) DEFAULT 0.00,
  `Estado` varchar(30) NOT NULL DEFAULT 'activo',
  `Tipos_Residuo` varchar(255) DEFAULT NULL,
  `Id_Municipio` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id_Centro`),
  KEY `idx_centro_acopio_municipio` (`Id_Municipio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chofer` (
  `Id` int(11) NOT NULL,
  `idCuadrilla` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  CONSTRAINT `1` FOREIGN KEY (`Id`) REFERENCES `empleado` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contenedor` (
  `Id_Contenedor` int(11) NOT NULL AUTO_INCREMENT,
  `Numero_Contenedor` int(11) NOT NULL,
  `Tipo_Residuo` varchar(50) DEFAULT NULL,
  `Capacidad` decimal(10,2) DEFAULT NULL,
  `Ubicacion` varchar(255) DEFAULT NULL,
  `Estado` varchar(50) DEFAULT NULL,
  `Motivo_Baja` varchar(500) DEFAULT NULL,
  `Id_Municipio` int(11) NOT NULL,
  `Id_Incidencia` int(11) DEFAULT NULL,
  `Latitud` decimal(10,7) DEFAULT NULL,
  `Longitud` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`Id_Contenedor`),
  UNIQUE KEY `uq_contenedor_numero` (`Numero_Contenedor`),
  KEY `Id_Municipio` (`Id_Municipio`),
  KEY `Id_Incidencia` (`Id_Incidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuadrilla` (
  `Id_Cuadrilla` int(11) NOT NULL AUTO_INCREMENT,
  `Turno` varchar(30) NOT NULL,
  `Num_integrantes` int(11) DEFAULT 0 CHECK (`Num_integrantes` >= 0),
  PRIMARY KEY (`Id_Cuadrilla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleado` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(30) NOT NULL,
  `Apellido` varchar(30) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `Area` varchar(30) DEFAULT NULL,
  `rol` varchar(20) DEFAULT NULL,
  `turno` varchar(20) NOT NULL DEFAULT 'Manana',
  `cedula` varchar(20) NOT NULL,
  `Estado_Cuenta` varchar(10) NOT NULL DEFAULT 'activa',
  `Debe_Cambiar_Contrasena` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Email` (`Email`),
  CONSTRAINT `chk_empleado_turno` CHECK (`turno` in ('Ma�ana','Tarde','Noche','Manana')),
  CONSTRAINT `chk_empleado_rol` CHECK (`rol` in ('admin','admi_municipal','empleado','operario','chofer','limpiador') or `rol` is null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `existe` (
  `Id_Contenedor` int(11) NOT NULL,
  `Id_Ruta` int(11) NOT NULL,
  PRIMARY KEY (`Id_Contenedor`,`Id_Ruta`),
  KEY `Id_Ruta` (`Id_Ruta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forma` (
  `Id` int(11) NOT NULL,
  `Id_Cuadrilla` int(11) NOT NULL,
  PRIMARY KEY (`Id`,`Id_Cuadrilla`),
  KEY `Id_Cuadrilla` (`Id_Cuadrilla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incidencia` (
  `Id_Incidencia` int(11) NOT NULL AUTO_INCREMENT,
  `Tipo` varchar(500) NOT NULL,
  `Descripcion` text DEFAULT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `Estado` varchar(20) NOT NULL DEFAULT 'abierta',
  `Zona` varchar(120) DEFAULT NULL,
  `Fecha_Resolucion` datetime DEFAULT NULL,
  `Latitud` decimal(10,7) DEFAULT NULL,
  `Longitud` decimal(10,7) DEFAULT NULL,
  `Numero_Contenedor` int(11) DEFAULT NULL,
  `Id_Cuadrilla` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id_Incidencia`),
  KEY `idx_incidencia_numero_contenedor` (`Numero_Contenedor`),
  KEY `idx_incidencia_cuadrilla` (`Id_Cuadrilla`),
  CONSTRAINT `fk_incidencia_cuadrilla` FOREIGN KEY (`Id_Cuadrilla`) REFERENCES `cuadrilla` (`Id_Cuadrilla`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_incidencia_numero_contenedor` FOREIGN KEY (`Numero_Contenedor`) REFERENCES `contenedor` (`Numero_Contenedor`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `informe` (
  `Id_Informe` int(11) NOT NULL AUTO_INCREMENT,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `Descripcion` text DEFAULT NULL,
  `Id_Ruta` int(11) NOT NULL,
  `Id_Cuadrilla` int(11) NOT NULL,
  PRIMARY KEY (`Id_Informe`),
  KEY `Id_Ruta` (`Id_Ruta`),
  KEY `Id_Cuadrilla` (`Id_Cuadrilla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `limpiador` (
  `Id` int(11) NOT NULL,
  `idCuadrilla` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  CONSTRAINT `1` FOREIGN KEY (`Id`) REFERENCES `empleado` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantenimiento` (
  `Id_Mantenimiento` int(11) NOT NULL AUTO_INCREMENT,
  `Fecha_Inicio` datetime NOT NULL,
  `Fecha_Fin` datetime DEFAULT NULL,
  `Tipo_Mantenimiento` varchar(100) DEFAULT NULL,
  `Descripcion` text DEFAULT NULL,
  `Estado` varchar(50) NOT NULL DEFAULT 'en proceso',
  `Id_Camion` int(11) NOT NULL,
  PRIMARY KEY (`Id_Mantenimiento`),
  KEY `Id_Camion` (`Id_Camion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maquinaria` (
  `Id_Maquinaria` int(11) NOT NULL AUTO_INCREMENT,
  `Id_Centro` int(11) NOT NULL,
  `Nombre` varchar(60) NOT NULL DEFAULT 'Maquinaria',
  `Tipo` varchar(50) DEFAULT NULL,
  `Descripcion` text DEFAULT NULL,
  `Estado` varchar(30) NOT NULL DEFAULT 'operativa',
  `Fecha_Baja` datetime DEFAULT NULL,
  `Motivo_Baja` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`Id_Maquinaria`),
  KEY `Id_Centro` (`Id_Centro`),
  CONSTRAINT `fk_maquinaria_centro` FOREIGN KEY (`Id_Centro`) REFERENCES `centro_acopio` (`Id_Centro`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `municipio` (
  `Id_Municipio` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(30) NOT NULL,
  `Departamento` varchar(50) NOT NULL,
  `Cant_Poblacion` int(11) DEFAULT NULL,
  `Estado` varchar(30) DEFAULT 'activo' CHECK (`Estado` in ('activo','inactivo')),
  PRIMARY KEY (`Id_Municipio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificacion` (
  `Id_Notificacion` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Panel_Origen` enum('admin','empleado') NOT NULL,
  `Recurso` varchar(50) NOT NULL,
  `Accion` varchar(50) NOT NULL,
  `Descripcion` varchar(255) NOT NULL,
  `Id_Empleado` int(11) DEFAULT NULL,
  `Nombre_Empleado` varchar(100) DEFAULT NULL,
  `Fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id_Notificacion`),
  KEY `idx_notificacion_panel_id` (`Panel_Origen`,`Id_Notificacion`),
  KEY `idx_notificacion_empleado` (`Id_Empleado`),
  CONSTRAINT `fk_notificacion_empleado` FOREIGN KEY (`Id_Empleado`) REFERENCES `empleado` (`Id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificacion_lectura` (
  `Id_Empleado` int(11) NOT NULL,
  `Panel` enum('admin','empleado') NOT NULL,
  `Ultimo_Id` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id_Empleado`,`Panel`),
  CONSTRAINT `fk_notificacion_lectura_empleado` FOREIGN KEY (`Id_Empleado`) REFERENCES `empleado` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operario` (
  `Id` int(11) NOT NULL,
  `Id_Centro` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `Id_Centro` (`Id_Centro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingreso_acopio` (
  `Id_Ingreso` int(11) NOT NULL AUTO_INCREMENT,
  `Id_Centro` int(11) NOT NULL,
  `Id_Empleado` int(11) DEFAULT NULL,
  `Cantidad` decimal(10,2) NOT NULL,
  `Tipo_Residuo` varchar(50) DEFAULT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id_Ingreso`),
  KEY `idx_ingreso_acopio_centro` (`Id_Centro`),
  KEY `idx_ingreso_acopio_empleado` (`Id_Empleado`),
  CONSTRAINT `fk_ingreso_acopio_centro` FOREIGN KEY (`Id_Centro`) REFERENCES `centro_acopio` (`Id_Centro`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ingreso_acopio_empleado` FOREIGN KEY (`Id_Empleado`) REFERENCES `empleado` (`Id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `realiza` (
  `Id_Ruta` int(11) NOT NULL,
  `Id_Cuadrilla` int(11) NOT NULL,
  PRIMARY KEY (`Id_Ruta`,`Id_Cuadrilla`),
  KEY `Id_Cuadrilla` (`Id_Cuadrilla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recoleccion` (
  `Id_Recoleccion` int(11) NOT NULL AUTO_INCREMENT,
  `Fecha` datetime NOT NULL,
  `Estado_Recoleccion` varchar(30) NOT NULL DEFAULT 'pendiente',
  `Volumen` decimal(10,2) DEFAULT NULL,
  `Tipo_Residuo` varchar(50) DEFAULT NULL,
  `Id_Ruta` int(11) NOT NULL,
  `Id_Cuadrilla` int(11) NOT NULL,
  PRIMARY KEY (`Id_Recoleccion`),
  KEY `Id_Ruta` (`Id_Ruta`),
  KEY `Id_Cuadrilla` (`Id_Cuadrilla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ruta` (
  `Id_Ruta` int(11) NOT NULL AUTO_INCREMENT,
  `Recorrido` text DEFAULT NULL,
  `Horario_Inicio` time NOT NULL,
  `Horario_Fin` time NOT NULL,
  `Frecuencia` varchar(50) DEFAULT NULL,
  `Distancia` decimal(8,2) DEFAULT NULL,
  `Estado` varchar(30) DEFAULT 'activa' CHECK (`Estado` in ('activa','inactiva')),
  `Id_Municipio` int(11) NOT NULL,
  PRIMARY KEY (`Id_Ruta`),
  KEY `Id_Municipio` (`Id_Municipio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
CREATE TABLE `asignacion_camion` (
  `Id_Asignacion` int(11) NOT NULL AUTO_INCREMENT,
  `Id_Camion` int(11) NOT NULL,
  `Id_Ruta` int(11) NOT NULL,
  `Fecha` date NOT NULL,
  `Hora_Inicio` time NOT NULL,
  `Hora_Fin` time NOT NULL,
  `Estado` varchar(20) NOT NULL DEFAULT 'asignada',
  PRIMARY KEY (`Id_Asignacion`),
  KEY `idx_asignacion_camion_horario` (`Id_Camion`,`Fecha`,`Hora_Inicio`,`Hora_Fin`),
  KEY `idx_asignacion_ruta_fecha` (`Id_Ruta`,`Fecha`),
  CONSTRAINT `fk_asignacion_camion_camion` FOREIGN KEY (`Id_Camion`) REFERENCES `camion` (`Id_Camion`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_asignacion_camion_ruta` FOREIGN KEY (`Id_Ruta`) REFERENCES `ruta` (`Id_Ruta`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telefono` (
  `Id` int(11) NOT NULL,
  `Telefono` varchar(20) NOT NULL,
  PRIMARY KEY (`Id`,`Telefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utiliza` (
  `Id_Cuadrilla` int(11) NOT NULL,
  `Id_Camion` int(11) NOT NULL,
  PRIMARY KEY (`Id_Cuadrilla`,`Id_Camion`),
  KEY `Id_Camion` (`Id_Camion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
ALTER TABLE `chofer`
  ADD CONSTRAINT `fk_chofer_cuadrilla` FOREIGN KEY (`idCuadrilla`) REFERENCES `cuadrilla` (`Id_Cuadrilla`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `limpiador`
  ADD CONSTRAINT `fk_limpiador_cuadrilla` FOREIGN KEY (`idCuadrilla`) REFERENCES `cuadrilla` (`Id_Cuadrilla`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `centro_acopio`
  ADD CONSTRAINT `fk_centro_acopio_municipio` FOREIGN KEY (`Id_Municipio`) REFERENCES `municipio` (`Id_Municipio`) ON DELETE SET NULL ON UPDATE CASCADE;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
