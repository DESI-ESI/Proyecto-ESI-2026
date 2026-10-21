ALTER TABLE empleado
    DROP CONSTRAINT chk_empleado_rol;

ALTER TABLE empleado
    ADD CONSTRAINT chk_empleado_rol
    CHECK (rol IN ('admin', 'admi_municipal', 'empleado', 'operario', 'chofer', 'limpiador') OR rol IS NULL);
