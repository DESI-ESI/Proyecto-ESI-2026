const claveSesion = 'sigeru_sesion';
const rutaActual = window.location.pathname.toLowerCase();
let panelActual = ['/empleados/', '/operario/', '/cuadrilla/'].some(ruta => rutaActual.includes(ruta)) ? 'empleado' : 'admin';
let apiNotificaciones = '';
const enSubcarpetaFrontend = ['/administrador/', '/empleados/', '/operario/', '/cuadrilla/'].some(ruta => rutaActual.includes(ruta));
const backendBase = enSubcarpetaFrontend ? '../../Backend/' : '../Backend/';
const paginaLogin = enSubcarpetaFrontend ? '../login.html' : 'login.html';
const paginaInicio = enSubcarpetaFrontend ? '../index.php' : 'index.php';
const clavePreferenciaNotificaciones = 'sigeru_mostrar_notificaciones';
const clavePreferenciaTema = 'sigeru_tema';

function aplicarTema(oscuro, guardar = false) {
    const tema = oscuro ? 'dark' : 'light';
    document.documentElement.dataset.sigeruTheme = tema;
    document.documentElement.style.colorScheme = tema;
    if (guardar) localStorage.setItem(clavePreferenciaTema, tema);

    const toggle = document.getElementById('account-theme-toggle');
    if (toggle) {
        toggle.setAttribute('aria-checked', String(oscuro));
        toggle.setAttribute('aria-label', oscuro ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
        toggle.querySelector('.account-theme-label').textContent = oscuro ? 'Tema oscuro' : 'Tema claro';
        toggle.querySelector('.account-theme-state').textContent = oscuro ? 'Activado' : 'Desactivado';
    }
}

function instalarEstilosTema() {
    const estilos = document.createElement('style');
    estilos.id = 'sigeru-theme-styles';
    estilos.textContent = `
        html[data-sigeru-theme="dark"] body {
            background: #111714 !important;
            color: #e5ece8 !important;
        }
        html[data-sigeru-theme="dark"] .topbar,
        html[data-sigeru-theme="dark"] .sidebar,
        html[data-sigeru-theme="dark"] .navbar {
            background: #18211d !important;
            color: #e5ece8 !important;
            border-color: #303b35 !important;
        }
        html[data-sigeru-theme="dark"] .main-content,
        html[data-sigeru-theme="dark"] .settings-content,
        html[data-sigeru-theme="dark"] .seccion,
        html[data-sigeru-theme="dark"] .servicios,
        html[data-sigeru-theme="dark"] .contacto {
            background-color: #111714 !important;
            color: #e5ece8 !important;
        }
        html[data-sigeru-theme="dark"] .stat-card,
        html[data-sigeru-theme="dark"] .panel,
        html[data-sigeru-theme="dark"] .filters,
        html[data-sigeru-theme="dark"] .data-table,
        html[data-sigeru-theme="dark"] .users-table,
        html[data-sigeru-theme="dark"] .settings-card,
        html[data-sigeru-theme="dark"] .tarjeta,
        html[data-sigeru-theme="dark"] .contacto-item,
        html[data-sigeru-theme="dark"] .account-menu-popover,
        html[data-sigeru-theme="dark"] .notif-menu,
        html[data-sigeru-theme="dark"] .modal,
        html[data-sigeru-theme="dark"] .modal-content,
        html[data-sigeru-theme="dark"] .modal-card,
        html[data-sigeru-theme="dark"] .modal-dialog,
        html[data-sigeru-theme="dark"] .modal-body,
        html[data-sigeru-theme="dark"] .modal-overlay > div {
            background-color: #1b2520 !important;
            color: #e5ece8 !important;
            border-color: #34423a !important;
        }
        html[data-sigeru-theme="dark"] .sidebar a,
        html[data-sigeru-theme="dark"] .menu a,
        html[data-sigeru-theme="dark"] .admin-info,
        html[data-sigeru-theme="dark"] .topbar-title,
        html[data-sigeru-theme="dark"] .welcome h1,
        html[data-sigeru-theme="dark"] .page-header h1,
        html[data-sigeru-theme="dark"] .seccion h2,
        html[data-sigeru-theme="dark"] .account-menu-name,
        html[data-sigeru-theme="dark"] .notif-title,
        html[data-sigeru-theme="dark"] .panel-header h2,
        html[data-sigeru-theme="dark"] .number,
        html[data-sigeru-theme="dark"] .activity-item strong,
        html[data-sigeru-theme="dark"] .action strong,
        html[data-sigeru-theme="dark"] td,
        html[data-sigeru-theme="dark"] th {
            color: #e5ece8 !important;
        }
        html[data-sigeru-theme="dark"] .sidebar a:hover,
        html[data-sigeru-theme="dark"] .sidebar a.active,
        html[data-sigeru-theme="dark"] .account-menu-action:hover,
        html[data-sigeru-theme="dark"] .action:hover,
        html[data-sigeru-theme="dark"] tbody tr:hover {
            background-color: #26372e !important;
            color: #9fe0b9 !important;
        }
        html[data-sigeru-theme="dark"] input:not([type="checkbox"]):not([type="radio"]),
        html[data-sigeru-theme="dark"] select,
        html[data-sigeru-theme="dark"] textarea {
            background-color: #121a16 !important;
            color: #e5ece8 !important;
            border-color: #46544c !important;
        }
        html[data-sigeru-theme="dark"] input::placeholder,
        html[data-sigeru-theme="dark"] textarea::placeholder,
        html[data-sigeru-theme="dark"] .page-header p,
        html[data-sigeru-theme="dark"] .welcome p,
        html[data-sigeru-theme="dark"] .settings-heading p,
        html[data-sigeru-theme="dark"] .settings-account dt,
        html[data-sigeru-theme="dark"] .settings-account dd,
        html[data-sigeru-theme="dark"] .seccion-texto,
        html[data-sigeru-theme="dark"] .tarjeta p,
        html[data-sigeru-theme="dark"] .account-menu-role,
        html[data-sigeru-theme="dark"] .account-theme-state,
        html[data-sigeru-theme="dark"] .notif-menu li,
        html[data-sigeru-theme="dark"] .notif-menu li small {
            color: #aab8b0 !important;
        }
        html[data-sigeru-theme="dark"] table thead,
        html[data-sigeru-theme="dark"] th,
        html[data-sigeru-theme="dark"] .panel-header,
        html[data-sigeru-theme="dark"] .activity-item,
        html[data-sigeru-theme="dark"] .action,
        html[data-sigeru-theme="dark"] .account-menu-profile,
        html[data-sigeru-theme="dark"] .account-menu-separator,
        html[data-sigeru-theme="dark"] .notif-title {
            border-color: #34423a !important;
        }
        html[data-sigeru-theme="dark"] .tarjetas .tarjeta,
        html[data-sigeru-theme="dark"] .empty-table-message {
            border-color: #34423a !important;
        }
        html[data-sigeru-theme="dark"] #index-greeting {
            color: #e5ece8 !important;
        }
        .account-theme-toggle { justify-content: space-between; gap: 12px; }
        .account-theme-copy { display: flex; flex: 1; flex-direction: column; gap: 3px; }
        .account-theme-label { font-weight: 600; }
        .account-theme-state { color: #748079; font-size: 11px; }
        .account-theme-track { position: relative; display: inline-flex; align-items: center; width: 62px; height: 30px; padding: 3px; border: 1px solid #cbd5cf; border-radius: 20px; background: #f0f3f1; }
        .account-theme-thumb { position: absolute; top: 3px; left: 3px; width: 22px; height: 22px; border-radius: 50%; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.2); transition: transform .2s ease, background .2s ease; }
        .account-theme-toggle[aria-checked="true"] .account-theme-track { background: #176b43; border-color: #176b43; }
        .account-theme-toggle[aria-checked="true"] .account-theme-thumb { transform: translateX(32px); background: #b9f2cc; }
        html[data-sigeru-theme="dark"] .account-theme-track { background: #111714; border-color: #46544c; }
        html[data-sigeru-theme="dark"] .account-theme-toggle[aria-checked="true"] .account-theme-track { background: #176b43; border-color: #238b57; }
        html[data-sigeru-theme="dark"] .account-theme-thumb { background: #e5ece8; }
        @media (prefers-reduced-motion: reduce) {
            .account-theme-thumb { transition: none; }
        }
    `;
    document.head.appendChild(estilos);
}

instalarEstilosTema();
aplicarTema(localStorage.getItem(clavePreferenciaTema) === 'dark');

function actualizarApiNotificaciones() {
    apiNotificaciones = backendBase + 'apis/gestion/ApiGestion.php?recurso=notificaciones&panel=' + panelActual;
}
actualizarApiNotificaciones();

if (typeof window.abrirModalSeleccionPanel !== 'function' && !document.getElementById('script-panel-selector')) {
    const s = document.createElement('script');
    s.id = 'script-panel-selector';
    s.src = enSubcarpetaFrontend ? '../js/panel-selector.js?v=7' : 'js/panel-selector.js?v=7';
    document.head.appendChild(s);
}

const fetchOriginal = window.fetch.bind(window);
window.fetch = (entrada, opciones) => {
    if (typeof entrada === 'string' && entrada.includes('Backend/apis/') && !/[?&]panel=/.test(entrada)) {
        entrada += (entrada.includes('?') ? '&' : '?') + 'panel=' + panelActual;
    }
    return fetchOriginal(entrada, opciones);
};

let ultimoLeido = 0;

function formatearFecha(fecha) {
    const [dia, hora] = String(fecha).split(' ');
    const [anio, mes, d] = dia.split('-');
    return `${d}/${mes}/${anio} ${(hora || '').slice(0, 5)}`;
}

function crearCampana() {
    const logout = document.getElementById('logout-btn');
    if (!logout || document.getElementById('notif-btn') || localStorage.getItem(clavePreferenciaNotificaciones) === 'false') return;

    const estilo = document.createElement('style');
    estilo.textContent = `
        .notif-wrap { position: relative; display: inline-flex; align-items: center; margin-right: 10px; }
        .notif-btn { position: relative; background: #f1f4f2; border: 1px solid #dfe6e2; border-radius: 50%; width: 42px; height: 42px; cursor: pointer; color: #34413b; display: flex; align-items: center; justify-content: center; padding: 0; transition: background .2s ease, color .2s ease, box-shadow .2s ease; }
        .notif-btn:hover, .notif-btn[aria-expanded="true"] { background: #e7f5ed; color: #238b57; border-color: #cce6d8; box-shadow: 0 0 0 3px rgba(35,139,87,.14); }
        .notif-badge { position: absolute; top: -7px; right: -7px; background: #d62828; color: #fff; font-size: 11px; font-weight: 700; min-width: 20px; height: 20px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 5px; box-sizing: border-box; }
        .notif-badge[hidden] { display: none; }
        .notif-menu { position: absolute; right: 0; top: calc(100% + 10px); width: 340px; max-width: calc(100vw - 24px); max-height: 420px; overflow-y: auto; background: #fff; border: 1px solid #e1e7e4; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); z-index: 600; text-align: left; }
        .notif-menu[hidden] { display: none; }
        .notif-title { padding: 12px 16px; font-weight: 700; color: #26322c; border-bottom: 1px solid #eef2f0; }
        .notif-menu ul { list-style: none; margin: 0; padding: 0; }
        .notif-menu li { padding: 10px 16px; border-bottom: 1px solid #f1f4f2; font-size: 13px; color: #36443e; }
        .notif-menu li.nueva { background: #eaf6ef; }
        .notif-menu li small { display: block; color: #7a8680; margin-top: 3px; font-size: 11px; }
        .notif-vacio { padding: 18px 16px; color: #7a8680; font-size: 13px; text-align: center; }
    `;
    document.head.appendChild(estilo);

    const caja = document.createElement('div');
    caja.className = 'notif-wrap';
    caja.innerHTML = `
        <button type="button" id="notif-btn" class="notif-btn" aria-label="Notificaciones">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            <span id="notif-badge" class="notif-badge" hidden></span>
        </button>
        <div id="notif-menu" class="notif-menu" hidden>
            <div class="notif-title">Notificaciones</div>
            <ul id="notif-list"></ul>
        </div>`;
    const menuCuenta = document.getElementById('account-menu');
    const contenedorPerfil = menuCuenta?.parentElement || logout.parentElement;
    if (menuCuenta?.parentElement) {
        menuCuenta.parentElement.insertBefore(caja, menuCuenta);
    } else {
        contenedorPerfil.insertBefore(caja, logout);
    }

    const menu = document.getElementById('notif-menu');
    const botonNotificaciones = document.getElementById('notif-btn');
    botonNotificaciones.setAttribute('aria-expanded', 'false');
    botonNotificaciones.addEventListener('click', event => {
        event.stopPropagation();
        menu.hidden = !menu.hidden;
        botonNotificaciones.setAttribute('aria-expanded', String(!menu.hidden));
        if (!menu.hidden) {
            cargarNotificaciones(true);
        }
    });
    document.addEventListener('click', event => {
        if (!caja.contains(event.target)) {
            menu.hidden = true;
            botonNotificaciones.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            menu.hidden = true;
            botonNotificaciones.setAttribute('aria-expanded', 'false');
        }
    });

    cargarNotificaciones(false);
    setInterval(() => cargarNotificaciones(!menu.hidden), 30000);
}

function cargarNotificaciones(abierto) {
    fetchOriginal(apiNotificaciones, { credentials: 'same-origin' })
        .then(response => response.json())
        .then(respuesta => {
            if (respuesta.status !== 'success') {
                throw new Error(respuesta.message || 'La API rechazó la consulta de notificaciones.');
            }
            const { notificaciones, no_leidas, ultimo_leido } = respuesta.data;
            const badge = document.getElementById('notif-badge');
            const lista = document.getElementById('notif-list');

            if (!abierto) {
                ultimoLeido = ultimo_leido;
                badge.textContent = no_leidas > 9 ? '9+' : String(no_leidas);
                badge.hidden = no_leidas === 0;
            }

            lista.innerHTML = '';
            if (notificaciones.length === 0) {
                lista.innerHTML = '<li class="notif-vacio">No hay notificaciones.</li>';
            }
            notificaciones.forEach(item => {
                const fila = document.createElement('li');
                if (Number(item.Id_Notificacion) > ultimoLeido) fila.className = 'nueva';
                fila.textContent = item.Descripcion;
                const fecha = document.createElement('small');
                fecha.textContent = formatearFecha(item.Fecha) + (panelActual === 'admin' ? ` · panel de ${item.Panel_Origen === 'empleado' ? 'empleados' : 'administrador'}` : '');
                fila.appendChild(fecha);
                lista.appendChild(fila);
            });

            if (abierto && no_leidas > 0) {
                badge.hidden = true;
                ultimoLeido = Math.max(...notificaciones.map(item => Number(item.Id_Notificacion)));
                fetchOriginal(apiNotificaciones + '&accion=leer', { method: 'POST', credentials: 'same-origin' })
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    })
                    .catch(error => console.error('No se pudieron marcar las notificaciones como leídas:', error));
            }
        })
        .catch(error => {
            console.error('No se pudieron cargar las notificaciones:', error);
            if (abierto) {
                const lista = document.getElementById('notif-list');
                if (lista) {
                    const fila = document.createElement('li');
                    fila.className = 'notif-vacio';
                    fila.textContent = 'No se pudieron cargar las notificaciones.';
                    lista.replaceChildren(fila);
                }
            }
        });
}

