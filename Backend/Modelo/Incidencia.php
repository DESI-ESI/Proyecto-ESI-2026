<?php

class incidencia
{
    private string $descripcion;
    private string $fecha;
    private string $tipo;
    private string $estado;
    private ?int $numeroContenedor;

    public function __construct(string $descripcion, string $fecha, string $tipo, string $estado, ?int $numeroContenedor = null)
    {
        $this->descripcion = $descripcion;
        $this->fecha = $fecha;
        $this->tipo = $tipo;
        $this->estado = $estado;
        $this->numeroContenedor = $numeroContenedor;
    }

    public static function listar(PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT Id_Incidencia, Tipo, Descripcion, Fecha, Estado, Numero_Contenedor
             FROM incidencia
             ORDER BY Fecha DESC, Id_Incidencia DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(PDO $pdo): bool
    {
        $stmt = $pdo->prepare(
            'INSERT INTO incidencia (Tipo, Descripcion, Fecha, Estado, Numero_Contenedor)
             VALUES (:tipo, :descripcion, :fecha, :estado, :numero)'
        );

        return $stmt->execute([
            ':tipo' => $this->tipo,
            ':descripcion' => $this->descripcion,
            ':fecha' => $this->fecha,
            ':estado' => $this->estado,
            ':numero' => $this->numeroContenedor
        ]);
    }

    public function actualizar(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare(
            'UPDATE incidencia
             SET Tipo = :tipo, Descripcion = :descripcion, Fecha = :fecha, Estado = :estado, Numero_Contenedor = :numero
             WHERE Id_Incidencia = :id'
        );
        $stmt->execute([
            ':tipo' => $this->tipo,
            ':descripcion' => $this->descripcion,
            ':fecha' => $this->fecha,
            ':estado' => $this->estado,
            ':numero' => $this->numeroContenedor,
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function eliminar(PDO $pdo, int $id): bool
    {
        $referencias = $pdo->prepare('SELECT COUNT(*) FROM contenedor WHERE Id_Incidencia = :id');
        $referencias->execute([':id' => $id]);
        if ((int) $referencias->fetchColumn() > 0) {
            throw new InvalidArgumentException('No se puede eliminar la incidencia porque está asociada a contenedores.');
        }

        $stmt = $pdo->prepare('DELETE FROM incidencia WHERE Id_Incidencia = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
?>