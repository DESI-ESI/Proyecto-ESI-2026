SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'empleado' AND column_name = 'Debe_Cambiar_Contrasena') = 0,
    'ALTER TABLE empleado ADD COLUMN Debe_Cambiar_Contrasena TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
