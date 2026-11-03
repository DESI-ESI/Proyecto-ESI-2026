<?php

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../Modelo/Camiones.php';

class CamionesController
{
    public function listar(PDO $pdo): array
    {
        return Camiones::listar($pdo);
    }

    public function crear(PDO $pdo, array $datos): bool
    {
        $matricula = trim($datos['matricula'] ?? '');
        $capacidad = floatval($datos['capacidad'] ?? 0);
        $estado = trim($datos['estado'] ?? '');
        $idMunicipio = intval($datos['id_municipio'] ?? 0);
        [$latitud, $longitud] = $this->validarCoordenadas($datos);

        if ($matricula === '' || $capacidad <= 0 || $idMunicipio <= 0) {
            throw new InvalidArgumentException('Faltan datos obligatorios o son inválidos.');
        }

        $this->validarEstado($estado);
        $pdo->beginTransaction();
        try {
            $idRuta = $this->validarRuta($pdo, $datos['id_ruta'] ?? null, $estado);
            $camion = new Camiones($matricula, $capacidad, $estado);
            $exito = $camion->crearCamion($pdo, $idMunicipio, $idRuta, $latitud, $longitud);
            $pdo->commit();
            return $exito;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function editar(PDO $pdo, array $datos): bool
    {
        $matricula = trim($datos['matricula'] ?? '');
        if ($matricula === '') {
            throw new InvalidArgumentException('Matrícula no especificada.');
        }

        $estado = trim((string) ($datos['estado'] ?? ''));
        $this->validarEstado($estado);
        [$datos['latitud'], $datos['longitud']] = $this->validarCoordenadas($datos);

        $pdo->beginTransaction();
        try {
            $datos['id_ruta'] = $this->validarRuta($pdo, $datos['id_ruta'] ?? null, $estado, $matricula);
            $actualizado = Camiones::actualizar($pdo, $matricula, $datos);
            $pdo->commit();
            return $actualizado;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function eliminar(PDO $pdo, array $datos): bool
    {
        $matricula = trim($datos['matricula'] ?? '');
        if ($matricula === '') {
            throw new InvalidArgumentException('Matrícula no especificada.');
        }

        $motivo = trim((string) ($datos['motivo'] ?? ''));
        if ($motivo === '' || mb_strlen($motivo, 'UTF-8') > 500) {
            throw new InvalidArgumentException('Indicá un motivo de baja de hasta 500 caracteres.');
        }

        return Camiones::darBaja($pdo, $matricula, $motivo);
    }

    private function validarRuta(PDO $pdo, $valor, string $estado, ?string $matricula = null): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        $idRuta = filter_var($valor, FILTER_VALIDATE_INT);
        if ($idRuta === false || $idRuta <= 0) {
            throw new InvalidArgumentException('La ruta seleccionada no es válida.');
        }
        if (!in_array($estado, ['Disponible', 'En Ruta'], true)) {
            throw new InvalidArgumentException('Solo se puede asignar una ruta a un camión disponible.');
        }
        $ruta = $pdo->prepare('SELECT Id_Ruta FROM ruta WHERE Id_Ruta = :id FOR UPDATE');
        $ruta->execute([':id' => $idRuta]);
        if (!$ruta->fetchColumn()) {
            throw new InvalidArgumentException('La ruta seleccionada no existe.');
        }

        $sql = 'SELECT Matricula FROM camion WHERE Id_Ruta = :ruta';
        $parametros = [':ruta' => $idRuta];
        if ($matricula !== null) {
            $sql .= ' AND Matricula <> :matricula';
            $parametros[':matricula'] = $matricula;
        }
        $asignado = $pdo->prepare($sql . ' LIMIT 1 FOR UPDATE');
        $asignado->execute($parametros);
        if ($asignado->fetchColumn()) {
            throw new InvalidArgumentException('La ruta seleccionada ya tiene un camión asignado.');
        }
        return $idRuta;
    }

    private function validarEstado(string $estado): void
    {
        if (!in_array($estado, ['Disponible', 'En Ruta', 'Mantenimiento', 'Roto', 'Fuera de servicio'], true)) {
            throw new InvalidArgumentException('Selecciona un estado válido para el camión.');
        }
    }

    private function validarCoordenadas(array $datos): array
    {
        $latitud = $datos['latitud'] ?? $datos['Latitud'] ?? null;
        $longitud = $datos['longitud'] ?? $datos['Longitud'] ?? null;
        if ($latitud === '' || $latitud === null) $latitud = null;
        if ($longitud === '' || $longitud === null) $longitud = null;
        if (($latitud === null) !== ($longitud === null)
            || ($latitud !== null && (!is_numeric($latitud) || !is_numeric($longitud)))) {
            throw new InvalidArgumentException('Ingresá ambas coordenadas del camión, o dejá ambas vacías.');
        }
        if ($latitud === null) {
            return [null, null];
        }

        $latitud = (float) $latitud;
        $longitud = (float) $longitud;
        if (!is_finite($latitud) || !is_finite($longitud)
            || $latitud < -90 || $latitud > 90 || $longitud < -180 || $longitud > 180) {
            throw new InvalidArgumentException('Las coordenadas del camión están fuera de rango.');
        }

        return [$latitud, $longitud];
    }
}
?>