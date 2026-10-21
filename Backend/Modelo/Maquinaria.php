<?php

class Maquinaria
{
    public function __construct(
        private int $idMaquinaria,
        private int $idCentro,
        private string $nombre = '',
        private ?string $tipo = null,
        private string $estado = 'operativa'
    ) {
    }

    public function registrar(PDO $pdo): bool
    {
        $stmt = $pdo->prepare('INSERT INTO maquinaria (Id_Centro, Nombre, Tipo, Estado) VALUES (:id_centro, :nombre, :tipo, :estado)');
        return $stmt->execute([
            ':id_centro' => $this->idCentro,
            ':nombre' => $this->nombre,
            ':tipo' => $this->tipo,
            ':estado' => $this->estado
        ]);
    }

    public static function listar(PDO $pdo): array
    {
        return $pdo->query('SELECT m.Id_Maquinaria, m.Id_Centro, m.Nombre, m.Tipo, m.Estado, c.Nombre AS Centro, c.Tipo AS Tipo_Instalacion FROM maquinaria m JOIN centro_acopio c ON c.Id_Centro = m.Id_Centro ORDER BY m.Id_Maquinaria')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function actualizar(PDO $pdo, int $id, array $datos): bool
    {
        $stmt = $pdo->prepare('UPDATE maquinaria SET Id_Centro = :id_centro, Nombre = :nombre, Tipo = :tipo, Estado = :estado WHERE Id_Maquinaria = :id');
        $stmt->execute([
            ':id' => $id,
            ':id_centro' => $datos['id_centro'],
            ':nombre' => $datos['nombre'],
            ':tipo' => $datos['tipo'],
            ':estado' => $datos['estado']
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function eliminar(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare('DELETE FROM maquinaria WHERE Id_Maquinaria = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
