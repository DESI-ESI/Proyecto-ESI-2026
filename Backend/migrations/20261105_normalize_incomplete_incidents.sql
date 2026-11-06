UPDATE incidencia
SET Tipo = 'incompleto'
WHERE Tipo IS NULL
   OR LOWER(TRIM(Tipo)) NOT IN ('incompleto');
