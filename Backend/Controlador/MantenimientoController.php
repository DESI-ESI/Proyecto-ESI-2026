<?php

class MantenimientoController
{
    private const TIPOS = ['preventivo', 'correctivo'];
    private const ESTADOS = ['en proceso', 'finalizado'];

    public function listar(PDO $pdo, int $idCamion = 0): array
    {
        $sql = 'SELECT m.Id_Mantenimiento, m.Fecha_Inicio, m.Fecha_Fin, m.Tipo_Mantenimiento, m.Descripcion, m.Estado, m.Id_Camion, c.Matricula
                FROM mantenimiento m INNER JOIN camion c ON c.Id_Camion = m.Id_Camion';
        $params = [];
        if ($idCamion > 0) {
            $sql .= ' WHERE m.Id_Camion = :id';
            $params[':id'] = $idCamion;
        }
        $stmt = $pdo->prepare($sql . ' ORDER BY m.Fecha_Inicio DESC, m.Id_Mantenimiento DESC');
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(PDO $pdo, array $datos): bool
    {
        $idCamion = (int) ($datos['id_camion'] ?? 0);
        $tipo = $this->validarTipo($datos['tipo'] ?? '');
        $descripcion = trim((string) ($datos['descripcion'] ?? ''));
        $fecha = $this->normalizarFecha($datos['fecha_inicio'] ?? '') ?? date('Y-m-d H:i:s');

        if ($idCamion <= 0 || !$this->existeCamion($pdo, $idCamion)) {
            throw new InvalidArgumentException('Selecciona un camión válido.');
        }
        $estadoCamion = $pdo->prepare('SELECT Estado FROM camion WHERE Id_Camion = :id');
        $estadoCamion->execute([':id' => $idCamion]);
        if (!in_array($estadoCamion->fetchColumn(), ['Mantenimiento', 'Roto'], true)) {
            throw new InvalidArgumentException('Solo se puede registrar mantenimiento a camiones en estado Mantenimiento o Roto.');
        }
        $abierto = $pdo->prepare("SELECT COUNT(*) FROM mantenimiento WHERE Id_Camion = :id AND Estado = 'en proceso'");
        $abierto->execute([':id' => $idCamion]);
        if ((int) $abierto->fetchColumn() > 0) {
            throw new InvalidArgumentException('Ese camión ya tiene un mantenimiento en proceso.');
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO mantenimiento (Fecha_Inicio, Tipo_Mantenimiento, Descripcion, Estado, Id_Camion)
                                   VALUES (:fecha, :tipo, :descripcion, 'en proceso', :camion)");
            $stmt->execute([':fecha' => $fecha, ':tipo' => $tipo, ':descripcion' => $descripcion, ':camion' => $idCamion]);
            $pdo->prepare("UPDATE camion SET Estado = 'Mantenimiento', Id_Ruta = NULL WHERE Id_Camion = :id")->execute([':id' => $idCamion]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return true;
    }

    public function actualizar(PDO $pdo, int $id, array $datos): bool
    {
        $actual = $this->obtener($pdo, $id);
        if (!$actual) {
            return false;
        }

        $tipo = $this->validarTipo($datos['tipo'] ?? '');
        $estado = (string) ($datos['estado'] ?? '');
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new InvalidArgumentException('Selecciona un estado válido.');
        }
        if ($estado === 'en proceso' && $actual['Estado'] !== 'en proceso') {
            $abierto = $pdo->prepare("SELECT COUNT(*) FROM mantenimiento WHERE Id_Camion = :id AND Estado = 'en proceso'");
            $abierto->execute([':id' => $actual['Id_Camion']]);
            if ((int) $abierto->fetchColumn() > 0) {
                throw new InvalidArgumentException('Ese camión ya tiene un mantenimiento en proceso.');
            }
        }
        $descripcion = trim((string) ($datos['descripcion'] ?? ''));
        $fechaInicio = $this->normalizarFecha($datos['fecha_inicio'] ?? '') ?? $actual['Fecha_Inicio'];
        $fechaFin = $estado === 'finalizado' ? ($actual['Fecha_Fin'] ?: date('Y-m-d H:i:s')) : null;

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE mantenimiento SET Fecha_Inicio = :ini, Fecha_Fin = :fin, Tipo_Mantenimiento = :tipo, Descripcion = :descripcion, Estado = :estado WHERE Id_Mantenimiento = :id');
            $stmt->execute([':ini' => $fechaInicio, ':fin' => $fechaFin, ':tipo' => $tipo, ':descripcion' => $descripcion, ':estado' => $estado, ':id' => $id]);
            $this->sincronizarCamion($pdo, (int) $actual['Id_Camion']);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return true;
    }

    public function eliminar(PDO $pdo, int $id): bool
    {
        $actual = $this->obtener($pdo, $id);
        if (!$actual) {
            return false;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM mantenimiento WHERE Id_Mantenimiento = :id')->execute([':id' => $id]);
            $this->sincronizarCamion($pdo, (int) $actual['Id_Camion']);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return true;
    }

    private function sincronizarCamion(PDO $pdo, int $idCamion): void
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mantenimiento WHERE Id_Camion = :id AND Estado = 'en proceso'");
        $stmt->execute([':id' => $idCamion]);
        if ((int) $stmt->fetchColumn() > 0) {
            $pdo->prepare("UPDATE camion SET Estado = 'Mantenimiento', Id_Ruta = NULL WHERE Id_Camion = :id")->execute([':id' => $idCamion]);
        } else {
            $pdo->prepare("UPDATE camion SET Estado = 'Disponible' WHERE Id_Camion = :id AND Estado = 'Mantenimiento'")->execute([':id' => $idCamion]);
        }
    }

    private function obtener(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM mantenimiento WHERE Id_Mantenimiento = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function existeCamion(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM camion WHERE Id_Camion = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    private function validarTipo($tipo): string
    {
        $tipo = strtolower(trim((string) $tipo));
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException('Selecciona un tipo de mantenimiento válido.');
        }
        return $tipo;
    }

    private function normalizarFecha($valor): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        $ts = strtotime(str_replace('T', ' ', $valor));
        if ($ts === false) {
            throw new InvalidArgumentException('La fecha no es válida.');
        }
        return date('Y-m-d H:i:s', $ts);
    }
}
