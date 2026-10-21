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
                    COALESCE(GROUP_CONCAT(miembros.cedula ORDER BY miembros.cedula SEPARATOR ', '), '') AS Cedulas,
                    (SELECT r.Id_Ruta FROM realiza r WHERE r.Id_Cuadrilla = c.Id_Cuadrilla LIMIT 1) AS Id_Ruta
             FROM cuadrilla c
             LEFT JOIN (
                 SELECT e.cedula, ch.idCuadrilla
                 FROM chofer ch JOIN empleado e ON e.Id = ch.Id
                 UNION ALL
                 SELECT e.cedula, l.idCuadrilla
                 FROM limpiador l JOIN empleado e ON e.Id = l.Id
             ) miembros ON miembros.idCuadrilla = c.Id_Cuadrilla
             GROUP BY c.Id_Cuadrilla, c.Turno, c.Num_integrantes
             ORDER BY c.Id_Cuadrilla DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarDeEmpleado(PDO $pdo, int $idEmpleado): ?array {
        $stmt = $pdo->prepare(
            'SELECT c.Id_Cuadrilla, c.Turno, c.Num_integrantes, r.Id_Ruta,
                    r.Recorrido, r.Horario_Inicio, r.Horario_Fin, r.Frecuencia,
                    r.Distancia, r.Estado, m.Nombre AS Municipio
             FROM empleado e
             LEFT JOIN chofer ch ON ch.Id = e.Id
             LEFT JOIN limpiador l ON l.Id = e.Id
             JOIN cuadrilla c ON c.Id_Cuadrilla = COALESCE(ch.idCuadrilla, l.idCuadrilla)
             LEFT JOIN realiza re ON re.Id_Cuadrilla = c.Id_Cuadrilla
             LEFT JOIN ruta r ON r.Id_Ruta = re.Id_Ruta
             LEFT JOIN municipio m ON m.Id_Municipio = r.Id_Municipio
             WHERE e.Id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $idEmpleado]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
    }

    public static function integrantes(PDO $pdo, int $idCuadrilla): array {
        $stmt = $pdo->prepare(
            'SELECT e.Nombre, e.Apellido, e.cedula
             FROM (
                 SELECT Id, idCuadrilla FROM chofer
                 UNION ALL
                 SELECT Id, idCuadrilla FROM limpiador
             ) miembros
             JOIN empleado e ON e.Id = miembros.Id
             WHERE miembros.idCuadrilla = :id
             ORDER BY e.Nombre, e.Apellido'
        );
        $stmt->execute([':id' => $idCuadrilla]);

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
            $stmt = $pdo->prepare(
                'SELECT e.Id, ch.idCuadrilla AS Id_Cuadrilla, "chofer" AS tipo
                 FROM empleado e JOIN chofer ch ON ch.Id = e.Id
                WHERE e.cedula = :cedula_chofer
                 UNION ALL
                 SELECT e.Id, l.idCuadrilla AS Id_Cuadrilla, "limpiador" AS tipo
                 FROM empleado e JOIN limpiador l ON l.Id = e.Id
                WHERE e.cedula = :cedula_limpiador
                 LIMIT 1'
            );
            $stmt->execute([
                ':cedula_chofer' => $cedula,
                ':cedula_limpiador' => $cedula
            ]);
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$empleado) {
                throw new InvalidArgumentException("La cédula $cedula no corresponde a un chofer o limpiador.");
            }

            $idEmpleado = (int) $empleado['Id'];
            $otraCuadrilla = $empleado['Id_Cuadrilla'];
            if ($otraCuadrilla !== null && (int) $otraCuadrilla !== $idCuadrilla) {
                throw new InvalidArgumentException("La cédula $cedula ya pertenece a la cuadrilla $otraCuadrilla.");
            }
            $ids[] = ['id' => $idEmpleado, 'tipo' => $empleado['tipo']];
        }

        $actuales = $pdo->prepare(
            'SELECT Id FROM chofer WHERE idCuadrilla = :chofer
             UNION
             SELECT Id FROM limpiador WHERE idCuadrilla = :limpiador'
        );
        $actuales->execute([':chofer' => $idCuadrilla, ':limpiador' => $idCuadrilla]);
        $antes = array_map('intval', $actuales->fetchAll(PDO::FETCH_COLUMN));
        $despues = array_column($ids, 'id');

        $pdo->prepare('UPDATE chofer SET idCuadrilla = NULL WHERE idCuadrilla = :id')
            ->execute([':id' => $idCuadrilla]);
        $pdo->prepare('UPDATE limpiador SET idCuadrilla = NULL WHERE idCuadrilla = :id')
            ->execute([':id' => $idCuadrilla]);
        $asignarChofer = $pdo->prepare('UPDATE chofer SET idCuadrilla = :cuadrilla WHERE Id = :empleado');
        $asignarLimpiador = $pdo->prepare('UPDATE limpiador SET idCuadrilla = :cuadrilla WHERE Id = :empleado');
        foreach ($ids as $empleado) {
            $asignacion = $empleado['tipo'] === 'chofer' ? $asignarChofer : $asignarLimpiador;
            $asignacion->execute([':cuadrilla' => $idCuadrilla, ':empleado' => $empleado['id']]);
        }

        sort($antes);
        sort($despues);
        return $antes !== $despues;
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