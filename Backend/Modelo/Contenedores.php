<?php

class Contenedores
{
    private int $idContenedor;
    private string $tipoResiduo;
    private string $ubicacion;
    private float $capacidad;
    private string $estado;
    private bool $enReserva;

    public function __construct(
        int $idContenedor,
        string $tipoResiduo,
        string $ubicacion,
        float $capacidad,
        string $estado,
        bool $enReserva = false
    ) {
        $this->idContenedor = $idContenedor;
        $this->tipoResiduo = $tipoResiduo;
        $this->ubicacion = $ubicacion;
        $this->capacidad = $capacidad;
        $this->estado = $estado;
        $this->enReserva = $enReserva;
    }
    
    public function getIdContenedor(): int
    {
        return $this->idContenedor;
    }

    public function setIdContenedor(int $idContenedor): void
    {
        $this->idContenedor = $idContenedor;
    }

    public function getTipoResiduo(): string
    {
        return $this->tipoResiduo;
    }

    public function setTipoResiduo(string $tipoResiduo): void
    {
        $this->tipoResiduo = $tipoResiduo;
    }

    public function getUbicacion(): string
    {
        return $this->ubicacion;
    }

    public function setUbicacion(string $ubicacion): void
    {
        $this->ubicacion = $ubicacion;
    }

    public function getCapacidad(): float
    {
        return $this->capacidad;
    }

    public function setCapacidad(float $capacidad): void
    {
        $this->capacidad = $capacidad;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }

    public function getEnReserva(): bool
    {
        return $this->enReserva;
    }

    public function setEnReserva(bool $enReserva): void
    {
        $this->enReserva = $enReserva;
    }


   

    public function registrarContenedor(
        PDO $pdo,
        int $idMunicipio,
        ?int $numero = null,
        ?float $latitud = null,
        ?float $longitud = null
    ): bool
    {
        $sql = "INSERT INTO contenedor
            (Numero_Contenedor, Tipo_Residuo, Capacidad, Ubicacion, Estado, Id_Municipio, Latitud, Longitud)
            VALUES
            (:numero, :tipo, :capacidad, :ubicacion, :estado, :municipio, :latitud, :longitud)";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':numero' => $numero,
            ':tipo' => $this->tipoResiduo,
            ':capacidad' => $this->capacidad,
            ':ubicacion' => $this->ubicacion,
            ':estado' => $this->estado,
            ':municipio' => $idMunicipio,
            ':latitud' => $latitud,
            ':longitud' => $longitud
        ]);
    }


    

    public static function darBajaContenedor(PDO $pdo, int $idContenedor, string $motivo): bool
    {
        $stmt = $pdo->prepare(
            "UPDATE contenedor
             SET Estado = 'inactivo', Motivo_Baja = :motivo
             WHERE Id_Contenedor = :id AND (Estado IS NULL OR Estado <> 'inactivo')"
        );

        $stmt->execute([':motivo' => $motivo, ':id' => $idContenedor]);
        return $stmt->rowCount() > 0;
    }


    

    public static function modificarUbicacion(
        PDO $pdo,
        int $idContenedor,
        string $ubicacion
    ): void
    {
        $stmt = $pdo->prepare(
            "UPDATE contenedor
             SET Ubicacion = :ubicacion
             WHERE Id_Contenedor = :id"
        );

        $stmt->execute([
            ':ubicacion' => $ubicacion,
            ':id' => $idContenedor
        ]);
    }


   

    public static function modificarEstado(
        PDO $pdo,
        int $idContenedor,
        string $estado
    ): void
    {
        $stmt = $pdo->prepare(
            "UPDATE contenedor
             SET Estado = :estado
             WHERE Id_Contenedor = :id"
        );

        $stmt->execute([
            ':estado' => $estado,
            ':id' => $idContenedor
        ]);
    }


    

    public static function modificarTipoResiduo(
        PDO $pdo,
        int $idContenedor,
        string $tipoResiduo
    ): void
    {
        $stmt = $pdo->prepare(
            "UPDATE contenedor
             SET Tipo_Residuo = :tipo
             WHERE Id_Contenedor = :id"
        );

        $stmt->execute([
            ':tipo' => $tipoResiduo,
            ':id' => $idContenedor
        ]);
    }


    

    public static function consultarCantidadRepuestos(PDO $pdo): int
    {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM contenedor
             WHERE Estado = 'reserva'"
        );

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }




    public static function registrarContenedorRepuesto(
        PDO $pdo,
        int $idContenedor
    ): void
    {
        $stmt = $pdo->prepare(
            "UPDATE contenedor
             SET Estado = 'reserva'
             WHERE Id_Contenedor = :id"
        );

        $stmt->execute([
            ':id' => $idContenedor
        ]);
    }



    public static function darBajaContenedorRepuesto(
        PDO $pdo,
        int $idContenedor
    ): void
    {
        $stmt = $pdo->prepare(
            "UPDATE contenedor
             SET Estado = 'activo'
             WHERE Id_Contenedor = :id"
        );

        $stmt->execute([
            ':id' => $idContenedor
        ]);
    }



    public static function modificarContenedorRepuesto(
        PDO $pdo,
        int $idContenedor,
        float $capacidad,
        string $tipoResiduo,
        string $estado
    ): void
    {
        $stmt = $pdo->prepare(
            "UPDATE contenedor
             SET Capacidad = :capacidad,
                 Tipo_Residuo = :tipo,
                 Estado = :estado
             WHERE Id_Contenedor = :id
             AND Estado = 'reserva'"
        );

        $stmt->execute([
            ':capacidad' => $capacidad,
            ':tipo' => $tipoResiduo,
            ':estado' => $estado,
            ':id' => $idContenedor
        ]);
    }


   

    public static function listar(PDO $pdo): array
    {
        $stmt = $pdo->query(
            "SELECT Id_Contenedor,
                    Numero_Contenedor,
                    Tipo_Residuo,
                    Capacidad,
                    Ubicacion,
                    Estado,
                    Motivo_Baja,
                    Id_Municipio,
                    Id_Incidencia,
                    Latitud,
                    Longitud
             FROM contenedor"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    public static function actualizar(
        PDO $pdo,
        int $idContenedor,
        array $datos
    ): bool
    {
        $sql = "UPDATE contenedor
                SET Numero_Contenedor = :numero,
                    Tipo_Residuo = :tipo,
                    Capacidad = :capacidad,
                    Ubicacion = :ubicacion,
                    Estado = :estado,
                    Id_Municipio = :municipio,
                    Id_Incidencia = :incidencia,
                    Latitud = :latitud,
                    Longitud = :longitud
                WHERE Id_Contenedor = :id";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':numero' => $datos['numero_contenedor'],
            ':tipo' => $datos['tipo_residuo'],
            ':capacidad' => $datos['capacidad'],
            ':ubicacion' => $datos['ubicacion'],
            ':estado' => $datos['estado'],
            ':municipio' => $datos['id_municipio'],
            ':incidencia' => $datos['id_incidencia'] ?? null,
            ':latitud' => $datos['latitud'] ?? null,
            ':longitud' => $datos['longitud'] ?? null,
            ':id' => $idContenedor
        ]);
    }
}

?>