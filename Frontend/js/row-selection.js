(() => {
    const backendBase = new URL('../../Backend/', document.currentScript.src);
    const tablasObservadas = new WeakSet();
    const clavesSeleccionadas = new WeakMap();

    function claveFila(fila, recurso) {
        const identificador = fila.cells[0]?.textContent.trim() || '';
        const idExplicito = identificador.match(/\bID:\s*(\d+)/i);
        if (idExplicito) return idExplicito[1];

        const botonEliminar = fila.querySelector('.delete');
        const idBoton = botonEliminar?.dataset.id;
        if (idBoton) return idBoton;

        if (recurso === 'mantenimientos') {
            const idMantenimiento = botonEliminar?.getAttribute('onclick')?.match(/eliminar\((\d+)\)/);
            if (idMantenimiento) return idMantenimiento[1];
        }

        return identificador;
    }

    function prepararTabla(tabla) {
        const encabezado = tabla.tHead?.rows[0];
        if (!encabezado) return;

        const indiceAcciones = [...encabezado.cells].findIndex(celda =>
            celda.textContent.trim().toLocaleLowerCase() === 'acciones'
        );
        if (indiceAcciones < 0) return;

        const recurso = tabla.dataset.bulkDelete;
        let seleccionadas = clavesSeleccionadas.get(tabla);
        if (!seleccionadas) {
            seleccionadas = new Map();
            clavesSeleccionadas.set(tabla, seleccionadas);
        }

        const procesarFilas = () => {
            for (const fila of tabla.tBodies) {
                for (const registro of fila.rows) {
                    const celda = registro.cells[indiceAcciones];
                    if (!celda || celda.hasAttribute('colspan') || celda.querySelector('.row-select-action')) continue;

                    const clave = claveFila(registro, recurso);
                    if (!clave) continue;

                    const etiqueta = document.createElement('label');
                    etiqueta.className = 'row-select-action';
                    const casilla = document.createElement('input');
                    casilla.type = 'checkbox';
                    casilla.checked = seleccionadas.has(clave);
                    casilla.setAttribute('aria-label', `Seleccionar fila ${clave}`);
                    const texto = document.createElement('span');
                    texto.textContent = 'Seleccionar';
                    etiqueta.append(casilla, texto);
                    celda.prepend(etiqueta);

                    casilla.addEventListener('change', () => {
                        if (casilla.checked) {
                            const botonEliminar = registro.querySelector('.delete');
                            seleccionadas.set(clave, {
                                id: clave,
                                eliminable: Boolean(recurso && botonEliminar && !botonEliminar.disabled)
                            });
                        }
                        else seleccionadas.delete(clave);
                    });
                }
            }
        };

        procesarFilas();

        const observador = new MutationObserver(procesarFilas);
        for (const cuerpo of tabla.tBodies) {
            observador.observe(cuerpo, { childList: true });
        }
        tablasObservadas.add(tabla);
    }

    function solicitudEliminar(recurso, id) {
        const gestionApi = new URL('apis/gestion/ApiGestion.php', backendBase);
        const opciones = { credentials: 'same-origin' };

        if (recurso === 'empleados') {
            const empleadosApi = new URL('apis/empleado/ApiEmpleado.php', backendBase);
            empleadosApi.searchParams.set('accion', 'eliminar');
            empleadosApi.searchParams.set('id', id);
            opciones.method = 'POST';
            return [empleadosApi, opciones];
        }

        gestionApi.searchParams.set('recurso', recurso);
        if (recurso === 'camiones') {
            gestionApi.searchParams.set('accion', 'eliminar');
            opciones.method = 'POST';
            opciones.headers = { 'Content-Type': 'application/json' };
            opciones.body = JSON.stringify({ matricula: id });
        } else if (recurso === 'rutas') {
            gestionApi.searchParams.set('accion', 'eliminar');
            opciones.method = 'POST';
            opciones.headers = { 'Content-Type': 'application/json' };
            opciones.body = JSON.stringify({ Id_Ruta: Number(id) });
        } else {
            gestionApi.searchParams.set('id', id);
            opciones.method = 'DELETE';
        }

        return [gestionApi, opciones];
    }

    const recargadores = {
        camiones: 'cargarCamiones',
        centros: 'cargarCentros',
        contenedores: 'cargarContenedores',
        cuadrillas: 'cargarCuadrillas',
        empleados: 'cargarEmpleados',
        incidencias: 'cargarIncidencias',
        rutas: 'cargarRutas',
        maquinaria: 'cargarMaquinas',
        mantenimientos: 'cargarMantenimientos',
        informes: 'cargar',
        operario: 'cargar'
    };

    async function eliminarSeleccionados(tabla, filaPulsada, evento) {
        const seleccionadas = clavesSeleccionadas.get(tabla);
        const recurso = tabla.dataset.bulkDelete;
        const clavePulsada = claveFila(filaPulsada, recurso);
        const registros = [...(seleccionadas?.entries() || [])]
            .filter(([, registro]) => registro.eliminable);

        if (!recurso || !seleccionadas?.get(clavePulsada)?.eliminable) return;
        if (registros.length < 2) {
            seleccionadas.delete(clavePulsada);
            return;
        }

        evento.preventDefault();
        evento.stopImmediatePropagation();
        const detalle = recurso === 'centros'
            ? ' También se borrarán su maquinaria y sus ingresos.'
            : '';
        if (!confirm(`Se eliminarán ${registros.length} registros seleccionados.${detalle} Esta acción no se puede deshacer. ¿Continuar?`)) return;

        const errores = [];
        let eliminados = 0;
        for (const [clave, registro] of registros) {
            try {
                const [url, opciones] = solicitudEliminar(recurso, registro.id);
                const respuesta = await fetch(url, opciones);
                const datos = await respuesta.json();
                if (!respuesta.ok || datos.status !== 'success') {
                    throw new Error(datos.message || `Error HTTP ${respuesta.status}`);
                }
                seleccionadas.delete(clave);
                eliminados++;
            } catch (error) {
                errores.push(`${registro.id}: ${error.message}`);
            }
        }

        const recargar = window[recargadores[recurso]];
        if (eliminados && typeof recargar === 'function') await recargar();

        const resumen = `${eliminados} de ${registros.length} registros eliminados.`;
        alert(errores.length ? `${resumen}\nNo se pudieron eliminar:\n${errores.join('\n')}` : resumen);
    }

    async function procesarClickEliminar(event) {
        const objetivo = event.target instanceof Element ? event.target : null;
        const boton = objetivo?.closest('button.delete');
        if (!boton) return;

        const tabla = boton.closest('table');
        const fila = boton.closest('tr');
        if (!tabla || !fila) return;

        await eliminarSeleccionados(tabla, fila, event);
    }

    document.addEventListener('click', event => {
        void procesarClickEliminar(event);
    }, true);

    function prepararTablas() {
        document.querySelectorAll('table').forEach(tabla => {
            if (!tablasObservadas.has(tabla)) prepararTabla(tabla);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', prepararTablas, { once: true });
    } else {
        prepararTablas();
    }
})();
