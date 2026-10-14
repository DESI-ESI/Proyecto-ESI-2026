<?php

const NOTIFICACION_RECURSOS = [
    'camiones' => ['un camión', ['matricula']],
    'mantenimientos' => ['un mantenimiento', ['tipo']],
    'contenedores' => ['un contenedor', ['numero_contenedor', 'numero']],
    'rutas' => ['una ruta', ['Recorrido', 'recorrido']],
    'centros' => ['una instalación', ['nombre']],
    'centros_acopio' => ['una instalación', ['nombre']],
    'maquinaria' => ['una maquinaria', ['nombre']],
    'incidencias' => ['una incidencia', ['descripcion']],
    'cuadrillas' => ['una cuadrilla', ['turno']],
    'operario' => ['un ingreso de residuos', ['tipo_residuo', 'tipo']],
    'informes' => ['un informe', []],
    'miruta' => ['el recorrido de su ruta', []],
    'empleados' => ['un empleado', ['nombre', 'email']],
];

function panelDeOrigen(): string
{
    $panel = strtolower(trim((string) ($_GET['panel'] ?? '')));
    if (in_array($panel, ['admin', 'empleado'], true)) {
        return $panel;
    }

    $referer = strtolower((string) ($_SERVER['HTTP_REFERER'] ?? ''));
    return strpos($referer, '/empleados/') !== false ? 'empleado' : 'admin';
}

function registrarNotificacion(PDO $pdo, string $recurso, string $metodo, string $accion, array $datos): void
{
    if (!isset(NOTIFICACION_RECURSOS[$recurso]) || $metodo === 'GET' || $metodo === 'OPTIONS') {
        return;
    }

    if ($accion === 'eliminar' || $metodo === 'DELETE') {
        $verbo = 'eliminó';
    } elseif ($accion === 'completar') {
        $verbo = 'completó';
    } elseif ($accion === 'no_completado') {
        $verbo = 'no pudo completar';
    } elseif ($accion === 'reportar_contenedor') {
        $verbo = 'reportó';
    } elseif ($accion === 'editar' || $metodo === 'PUT') {
        $verbo = 'modificó';
    } else {
        $verbo = 'registró';
    }

    [$etiqueta, $claves] = NOTIFICACION_RECURSOS[$recurso];
    if ($accion === 'reportar_contenedor') {
        $etiqueta = 'un problema en un contenedor';
        $claves = ['descripcion'];
    } elseif ($accion === 'no_completado') {
        $claves = ['descripcion'];
    }
    $detalle = '';
    foreach ($claves as $clave) {
        if (isset($datos[$clave]) && trim((string) $datos[$clave]) !== '') {
            $detalle = mb_substr(trim((string) $datos[$clave]), 0, 60);
            break;
        }
    }

    $nombre = trim((string) ($_SESSION['nombre_empleado'] ?? ''));
    $descripcion = ($nombre !== '' ? $nombre : 'Un usuario') . " $verbo $etiqueta" . ($detalle !== '' ? " ($detalle)" : '');

    $stmt = $pdo->prepare(
        'INSERT INTO notificacion (Panel_Origen, Recurso, Accion, Descripcion, Id_Empleado, Nombre_Empleado)
         VALUES (:panel, :recurso, :accion, :descripcion, :empleado, :nombre)'
    );
    $stmt->execute([
        ':panel' => panelDeOrigen(),
        ':recurso' => $recurso,
        ':accion' => $verbo,
        ':descripcion' => mb_substr($descripcion, 0, 255),
        ':empleado' => $_SESSION['empleado_id'] ?? null,
        ':nombre' => $nombre !== '' ? $nombre : null,
    ]);
}

function paneles_visibles(string $panel): array
{
    return $panel === 'admin' ? ['admin', 'empleado'] : ['empleado'];
}

function listarNotificaciones(PDO $pdo, string $panel, int $idEmpleado): array
{
    $origenes = paneles_visibles($panel);
    $marcas = implode(',', array_fill(0, count($origenes), '?'));

    $stmt = $pdo->prepare(
        "SELECT Id_Notificacion, Panel_Origen, Recurso, Accion, Descripcion, Fecha
         FROM notificacion WHERE Panel_Origen IN ($marcas)
         ORDER BY Id_Notificacion DESC LIMIT 50"
    );
    $stmt->execute($origenes);
    $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $leida = $pdo->prepare('SELECT Ultimo_Id FROM notificacion_lectura WHERE Id_Empleado = ? AND Panel = ?');
    $leida->execute([$idEmpleado, $panel]);
    $ultimo = (int) $leida->fetchColumn();

    $noLeidas = $pdo->prepare("SELECT COUNT(*) FROM notificacion WHERE Panel_Origen IN ($marcas) AND Id_Notificacion > ?");
    $noLeidas->execute(array_merge($origenes, [$ultimo]));

    return ['ultimo_leido' => $ultimo, 'no_leidas' => (int) $noLeidas->fetchColumn(), 'notificaciones' => $lista];
}

function marcarNotificacionesLeidas(PDO $pdo, string $panel, int $idEmpleado): void
{
    $origenes = paneles_visibles($panel);
    $marcas = implode(',', array_fill(0, count($origenes), '?'));

    $max = $pdo->prepare("SELECT COALESCE(MAX(Id_Notificacion), 0) FROM notificacion WHERE Panel_Origen IN ($marcas)");
    $max->execute($origenes);

    $stmt = $pdo->prepare(
        'INSERT INTO notificacion_lectura (Id_Empleado, Panel, Ultimo_Id) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE Ultimo_Id = VALUES(Ultimo_Id)'
    );
    $stmt->execute([$idEmpleado, $panel, (int) $max->fetchColumn()]);
}
