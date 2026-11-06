UPDATE ruta r
JOIN centro_acopio c
  ON TRIM(c.Nombre) = TRIM(r.Recorrido)
 AND c.Id_Municipio IS NOT NULL
SET r.Id_Municipio = c.Id_Municipio
WHERE c.Estado <> 'inactivo';
