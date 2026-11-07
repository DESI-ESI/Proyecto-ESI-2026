
(function () {
    function ocultarEnlacesEstaticos() {
        document.querySelectorAll(
            'a[href="Operario.html"], a[href="Operario.html?tipo=vertedero"]'
        ).forEach(link => {
            link.style.display = 'none';
        });
    }

    ocultarEnlacesEstaticos();

    async function configurarInstalacion() {
        try {
            const respuesta = await fetch(
                '../../Backend/apis/gestion/ApiGestion.php?recurso=operario',
                { credentials: 'same-origin' }
            );

            const datos = await respuesta.json();

            if (!respuesta.ok || datos.status === 'error') return;

            const asignado = datos.data && datos.data.asignado;

            const esVertedero = asignado &&
                String(asignado.Tipo || '').toLowerCase() === 'vertedero';

            document.querySelectorAll('aside.sidebar nav a').forEach(link => {
                const href = link.getAttribute('href') || '';

                const esCentro = href === 'Operario.html';
                const esVertederoLink =
                    href === 'Operario.html?tipo=vertedero';

                if (!esCentro && !esVertederoLink) return;

                if (!asignado) {
                    link.style.display = 'none';
                    return;
                }

                if (esVertedero && esVertederoLink) {
                    link.style.display = '';
                    link.innerHTML = '<span>▤</span> Vertedero';
                } else if (!esVertedero && esCentro) {
                    link.style.display = '';
                    link.innerHTML = '<span>▦</span> Centro de acopio';
                } else {
                    link.style.display = 'none';
                }
            });

        } catch (error) {
            console.error(
                'No se pudo determinar la instalación del operario:',
                error
            );
        }
    }

    document.addEventListener('DOMContentLoaded', configurarInstalacion);
})();

