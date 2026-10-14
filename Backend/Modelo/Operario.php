<?php 

class Operario extends Empleado {
    private string $idCentroAcopio;

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
        string $idCentroAcopio
    ) {
        parent::__construct($id, $cedula, $nombre, $apellido, $email, $telefono, $rol, $turno, $area);
        $this->idCentroAcopio = $idCentroAcopio;
    }

    public function getIdCentroAcopio(): string {
        return $this->idCentroAcopio;
    }

    public function setIdCentroAcopio(string $idCentroAcopio): void {
        $this->idCentroAcopio = $idCentroAcopio;
    }
}
?>