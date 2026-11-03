<?php
require_once __DIR__ . '/config/Conexion.php';
session_start();

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

$identificador = trim((string) ($_POST['identificador'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($identificador === '' || $password === '') {
    echo json_encode(['status' => 'error', 'message' => 'Por favor, completá todos los campos.']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT * FROM empleado WHERE cedula = :identificador OR Email = :email LIMIT 1"
);
$stmt->execute([
    ':identificador' => $identificador,
    ':email' => $identificador
]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Cédula o contraseña incorrectos.']);
    exit;
}

$estadoCuenta = strtolower((string) ($user['Estado_Cuenta'] ?? 'activa'));
if ($estadoCuenta !== 'activa') {
    echo json_encode(['status' => 'error', 'message' => 'La cuenta está inactiva. Contactá al administrador.']);
    exit;
}

$hash = $user['password'] ?? $user['Password'] ?? null;
if (!$hash || !password_verify($password, $hash)) {
    echo json_encode(['status' => 'error', 'message' => 'Cédula o contraseña incorrectos.']);
    exit;
}

session_regenerate_id(true);
$_SESSION['empleado_id'] = $user['Id'] ?? $user['id'] ?? null;
$_SESSION['nombre_empleado'] = $user['Nombre'] ?? $user['nombre'] ?? 'Empleado';
$_SESSION['rol'] = $user['Rol'] ?? $user['rol'] ?? 'empleado';
$_SESSION['debe_cambiar_contrasena'] = (bool) ($user['Debe_Cambiar_Contrasena'] ?? false);

echo json_encode([
    'status' => 'success',
    'message' => '¡Inicio de sesión exitoso!',
    'nombre' => $_SESSION['nombre_empleado'],
    'must_change_password' => $_SESSION['debe_cambiar_contrasena']
]);
?>