<?php

require_once __DIR__ . '/../Modelo/Contenedores.php';

class ContenedoresController
{
    public function listar(PDO $pdo): array
    {
        return Contenedores::listar($pdo);
    }

    public function registrar(
        PDO $pdo,
        Contenedores $contenedor,
        int $idMunicipio,
        ?int $numero = null,
        ?float $latitud = null,
        ?float $longitud = null
    ): bool
    {
        return $contenedor->registrarContenedor($pdo, $idMunicipio, $numero, $latitud, $longitud);
    }

    public function darBaja(PDO $pdo, int $idContenedor, string $motivo): bool
    {
        $motivo = trim($motivo);
        if ($motivo === '' || mb_strlen($motivo, 'UTF-8') > 500) {
            throw new InvalidArgumentException('Indicá un motivo de baja de hasta 500 caracteres.');
        }

        return Contenedores::darBajaContenedor($pdo, $idContenedor, $motivo);
    }

    public function modificarUbicacion(PDO $pdo, int $idContenedor, string $ubicacion): void
    {
        Contenedores::modificarUbicacion($pdo, $idContenedor, $ubicacion);
    }

    public function modificarEstado(PDO $pdo, int $idContenedor, string $estado): void
    {
        Contenedores::modificarEstado($pdo, $idContenedor, $estado);
    }

    public function modificarTipoResiduo(PDO $pdo, int $idContenedor, string $tipoResiduo): void
    {
        Contenedores::modificarTipoResiduo($pdo, $idContenedor, $tipoResiduo);
    }

    public function actualizar(PDO $pdo, int $idContenedor, array $datos): bool
    {
        return Contenedores::actualizar($pdo, $idContenedor, $datos);
    }

    public function consultarCantidadRepuestos(PDO $pdo): int
    {
        return Contenedores::consultarCantidadRepuestos($pdo);
    }

    public function registrarRepuesto(PDO $pdo, int $idContenedor): void
    {
        Contenedores::registrarContenedorRepuesto($pdo, $idContenedor);
    }

    public function darBajaRepuesto(PDO $pdo, int $idContenedor): void
    {
        Contenedores::darBajaContenedorRepuesto($pdo, $idContenedor);
    }

    public function modificarRepuesto(
        PDO $pdo,
        int $idContenedor,
        float $capacidad,
        string $tipoResiduo,
        string $estado
    ): void {
        Contenedores::modificarContenedorRepuesto(
            $pdo,
            $idContenedor,
            $capacidad,
            $tipoResiduo,
            $estado
        );
    }
}

?>