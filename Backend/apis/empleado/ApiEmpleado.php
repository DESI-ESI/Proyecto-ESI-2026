<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/Conexion.php';
require_once __DIR__ . '/../../config/Notificaciones.php';
require_once __DIR__ . '/../../Controlador/EmpleadoController.php';

$metodo = $_SERVER['REQUEST_METHOD'];

$rawInput = file_get_contents('php://input');
$datos = json_decode($rawInput, true);
$datos = is_array($datos) ? $datos : $_POST;

$accion = strtolower(trim((string) ($_GET['accion'] ?? $datos['accion'] ?? '')));

if (!in_array($accion, ['login', 'registro'], true) && !empleadoEsAdministrador()) {
    responderEmpleados(['status' => 'error', 'message' => 'Se requiere rol administrador.'], 403);
}

$pathInfo = $_SERVER['PATH_INFO'] ?? '';
$segmentos = explode('/', trim($pathInfo, '/'));
$idUrl = isset($segmentos[0]) && is_numeric($segmentos[0]) ? (int) $segmentos[0] : null;

$id = $datos['id'] ?? $_GET['id'] ?? $idUrl;

try {
    $controller = new EmpleadoController();

    switch ($metodo) {
        case 'GET':
            if ($accion === 'listar' || $accion === '') {
                $rol = $_GET['rol'] ?? null;
                responderEmpleados(['status' => 'success', 'data' => $controller->listar($pdo, $rol)]);
            } else {
                responderEmpleados(['status' => 'error', 'message' => 'Acción GET no válida'], 404);
            }
            break;

        case 'POST':
            if ($accion === 'crear') {
                $resultado = $controller->crear($pdo, $datos);
                responderEmpleados($resultado);
            } elseif ($accion === 'editar') {
                if (!$id) {
                    responderEmpleados(['status' => 'error', 'message' => 'ID de empleado no proporcionado para actualizar'], 400);
                }
                $resultado = $controller->actualizar($pdo, (int)$id, $datos);
                if ($resultado) {
                    responderEmpleados(['status' => 'success', 'message' => 'Empleado actualizado correctamente']);
                } else {
                    responderEmpleados(['status' => 'error', 'message' => 'No se pudo actualizar el empleado o no hubo cambios'], 400);
                }
            } elseif ($accion === 'login') {
                responderEmpleados($controller->iniciarSesion($pdo, $datos));
            } elseif ($accion === 'registro') {
                $datos['rol'] = 'empleado';
                responderEmpleados($controller->registrarse($pdo, $datos));
            } elseif ($accion === 'logout') {
                $controller->cerrarSesion();
                responderEmpleados(['status' => 'success', 'message' => 'Sesión cerrada']);
            } elseif ($accion === 'eliminar') {
                if (!$id) {
                    responderEmpleados(['status' => 'error', 'message' => 'ID de empleado no proporcionado para eliminar'], 400);
                }
                $resultado = $controller->eliminar($pdo, (int)$id);
                if ($resultado) {
                    responderEmpleados(['status' => 'success', 'message' => 'Empleado eliminado con éxito']);
                } else {
                    responderEmpleados(['status' => 'error', 'message' => 'No se encontró el empleado a eliminar'], 404);
                }
            } else {
                responderEmpleados(['status' => 'error', 'message' => 'Acción POST no válida (Recibido: "' . $accion . '")'], 405);
            }
            break;

        case 'PUT':
            if (!$id) {
                responderEmpleados(['status' => 'error', 'message' => 'ID de empleado no proporcionado para actualizar'], 400);
            }
            $resultado = $controller->actualizar($pdo, (int)$id, $datos);
            if ($resultado) {
                responderEmpleados(['status' => 'success', 'message' => 'Empleado actualizado correctamente']);
            } else {
                responderEmpleados(['status' => 'error', 'message' => 'No se pudo actualizar el empleado o no hubo cambios'], 400);
            }
            break;

        case 'DELETE':
            if (!$id) {
                responderEmpleados(['status' => 'error', 'message' => 'ID de empleado no proporcionado para eliminar'], 400);
            }
            $resultado = $controller->eliminar($pdo, (int)$id);
            if ($resultado) {
                responderEmpleados(['status' => 'success', 'message' => 'Empleado eliminado con éxito']);
            } else {
                responderEmpleados(['status' => 'error', 'message' => 'No se encontró el empleado a eliminar'], 404);
            }
            break;

        default:
            responderEmpleados(['status' => 'error', 'message' => 'Método no permitido'], 405);
    }
} catch (PDOException $e) {
    responderEmpleados([
        'status' => 'error', 
        'message' => 'Error PDO (SQL): ' . $e->getMessage(),
        'codigo' => $e->getCode()
    ], 500);
} catch (Throwable $e) {
    responderEmpleados([
        'status' => 'error', 
        'message' => 'Error General: ' . $e->getMessage(),
        'archivo' => $e->getFile(),
        'linea' => $e->getLine()
    ], 500);
}

function responderEmpleados(array $respuesta, int $codigo = 200): void
{
    global $pdo, $metodo, $accion, $datos;

    $exito = $codigo < 400 && ($respuesta['status'] ?? '') === 'success';
    $modifica = in_array($accion, ['crear', 'editar', 'eliminar'], true) || in_array($metodo, ['PUT', 'DELETE'], true);
    if ($exito && $modifica) {
        try {
            $_GET['panel'] = 'admin';
            registrarNotificacion($pdo, 'empleados', $metodo, $accion, is_array($datos ?? null) ? $datos : []);
        } catch (Throwable $e) {
            error_log('No se pudo registrar una notificación de empleados: ' . $e->getMessage());
        }
    }

    http_response_code($codigo);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

function empleadoEsAdministrador(): bool
{
    $rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
    return !empty($_SESSION['empleado_id']) && $rol === 'admi_municipal';
}