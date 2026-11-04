SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'incidencia' AND column_name = 'Id_Creador') = 0,
    'ALTER TABLE incidencia ADD COLUMN Id_Creador INT(11) NULL AFTER Id_Cuadrilla',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = DATABASE() AND table_name = 'incidencia' AND index_name = 'idx_incidencia_creador') = 0,
    'ALTER TABLE incidencia ADD KEY idx_incidencia_creador (Id_Creador)',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.referential_constraints
     WHERE constraint_schema = DATABASE() AND table_name = 'incidencia' AND constraint_name = 'fk_incidencia_creador') = 0,
    'ALTER TABLE incidencia ADD CONSTRAINT fk_incidencia_creador FOREIGN KEY (Id_Creador) REFERENCES empleado (Id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
