SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'recoleccion' AND column_name = 'Tipo_Residuo') = 0,
    'ALTER TABLE recoleccion ADD COLUMN Tipo_Residuo VARCHAR(50) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'incidencia' AND column_name = 'Zona') = 0,
    'ALTER TABLE incidencia ADD COLUMN Zona VARCHAR(120) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'camion' AND column_name = 'Fecha_Baja') = 0,
    'ALTER TABLE camion ADD COLUMN Fecha_Baja DATETIME NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'camion' AND column_name = 'Motivo_Baja') = 0,
    'ALTER TABLE camion ADD COLUMN Motivo_Baja VARCHAR(500) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'maquinaria' AND column_name = 'Descripcion') = 0,
    'ALTER TABLE maquinaria ADD COLUMN Descripcion TEXT NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'maquinaria' AND column_name = 'Fecha_Baja') = 0,
    'ALTER TABLE maquinaria ADD COLUMN Fecha_Baja DATETIME NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'maquinaria' AND column_name = 'Motivo_Baja') = 0,
    'ALTER TABLE maquinaria ADD COLUMN Motivo_Baja VARCHAR(500) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
