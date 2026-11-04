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
$sesionUsuarioActiva = false;
$sesionDebeCambiarContrasena = false;
if (!empty($_SESSION['empleado_id'])) {
    $estadoSesion = $pdo->prepare('SELECT Estado_Cuenta, Debe_Cambiar_Contrasena FROM empleado WHERE Id = :id LIMIT 1');
    $estadoSesion->execute([':id' => (int) $_SESSION['empleado_id']]);
    $datosSesion = $estadoSesion->fetch(PDO::FETCH_ASSOC);
    $sesionUsuarioActiva = $datosSesion && strtolower((string) $datosSesion['Estado_Cuenta']) === 'activa';
    $sesionDebeCambiarContrasena = $sesionUsuarioActiva && (bool) $datosSesion['Debe_Cambiar_Contrasena'];
    if (!$sesionUsuarioActiva) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}

$pathInfo = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
$segments = $pathInfo === '' ? [] : explode('/', $pathInfo);
$resource = strtolower(trim((string) ($_GET['recurso'] ?? ($segments[0] ?? ''))));
$action = strtolower(trim((string) ($_GET['accion'] ?? '')));
$resourceId = $segments[1] ?? ($_GET['id'] ?? null);

if ($sesionDebeCambiarContrasena && !($resource === 'sesion' && $method === 'GET')
    && !($resource === 'contrasena' && $method === 'POST')) {
    respond(['status' => 'error', 'message' => 'Debes cambiar tu contraseña antes de continuar.'], 403);
}

