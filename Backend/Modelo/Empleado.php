<?php
class Empleado {
    private int $id;
    private string $cedula;
    private string $nombre;
    private string $apellido;
    private string $email;
    private string $password;
    private string $telefono;
    private string $rol;
    private string $turno;
    private bool $debeCambiarContrasena;

    public function __construct(int $id, string $cedula, string $nombre, string $apellido, string $email, string $telefono, string $rol, string $turno, string $area = '', bool $debeCambiarContrasena = false) {
        $this->id = $id;
        $this->cedula = $cedula;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->email = $email;
        $this->telefono = $telefono;
        $this->rol = $rol;
        $this->turno = $turno;
        $this->debeCambiarContrasena = $debeCambiarContrasena;
    }

    public function getId(): int { return $this->id; }
    public function getCedula(): string { return $this->cedula; }
    public function getNombre(): string { return $this->nombre; }
    public function getApellido(): string { return $this->apellido; }
    public function getEmail(): string { return $this->email; }
    public function getTelefono(): string { return $this->telefono; }
    public function getRol(): string { return $this->rol; }
    public function getTurno(): string { return $this->turno; }
    public function debeCambiarContrasena(): bool { return $this->debeCambiarContrasena; }

    public function registrarse(PDO $pdo, string $password, ?string $area = null, ?string $turno = null, ?int $idMunicipio = null, string $estadoCuenta = 'activa', ?int $idCentro = null): bool
    {
        try {
            $pdo->beginTransaction();

            $this->password = password_hash($password, PASSWORD_DEFAULT);
            $this->turno = $turno ?? ($this->turno !== '' ? $this->turno : 'mañana');

            $sql = 'INSERT INTO empleado (cedula, Id_Centro, Nombre, Apellido, Email, password, Rol, turno, Estado_Cuenta, Debe_Cambiar_Contrasena)
                    VALUES (:cedula, :id_centro, :nombre, :apellido, :email, :password, :rol, :turno, :estado_cuenta, 1)';
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':cedula' => $this->cedula,
                ':id_centro' => $idCentro,
                ':nombre' => $this->nombre,
                ':apellido' => $this->apellido,
                ':email' => $this->email,
                ':password' => $this->password,
                ':rol' => $this->rol,
                ':turno' => $this->turno,
                ':estado_cuenta' => $estadoCuenta
            ]);

            $empleadoId = (int) $pdo->lastInsertId();
            if ($this->telefono !== '') {
                $telefono = $pdo->prepare('INSERT INTO telefono (Id, Telefono) VALUES (:id, :telefono)');
                $telefono->execute([':id' => $empleadoId, ':telefono' => $this->telefono]);
            }

            $rolLimpio = strtolower(trim($this->rol));

            if ($rolLimpio === 'admi_municipal') {
                $sqlRol = "INSERT INTO admi_municipal (Id, Id_Municipio) VALUES (:id, :id_municipio)";
                $stmtRol = $pdo->prepare($sqlRol);
                $stmtRol->execute([
                    ':id' => $empleadoId,
                    ':id_municipio' => $idMunicipio
                ]);
            } elseif (in_array($rolLimpio, ['chofer', 'limpiador', 'operario'])) {
                if ($rolLimpio === 'operario') {
                    $sqlRol = "INSERT INTO operario (Id, Id_Centro) VALUES (:id, :id_centro)";
                    $stmtRol = $pdo->prepare($sqlRol);
                    $stmtRol->execute([
                        ':id' => $empleadoId,
                        ':id_centro' => $idCentro
                    ]);
                } else {
                    $sqlRol = "INSERT INTO {$rolLimpio} (Id) VALUES (:id)";
                    $stmtRol = $pdo->prepare($sqlRol);
                    $stmtRol->execute([':id' => $empleadoId]);
                }
            }

