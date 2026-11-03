SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'empleado' AND column_name = 'Estado_Cuenta') = 0,
    'ALTER TABLE empleado ADD COLUMN Estado_Cuenta VARCHAR(10) NOT NULL DEFAULT ''activa''',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @migration_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'contenedor' AND column_name = 'Motivo_Baja') = 0,
    'ALTER TABLE contenedor ADD COLUMN Motivo_Baja VARCHAR(500) NULL',
    'SELECT 1'
);
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

CREATE TABLE IF NOT EXISTS telefono (
    Id INT(11) NOT NULL,
    Telefono VARCHAR(20) NOT NULL,
    PRIMARY KEY (Id, Telefono),
    CONSTRAINT fk_telefono_empleado FOREIGN KEY (Id)
        REFERENCES empleado (Id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
