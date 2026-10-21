ALTER TABLE camion
    ADD COLUMN Id_Ruta INT NULL AFTER Id_Municipio,
    ADD INDEX idx_camion_id_ruta (Id_Ruta),
    ADD CONSTRAINT fk_camion_ruta
        FOREIGN KEY (Id_Ruta) REFERENCES ruta (Id_Ruta)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