            $pdo->commit();
            return true;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function iniciarSesion(PDO $pdo, string $email, string $password): ?self
    {
        $stmt = $pdo->prepare('SELECT * FROM empleado WHERE Email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $datos = $stmt->fetch();

        if (!$datos || !password_verify($password, $datos['password'])) {
            return null;
        }
        if (strtolower((string) ($datos['Estado_Cuenta'] ?? 'activa')) !== 'activa') {
            return null;
        }

        return new self(
            (int) ($datos['Id'] ?? $datos['id'] ?? 0),
            (string) ($datos['cedula'] ?? $datos['Cedula'] ?? ''),
            (string) ($datos['Nombre'] ?? ''),
            (string) ($datos['Apellido'] ?? ''),
            (string) ($datos['Email'] ?? $email),
            (string) ($datos['Telefono'] ?? ''),
            (string) ($datos['rol'] ?? $datos['Rol'] ?? ''),
            (string) ($datos['turno'] ?? 'mañana'),
            '',
            (bool) ($datos['Debe_Cambiar_Contrasena'] ?? false)
        );
    }

    public static function listar(PDO $pdo, ?string $rol = null): array
    {
        if ($rol === null || trim($rol) === '' || strtolower($rol) === 'todos') {
            $stmt = $pdo->query(
                'SELECT e.Id, e.cedula, e.Id_Centro, e.Nombre, e.Apellido, e.Email, e.Rol, e.turno,
                        e.Estado_Cuenta, e.Debe_Cambiar_Contrasena,
                        c.Nombre AS Centro_Nombre, c.Tipo AS Centro_Tipo
                 FROM empleado e
                 LEFT JOIN centro_acopio c ON c.Id_Centro = e.Id_Centro'
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $pdo->prepare(
            'SELECT e.Id, e.cedula, e.Id_Centro, e.Nombre, e.Apellido, e.Email, e.Rol, e.turno,
                    e.Estado_Cuenta, e.Debe_Cambiar_Contrasena,
                    c.Nombre AS Centro_Nombre, c.Tipo AS Centro_Tipo
             FROM empleado e
             LEFT JOIN centro_acopio c ON c.Id_Centro = e.Id_Centro
             WHERE e.Rol = :rol'
        );
        $stmt->execute([':rol' => $rol]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function actualizar(PDO $pdo, int $id, array $datos): bool
    {
        $pdo->beginTransaction();

        try {
            $stmtActual = $pdo->prepare('SELECT Rol FROM empleado WHERE Id = :id');
            $stmtActual->execute([':id' => $id]);
            $rolActual = $stmtActual->fetchColumn();
            if ($rolActual === false) {
                $pdo->rollBack();
                return false;
            }

            $rolActual = strtolower(trim((string) $rolActual));
            $rolNuevo = strtolower(trim((string) ($datos['rol'] ?? $rolActual)));
            $valores = [
                ':nombre' => trim((string) ($datos['nombre'] ?? '')),
                ':apellido' => trim((string) ($datos['apellido'] ?? '')),
                ':email' => trim((string) ($datos['email'] ?? '')),
                ':turno' => trim((string) ($datos['turno'] ?? 'mañana')),
                ':estado_cuenta' => trim((string) ($datos['estado_cuenta'] ?? 'activa')),
                ':id_centro' => (int) ($datos['id_centro'] ?? 0),
                ':id' => $id
            ];

            $sql = 'UPDATE empleado SET Nombre = :nombre, Apellido = :apellido,
                    Email = :email, turno = :turno, Rol = :rol, Estado_Cuenta = :estado_cuenta, Id_Centro = :id_centro';
            $valores[':rol'] = $rolNuevo;

            if (!empty($datos['password'])) {
                $sql .= ', password = :password';
                $sql .= ', Debe_Cambiar_Contrasena = 1';
                $valores[':password'] = password_hash($datos['password'], PASSWORD_DEFAULT);
            }

            $stmt = $pdo->prepare($sql . ' WHERE Id = :id');
            $stmt->execute($valores);

            if ($rolActual !== $rolNuevo) {
                $tablasRol = [
                    'admi_municipal' => 'admi_municipal',
                    'chofer' => 'chofer',
                    'limpiador' => 'limpiador',
                    'operario' => 'operario'
                ];

                if (isset($tablasRol[$rolActual])) {
                    $eliminarRol = $pdo->prepare('DELETE FROM ' . $tablasRol[$rolActual] . ' WHERE Id = :id');
                    $eliminarRol->execute([':id' => $id]);
                }

                if (isset($tablasRol[$rolNuevo])) {
                    if ($rolNuevo === 'admi_municipal') {
                        $insertarRol = $pdo->prepare(
                            'INSERT INTO admi_municipal (Id, Id_Municipio) VALUES (:id, :id_municipio)'
                        );
                        $insertarRol->execute([
                            ':id' => $id,
                            ':id_municipio' => (int) ($datos['id_municipio'] ?? 0)
                        ]);
                    } else {
                        $insertarRol = $rolNuevo === 'operario'
                            ? $pdo->prepare('INSERT INTO operario (Id, Id_Centro) VALUES (:id, :id_centro)')
                            : $pdo->prepare('INSERT INTO ' . $tablasRol[$rolNuevo] . ' (Id) VALUES (:id)');
                        if ($rolNuevo === 'operario') {
                            $insertarRol->execute([
                                ':id' => $id,
                                ':id_centro' => (int) ($datos['id_centro'] ?? 0)
                            ]);
                        } else {
                            $insertarRol->execute([':id' => $id]);
                        }
                    }
                }
            } elseif ($rolNuevo === 'operario') {
                $actualizarOperario = $pdo->prepare(
                    'INSERT INTO operario (Id, Id_Centro) VALUES (:id, :id_centro)
                     ON DUPLICATE KEY UPDATE Id_Centro = VALUES(Id_Centro)'
                );
                $actualizarOperario->execute([
                    ':id' => $id,
                    ':id_centro' => (int) ($datos['id_centro'] ?? 0)
                ]);
            } elseif ($rolNuevo === 'admi_municipal') {
                $actualizarMunicipio = $pdo->prepare(
                    'INSERT INTO admi_municipal (Id, Id_Municipio) VALUES (:id, :id_municipio)
                     ON DUPLICATE KEY UPDATE Id_Municipio = VALUES(Id_Municipio)'
                );
                $actualizarMunicipio->execute([
                    ':id' => $id,
                    ':id_municipio' => (int) ($datos['id_municipio'] ?? 0)
                ]);
            }

            $pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function eliminar(PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare('DELETE FROM empleado WHERE Id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function cerrarSesion(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public static function existeCedula(PDO $pdo, string $cedula, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM empleado WHERE cedula = :cedula';
        $params = [':cedula' => $cedula];

        if ($excluirId !== null) {
            $sql .= ' AND Id != :id';
            $params[':id'] = $excluirId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function existeEmail(PDO $pdo, string $email, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM empleado WHERE Email = :email';
        $params = [':email' => $email];

        if ($excluirId !== null) {
            $sql .= ' AND Id != :id';
            $params[':id'] = $excluirId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function obtenerRol(PDO $pdo, int $id): ?string
    {
        $stmt = $pdo->prepare('SELECT Rol FROM empleado WHERE Id = :id');
        $stmt->execute([':id' => $id]);
        $rol = $stmt->fetchColumn();
        return $rol === false ? null : (string) $rol;
    }
}
?>