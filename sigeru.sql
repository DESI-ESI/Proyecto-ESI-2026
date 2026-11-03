-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2026 a las 16:43:48
-- Versión del servidor: 12.2.2-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sigeru`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admi_municipal`
--

CREATE TABLE `admi_municipal` (
  `Id` int(11) NOT NULL,
  `Id_Municipio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `admi_municipal`
--

INSERT INTO `admi_municipal` (`Id`, `Id_Municipio`) VALUES
(56, 1),
(60, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asigna`
--

CREATE TABLE `asigna` (
  `Id_Ruta` int(11) NOT NULL,
  `Id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `camion`
--

CREATE TABLE `camion` (
  `Id_Camion` int(11) NOT NULL,
  `Matricula` varchar(30) NOT NULL,
  `Capacidad` decimal(10,2) DEFAULT NULL,
  `Estado` varchar(50) NOT NULL DEFAULT 'disponible',
  `Id_Municipio` int(11) NOT NULL,
  `Id_Ruta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `camion`
--

INSERT INTO `camion` (`Id_Camion`, `Matricula`, `Capacidad`, `Estado`, `Id_Municipio`, `Id_Ruta`) VALUES
(25, 'STU-1053', 1.00, 'Disponible', 1, NULL),
(26, 'STU-1057', 100.00, 'Mantenimiento', 4, NULL),
(28, 'STU-1062', 100.00, 'En Ruta', 7, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `centro_acopio`
--

CREATE TABLE `centro_acopio` (
  `Id_Centro` int(11) NOT NULL,
  `Nombre` varchar(30) NOT NULL,
  `Ubicacion` varchar(255) DEFAULT NULL,
  `Capacidad` decimal(10,2) NOT NULL CHECK (`Capacidad` > 0),
  `Cantidad_Almacenada` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chofer`
--

CREATE TABLE `chofer` (
  `Id` int(11) NOT NULL,
  `idCuadrilla` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `chofer`
--

INSERT INTO `chofer` (`Id`, `idCuadrilla`) VALUES
(58, NULL),
(62, 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedor`
--

CREATE TABLE `contenedor` (
  `Id_Contenedor` int(11) NOT NULL,
  `Numero_Contenedor` int(11) NOT NULL,
  `Tipo_Residuo` varchar(50) DEFAULT NULL,
  `Capacidad` decimal(10,2) DEFAULT NULL,
  `Ubicacion` varchar(255) DEFAULT NULL,
  `Estado` varchar(50) DEFAULT NULL,
  `Motivo_Baja` varchar(500) DEFAULT NULL,
  `Id_Municipio` int(11) NOT NULL,
  `Id_Incidencia` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuadrilla`
--

CREATE TABLE `cuadrilla` (
  `Id_Cuadrilla` int(11) NOT NULL,
  `Turno` varchar(30) NOT NULL,
  `Num_integrantes` int(11) DEFAULT 0 CHECK (`Num_integrantes` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuadrilla`
--

INSERT INTO `cuadrilla` (`Id_Cuadrilla`, `Turno`, `Num_integrantes`) VALUES
(13, 'mañana', 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `Id` int(11) NOT NULL,
  `Nombre` varchar(30) NOT NULL,
  `Apellido` varchar(30) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `Area` varchar(30) DEFAULT NULL,
  `rol` varchar(20) DEFAULT NULL,
  `turno` varchar(20) NOT NULL DEFAULT 'Manana',
  `cedula` varchar(20) NOT NULL,
  `Estado_Cuenta` varchar(10) NOT NULL DEFAULT 'activa',
  `Debe_Cambiar_Contrasena` tinyint(1) NOT NULL DEFAULT 0
) ;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`Id`, `Nombre`, `Apellido`, `Email`, `password`, `Area`, `rol`, `turno`, `cedula`) VALUES
(56, 'miguel', 'radi', 'miguelradi2008@gmail.com', '$2y$10$nl/0WG2iaOkHQifQksUkuexmTUDB2Bp3OeABk3IGt.CA1k1dXAa.m', '2', 'admi_municipal', 'mañana', '57301063'),
(58, 'jorge', 'alvarez', 'jaab@gmail.com', '$2y$10$X661.kdRyUqHYTZbbYpg.eKrnjZnrp2ba5HXVKiQJDzz/g9da3BLK', 'afuera', 'chofer', 'Manana', '18589830'),
(60, 'pepato', 'potra', 'yuyuhu@gmil.com', '$2y$10$qhpS0gPSQt/cmyf8G6R4nupt/gYxt2i/e4cYTFP4uIIqJWlfOg5Nu', NULL, 'admi_municipal', 'mañana', '64052146'),
(62, 'Timoteo', 'Massaro', 'Timoteomassaro48@gmail.com', '$2y$10$shLRnHs4cs92DwFR2ZPqmuyCUUhuK1PGUaFsSqbzfHGajto7NTraa', NULL, 'chofer', 'Manana', '57003225');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `existe`
--

CREATE TABLE `existe` (
  `Id_Contenedor` int(11) NOT NULL,
  `Id_Ruta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `forma`
--

CREATE TABLE `forma` (
  `Id` int(11) NOT NULL,
  `Id_Cuadrilla` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencia`
--

CREATE TABLE `incidencia` (
  `Id_Incidencia` int(11) NOT NULL,
  `Tipo` varchar(500) NOT NULL,
  `Descripcion` text DEFAULT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `Estado` varchar(20) NOT NULL DEFAULT 'abierta',
  `Numero_Contenedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `incidencia`
--

INSERT INTO `incidencia` (`Id_Incidencia`, `Tipo`, `Descripcion`, `Fecha`, `Estado`, `Numero_Contenedor`) VALUES
(3, 'desborde', 'k', '2026-10-08 12:26:00', 'en curso', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `informe`
--

CREATE TABLE `informe` (
  `Id_Informe` int(11) NOT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `Descripcion` text DEFAULT NULL,
  `Id_Ruta` int(11) NOT NULL,
  `Id_Cuadrilla` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `informe`
--

INSERT INTO `informe` (`Id_Informe`, `Fecha`, `Descripcion`, `Id_Ruta`, `Id_Cuadrilla`) VALUES
(1, '2026-10-14 12:10:48', 'Recorrido completado: ruta #4 (La Paloma), turno mañana, 0 contenedores, horario 11:11 a 14:22.', 4, 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `limpiador`
--

CREATE TABLE `limpiador` (
  `Id` int(11) NOT NULL,
  `idCuadrilla` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento`
--

CREATE TABLE `mantenimiento` (
  `Id_Mantenimiento` int(11) NOT NULL,
  `Fecha_Inicio` datetime NOT NULL,
  `Fecha_Fin` datetime DEFAULT NULL,
  `Tipo_Mantenimiento` varchar(100) DEFAULT NULL,
  `Descripcion` text DEFAULT NULL,
  `Estado` varchar(50) NOT NULL DEFAULT 'en proceso',
  `Id_Camion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maquinaria`
--

CREATE TABLE `maquinaria` (
  `Id_Maquinaria` int(11) NOT NULL,
  `Id_Centro` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `municipio`
--

CREATE TABLE `municipio` (
  `Id_Municipio` int(11) NOT NULL,
  `Nombre` varchar(30) NOT NULL,
  `Departamento` varchar(50) NOT NULL,
  `Cant_Poblacion` int(11) DEFAULT NULL,
  `Estado` varchar(30) DEFAULT 'activo' CHECK (`Estado` in ('activo','inactivo'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `municipio`
--

INSERT INTO `municipio` (`Id_Municipio`, `Nombre`, `Departamento`, `Cant_Poblacion`, `Estado`) VALUES
(1, 'Municipio A', 'Montevideo', 203088, 'activo'),
(2, 'Municipio B', 'Montevideo', 154588, 'activo'),
(3, 'Municipio C', 'Montevideo', 145891, 'activo'),
(4, 'Municipio CH', 'Montevideo', 161751, 'activo'),
(5, 'Municipio D', 'Montevideo', 166695, 'activo'),
(6, 'Municipio E', 'Montevideo', 149054, 'activo'),
(7, 'Municipio F', 'Montevideo', 168683, 'activo'),
(9, 'Municipio G', 'Montevideo', 150393, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion`
--

CREATE TABLE `notificacion` (
  `Id_Notificacion` bigint(20) UNSIGNED NOT NULL,
  `Panel_Origen` enum('admin','empleado') NOT NULL,
  `Recurso` varchar(50) NOT NULL,
  `Accion` varchar(50) NOT NULL,
  `Descripcion` varchar(255) NOT NULL,
  `Id_Empleado` int(11) DEFAULT NULL,
  `Nombre_Empleado` varchar(100) DEFAULT NULL,
  `Fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificacion`
--

INSERT INTO `notificacion` (`Id_Notificacion`, `Panel_Origen`, `Recurso`, `Accion`, `Descripcion`, `Id_Empleado`, `Nombre_Empleado`, `Fecha`) VALUES
(2, 'admin', 'empleados', 'modificó', 'miguel modificó un empleado (Miguel Radi)', 56, 'miguel', '2026-10-14 12:38:02'),
(3, 'admin', 'empleados', 'eliminó', 'miguel eliminó un empleado', 56, 'miguel', '2026-10-14 14:35:43'),
(4, 'admin', 'empleados', 'registró', 'miguel registró un empleado (Timoteo)', 56, 'miguel', '2026-10-14 14:57:45'),
(5, 'admin', 'empleados', 'modificó', 'miguel modificó un empleado (jorge)', 56, 'miguel', '2026-10-14 15:02:57'),
(6, 'admin', 'cuadrillas', 'registró', 'miguel registró una cuadrilla', 56, 'miguel', '2026-10-14 15:05:38'),
(7, 'admin', 'empleados', 'eliminó', 'miguel eliminó un empleado', 56, 'miguel', '2026-10-14 15:06:56'),
(8, 'admin', 'empleados', 'registró', 'miguel registró un empleado (Timoteo)', 56, 'miguel', '2026-10-14 15:07:36'),
(9, 'admin', 'rutas', 'eliminó', 'miguel eliminó una ruta', 56, 'miguel', '2026-10-14 15:08:53'),
(10, 'admin', 'cuadrillas', 'eliminó', 'miguel eliminó una cuadrilla', 56, 'miguel', '2026-10-14 15:09:20'),
(11, 'admin', 'cuadrillas', 'registró', 'miguel registró una cuadrilla', 56, 'miguel', '2026-10-14 15:09:52'),
(12, 'admin', 'rutas', 'registró', 'miguel registró una ruta (La Paloma)', 56, 'miguel', '2026-10-14 15:10:22'),
(13, 'empleado', 'miruta', 'completó', 'Timoteo completó el recorrido de su ruta', 62, 'Timoteo', '2026-10-14 15:10:48'),
(14, 'admin', 'rutas', 'registró', 'miguel registró una ruta (Felipe Cardoso)', 56, 'miguel', '2026-10-15 14:35:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion_lectura`
--

CREATE TABLE `notificacion_lectura` (
  `Id_Empleado` int(11) NOT NULL,
  `Panel` enum('admin','empleado') NOT NULL,
  `Ultimo_Id` bigint(20) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificacion_lectura`
--

INSERT INTO `notificacion_lectura` (`Id_Empleado`, `Panel`, `Ultimo_Id`) VALUES
(56, 'admin', 14),
(62, 'empleado', 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operario`
--

CREATE TABLE `operario` (
  `Id` int(11) NOT NULL,
  `Id_Centro` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `realiza`
--

CREATE TABLE `realiza` (
  `Id_Ruta` int(11) NOT NULL,
  `Id_Cuadrilla` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `realiza`
--

INSERT INTO `realiza` (`Id_Ruta`, `Id_Cuadrilla`) VALUES
(3, 12),
(4, 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recoleccion`
--

CREATE TABLE `recoleccion` (
  `Id_Recoleccion` int(11) NOT NULL,
  `Fecha` datetime NOT NULL,
  `Estado_Recoleccion` varchar(30) NOT NULL DEFAULT 'pendiente',
  `Volumen` decimal(10,2) DEFAULT NULL,
  `Id_Ruta` int(11) NOT NULL,
  `Id_Cuadrilla` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ruta`
--

CREATE TABLE `ruta` (
  `Id_Ruta` int(11) NOT NULL,
  `Recorrido` text DEFAULT NULL,
  `Horario_Inicio` time NOT NULL,
  `Horario_Fin` time NOT NULL,
  `Frecuencia` varchar(50) DEFAULT NULL,
  `Distancia` decimal(8,2) DEFAULT NULL,
  `Estado` varchar(30) DEFAULT 'activa' CHECK (`Estado` in ('activa','inactiva')),
  `Id_Municipio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ruta`
--

INSERT INTO `ruta` (`Id_Ruta`, `Recorrido`, `Horario_Inicio`, `Horario_Fin`, `Frecuencia`, `Distancia`, `Estado`, `Id_Municipio`) VALUES
(4, 'La Paloma', '11:11:00', '14:22:00', '1 vez por semana', 500.00, 'activa', 1),
(5, 'Felipe Cardoso', '11:11:00', '14:22:00', '1 vez por semana', 56.00, 'activa', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefono`
--

CREATE TABLE `telefono` (
  `Id` int(11) NOT NULL,
  `Telefono` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `utiliza`
--

CREATE TABLE `utiliza` (
  `Id_Cuadrilla` int(11) NOT NULL,
  `Id_Camion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admi_municipal`
--
ALTER TABLE `admi_municipal`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Id_Municipio` (`Id_Municipio`);

--
-- Indices de la tabla `asigna`
--
ALTER TABLE `asigna`
  ADD PRIMARY KEY (`Id_Ruta`,`Id`),
  ADD KEY `Id` (`Id`);

--
-- Indices de la tabla `camion`
--
ALTER TABLE `camion`
  ADD PRIMARY KEY (`Id_Camion`),
  ADD UNIQUE KEY `Matricula` (`Matricula`),
  ADD KEY `Id_Municipio` (`Id_Municipio`),
  ADD KEY `idx_camion_id_ruta` (`Id_Ruta`);

--
-- Indices de la tabla `centro_acopio`
--
ALTER TABLE `centro_acopio`
  ADD PRIMARY KEY (`Id_Centro`);

--
-- Indices de la tabla `chofer`
--
ALTER TABLE `chofer`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `contenedor`
--
ALTER TABLE `contenedor`
  ADD PRIMARY KEY (`Id_Contenedor`),
  ADD UNIQUE KEY `uq_contenedor_numero` (`Numero_Contenedor`),
  ADD KEY `Id_Municipio` (`Id_Municipio`),
  ADD KEY `Id_Incidencia` (`Id_Incidencia`);

--
-- Indices de la tabla `cuadrilla`
--
ALTER TABLE `cuadrilla`
  ADD PRIMARY KEY (`Id_Cuadrilla`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indices de la tabla `existe`
--
ALTER TABLE `existe`
  ADD PRIMARY KEY (`Id_Contenedor`,`Id_Ruta`),
  ADD KEY `Id_Ruta` (`Id_Ruta`);

--
-- Indices de la tabla `forma`
--
ALTER TABLE `forma`
  ADD PRIMARY KEY (`Id`,`Id_Cuadrilla`),
  ADD KEY `Id_Cuadrilla` (`Id_Cuadrilla`);

--
-- Indices de la tabla `incidencia`
--
ALTER TABLE `incidencia`
  ADD PRIMARY KEY (`Id_Incidencia`),
  ADD KEY `idx_incidencia_numero_contenedor` (`Numero_Contenedor`);

--
-- Indices de la tabla `informe`
--
ALTER TABLE `informe`
  ADD PRIMARY KEY (`Id_Informe`),
  ADD KEY `Id_Ruta` (`Id_Ruta`),
  ADD KEY `Id_Cuadrilla` (`Id_Cuadrilla`);

--
-- Indices de la tabla `limpiador`
--
ALTER TABLE `limpiador`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  ADD PRIMARY KEY (`Id_Mantenimiento`),
  ADD KEY `Id_Camion` (`Id_Camion`);

--
-- Indices de la tabla `maquinaria`
--
ALTER TABLE `maquinaria`
  ADD PRIMARY KEY (`Id_Maquinaria`),
  ADD KEY `Id_Centro` (`Id_Centro`);

--
-- Indices de la tabla `municipio`
--
ALTER TABLE `municipio`
  ADD PRIMARY KEY (`Id_Municipio`);

--
-- Indices de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD PRIMARY KEY (`Id_Notificacion`),
  ADD KEY `idx_notificacion_panel_id` (`Panel_Origen`,`Id_Notificacion`),
  ADD KEY `idx_notificacion_empleado` (`Id_Empleado`);

--
-- Indices de la tabla `notificacion_lectura`
--
ALTER TABLE `notificacion_lectura`
  ADD PRIMARY KEY (`Id_Empleado`,`Panel`);

--
-- Indices de la tabla `operario`
--
ALTER TABLE `operario`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `Id_Centro` (`Id_Centro`);

--
-- Indices de la tabla `realiza`
--
ALTER TABLE `realiza`
  ADD PRIMARY KEY (`Id_Ruta`,`Id_Cuadrilla`),
  ADD KEY `Id_Cuadrilla` (`Id_Cuadrilla`);

--
-- Indices de la tabla `recoleccion`
--
ALTER TABLE `recoleccion`
  ADD PRIMARY KEY (`Id_Recoleccion`),
  ADD KEY `Id_Ruta` (`Id_Ruta`),
  ADD KEY `Id_Cuadrilla` (`Id_Cuadrilla`);

--
-- Indices de la tabla `ruta`
--
ALTER TABLE `ruta`
  ADD PRIMARY KEY (`Id_Ruta`),
  ADD KEY `Id_Municipio` (`Id_Municipio`);

--
-- Indices de la tabla `telefono`
--
ALTER TABLE `telefono`
  ADD PRIMARY KEY (`Id`,`Telefono`);

--
-- Indices de la tabla `utiliza`
--
ALTER TABLE `utiliza`
  ADD PRIMARY KEY (`Id_Cuadrilla`,`Id_Camion`),
  ADD KEY `Id_Camion` (`Id_Camion`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `camion`
--
ALTER TABLE `camion`
  MODIFY `Id_Camion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `centro_acopio`
--
ALTER TABLE `centro_acopio`
  MODIFY `Id_Centro` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contenedor`
--
ALTER TABLE `contenedor`
  MODIFY `Id_Contenedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cuadrilla`
--
ALTER TABLE `cuadrilla`
  MODIFY `Id_Cuadrilla` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `incidencia`
--
ALTER TABLE `incidencia`
  MODIFY `Id_Incidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `informe`
--
ALTER TABLE `informe`
  MODIFY `Id_Informe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  MODIFY `Id_Mantenimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `maquinaria`
--
ALTER TABLE `maquinaria`
  MODIFY `Id_Maquinaria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `municipio`
--
ALTER TABLE `municipio`
  MODIFY `Id_Municipio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  MODIFY `Id_Notificacion` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `recoleccion`
--
ALTER TABLE `recoleccion`
  MODIFY `Id_Recoleccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ruta`
--
ALTER TABLE `ruta`
  MODIFY `Id_Ruta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `admi_municipal`
--
ALTER TABLE `admi_municipal`
  ADD CONSTRAINT `1` FOREIGN KEY (`Id`) REFERENCES `empleado` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `2` FOREIGN KEY (`Id_Municipio`) REFERENCES `municipio` (`Id_Municipio`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `asigna`
--
ALTER TABLE `asigna`
  ADD CONSTRAINT `fk_asigna_admi` FOREIGN KEY (`Id`) REFERENCES `admi_municipal` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asigna_ruta` FOREIGN KEY (`Id_Ruta`) REFERENCES `ruta` (`Id_Ruta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `camion`
--
ALTER TABLE `camion`
  ADD CONSTRAINT `fk_camion_ruta` FOREIGN KEY (`Id_Ruta`) REFERENCES `ruta` (`Id_Ruta`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `chofer`
--
ALTER TABLE `chofer`
  ADD CONSTRAINT `1` FOREIGN KEY (`Id`) REFERENCES `empleado` (`Id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `incidencia`
--
ALTER TABLE `incidencia`
  ADD CONSTRAINT `fk_incidencia_numero_contenedor` FOREIGN KEY (`Numero_Contenedor`) REFERENCES `contenedor` (`Numero_Contenedor`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `limpiador`
--
ALTER TABLE `limpiador`
  ADD CONSTRAINT `1` FOREIGN KEY (`Id`) REFERENCES `empleado` (`Id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD CONSTRAINT `fk_notificacion_empleado` FOREIGN KEY (`Id_Empleado`) REFERENCES `empleado` (`Id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificacion_lectura`
--
ALTER TABLE `notificacion_lectura`
  ADD CONSTRAINT `fk_notificacion_lectura_empleado` FOREIGN KEY (`Id_Empleado`) REFERENCES `empleado` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
