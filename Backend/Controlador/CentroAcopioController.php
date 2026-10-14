<?php

require_once __DIR__ . '/../Modelo/CentroAcopio.php';

class CentroAcopioController
{
    public function listar(PDO $pdo): array
    {
        return CentroAcopio::listar($pdo);
    }

    public function crear(PDO $pdo, array $datos): bool
    {
        $d = $this->normalizar($pdo, $datos);

        return (new CentroAcopio(0, $d['nombre'], $d['ubicacion'], $d['capacidad'], $d['cantidad_almacenada'], $d['tipo'], $d['estado'], $d['tipos_residuo'], $d['id_municipio']))->registrar($pdo);
    }

    public function actualizar(PDO $pdo, int $id, array $datos): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID de instalación inválido.');
        }

        return CentroAcopio::actualizar($pdo, $id, $this->normalizar($pdo, $datos));
    }

    private function normalizar(PDO $pdo, array $datos): array
    {
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $tipo = strtolower(trim((string) ($datos['tipo'] ?? 'centro_acopio')));
        $estado = strtolower(trim((string) ($datos['estado'] ?? 'activo')));
        $ubicacion = trim((string) ($datos['ubicacion'] ?? ''));
        $capacidad = (float) ($datos['capacidad'] ?? 0);
        $almacenado = (float) ($datos['cantidad_almacenada'] ?? 0);
        $tipos = trim((string) ($datos['tipos_residuo'] ?? ''));
        $idMunicipio = (int) ($datos['id_municipio'] ?? 0);

        if ($nombre === '' || mb_strlen($nombre) > 30 || $ubicacion === '' || $capacidad <= 0 || $almacenado < 0 || $almacenado > $capacidad) {
            throw new InvalidArgumentException('Datos de la instalación inválidos.');
        }

        if (!in_array($tipo, ['centro_acopio', 'vertedero'], true) || !in_array($estado, ['activo', 'inactivo', 'mantenimiento'], true)) {
            throw new InvalidArgumentException('Tipo o estado de instalación inválido.');
        }

        if ($idMunicipio > 0) {
            $existe = $pdo->prepare('SELECT 1 FROM municipio WHERE Id_Municipio = :id');
            $existe->execute([':id' => $idMunicipio]);
            if (!$existe->fetchColumn()) {
                throw new InvalidArgumentException('El municipio indicado no existe.');
            }
        }

        if (mb_strlen($tipos) > 255) {
            throw new InvalidArgumentException('La lista de tipos de residuo es demasiado larga.');
        }

        return [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'estado' => $estado,
            'ubicacion' => $ubicacion,
            'capacidad' => $capacidad,
            'cantidad_almacenada' => $almacenado,
            'tipos_residuo' => $tipos === '' ? null : $tipos,
            'id_municipio' => $idMunicipio > 0 ? $idMunicipio : null
        ];
    }
    public function eliminar(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID de centro de acopio inválido.');
        }

        return CentroAcopio::eliminar($pdo, $id);
    }
}
