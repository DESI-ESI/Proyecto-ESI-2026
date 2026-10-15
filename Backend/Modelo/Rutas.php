<?php

class rutas{

private int $idRuta;
private string $recorrido;
private string $estado;
private float $distancia;
private string $horaInicio;
private string $horaFin;
private string $frecuencia;
private int $idMunicipio;

public function __construct(int $idRuta, string $recorrido, string $estado, float $distancia, string $horaInicio, string $horaFin, string $frecuencia, int $idMunicipio = 0) {
    $this->idRuta = $idRuta;
    $this->recorrido = $recorrido;
    $this->estado = $estado;
    $this->distancia = $distancia;
    $this->horaInicio = $horaInicio;
    $this->horaFin = $horaFin;
    $this->frecuencia = $frecuencia;
    $this->idMunicipio = $idMunicipio;

}

public function getIdRuta(): int {
    return $this->idRuta;

}

public function setIdRuta(int $idRuta): void {
    $this->idRuta = $idRuta;
}

public function getRecorrido(): string {
    return $this->recorrido;
}

public function setRecorrido(string $recorrido): void {
    $this->recorrido = $recorrido;


}

public function getEstado(): string {
    return $this->estado;
}

public function setEstado(string $estado): void {
    $this->estado = $estado;
}


public function getDistancia(): float {
    return $this->distancia;
}

public function setDistancia(float $distancia): void {
    $this->distancia = $distancia;
}

public function getHoraInicio(): string {
    return $this->horaInicio;
}

public function setHoraInicio(string $horaInicio): void {
    $this->horaInicio = $horaInicio;
}


public function getHoraFin(): string {
    return $this->horaFin;
}

public function setHoraFin(string $horaFin): void {
    $this->horaFin = $horaFin;
}

public function getFrecuencia(): string {
    return $this->frecuencia;
}

public function setFrecuencia(string $frecuencia): void {
    $this->frecuencia = $frecuencia;
}

public function getIdMunicipio(): int {
    return $this->idMunicipio;
}

public function setIdMunicipio(int $idMunicipio): void {
    $this->idMunicipio = $idMunicipio;
}

public static function listar(PDO $pdo): array {
    $stmt = $pdo->query(
        'SELECT r.Id_Ruta, r.Recorrido, r.Horario_Inicio, r.Horario_Fin, r.Frecuencia,
                r.Distancia, r.Estado, r.Id_Municipio,
                (SELECT GROUP_CONCAT(re.Id_Cuadrilla ORDER BY re.Id_Cuadrilla) FROM realiza re WHERE re.Id_Ruta = r.Id_Ruta) AS Cuadrillas,
                (SELECT GROUP_CONCAT(e.Id_Contenedor ORDER BY c.Numero_Contenedor) FROM existe e JOIN contenedor c ON c.Id_Contenedor = e.Id_Contenedor WHERE e.Id_Ruta = r.Id_Ruta) AS Contenedores,
                (SELECT GROUP_CONCAT(c.Numero_Contenedor ORDER BY c.Numero_Contenedor) FROM existe e JOIN contenedor c ON c.Id_Contenedor = e.Id_Contenedor WHERE e.Id_Ruta = r.Id_Ruta) AS Numeros_Contenedores
         FROM ruta r
         ORDER BY r.Id_Ruta DESC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function crear(PDO $pdo): bool {
    $stmt = $pdo->prepare(
        'INSERT INTO ruta
            (Recorrido, Horario_Inicio, Horario_Fin, Frecuencia, Distancia, Estado, Id_Municipio)
         VALUES (:recorrido, :hora_inicio, :hora_fin, :frecuencia, :distancia, :estado, :id_municipio)'
    );

    return $stmt->execute([
        ':recorrido' => $this->recorrido,
        ':hora_inicio' => $this->horaInicio,
        ':hora_fin' => $this->horaFin,
        ':frecuencia' => $this->frecuencia,
        ':distancia' => $this->distancia,
        ':estado' => $this->estado,
        ':id_municipio' => $this->idMunicipio
    ]);
}

public static function actualizar(PDO $pdo, int $idRuta, array $datos): bool {
    $stmt = $pdo->prepare(
        'UPDATE ruta
         SET Recorrido = :recorrido,
             Horario_Inicio = :hora_inicio,
             Horario_Fin = :hora_fin,
             Frecuencia = :frecuencia,
             Distancia = :distancia,
             Estado = :estado,
             Id_Municipio = :id_municipio
         WHERE Id_Ruta = :id_ruta'
    );

    $stmt->execute([
        ':recorrido' => $datos['recorrido'],
        ':hora_inicio' => $datos['hora_inicio'],
        ':hora_fin' => $datos['hora_fin'],
        ':frecuencia' => $datos['frecuencia'],
        ':distancia' => $datos['distancia'],
        ':estado' => $datos['estado'],
        ':id_municipio' => $datos['id_municipio'],
        ':id_ruta' => $idRuta
    ]);

    return $stmt->rowCount() > 0;
}

public static function eliminar(PDO $pdo, int $idRuta): bool {
    $stmt = $pdo->prepare('DELETE FROM ruta WHERE Id_Ruta = :id_ruta');
    $stmt->execute([':id_ruta' => $idRuta]);

    return $stmt->rowCount() > 0;
}

}
?>