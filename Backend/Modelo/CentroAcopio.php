<?php

class CentroAcopio
{
    public function __construct(
        private int $idCentro,
        private string $nombre,
        private string $ubicacion,
        private float $capacidad,
        private float $cantidadAlmacenada = 0,
        private string $tipo = 'centro_acopio',
        private string $estado = 'activo',
        private ?string $tiposResiduo = null,
        private ?int $idMunicipio = null
    ) {
    }

    public function registrar(PDO $pdo): bool
    {
        $stmt = $pdo->prepare('INSERT INTO centro_acopio (Nombre, Tipo, Ubicacion, Capacidad, Cantidad_Almacenada, Estado, Tipos_Residuo, Id_Municipio) VALUES (:nombre, :tipo, :ubicacion, :capacidad, :cantidad, :estado, :tipos, :municipio)');
        return $stmt->execute([
            ':nombre' => $this->nombre,
            ':tipo' => $this->tipo,
            ':estado' => $this->estado,
            ':tipos' => $this->tiposResiduo,
            ':municipio' => $this->idMunicipio,
            ':ubicacion' => $this->ubicacion,
            ':capacidad' => $this->capacidad,
            ':cantidad' => $this->cantidadAlmacenada
        ]);
    }

    public static function listar(PDO $pdo): array
    {
        return $pdo->query('SELECT Id_Centro, Nombre, Tipo, Ubicacion, Capacidad, Cantidad_Almacenada, Estado, Tipos_Residuo, Id_Municipio FROM centro_acopio ORDER BY Nombre')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function actualizar(PDO $pdo, int $id, array $datos): bool
    {
        $stmt = $pdo->prepare('UPDATE centro_acopio SET Nombre = :nombre, Tipo = :tipo, Ubicacion = :ubicacion, Capacidad = :capacidad, Cantidad_Almacenada = :cantidad, Estado = :estado, Tipos_Residuo = :tipos, Id_Municipio = :municipio WHERE Id_Centro = :id');
        $stmt->execute([
            ':id' => $id,
            ':tipo' => $datos['tipo'],
            ':estado' => $datos['estado'],
            ':tipos' => $datos['tipos_residuo'],
            ':municipio' => $datos['id_municipio'],
            ':nombre' => $datos['nombre'],
            ':ubicacion' => $datos['ubicacion'],
            ':capacidad' => $datos['capacidad'],
            ':cantidad' => $datos['cantidad_almacenada']
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function eliminar(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare('DELETE FROM centro_acopio WHERE Id_Centro = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
