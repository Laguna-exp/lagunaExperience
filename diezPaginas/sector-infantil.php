<?php

session_start();

require_once __DIR__ . "/../conexion.php";

/* =========================================================
   CERRAR SESIÓN
========================================================= */

if (
    isset($_GET["cerrar"]) &&
    $_GET["cerrar"] == "1"
) {
    $_SESSION = array();

    session_unset();

    session_destroy();

    header("Location: spa.php");

    exit;
}

/* =========================================================
   SI YA HAY UNA SESIÓN GENERAL, USARLA EN SPA
========================================================= */

if (
    isset($_SESSION["usuario_id"]) &&
    isset($_SESSION["usuario"]) &&
    !isset($_SESSION["spa_usuario_id"])
) {
    $_SESSION["spa_usuario_id"] = $_SESSION["usuario_id"];
    $_SESSION["spa_usuario_nombre"] = $_SESSION["usuario"];
}

/* =========================================================
   CREAR TABLA DE SERVICIOS
========================================================= */

mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS spa_servicios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(10) NOT NULL UNIQUE,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT NOT NULL,
        duracion INT NOT NULL,
        precio DECIMAL(10,2) NOT NULL,
        cupos INT NOT NULL DEFAULT 0,
        activo TINYINT(1) NOT NULL DEFAULT 1
    )
");

/* =========================================================
   CREAR TABLA DE RESERVAS
========================================================= */

mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS spa_reservas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        servicio_id INT NOT NULL,
        fecha DATE NOT NULL,
        horario TIME NOT NULL,
        codigo VARCHAR(30) NOT NULL UNIQUE,
        estado ENUM('confirmada','cancelada') NOT NULL DEFAULT 'confirmada',
        creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");

/* =========================================================
   CARGAR SERVICIOS INICIALES
========================================================= */

$consultaCantidad = mysqli_query(
    $conexion,
    "SELECT COUNT(*) AS cantidad FROM spa_servicios"
);

$filaCantidad = mysqli_fetch_assoc(
    $consultaCantidad
);

