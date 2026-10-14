<?php

require_once __DIR__ . '/../config/Conexion.php';

class MunicipioController
{
    public function listar(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT Id_Municipio, Nombre, Departamento, Cant_Poblacion, Estado FROM municipio ORDER BY Nombre');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