if ($resource === 'sesion' && $method === 'GET') {
    if (!empty($_SESSION['empleado_id'])) {
        $stmt = $pdo->prepare('SELECT rol AS Rol, Nombre, Apellido, Debe_Cambiar_Contrasena FROM empleado WHERE Id = :id');
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
        'nombre' => $_SESSION['nombre_empleado'] ?? '',
        'must_change_password' => $sesionDebeCambiarContrasena
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
        $stmt = $pdo->prepare('UPDATE empleado SET password = :password, Debe_Cambiar_Contrasena = 0 WHERE Id = :id');
        $stmt->execute([
            ':password' => $hashNuevo,
            ':id' => (int) $_SESSION['empleado_id']
        ]);
        $_SESSION['debe_cambiar_contrasena'] = false;

        responderCambioContrasena([
            'status' => 'success',
            'message' => 'La contraseña se actualizó correctamente.',
            'must_change_password' => false
        ]);
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
            $incidenciaAdicional = $data['incidencia'] ?? null;
            if ($incidenciaAdicional !== null) {
                if (!is_array($incidenciaAdicional)) {
                    respond(['status' => 'error', 'message' => 'Los datos del inconveniente son inválidos.'], 400);
                }
                $numeroIncidencia = (int) ($incidenciaAdicional['numero_contenedor'] ?? 0);
                $tipoIncidencia = trim((string) ($incidenciaAdicional['tipo'] ?? ''));
                $descripcionIncidencia = trim((string) ($incidenciaAdicional['descripcion'] ?? ''));
                if ($numeroIncidencia <= 0 || !in_array($tipoIncidencia, ['rotura', 'desborde', 'falta de recolección'], true)
                    || $descripcionIncidencia === '' || mb_strlen($descripcionIncidencia, 'UTF-8') > 1000) {
                    respond(['status' => 'error', 'message' => 'Para agregar un inconveniente, indicá un contenedor, un tipo de problema y una descripción válida.'], 400);
                }
                $enRuta = $pdo->prepare(
                    'SELECT 1 FROM existe x JOIN contenedor c ON c.Id_Contenedor = x.Id_Contenedor
                     WHERE x.Id_Ruta = :ruta AND c.Numero_Contenedor = :numero'
                );
                $enRuta->execute([':ruta' => $fila['Id_Ruta'], ':numero' => $numeroIncidencia]);
                if (!$enRuta->fetchColumn()) {
                    respond(['status' => 'error', 'message' => 'El contenedor seleccionado no pertenece a tu ruta.'], 400);
                }
                $incidenciaAdicional = [
                    'numero_contenedor' => $numeroIncidencia,
                    'tipo' => $tipoIncidencia,
                    'descripcion' => $descripcionIncidencia,
                ];
            }
            $descripcion = sprintf('Recorrido NO completado: ruta #%d (%s), turno %s. Motivo: %s', $fila['Id_Ruta'], $fila['Recorrido'], $fila['Turno'], $motivo);
            $resultado = registrarResultadoRecorrido($pdo, $fila, $descripcion, $incidenciaAdicional);
            if ($resultado === 'repetido') {
                respond(['status' => 'error', 'message' => 'El recorrido de hoy ya fue registrado.'], 409);
            }
            if ($resultado !== 'registrado') {
                respond(['status' => 'error', 'message' => 'La ruta ya no está asignada a tu cuadrilla.'], 409);
            }
            $mensaje = 'Se registró que el recorrido no se pudo completar, se resolvieron sus incidencias y la ruta dejó de estar asignada a tu cuadrilla.';
            if ($incidenciaAdicional !== null) {
                $mensaje .= ' También se registró el nuevo inconveniente.';
            }
            respond(['status' => 'success', 'message' => $mensaje], 201);
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
            $pdo->prepare('INSERT INTO incidencia (Tipo, Descripcion, Estado, Numero_Contenedor, Id_Cuadrilla) VALUES (:tipo, :descripcion, "abierta", :numero, :cuadrilla)')
                ->execute([
                    ':tipo' => $tipo,
                    ':descripcion' => $descripcion,
                    ':numero' => $numero,
                    ':cuadrilla' => $fila['Id_Cuadrilla']
                ]);
            respond(['status' => 'success', 'message' => 'Incidencia reportada correctamente.'], 201);
        }

        $volumen = filter_var($data['volumen'] ?? null, FILTER_VALIDATE_FLOAT);
        $tipoResiduo = trim((string) ($data['tipo_residuo'] ?? ''));
        if ($volumen === false || $volumen <= 0 || !is_finite((float) $volumen) || $volumen > 99999999.99
            || mb_strlen($tipoResiduo, 'UTF-8') > 50) {
            respond(['status' => 'error', 'message' => 'Ingresá un volumen mayor a cero y un tipo de residuo válido.'], 400);
        }

        $cantidad = $pdo->prepare('SELECT COUNT(*) FROM existe WHERE Id_Ruta = :ruta');
        $cantidad->execute([':ruta' => $fila['Id_Ruta']]);
        $descripcion = sprintf(
            'Recorrido completado: ruta #%d (%s), turno %s, %d contenedores, horario %s a %s. Volumen recolectado: %s kg.',
            $fila['Id_Ruta'],
            $fila['Recorrido'],
            $fila['Turno'],
            (int) $cantidad->fetchColumn(),
            substr((string) $fila['Horario_Inicio'], 0, 5),
            substr((string) $fila['Horario_Fin'], 0, 5),
            number_format((float) $volumen, 2, '.', '')
        );

        $resultado = registrarResultadoRecorrido($pdo, $fila, $descripcion, null, (float) $volumen, $tipoResiduo);
        if ($resultado === 'repetido') {
            respond(['status' => 'error', 'message' => 'El recorrido de hoy ya fue marcado como completado.'], 409);
        }
        if ($resultado !== 'registrado') {
            respond(['status' => 'error', 'message' => 'La ruta ya no está asignada a tu cuadrilla.'], 409);
        }
        respond(['status' => 'success', 'message' => 'Recorrido completado. Se generó el informe, se resolvieron sus incidencias y la ruta dejó de estar asignada a tu cuadrilla.'], 201);
    }
    $respuesta = [
        'cuadrilla' => null,
        'ruta' => null,
        'contenedores' => [],
        'incidencias' => []
    ];
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

            $incidencias = $pdo->prepare(
                "SELECT i.Id_Incidencia, i.Tipo, i.Descripcion, i.Fecha, i.Estado, i.Zona, i.Numero_Contenedor, i.Id_Cuadrilla
                 FROM incidencia i
                 WHERE i.Estado IN ('abierta', 'en curso')
                   AND (i.Id_Cuadrilla IS NULL OR i.Id_Cuadrilla = :cuadrilla)
                   AND EXISTS (
                       SELECT 1
                       FROM existe x
                       JOIN contenedor c ON c.Id_Contenedor = x.Id_Contenedor
                       WHERE x.Id_Ruta = :ruta
                         AND c.Numero_Contenedor = i.Numero_Contenedor
                   )
                 ORDER BY FIELD(i.Estado, 'en curso', 'abierta'), i.Fecha DESC, i.Id_Incidencia DESC"
            );
            $incidencias->execute([
                ':ruta' => $fila['Id_Ruta'],
                ':cuadrilla' => $fila['Id_Cuadrilla']
            ]);
            $respuesta['incidencias'] = $incidencias->fetchAll(PDO::FETCH_ASSOC);
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
                LEFT JOIN empleado e ON e.Id = i.Id_Empleado
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
if ($resource === 'reporte_recolecciones') {
    if (!esAdministrador()) {
        respond(['status' => 'error', 'message' => 'Solo administración puede consultar este reporte.'], 403);
    }
    if ($method !== 'GET') {
        respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);
    }

    try {
    $desde = trim((string) ($_GET['desde'] ?? ''));
    $hasta = trim((string) ($_GET['hasta'] ?? ''));
    $ruta = (int) ($_GET['ruta'] ?? 0);
    $cuadrilla = (int) ($_GET['cuadrilla'] ?? 0);
    $municipio = (int) ($_GET['municipio'] ?? 0);
    $tipo = trim((string) ($_GET['tipo'] ?? ''));
    foreach (['desde' => $desde, 'hasta' => $hasta] as $campo => $fecha) {
        $fechaValida = $fecha === '' ? true
            : preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) === 1
                && DateTime::createFromFormat('!Y-m-d', $fecha) !== false
                && DateTime::createFromFormat('!Y-m-d', $fecha)->format('Y-m-d') === $fecha;
        if (!$fechaValida) {
            respond(['status' => 'error', 'message' => "La fecha $campo no es válida."], 400);
        }
    }
    if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
        respond(['status' => 'error', 'message' => 'La fecha inicial no puede ser posterior a la fecha final.'], 400);
    }

    $condiciones = ["r.Estado_Recoleccion = 'completada'"];
    $parametros = [];
    if ($desde !== '') {
        $condiciones[] = 'r.Fecha >= :desde';
        $parametros[':desde'] = $desde . ' 00:00:00';
    }
    if ($hasta !== '') {
        $condiciones[] = 'r.Fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)';
        $parametros[':hasta'] = $hasta . ' 00:00:00';
    }
    if ($ruta > 0) {
        $condiciones[] = 'r.Id_Ruta = :ruta';
        $parametros[':ruta'] = $ruta;
    }
    if ($cuadrilla > 0) {
        $condiciones[] = 'r.Id_Cuadrilla = :cuadrilla';
        $parametros[':cuadrilla'] = $cuadrilla;
    }
    if ($municipio > 0) {
        $condiciones[] = 'rt.Id_Municipio = :municipio';
        $parametros[':municipio'] = $municipio;
    }
    if ($tipo !== '') {
        $condiciones[] = 'r.Tipo_Residuo = :tipo';
        $parametros[':tipo'] = $tipo;
    }

    $sql = 'SELECT r.Id_Recoleccion, r.Fecha, r.Volumen, r.Tipo_Residuo, r.Id_Ruta, rt.Recorrido,
                   rt.Id_Municipio, m.Nombre AS Municipio, r.Id_Cuadrilla
            FROM recoleccion r
            JOIN ruta rt ON rt.Id_Ruta = r.Id_Ruta
            JOIN municipio m ON m.Id_Municipio = rt.Id_Municipio
            WHERE ' . implode(' AND ', $condiciones) . '
            ORDER BY r.Fecha DESC, r.Id_Recoleccion DESC';
    $consulta = $pdo->prepare($sql);
    $consulta->execute($parametros);
    $registros = $consulta->fetchAll(PDO::FETCH_ASSOC);
    $volumenTotal = array_sum(array_map(static fn(array $registro): float => (float) $registro['Volumen'], $registros));

    $opciones = [
        'rutas' => $pdo->query('SELECT Id_Ruta, Recorrido FROM ruta ORDER BY Id_Ruta DESC')->fetchAll(PDO::FETCH_ASSOC),
        'cuadrillas' => $pdo->query('SELECT Id_Cuadrilla, Turno FROM cuadrilla ORDER BY Id_Cuadrilla')->fetchAll(PDO::FETCH_ASSOC),
        'municipios' => $pdo->query('SELECT Id_Municipio, Nombre FROM municipio ORDER BY Nombre')->fetchAll(PDO::FETCH_ASSOC),
        'tipos' => $pdo->query("SELECT DISTINCT Tipo_Residuo FROM recoleccion WHERE Tipo_Residuo IS NOT NULL AND Tipo_Residuo <> '' ORDER BY Tipo_Residuo")->fetchAll(PDO::FETCH_COLUMN),
    ];
    respond(['status' => 'success', 'data' => [
        'desde' => $desde,
        'hasta' => $hasta,
        'volumen_total' => $volumenTotal,
        'recolecciones' => $registros,
        'opciones' => $opciones,
    ]]);
    } catch (Throwable $error) {
        error_log('Error al generar el reporte de recolecciones: ' . $error->getMessage());
        respond(['status' => 'error', 'message' => 'No se pudo generar el reporte de recolecciones.'], 500);
    }
}

