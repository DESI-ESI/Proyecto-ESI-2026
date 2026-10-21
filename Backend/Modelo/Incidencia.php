<?php

class incidencia
{
    private string $descripcion;
    private string $fecha;
    private string $tipo;
    private string $estado;
    private ?int $numeroContenedor;
    private ?int $idCuadrilla;

    public function __construct(string $descripcion, string $fecha, string $tipo, string $estado, ?int $numeroContenedor = null, ?int $idCuadrilla = null)
    {
        $this->descripcion = $descripcion;
        $this->fecha = $fecha;
        $this->tipo = $tipo;
        $this->estado = $estado;
        $this->numeroContenedor = $numeroContenedor;
        $this->idCuadrilla = $idCuadrilla;
    }

    public static function listar(PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT Id_Incidencia, Tipo, Descripcion, Fecha, Estado, Numero_Contenedor, Id_Cuadrilla
             FROM incidencia
             ORDER BY Fecha DESC, Id_Incidencia DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(PDO $pdo): bool
    {
        $stmt = $pdo->prepare(
            'INSERT INTO incidencia (Tipo, Descripcion, Fecha, Estado, Numero_Contenedor, Id_Cuadrilla)
             VALUES (:tipo, :descripcion, :fecha, :estado, :numero, :cuadrilla)'
        );

        return $stmt->execute([
            ':tipo' => $this->tipo,
            ':descripcion' => $this->descripcion,
            ':fecha' => $this->fecha,
            ':estado' => $this->estado,
            ':numero' => $this->numeroContenedor,
            ':cuadrilla' => $this->idCuadrilla
        ]);
    }

    public function actualizar(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare(
            'UPDATE incidencia
             SET Tipo = :tipo, Descripcion = :descripcion, Fecha = :fecha, Estado = :estado, Numero_Contenedor = :numero, Id_Cuadrilla = :cuadrilla
             WHERE Id_Incidencia = :id'
        );
        $stmt->execute([
            ':tipo' => $this->tipo,
            ':descripcion' => $this->descripcion,
            ':fecha' => $this->fecha,
            ':estado' => $this->estado,
            ':numero' => $this->numeroContenedor,
            ':cuadrilla' => $this->idCuadrilla,
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

    public static function aceptar(PDO $pdo, int $id, int $idCuadrilla, int $idRuta): bool
    {
        $stmt = $pdo->prepare(
            "UPDATE incidencia AS i
             SET Estado = 'en curso', Id_Cuadrilla = :cuadrilla
             WHERE i.Id_Incidencia = :id
               AND i.Estado = 'abierta'
               AND (i.Id_Cuadrilla IS NULL OR i.Id_Cuadrilla = :cuadrilla_actual)
               AND EXISTS (
                   SELECT 1
                   FROM existe x
                   JOIN contenedor c ON c.Id_Contenedor = x.Id_Contenedor
                   WHERE x.Id_Ruta = :ruta
                     AND c.Numero_Contenedor = i.Numero_Contenedor
               )"
        );
        $stmt->execute([
            ':cuadrilla' => $idCuadrilla,
            ':id' => $id,
            ':cuadrilla_actual' => $idCuadrilla,
            ':ruta' => $idRuta
        ]);

        return $stmt->rowCount() > 0;
    }
}
?>