if ((int)$filaCantidad["cantidad"] === 0) {

    mysqli_query($conexion, "
        INSERT INTO spa_servicios
        (
            codigo,
            nombre,
            descripcion,
            duracion,
            precio,
            cupos
        )
        VALUES
        (
            'SP1',
            'Masaje Bosque',
            'Masaje relajante en un ambiente tranquilo y natural.',
            60,
            18000,
            4
        ),
        (
            'SP2',
            'Facial Relax',
            'Tratamiento facial pensado para brindar relajación y cuidado.',
            45,
            15000,
            5
        ),
        (
            'SP3',
            'Circuito Wellness',
            'Experiencia de relajación que combina diferentes espacios del Spa.',
            90,
            25000,
            3
        )
    ");
}

/* =========================================================
   FUNCIONES
========================================================= */

function mostrarMensaje($texto, $tipo)
{
    $_SESSION["mensaje_spa"] = $texto;
    $_SESSION["tipo_mensaje_spa"] = $tipo;
}

/* =========================================================
   LOGIN
========================================================= */

if (
    isset($_POST["accion"]) &&
    $_POST["accion"] === "identificar"
) {

    $usuario = trim(
        $_POST["usuario"] ?? ""
    );

    $password = trim(
        $_POST["password"] ?? ""
    );

    if (
        $usuario === "" ||
        $password === ""
    ) {

        mostrarMensaje(
            "Ingresá tu usuario y contraseña para continuar.",
            "bad"
        );

    } else {

        $usuarioSeguro = mysqli_real_escape_string(
            $conexion,
            $usuario
        );

        $consultaUsuario = mysqli_query(
            $conexion,
            "
            SELECT *
            FROM usuarios
            WHERE activo = 1
            AND (
                nombre = '$usuarioSeguro'
                OR email = '$usuarioSeguro'
            )
            LIMIT 1
            "
        );

        if (
            $consultaUsuario &&
            mysqli_num_rows($consultaUsuario) === 1
        ) {

            $datosUsuario = mysqli_fetch_assoc(
                $consultaUsuario
            );

            $passwordCorrecta = false;

            /* PASSWORD HASH */

            if (
                isset($datosUsuario["password_hash"]) &&
                password_verify(
                    $password,
                    $datosUsuario["password_hash"]
                )
            ) {
                $passwordCorrecta = true;
            }

            /* PASSWORD NORMAL */

            if (
                isset($datosUsuario["password"]) &&
                $password === $datosUsuario["password"]
            ) {
                $passwordCorrecta = true;
            }

            /* PASSWORD HASH GUARDADA COMO TEXTO */

            if (
                isset($datosUsuario["password_hash"]) &&
                $password === $datosUsuario["password_hash"]
            ) {
                $passwordCorrecta = true;
            }

            if ($passwordCorrecta) {

                $_SESSION["spa_usuario_id"] =
                    $datosUsuario["id"];

                $_SESSION["spa_usuario_nombre"] =
                    $datosUsuario["nombre"];

                /* SESIÓN GENERAL */

                $_SESSION["usuario_id"] =
                    $datosUsuario["id"];

                $_SESSION["usuario"] =
                    $datosUsuario["nombre"];

                $_SESSION["email"] =
                    $datosUsuario["email"];

                if (isset($datosUsuario["rol"])) {
                    $_SESSION["rol"] =
                        $datosUsuario["rol"];
                }

                mostrarMensaje(
                    "Inicio de sesión correcto. Ya podés reservar.",
                    "ok"
                );

            } else {

                mostrarMensaje(
                    "Usuario o contraseña incorrectos.",
                    "bad"
                );
            }

        } else {

            mostrarMensaje(
                "Usuario o contraseña incorrectos.",
                "bad"
            );
        }
    }

    header(
        "Location: " .
        $_SERVER["PHP_SELF"] .
        "#reservar"
    );

    exit;
}

/* =========================================================
   RESERVAR SERVICIO
========================================================= */

if (
    isset($_POST["accion"]) &&
    $_POST["accion"] === "reservar"
) {

    if (
        !isset($_SESSION["spa_usuario_id"]) ||
        !isset($_SESSION["spa_usuario_nombre"])
    ) {

        mostrarMensaje(
            "Primero tenés que iniciar sesión.",
            "bad"
        );

        header(
            "Location: " .
            $_SERVER["PHP_SELF"] .
            "#reservar"
        );

        exit;
    }

    $usuarioId =
        (int)$_SESSION["spa_usuario_id"];

    $usuario =
        $_SESSION["spa_usuario_nombre"];

    $servicioId =
        (int)($_POST["servicio"] ?? 0);

    $fecha =
        trim($_POST["fecha"] ?? "");

    $horario =
        trim($_POST["horario"] ?? "");

    /* =====================================================
       BUSCAR SERVICIO
    ===================================================== */

    $consultaServicio = mysqli_query(
        $conexion,
        "
        SELECT *
        FROM spa_servicios
        WHERE id = $servicioId
        AND activo = 1
        LIMIT 1
        "
    );

    if (
        !$consultaServicio ||
        mysqli_num_rows($consultaServicio) === 0
    ) {

        mostrarMensaje(
            "Seleccioná un servicio válido.",
            "bad"
        );

        header(
            "Location: " .
            $_SERVER["PHP_SELF"] .
            "#reservar"
        );

        exit;
    }

    $servicio =
        mysqli_fetch_assoc(
            $consultaServicio
        );

    /* =====================================================
       FECHA
    ===================================================== */

    if ($fecha === "") {

        mostrarMensaje(
            "Seleccioná una fecha.",
            "bad"
        );

        header(
            "Location: " .
            $_SERVER["PHP_SELF"] .
            "#reservar"
        );

        exit;
    }

    $fechaActual =
        date("Y-m-d");

    if ($fecha < $fechaActual) {

        mostrarMensaje(
            "La fecha seleccionada no puede ser anterior a hoy.",
            "bad"
        );

        header(
            "Location: " .
            $_SERVER["PHP_SELF"] .
            "#reservar"
        );

        exit;
    }

    /* =====================================================
       HORARIO
    ===================================================== */

    if ($horario === "") {

        mostrarMensaje(
            "Seleccioná un horario.",
            "bad"
        );

        header(
            "Location: " .
            $_SERVER["PHP_SELF"] .
            "#reservar"
        );

        exit;
    }

    if (
        $horario < "10:00" ||
        $horario > "20:00"
    ) {

        mostrarMensaje(
            "El horario debe estar entre las 10:00 y las 20:00.",
            "bad"
        );

        header(
            "Location: " .
            $_SERVER["PHP_SELF"] .
            "#reservar"
        );

        exit;
    }

    /* =====================================================
       CUPOS
    ===================================================== */

    if (
        (int)$servicio["cupos"] <= 0
    ) {

        mostrarMensaje(
            "Este servicio ya no tiene cupos disponibles.",
            "bad"
        );

        header(
            "Location: " .
            $_SERVER["PHP_SELF"] .
            "#reservar"
        );

        exit;
    }

    /* =====================================================
       VERIFICAR RESERVA EXISTENTE
    ===================================================== */

    $fechaSeguro =
        mysqli_real_escape_string(
            $conexion,
            $fecha
        );

    $horarioSeguro =
        mysqli_real_escape_string(
            $conexion,
            $horario
        );

    $consultaReserva = mysqli_query(
        $conexion,
        "
        SELECT id
        FROM spa_reservas
        WHERE servicio_id = $servicioId
        AND fecha = '$fechaSeguro'
        AND horario = '$horarioSeguro'
        AND estado = 'confirmada'
        LIMIT 1
        "
    );

    if (
        $consultaReserva &&
        mysqli_num_rows($consultaReserva) > 0
    ) {

        mostrarMensaje(
            "Ese horario ya está reservado para este servicio.",
            "bad"
        );

        header(
            "Location: " .
            $_SERVER["PHP_SELF"] .
            "#reservar"
        );

        exit;
    }

    /* =====================================================
       TRANSACCIÓN
    ===================================================== */

    mysqli_begin_transaction($conexion);

    try {

        /* RESTAR CUPO */

        $actualizarCupos =
            mysqli_query(
                $conexion,
                "
                UPDATE spa_servicios
                SET cupos = cupos - 1
                WHERE id = $servicioId
                AND cupos > 0
                "
            );

        if (
            !$actualizarCupos ||
            mysqli_affected_rows($conexion) === 0
        ) {

            throw new Exception(
                "No quedan cupos disponibles."
            );
        }

        /* =================================================
           GENERAR CÓDIGO
        ================================================= */

        do {

            $codigo =
                "SPA-" .
                date("ymd") .
                "-" .
                rand(1000, 9999);

            $codigoSeguro =
                mysqli_real_escape_string(
                    $conexion,
                    $codigo
                );

            $buscarCodigo =
                mysqli_query(
                    $conexion,
                    "
                    SELECT id
                    FROM spa_reservas
                    WHERE codigo = '$codigoSeguro'
                    LIMIT 1
                    "
                );

        } while (
            $buscarCodigo &&
            mysqli_num_rows($buscarCodigo) > 0
        );

        /* =================================================
           INSERTAR RESERVA
        ================================================= */

        $insertar =
            mysqli_query(
                $conexion,
                "
                INSERT INTO spa_reservas
                (
                    usuario_id,
                    servicio_id,
                    fecha,
                    horario,
                    codigo,
                    estado
                )
                VALUES
                (
                    $usuarioId,
                    $servicioId,
                    '$fechaSeguro',
                    '$horarioSeguro',
                    '$codigoSeguro',
                    'confirmada'
                )
                "
            );

        if (!$insertar) {

            throw new Exception(
                "No se pudo guardar la reserva."
            );
        }

        $reservaId =
            mysqli_insert_id(
                $conexion
            );

        mysqli_commit(
            $conexion
        );

        /* =================================================
           GUARDAR ÚLTIMA RESERVA
        ================================================= */

        $_SESSION["ultima_reserva_spa"] = [

            "codigo" =>
                $codigo,

            "usuario" =>
                $usuario,

            "servicio" =>
                $servicio["nombre"],

            "fecha" =>
                $fecha,

            "horario" =>
                $horario,

            "duracion" =>
                $servicio["duracion"],

            "precio" =>
                $servicio["precio"]
        ];

        mostrarMensaje(
            "La reserva fue registrada correctamente.",
            "ok"
        );

        header(
            "Location: " .
            $_SERVER["PHP_SELF"] .
            "?reserva=" .
            $reservaId .
            "#confirmacion"
        );

        exit;

    } catch (Exception $e) {

        mysqli_rollback(
            $conexion
        );

        mostrarMensaje(
            $e->getMessage(),
            "bad"
        );

        header(
            "Location: " .
            $_SERVER["PHP_SELF"] .
            "#reservar"
        );

        exit;
    }
}

/* =========================================================
   SERVICIOS
========================================================= */

$servicios = [];

$consultaServicios =
    mysqli_query(
        $conexion,
        "
        SELECT *
        FROM spa_servicios
        WHERE activo = 1
        ORDER BY id ASC
        "
    );

if ($consultaServicios) {

    while (
        $servicioFila =
        mysqli_fetch_assoc(
            $consultaServicios
        )
    ) {

        $servicios[] =
            $servicioFila;
    }
}

/* =========================================================
   ESTADÍSTICAS
========================================================= */

$cantidadServicios =
    count($servicios);

$capacidadMaxima = 0;
$duracionMinima = 0;
$duracionMaxima = 0;

if ($cantidadServicios > 0) {

    $cupos = [];
    $duraciones = [];

    foreach (
        $servicios as $servicio
    ) {

        $cupos[] =
            (int)$servicio["cupos"];

        $duraciones[] =
            (int)$servicio["duracion"];
    }

    $capacidadMaxima =
        max($cupos);

    $duracionMinima =
        min($duraciones);

    $duracionMaxima =
        max($duraciones);
}

/* =========================================================
   MODALIDADES
========================================================= */

$tiposServicio = 0;

if ($cantidadServicios > 0) {

    $tiposServicio =
        $cantidadServicios;
}

/* =========================================================
   USUARIO ACTUAL
========================================================= */

$usuarioActual =
    $_SESSION["spa_usuario_nombre"]
    ?? $_SESSION["usuario"]
    ?? "";

$usuarioActualId =
    $_SESSION["spa_usuario_id"]
    ?? $_SESSION["usuario_id"]
    ?? "";

/* =========================================================
   MENSAJE
========================================================= */

$mensaje =
    $_SESSION["mensaje_spa"]
    ?? "";

$tipoMensaje =
    $_SESSION["tipo_mensaje_spa"]
    ?? "";

unset(
    $_SESSION["mensaje_spa"],
    $_SESSION["tipo_mensaje_spa"]
);

/* =========================================================
   ÚLTIMA RESERVA
========================================================= */

$ultimaReserva =
    $_SESSION["ultima_reserva_spa"]
    ?? null;

$mostrarConfirmacion = false;

if (
    isset($_GET["reserva"]) &&
    $ultimaReserva
) {

    $mostrarConfirmacion = true;
}

/* =========================================================
   SERVICIO SELECCIONADO
========================================================= */

$servicioSeleccionado =
    isset($_GET["servicio"])
        ? (int)$_GET["servicio"]
        : 0;

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Spa & Wellness | Laguna Experience
</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap"
    rel="stylesheet"
>

<style>

:root{

    --deep:#5C2A4A;
    --mid:#8A4468;
    --sand:#FBF3F1;
    --sand2:#FBEAE3;
    --ink:#2E1A26;
    --soft:#8A6B7C;
    --accent:#E97C6B;
    --accent-dark:#D65F4C;
    --ok:#4C9A6A;
    --ok-light:#E5F3EA;
    --bad:#C24A3A;
    --bad-light:#FBE8E4;
    --warn:#C98A2B;
    --warn-light:#FDF0DD;
    --line:#F1DCD2;
    --radius:18px;

    font-family:'Inter',sans-serif;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{

    min-height:100vh;

    background:
        linear-gradient(
            180deg,
            var(--sand) 0%,
            var(--sand2) 100%
        );

    color:var(--ink);

    font-family:
        "DM Sans",
        sans-serif;

    overflow-x:hidden;
}

a{
    color:inherit;
    text-decoration:none;
}

button,
input,
select{
    font-family:inherit;
}

/* =====================================================
   HEADER
===================================================== */

header{

    position:relative;

    min-height:510px;

    display:flex;
    align-items:center;

    padding:
        80px 8%;

    overflow:hidden;

    color:white;

    background:
        linear-gradient(
            90deg,
            rgba(92,42,74,.96),
            rgba(138,68,104,.82),
            rgba(233,124,107,.36)
        ),
        url("https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=2000&q=90")
        center / cover no-repeat;
}

header::before{

    content:"";

    position:absolute;

    width:520px;
    height:520px;

    top:-290px;
    right:-130px;

    border-radius:50%;

    background:
        rgba(233,124,107,.18);

    filter:blur(10px);
}

header::after{

    content:"";

    position:absolute;

    inset:0;

    pointer-events:none;

    background:
        linear-gradient(
            180deg,
            transparent 55%,
            rgba(92,42,74,.62)
        );
}

.header-content{

    position:relative;

    z-index:2;

    max-width:760px;
}

.eyebrow{

    margin-bottom:17px;

    color:#FBEAE3;

    font-size:11px;

    font-weight:800;

    letter-spacing:.2em;

    text-transform:uppercase;
}

header h1{

    margin-bottom:22px;

    color:white;

    font-family:
        "Manrope",
        sans-serif;

    font-size:
        clamp(
            52px,
            8vw,
            100px
        );

    font-weight:800;

    line-height:.92;

    letter-spacing:-.07em;
}

header h1 span{

    display:block;

    color:#FBEAE3;
}

header p{

    max-width:650px;

    margin-bottom:30px;

    color:
        rgba(255,255,255,.84);

    font-size:16px;

    line-height:1.7;
}

.back-button{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:48px;

    padding:
        0 22px;

    border-radius:14px;

    background:white;

    color:var(--deep);

    font-size:13px;

    font-weight:800;

    box-shadow:
        0 15px 35px
        rgba(0,0,0,.18);

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        background .25s ease;
}

.back-button:hover{

    transform:
        translateY(-3px);

    box-shadow:
        0 20px 45px
        rgba(0,0,0,.25);

    background:#fffaf8;
}

/* =====================================================
   MAIN
===================================================== */

main{

    position:relative;

    z-index:3;

    max-width:1250px;

    margin:
        -45px auto 80px;

    padding:
        0 5%;
}

.hero-card{

    margin-bottom:25px;

    padding:32px;

    background:
        rgba(255,255,255,.96);

    border:
        1px solid var(--line);

    border-radius:26px;

    box-shadow:
        0 24px 70px
        rgba(92,42,74,.12);

    transition:
        transform .3s ease,
        box-shadow .3s ease;
}

.hero-card:hover{

    transform:
        translateY(-4px);

    box-shadow:
        0 30px 75px
        rgba(92,42,74,.15);
}

.hero-card h2{

    margin-bottom:10px;

    color:var(--deep);

    font-family:
        "Manrope",
        sans-serif;

    font-size:34px;

    font-weight:800;

    letter-spacing:-.05em;
}

.hero-card p{

    max-width:850px;

    color:
        rgba(46,26,38,.64);

    line-height:1.75;

    font-size:14px;
}

/* =====================================================
   STATS
===================================================== */

.stats{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:16px;

    margin-bottom:25px;
}

.stat{

    padding:24px;

    text-align:center;

    background:
        rgba(255,255,255,.96);

    border:
        1px solid var(--line);

    border-radius:18px;

    box-shadow:
        0 24px 70px
        rgba(92,42,74,.12);

    transition:
        transform .3s ease;
}

.stat:hover{

    transform:
        translateY(-5px);
}

.stat-number{

    color:var(--deep);

    font-family:
        "Manrope",
        sans-serif;

    font-size:35px;

    font-weight:800;
}

.stat-label{

    margin-top:5px;

    color:var(--soft);

    font-size:10px;

    font-weight:800;

    letter-spacing:.12em;

    text-transform:uppercase;
}

/* =====================================================
   SECCIONES
===================================================== */

.section{

    margin-bottom:25px;

    padding:30px;

    background:
        rgba(255,255,255,.96);

    border:
        1px solid var(--line);

    border-radius:26px;

    box-shadow:
        0 24px 70px
        rgba(92,42,74,.12);
}

.section-title{

    margin-bottom:8px;

    color:var(--deep);

    font-family:
        "Manrope",
        sans-serif;

    font-size:30px;

    font-weight:800;

    letter-spacing:-.05em;
}

.section-description{

    max-width:850px;

    margin-bottom:24px;

    color:var(--soft);

    font-size:14px;

    line-height:1.7;
}

/* =====================================================
   CARDS
===================================================== */

.cards{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:16px;
}

.card{

    padding:22px;

    background:
        linear-gradient(
            145deg,
            #ffffff,
            var(--sand2)
        );

    border:
        1px solid var(--line);

    border-radius:18px;

    transition:
        transform .3s ease,
        box-shadow .3s ease;
}

.card:hover{

    transform:
        translateY(-6px);

    box-shadow:
        0 20px 48px
        rgba(92,42,74,.11);
}

.card h3{

    margin-bottom:8px;

    color:var(--deep);

    font-family:
        "Manrope",
        sans-serif;

    font-size:19px;

    font-weight:800;
}

.card p{

    color:var(--soft);

    font-size:13px;

    line-height:1.65;
}

/* =====================================================
   SERVICIOS
===================================================== */

.service-grid{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:16px;
}

.service-card{

    padding:22px;

    background:white;

    border:
        1px solid var(--line);

    border-radius:18px;

    transition:
        transform .3s ease,
        box-shadow .3s ease;
}

.service-card:hover{

    transform:
        translateY(-6px);

    box-shadow:
        0 20px 48px
        rgba(92,42,74,.12);
}

.service-code{

    margin-bottom:10px;

    color:var(--accent);

    font-size:10px;

    font-weight:800;

    letter-spacing:.12em;

    text-transform:uppercase;
}

.service-card h3{

    margin-bottom:9px;

    color:var(--deep);

    font-family:
        "Manrope",
        sans-serif;

    font-size:21px;

    font-weight:800;
}

.service-card p{

    min-height:65px;

    margin-bottom:17px;

    color:var(--soft);

    font-size:13px;

    line-height:1.65;
}

.service-info{

    display:flex;

    flex-wrap:wrap;

    gap:7px;
}

.badge{

    display:inline-flex;

    align-items:center;

    padding:
        6px 10px;

    border-radius:999px;

    font-size:10px;

    font-weight:800;
}

.badge-duration{

    background:var(--sand2);

    color:var(--deep);
}

.badge-price{

    background:var(--warn-light);

    color:var(--warn);
}

.badge-cupos{

    background:var(--ok-light);

    color:var(--ok);
}

.cupos{

    margin-top:13px;

    color:var(--soft);

    font-size:12px;
}

.cupos strong{

    color:var(--deep);
}

.service-button{

    width:100%;

    min-height:46px;

    margin-top:18px;

    padding:
        0 16px;

    border:none;

    border-radius:12px;

    background:var(--accent);

    color:white;

    font-size:13px;

    font-weight:800;

    cursor:pointer;

    transition:
        transform .2s ease,
        background .2s ease;
}

.service-button:hover{

    transform:
        translateY(-2px);

    background:var(--accent-dark);
}

.service-button.disabled{

    background:#ddd;

    color:#888;

    cursor:not-allowed;
}

/* =====================================================
   RESERVACIÓN
===================================================== */

.reservation{

    margin-bottom:25px;

    padding:32px;

    background:
        linear-gradient(
            145deg,
            var(--sand2),
            #ffffff
        );

    border:
        1px solid var(--line);

    border-radius:26px;

    box-shadow:
        0 24px 70px
        rgba(92,42,74,.12);
}

.reservation h2{

    margin-bottom:8px;

    color:var(--deep);

    font-family:
        "Manrope",
        sans-serif;

    font-size:34px;

    font-weight:800;

    letter-spacing:-.05em;
}

.reservation-description{

    margin-bottom:25px;

    color:var(--soft);

    font-size:14px;

    line-height:1.7;
}

/* =====================================================
   LOGIN
===================================================== */

.login-box{

    margin-bottom:22px;

    padding:24px;

    background:
        rgba(255,255,255,.70);

    border:
        1px solid var(--line);

    border-radius:18px;
}

.login-box h3{

    margin-bottom:7px;

    color:var(--deep);

    font-family:
        "Manrope",
        sans-serif;

    font-size:22px;

    font-weight:800;
}

.login-box p{

    margin-bottom:18px;

    color:var(--soft);

    font-size:13px;

    line-height:1.6;
}

.login-row{

    display:grid;

    grid-template-columns:
        1fr 1fr auto;

    gap:12px;

    align-items:end;
}

.login-field label{

    display:block;

    margin-bottom:6px;

    color:var(--deep);

    font-size:12px;

    font-weight:800;
}

.login-field input{

    width:100%;

    min-height:47px;

    padding:
        11px 13px;

    background:white;

    border:
        1px solid var(--line);

    border-radius:12px;

    color:var(--ink);

    outline:none;
}

.login-field input:focus{

    border-color:var(--accent);

    box-shadow:
        0 0 0 4px
        rgba(233,124,107,.12);
}

.logged-box{

    display:flex;

    align-items:center;

    justify-content:space-between;

    flex-wrap:wrap;

    gap:12px;

    padding:
        15px 17px;

    background:var(--ok-light);

    border:
        1px solid #c1e2cd;

    border-radius:14px;

    color:var(--ok);

    font-size:13px;
}

.logged-box strong{

    color:#286c47;
}

.logout{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:40px;

    padding:
        0 15px;

    border:
        1px solid #c1e2cd;

    border-radius:10px;

    background:white;

    color:var(--ok);

    font-size:12px;

    font-weight:800;

    transition:
        transform .2s ease,
        background .2s ease;
}

.logout:hover{

    transform:
        translateY(-2px);

    background:var(--ok-light);
}

/* =====================================================
   FORMULARIO
===================================================== */

.form-grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:17px;
}

.form-group label{

    display:block;

    margin-bottom:7px;

    color:var(--deep);

    font-size:12px;

    font-weight:800;
}

.form-group input,
.form-group select{

    width:100%;

    min-height:47px;

    padding:
        11px 13px;

    background:white;

    border:
        1px solid var(--line);

    border-radius:12px;

    color:var(--ink);

    outline:none;

    transition:
        border-color .2s ease,
        box-shadow .2s ease;
}

.form-group input:focus,
.form-group select:focus{

    border-color:var(--accent);

    box-shadow:
        0 0 0 4px
        rgba(233,124,107,.12);
}

.form-group input:disabled{

    background:#f7f2f0;

    color:var(--soft);
}

.primary{

    min-height:48px;

    padding:
        0 22px;

    border:none;

    border-radius:13px;

    background:
        linear-gradient(
            135deg,
            var(--accent),
            var(--accent-dark)
        );

    color:white;

    font-size:13px;

    font-weight:800;

    cursor:pointer;

    box-shadow:
        0 14px 30px
        rgba(233,124,107,.20);

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}

.primary:hover{

    transform:
        translateY(-3px);

    box-shadow:
        0 20px 40px
        rgba(233,124,107,.28);
}

.reserve-button{

    width:100%;

    margin-top:20px;

    min-height:53px;

    font-size:14px;
}

.selected-info{

    margin-top:18px;

    padding:
        14px 16px;

    background:var(--sand2);

    border:
        1px solid var(--line);

    border-radius:12px;

    color:var(--deep);

    font-size:13px;

    line-height:1.6;
}

/* =====================================================
   MENSAJES
===================================================== */

.message{

    margin-top:17px;

    padding:
        14px 16px;

    border-radius:12px;

    font-size:13px;

    line-height:1.5;
}

.message.ok{

    background:var(--ok-light);

    border:
        1px solid #c1e2cd;

    color:var(--ok);
}

.message.bad{

    background:var(--bad-light);

    border:
        1px solid #f1c3ba;

    color:var(--bad);
}

/* =====================================================
   CONFIRMACIÓN
===================================================== */

.confirmation{

    margin-top:23px;

    padding:27px;

    background:
        linear-gradient(
            145deg,
            var(--ok-light),
            #f4faf6
        );

    border:
        1px solid #c1e2cd;

    border-radius:18px;

    color:var(--ok);
}

.confirmation h3{

    margin-bottom:12px;

    color:var(--ok);

    font-family:
        "Manrope",
        sans-serif;

    font-size:25px;

    font-weight:800;
}

.confirmation p{

    margin:
        7px 0;

    color:#316b48;

    font-size:13px;
}

.code{

    display:inline-flex;

    margin-top:12px;

    padding:
        9px 15px;

    background:white;

    border:
        1px solid #c1e2cd;

    border-radius:9px;

    color:var(--ok);

    font-family:monospace;

    font-size:13px;

    font-weight:700;
}

/* =====================================================
   ANTES DE VENIR
===================================================== */

.before-grid{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:16px;
}

.before{

    padding:21px;

    background:
        linear-gradient(
            145deg,
            #ffffff,
            var(--sand2)
        );

    border:
        1px solid var(--line);

    border-radius:17px;

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}

.before:hover{

    transform:
        translateY(-5px);

    box-shadow:
        0 17px 38px
        rgba(92,42,74,.09);
}

.before h3{

    margin-bottom:8px;

    color:var(--deep);

    font-family:
        "Manrope",
        sans-serif;

    font-size:18px;

    font-weight:800;
}

.before p{

    color:var(--soft);

    font-size:13px;

    line-height:1.65;
}

/* =====================================================
   FOOTER
===================================================== */

footer{

    padding:
        36px 20px;

    background:var(--deep);

    color:
        rgba(255,255,255,.65);

    text-align:center;

    font-size:12px;
}

footer strong{

    display:block;

    margin-bottom:5px;

    color:white;

    font-family:
        "Manrope",
        sans-serif;

    font-size:15px;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:1000px){

    .service-grid{

        grid-template-columns:
            repeat(2,1fr);
    }

    .cards{

        grid-template-columns:
            repeat(2,1fr);
    }

    .before-grid{

        grid-template-columns:
            repeat(2,1fr);
    }

    .stats{

        grid-template-columns:
            repeat(2,1fr);
    }
}

@media(max-width:750px){

    header{

        min-height:540px;

        padding:
            70px 24px 100px;
    }

    header h1{

        font-size:60px;
    }

    main{

        padding:
            0 18px;
    }

    .hero-card,
    .section,
    .reservation{

        padding:23px;
    }

    .service-grid,
    .cards{

        grid-template-columns:1fr;
    }

    .before-grid{

        grid-template-columns:1fr;
    }

    .form-grid{

        grid-template-columns:1fr;
    }

    .login-row{

        grid-template-columns:1fr;
    }

    .login-row .primary{

        width:100%;
    }
}

@media(max-width:450px){

    header{

        min-height:500px;

        padding:
            60px 18px 90px;
    }

    header h1{

        font-size:46px;
    }

    header p{

        font-size:14px;
    }

    main{

        padding:
            0 12px;
    }

    .hero-card,
    .section,
    .reservation{

        padding:19px;
    }

    .stats{

        grid-template-columns:1fr;
    }

    .back-button{

        width:100%;
    }

    .primary{

        width:100%;
    }
}

</style>

</head>

<body>

<header>

    <div class="header-content">

        <div class="eyebrow">
            Laguna Experience · Bienestar
        </div>

        <h1>
            Spa &
            <span>
                Wellness
            </span>
        </h1>

        <p>
            Un espacio pensado para relajarse, desconectar
            y disfrutar diferentes experiencias de bienestar
            durante tu visita a Laguna Experience.
        </p>

        <a
            href="../index.php"
            class="back-button"
        >
            ← Volver al inicio
        </a>

    </div>

</header>

<main>

    <!-- INTRODUCCIÓN -->

    <section class="hero-card">

        <h2>
            Un espacio para relajarse
        </h2>

        <p>
            El Spa & Wellness reúne diferentes propuestas
            pensadas para descansar y disfrutar de momentos
            de relajación. Podés elegir entre masajes,
            tratamientos faciales y circuitos de bienestar.
        </p>

    </section>

    <!-- ESTADÍSTICAS -->

    <div class="stats">

        <div class="stat">

            <div class="stat-number">
                <?php
                echo $cantidadServicios;
                ?>
            </div>

            <div class="stat-label">
                Servicios
            </div>

        </div>

        <div class="stat">

            <div class="stat-number">

                <?php
                echo $duracionMinima;
                ?>

                -

                <?php
                echo $duracionMaxima;
                ?>

            </div>

            <div class="stat-label">
                Minutos
            </div>

        </div>

        <div class="stat">

            <div class="stat-number">

                <?php
                echo $capacidadMaxima;
                ?>

            </div>

            <div class="stat-label">
                Cupos
            </div>

        </div>

        <div class="stat">

            <div class="stat-number">

                <?php
                echo $tiposServicio;
                ?>

            </div>

            <div class="stat-label">
                Experiencias
            </div>

        </div>

    </div>

    <!-- EXPERIENCIA -->

    <section class="section">

        <h2 class="section-title">
            La experiencia
        </h2>

        <p class="section-description">
            Una propuesta de bienestar pensada para complementar
            la visita a Laguna Experience y disfrutar de un
            momento de tranquilidad.
        </p>

        <div class="cards">

            <div class="card">

                <h3>
                    Relajación
                </h3>

                <p>
                    Tratamientos pensados para reducir el ritmo
                    y disfrutar de un momento de descanso.
                </p>

            </div>

            <div class="card">

                <h3>
                    Cuidado personal
                </h3>

                <p>
                    Diferentes propuestas de cuidado y bienestar
                    para complementar tu experiencia.
                </p>

            </div>

            <div class="card">

                <h3>
                    Espacio Wellness
                </h3>

                <p>
                    Un ambiente tranquilo pensado para disfrutar
                    de una experiencia diferente dentro del parque.
                </p>

            </div>

        </div>

    </section>

    <!-- CÓMO FUNCIONA -->

    <section class="section">

        <h2 class="section-title">
            ¿Cómo funciona?
        </h2>

        <p class="section-description">
            Elegí un servicio, seleccioná una fecha y horario
            disponibles y confirmá tu reserva.
        </p>

        <div class="cards">

            <div class="card">

                <h3>
                    01 · Elegí
                </h3>

                <p>
                    Revisá los servicios disponibles y conocé
                    su duración, precio y cantidad de cupos.
                </p>

            </div>

            <div class="card">

                <h3>
                    02 · Iniciá sesión
                </h3>

                <p>
                    Ingresá con tu usuario o email y contraseña
                    de Laguna Experience.
                </p>

            </div>

            <div class="card">

                <h3>
                    03 · Reservá
                </h3>

                <p>
                    Seleccioná una fecha y horario para confirmar
                    tu experiencia en el Spa.
                </p>

            </div>

        </div>

    </section>

    <!-- SERVICIOS -->

    <section
        class="section"
        id="servicios"
    >

        <h2 class="section-title">
            Servicios del Spa
        </h2>

        <p class="section-description">
            Conocé las experiencias disponibles y elegí
            la que quieras reservar.
        </p>

        <div class="service-grid">

            <?php foreach (
                $servicios
                as $servicio
            ): ?>

                <div class="service-card">

                    <div class="service-code">

                        <?php
                        echo htmlspecialchars(
                            $servicio["codigo"]
                        );
                        ?>

                    </div>

                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $servicio["nombre"]
                        );
                        ?>

                    </h3>

                    <p>

                        <?php
                        echo htmlspecialchars(
                            $servicio["descripcion"]
                        );
                        ?>

                    </p>

                    <div class="service-info">

                        <span class="badge badge-duration">

                            <?php
                            echo $servicio["duracion"];
                            ?>

                            min

                        </span>

                        <span class="badge badge-price">

                            $

                            <?php
                            echo number_format(
                                $servicio["precio"],
                                0,
                                ",",
                                "."
                            );
                            ?>

                        </span>

                        <span class="badge badge-cupos">

                            <?php
                            echo $servicio["cupos"];
                            ?>

                            cupos

                        </span>

                    </div>

                    <div class="cupos">

                        Cupos disponibles:

                        <strong>

                            <?php
                            echo $servicio["cupos"];
                            ?>

                        </strong>

                    </div>

                    <?php if (
                        (int)$servicio["cupos"] > 0
                    ): ?>

                        <button
                            type="button"
                            class="service-button"
                            onclick="seleccionarServicio(
                                <?php
                                echo (int)$servicio["id"];
                                ?>
                            )"
                        >

                            Reservar servicio

                        </button>

                    <?php else: ?>

                        <button
                            type="button"
                            class="service-button disabled"
                            disabled
                        >

                            Sin cupos

                        </button>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

    <!-- RESERVA -->

    <section
        class="reservation"
        id="reservar"
    >

        <h2>
            Reservá tu experiencia
        </h2>

        <p class="reservation-description">
            Para realizar una reserva tenés que iniciar
            sesión. Después podrás seleccionar el servicio,
            la fecha y el horario.
        </p>

        <!-- LOGIN -->

        <div class="login-box">

            <h3>
                Iniciar sesión
            </h3>

            <?php if ($usuarioActual !== ""): ?>

                <p>
                    Ya estás identificado.
                </p>

                <div class="logged-box">

                    <div>

                        Sesión iniciada como

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $usuarioActual
                            );
                            ?>

                        </strong>

                    </div>

                    <a
                        href="spa.php?cerrar=1"
                        class="logout"
                    >

                        Cerrar sesión

                    </a>

                </div>

            <?php else: ?>

                <p>
                    Ingresá tu usuario o email y contraseña
                    para continuar.
                </p>

                <form
                    method="POST"
                    action="<?php echo htmlspecialchars(
                        $_SERVER["PHP_SELF"]
                    ); ?>#reservar"
                >

                    <input
                        type="hidden"
                        name="accion"
                        value="identificar"
                    >

                    <div class="login-row">

                        <div class="login-field">

                            <label>
                                Usuario o email
                            </label>

                            <input
                                type="text"
                                name="usuario"
                                placeholder="Usuario o email"
                                autocomplete="username"
                                required
                            >

                        </div>

                        <div class="login-field">

                            <label>
                                Contraseña
                            </label>

                            <input
                                type="password"
                                name="password"
                                placeholder="Contraseña"
                                autocomplete="current-password"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="primary"
                        >

                            Iniciar sesión

                        </button>

                    </div>

                </form>

            <?php endif; ?>

        </div>

        <!-- FORMULARIO DE RESERVA -->

        <?php if ($usuarioActual !== ""): ?>

            <form
                method="POST"
                action="<?php echo htmlspecialchars(
                    $_SERVER["PHP_SELF"]
                ); ?>#reservar"
            >

                <input
                    type="hidden"
                    name="accion"
                    value="reservar"
                >

                <div class="form-grid">

                    <div class="form-group">

                        <label for="servicio">
                            Servicio
                        </label>

                        <select
                            id="servicio"
                            name="servicio"
                            required
                        >

                            <option value="">
                                Seleccioná un servicio
                            </option>

                            <?php foreach (
                                $servicios
                                as $servicio
                            ): ?>

                                <?php if (
                                    (int)$servicio["cupos"] > 0
                                ): ?>

                                    <option
                                        value="<?php
                                        echo (int)$servicio["id"];
                                        ?>"
                                        <?php
                                        if (
                                            $servicioSeleccionado ==
                                            (int)$servicio["id"]
                                        ) {
                                            echo "selected";
                                        }
                                        ?>
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $servicio["nombre"]
                                        );
                                        ?>

                                        -

                                        <?php
                                        echo $servicio["duracion"];
                                        ?>

                                        min

                                    </option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="fecha">
                            Fecha
                        </label>

                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            min="<?php
                            echo date("Y-m-d");
                            ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="horario">
                            Horario
                        </label>

                        <input
                            type="time"
                            id="horario"
                            name="horario"
                            min="10:00"
                            max="20:00"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label>
                            Usuario responsable
                        </label>

                        <input
                            type="text"
                            value="<?php
                            echo htmlspecialchars(
                                $usuarioActual
                            );
                            ?>"
                            disabled
                        >

                    </div>

                </div>

                <div
                    class="selected-info"
                    id="selectedInfo"
                >

                    Seleccioná un servicio para consultar
                    sus condiciones.

                </div>

                <button
                    type="submit"
                    class="primary reserve-button"
                >

                    Confirmar reserva

                </button>

            </form>

        <?php else: ?>

            <div class="message bad">

                Para reservar un servicio tenés que iniciar sesión primero.

            </div>

        <?php endif; ?>

        <!-- MENSAJE -->

        <?php if (
            $mensaje !== ""
        ): ?>

            <div
                class="
                    message
                    <?php
                    echo $tipoMensaje === "ok"
                        ? "ok"
                        : "bad";
                    ?>
                "
            >

                <?php
                echo htmlspecialchars(
                    $mensaje
                );
                ?>

            </div>

        <?php endif; ?>

        <!-- CONFIRMACIÓN -->

        <?php if (
            $mostrarConfirmacion &&
            $ultimaReserva
        ): ?>

            <div
                class="confirmation"
                id="confirmacion"
            >

                <h3>
                    Reserva confirmada
                </h3>

                <p>

                    Servicio:

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $ultimaReserva["servicio"]
                        );
                        ?>

                    </strong>

                </p>

                <p>

                    Fecha:

                    <strong>

                        <?php
                        echo date(
                            "d/m/Y",
                            strtotime(
                                $ultimaReserva["fecha"]
                            )
                        );
                        ?>

                    </strong>

                </p>

                <p>

                    Horario:

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $ultimaReserva["horario"]
                        );
                        ?>

                    </strong>

                </p>

                <p>

                    Duración:

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $ultimaReserva["duracion"]
                        );
                        ?>

                        minutos

                    </strong>

                </p>

                <p>

                    Precio:

                    <strong>

                        $

                        <?php
                        echo number_format(
                            $ultimaReserva["precio"],
                            0,
                            ",",
                            "."
                        );
                        ?>

                    </strong>

                </p>

                <div class="code">

                    Código:

                    <?php
                    echo htmlspecialchars(
                        $ultimaReserva["codigo"]
                    );
                    ?>

                </div>

            </div>

        <?php endif; ?>

    </section>

    <!-- ANTES DE VENIR -->

    <section class="section">

        <h2 class="section-title">
            Antes de tu visita
        </h2>

        <p class="section-description">
            Algunas recomendaciones para disfrutar
            mejor de la experiencia Wellness.
        </p>

        <div class="before-grid">

            <div class="before">

                <h3>
                    Llegá con tiempo
                </h3>

                <p>
                    Se recomienda llegar unos minutos antes
                    del horario reservado para facilitar
                    el ingreso al Spa.
                </p>

            </div>

            <div class="before">

                <h3>
                    Ropa cómoda
                </h3>

                <p>
                    Utilizá ropa cómoda que te permita
                    disfrutar de la experiencia de relajación.
                </p>

            </div>

            <div class="before">

                <h3>
                    Respetá el horario
                </h3>

                <p>
                    Cada experiencia tiene una duración
                    determinada y un horario de inicio.
                </p>

            </div>

        </div>

    </section>

