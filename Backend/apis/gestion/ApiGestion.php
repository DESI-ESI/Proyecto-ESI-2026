<?php

ini_set('display_errors', '0');
error_reporting(0);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();

require_once __DIR__ . '/../../config/Conexion.php';
require_once __DIR__ . '/../../config/Notificaciones.php';
require_once __DIR__ . '/../../Controlador/CamionesController.php';
require_once __DIR__ . '/../../Controlador/MantenimientoController.php';
require_once __DIR__ . '/../../Controlador/ContenedoresController.php';
require_once __DIR__ . '/../../Controlador/RutasController.php';
require_once __DIR__ . '/../../Controlador/CentroAcopioController.php';
require_once __DIR__ . '/../../Controlador/MaquinariaController.php';
require_once __DIR__ . '/../../Controlador/IncidenciaController.php';
require_once __DIR__ . '/../../Controlador/CuadrillaController.php';

$method = $_SERVER['REQUEST_METHOD'];
$jsonData = json_decode(file_get_contents('php://input'), true);
$data = is_array($jsonData) ? $jsonData : $_POST;

$pathInfo = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
$segments = $pathInfo === '' ? [] : explode('/', $pathInfo);
$resource = strtolower(trim((string) ($_GET['recurso'] ?? ($segments[0] ?? ''))));
$action = strtolower(trim((string) ($_GET['accion'] ?? '')));
$resourceId = $segments[1] ?? ($_GET['id'] ?? null);

if ($resource === 'sesion' && $method === 'GET') {
    if (!empty($_SESSION['empleado_id'])) {
        $stmt = $pdo->prepare('SELECT Rol, Nombre, Apellido FROM empleado WHERE Id = :id');
        $stmt->execute([':id' => $_SESSION['empleado_id']]);
        $empleadoActual = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($empleadoActual) {
            $_SESSION['rol'] = $empleadoActual['Rol'];
            if (!empty($empleadoActual['Nombre'])) {
                $_SESSION['nombre_empleado'] = $empleadoActual['Nombre'];
            }
        }
    }
    $rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
    respond([
        'status' => 'success',
        'authenticated' => !empty($_SESSION['empleado_id']),
        'admin' => esAdministrador(),
        'operario' => esOperario(),
        'cuadrilla' => esCuadrilla(),
        'rol' => $rol,
        'nombre' => $_SESSION['nombre_empleado'] ?? ''
    ]);
}

