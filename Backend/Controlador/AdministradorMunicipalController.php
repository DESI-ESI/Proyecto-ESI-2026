<?php

require_once __DIR__ . '/../Modelo/Empleado.php';
require_once __DIR__ . '/../Modelo/AdministradorMunicipal.php';

class AdministradorMunicipalController
{
    public function obtenerMunicipio(AdministradorMunicipal $administrador): int
    {
        return $administrador->getIdMunicipio();
    }

    public function cambiarMunicipio(AdministradorMunicipal $administrador, int $idMunicipio): void
    {
        if ($idMunicipio <= 0) {
            throw new InvalidArgumentException('El municipio debe ser válido.');
        }

        $administrador->setIdMunicipio($idMunicipio);
    }
}