if (in_array($resource, ['reporte_incidencias', 'zonas_criticas', 'tiempos_resolucion', 'frecuencia_recoleccion', 'dashboard'], true)) {
    if (!esAdministrador()) {
        respond(['status' => 'error', 'message' => 'Solo administración puede consultar estos reportes.'], 403);
    }
    if ($method !== 'GET') {
        respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);
    }

    $desde = trim((string) ($_GET['desde'] ?? ''));
    $hasta = trim((string) ($_GET['hasta'] ?? ''));
    foreach (['desde' => $desde, 'hasta' => $hasta] as $campo => $fecha) {
        if ($fecha !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)
            || DateTime::createFromFormat('!Y-m-d', $fecha) === false
            || DateTime::createFromFormat('!Y-m-d', $fecha)->format('Y-m-d') !== $fecha)) {
            respond(['status' => 'error', 'message' => "La fecha $campo no es válida."], 400);
        }
    }
    if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
        respond(['status' => 'error', 'message' => 'La fecha inicial no puede ser posterior a la fecha final.'], 400);
    }
    $periodoIncidencias = [];
    $parametrosPeriodo = [];
    if ($desde !== '') {
        $periodoIncidencias[] = 'i.Fecha >= :desde';
        $parametrosPeriodo[':desde'] = $desde . ' 00:00:00';
    }
    if ($hasta !== '') {
        $periodoIncidencias[] = 'i.Fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)';
        $parametrosPeriodo[':hasta'] = $hasta . ' 00:00:00';
    }

    try {
        if ($resource === 'reporte_incidencias') {
            $estado = (string) ($_GET['estado'] ?? 'abiertas');
            $estadosValidos = ['abiertas' => "i.Estado <> 'resuelta'", 'cerradas' => "i.Estado = 'resuelta'", 'todas' => '1 = 1'];
            if (!isset($estadosValidos[$estado])) {
                respond(['status' => 'error', 'message' => 'El estado del reporte no es válido.'], 400);
            }
            $condiciones = [$estadosValidos[$estado], ...$periodoIncidencias];
            $zona = trim((string) ($_GET['zona'] ?? ''));
            $tipo = trim((string) ($_GET['tipo'] ?? ''));
            $contenedor = (int) ($_GET['contenedor'] ?? 0);
            $parametros = $parametrosPeriodo;
            if ($zona !== '') {
                $condiciones[] = '(i.Zona LIKE :zona OR c.Ubicacion LIKE :zona)';
                $parametros[':zona'] = '%' . $zona . '%';
            }
            if ($tipo !== '') {
                $condiciones[] = 'i.Tipo = :tipo';
                $parametros[':tipo'] = $tipo;
            }
            if ($contenedor > 0) {
                $condiciones[] = 'i.Numero_Contenedor = :contenedor';
                $parametros[':contenedor'] = $contenedor;
            }
            $consulta = $pdo->prepare(
                'SELECT i.Id_Incidencia, i.Tipo, i.Descripcion, i.Fecha, i.Estado, i.Fecha_Resolucion,
                        i.Zona, i.Numero_Contenedor, c.Ubicacion
                 FROM incidencia i LEFT JOIN contenedor c ON c.Numero_Contenedor = i.Numero_Contenedor
                 WHERE ' . implode(' AND ', $condiciones) . '
                 ORDER BY i.Fecha DESC, i.Id_Incidencia DESC'
            );
            $consulta->execute($parametros);
            respond(['status' => 'success', 'data' => $consulta->fetchAll(PDO::FETCH_ASSOC)]);
        }

        if ($resource === 'zonas_criticas') {
            $consulta = $pdo->prepare(
                'SELECT COALESCE(NULLIF(i.Zona, \'\'), NULLIF(c.Ubicacion, \'\'), \'Sin zona especificada\') AS Zona,
                        COUNT(*) AS Incidencias,
                        SUM(i.Estado <> \'resuelta\') AS Abiertas,
                        SUM(i.Estado = \'resuelta\') AS Resueltas
                 FROM incidencia i LEFT JOIN contenedor c ON c.Numero_Contenedor = i.Numero_Contenedor
                 WHERE ' . ($periodoIncidencias ? implode(' AND ', $periodoIncidencias) : '1 = 1') . '
                 GROUP BY Zona ORDER BY Incidencias DESC, Zona ASC LIMIT 50'
            );
            $consulta->execute($parametrosPeriodo);
            respond(['status' => 'success', 'data' => $consulta->fetchAll(PDO::FETCH_ASSOC)]);
        }

        if ($resource === 'tiempos_resolucion') {
            $consulta = $pdo->prepare(
                'SELECT i.Id_Incidencia, i.Tipo, i.Zona, i.Numero_Contenedor, i.Fecha, i.Fecha_Resolucion,
                        ROUND(TIMESTAMPDIFF(MINUTE, i.Fecha, i.Fecha_Resolucion) / 60, 2) AS Horas_Resolucion
                 FROM incidencia i
                 WHERE i.Estado = \'resuelta\' AND i.Fecha_Resolucion IS NOT NULL'
                    . ($periodoIncidencias ? ' AND ' . implode(' AND ', $periodoIncidencias) : '') . '
                 ORDER BY i.Fecha_Resolucion DESC, i.Id_Incidencia DESC'
            );
            $consulta->execute($parametrosPeriodo);
            $registros = $consulta->fetchAll(PDO::FETCH_ASSOC);
            $promedio = count($registros)
                ? array_sum(array_map(static fn(array $fila): float => (float) $fila['Horas_Resolucion'], $registros)) / count($registros)
                : 0;
            respond(['status' => 'success', 'data' => ['promedio_horas' => round($promedio, 2), 'incidencias' => $registros]]);
        }

        if ($resource === 'frecuencia_recoleccion') {
            $condiciones = ["r.Estado_Recoleccion = 'completada'"];
            $parametros = [];
            if ($desde !== '') {
                $condiciones[] = 'r.Fecha >= :desde';
                $parametros[':desde'] = $desde . ' 00:00:00';
            }
            if ($hasta !== '') {
                $condiciones[] = 'r.Fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)';
                $parametros[':hasta'] = $hasta . ' 00:00:00';
            }
            $ruta = (int) ($_GET['ruta'] ?? 0);
            $contenedor = (int) ($_GET['contenedor'] ?? 0);
            $zona = trim((string) ($_GET['zona'] ?? ''));
            if ($ruta > 0) {
                $condiciones[] = 'r.Id_Ruta = :ruta';
                $parametros[':ruta'] = $ruta;
            }
            if ($contenedor > 0 || $zona !== '') {
                $condiciones[] = 'EXISTS (
                    SELECT 1 FROM existe x JOIN contenedor c ON c.Id_Contenedor = x.Id_Contenedor
                    WHERE x.Id_Ruta = r.Id_Ruta'
                    . ($contenedor > 0 ? ' AND c.Numero_Contenedor = :contenedor' : '')
                    . ($zona !== '' ? ' AND c.Ubicacion LIKE :zona' : '') . ')';
                if ($contenedor > 0) $parametros[':contenedor'] = $contenedor;
                if ($zona !== '') $parametros[':zona'] = '%' . $zona . '%';
            }
            $consulta = $pdo->prepare(
                'SELECT r.Id_Ruta, rt.Recorrido, rt.Frecuencia, COUNT(*) AS Recolecciones,
                        ROUND(SUM(COALESCE(r.Volumen, 0)), 2) AS Volumen_Total,
                        MAX(r.Fecha) AS Ultima_Recoleccion
                 FROM recoleccion r JOIN ruta rt ON rt.Id_Ruta = r.Id_Ruta
                 WHERE ' . implode(' AND ', $condiciones) . '
                 GROUP BY r.Id_Ruta, rt.Recorrido, rt.Frecuencia
                 ORDER BY Recolecciones DESC, r.Id_Ruta'
            );
            $consulta->execute($parametros);
            respond(['status' => 'success', 'data' => $consulta->fetchAll(PDO::FETCH_ASSOC)]);
        }

        $municipio = (int) ($_GET['municipio'] ?? 0);
        $modulo = (string) ($_GET['modulo'] ?? 'todos');
        if (!in_array($modulo, ['todos', 'incidencias', 'rutas', 'recolecciones', 'recursos'], true)) {
            respond(['status' => 'error', 'message' => 'El módulo seleccionado no es válido.'], 400);
        }
        $filtroFechaIncidencias = $periodoIncidencias;
        $filtroFechaRecolecciones = [];
        $paramsReco = [];
        if ($desde !== '') {
            $filtroFechaRecolecciones[] = 'r.Fecha >= :desde';
            $paramsReco[':desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== '') {
            $filtroFechaRecolecciones[] = 'r.Fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)';
            $paramsReco[':hasta'] = $hasta . ' 00:00:00';
        }
        $incidenciasCondiciones = $filtroFechaIncidencias;
        $paramsIncidencias = $parametrosPeriodo;
        if ($municipio > 0) {
            $incidenciasCondiciones[] = 'EXISTS (SELECT 1 FROM contenedor c WHERE c.Numero_Contenedor = i.Numero_Contenedor AND c.Id_Municipio = :municipio)';
            $paramsIncidencias[':municipio'] = $municipio;
        }
        $rutasCondiciones = [];
        $paramsRutas = [];
        if ($municipio > 0) {
            $rutasCondiciones[] = 'Id_Municipio = :municipio';
            $paramsRutas[':municipio'] = $municipio;
        }
        $recoleccionesCondiciones = ["r.Estado_Recoleccion = 'completada'", ...$filtroFechaRecolecciones];
        if ($municipio > 0) {
            $recoleccionesCondiciones[] = 'rt.Id_Municipio = :municipio';
            $paramsReco[':municipio'] = $municipio;
        }
        $incidenciasTotal = $pdo->prepare('SELECT COUNT(*) FROM incidencia i WHERE ' . ($incidenciasCondiciones ? implode(' AND ', $incidenciasCondiciones) : '1 = 1'));
        $incidenciasTotal->execute($paramsIncidencias);
        $rutasTotal = $pdo->prepare('SELECT COUNT(*) FROM ruta WHERE ' . ($rutasCondiciones ? implode(' AND ', $rutasCondiciones) : '1 = 1'));
        $rutasTotal->execute($paramsRutas);
        $recoleccionesTotal = $pdo->prepare('SELECT COUNT(*) FROM recoleccion r JOIN ruta rt ON rt.Id_Ruta = r.Id_Ruta WHERE ' . implode(' AND ', $recoleccionesCondiciones));
        $recoleccionesTotal->execute($paramsReco);
        $contenedores = $pdo->prepare('SELECT COUNT(*) FROM contenedor' . ($municipio > 0 ? ' WHERE Id_Municipio = :municipio' : ''));
        $contenedores->execute($municipio > 0 ? [':municipio' => $municipio] : []);
        $camiones = $pdo->prepare('SELECT COUNT(*) FROM camion' . ($municipio > 0 ? ' WHERE Id_Municipio = :municipio' : ''));
        $camiones->execute($municipio > 0 ? [':municipio' => $municipio] : []);
        $maquinaria = $pdo->prepare(
            'SELECT COUNT(*) FROM maquinaria ma LEFT JOIN centro_acopio ce ON ce.Id_Centro = ma.Id_Centro'
                . ($municipio > 0 ? ' WHERE ce.Id_Municipio = :municipio' : '')
        );
        $maquinaria->execute($municipio > 0 ? [':municipio' => $municipio] : []);
        $totalesDashboard = [
            'incidencias' => (int) $incidenciasTotal->fetchColumn(),
            'rutas' => (int) $rutasTotal->fetchColumn(),
            'recolecciones' => (int) $recoleccionesTotal->fetchColumn(),
            'contenedores' => (int) $contenedores->fetchColumn(),
            'camiones' => (int) $camiones->fetchColumn(),
            'maquinaria' => (int) $maquinaria->fetchColumn(),
        ];
        if ($modulo !== 'todos') {
            foreach (array_keys($totalesDashboard) as $clave) {
                $permitido = $modulo === 'recursos'
                    ? in_array($clave, ['contenedores', 'camiones', 'maquinaria'], true)
                    : $clave === $modulo;
                if (!$permitido) $totalesDashboard[$clave] = 0;
            }
        }
        respond(['status' => 'success', 'data' => $totalesDashboard]);
    } catch (Throwable $error) {
        error_log('Error al generar reporte ' . $resource . ': ' . $error->getMessage());
        respond(['status' => 'error', 'message' => 'No se pudo generar el reporte solicitado.'], 500);
    }
}

$recursosEmpleado = ['incidencias', 'contenedores', 'municipios', 'centros', 'centros_acopio'];
$recursosLecturaOperario = ['camiones', 'maquinaria'];
$accesoLecturaOperario = esOperario()
    && $method === 'GET'
    && in_array($resource, $recursosLecturaOperario, true);
if (!esAdministrador() && !(esEmpleado() && in_array($resource, $recursosEmpleado, true)) && !$accesoLecturaOperario) {
    respond(['status' => 'error', 'message' => 'No tienes permisos para acceder a este recurso.'], 403);
}

try {
    switch ($resource) {
        case 'camiones':
            if (esOperario() && $method !== 'GET') {
                respond(['status' => 'error', 'message' => 'Los operarios solo pueden consultar los camiones.'], 403);
            }
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

        case 'asignaciones_camion':
            if ($method === 'GET') {
                $consulta = $pdo->query(
                    'SELECT a.Id_Asignacion, a.Id_Camion, c.Matricula, a.Id_Ruta, r.Recorrido,
                            r.Id_Municipio, m.Nombre AS Municipio, a.Fecha, a.Hora_Inicio, a.Hora_Fin, a.Estado
                     FROM asignacion_camion a
                     JOIN camion c ON c.Id_Camion = a.Id_Camion
                     JOIN ruta r ON r.Id_Ruta = a.Id_Ruta
                     JOIN municipio m ON m.Id_Municipio = r.Id_Municipio
                     ORDER BY a.Fecha DESC, a.Hora_Inicio DESC, a.Id_Asignacion DESC'
                );
                respond(['status' => 'success', 'data' => $consulta->fetchAll(PDO::FETCH_ASSOC)]);
            }

            if ($method === 'POST' && $action === 'cancelar') {
                $id = (int) ($data['id'] ?? $resourceId ?? 0);
                $cancelar = $pdo->prepare("UPDATE asignacion_camion SET Estado = 'cancelada' WHERE Id_Asignacion = :id AND Estado = 'asignada'");
                $cancelar->execute([':id' => $id]);
                respond([
                    'status' => $cancelar->rowCount() ? 'success' : 'error',
                    'message' => $cancelar->rowCount() ? 'Asignación cancelada.' : 'No se encontró una asignación activa con ese ID.'
                ], $cancelar->rowCount() ? 200 : 404);
            }

            if ($method === 'POST') {
                $idCamion = filter_var($data['id_camion'] ?? null, FILTER_VALIDATE_INT);
                $idRuta = filter_var($data['id_ruta'] ?? null, FILTER_VALIDATE_INT);
                $fecha = trim((string) ($data['fecha'] ?? ''));
                $inicio = trim((string) ($data['hora_inicio'] ?? ''));
                $fin = trim((string) ($data['hora_fin'] ?? ''));
                if (!$idCamion || !$idRuta || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)
                    || DateTime::createFromFormat('!Y-m-d', $fecha) === false
                    || DateTime::createFromFormat('!Y-m-d', $fecha)->format('Y-m-d') !== $fecha
                    || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $inicio)
                    || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $fin)
                    || $inicio >= $fin) {
                    respond(['status' => 'error', 'message' => 'Ingresá camión, ruta, fecha y un horario válido donde el fin sea posterior al inicio.'], 400);
                }

                $pdo->beginTransaction();
                try {
                    $camion = $pdo->prepare('SELECT Estado, Fecha_Baja FROM camion WHERE Id_Camion = :id FOR UPDATE');
                    $camion->execute([':id' => $idCamion]);
                    $datosCamion = $camion->fetch(PDO::FETCH_ASSOC);
                    if (!$datosCamion || $datosCamion['Fecha_Baja'] !== null || $datosCamion['Estado'] !== 'Disponible') {
                        throw new InvalidArgumentException('Solo se pueden asignar camiones activos y disponibles.');
                    }
                    $rutaValida = $pdo->prepare("SELECT COUNT(*) FROM ruta WHERE Id_Ruta = :id AND Estado = 'activa'");
                    $rutaValida->execute([':id' => $idRuta]);
                    if ((int) $rutaValida->fetchColumn() === 0) {
                        throw new InvalidArgumentException('Selecciona una ruta activa.');
                    }
                    $conflicto = $pdo->prepare(
                        "SELECT COUNT(*) FROM asignacion_camion
                         WHERE Id_Camion = :camion AND Fecha = :fecha AND Estado = 'asignada'
                           AND Hora_Inicio < :fin AND Hora_Fin > :inicio"
                    );
                    $conflicto->execute([
                        ':camion' => $idCamion,
                        ':fecha' => $fecha,
                        ':inicio' => $inicio,
                        ':fin' => $fin
                    ]);
                    if ((int) $conflicto->fetchColumn() > 0) {
                        throw new InvalidArgumentException('El camión ya tiene una asignación en ese horario.');
                    }
                    $insertar = $pdo->prepare(
                        "INSERT INTO asignacion_camion (Id_Camion, Id_Ruta, Fecha, Hora_Inicio, Hora_Fin, Estado)
                         VALUES (:camion, :ruta, :fecha, :inicio, :fin, 'asignada')"
                    );
                    $insertar->execute([
                        ':camion' => $idCamion,
                        ':ruta' => $idRuta,
                        ':fecha' => $fecha,
                        ':inicio' => $inicio,
                        ':fin' => $fin
                    ]);
                    $pdo->commit();
                    respond(['status' => 'success', 'message' => 'Camión asignado a la ruta correctamente.'], 201);
                } catch (Throwable $error) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    if ($error instanceof InvalidArgumentException) {
                        respond(['status' => 'error', 'message' => $error->getMessage()], 400);
                    }
                    throw $error;
                }
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
                'SELECT i.Id_Incidencia, i.Tipo, i.Descripcion, i.Fecha, i.Estado, i.Zona, i.Numero_Contenedor, c.Ubicacion
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
            if (esCuadrilla() && $method !== 'GET') {
                respond(['status' => 'error', 'message' => 'Las cuadrillas solo pueden consultar los contenedores.'], 403);
            }

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
                    ...coordenadasContenedor($data),
                    'numero_contenedor' => numeroContenedor($pdo, $data, $containerId)
                ];

                if ($updateData['tipo_residuo'] === '' || $updateData['ubicacion'] === '' || $updateData['estado'] === '' || $updateData['capacidad'] <= 0 || $updateData['id_municipio'] <= 0
                    || !in_array(strtolower($updateData['estado']), ['funcional', 'roto', 'desbordado', 'activo', 'inactivo', 'reserva'], true)) {
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

                $motivo = trim((string) ($data['motivo'] ?? ''));
                if ($motivo === '' || mb_strlen($motivo, 'UTF-8') > 500) {
                    respond(['status' => 'error', 'message' => 'Indicá un motivo de baja de hasta 500 caracteres.'], 400);
                }
                $updated = $controller->darBaja($pdo, $containerId, $motivo);
                respond([
                    'status' => $updated ? 'success' : 'error',
                    'message' => $updated ? 'Contenedor dado de baja; su historial se conservó.' : 'No se encontró un contenedor activo con ese ID.'
                ], $updated ? 200 : 404);
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
                $coordenadas = coordenadasContenedor($data);

                validateContainer($container, $municipioId);
                $created = $controller->registrar(
                    $pdo,
                    $container,
                    $municipioId,
                    numeroContenedor($pdo, $data, 0),
                    $coordenadas['latitud'],
                    $coordenadas['longitud']
                );
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
                    ...coordenadasContenedor($data),
                    'numero_contenedor' => numeroContenedor($pdo, $data, $containerId)
                ];

                if ($updateData['tipo_residuo'] === '' || $updateData['ubicacion'] === '' || $updateData['estado'] === '' || $updateData['capacidad'] <= 0 || $updateData['id_municipio'] <= 0
                    || !in_array(strtolower($updateData['estado']), ['funcional', 'roto', 'desbordado', 'activo', 'inactivo', 'reserva'], true)) {
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

                $motivo = trim((string) ($data['motivo'] ?? ''));
                if ($motivo === '' || mb_strlen($motivo, 'UTF-8') > 500) {
                    respond(['status' => 'error', 'message' => 'Indicá un motivo de baja de hasta 500 caracteres.'], 400);
                }
                $updated = $controller->darBaja($pdo, $containerId, $motivo);
                respond([
                    'status' => $updated ? 'success' : 'error',
                    'message' => $updated ? 'Contenedor dado de baja; su historial se conservó.' : 'No se encontró un contenedor activo con ese ID.'
                ], $updated ? 200 : 404);
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
            if (esOperario() && $method !== 'GET') {
                respond(['status' => 'error', 'message' => 'Los operarios solo pueden consultar la maquinaria.'], 403);
            }
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
                $deleted = $controller->eliminar($pdo, (int) ($data['id'] ?? $resourceId ?? 0), (string) ($data['motivo'] ?? ''));
                respond(['status' => $deleted ? 'success' : 'error', 'message' => $deleted ? 'Maquinaria dada de baja; su historial se conservó.' : 'No se encontró maquinaria activa con ese ID.'], $deleted ? 200 : 404);
            }

            respond(['status' => 'error', 'message' => 'Método no permitido.'], 405);

        case 'incidencias':
            $controller = new IncidenciaController();

            if ($method === 'GET') {
                if (esCuadrilla()) {
                    $cuadrillaActual = cuadrilla::buscarDeEmpleado($pdo, (int) $_SESSION['empleado_id']);
                    $consulta = $pdo->prepare(
                        "SELECT i.Id_Incidencia, i.Tipo, i.Descripcion, i.Fecha, i.Estado, i.Zona, i.Numero_Contenedor, i.Id_Cuadrilla,
                                CASE WHEN i.Id_Creador = :creador_edicion THEN 1 ELSE 0 END AS Puede_Editar
                         FROM incidencia i
                         WHERE (
                               (
                                   ((i.Id_Cuadrilla IS NULL AND i.Estado = 'abierta') OR i.Id_Cuadrilla = :cuadrilla)
                                   AND EXISTS (
                                       SELECT 1
                                       FROM existe x
                                       JOIN contenedor c ON c.Id_Contenedor = x.Id_Contenedor
                                       WHERE x.Id_Ruta = :ruta
                                         AND c.Numero_Contenedor = i.Numero_Contenedor
                                   )
                               )
                               OR i.Id_Creador = :creador_listado
                           )
                         ORDER BY i.Fecha DESC, i.Id_Incidencia DESC"
                    );
                    $consulta->execute([
                        ':cuadrilla' => $cuadrillaActual ? (int) $cuadrillaActual['Id_Cuadrilla'] : 0,
                        ':ruta' => $cuadrillaActual && $cuadrillaActual['Id_Ruta'] !== null
                            ? (int) $cuadrillaActual['Id_Ruta']
                            : 0,
                        ':creador_edicion' => (int) $_SESSION['empleado_id'],
                        ':creador_listado' => (int) $_SESSION['empleado_id']
                    ]);
                    $incidencias = $consulta->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $incidencias = $controller->listar($pdo);
                }
                respond(['status' => 'success', 'data' => $incidencias]);
            }

            if ($method === 'POST' && $action === 'aceptar') {
                if (!esCuadrilla()) {
                    respond(['status' => 'error', 'message' => 'Solo una cuadrilla puede aceptar incidencias.'], 403);
                }

                $id = (int) ($data['id'] ?? $resourceId ?? 0);
                $cuadrillaActual = cuadrilla::buscarDeEmpleado($pdo, (int) $_SESSION['empleado_id']);
                if (!$cuadrillaActual) {
                    respond(['status' => 'error', 'message' => 'Tenés que pertenecer a una cuadrilla para aceptar incidencias.'], 400);
                }
                if ($cuadrillaActual['Id_Ruta'] === null) {
                    respond(['status' => 'error', 'message' => 'Tu cuadrilla no tiene una ruta asignada.'], 400);
                }

                $accepted = $controller->aceptar(
                    $pdo,
                    $id,
                    (int) $cuadrillaActual['Id_Cuadrilla'],
                    (int) $cuadrillaActual['Id_Ruta']
                );
                respond([
                    'status' => $accepted ? 'success' : 'error',
                    'message' => $accepted ? 'Incidencia aceptada y asignada a tu cuadrilla.' : 'La incidencia no corresponde a tu ruta, ya fue aceptada o no existe.'
                ], $accepted ? 200 : 409);
            }

            if ($method === 'POST' && $action === 'asignar') {
                if (!esAdministrador()) {
                    respond(['status' => 'error', 'message' => 'Solo administración puede asignar incidencias.'], 403);
                }

                $idIncidencia = filter_var($data['id_incidencia'] ?? $data['id'] ?? null, FILTER_VALIDATE_INT);
                $idCuadrilla = filter_var($data['id_cuadrilla'] ?? null, FILTER_VALIDATE_INT);
                if (!$idIncidencia || !$idCuadrilla) {
                    respond(['status' => 'error', 'message' => 'Selecciona una incidencia y una cuadrilla válidas.'], 400);
                }

                $asignada = $controller->asignar($pdo, $idIncidencia, $idCuadrilla);
                respond([
                    'status' => $asignada ? 'success' : 'error',
                    'message' => $asignada
                        ? 'Incidencia asignada a la cuadrilla y marcada en curso.'
                        : 'La incidencia debe estar abierta y sin asignar.'
                ], $asignada ? 200 : 409);
            }

            if ($method === 'POST') {
                if (esCuadrilla()) {
                    $cuadrillaActual = cuadrilla::buscarDeEmpleado($pdo, (int) $_SESSION['empleado_id']);
                    if (!$cuadrillaActual) {
                        respond(['status' => 'error', 'message' => 'Tenés que pertenecer a una cuadrilla para registrar incidencias.'], 400);
                    }
                    $data['Id_Cuadrilla'] = (int) $cuadrillaActual['Id_Cuadrilla'];
                }

                $created = $controller->crear($pdo, $data, (int) $_SESSION['empleado_id']);
                respond(['status' => $created ? 'success' : 'error', 'message' => $created ? 'Incidencia creada correctamente.' : 'No se pudo crear la incidencia.'], $created ? 201 : 400);
            }

            if ($method === 'PUT') {
                $id = (int) ($data['id'] ?? $resourceId ?? 0);
                if (esCuadrilla()) {
                    $creador = $pdo->prepare('SELECT Id_Creador FROM incidencia WHERE Id_Incidencia = :id');
                    $creador->execute([':id' => $id]);
                    $idCreador = $creador->fetchColumn();
                    if ($idCreador === false || (int) $idCreador !== (int) $_SESSION['empleado_id']) {
                        respond(['status' => 'error', 'message' => 'Solo puedes modificar las incidencias que registraste.'], 403);
                    }
                }

                if (esCuadrilla() && strtolower(trim((string) ($data['Estado'] ?? $data['estado'] ?? ''))) === 'abierta') {
                    respond(['status' => 'error', 'message' => 'Una cuadrilla no puede volver a marcar como abierta una incidencia.'], 403);
                }
                if (esCuadrilla()) {
                    $cuadrillaActual = cuadrilla::buscarDeEmpleado($pdo, (int) $_SESSION['empleado_id']);
                    if (!$cuadrillaActual) {
                        respond(['status' => 'error', 'message' => 'Tenés que pertenecer a una cuadrilla para editar incidencias.'], 400);
                    }
                    $data['Id_Cuadrilla'] = (int) $cuadrillaActual['Id_Cuadrilla'];
                }

                $updated = $controller->actualizar($pdo, $id, $data);
                respond(['status' => $updated ? 'success' : 'error', 'message' => $updated ? 'Incidencia actualizada correctamente.' : 'No se encontró la incidencia o no hubo cambios.'], $updated ? 200 : 404);
            }

            if ($method === 'DELETE') {
                $id = (int) ($data['id'] ?? $resourceId ?? 0);
                if (esCuadrilla()) {
                    $creador = $pdo->prepare('SELECT Id_Creador FROM incidencia WHERE Id_Incidencia = :id');
                    $creador->execute([':id' => $id]);
                    $idCreador = $creador->fetchColumn();
                    if ($idCreador === false || (int) $idCreador !== (int) $_SESSION['empleado_id']) {
                        respond(['status' => 'error', 'message' => 'Solo puedes eliminar las incidencias que registraste.'], 403);
                    }
                }

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

function registrarResultadoRecorrido(
    PDO $pdo,
    array $cuadrilla,
    string $descripcion,
    ?array $incidenciaAdicional = null,
    ?float $volumen = null,
    ?string $tipoResiduo = null
): string
{
    $idRuta = (int) $cuadrilla['Id_Ruta'];
    $idCuadrilla = (int) $cuadrilla['Id_Cuadrilla'];

    $pdo->beginTransaction();
    try {
        $asignacion = $pdo->prepare(
            'SELECT 1 FROM realiza WHERE Id_Ruta = :ruta AND Id_Cuadrilla = :cuadrilla FOR UPDATE'
        );
        $asignacion->execute([':ruta' => $idRuta, ':cuadrilla' => $idCuadrilla]);
        if (!$asignacion->fetchColumn()) {
            $pdo->rollBack();
            return 'sin_asignacion';
        }

        $repetido = $pdo->prepare(
            'SELECT 1 FROM informe
             WHERE Id_Ruta = :ruta AND Id_Cuadrilla = :cuadrilla AND DATE(Fecha) = CURDATE()
               AND (Descripcion LIKE "Recorrido completado%" OR Descripcion LIKE "Recorrido NO completado%")
             LIMIT 1'
        );
        $repetido->execute([':ruta' => $idRuta, ':cuadrilla' => $idCuadrilla]);
        if ($repetido->fetchColumn()) {
            $pdo->rollBack();
            return 'repetido';
        }

        $pdo->prepare('INSERT INTO informe (Descripcion, Id_Ruta, Id_Cuadrilla) VALUES (:descripcion, :ruta, :cuadrilla)')
            ->execute([':descripcion' => $descripcion, ':ruta' => $idRuta, ':cuadrilla' => $idCuadrilla]);

        if ($volumen !== null) {
            $pdo->prepare(
                "INSERT INTO recoleccion (Fecha, Estado_Recoleccion, Volumen, Tipo_Residuo, Id_Ruta, Id_Cuadrilla)
                 VALUES (NOW(), 'completada', :volumen, :tipo, :ruta, :cuadrilla)"
            )->execute([
                ':volumen' => $volumen,
                ':tipo' => $tipoResiduo !== '' ? $tipoResiduo : null,
                ':ruta' => $idRuta,
                ':cuadrilla' => $idCuadrilla
            ]);
        }

        $pdo->prepare(
            "UPDATE incidencia i
             JOIN contenedor c ON c.Numero_Contenedor = i.Numero_Contenedor
             JOIN existe x ON x.Id_Contenedor = c.Id_Contenedor
             SET i.Estado = 'resuelta', i.Fecha_Resolucion = COALESCE(i.Fecha_Resolucion, NOW())
             WHERE x.Id_Ruta = :ruta AND i.Estado IN ('abierta', 'en curso')"
        )->execute([':ruta' => $idRuta]);

        if ($incidenciaAdicional !== null) {
            $pdo->prepare(
                'INSERT INTO incidencia (Tipo, Descripcion, Estado, Numero_Contenedor, Id_Cuadrilla)
                 VALUES (:tipo, :descripcion, "abierta", :numero, :cuadrilla)'
            )->execute([
                ':tipo' => $incidenciaAdicional['tipo'],
                ':descripcion' => $incidenciaAdicional['descripcion'],
                ':numero' => $incidenciaAdicional['numero_contenedor'],
                ':cuadrilla' => $idCuadrilla,
            ]);
        }

        $pdo->prepare('DELETE FROM realiza WHERE Id_Ruta = :ruta AND Id_Cuadrilla = :cuadrilla')
            ->execute([':ruta' => $idRuta, ':cuadrilla' => $idCuadrilla]);
        $pdo->commit();
        return 'registrado';
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
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
    if ($municipioId <= 0 || $container->getTipoResiduo() === '' || $container->getUbicacion() === '' || $container->getCapacidad() <= 0
        || !in_array(strtolower($container->getEstado()), ['funcional', 'roto', 'desbordado', 'activo', 'inactivo', 'reserva'], true)) {
        throw new InvalidArgumentException('Datos de contenedor inválidos.');
    }
}

function coordenadasContenedor(array $data): array
{
    $latitud = $data['latitud'] ?? null;
    $longitud = $data['longitud'] ?? null;

    if ($latitud === null && $longitud === null) {
        return ['latitud' => null, 'longitud' => null];
    }

    if (!is_numeric($latitud) || !is_numeric($longitud)) {
        throw new InvalidArgumentException('Las coordenadas del contenedor deben incluir latitud y longitud válidas.');
    }

    $latitud = (float) $latitud;
    $longitud = (float) $longitud;
    if (!is_finite($latitud) || !is_finite($longitud)
        || $latitud < -90 || $latitud > 90
        || $longitud < -180 || $longitud > 180) {
        throw new InvalidArgumentException('Las coordenadas del contenedor están fuera de rango.');
    }

    return ['latitud' => $latitud, 'longitud' => $longitud];
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
    global $sesionUsuarioActiva;
    return $sesionUsuarioActiva && !empty($_SESSION['empleado_id']);
}
