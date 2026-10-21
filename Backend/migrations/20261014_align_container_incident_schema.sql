ALTER TABLE contenedor
    ADD COLUMN Numero_Contenedor INT NULL AFTER Id_Contenedor;

UPDATE contenedor
SET Numero_Contenedor = Id_Contenedor
WHERE Numero_Contenedor IS NULL;

ALTER TABLE contenedor
    MODIFY Numero_Contenedor INT NOT NULL,
    ADD UNIQUE KEY uq_contenedor_numero (Numero_Contenedor);

ALTER TABLE incidencia
    ADD COLUMN Numero_Contenedor INT NULL AFTER Estado,
    ADD KEY idx_incidencia_numero_contenedor (Numero_Contenedor),
    ADD CONSTRAINT fk_incidencia_numero_contenedor
        FOREIGN KEY (Numero_Contenedor)
        REFERENCES contenedor (Numero_Contenedor)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
