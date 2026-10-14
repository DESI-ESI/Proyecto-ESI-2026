<?php

class Camiones {
    private string $matricula;
    private float $capacidad;
    private string $estado;
    

    public function __construct(string $matricula, float $capacidad, string $estado) {
        $this->matricula = $matricula;
        $this->capacidad = $capacidad;
        $this->estado = $estado;
    }

    public function getMatricula(): string {
        return $this->matricula;
    }

    public function getCapacidad(): float {
        return $this->capacidad;
    }

    public function getEstado(): string {
        return $this->estado;
    }

    public function setMatricula(string $matricula): void {
        $this->matricula = $matricula;
    }

    public function setCapacidad(float $capacidad): void {
        $this->capacidad = $capacidad;
    }

    public function setEstado(string $estado): void {
        $this->estado = $estado;
    }

    public function crearCamion(PDO $pdo, int $idMunicipio, ?int $idRuta = null): bool {
        $sql = "INSERT INTO camion (matricula, capacidad, estado, Id_Municipio, Id_Ruta)
                VALUES (:matricula, :capacidad, :estado, :id_municipio, :id_ruta)";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':matricula' => $this->matricula,
            ':capacidad' => $this->capacidad,
            ':estado' => $this->estado,
            ':id_municipio' => $idMunicipio,
            ':id_ruta' => $idRuta
        ]);
    }

    public static function existeRuta(PDO $pdo, int $idRuta): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM ruta WHERE Id_Ruta = :id');
        $stmt->execute([':id' => $idRuta]);
        return (bool) $stmt->fetchColumn();
    }

    public static function listar(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT c.Id_Camion, c.matricula, c.capacidad, c.estado, c.Id_Municipio, c.Id_Ruta, r.Recorrido AS ruta_recorrido
                             FROM camion c LEFT JOIN ruta r ON r.Id_Ruta = c.Id_Ruta');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function actualizar(PDO $pdo, string $matricula, array $datos): bool
    {
        $stmt = $pdo->prepare(
            'UPDATE camion
             SET capacidad = :capacidad, estado = :estado, Id_Municipio = :id_municipio, Id_Ruta = :id_ruta
             WHERE matricula = :matricula'
        );

        $stmt->execute([
            ':capacidad'    => $datos['capacidad'],
            ':estado'       => $datos['estado'],
            ':id_municipio' => $datos['id_municipio'],
            ':id_ruta'      => $datos['id_ruta'],
            ':matricula'    => $matricula
        ]);

        if ($stmt->rowCount() > 0) {
            return true;
        }
        $check = $pdo->prepare('SELECT 1 FROM camion WHERE matricula = :matricula');
        $check->execute([':matricula' => $matricula]);
        return (bool) $check->fetchColumn();
    }

    public static function eliminar(PDO $pdo, string $matricula): bool
    {
        $stmt = $pdo->prepare('DELETE FROM camion WHERE matricula = :matricula');
        return $stmt->execute([':matricula' => $matricula]);
    }
}
?>