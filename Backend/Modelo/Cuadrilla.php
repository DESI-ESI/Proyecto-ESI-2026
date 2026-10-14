<?php

class cuadrilla {
    private int $idCuadrilla;
    private string $turno;
    private int $NumIntegrantes;
    private array $cedulas = [];
    private ?int $idRuta = null;
    

    public function __construct(int $idCuadrilla, string $turno, int $NumIntegrantes) {
        $this->idCuadrilla = $idCuadrilla;
        $this->turno = $turno;
        $this->NumIntegrantes = $NumIntegrantes;
    }

    public function getIdCuadrilla(): int {
        return $this->idCuadrilla;
    }

    public function setIdCuadrilla(int $idCuadrilla): void {
        $this->idCuadrilla = $idCuadrilla;
    }

    public function getTurno(): string {
        return $this->turno;
    }

    public function setTurno(string $turno): void {
        $this->turno = $turno;
    }

    public function getNumIntegrantes(): int {
        return $this->NumIntegrantes;
    }

    public function setNumIntegrantes(int $NumIntegrantes): void {
        $this->NumIntegrantes = $NumIntegrantes;
    }

    public function setIdRuta(?int $idRuta): void {
        $this->idRuta = $idRuta;
    }

    public function setCedulas(array $cedulas): void {
        $this->cedulas = $cedulas;
    }

    public static function listar(PDO $pdo): array {
        $stmt = $pdo->query(
            "SELECT c.Id_Cuadrilla, c.Turno, c.Num_integrantes,
                    COALESCE(GROUP_CONCAT(e.cedula ORDER BY e.cedula SEPARATOR ', '), '') AS Cedulas,
                    (SELECT r.Id_Ruta FROM realiza r WHERE r.Id_Cuadrilla = c.Id_Cuadrilla LIMIT 1) AS Id_Ruta
             FROM cuadrilla c
             LEFT JOIN integrante_cuadrilla ic ON ic.Id_Cuadrilla = c.Id_Cuadrilla
             LEFT JOIN empleado e ON e.Id = ic.Id_Empleado
             GROUP BY c.Id_Cuadrilla, c.Turno, c.Num_integrantes
             ORDER BY c.Id_Cuadrilla DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(PDO $pdo): bool {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO cuadrilla (Turno, Num_integrantes)
                 VALUES (:turno, :integrantes)'
            );
            $stmt->execute([
                ':turno' => $this->turno,
                ':integrantes' => $this->NumIntegrantes
            ]);
            $nuevoId = (int) $pdo->lastInsertId();
            $this->sincronizarIntegrantes($pdo, $nuevoId);
            $this->sincronizarRuta($pdo, $nuevoId);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function actualizar(PDO $pdo, int $id): bool {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE cuadrilla
                 SET Turno = :turno, Num_integrantes = :integrantes
                 WHERE Id_Cuadrilla = :id'
            );
            $stmt->execute([
                ':turno' => $this->turno,
                ':integrantes' => $this->NumIntegrantes,
                ':id' => $id
            ]);
            $existe = $stmt->rowCount() > 0;
            if (!$existe) {
                $check = $pdo->prepare('SELECT COUNT(*) FROM cuadrilla WHERE Id_Cuadrilla = :id');
                $check->execute([':id' => $id]);
                $existe = (int) $check->fetchColumn() > 0;
            }
            if (!$existe) {
                $pdo->rollBack();
                return false;
            }

            $cambios = $this->sincronizarIntegrantes($pdo, $id);
            $cambios = $this->sincronizarRuta($pdo, $id) || $cambios;
            $pdo->commit();
            return $stmt->rowCount() > 0 || $cambios;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function sincronizarRuta(PDO $pdo, int $idCuadrilla): bool {
        $actual = $pdo->prepare('SELECT Id_Ruta FROM realiza WHERE Id_Cuadrilla = :id LIMIT 1');
        $actual->execute([':id' => $idCuadrilla]);
        $antes = $actual->fetchColumn();
        $antes = $antes === false ? null : (int) $antes;
        if ($antes === $this->idRuta) {
            return false;
        }

        $pdo->prepare('DELETE FROM realiza WHERE Id_Cuadrilla = :id')->execute([':id' => $idCuadrilla]);
        if ($this->idRuta !== null) {
            $pdo->prepare('INSERT INTO realiza (Id_Ruta, Id_Cuadrilla) VALUES (:ruta, :cuadrilla)')
                ->execute([':ruta' => $this->idRuta, ':cuadrilla' => $idCuadrilla]);
        }
        return true;
    }

    private function sincronizarIntegrantes(PDO $pdo, int $idCuadrilla): bool {
        $ids = [];
        foreach ($this->cedulas as $cedula) {
            $stmt = $pdo->prepare('SELECT Id FROM empleado WHERE cedula = :cedula LIMIT 1');
            $stmt->execute([':cedula' => $cedula]);
            $idEmpleado = $stmt->fetchColumn();
            if ($idEmpleado === false) {
                throw new InvalidArgumentException("No existe ningún empleado con la cédula $cedula.");
            }

            $otra = $pdo->prepare('SELECT Id_Cuadrilla FROM integrante_cuadrilla WHERE Id_Empleado = :id AND Id_Cuadrilla <> :cuadrilla');
            $otra->execute([':id' => $idEmpleado, ':cuadrilla' => $idCuadrilla]);
            $otraCuadrilla = $otra->fetchColumn();
            if ($otraCuadrilla !== false) {
                throw new InvalidArgumentException("La cédula $cedula ya pertenece a la cuadrilla $otraCuadrilla.");
            }
            $ids[] = (int) $idEmpleado;
        }

        $actuales = $pdo->prepare('SELECT Id_Empleado FROM integrante_cuadrilla WHERE Id_Cuadrilla = :id');
        $actuales->execute([':id' => $idCuadrilla]);
        $antes = array_map('intval', $actuales->fetchAll(PDO::FETCH_COLUMN));

        $borrar = $pdo->prepare('DELETE FROM integrante_cuadrilla WHERE Id_Cuadrilla = :id');
        $borrar->execute([':id' => $idCuadrilla]);
        $insertar = $pdo->prepare('INSERT INTO integrante_cuadrilla (Id_Cuadrilla, Id_Empleado) VALUES (:cuadrilla, :empleado)');
        foreach ($ids as $idEmpleado) {
            $insertar->execute([':cuadrilla' => $idCuadrilla, ':empleado' => $idEmpleado]);
        }

        sort($antes);
        sort($ids);
        return $antes !== $ids;
    }
    public static function eliminar(PDO $pdo, int $id): bool {
        $asignaciones = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM chofer WHERE idCuadrilla = :id_chofer) +
                (SELECT COUNT(*) FROM limpiador WHERE idCuadrilla = :id_limpiador)'
        );
        $asignaciones->execute([':id_chofer' => $id, ':id_limpiador' => $id]);
        if ((int) $asignaciones->fetchColumn() > 0) {
            throw new InvalidArgumentException('No se puede eliminar la cuadrilla porque tiene empleados asignados.');
        }

        $stmt = $pdo->prepare('DELETE FROM cuadrilla WHERE Id_Cuadrilla = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
?>