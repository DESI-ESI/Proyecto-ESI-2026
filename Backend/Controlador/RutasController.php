<?php

require_once __DIR__ . '/../Modelo/Rutas.php';

class RutasController
{
    public function listar(PDO $pdo): array
    {
        return Rutas::listar($pdo);
    }

    public function crear(PDO $pdo, array $datos): bool
    {
        $ruta = $this->crearRutaDesdeDatos($datos);
        $cuadrillas = $this->leerCuadrillas($pdo, $datos);
        $contenedores = $this->leerContenedores($pdo, $datos);

        $pdo->beginTransaction();
        try {
            $ok = $ruta->crear($pdo);
            if ($ok) {
                $idRuta = (int) $pdo->lastInsertId();
                $this->sincronizarCuadrillas($pdo, $idRuta, $cuadrillas);
                $this->sincronizarContenedores($pdo, $idRuta, $contenedores);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $ok;
    }

    public function editar(PDO $pdo, int $idRuta, array $datos): bool
    {
        if ($idRuta <= 0) {
            throw new InvalidArgumentException('ID de ruta obligatorio.');
        }

        $ruta = $this->crearRutaDesdeDatos($datos);
        $cuadrillas = $this->leerCuadrillas($pdo, $datos);
        $contenedores = $this->leerContenedores($pdo, $datos);

        $pdo->beginTransaction();
        try {
            Rutas::actualizar($pdo, $idRuta, [
                'recorrido' => $ruta->getRecorrido(),
                'hora_inicio' => $ruta->getHoraInicio(),
                'hora_fin' => $ruta->getHoraFin(),
                'frecuencia' => $ruta->getFrecuencia(),
                'distancia' => $ruta->getDistancia(),
                'estado' => $ruta->getEstado(),
                'id_municipio' => $ruta->getIdMunicipio()
            ]);

            $existe = $pdo->prepare('SELECT 1 FROM ruta WHERE Id_Ruta = :id');
            $existe->execute([':id' => $idRuta]);
            if (!$existe->fetchColumn()) {
                $pdo->rollBack();
                return false;
            }

            $this->sincronizarCuadrillas($pdo, $idRuta, $cuadrillas);
            $this->sincronizarContenedores($pdo, $idRuta, $contenedores);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        return true;
    }

    private function leerCuadrillas(PDO $pdo, array $datos): array
    {
        $crudo = $datos['cuadrillas'] ?? [];
        if (!is_array($crudo)) {
            throw new InvalidArgumentException('Cuadrillas inválidas.');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $crudo), fn($id) => $id > 0)));
        if ($ids) {
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cuadrilla WHERE Id_Cuadrilla IN ($marcas)");
            $stmt->execute($ids);
            if ((int) $stmt->fetchColumn() !== count($ids)) {
                throw new InvalidArgumentException('Alguna de las cuadrillas seleccionadas no existe.');
            }
        }
        return $ids;
    }

    private function sincronizarCuadrillas(PDO $pdo, int $idRuta, array $ids): void
    {
        if ($ids) {
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM realiza WHERE Id_Ruta = ? AND Id_Cuadrilla NOT IN ($marcas)")
                ->execute(array_merge([$idRuta], $ids));
            $pdo->prepare("DELETE FROM realiza WHERE Id_Ruta <> ? AND Id_Cuadrilla IN ($marcas)")
                ->execute(array_merge([$idRuta], $ids));
        } else {
            $pdo->prepare('DELETE FROM realiza WHERE Id_Ruta = ?')->execute([$idRuta]);
        }

        $insertar = $pdo->prepare('INSERT IGNORE INTO realiza (Id_Ruta, Id_Cuadrilla) VALUES (?, ?)');
        foreach ($ids as $id) {
            $insertar->execute([$idRuta, $id]);
        }
    }

    private function leerContenedores(PDO $pdo, array $datos): array
    {
        $crudo = $datos['contenedores'] ?? [];
        if (!is_array($crudo)) {
            throw new InvalidArgumentException('Contenedores inválidos.');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $crudo), fn($id) => $id > 0)));
        if ($ids) {
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contenedor WHERE Id_Contenedor IN ($marcas)");
            $stmt->execute($ids);
            if ((int) $stmt->fetchColumn() !== count($ids)) {
                throw new InvalidArgumentException('Alguno de los contenedores seleccionados no existe.');
            }
        }
        return $ids;
    }

    private function sincronizarContenedores(PDO $pdo, int $idRuta, array $ids): void
    {
        if ($ids) {
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM existe WHERE Id_Ruta = ? AND Id_Contenedor NOT IN ($marcas)")
                ->execute(array_merge([$idRuta], $ids));
        } else {
            $pdo->prepare('DELETE FROM existe WHERE Id_Ruta = ?')->execute([$idRuta]);
        }

        $insertar = $pdo->prepare('INSERT IGNORE INTO existe (Id_Contenedor, Id_Ruta) VALUES (?, ?)');
        foreach ($ids as $id) {
            $insertar->execute([$id, $idRuta]);
        }
    }

    public function eliminar(PDO $pdo, int $idRuta): bool
    {
        if ($idRuta <= 0) {
            throw new InvalidArgumentException('ID de ruta obligatorio.');
        }

        return Rutas::eliminar($pdo, $idRuta);
    }

    private function crearRutaDesdeDatos(array $datos): Rutas
    {
        $recorrido = trim((string) ($datos['Recorrido'] ?? $datos['recorrido'] ?? ''));
        $horaInicio = substr(trim((string) ($datos['Horario_Inicio'] ?? '')), 0, 5);
        $horaFin = substr(trim((string) ($datos['Horario_Fin'] ?? '')), 0, 5);
        $frecuencia = trim((string) ($datos['Frecuencia'] ?? ''));
        $distancia = (float) ($datos['Distancia'] ?? 0);
        $estado = trim((string) ($datos['Estado'] ?? ''));
        $idMunicipio = (int) ($datos['Id_Municipio'] ?? $datos['id_municipio'] ?? 0);

        $frecuenciasValidas = [
            '1 vez por semana',
            '2 veces por semana',
            '3 veces por semana',
            '4 veces por semana',
            '5 veces por semana',
            '6 veces por semana',
            '7 veces por semana',
            'especifica'
        ];
        $frecuenciaEspecificaValida = preg_match('/^Días específicos:\\s*.+$/u', $frecuencia) === 1;
        $horariosValidos = preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $horaInicio) === 1
            && preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $horaFin) === 1;

        if ($recorrido === '' || !$horariosValidos || $horaInicio === $horaFin ||
            (!in_array($frecuencia, $frecuenciasValidas, true) && !$frecuenciaEspecificaValida) ||
            $distancia < 0 || !is_finite($distancia) || !in_array($estado, ['activa', 'inactiva'], true) || $idMunicipio <= 0) {
            throw new InvalidArgumentException('Datos de ruta inválidos.');
        }

        return new Rutas(0, $recorrido, $estado, $distancia, $horaInicio, $horaFin, $frecuencia, $idMunicipio);
    }
}