if ($resource === 'contrasena' && $method === 'POST') {
    if (!esEmpleado()) {
        responderCambioContrasena(['status' => 'error', 'message' => 'Sesión no iniciada.'], 401);
    }

    $contrasenaActual = $data['contrasena_actual'] ?? null;
    $contrasenaNueva = $data['contrasena_nueva'] ?? null;
    $confirmacion = $data['confirmacion'] ?? null;
    if (!is_string($contrasenaActual) || !is_string($contrasenaNueva) || !is_string($confirmacion)
        || $contrasenaActual === '' || $contrasenaNueva === '' || $confirmacion === '') {
        responderCambioContrasena(['status' => 'error', 'message' => 'Completa todos los campos de contraseña.'], 400);
    }

    if (strlen($contrasenaNueva) < 8 || !preg_match('/[A-Za-z]/', $contrasenaNueva) || !preg_match('/[0-9]/', $contrasenaNueva)) {
        responderCambioContrasena(['status' => 'error', 'message' => 'La nueva contraseña debe tener al menos 8 caracteres, una letra y un número.'], 400);
    }

    if ($contrasenaNueva !== $confirmacion) {
        responderCambioContrasena(['status' => 'error', 'message' => 'La confirmación no coincide con la nueva contraseña.'], 400);
    }

    try {
        $stmt = $pdo->prepare('SELECT password FROM empleado WHERE Id = :id LIMIT 1');
        $stmt->execute([':id' => (int) $_SESSION['empleado_id']]);
        $hashActual = $stmt->fetchColumn();

        if (!is_string($hashActual) || !password_verify($contrasenaActual, $hashActual)) {
            responderCambioContrasena(['status' => 'error', 'message' => 'La contraseña actual es incorrecta.'], 400);
        }

        if (password_verify($contrasenaNueva, $hashActual)) {
            responderCambioContrasena(['status' => 'error', 'message' => 'La nueva contraseña debe ser distinta de la actual.'], 400);
        }

        $hashNuevo = password_hash($contrasenaNueva, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE empleado SET password = :password WHERE Id = :id');
        $stmt->execute([
            ':password' => $hashNuevo,
            ':id' => (int) $_SESSION['empleado_id']
        ]);

        responderCambioContrasena(['status' => 'success', 'message' => 'La contraseña se actualizó correctamente.']);
    } catch (Throwable $error) {
        error_log('Error al cambiar la contraseña del empleado autenticado: ' . $error->getMessage());
        responderCambioContrasena(['status' => 'error', 'message' => 'No se pudo cambiar la contraseña. Inténtalo nuevamente.'], 500);
    }
}

if ($resource === 'micuadrilla' && $method === 'GET') {
    if (!esEmpleado()) {
        respond(['status' => 'error', 'message' => 'Sesión no iniciada.'], 401);
    }
    if (!esCuadrilla() && !esAdministrador()) {
        respond(['status' => 'error', 'message' => 'Acceso denegado: solo personal de cuadrilla (chofer o limpiador) o administradores pueden acceder a este recurso.'], 403);
    }

    $cuadrilla = cuadrilla::buscarDeEmpleado($pdo, (int) $_SESSION['empleado_id']);

    if ($cuadrilla) {
        $cuadrilla['Integrantes'] = cuadrilla::integrantes($pdo, (int) $cuadrilla['Id_Cuadrilla']);
    }

    respond(['status' => 'success', 'data' => $cuadrilla ?: null]);
}
if ($resource === 'miruta' && in_array($method, ['GET', 'POST'], true)) {
    if (!esEmpleado()) {
        respond(['status' => 'error', 'message' => 'Sesión no iniciada.'], 401);
    }
    if (!esCuadrilla() && !esAdministrador()) {
        respond(['status' => 'error', 'message' => 'Acceso denegado: solo personal de cuadrilla (chofer o limpiador) o administradores pueden acceder a este recurso.'], 403);
    }

    $fila = cuadrilla::buscarDeEmpleado($pdo, (int) $_SESSION['empleado_id']);

    if ($method === 'POST') {
        if (!in_array($action, ['completar', 'no_completado', 'reportar_contenedor'], true)) {
            respond(['status' => 'error', 'message' => 'Acción no válida.'], 400);
        }
        if (!$fila) {
            respond(['status' => 'error', 'message' => 'Tenés que pertenecer a una cuadrilla.'], 400);
        }
        if ($fila['Id_Ruta'] === null) {
            respond(['status' => 'error', 'message' => 'Tu cuadrilla no tiene una ruta asignada.'], 400);
        }

        if ($action === 'no_completado') {
            $motivo = trim((string) ($data['descripcion'] ?? ''));
            if ($motivo === '' || mb_strlen($motivo, 'UTF-8') > 1000) {
                respond(['status' => 'error', 'message' => 'Indicá el motivo (hasta 1000 caracteres).'], 400);
            }
            $repetido = $pdo->prepare('SELECT 1 FROM informe WHERE Id_Ruta = :ruta AND Id_Cuadrilla = :cuadrilla AND DATE(Fecha) = CURDATE() AND (Descripcion LIKE "Recorrido completado%" OR Descripcion LIKE "Recorrido NO completado%") LIMIT 1');
            $repetido->execute([':ruta' => $fila['Id_Ruta'], ':cuadrilla' => $fila['Id_Cuadrilla']]);
            if ($repetido->fetchColumn()) {
                respond(['status' => 'error', 'message' => 'El recorrido de hoy ya fue registrado.'], 409);
            }
            $pdo->prepare('INSERT INTO informe (Descripcion, Id_Ruta, Id_Cuadrilla) VALUES (:descripcion, :ruta, :cuadrilla)')
                ->execute([
                    ':descripcion' => sprintf('Recorrido NO completado: ruta #%d (%s), turno %s. Motivo: %s', $fila['Id_Ruta'], $fila['Recorrido'], $fila['Turno'], $motivo),
                    ':ruta' => $fila['Id_Ruta'],
                    ':cuadrilla' => $fila['Id_Cuadrilla'],
                ]);
            respond(['status' => 'success', 'message' => 'Se registró que el recorrido no se pudo completar.'], 201);
        }

        if ($action === 'reportar_contenedor') {
            $numero = (int) ($data['numero_contenedor'] ?? 0);
            $tipo = trim((string) ($data['tipo'] ?? ''));
            $descripcion = trim((string) ($data['descripcion'] ?? ''));
            if (!in_array($tipo, ['rotura', 'desborde', 'falta de recolección'], true) || $descripcion === '' || mb_strlen($descripcion, 'UTF-8') > 1000) {
                respond(['status' => 'error', 'message' => 'Elegí un tipo e indicá una descripción.'], 400);
            }
            $enRuta = $pdo->prepare('SELECT 1 FROM existe x JOIN contenedor c ON c.Id_Contenedor = x.Id_Contenedor WHERE x.Id_Ruta = :ruta AND c.Numero_Contenedor = :numero');
            $enRuta->execute([':ruta' => $fila['Id_Ruta'], ':numero' => $numero]);
            if (!$enRuta->fetchColumn()) {
                respond(['status' => 'error', 'message' => 'El contenedor no pertenece a tu ruta.'], 400);
            }
            $pdo->prepare('INSERT INTO incidencia (Tipo, Descripcion, Estado, Numero_Contenedor) VALUES (:tipo, :descripcion, "abierta", :numero)')
                ->execute([':tipo' => $tipo, ':descripcion' => $descripcion, ':numero' => $numero]);
            respond(['status' => 'success', 'message' => 'Incidencia reportada correctamente.'], 201);
        }

        $repetido = $pdo->prepare('SELECT 1 FROM informe WHERE Id_Ruta = :ruta AND Id_Cuadrilla = :cuadrilla AND DATE(Fecha) = CURDATE() AND (Descripcion LIKE "Recorrido completado%" OR Descripcion LIKE "Recorrido NO completado%") LIMIT 1');
        $repetido->execute([':ruta' => $fila['Id_Ruta'], ':cuadrilla' => $fila['Id_Cuadrilla']]);
        if ($repetido->fetchColumn()) {
            respond(['status' => 'error', 'message' => 'El recorrido de hoy ya fue marcado como completado.'], 409);
        }

        $cantidad = $pdo->prepare('SELECT COUNT(*) FROM existe WHERE Id_Ruta = :ruta');
        $cantidad->execute([':ruta' => $fila['Id_Ruta']]);
        $descripcion = sprintf(
            'Recorrido completado: ruta #%d (%s), turno %s, %d contenedores, horario %s a %s.',
            $fila['Id_Ruta'],
            $fila['Recorrido'],
            $fila['Turno'],
            (int) $cantidad->fetchColumn(),
            substr((string) $fila['Horario_Inicio'], 0, 5),
            substr((string) $fila['Horario_Fin'], 0, 5)
        );

        $pdo->prepare('INSERT INTO informe (Descripcion, Id_Ruta, Id_Cuadrilla) VALUES (:descripcion, :ruta, :cuadrilla)')
            ->execute([
                ':descripcion' => $descripcion,
                ':ruta' => $fila['Id_Ruta'],
                ':cuadrilla' => $fila['Id_Cuadrilla'],
            ]);
        respond(['status' => 'success', 'message' => 'Recorrido completado. Se generó el informe automáticamente.'], 201);
    }
    if ($fila) {
        $respuesta['cuadrilla'] = ['Id_Cuadrilla' => $fila['Id_Cuadrilla'], 'Turno' => $fila['Turno']];
        if ($fila['Id_Ruta'] !== null) {
            $respuesta['ruta'] = array_diff_key($fila, ['Id_Cuadrilla' => 1, 'Turno' => 1]);
            $contenedores = $pdo->prepare(
                'SELECT c.Numero_Contenedor, c.Ubicacion, c.Tipo_Residuo, c.Estado
                 FROM existe x JOIN contenedor c ON c.Id_Contenedor = x.Id_Contenedor
                 WHERE x.Id_Ruta = :ruta ORDER BY c.Numero_Contenedor'
            );
            $contenedores->execute([':ruta' => $fila['Id_Ruta']]);
            $respuesta['contenedores'] = $contenedores->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    respond(['status' => 'success', 'data' => $respuesta]);
}

if ($resource === 'notificaciones') {
    $panelNotificaciones = strtolower(trim((string) ($_GET['panel'] ?? '')));
    if (!in_array($panelNotificaciones, ['admin', 'empleado'], true)) {
        respond(['status' => 'error', 'message' => 'Panel no válido.'], 400);
    }
    if (!esEmpleado() || ($panelNotificaciones === 'admin' && !esAdministrador())) {
        respond(['status' => 'error', 'message' => 'No tienes permisos para ver estas notificaciones.'], 403);
    }

    $idUsuario = (int) $_SESSION['empleado_id'];
    if ($method === 'POST' && $action === 'leer') {
        marcarNotificacionesLeidas($pdo, $panelNotificaciones, $idUsuario);
        respond(['status' => 'success']);
    }
    if ($method === 'GET') {
        respond(['status' => 'success', 'data' => listarNotificaciones($pdo, $panelNotificaciones, $idUsuario)]);
    }
    respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);
}

if ($resource === 'informes') {
    if (!esEmpleado()) {
        respond(['status' => 'error', 'message' => 'Sesión no iniciada.'], 401);
    }

    try {
        $contexto = cuadrilla::buscarDeEmpleado($pdo, (int) $_SESSION['empleado_id']);

        if ($method === 'GET') {
            $informes = [];
            $consulta = $pdo->query(
                'SELECT i.Id_Informe, i.Fecha, i.Descripcion, i.Id_Ruta, i.Id_Cuadrilla,
                        CONCAT("Cuadrilla #", i.Id_Cuadrilla) AS Autor
                 FROM informe i
                 ORDER BY i.Fecha DESC, i.Id_Informe DESC LIMIT 200'
            );
            $informes = $consulta->fetchAll(PDO::FETCH_ASSOC);
            respond(['status' => 'success', 'data' => ['contexto' => $contexto ?: null, 'informes' => $informes, 'es_admin' => esAdministrador()]]);
        }

        if ($method === 'POST') {
            if (!$contexto) {
                respond(['status' => 'error', 'message' => 'Tenés que pertenecer a una cuadrilla para crear informes.'], 400);
            }
            if ($contexto['Id_Ruta'] === null) {
                respond(['status' => 'error', 'message' => 'Tu cuadrilla no tiene una ruta asignada.'], 400);
            }
            $descripcion = trim((string) ($data['descripcion'] ?? ''));
            if ($descripcion === '' || mb_strlen($descripcion, 'UTF-8') > 2000) {
                respond(['status' => 'error', 'message' => 'Escribí una descripción de hasta 2000 caracteres.'], 400);
            }
            $pdo->prepare('INSERT INTO informe (Descripcion, Id_Ruta, Id_Cuadrilla) VALUES (:descripcion, :ruta, :cuadrilla)')
                ->execute([
                    ':descripcion' => $descripcion,
                    ':ruta' => $contexto['Id_Ruta'],
                    ':cuadrilla' => $contexto['Id_Cuadrilla']
                ]);
            respond(['status' => 'success', 'message' => 'Informe registrado correctamente.'], 201);
        }

        if (in_array($method, ['PUT', 'DELETE'], true)) {
            $idInforme = (int) ($_GET['id'] ?? 0);
            $stmtInforme = $pdo->prepare('SELECT Id_Cuadrilla FROM informe WHERE Id_Informe = :id');
            $stmtInforme->execute([':id' => $idInforme]);
            $cuadrillaInforme = $stmtInforme->fetchColumn();
            if ($cuadrillaInforme === false) {
                respond(['status' => 'error', 'message' => 'No se encontró el informe.'], 404);
            }
            if (!esAdministrador() && (!$contexto || (int) $contexto['Id_Cuadrilla'] !== (int) $cuadrillaInforme)) {
                respond(['status' => 'error', 'message' => 'Solo podés modificar los informes de tu cuadrilla.'], 403);
            }

            if ($method === 'DELETE') {
                $pdo->prepare('DELETE FROM informe WHERE Id_Informe = :id')->execute([':id' => $idInforme]);
                respond(['status' => 'success', 'message' => 'Informe eliminado correctamente.']);
            }

            $descripcion = trim((string) ($data['descripcion'] ?? ''));
            if ($descripcion === '' || mb_strlen($descripcion, 'UTF-8') > 2000) {
                respond(['status' => 'error', 'message' => 'Escribí una descripción de hasta 2000 caracteres.'], 400);
            }
            $pdo->prepare('UPDATE informe SET Descripcion = :descripcion WHERE Id_Informe = :id')
                ->execute([':descripcion' => $descripcion, ':id' => $idInforme]);
            respond(['status' => 'success', 'message' => 'Informe actualizado correctamente.']);
        }

        respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);
    } catch (Throwable $error) {
        respond(['status' => 'error', 'message' => 'Error interno del servidor.'], 500);
    }
}

if ($resource === 'operario') {
    if (!esOperario() && !esAdministrador()) {
        respond(['status' => 'error', 'message' => 'Solo los operarios pueden acceder a este recurso.'], 403);
    }

    try {
        if ($method === 'GET') {
            $centros = $pdo->query(
                'SELECT Id_Centro, Nombre, Tipo, Ubicacion, Capacidad, Cantidad_Almacenada, Estado, Tipos_Residuo,
                        (Capacidad - COALESCE(Cantidad_Almacenada, 0)) AS Disponible
                 FROM centro_acopio ORDER BY Nombre'
            )->fetchAll(PDO::FETCH_ASSOC);
            $ingresos = $pdo->query(
                'SELECT i.Id_Ingreso, i.Id_Centro, c.Nombre AS Centro, i.Cantidad, i.Tipo_Residuo, i.Fecha,
                        CONCAT(e.Nombre, " ", e.Apellido) AS Operario
                 FROM ingreso_acopio i
                 JOIN centro_acopio c ON c.Id_Centro = i.Id_Centro
                 JOIN empleado e ON e.Id = i.Id_Empleado
                 ORDER BY i.Fecha DESC, i.Id_Ingreso DESC LIMIT 100'
            )->fetchAll(PDO::FETCH_ASSOC);
            respond(['status' => 'success', 'data' => ['centros' => $centros, 'ingresos' => $ingresos]]);
        }

        if ($method === 'POST') {
            $idCentro = (int) ($data['id_centro'] ?? 0);
            $cantidad = (float) ($data['cantidad'] ?? 0);
            $tipoResiduo = trim((string) ($data['tipo_residuo'] ?? ''));
            if ($idCentro <= 0 || $cantidad <= 0 || mb_strlen($tipoResiduo, 'UTF-8') > 50) {
                respond(['status' => 'error', 'message' => 'Indicá una instalación y una cantidad mayor a cero.'], 400);
            }

            $pdo->beginTransaction();
            $consulta = $pdo->prepare('SELECT Capacidad, Cantidad_Almacenada, Estado, Tipos_Residuo FROM centro_acopio WHERE Id_Centro = :id FOR UPDATE');
            $consulta->execute([':id' => $idCentro]);
            $centro = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$centro) {
                $pdo->rollBack();
                respond(['status' => 'error', 'message' => 'La instalación no existe.'], 404);
            }
            if ($centro['Estado'] !== 'activo') {
                $pdo->rollBack();
                respond(['status' => 'error', 'message' => 'La instalación no está activa.'], 400);
            }
            if ($tipoResiduo !== '' && trim((string) $centro['Tipos_Residuo']) !== '') {
                $admitidos = array_map(fn($t) => mb_strtolower(trim($t), 'UTF-8'), explode(',', $centro['Tipos_Residuo']));
                if (!in_array(mb_strtolower($tipoResiduo, 'UTF-8'), $admitidos, true)) {
                    $pdo->rollBack();
                    respond(['status' => 'error', 'message' => 'La instalación no recibe ese tipo de residuo.'], 400);
                }
            }
            $disponible = (float) $centro['Capacidad'] - (float) $centro['Cantidad_Almacenada'];
            if ($cantidad > $disponible) {
                $pdo->rollBack();
                respond(['status' => 'error', 'message' => 'La cantidad supera la capacidad disponible (' . number_format($disponible, 2, '.', '') . ').'], 400);
            }

            $pdo->prepare('INSERT INTO ingreso_acopio (Id_Centro, Id_Empleado, Cantidad, Tipo_Residuo) VALUES (:centro, :empleado, :cantidad, :tipo)')
                ->execute([
                    ':centro' => $idCentro,
                    ':empleado' => $_SESSION['empleado_id'],
                    ':cantidad' => $cantidad,
                    ':tipo' => $tipoResiduo === '' ? null : $tipoResiduo
                ]);
            $pdo->prepare('UPDATE centro_acopio SET Cantidad_Almacenada = COALESCE(Cantidad_Almacenada, 0) + :cantidad WHERE Id_Centro = :id')
                ->execute([':cantidad' => $cantidad, ':id' => $idCentro]);
            $pdo->commit();
            respond(['status' => 'success', 'message' => 'Ingreso registrado correctamente.'], 201);
        }

        if ($method === 'PUT') {
            $idIngreso = (int) ($data['id'] ?? $resourceId ?? 0);
            $idCentro = (int) ($data['id_centro'] ?? 0);
            $cantidad = (float) ($data['cantidad'] ?? 0);
            $tipoResiduo = trim((string) ($data['tipo_residuo'] ?? ''));
            if ($idIngreso <= 0 || $idCentro <= 0 || $cantidad <= 0 || mb_strlen($tipoResiduo, 'UTF-8') > 50) {
                respond(['status' => 'error', 'message' => 'Indicá una instalación y una cantidad mayor a cero.'], 400);
            }

            $pdo->beginTransaction();
            $consulta = $pdo->prepare('SELECT Id_Centro, Cantidad FROM ingreso_acopio WHERE Id_Ingreso = :id FOR UPDATE');
            $consulta->execute([':id' => $idIngreso]);
            $anterior = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$anterior) {
                $pdo->rollBack();
                respond(['status' => 'error', 'message' => 'No se encontró el ingreso.'], 404);
            }

            $pdo->prepare('UPDATE centro_acopio SET Cantidad_Almacenada = GREATEST(COALESCE(Cantidad_Almacenada, 0) - :cantidad, 0) WHERE Id_Centro = :id')
                ->execute([':cantidad' => $anterior['Cantidad'], ':id' => $anterior['Id_Centro']]);

            $consulta = $pdo->prepare('SELECT Capacidad, Cantidad_Almacenada, Estado, Tipos_Residuo FROM centro_acopio WHERE Id_Centro = :id FOR UPDATE');
            $consulta->execute([':id' => $idCentro]);
            $centro = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$centro) {
                $pdo->rollBack();
                respond(['status' => 'error', 'message' => 'La instalación no existe.'], 404);
            }
            if ($centro['Estado'] !== 'activo') {
                $pdo->rollBack();
                respond(['status' => 'error', 'message' => 'La instalación no está activa.'], 400);
            }
            if ($tipoResiduo !== '' && trim((string) $centro['Tipos_Residuo']) !== '') {
                $admitidos = array_map(fn($t) => mb_strtolower(trim($t), 'UTF-8'), explode(',', $centro['Tipos_Residuo']));
                if (!in_array(mb_strtolower($tipoResiduo, 'UTF-8'), $admitidos, true)) {
                    $pdo->rollBack();
                    respond(['status' => 'error', 'message' => 'La instalación no recibe ese tipo de residuo.'], 400);
                }
            }
            $disponible = (float) $centro['Capacidad'] - (float) $centro['Cantidad_Almacenada'];
            if ($cantidad > $disponible) {
                $pdo->rollBack();
                respond(['status' => 'error', 'message' => 'La cantidad supera la capacidad disponible (' . number_format($disponible, 2, '.', '') . ').'], 400);
            }

            $pdo->prepare('UPDATE centro_acopio SET Cantidad_Almacenada = COALESCE(Cantidad_Almacenada, 0) + :cantidad WHERE Id_Centro = :id')
                ->execute([':cantidad' => $cantidad, ':id' => $idCentro]);
            $pdo->prepare('UPDATE ingreso_acopio SET Id_Centro = :centro, Cantidad = :cantidad, Tipo_Residuo = :tipo WHERE Id_Ingreso = :id')
                ->execute([':centro' => $idCentro, ':cantidad' => $cantidad, ':tipo' => $tipoResiduo === '' ? null : $tipoResiduo, ':id' => $idIngreso]);
            $pdo->commit();
            respond(['status' => 'success', 'message' => 'Ingreso actualizado correctamente.']);
        }

        if ($method === 'DELETE') {
            $idIngreso = (int) ($data['id'] ?? $resourceId ?? 0);
            if ($idIngreso <= 0) {
                respond(['status' => 'error', 'message' => 'Ingreso inválido.'], 400);
            }

            $pdo->beginTransaction();
            $consulta = $pdo->prepare('SELECT Id_Centro, Cantidad FROM ingreso_acopio WHERE Id_Ingreso = :id FOR UPDATE');
            $consulta->execute([':id' => $idIngreso]);
            $ingreso = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$ingreso) {
                $pdo->rollBack();
                respond(['status' => 'error', 'message' => 'No se encontró el ingreso.'], 404);
            }
            $pdo->prepare('UPDATE centro_acopio SET Cantidad_Almacenada = GREATEST(COALESCE(Cantidad_Almacenada, 0) - :cantidad, 0) WHERE Id_Centro = :id')
                ->execute([':cantidad' => $ingreso['Cantidad'], ':id' => $ingreso['Id_Centro']]);
            $pdo->prepare('DELETE FROM ingreso_acopio WHERE Id_Ingreso = :id')->execute([':id' => $idIngreso]);
            $pdo->commit();
            respond(['status' => 'success', 'message' => 'Ingreso eliminado correctamente.']);
        }
        respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        respond(['status' => 'error', 'message' => 'Error interno del servidor.'], 500);
    }
}
$recursosEmpleado = ['incidencias', 'contenedores', 'municipios', 'centros', 'centros_acopio'];
if (!esAdministrador() && !(esEmpleado() && in_array($resource, $recursosEmpleado, true))) {
    respond(['status' => 'error', 'message' => 'No tienes permisos para acceder a este recurso.'], 403);
}

