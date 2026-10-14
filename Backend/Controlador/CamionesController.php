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

        if ($matricula === '' || $capacidad <= 0 || $idMunicipio <= 0) {
            throw new InvalidArgumentException('Faltan datos obligatorios o son inválidos.');
        }

        $this->validarEstado($estado);
        $idRuta = $this->validarRuta($pdo, $datos['id_ruta'] ?? null, $estado);

        $camion = new Camiones($matricula, $capacidad, $estado);
        $exito = $camion->crearCamion($pdo, $idMunicipio, $idRuta);

        return $exito;
    }

    public function editar(PDO $pdo, array $datos): bool
    {
        $matricula = trim($datos['matricula'] ?? '');
        if ($matricula === '') {
            throw new InvalidArgumentException('Matrícula no especificada.');
        }

        $estado = trim((string) ($datos['estado'] ?? ''));
        $this->validarEstado($estado);
        $datos['id_ruta'] = $this->validarRuta($pdo, $datos['id_ruta'] ?? null, $estado);

        return Camiones::actualizar($pdo, $matricula, $datos);
    }

    public function eliminar(PDO $pdo, array $datos): bool
    {
        $matricula = trim($datos['matricula'] ?? '');
        if ($matricula === '') {
            throw new InvalidArgumentException('Matrícula no especificada.');
        }

        return Camiones::eliminar($pdo, $matricula);
    }

    private function validarRuta(PDO $pdo, $valor, string $estado): ?int
    {
        $idRuta = (int) $valor;
        if ($idRuta <= 0) {
            return null;
        }
        if (!in_array($estado, ['Disponible', 'En Ruta'], true)) {
            throw new InvalidArgumentException('Solo se puede asignar una ruta a un camión disponible.');
        }
        if (!Camiones::existeRuta($pdo, $idRuta)) {
            throw new InvalidArgumentException('La ruta seleccionada no existe.');
        }
        return $idRuta;
    }

    private function validarEstado(string $estado): void
    {
        if (!in_array($estado, ['Disponible', 'En Ruta', 'Mantenimiento', 'Roto', 'Fuera de servicio'], true)) {
            throw new InvalidArgumentException('Selecciona un estado válido para el camión.');
        }
    }
}
?>