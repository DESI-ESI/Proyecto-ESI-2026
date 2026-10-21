// panel-selector.js - Modal de selección de panel de empleados (Cuadrilla / Operario)
(function () {
    const MODAL_ID = 'modal-seleccion-panel-sigeru';
    const CLAVE_SESION = 'sigeru_sesion';

    function obtenerRutaBase() {
        const path = window.location.pathname.toLowerCase();
        if (path.includes('/administrador/') || path.includes('/empleados/') ||
            path.includes('/operario/') || path.includes('/cuadrilla/')) return '../';
        return '';
    }

    function inyectarEstilos() {
        if (document.getElementById('estilos-modal-panel-sigeru')) return;
        const style = document.createElement('style');
        style.id = 'estilos-modal-panel-sigeru';
        style.textContent = `
            #${MODAL_ID} {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(10, 25, 18, 0.68);
                backdrop-filter: blur(5px);
                -webkit-backdrop-filter: blur(5px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                padding: 16px;
                box-sizing: border-box;
                animation: animOverlayFade 0.2s ease-out;
            }
            #${MODAL_ID} .modal-panel-box {
                position: relative;
                background: #ffffff;
                width: 100%;
                max-width: 900px;
                border-radius: 18px;
                padding: 32px 28px 26px;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.28);
                box-sizing: border-box;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                animation: animCardPop 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            }
            #${MODAL_ID} .btn-cerrar-modal {
                position: absolute;
                top: 14px;
                right: 18px;
                background: #f1f4f2;
                border: 1px solid #dfe5e1;
                border-radius: 50%;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                cursor: pointer;
                color: #55625c;
                line-height: 1;
                transition: all 0.15s ease;
            }
            #${MODAL_ID} .btn-cerrar-modal:hover {
                background: #e2e8e4;
                color: #1e2622;
            }
            #${MODAL_ID} .modal-header {
                text-align: center;
                margin-bottom: 24px;
            }
            #${MODAL_ID} .modal-header .modal-badge-top {
                display: inline-block;
                background: #e7f5ed;
                color: #238b57;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.6px;
                padding: 4px 12px;
                border-radius: 20px;
                margin-bottom: 8px;
                text-transform: uppercase;
            }
            #${MODAL_ID} .modal-header h2 {
                margin: 0 0 6px;
                font-size: 24px;
                color: #1b2621;
                font-weight: 700;
            }
            #${MODAL_ID} .modal-header p {
                margin: 0;
                font-size: 14px;
                color: #617068;
            }
            #${MODAL_ID} .modal-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 18px;
            }
            #${MODAL_ID} .modal-footer {
                margin-top: 20px;
                text-align: center;
            }
            #${MODAL_ID} .pagina-principal-link {
                color: #238b57;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
            }
            #${MODAL_ID} .pagina-principal-link:hover {
                text-decoration: underline;
            }
            @media (max-width: 760px) {
                #${MODAL_ID} .modal-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }
            @media (max-width: 560px) {
                #${MODAL_ID} .modal-grid {
                    grid-template-columns: 1fr;
                }
            }
            #${MODAL_ID} .modulo-card {
                position: relative;
                background: #ffffff;
                border: 2px solid #dce4e0;
                border-radius: 14px;
                padding: 22px 18px;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                transition: all 0.2s ease;
                box-sizing: border-box;
            }
            #${MODAL_ID} .modulo-card.habilitada {
                cursor: pointer;
            }
            #${MODAL_ID} .modulo-card.habilitada:hover {
                border-color: #238b57;
                box-shadow: 0 8px 24px rgba(35, 139, 87, 0.16);
                transform: translateY(-3px);
                background: #fbfdfc;
            }
            #${MODAL_ID} .modulo-card.bloqueada {
                background: #f7f9f8;
                border: 2px dashed #d1dbd5;
                opacity: 0.72;
                cursor: not-allowed;
            }
            #${MODAL_ID} .modulo-icono {
                width: 58px;
                height: 58px;
                border-radius: 12px;
                background: #eaf6ef;
                color: #238b57;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 28px;
                margin-bottom: 12px;
                transition: transform 0.2s ease;
            }
            #${MODAL_ID} .modulo-card.bloqueada .modulo-icono {
                background: #ecefe0;
                color: #8c9791;
            }
            #${MODAL_ID} .modulo-card.habilitada:hover .modulo-icono {
                transform: scale(1.06);
            }
            #${MODAL_ID} .modulo-card h3 {
                margin: 0 0 6px;
                font-size: 18px;
                color: #1f2a24;
                font-weight: 700;
            }
            #${MODAL_ID} .modulo-card p {
                margin: 0 0 14px;
                font-size: 12px;
                line-height: 1.45;
                color: #617068;
                flex-grow: 1;
            }
            #${MODAL_ID} .modulo-tag {
                font-size: 11px;
                font-weight: 600;
                padding: 4px 10px;
                border-radius: 6px;
                margin-bottom: 14px;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            #${MODAL_ID} .modulo-card.habilitada .modulo-tag {
                background: #e7f5ed;
                color: #1b6d43;
            }
            #${MODAL_ID} .modulo-card.bloqueada .modulo-tag {
                background: #fde8e8;
                color: #c81e1e;
            }
            #${MODAL_ID} .btn-modulo {
                width: 100%;
                padding: 10px 14px;
                border-radius: 9px;
                font-size: 13px;
                font-weight: 600;
                border: none;
                cursor: pointer;
                transition: background 0.15s ease;
            }
            #${MODAL_ID} .modulo-card.habilitada .btn-modulo {
                background: #238b57;
                color: #ffffff;
            }
            #${MODAL_ID} .modulo-card.habilitada .btn-modulo:hover {
                background: #1b6d43;
            }
            #${MODAL_ID} .modulo-card.bloqueada .btn-modulo {
                background: #bcc8c1;
                color: #ffffff;
                cursor: not-allowed;
            }
            #${MODAL_ID} .modal-aviso {
                margin-top: 18px;
                padding: 11px 16px;
                border-radius: 10px;
                font-size: 13px;
                background: #fef2f2;
                border: 1px solid #fecaca;
                color: #991b1b;
                display: none;
                text-align: center;
                animation: animCardPop 0.18s ease-out;
            }
            @keyframes animOverlayFade {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes animCardPop {
                from { opacity: 0; transform: scale(0.96); }
                to { opacity: 1; transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    }

    async function resolverSesion() {
        if (window.__SESION_USUARIO__ && typeof window.__SESION_USUARIO__ === 'object') {
            return window.__SESION_USUARIO__;
        }
        try {
            const guardada = JSON.parse(sessionStorage.getItem(CLAVE_SESION));
            if (guardada && guardada.authenticated) return guardada;
        } catch (e) {}

        try {
            const res = await fetch('/Proyecto-ESI-2026/Backend/apis/gestion/ApiGestion.php?recurso=sesion', {
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (data && data.authenticated) {
                sessionStorage.setItem(CLAVE_SESION, JSON.stringify(data));
                return data;
            }
        } catch (e) {}

        return { authenticated: false };
    }

    function abrirModal(sesion) {
        inyectarEstilos();
        const base = obtenerRutaBase();
        let modal = document.getElementById(MODAL_ID);
        if (modal) modal.remove();

        modal = document.createElement('div');
        modal.id = MODAL_ID;

        const rol = (sesion && sesion.rol ? String(sesion.rol).toLowerCase().trim() : '');
        const esAdmin = Boolean(sesion && (sesion.admin || rol === 'admi_municipal'));
        const esOperario = Boolean(sesion && (sesion.operario || rol === 'operario'));
        const esCuadrilla = Boolean(sesion && (sesion.cuadrilla || ['chofer', 'limpiador'].includes(rol)));

        const puedeCuadrilla = esAdmin || esCuadrilla;
        const puedeOperario = esAdmin || esOperario;

        modal.innerHTML = `
            <div class="modal-panel-box" role="dialog" aria-modal="true" aria-labelledby="modal-panel-titulo">
                <button type="button" class="btn-cerrar-modal" id="btn-cerrar-panel-sigeru" aria-label="Cerrar">&times;</button>
                <div class="modal-header">
                    <span class="modal-badge-top">Área operativa</span>
                    <h2 id="modal-panel-titulo">Elegir panel</h2>
                    <p>Seleccioná el módulo al que querés ingresar:</p>
                </div>
                <div class="modal-grid">
                    <div class="modulo-card ${esAdmin ? 'habilitada' : 'bloqueada'}" id="card-modulo-admin">
                        <div class="modulo-icono">⚙</div>
                        <h3>Administrador</h3>
                        <p>Administración general de SIGERU, empleados, rutas e instalaciones.</p>
                        <div class="modulo-tag">${esAdmin ? '✓ Disponible' : '🔒 Solo Administrador'}</div>
                        <button type="button" class="btn-modulo" id="btn-modulo-admin">
                            ${esAdmin ? 'Ingresar a Administrador' : 'Restringido'}
                        </button>
                    </div>

                    <!-- Opción Cuadrilla -->
                    <div class="modulo-card ${puedeCuadrilla ? 'habilitada' : 'bloqueada'}" id="card-modulo-cuadrilla">
                        <div class="modulo-icono">🚚</div>
                        <h3>Cuadrilla</h3>
                        <p>Rutas de recolección, integrantes, mapa interactivo, contenedores e incidencias.</p>
                        <div class="modulo-tag">${puedeCuadrilla ? '✓ Disponible' : '🔒 Solo Chofer y Limpiador'}</div>
                        <button type="button" class="btn-modulo" id="btn-modulo-cuadrilla">
                            ${puedeCuadrilla ? 'Ingresar a Cuadrilla' : 'Restringido'}
                        </button>
                    </div>

                    <!-- Opción Operario -->
                    <div class="modulo-card ${puedeOperario ? 'habilitada' : 'bloqueada'}" id="card-modulo-operario">
                        <div class="modulo-icono">▦</div>
                        <h3>Operario</h3>
                        <p>Centro de acopio, registro y clasificación de residuos y control de capacidad.</p>
                        <div class="modulo-tag">${puedeOperario ? '✓ Disponible' : '🔒 Solo Operario'}</div>
                        <button type="button" class="btn-modulo" id="btn-modulo-operario">
                            ${puedeOperario ? 'Ingresar a Operario' : 'Restringido'}
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="pagina-principal-link" href="${base}index.php">Ir a la página principal</a>
                </div>
                <div id="modal-aviso-panel" class="modal-aviso"></div>
            </div>
        `;

        document.body.appendChild(modal);

        const aviso = modal.querySelector('#modal-aviso-panel');
        const mostrarAviso = msg => {
            if (!aviso) return;
            aviso.textContent = msg;
            aviso.style.display = 'block';
        };

        const cerrar = () => {
            modal.remove();
            document.removeEventListener('keydown', teclaEscape);
        };

        const teclaEscape = e => {
            if (e.key === 'Escape') cerrar();
        };
        document.addEventListener('keydown', teclaEscape);

        modal.querySelector('#btn-cerrar-panel-sigeru').addEventListener('click', cerrar);
        modal.addEventListener('click', e => {
            if (e.target === modal) cerrar();
        });

        // Click en Cuadrilla
        const irCuadrilla = () => {
            if (puedeCuadrilla) {
                window.location.assign(base + 'Cuadrilla/Panel.html');
            } else {
                mostrarAviso('Acceso denegado: Tu rol actual (' + (rol || 'sin rol') + ') no tiene permisos para el panel de Cuadrilla. Solo choferes, limpiadores o administradores pueden ingresar.');
            }
        };
        modal.querySelector('#card-modulo-cuadrilla').addEventListener('click', irCuadrilla);

        const irAdministrador = () => {
            if (esAdmin) {
                window.location.assign(base + 'Administrador/Panel.html');
            } else {
                mostrarAviso('Acceso denegado: solo los administradores municipales pueden ingresar a este panel.');
            }
        };
        modal.querySelector('#card-modulo-admin').addEventListener('click', irAdministrador);

        // Click en Operario
        const irOperario = () => {
            if (puedeOperario) {
                window.location.assign(base + 'Operario/Panel.html');
            } else {
                mostrarAviso('Acceso denegado: Tu rol actual (' + (rol || 'sin rol') + ') no tiene permisos para el Centro de Acopio. Solo operarios o administradores pueden ingresar.');
            }
        };
        modal.querySelector('#card-modulo-operario').addEventListener('click', irOperario);
    }

    window.abrirModalSeleccionPanel = async function (sesionPersonalizada) {
        const sesion = sesionPersonalizada || await resolverSesion();
        abrirModal(sesion);
    };

    function asociarEventos() {
        const selectores = [
            '#btn-panel-empleados',
            '.boton-panel-empleados',
            '.employee-panel-link',
            '.account-menu-select-panel'
        ];

        document.addEventListener('click', async e => {
            const elemento = e.target.closest(selectores.join(','));
            if (!elemento) return;

            // Si es un enlace hacia panel de empleados o tiene la clase, interceptar
            const href = (elemento.getAttribute('href') || '').toLowerCase();
            if (href.includes('empleados/panel.html') || elemento.matches('#btn-panel-empleados, .boton-panel-empleados, .employee-panel-link, .account-menu-select-panel')) {
                e.preventDefault();
                e.stopPropagation();
                window.abrirModalSeleccionPanel();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', asociarEventos);
    } else {
        asociarEventos();
    }

    function abrirSelectorDesdeHash() {
        if (window.location.hash === '#seleccionar-panel-empleado') {
            window.abrirModalSeleccionPanel();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', abrirSelectorDesdeHash);
    } else {
        abrirSelectorDesdeHash();
    }
})();
