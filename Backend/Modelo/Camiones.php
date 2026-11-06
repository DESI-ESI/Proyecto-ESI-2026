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

    public function crearCamion(PDO $pdo, int $idMunicipio, ?int $idRuta = null, ?int $idCentro = null): bool {
        $sql = "INSERT INTO camion (matricula, capacidad, estado, Id_Municipio, Id_Ruta, Id_Centro)
                VALUES (:matricula, :capacidad, :estado, :id_municipio, :id_ruta, :id_centro)";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':matricula' => $this->matricula,
            ':capacidad' => $this->capacidad,
            ':estado' => $this->estado,
            ':id_municipio' => $idMunicipio,
            ':id_ruta' => $idRuta,
            ':id_centro' => $idCentro
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
        $stmt = $pdo->query('SELECT c.Id_Camion, c.matricula, c.capacidad, c.estado, c.Fecha_Baja, c.Motivo_Baja, c.Id_Municipio, c.Id_Centro, c.Id_Ruta, r.Recorrido AS ruta_recorrido
                             FROM camion c LEFT JOIN ruta r ON r.Id_Ruta = c.Id_Ruta
                             WHERE c.Fecha_Baja IS NULL
                               AND LOWER(TRIM(c.Estado)) <> \'fuera de servicio\'');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function actualizar(PDO $pdo, string $matricula, array $datos): bool
    {
        $stmt = $pdo->prepare(
            'UPDATE camion
             SET capacidad = :capacidad, estado = :estado, Id_Municipio = :id_municipio, Id_Centro = :id_centro, Id_Ruta = :id_ruta
             WHERE matricula = :matricula AND Fecha_Baja IS NULL'
        );

        $stmt->execute([
            ':capacidad'    => $datos['capacidad'],
            ':estado'       => $datos['estado'],
            ':id_municipio' => $datos['id_municipio'],
            ':id_centro'    => $datos['id_centro'],
            ':id_ruta'      => $datos['id_ruta'],
            ':matricula'    => $matricula
        ]);

        if ($stmt->rowCount() > 0) {
            return true;
        }
        $check = $pdo->prepare('SELECT 1 FROM camion WHERE matricula = :matricula AND Fecha_Baja IS NULL');
        $check->execute([':matricula' => $matricula]);
        return (bool) $check->fetchColumn();
    }

    public static function darBaja(PDO $pdo, string $matricula, string $motivo): bool
    {
        try {
            $pdo->beginTransaction();

            $camion = $pdo->prepare(
                'SELECT Id_Camion FROM camion WHERE matricula = :matricula LIMIT 1 FOR UPDATE'
            );
            $camion->execute([':matricula' => $matricula]);
            $idCamion = $camion->fetchColumn();

            if (!$idCamion) {
                $pdo->rollBack();
                return false;
            }

            $relaciones = $pdo->prepare(
                'SELECT TABLE_NAME, COLUMN_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
                   AND REFERENCED_TABLE_NAME = :tabla
                   AND REFERENCED_COLUMN_NAME IN (\'Id_Camion\', \'matricula\')'
            );
            $relaciones->execute([':tabla' => 'camion']);

            $tablasEliminadas = [];
            foreach ($relaciones->fetchAll(PDO::FETCH_ASSOC) as $relacion) {
                $tabla = (string) $relacion['TABLE_NAME'];
                $columna = (string) $relacion['COLUMN_NAME'];
                if ($tabla === '' || $columna === '' || isset($tablasEliminadas[$tabla])) {
                    continue;
                }

                $tablaSegura = '`' . str_replace('`', '``', $tabla) . '`';
                $columnaSegura = '`' . str_replace('`', '``', $columna) . '`';
                if ($columna === 'Id_Camion') {
                    $stmtRelacion = $pdo->prepare(
                        "DELETE FROM {$tablaSegura} WHERE {$columnaSegura} = :id_camion"
                    );
                    $stmtRelacion->execute([':id_camion' => (int) $idCamion]);
                }
                $tablasEliminadas[$tabla] = true;
            }

            $stmtRelacionConRuta = $pdo->prepare(
                'DELETE FROM asignacion_camion WHERE Id_Camion = :id_camion'
            );
            $stmtRelacionConRuta->execute([':id_camion' => (int) $idCamion]);

            $stmtRelacionUtiliza = $pdo->prepare(
                'DELETE FROM utiliza WHERE Id_Camion = :id_camion'
            );
            $stmtRelacionUtiliza->execute([':id_camion' => (int) $idCamion]);

            $stmtMantenimiento = $pdo->prepare(
                'DELETE FROM mantenimiento WHERE Id_Camion = :id_camion'
            );
            $stmtMantenimiento->execute([':id_camion' => (int) $idCamion]);

            $eliminar = $pdo->prepare('DELETE FROM camion WHERE Id_Camion = :id_camion');
            $eliminar->execute([':id_camion' => (int) $idCamion]);
            $eliminado = $eliminar->rowCount() > 0;

            if (!$eliminado) {
                $pdo->rollBack();
                return false;
            }

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
?>