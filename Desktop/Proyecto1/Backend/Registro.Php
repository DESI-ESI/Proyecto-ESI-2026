<?php

$archivo = "Usuarios.json";

if (!file_exists($archivo)) {
    file_put_contents($archivo, json_encode([], JSON_PRETTY_PRINT));
}

$usuarios = json_decode(file_get_contents($archivo), true);

$nombre = ($_POST["nombre"] ?? "");
$apellido = ($_POST["apellido"] ?? "");
$correo = ($_POST["correo"] ?? "");
$password = ($_POST["password"] ?? "");
$confirmPassword = ($_POST["confirm_password"] ?? "");


if (
    empty($nombre) ||
    empty($apellido) ||
    empty($correo) ||
    empty($password) ||
    empty($confirmPassword)
) {
    die("Todos los campos son obligatorios.");
}


if ($password !== $confirmPassword) {
    die("Las contraseñas no coinciden.");
}


foreach ($usuarios as $u) {

    if ($u["correo"] === $correo) {
        die("El correo ya está registrado.");
    }

}

$nuevoUsuario = [
    "id" => count($usuarios) + 1,
    "nombre" => $nombre,
    "apellido" => $apellido,
    "correo" => $correo,
    "password" => password_hash($password, PASSWORD_DEFAULT)
];


$usuarios[] = $nuevoUsuario;

file_put_contents($archivo, json_encode($usuarios, JSON_PRETTY_PRINT));


header("Location: login.html");
exit;

?>