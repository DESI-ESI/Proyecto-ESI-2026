SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'contenedor' AND column_name = 'Latitud') = 0,
    'ALTER TABLE contenedor ADD COLUMN Latitud DECIMAL(10, 7) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'centro_acopio' AND column_name = 'Tipo') = 0,
    'ALTER TABLE centro_acopio ADD COLUMN Tipo VARCHAR(30) NOT NULL DEFAULT ''centro_acopio''',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'centro_acopio' AND column_name = 'Estado') = 0,
    'ALTER TABLE centro_acopio ADD COLUMN Estado VARCHAR(30) NOT NULL DEFAULT ''activo''',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'centro_acopio' AND column_name = 'Tipos_Residuo') = 0,
    'ALTER TABLE centro_acopio ADD COLUMN Tipos_Residuo VARCHAR(255) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'centro_acopio' AND column_name = 'Id_Municipio') = 0,
    'ALTER TABLE centro_acopio ADD COLUMN Id_Municipio INT(11) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = DATABASE() AND table_name = 'centro_acopio' AND index_name = 'idx_centro_acopio_municipio') = 0,
    'ALTER TABLE centro_acopio ADD KEY idx_centro_acopio_municipio (Id_Municipio)',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.referential_constraints
     WHERE constraint_schema = DATABASE() AND table_name = 'centro_acopio' AND constraint_name = 'fk_centro_acopio_municipio') = 0,
    'ALTER TABLE centro_acopio ADD CONSTRAINT fk_centro_acopio_municipio FOREIGN KEY (Id_Municipio) REFERENCES municipio (Id_Municipio) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'maquinaria' AND column_name = 'Nombre') = 0,
    'ALTER TABLE maquinaria ADD COLUMN Nombre VARCHAR(60) NOT NULL DEFAULT ''Maquinaria''',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'maquinaria' AND column_name = 'Tipo') = 0,
    'ALTER TABLE maquinaria ADD COLUMN Tipo VARCHAR(50) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'maquinaria' AND column_name = 'Estado') = 0,
    'ALTER TABLE maquinaria ADD COLUMN Estado VARCHAR(30) NOT NULL DEFAULT ''operativa''',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.referential_constraints
     WHERE constraint_schema = DATABASE() AND table_name = 'maquinaria' AND constraint_name = 'fk_maquinaria_centro') = 0,
    'ALTER TABLE maquinaria ADD CONSTRAINT fk_maquinaria_centro FOREIGN KEY (Id_Centro) REFERENCES centro_acopio (Id_Centro) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

CREATE TABLE IF NOT EXISTS ingreso_acopio (
    Id_Ingreso INT(11) NOT NULL AUTO_INCREMENT,
    Id_Centro INT(11) NOT NULL,
    Id_Empleado INT(11) DEFAULT NULL,
    Cantidad DECIMAL(10, 2) NOT NULL,
    Tipo_Residuo VARCHAR(50) DEFAULT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Id_Ingreso),
    KEY idx_ingreso_acopio_centro (Id_Centro),
    KEY idx_ingreso_acopio_empleado (Id_Empleado),
    CONSTRAINT fk_ingreso_acopio_centro FOREIGN KEY (Id_Centro)
        REFERENCES centro_acopio (Id_Centro) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ingreso_acopio_empleado FOREIGN KEY (Id_Empleado)
        REFERENCES empleado (Id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'contenedor' AND column_name = 'Longitud') = 0,
    'ALTER TABLE contenedor ADD COLUMN Longitud DECIMAL(10, 7) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'incidencia' AND column_name = 'Id_Cuadrilla') = 0,
    'ALTER TABLE incidencia ADD COLUMN Id_Cuadrilla INT(11) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = DATABASE() AND table_name = 'incidencia' AND index_name = 'idx_incidencia_cuadrilla') = 0,
    'ALTER TABLE incidencia ADD KEY idx_incidencia_cuadrilla (Id_Cuadrilla)',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.referential_constraints
     WHERE constraint_schema = DATABASE() AND table_name = 'incidencia' AND constraint_name = 'fk_incidencia_cuadrilla') = 0,
    'ALTER TABLE incidencia ADD CONSTRAINT fk_incidencia_cuadrilla FOREIGN KEY (Id_Cuadrilla) REFERENCES cuadrilla (Id_Cuadrilla) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
