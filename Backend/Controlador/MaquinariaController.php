<?php

require_once __DIR__ . '/../Modelo/Maquinaria.php';

class MaquinariaController
{
    public function listar(PDO $pdo): array
    {
        return Maquinaria::listar($pdo);
    }

    public function crear(PDO $pdo, array $datos): bool
    {
        $d = $this->validar($pdo, $datos);
        return (new Maquinaria(0, $d['id_centro'], $d['nombre'], $d['tipo'], $d['estado']))->registrar($pdo);
    }

    public function actualizar(PDO $pdo, int $id, array $datos): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID de maquinaria inválido.');
        }

        return Maquinaria::actualizar($pdo, $id, $this->validar($pdo, $datos));
    }

    public function eliminar(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID de maquinaria inválido.');
        }

        return Maquinaria::eliminar($pdo, $id);
    }

    private function validar(PDO $pdo, array $datos): array
    {
        $idCentro = (int) ($datos['id_centro'] ?? $datos['Id_Centro'] ?? 0);
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $tipo = trim((string) ($datos['tipo'] ?? ''));
        $estado = strtolower(trim((string) ($datos['estado'] ?? 'operativa')));

        if ($idCentro <= 0 || $nombre === '' || mb_strlen($nombre) > 60 || mb_strlen($tipo) > 50) {
            throw new InvalidArgumentException('Datos de maquinaria inválidos.');
        }

        if (!in_array($estado, ['operativa', 'mantenimiento', 'fuera_de_servicio'], true)) {
            throw new InvalidArgumentException('Estado de maquinaria inválido.');
        }

        $existe = $pdo->prepare('SELECT 1 FROM centro_acopio WHERE Id_Centro = :id');
        $existe->execute([':id' => $idCentro]);
        if (!$existe->fetchColumn()) {
            throw new InvalidArgumentException('La instalación indicada no existe.');
        }

        return ['id_centro' => $idCentro, 'nombre' => $nombre, 'tipo' => $tipo === '' ? null : $tipo, 'estado' => $estado];
    }
}