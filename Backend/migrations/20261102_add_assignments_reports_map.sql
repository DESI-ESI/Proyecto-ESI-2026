SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'incidencia' AND column_name = 'Fecha_Resolucion') = 0,
    'ALTER TABLE incidencia ADD COLUMN Fecha_Resolucion DATETIME NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'incidencia' AND column_name = 'Latitud') = 0,
    'ALTER TABLE incidencia ADD COLUMN Latitud DECIMAL(10, 7) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'incidencia' AND column_name = 'Longitud') = 0,
    'ALTER TABLE incidencia ADD COLUMN Longitud DECIMAL(10, 7) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

CREATE TABLE IF NOT EXISTS asignacion_camion (
    Id_Asignacion INT(11) NOT NULL AUTO_INCREMENT,
    Id_Camion INT(11) NOT NULL,
    Id_Ruta INT(11) NOT NULL,
    Fecha DATE NOT NULL,
    Hora_Inicio TIME NOT NULL,
    Hora_Fin TIME NOT NULL,
    Estado VARCHAR(20) NOT NULL DEFAULT 'asignada',
    PRIMARY KEY (Id_Asignacion),
    KEY idx_asignacion_camion_horario (Id_Camion, Fecha, Hora_Inicio, Hora_Fin),
    KEY idx_asignacion_ruta_fecha (Id_Ruta, Fecha),
    CONSTRAINT fk_asignacion_camion_camion FOREIGN KEY (Id_Camion)
        REFERENCES camion (Id_Camion) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_asignacion_camion_ruta FOREIGN KEY (Id_Ruta)
        REFERENCES ruta (Id_Ruta) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
