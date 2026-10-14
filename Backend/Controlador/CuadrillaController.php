<?php

require_once __DIR__ . '/../Modelo/Cuadrilla.php';

class CuadrillaController
{
    public function listar(PDO $pdo): array
    {
        return cuadrilla::listar($pdo);
    }

    public function crear(PDO $pdo, array $datos): bool
    {
        return $this->crearDesdeDatos($pdo, $datos)->guardar($pdo);
    }

    public function actualizar(PDO $pdo, int $id, array $datos): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID de cuadrilla obligatorio.');
        }

        return $this->crearDesdeDatos($pdo, $datos)->actualizar($pdo, $id);
    }

    public function eliminar(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID de cuadrilla obligatorio.');
        }

        return cuadrilla::eliminar($pdo, $id);
    }

    private function crearDesdeDatos(PDO $pdo, array $datos): cuadrilla
    {
        $turno = trim((string) ($datos['Turno'] ?? $datos['turno'] ?? ''));
        $integrantes = filter_var($datos['Num_integrantes'] ?? $datos['num_integrantes'] ?? null, FILTER_VALIDATE_INT);

        if (!in_array($turno, ['mañana', 'tarde', 'noche'], true)) {
            throw new InvalidArgumentException('Selecciona un turno válido para la cuadrilla.');
        }

        if ($integrantes === false || $integrantes < 1 || $integrantes > 100) {
            throw new InvalidArgumentException('La cantidad de integrantes debe estar entre 1 y 100.');
        }

        $cedulas = $datos['Cedulas'] ?? $datos['cedulas'] ?? [];
        if (is_string($cedulas)) {
            $cedulas = preg_split('/[\s,;]+/', trim($cedulas), -1, PREG_SPLIT_NO_EMPTY);
        }
        $cedulas = array_values(array_unique(array_map('trim', (array) $cedulas)));
        foreach ($cedulas as $cedula) {
            if (!preg_match('/^[0-9.\-]{6,20}$/', $cedula)) {
                throw new InvalidArgumentException("La cédula '$cedula' no es válida.");
            }
        }
        if (count($cedulas) > $integrantes) {
            throw new InvalidArgumentException('Hay más cédulas que la cantidad de integrantes de la cuadrilla.');
        }

        $idRuta = (int) ($datos['Id_Ruta'] ?? $datos['id_ruta'] ?? 0);
        if ($idRuta > 0) {
            $existe = $pdo->prepare('SELECT COUNT(*) FROM ruta WHERE Id_Ruta = :id');
            $existe->execute([':id' => $idRuta]);
            if ((int) $existe->fetchColumn() === 0) {
                throw new InvalidArgumentException('La ruta seleccionada no existe.');
            }
        }

        $cuadrilla = new cuadrilla(0, $turno, $integrantes);
        $cuadrilla->setIdRuta($idRuta > 0 ? $idRuta : null);
        $cuadrilla->setCedulas($cedulas);
        return $cuadrilla;
    }
}
