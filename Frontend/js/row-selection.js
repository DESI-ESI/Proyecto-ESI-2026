(() => {
    const tablasObservadas = new WeakSet();
    const clavesSeleccionadas = new WeakMap();

    function claveFila(fila) {
        const identificador = fila.cells[0]?.textContent.trim() || '';
        const idExplicito = identificador.match(/\bID:\s*(\d+)/i);
        return idExplicito ? idExplicito[1] : identificador;
    }

    function prepararTabla(tabla) {
        const encabezado = tabla.tHead?.rows[0];
        if (!encabezado) return;

        const indiceAcciones = [...encabezado.cells].findIndex(celda =>
            celda.textContent.trim().toLocaleLowerCase() === 'acciones'
        );
        if (indiceAcciones < 0) return;

        let seleccionadas = clavesSeleccionadas.get(tabla);
        if (!seleccionadas) {
            seleccionadas = new Set();
            clavesSeleccionadas.set(tabla, seleccionadas);
        }

        const procesarFilas = () => {
            for (const fila of tabla.tBodies) {
                for (const registro of fila.rows) {
                    const celda = registro.cells[indiceAcciones];
                    if (!celda || celda.hasAttribute('colspan') || celda.querySelector('.row-select-action')) continue;

                    const clave = claveFila(registro);
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
                        if (casilla.checked) seleccionadas.add(clave);
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
