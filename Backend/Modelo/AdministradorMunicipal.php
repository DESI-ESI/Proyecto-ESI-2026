<?php

class AdministradorMunicipal extends Empleado
{
    private int $idMunicipio;

    public function __construct(
        int $id,
        string $cedula,
        string $nombre,
        string $apellido,
        string $email,
        string $telefono,
        string $rol,
        string $turno,
        string $area,
        int $idMunicipio
    ) {
        parent::__construct($id, $cedula, $nombre, $apellido, $email, $telefono, $rol, $turno, $area);
        $this->idMunicipio = $idMunicipio;
    }

    public function getIdMunicipio(): int
    {
        return $this->idMunicipio;
    }

    public function setIdMunicipio(int $idMunicipio): void
    {
        $this->idMunicipio = $idMunicipio;
    }
}