function crearMenuCuenta(session, esAdmin, rol) {
    const adminInfo = document.querySelector('.admin-info, .acciones');
    const logout = document.getElementById('logout-btn');
    if (!adminInfo || !logout) return;

    let menu = document.getElementById('account-menu');
    if (!menu) {
        const estilos = document.createElement('style');
        estilos.id = 'account-menu-styles';
        estilos.textContent = `
            .account-menu { position: relative; display: inline-flex; }
            .account-menu-trigger { width: 42px; height: 42px; border: 0; border-radius: 50%; padding: 0; background: #e7f5ed; color: #176b43; font-weight: 700; font-size: 15px; cursor: pointer; }
            .account-menu-trigger:hover, .account-menu-trigger[aria-expanded="true"] { box-shadow: 0 0 0 3px rgba(35,139,87,.18); }
            .account-menu-popover { position: absolute; z-index: 500; top: calc(100% + 12px); right: 0; width: 320px; max-width: calc(100vw - 24px); background: #fff; border: 1px solid #e1e7e4; border-radius: 14px; box-shadow: 0 12px 34px rgba(0,0,0,.2); color: #26332f; overflow: hidden; }
            .account-menu-popover[hidden] { display: none; }
            .account-menu-profile { display: flex; align-items: center; gap: 12px; padding: 18px; border-bottom: 1px solid #e8ecea; }
            .account-menu-avatar { flex: 0 0 42px; width: 42px; height: 42px; border-radius: 50%; display: grid; place-items: center; background: #e7f5ed; color: #176b43; font-weight: 700; }
            .account-menu-name { font-weight: 700; font-size: 14px; }
            .account-menu-role { color: #748079; font-size: 12px; margin-top: 3px; text-transform: capitalize; }
            .account-menu-actions { padding: 8px; }
            .account-menu-action { width: 100%; min-height: 44px; display: flex; align-items: center; gap: 12px; padding: 9px 11px; border: 0; border-radius: 8px; background: transparent; color: inherit; text-decoration: none; font: inherit; font-size: 14px; text-align: left; cursor: pointer; }
            .account-menu-action:hover { background: #f1f5f3; }
            .account-theme-toggle:hover { background: #f1f5f3; }
            .account-menu-action-icon { width: 22px; text-align: center; font-size: 17px; }
            .account-menu-separator { height: 1px; margin: 5px 8px; background: #e8ecea; }
            .account-menu-actions #logout-btn { width: 100%; background: transparent; border: 0; border-radius: 8px; padding: 9px 11px; margin: 0; text-align: left; font: inherit; font-size: 14px; color: inherit; }
            .account-menu-actions #logout-btn:hover { background: #f1f5f3; color: #b42318; transform: none; }
            @media (max-width: 520px) { .account-menu-popover { right: -8px; } }
        `;
        document.head.appendChild(estilos);

        menu = document.createElement('div');
        menu.id = 'account-menu';
        menu.className = 'account-menu';
        menu.innerHTML = `
            <button type="button" class="account-menu-trigger" id="account-menu-trigger" aria-label="Abrir menú de cuenta" aria-expanded="false"></button>
            <section class="account-menu-popover" id="account-menu-popover" aria-label="Menú de cuenta" hidden>
                <div class="account-menu-profile">
                    <div class="account-menu-avatar" id="account-menu-avatar"></div>
                    <div>
                        <div class="account-menu-name" id="account-menu-name"></div>
                        <div class="account-menu-role" id="account-menu-role"></div>
                    </div>
                </div>
                <div class="account-menu-actions">
                    <a href="#seleccionar-panel-empleado" class="account-menu-action account-menu-select-panel">
                        <span class="account-menu-action-icon">▣</span><span>Elegir panel</span>
                    </a>
                    <a href="${enSubcarpetaFrontend ? '../Configuracion.html' : 'Configuracion.html'}" class="account-menu-action">
                        <span class="account-menu-action-icon">⚙</span><span>Configuración</span>
                    </a>
                    <button type="button" id="account-theme-toggle" class="account-menu-action account-theme-toggle" role="switch" aria-checked="false">
                        <span class="account-menu-action-icon" aria-hidden="true">◐</span>
                        <span class="account-theme-copy">
                            <span class="account-theme-label">Tema claro</span>
                            <span class="account-theme-state">Desactivado</span>
                        </span>
                        <span class="account-theme-track" aria-hidden="true"><span class="account-theme-thumb"></span></span>
                    </button>
                    <div class="account-menu-separator"></div>
                </div>
            </section>`;
        adminInfo.appendChild(menu);

        const trigger = menu.querySelector('#account-menu-trigger');
        const popover = menu.querySelector('#account-menu-popover');
        trigger.addEventListener('click', () => {
            popover.hidden = !popover.hidden;
            trigger.setAttribute('aria-expanded', String(!popover.hidden));
        });
        document.addEventListener('click', event => {
            if (!menu.contains(event.target)) {
                popover.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                popover.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
        menu.querySelector('#account-theme-toggle').addEventListener('click', () => {
            aplicarTema(document.documentElement.dataset.sigeruTheme !== 'dark', true);
        });
        menu.querySelector('.account-menu-actions').appendChild(logout);
    }

    const nombre = session.nombre || (esAdmin ? 'Administrador' : (session.operario || rol === 'operario' ? 'Operario' : 'Cuadrilla'));
    const iniciales = nombre.trim().split(/\s+/).slice(0, 2).map(parte => parte.charAt(0)).join('').toUpperCase();
    menu.querySelector('#account-menu-trigger').textContent = iniciales || 'U';
    menu.querySelector('#account-menu-trigger').title = nombre;
    menu.querySelector('#account-menu-avatar').textContent = iniciales || 'U';
    menu.querySelector('#account-menu-name').textContent = nombre;
    menu.querySelector('#account-menu-role').textContent = esAdmin ? 'Administrador municipal' : (rol || 'Empleado');
    aplicarTema(localStorage.getItem(clavePreferenciaTema) === 'dark');

    document.querySelectorAll('.topbar .employee-panel-link, .topbar #admin-panel-link, #btn-panel-empleados, .boton-panel-empleados').forEach(link => link.remove());
    const adminName = document.getElementById('admin-name');
    if (adminName) adminName.remove();
    adminInfo.querySelector(':scope > span')?.remove();
}

function aplicarSesion(session) {
        if (!session.authenticated) {
            window.location.replace(paginaLogin);
            return;
        }

        const rol = (session.rol || '').toLowerCase().trim();
        const esAdmin = Boolean(session.admin || rol === 'admi_municipal');
        const esOperario = Boolean(session.operario || rol === 'operario');
        const esCuadrilla = Boolean(session.cuadrilla || ['chofer', 'limpiador'].includes(rol));

        const pathLower = window.location.pathname.toLowerCase();
        const enAreaOperario = pathLower.includes('/operario/');
        const enAreaCuadrilla = pathLower.includes('/cuadrilla/');
        const enConfiguracion = pathLower.endsWith('/configuracion.html');
        const enPaginaPrincipal = pathLower.endsWith('/index.php');
        const enPanelEmpleado = pathLower.includes('/empleados/') || enAreaOperario || enAreaCuadrilla;
        const esPaginaOperario = enAreaOperario || pathLower.endsWith('/operario.html');
        panelActual = esAdmin ? 'admin' : 'empleado';
        actualizarApiNotificaciones();

        // Protección de acceso al panel de Administrador
        if (!enPanelEmpleado && !enConfiguracion && !enPaginaPrincipal && !esAdmin) {
            window.location.replace(paginaInicio);
            return;
        }

        // Protección de acceso dentro de las áreas operativas
        if (enPanelEmpleado) {
            if (enAreaOperario && pathLower.endsWith('/informes.html') && !esAdmin) {
                window.location.replace('Panel.html');
                return;
            }

            if (enAreaOperario && !esAdmin && !esOperario) {
                window.location.replace(esCuadrilla ? '../Cuadrilla/Panel.html' : paginaInicio);
                return;
            }

            if (enAreaCuadrilla && !esAdmin && !esCuadrilla) {
                window.location.replace(esOperario ? '../Operario/Panel.html' : paginaInicio);
                return;
            }

            // Adaptar interfaz y navegación según rol
            const topbarTitle = document.querySelector('.topbar-title');

            if (enAreaOperario) {
                document.querySelectorAll('a[href="MapaInteractivo.html"], a[href="MiCuadrilla.html"], a[href="MiRuta.html"]').forEach(e => e.remove());
            }

            if (!esAdmin && esOperario) {
                // Modo Operario
                if (topbarTitle) topbarTitle.textContent = 'Panel operario';

                document.querySelectorAll('#operario-link').forEach(e => {
                    e.style.display = '';
                    if (esPaginaOperario) e.classList.add('active');
                });
            } else if (!esAdmin && esCuadrilla) {
                // Modo Cuadrilla
                if (topbarTitle) topbarTitle.textContent = 'Panel cuadrilla';

                // Ocultar módulo de Operario
                document.querySelectorAll('#operario-link, #operario-link-card, a[href="Operario.html"]').forEach(e => e.remove());
            } else if (esAdmin) {
                // Administrador municipal: acceso a ambos módulos
                if (topbarTitle) {
                    topbarTitle.textContent = esPaginaOperario ? 'Panel operario (Admin)' : 'Panel cuadrilla (Admin)';
                }
                document.querySelectorAll('#operario-link, #operario-link-card, a[href="Operario.html"]').forEach(e => {
                    e.style.display = '';
                });
            }
        }

        const adminName = document.getElementById('admin-name');
        if (adminName) {
            adminName.textContent = session.nombre || (esAdmin ? 'Administrador' : (esOperario ? 'Operario' : 'Cuadrilla'));
        }

        const rolActual = rol || (esAdmin ? 'admi_municipal' : (esOperario ? 'operario' : 'cuadrilla'));
        const nombreActual = session.nombre || (esAdmin ? 'Administrador' : (esOperario ? 'Operario' : 'Cuadrilla'));
        document.getElementById('config-account-name')?.replaceChildren(document.createTextNode(nombreActual));
        document.getElementById('config-account-role')?.replaceChildren(document.createTextNode(esAdmin ? 'Administrador municipal' : rolActual));
        const configForm = document.getElementById('config-form');
        const configNotifications = document.getElementById('config-notifications');
        if (configNotifications) {
            configNotifications.checked = localStorage.getItem(clavePreferenciaNotificaciones) !== 'false';
        }
        if (configForm && !configForm.dataset.listenerAttached) {
            configForm.dataset.listenerAttached = 'true';
            configForm.addEventListener('submit', event => {
                event.preventDefault();
                localStorage.setItem(clavePreferenciaNotificaciones, String(configNotifications.checked));
                window.location.reload();
            });
        }
        const passwordForm = document.getElementById('password-form');
        if (passwordForm && !passwordForm.dataset.listenerAttached) {
            passwordForm.dataset.listenerAttached = 'true';
            passwordForm.addEventListener('submit', async event => {
                event.preventDefault();
                const submitButton = passwordForm.querySelector('button[type="submit"]');
                const status = document.getElementById('password-status');
                const currentPassword = document.getElementById('password-current');
                const newPassword = document.getElementById('password-new');
                const confirmation = document.getElementById('password-confirm');
                submitButton.disabled = true;
                status.textContent = 'Actualizando contraseña...';
                status.dataset.status = '';

                try {
                    const response = await fetch(backendBase + 'apis/gestion/ApiGestion.php?recurso=contrasena', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            contrasena_actual: currentPassword.value,
                            contrasena_nueva: newPassword.value,
                            confirmacion: confirmation.value
                        })
                    });
                    const resultado = await response.json();
                    if (!response.ok || resultado.status !== 'success') {
                        throw new Error(resultado.message || 'No se pudo cambiar la contraseña.');
                    }

                    status.textContent = resultado.message;
                    status.dataset.status = 'success';
                    passwordForm.reset();
                } catch (error) {
                    status.textContent = error.message || 'No se pudo conectar para cambiar la contraseña.';
                    status.dataset.status = 'error';
                } finally {
                    submitButton.disabled = false;
                }
            });
        }

        crearMenuCuenta(session, esAdmin, rolActual);

        // Cargar modal selector si no está presente
        if (typeof window.abrirModalSeleccionPanel !== 'function' && !document.getElementById('script-panel-selector')) {
            const script = document.createElement('script');
            script.id = 'script-panel-selector';
            script.src = (enPanelEmpleado || pathLower.includes('/administrador/')) ? '../js/panel-selector.js?v=7' : 'js/panel-selector.js?v=7';
            document.head.appendChild(script);
        }

        crearCampana();
}

try {
    const guardada = JSON.parse(sessionStorage.getItem(claveSesion));
    if (guardada) document.addEventListener('DOMContentLoaded', () => aplicarSesion(guardada));
} catch (error) {}

fetch(backendBase + 'apis/gestion/ApiGestion.php?recurso=sesion', { credentials: 'same-origin' })
    .then(response => response.json())
    .then(session => {
        sessionStorage.setItem(claveSesion, JSON.stringify(session));
        aplicarSesion(session);
    })
    .catch(() => window.location.replace(paginaLogin));

document.getElementById('logout-btn')?.addEventListener('click', () => {
    sessionStorage.removeItem(claveSesion);
    window.location.assign(backendBase + 'cerrar_sesion.php');
});