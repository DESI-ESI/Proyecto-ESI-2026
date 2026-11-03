<?php session_start(); ?>
<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SIGERU | Gestión de Residuos Urbanos</title>

<link rel="stylesheet" href="Css\index.css">

</head>

<body>


<header class="navbar">

    <div class="navbar-contenido">

        <a href="#inicio" class="logo">
            <img src="css/img/logosigeru.png" alt="Logo SIGERU">
        </a>

        <div class="navbar-derecha">

            <nav class="menu">

                <a href="#inicio">Inicio</a>

                <a href="#nosotros">Nosotros</a>

                <a href="#contacto">Contacto</a>

            </nav>

            <div class="acciones">
                <?php if (isset($_SESSION['empleado_id'])): ?>
                    <span id="index-greeting">
                        Hola, <?php echo htmlspecialchars($_SESSION['nombre_empleado'] ?? 'Empleado'); ?>
                    </span>
                    <a href="#seleccionar-panel-empleado" class="boton boton-login boton-panel-empleados" id="btn-panel-empleados">
                        Elegir panel
                    </a>
                    <a href="../Backend/cerrar_sesion.php" class="boton boton-login" id="logout-btn">
                        Cerrar sesión
                    </a>
                <?php else: ?>
                    <a href="login.html" class="boton boton-login">
                        Iniciar sesión
                    </a>
                <?php endif; ?>
            </div>

        </div>

    </div>

</header>


<main>

    <section id="inicio" class="hero">

        <div class="hero-contenido">

            <p class="hero-etiqueta">
                SISTEMA INTELIGENTE DE GESTIÓN
            </p>

            <h1>
                Gestión inteligente de residuos urbanos
            </h1>

            <p class="hero-descripcion">
                SIGERU es una plataforma diseñada para mejorar
                la gestión de residuos urbanos, permitiendo
                administrar recursos, rutas, contenedores,
                incidencias y centros de acopio desde un
                único sistema.
            </p>

            <?php if (!isset($_SESSION['empleado_id'])): ?>
                <div class="hero-botones">

                    <a href="login.html" class="boton boton-login">
                        Iniciar sesión
                    </a>

                </div>
            <?php endif; ?>

        </div>

        <div class="hero-logo">

            <img src="css/img/logosigeru.png" alt="Logo SIGERU">

        </div>

    </section>


    <section id="nosotros" class="seccion">

        <div class="seccion-contenido">

            <p class="seccion-etiqueta">
                NOSOTROS
            </p>

            <h2>
                ¿Qué es SIGERU?
            </h2>

            <p class="seccion-texto">
                SIGERU es un sistema orientado a facilitar y
                optimizar la gestión de residuos urbanos.
            </p>

            <p class="seccion-texto">
                La plataforma permite centralizar la información
                relacionada con contenedores, camiones, rutas
                de recolección, incidencias y centros de acopio,
                facilitando la organización y el control del
                servicio.
            </p>

        </div>

    </section>


    <section class="seccion servicios">

        <div class="seccion-contenido">

            <p class="seccion-etiqueta">
                SERVICIOS
            </p>

            <h2>
                ¿Qué puedes gestionar?
            </h2>

            <div class="tarjetas">

                <article class="tarjeta">

                    <h3>
                        Contenedores
                    </h3>

                    <p>
                        Gestiona la información de los contenedores,
                        su ubicación, capacidad, estado y tipo de residuo.
                    </p>

                </article>

                <article class="tarjeta">

                    <h3>
                        Camiones
                    </h3>

                    <p>
                        Administra los vehículos utilizados para la
                        recolección y controla su disponibilidad.
                    </p>

                </article>

                <article class="tarjeta">

                    <h3>
                        Rutas
                    </h3>

                    <p>
                        Organiza y controla las rutas de recolección
                        de residuos urbanos.
                    </p>

                </article>

                <article class="tarjeta">

                    <h3>
                        Incidencias
                    </h3>

                    <p>
                        Registra y realiza el seguimiento de problemas
                        relacionados con el servicio de recolección.
                    </p>

                </article>

                <article class="tarjeta">

                    <h3>
                        Centros de acopio
                    </h3>

                    <p>
                        Gestiona los centros de acopio, su capacidad
                        y los recursos disponibles.
                    </p>

                </article>

                <article class="tarjeta">

                    <h3>
                        Reportes
                    </h3>

                    <p>
                        Obtén información para analizar el funcionamiento
                        del servicio y mejorar la toma de decisiones.
                    </p>

                </article>

            </div>

        </div>

    </section>


    <section id="contacto" class="seccion contacto">

        <div class="seccion-contenido">

            <p class="seccion-etiqueta">
                CONTACTO
            </p>

            <h2>
                Ponte en contacto
            </h2>

            <p class="seccion-texto">
                Si tienes alguna consulta o necesitas información
                sobre SIGERU, puedes comunicarte con nosotros.
            </p>

            <div class="contacto-info">

                <div class="contacto-item">

                    <h3>
                        email electrónico
                    </h3>

                    <p>
                        desi2026.esi@gmail.com
                    </p>

                </div>

                <div class="contacto-item">

                    <h3>
                        Sistema
                    </h3>

                    <p>
                        SIGERU - Gestión de Residuos Urbanos
                    </p>

                </div>

            </div>

        </div>

    </section>

</main>


<footer class="footer">

    <div class="footer-contenido">

        <p>
            © 2026 SIGERU
        </p>

        <p>
            Sistema Inteligente de Gestión de Residuos Urbanos
        </p>

    </div>

</footer>

<?php if (isset($_SESSION['empleado_id'])): 
    $rolActualIndex = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
?>
<script>
window.__SESION_USUARIO__ = {
    authenticated: true,
    rol: <?php echo json_encode($rolActualIndex); ?>,
    admin: <?php echo ($rolActualIndex === 'admi_municipal') ? 'true' : 'false'; ?>,
    operario: <?php echo ($rolActualIndex === 'operario') ? 'true' : 'false'; ?>,
    cuadrilla: <?php echo in_array($rolActualIndex, ['chofer', 'limpiador'], true) ? 'true' : 'false'; ?>,
    nombre: <?php echo json_encode($_SESSION['nombre_empleado'] ?? ''); ?>
};
</script>
<script src="js/panel-selector.js?v=7"></script>
<script src="js/admin-auth.js?v=26"></script>
<?php endif; ?>

</body>

</html>
