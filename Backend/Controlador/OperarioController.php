<?php

require_once __DIR__ . '/../Modelo/Empleado.php';
require_once __DIR__ . '/../Modelo/Operario.php';

class OperarioController
{
    public function obtenerCentroAcopio(Operario $operario): string
    {
        return $operario->getIdCentroAcopio();
    }

    public function cambiarCentroAcopio(Operario $operario, string $idCentroAcopio): void
    {
        if (trim($idCentroAcopio) === '') {
            throw new InvalidArgumentException('El centro de acopio es obligatorio.');
        }

        $operario->setIdCentroAcopio($idCentroAcopio);
    }
}
