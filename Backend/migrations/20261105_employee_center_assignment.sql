ALTER TABLE empleado
  ADD COLUMN Id_Centro int(11) DEFAULT NULL AFTER cedula,
  ADD KEY idx_empleado_centro (Id_Centro),
  ADD CONSTRAINT fk_empleado_centro FOREIGN KEY (Id_Centro)
    REFERENCES centro_acopio(Id_Centro) ON DELETE SET NULL ON UPDATE CASCADE;
