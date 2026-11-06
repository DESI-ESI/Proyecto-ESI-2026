<?php

require_once __DIR__ . '/../Modelo/Incidencia.php';

class IncidenciaController
{
    public function listar(PDO $pdo): array
    {
        return incidencia::listar($pdo);
    }

    public function crear(PDO $pdo, array $datos, ?int $idCreador = null): bool
    {
        $datos['Estado'] = 'abierta';
        return $this->crearDesdeDatos($pdo, $datos, $idCreador)->guardar($pdo);
    }

    public function actualizar(PDO $pdo, int $id, array $datos): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID de incidencia obligatorio.');
        }

        return $this->crearDesdeDatos($pdo, $datos)->actualizar($pdo, $id);
    }

    public function asignar(PDO $pdo, int $id, int $idCuadrilla): bool
    {
        if ($id <= 0 || $idCuadrilla <= 0) {
            throw new InvalidArgumentException('ID de incidencia y cuadrilla obligatorios.');
        }

        return incidencia::asignar($pdo, $id, $idCuadrilla);
    }

    public function eliminar(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID de incidencia obligatorio.');
        }

        return incidencia::eliminar($pdo, $id);
    }

    private function crearDesdeDatos(PDO $pdo, array $datos, ?int $idCreador = null): incidencia
    {
        $tipo = trim((string) ($datos['Tipo'] ?? $datos['tipo'] ?? ''));
        $descripcion = trim((string) ($datos['Descripcion'] ?? $datos['descripcion'] ?? ''));
        $fecha = trim((string) ($datos['Fecha'] ?? $datos['fecha'] ?? ''));
        $estado = trim((string) ($datos['Estado'] ?? $datos['estado'] ?? ''));
        $zona = trim((string) ($datos['Zona'] ?? $datos['zona'] ?? ''));

        if (!in_array($tipo, ['incompleto'], true) || mb_strlen($tipo, 'UTF-8') > 500) {
            throw new InvalidArgumentException('Selecciona un tipo permitido para la incidencia.');
        }
        if (mb_strlen($descripcion, 'UTF-8') > 500) {
            throw new InvalidArgumentException('La descripción no puede superar los 500 caracteres.');
        }

        $fechaObjeto = null;
        foreach (['Y-m-d\TH:i:s', 'Y-m-d\TH:i'] as $formatoFecha) {
            $fechaObjeto = DateTime::createFromFormat($formatoFecha, $fecha);
            $erroresFecha = DateTime::getLastErrors();
            if ($fechaObjeto && ($erroresFecha === false || ($erroresFecha['warning_count'] === 0 && $erroresFecha['error_count'] === 0))) {
                break;
            }
            $fechaObjeto = null;
        }
        if (!$fechaObjeto) {
            throw new InvalidArgumentException('La fecha de la incidencia no es válida.');
        }

        if (!in_array($estado, ['abierta', 'en curso', 'resuelta'], true)) {
            throw new InvalidArgumentException('Selecciona un estado válido para la incidencia.');
        }
        if (mb_strlen($zona, 'UTF-8') > 120) {
            throw new InvalidArgumentException('La zona no puede superar los 120 caracteres.');
        }
        $numero = (int) ($datos['numero_contenedor'] ?? $datos['Numero_Contenedor'] ?? 0);
        if ($numero <= 0) {
            throw new InvalidArgumentException('Seleccioná un contenedor para la incidencia.');
        }
        $latitud = null;
        $longitud = null;
        if ($numero > 0) {
            $contenedor = $pdo->prepare(
                'SELECT Latitud, Longitud FROM contenedor WHERE Numero_Contenedor = :numero LIMIT 1'
            );
            $contenedor->execute([':numero' => $numero]);
            $coordenadas = $contenedor->fetch(PDO::FETCH_ASSOC);
            if (!$coordenadas) {
                throw new InvalidArgumentException('El contenedor número ' . $numero . ' no existe.');
            }
            $latitud = is_numeric($coordenadas['Latitud']) ? (float) $coordenadas['Latitud'] : null;
            $longitud = is_numeric($coordenadas['Longitud']) ? (float) $coordenadas['Longitud'] : null;
            if ($latitud === null || $longitud === null) {
                $latitud = null;
                $longitud = null;
            }
        }

        $idCuadrilla = (int) ($datos['Id_Cuadrilla'] ?? $datos['id_cuadrilla'] ?? 0);
        if ($idCuadrilla > 0) {
            $existe = $pdo->prepare('SELECT COUNT(*) FROM cuadrilla WHERE Id_Cuadrilla = :id');
            $existe->execute([':id' => $idCuadrilla]);
            if ((int) $existe->fetchColumn() === 0) {
                throw new InvalidArgumentException('La cuadrilla seleccionada no existe.');
            }
        }

        return new incidencia(
            $descripcion,
            $fechaObjeto->format('Y-m-d H:i:s'),
            $tipo,
            $estado,
            $numero > 0 ? $numero : null,
            $idCuadrilla > 0 ? $idCuadrilla : null,
            $zona !== '' ? $zona : null,
            $latitud,
            $longitud,
            $idCreador
        );
    }
}
