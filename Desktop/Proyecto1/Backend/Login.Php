<?php
session_start();

$archivo = "usuarios.json";

$correo = ($_POST["correo"] ?? "");
$password = ($_POST["password"] ?? "");

if (!file_exists($archivo)) {
    die("No existen usuarios registrados.");
}

$usuarios = json_decode(file_get_contents($archivo), true);

foreach ($usuarios as $u) {

    if ($u["correo"] === $correo) {

        if (password_verify($password, $u["password"])) {

            $_SESSION["nombre"] = $u["nombre"];
            $_SESSION["correo"] = $u["correo"];

            header("Location: index.html");
            exit;
        } else {
            die("Contraseña incorrecta.");
        }

    }

}
die("El correo no está registrado.");

?>