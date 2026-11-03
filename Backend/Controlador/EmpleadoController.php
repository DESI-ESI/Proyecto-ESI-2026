<?php

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../Modelo/Empleado.php';

class EmpleadoController
{
    public function listar(PDO $pdo, ?string $rol = null): array
    {
        return Empleado::listar($pdo, $rol);
    }

    public function actualizar(PDO $pdo, int $id, array $datos): bool
    {
        $cedula = trim((string) ($datos['cedula'] ?? ''));

        if ($cedula !== '' && Empleado::existeCedula($pdo, $cedula, $id)) {
            throw new Exception('Ya existe otro empleado con esta cédula.');
        }

        $rolActual = Empleado::obtenerRol($pdo, $id);
        if ($rolActual === null) {
            return false;
        }

        $rolActual = strtolower(trim($rolActual));
        $rol = strtolower(trim((string) ($datos['rol'] ?? $rolActual)));
        if (!in_array($rol, ['empleado', 'admi_municipal', 'chofer', 'limpiador', 'operario'], true)) {
            throw new InvalidArgumentException('El rol seleccionado no es válido.');
        }

        $email = filter_var(trim((string) ($datos['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        if ($email === false || strlen($email) > 30) {
            throw new InvalidArgumentException('El email no es válido o supera los 30 caracteres.');
        }

        if (Empleado::existeEmail($pdo, $email, $id)) {
            throw new InvalidArgumentException('Ya existe otro empleado con este email.');
        }

        if ($rol === 'admi_municipal' && (int) ($datos['id_municipio'] ?? 0) <= 0) {
            throw new InvalidArgumentException('Debes seleccionar un municipio para el administrador municipal.');
        }
        $estadoCuenta = strtolower(trim((string) ($datos['estado_cuenta'] ?? 'activa')));
        if (!in_array($estadoCuenta, ['activa', 'inactiva'], true)) {
            throw new InvalidArgumentException('El estado de cuenta no es válido.');
        }

        $datos['rol'] = $rol;
        $datos['estado_cuenta'] = $estadoCuenta;
        $datos['turno'] = $rol === 'admi_municipal'
            ? 'mañana'
            : trim((string) ($datos['turno'] ?? ''));

        return Empleado::actualizar($pdo, $id, $datos);
    }

    public function crear(PDO $pdo, array $datos): array
    {
        return $this->registrarse($pdo, $datos);
    }

    public function eliminar(PDO $pdo, int $id): bool
    {
        return Empleado::eliminar($pdo, $id);
    }

    public function registrarse(PDO $pdo, array $datos): array
        {
            $cedula = trim((string) ($datos['cedula'] ?? ''));
            $nombre = trim((string) ($datos['nombre'] ?? ''));
            $apellido = trim((string) ($datos['apellido'] ?? ''));
            $email = filter_var(trim((string) ($datos['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $password = (string) ($datos['password'] ?? '');
            $confirmacion = (string) ($datos['confirm_password'] ?? $password);
            $rol = strtolower(trim((string) ($datos['rol'] ?? 'empleado')));
            $telefono = trim((string) ($datos['telefono'] ?? ''));
            $estadoCuenta = strtolower(trim((string) ($datos['estado_cuenta'] ?? 'activa')));
            $turnoIngresado = strtolower(trim((string) ($datos['turno'] ?? '')));
            $turnosPermitidos = [
                'mañana' => 'Manana',
                'manana' => 'Manana',
                'tarde' => 'Tarde',
                'noche' => 'Noche'
            ];
            $turno = $turnosPermitidos[$turnoIngresado] ?? '';
            $idMunicipio = !empty($datos['id_municipio']) ? (int)$datos['id_municipio'] : null;

            if (!preg_match('/^[0-9]{7,8}$/', $cedula)
                || !preg_match('/^.{1,30}$/us', $nombre)
                || !preg_match('/^.{1,30}$/us', $apellido)
                || $email === false
                || strlen($email) > 30
                || strlen($telefono) > 20
                || $password === '') {
                return ['status' => 'error', 'message' => 'Todos los campos obligatorios deben estar completos.'];
            }
            if (!in_array($estadoCuenta, ['activa', 'inactiva'], true)) {
                return ['status' => 'error', 'message' => 'El estado de la cuenta no es válido.'];
            }
            if (array_key_exists('telefono', $datos) && ($telefono === '' || !preg_match('/^[0-9+() -]{7,20}$/', $telefono))) {
                return ['status' => 'error', 'message' => 'Ingresá un teléfono válido.'];
            }

            if (!in_array($rol, ['empleado', 'admi_municipal', 'chofer', 'limpiador', 'operario'], true)) {
                return ['status' => 'error', 'message' => 'El rol seleccionado no puede gestionarse desde este formulario.'];
            }

            if ($rol === 'admi_municipal' && $idMunicipio === null) {
                return ['status' => 'error', 'message' => 'Debes seleccionar un municipio.'];
            }

            if ($idMunicipio !== null) {
                $municipio = $pdo->prepare('SELECT 1 FROM municipio WHERE Id_Municipio = :id');
                $municipio->execute([':id' => $idMunicipio]);
                if (!$municipio->fetchColumn()) {
                    return ['status' => 'error', 'message' => 'El municipio seleccionado no existe.'];
                }
            }

            if (!preg_match('/^[\p{L} ]+$/u', $nombre) || !preg_match('/^[\p{L} ]+$/u', $apellido)) {
                return ['status' => 'error', 'message' => 'El nombre y el apellido solo pueden contener letras.'];
            }

            if ($turno === '' && $rol === 'empleado') {
                $turno = 'Manana';
            }
            if ($turno === '') {
                return ['status' => 'error', 'message' => 'Debes seleccionar un turno válido.'];
            }

            if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                return ['status' => 'error', 'message' => 'La contraseña debe tener al menos 8 caracteres, una letra y un número.'];
            }

            if ($password !== $confirmacion) {
                return ['status' => 'error', 'message' => 'Las contraseñas no coinciden.'];
            }

            if (Empleado::existeCedula($pdo, $cedula)) {
                return ['status' => 'error', 'message' => 'Ya existe un empleado registrado con esta cédula.'];
            }

            if (Empleado::existeEmail($pdo, $email)) {
                return ['status' => 'error', 'message' => 'Ya existe un empleado registrado con este email.'];
            }

            try {
                $empleado = new Empleado(0, $cedula, $nombre, $apellido, $email, $telefono, $rol, $turno);
                $exito = $empleado->registrarse($pdo, $password, null, $turno, $idMunicipio, $estadoCuenta);

                if ($exito) {
                    return ['status' => 'success', 'message' => '¡Empleado registrado correctamente en el sistema y en su tabla de rol!'];
                }

                return ['status' => 'error', 'message' => 'No se pudo completar el registro.'];

            } catch (PDOException $e) {
                return [
                    'status' => 'error', 
                    'message' => 'Error SQL: ' . $e->getMessage()
                ];
            }
}

    public function iniciarSesion(PDO $pdo, array $datos): array
    {
        $email = trim((string) ($datos['email'] ?? ''));
        $password = (string) ($datos['password'] ?? '');

        if ($email === '' || $password === '') {
            return ['status' => 'error', 'message' => 'Por favor, completa todos los campos.'];
        }

        $empleado = Empleado::iniciarSesion($pdo, $email, $password);
        if ($empleado === null) {
            return ['status' => 'error', 'message' => 'Email o contraseña incorrectos.'];
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(true);
        $_SESSION['empleado_id'] = $empleado->getId();
        $_SESSION['nombre_empleado'] = $empleado->getNombre();
        $_SESSION['rol'] = $empleado->getRol();
        $_SESSION['debe_cambiar_contrasena'] = $empleado->debeCambiarContrasena();

        return [
            'status' => 'success',
            'message' => '¡Inicio de sesión exitoso!',
            'must_change_password' => $empleado->debeCambiarContrasena()
        ];
    }

    public function cerrarSesion(): void
    {
        Empleado::cerrarSesion();
    }
}
?>