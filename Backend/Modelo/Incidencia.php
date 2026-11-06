<?php

class incidencia
{
    private string $descripcion;
    private string $fecha;
    private string $tipo;
    private string $estado;
    private ?string $zona;
    private ?int $numeroContenedor;
    private ?int $idCuadrilla;
    private ?int $idCreador;
    private ?float $latitud;
    private ?float $longitud;

    public function __construct(
        string $descripcion,
        string $fecha,
        string $tipo,
        string $estado,
        ?int $numeroContenedor = null,
        ?int $idCuadrilla = null,
        ?string $zona = null,
        ?float $latitud = null,
        ?float $longitud = null,
        ?int $idCreador = null
    )
    {
        $this->descripcion = $descripcion;
        $this->fecha = $fecha;
        $this->tipo = $tipo;
        $this->estado = $estado;
        $this->zona = $zona;
        $this->numeroContenedor = $numeroContenedor;
        $this->idCuadrilla = $idCuadrilla;
        $this->idCreador = $idCreador;
        $this->latitud = $latitud;
        $this->longitud = $longitud;
    }

    public static function listar(PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT Id_Incidencia, Tipo, Descripcion, Fecha, Estado, Zona, Fecha_Resolucion, Latitud, Longitud, Numero_Contenedor, Id_Cuadrilla, Id_Creador
             FROM incidencia
             ORDER BY Fecha DESC, Id_Incidencia DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(PDO $pdo): bool
    {
        $stmt = $pdo->prepare(
            'INSERT INTO incidencia (Tipo, Descripcion, Fecha, Estado, Zona, Fecha_Resolucion, Latitud, Longitud, Numero_Contenedor, Id_Cuadrilla, Id_Creador)
             VALUES (:tipo, :descripcion, :fecha, :estado, :zona, :fecha_resolucion, :latitud, :longitud, :numero, :cuadrilla, :creador)'
        );

        return $stmt->execute([
            ':tipo' => $this->tipo,
            ':descripcion' => $this->descripcion,
            ':fecha' => $this->fecha,
            ':estado' => $this->estado,
            ':zona' => $this->zona,
            ':fecha_resolucion' => $this->estado === 'resuelta' ? date('Y-m-d H:i:s') : null,
            ':latitud' => $this->latitud,
            ':longitud' => $this->longitud,
            ':numero' => $this->numeroContenedor,
            ':cuadrilla' => $this->idCuadrilla,
            ':creador' => $this->idCreador
        ]);
    }

    public function actualizar(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare(
            "UPDATE incidencia
             SET Tipo = :tipo, Descripcion = :descripcion, Fecha = :fecha,
                 Fecha_Resolucion = CASE WHEN :estado_resolucion = 'resuelta' THEN COALESCE(Fecha_Resolucion, NOW()) ELSE NULL END,
                 Estado = :estado, Zona = :zona, Latitud = :latitud, Longitud = :longitud,
                 Numero_Contenedor = :numero, Id_Cuadrilla = :cuadrilla
             WHERE Id_Incidencia = :id"
        );
        $stmt->execute([
            ':tipo' => $this->tipo,
            ':descripcion' => $this->descripcion,
            ':fecha' => $this->fecha,
            ':estado' => $this->estado,
            ':zona' => $this->zona,
            ':estado_resolucion' => $this->estado,
            ':latitud' => $this->latitud,
            ':longitud' => $this->longitud,
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


    public static function asignar(PDO $pdo, int $id, int $idCuadrilla): bool
    {
        $stmt = $pdo->prepare(
            "UPDATE incidencia
             SET Estado = 'en curso', Id_Cuadrilla = :cuadrilla
             WHERE Id_Incidencia = :id
               AND Estado = 'abierta'
               AND Id_Cuadrilla IS NULL"
        );
        $stmt->execute([
            ':cuadrilla' => $idCuadrilla,
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }
}
?>