try {
    switch ($resource) {
        case 'camiones':
            $controller = new CamionesController();

            if ($method === 'GET') {
                respond(['status' => 'success', 'data' => $controller->listar($pdo)]);
            }

            if ($method === 'POST' && $action === 'editar') {
                $updated = $controller->editar($pdo, $data);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Camión actualizado correctamente.' : 'No se encontró el camión.'], $updated ? 200 : 404);
            }

            if ($method === 'POST' && $action === 'eliminar') {
                $data['matricula'] = $data['matricula'] ?? $resourceId;
                $deleted = $controller->eliminar($pdo, $data);
                respond(['status' => $deleted ? 'success' : 'error', 'message' => $deleted ? 'Camión eliminado correctamente.' : 'No se encontró el camión.'], $deleted ? 200 : 404);
            }

            if ($method === 'POST') {
                $created = $controller->crear($pdo, $data);
                respond(['status' => $created ? 'success' : 'error', 'message' => $created ? 'Camión creado correctamente.' : 'No se pudo crear el camión.'], $created ? 201 : 400);
            }

            if ($method === 'PUT') {
                $data['matricula'] = $data['matricula'] ?? $resourceId;
                $updated = $controller->editar($pdo, $data);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Camión actualizado correctamente.' : 'No se encontró el camión.'], $updated ? 200 : 404);
            }

            if ($method === 'DELETE') {
                $data['matricula'] = $data['matricula'] ?? $resourceId;
                $deleted = $controller->eliminar($pdo, $data);
                respond(['status' => $deleted ? 'success' : 'error', 'message' => $deleted ? 'Camión eliminado correctamente.' : 'No se encontró el camión.'], $deleted ? 200 : 404);
            }

            respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);

        case 'estadisticas':
            if ($method !== 'GET') {
                respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);
            }

            $mes = (string) ($_GET['mes'] ?? date('Y-m'));
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
                throw new InvalidArgumentException('El mes no es válido.');
            }
            $desde = $mes . '-01 00:00:00';
            $hasta = date('Y-m-d H:i:s', strtotime($desde . ' +1 month'));

            $conteo = fn(string $sql) => (int) $pdo->query($sql)->fetchColumn();
            $totales = [
                'contenedores' => $conteo('SELECT COUNT(*) FROM contenedor'),
                'camiones' => $conteo('SELECT COUNT(*) FROM camion'),
                'empleados' => $conteo('SELECT COUNT(*) FROM empleado'),
                'rutas' => $conteo('SELECT COUNT(*) FROM ruta'),
                'incidencias_abiertas' => $conteo("SELECT COUNT(*) FROM incidencia WHERE Estado <> 'resuelta'"),
                'incidencias_cerradas' => $conteo("SELECT COUNT(*) FROM incidencia WHERE Estado = 'resuelta'"),
            ];

            $incidencias = $pdo->query(
                'SELECT i.Id_Incidencia, i.Tipo, i.Descripcion, i.Fecha, i.Estado, i.Numero_Contenedor, c.Ubicacion
                 FROM incidencia i LEFT JOIN contenedor c ON c.Numero_Contenedor = i.Numero_Contenedor
                 ORDER BY i.Fecha DESC, i.Id_Incidencia DESC'
            )->fetchAll(PDO::FETCH_ASSOC);

            $top = $pdo->prepare(
                "SELECT i.Numero_Contenedor, c.Ubicacion, COUNT(*) AS Cantidad
                 FROM incidencia i JOIN contenedor c ON c.Numero_Contenedor = i.Numero_Contenedor
                 WHERE i.Tipo = 'desborde' AND i.Fecha >= :desde AND i.Fecha < :hasta
                 GROUP BY i.Numero_Contenedor, c.Ubicacion
                 ORDER BY Cantidad DESC, i.Numero_Contenedor ASC LIMIT 10"
            );
            $top->execute([':desde' => $desde, ':hasta' => $hasta]);

            $porTipo = $pdo->prepare(
                'SELECT Tipo, COUNT(*) AS Cantidad FROM incidencia
                 WHERE Fecha >= :desde AND Fecha < :hasta GROUP BY Tipo ORDER BY Cantidad DESC'
            );
            $porTipo->execute([':desde' => $desde, ':hasta' => $hasta]);

            respond(['status' => 'success', 'data' => [
                'mes' => $mes,
                'totales' => $totales,
                'incidencias' => $incidencias,
                'top_desbordados' => $top->fetchAll(PDO::FETCH_ASSOC),
                'incidencias_por_tipo' => $porTipo->fetchAll(PDO::FETCH_ASSOC),
            ]]);

        case 'mantenimientos':
            $controller = new MantenimientoController();

            if ($method === 'GET') {
                respond(['status' => 'success', 'data' => $controller->listar($pdo, (int) ($_GET['id_camion'] ?? 0))]);
            }

            if ($method === 'POST') {
                $created = $controller->crear($pdo, $data);
                respond(['status' => $created ? 'success' : 'error', 'message' => $created ? 'Mantenimiento registrado correctamente.' : 'No se pudo registrar el mantenimiento.'], $created ? 201 : 400);
            }

            if ($method === 'PUT') {
                $updated = $controller->actualizar($pdo, (int) ($data['id'] ?? $resourceId ?? 0), $data);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Mantenimiento actualizado correctamente.' : 'No se encontró el mantenimiento.'], $updated ? 200 : 404);
            }

            if ($method === 'DELETE') {
                $deleted = $controller->eliminar($pdo, (int) ($data['id'] ?? $resourceId ?? 0));
                respond(['status' => $deleted ? 'success' : 'error', 'message' => $deleted ? 'Mantenimiento eliminado correctamente.' : 'No se encontró el mantenimiento.'], $deleted ? 200 : 404);
            }

            respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);

        case 'contenedores':
            $controller = new ContenedoresController();

            if ($method === 'GET') {
                respond(['status' => 'success', 'data' => $controller->listar($pdo)]);
            }

            if ($method === 'POST' && $action === 'editar') {
                $containerId = (int) ($data['id'] ?? $data['Id_Contenedor'] ?? $resourceId ?? 0);
                if ($containerId <= 0) {
                    respond(['status' => 'error', 'message' => 'ID de contenedor obligatorio.'], 400);
                }

                $updateData = [
                    'tipo_residuo' => trim((string) ($data['tipo_residuo'] ?? '')),
                    'capacidad' => (float) ($data['capacidad'] ?? 0),
                    'ubicacion' => trim((string) ($data['ubicacion'] ?? '')),
                    'estado' => trim((string) ($data['estado'] ?? '')),
                    'id_municipio' => (int) ($data['id_municipio'] ?? 0),
                    'id_incidencia' => isset($data['id_incidencia']) ? (int) $data['id_incidencia'] : null,
                    'numero_contenedor' => numeroContenedor($pdo, $data, $containerId)
                ];

                if ($updateData['tipo_residuo'] === '' || $updateData['ubicacion'] === '' || $updateData['estado'] === '' || $updateData['capacidad'] <= 0 || $updateData['id_municipio'] <= 0) {
                    respond(['status' => 'error', 'message' => 'Datos de contenedor inválidos.'], 400);
                }

                $updated = $controller->actualizar($pdo, $containerId, $updateData);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Contenedor actualizado correctamente.' : 'No se encontró el contenedor.'], $updated ? 200 : 404);
            }

            if ($method === 'POST' && $action === 'eliminar') {
                $containerId = (int) ($data['id'] ?? $data['Id_Contenedor'] ?? $resourceId ?? 0);
                if ($containerId <= 0) {
                    respond(['status' => 'error', 'message' => 'ID de contenedor obligatorio.'], 400);
                }

                $controller->darBaja($pdo, $containerId);
                respond(['status' => 'success', 'message' => 'Contenedor eliminado correctamente.']);
            }

            if ($method === 'POST') {
                $municipioId = (int) ($data['id_municipio'] ?? 0);
                $container = new Contenedores(
                    0,
                    trim((string) ($data['tipo_residuo'] ?? '')),
                    trim((string) ($data['ubicacion'] ?? '')),
                    (float) ($data['capacidad'] ?? 0),
                    trim((string) ($data['estado'] ?? '')),
                    (bool) ($data['en_reserva'] ?? false)
                );

                validateContainer($container, $municipioId);
                $created = $controller->registrar($pdo, $container, $municipioId, numeroContenedor($pdo, $data, 0));
                respond(['status' => $created ? 'success' : 'error', 'message' => $created ? 'Contenedor creado correctamente.' : 'No se pudo crear el contenedor.'], $created ? 201 : 400);
            }

            if ($method === 'PUT') {
                $containerId = (int) ($data['id'] ?? $resourceId ?? 0);
                if ($containerId <= 0) {
                    respond(['status' => 'error', 'message' => 'ID de contenedor obligatorio.'], 400);
                }

                $updateData = [
                    'tipo_residuo' => trim((string) ($data['tipo_residuo'] ?? '')),
                    'capacidad' => (float) ($data['capacidad'] ?? 0),
                    'ubicacion' => trim((string) ($data['ubicacion'] ?? '')),
                    'estado' => trim((string) ($data['estado'] ?? '')),
                    'id_municipio' => (int) ($data['id_municipio'] ?? 0),
                    'id_incidencia' => isset($data['id_incidencia']) ? (int) $data['id_incidencia'] : null,
                    'numero_contenedor' => numeroContenedor($pdo, $data, $containerId)
                ];

                if ($updateData['tipo_residuo'] === '' || $updateData['ubicacion'] === '' || $updateData['estado'] === '' || $updateData['capacidad'] <= 0 || $updateData['id_municipio'] <= 0) {
                    respond(['status' => 'error', 'message' => 'Datos de contenedor inválidos.'], 400);
                }

                $updated = $controller->actualizar($pdo, $containerId, $updateData);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Contenedor actualizado correctamente.' : 'No se encontró el contenedor.'], $updated ? 200 : 404);
            }

            if ($method === 'DELETE') {
                $containerId = (int) ($data['id'] ?? $resourceId ?? 0);
                if ($containerId <= 0) {
                    respond(['status' => 'error', 'message' => 'ID de contenedor obligatorio.'], 400);
                }

                $controller->darBaja($pdo, $containerId);
                respond(['status' => 'success', 'message' => 'Contenedor eliminado correctamente.']);
            }

            respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);

        case 'municipios':
            if ($method !== 'GET') {
                respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);
            }

            $statement = $pdo->query('SELECT Id_Municipio, Nombre, Departamento, Cant_Poblacion, Estado FROM municipio ORDER BY Nombre');
            respond(['status' => 'success', 'data' => $statement->fetchAll(PDO::FETCH_ASSOC)]);

        case 'centros':
        case 'centros_acopio':
            $controller = new CentroAcopioController();

            if ($method === 'GET') {
                respond(['status' => 'success', 'data' => $controller->listar($pdo)]);
            }

            if ($method === 'POST') {
                $created = $controller->crear($pdo, $data);
                respond(['status' => $created ? 'success' : 'error', 'message' => $created ? 'Centro de acopio creado correctamente.' : 'No se pudo crear el centro.'], $created ? 201 : 400);
            }

            if ($method === 'PUT') {
                $updated = $controller->actualizar($pdo, (int) ($data['id'] ?? $resourceId ?? 0), $data);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Centro de acopio actualizado correctamente.' : 'No se encontró el centro.'], $updated ? 200 : 404);
            }

            if ($method === 'DELETE') {
                $deleted = $controller->eliminar($pdo, (int) ($data['id'] ?? $resourceId ?? 0));
                respond(['status' => $deleted ? 'success' : 'error', 'message' => $deleted ? 'Centro de acopio eliminado correctamente.' : 'No se encontró el centro.'], $deleted ? 200 : 404);
            }

            respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);

        case 'maquinaria':
            $controller = new MaquinariaController();

            if ($method === 'GET') {
                respond(['status' => 'success', 'data' => $controller->listar($pdo)]);
            }

            if ($method === 'POST') {
                $created = $controller->crear($pdo, $data);
                respond(['status' => $created ? 'success' : 'error', 'message' => $created ? 'Maquinaria creada correctamente.' : 'No se pudo crear la maquinaria.'], $created ? 201 : 400);
            }

            if ($method === 'PUT') {
                $updated = $controller->actualizar($pdo, (int) ($data['id'] ?? $resourceId ?? 0), $data);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Maquinaria actualizada correctamente.' : 'No se encontró la maquinaria.'], $updated ? 200 : 404);
            }

            if ($method === 'DELETE') {
                $deleted = $controller->eliminar($pdo, (int) ($data['id'] ?? $resourceId ?? 0));
                respond(['status' => $deleted ? 'success' : 'error', 'message' => $deleted ? 'Maquinaria eliminada correctamente.' : 'No se encontró la maquinaria.'], $deleted ? 200 : 404);
            }

            respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);

        case 'incidencias':
            $controller = new IncidenciaController();

            if ($method === 'GET') {
                respond(['status' => 'success', 'data' => $controller->listar($pdo)]);
            }

            if ($method === 'POST') {
                $created = $controller->crear($pdo, $data);
                respond(['status' => $created ? 'success' : 'error', 'message' => $created ? 'Incidencia creada correctamente.' : 'No se pudo crear la incidencia.'], $created ? 201 : 400);
            }

            if ($method === 'PUT') {
                $id = (int) ($data['id'] ?? $resourceId ?? 0);
                $updated = $controller->actualizar($pdo, $id, $data);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Incidencia actualizada correctamente.' : 'No se encontró la incidencia o no hubo cambios.'], $updated ? 200 : 404);
            }

            if ($method === 'DELETE') {
                $id = (int) ($data['id'] ?? $resourceId ?? 0);
                $deleted = $controller->eliminar($pdo, $id);
                respond(['status' => $deleted ? 'success' : 'error', 'message' => $deleted ? 'Incidencia eliminada correctamente.' : 'No se encontró la incidencia.'], $deleted ? 200 : 404);
            }

            respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);

        case 'cuadrillas':
            $controller = new CuadrillaController();

            if ($method === 'GET') {
                respond(['status' => 'success', 'data' => $controller->listar($pdo)]);
            }

            if ($method === 'POST') {
                $created = $controller->crear($pdo, $data);
                respond(['status' => $created ? 'success' : 'error', 'message' => $created ? 'Cuadrilla creada correctamente.' : 'No se pudo crear la cuadrilla.'], $created ? 201 : 400);
            }

            if ($method === 'PUT') {
                $id = (int) ($data['id'] ?? $resourceId ?? 0);
                $updated = $controller->actualizar($pdo, $id, $data);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Cuadrilla actualizada correctamente.' : 'No se encontró la cuadrilla o no hubo cambios.'], $updated ? 200 : 404);
            }

            if ($method === 'DELETE') {
                $id = (int) ($data['id'] ?? $resourceId ?? 0);
                $deleted = $controller->eliminar($pdo, $id);
                respond(['status' => $deleted ? 'success' : 'error', 'message' => $deleted ? 'Cuadrilla eliminada correctamente.' : 'No se encontró la cuadrilla.'], $deleted ? 200 : 404);
            }

            respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);

        case 'rutas':
            $controller = new RutasController();

            if ($method === 'GET') {
                respond(['status' => 'success', 'data' => $controller->listar($pdo)]);
            }

            if ($method === 'POST' && $action === 'editar') {
                $idRuta = (int) ($data['Id_Ruta'] ?? $data['id_ruta'] ?? $data['id'] ?? 0);
                $updated = $controller->editar($pdo, $idRuta, $data);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Ruta actualizada correctamente.' : 'No se encontró la ruta.'], $updated ? 200 : 404);
            }

            if ($method === 'POST' && $action === 'eliminar') {
                $idRuta = (int) ($data['Id_Ruta'] ?? $data['id_ruta'] ?? $data['id'] ?? 0);
                $deleted = $controller->eliminar($pdo, $idRuta);
                respond(['status' => $deleted ? 'success' : 'error', 'message' => $deleted ? 'Ruta eliminada correctamente.' : 'No se encontró la ruta.'], $deleted ? 200 : 404);
            }

            if ($method === 'POST') {
                $created = $controller->crear($pdo, $data);
                respond(['status' => $created ? 'success' : 'error', 'message' => $created ? 'Ruta creada correctamente.' : 'No se pudo crear la ruta.'], $created ? 201 : 400);
            }

            respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);

        default:
            respond(['status' => 'error', 'message' => 'Recurso no válido.'], 404);
    }
} catch (InvalidArgumentException $exception) {
    respond(['status' => 'error', 'message' => $exception->getMessage()], 400);
} catch (PDOException $exception) {
    respond(['status' => 'error', 'message' => 'Error de base de datos.'], 500);
} catch (Throwable $exception) {
    respond(['status' => 'error', 'message' => 'Error interno del servidor.'], 500);
}

