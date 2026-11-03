<?php

class Maquinaria
{
    public function __construct(
        private int $idMaquinaria,
        private int $idCentro,
        private string $nombre = '',
        private ?string $tipo = null,
        private string $estado = 'operativa',
        private ?string $descripcion = null
    ) {
    }

    public function registrar(PDO $pdo): bool
    {
        $stmt = $pdo->prepare('INSERT INTO maquinaria (Id_Centro, Nombre, Tipo, Descripcion, Estado) VALUES (:id_centro, :nombre, :tipo, :descripcion, :estado)');
        return $stmt->execute([
            ':id_centro' => $this->idCentro,
            ':nombre' => $this->nombre,
            ':tipo' => $this->tipo,
            ':descripcion' => $this->descripcion,
            ':estado' => $this->estado
        ]);
    }

    public static function listar(PDO $pdo): array
    {
        return $pdo->query('SELECT m.Id_Maquinaria, m.Id_Centro, m.Nombre, m.Tipo, m.Descripcion, m.Estado, m.Fecha_Baja, m.Motivo_Baja, c.Nombre AS Centro, c.Tipo AS Tipo_Instalacion FROM maquinaria m JOIN centro_acopio c ON c.Id_Centro = m.Id_Centro ORDER BY m.Id_Maquinaria')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function actualizar(PDO $pdo, int $id, array $datos): bool
    {
        $stmt = $pdo->prepare('UPDATE maquinaria SET Id_Centro = :id_centro, Nombre = :nombre, Tipo = :tipo, Descripcion = :descripcion, Estado = :estado WHERE Id_Maquinaria = :id AND Fecha_Baja IS NULL');
        $stmt->execute([
            ':id' => $id,
            ':id_centro' => $datos['id_centro'],
            ':nombre' => $datos['nombre'],
            ':tipo' => $datos['tipo'],
            ':descripcion' => $datos['descripcion'],
            ':estado' => $datos['estado']
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function eliminar(PDO $pdo, int $id, string $motivo): bool
    {
        $stmt = $pdo->prepare(
            "UPDATE maquinaria
             SET Estado = 'fuera_de_servicio', Fecha_Baja = NOW(), Motivo_Baja = :motivo
             WHERE Id_Maquinaria = :id AND Fecha_Baja IS NULL"
        );
        $stmt->execute([':motivo' => $motivo, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
