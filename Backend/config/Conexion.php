<?php
$hostConfigurado = getenv('DB_HOST');
$hosts = $hostConfigurado
    ? [$hostConfigurado]
    : ['127.0.0.1', 'localhost', '192.168.56.12'];

$db = 'sigeru';
$userConfigurado = getenv('DB_USER');
$passConfigurada = getenv('DB_PASS');
$credenciales = $userConfigurado !== false
    ? [[$userConfigurado, $passConfigurada !== false ? $passConfigurada : '']]
    : [['sigeru_user', 'Sigeru2026_DB!'], ['root', '']];
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

foreach ($hosts as $host) {
    foreach ($credenciales as [$user, $pass]) {
        try {
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, $options);
            $conexion = $pdo;
            break 2;
        } catch (PDOException $e) {
            $conexion = null;
        }
    }
}

if (!$conexion) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de conexión a la base de datos'
    ]);
    exit;
}
?>