</main>

<footer>

    <strong>
        Laguna Experience
    </strong>

    Spa & Wellness

</footer>

<script>

/* =====================================================
   SERVICIOS
===================================================== */

const servicios =
    <?php
    echo json_encode(
        $servicios,
        JSON_UNESCAPED_UNICODE
    );
    ?>;

/* =====================================================
   SELECCIONAR SERVICIO
===================================================== */

function seleccionarServicio(id){

    const select =
        document.getElementById(
            "servicio"
        );

    if (!select) {
        return;
    }

    select.value = id;

    actualizarServicio();

    document
        .getElementById("reservar")
        .scrollIntoView({
            behavior:"smooth"
        });
}

/* =====================================================
   ACTUALIZAR SERVICIO
===================================================== */

function actualizarServicio(){

    const select =
        document.getElementById(
            "servicio"
        );

    const info =
        document.getElementById(
            "selectedInfo"
        );

    if (!select || !info) {
        return;
    }

    const id =
        Number(select.value);

    const servicio =
        servicios.find(
            function(item){

                return Number(item.id) === id;

            }
        );

    if (!servicio){

        info.innerHTML =
            "Seleccioná un servicio para consultar sus condiciones.";

        return;
    }

    let texto =
        "<strong>" +
        servicio.nombre +
        "</strong> · ";

    texto +=
        "Duración: " +
        servicio.duracion +
        " minutos. ";

    texto +=
        "Precio: $" +
        Number(
            servicio.precio
        ).toLocaleString(
            "es-AR"
        ) +
        ". ";

    texto +=
        "Cupos disponibles: " +
        servicio.cupos +
        ".";

    info.innerHTML =
        texto;
}

/* =====================================================
   CAMBIO DE SERVICIO
===================================================== */

const selector =
    document.getElementById(
        "servicio"
    );

if (selector){

    selector.addEventListener(
        "change",
        actualizarServicio
    );
}

actualizarServicio();

/* =====================================================
   SCROLL CONFIRMACIÓN
===================================================== */

<?php if ($mostrarConfirmacion): ?>

setTimeout(
    function(){

        const confirmacion =
            document.getElementById(
                "confirmacion"
            );

        if (confirmacion){

            confirmacion.scrollIntoView({

                behavior:"smooth",

                block:"center"

            });
        }

    },
    300
);

<?php endif; ?>

</script>

</body>

</html>