function numeroContenedor(PDO $pdo, array $data, int $idActual): int
{
    $numero = (int) ($data['numero_contenedor'] ?? 0);

    if ($numero <= 0) {
        if ($idActual > 0) {
            $stmt = $pdo->prepare('SELECT Numero_Contenedor FROM contenedor WHERE Id_Contenedor = :id');
            $stmt->execute([':id' => $idActual]);
            $actual = (int) $stmt->fetchColumn();
            if ($actual > 0) {
                return $actual;
            }
        }
        return (int) $pdo->query('SELECT COALESCE(MAX(Numero_Contenedor), 0) + 1 FROM contenedor')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM contenedor WHERE Numero_Contenedor = :numero AND Id_Contenedor <> :id');
    $stmt->execute([':numero' => $numero, ':id' => $idActual]);
    if ((int) $stmt->fetchColumn() > 0) {
        throw new InvalidArgumentException('El número de contenedor ' . $numero . ' ya está en uso.');
    }

    return $numero;
}

function validateContainer(Contenedores $container, int $municipioId): void
{
    if ($municipioId <= 0 || $container->getTipoResiduo() === '' || $container->getUbicacion() === '' || $container->getCapacidad() <= 0 || $container->getEstado() === '') {
        throw new InvalidArgumentException('Datos de contenedor inválidos.');
    }
}

function respond(array $payload, int $statusCode = 200): void
{
    global $pdo, $resource, $method, $action, $data;

    if ($statusCode < 400 && ($payload['status'] ?? '') === 'success' && isset($pdo, $resource, $method)) {
        try {
            registrarNotificacion($pdo, $resource, $method, $action ?? '', is_array($data ?? null) ? $data : []);
        } catch (Throwable $error) {
            error_log('No se pudo registrar una notificación de ' . $resource . ': ' . $error->getMessage());
        }
    }

    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function responderCambioContrasena(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function esAdministrador(): bool
{
    $rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
    return !empty($_SESSION['empleado_id']) && $rol === 'admi_municipal';
}

function esOperario(): bool
{
    return !empty($_SESSION['empleado_id']) && strtolower(trim((string) ($_SESSION['rol'] ?? ''))) === 'operario';
}

function esCuadrilla(): bool
{
    $rol = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
    return !empty($_SESSION['empleado_id']) && in_array($rol, ['chofer', 'limpiador'], true);
}

function esEmpleado(): bool
{
    return !empty($_SESSION['empleado_id']);
}
