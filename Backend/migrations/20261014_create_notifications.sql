CREATE TABLE IF NOT EXISTS notificacion (
    Id_Notificacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    Panel_Origen ENUM('admin', 'empleado') NOT NULL,
    Recurso VARCHAR(50) NOT NULL,
    Accion VARCHAR(50) NOT NULL,
    Descripcion VARCHAR(255) NOT NULL,
    Id_Empleado INT(11) NULL,
    Nombre_Empleado VARCHAR(100) NULL,
    Fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Id_Notificacion),
    KEY idx_notificacion_panel_id (Panel_Origen, Id_Notificacion),
    KEY idx_notificacion_empleado (Id_Empleado),
    CONSTRAINT fk_notificacion_empleado
        FOREIGN KEY (Id_Empleado) REFERENCES empleado (Id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificacion_lectura (
    Id_Empleado INT(11) NOT NULL,
    Panel ENUM('admin', 'empleado') NOT NULL,
    Ultimo_Id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (Id_Empleado, Panel),
    CONSTRAINT fk_notificacion_lectura_empleado
        FOREIGN KEY (Id_Empleado) REFERENCES empleado (Id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
