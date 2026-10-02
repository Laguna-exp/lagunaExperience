<?php

session_start();

require_once "../conexion.php";

/* =====================================================
   VARIABLES
===================================================== */

$loginError = "";
$reservaError = "";

$usuarioLogueado = isset($_SESSION["usuario_id"]);
$nombreUsuario = "";

if ($usuarioLogueado) {
    $nombreUsuario = $_SESSION["usuario"] ?? "";
}

/* =====================================================
   CERRAR SESIÓN
===================================================== */

if (isset($_GET["cerrar"])) {

    session_unset();
    session_destroy();

    header("Location: eventos-especiales.php");
    exit;
}

/* =====================================================
   INICIAR SESIÓN
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["accion"]) &&
    $_POST["accion"] === "login"
) {

    $usuario = trim($_POST["usuario"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($usuario === "" || $password === "") {

        $loginError = "Completá todos los campos.";

    } else {

        $usuarioEscapado = mysqli_real_escape_string(
            $conexion,
            $usuario
        );

        $sqlLogin = "
            SELECT *
            FROM usuarios
            WHERE email = '$usuarioEscapado'
            OR nombre = '$usuarioEscapado'
            LIMIT 1
        ";

        $resultadoLogin = mysqli_query(
            $conexion,
            $sqlLogin
        );

        if (
            $resultadoLogin &&
            mysqli_num_rows($resultadoLogin) > 0
        ) {

            $datosUsuario = mysqli_fetch_assoc(
                $resultadoLogin
            );

            $passwordCorrecta = false;

            if (isset($datosUsuario["password_hash"])) {

                $passwordCorrecta = password_verify(
                    $password,
                    $datosUsuario["password_hash"]
                );

                if (!$passwordCorrecta) {

                    $passwordCorrecta =
                        ($password === $datosUsuario["password_hash"]);
                }
            }

            if ($passwordCorrecta) {

                $_SESSION["usuario_id"] =
                    $datosUsuario["id"];

                $_SESSION["usuario"] =
                    $datosUsuario["nombre"];

                $_SESSION["email"] =
                    $datosUsuario["email"];

                header(
                    "Location: eventos-especiales.php#reserva"
                );

                exit;

            } else {

                $loginError =
                    "El usuario o la contraseña son incorrectos.";
            }

        } else {

            $loginError =
                "El usuario o la contraseña son incorrectos.";
        }
    }
}

/* =====================================================
   OBTENER EVENTOS
===================================================== */

$eventos = [];

$sqlEventos = "
    SELECT *
    FROM eventos
    ORDER BY fecha ASC
";

$resultadoEventos = mysqli_query(
    $conexion,
    $sqlEventos
);

if ($resultadoEventos) {

    while (
        $evento = mysqli_fetch_assoc(
            $resultadoEventos
        )
    ) {

        $eventos[] = $evento;
    }
}

/* =====================================================
   FUNCIÓN FECHA EN ESPAÑOL
===================================================== */

function fechaCompletaEspanol($fecha)
{
    $dias = [
        "domingo",
        "lunes",
        "martes",
        "miércoles",
        "jueves",
        "viernes",
        "sábado"
    ];

    $meses = [
        "enero",
        "febrero",
        "marzo",
        "abril",
        "mayo",
        "junio",
        "julio",
        "agosto",
        "septiembre",
        "octubre",
        "noviembre",
        "diciembre"
    ];

    $timestamp = strtotime($fecha);

    if (!$timestamp) {
        return $fecha;
    }

    $dia = $dias[
        (int) date("w", $timestamp)
    ];

    $numeroDia = date(
        "d",
        $timestamp
    );

    $mes = $meses[
        (int) date("n", $timestamp) - 1
    ];

    return ucfirst(
        $dia .
        " " .
        $numeroDia .
        " de " .
        $mes
    );
}

/* =====================================================
   RESERVA
===================================================== */

$reservaConfirmada = false;
$reservaRealizada = [];

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["accion"]) &&
    $_POST["accion"] === "reservar"
) {

    if (!$usuarioLogueado) {

        $reservaError =
            "Tenés que iniciar sesión para realizar una reserva.";

    } else {

        $eventoId = intval(
            $_POST["evento_id"] ?? 0
        );

        $cantidad = intval(
            $_POST["cantidad"] ?? 0
        );

        $nombreTitular = trim(
            $_POST["nombre"] ?? ""
        );

        if ($eventoId <= 0) {

            $reservaError =
                "Seleccioná un evento.";

        } elseif ($cantidad < 1) {

            $reservaError =
                "La cantidad de personas debe ser mayor a 0.";

        } elseif ($nombreTitular === "") {

            $reservaError =
                "Ingresá el nombre del titular de la reserva.";
        }

        /* ---------------------------------------------
           BUSCAR EVENTO
        --------------------------------------------- */

        if ($reservaError === "") {

            $eventoIdEscapado =
                mysqli_real_escape_string(
                    $conexion,
                    (string) $eventoId
                );

            $sqlEvento = "
                SELECT *
                FROM eventos
                WHERE id = $eventoIdEscapado
                LIMIT 1
            ";

            $resultadoEvento = mysqli_query(
                $conexion,
                $sqlEvento
            );

            if (
                !$resultadoEvento ||
                mysqli_num_rows($resultadoEvento) === 0
            ) {

                $reservaError =
                    "El evento seleccionado no existe.";

            } else {

                $evento = mysqli_fetch_assoc(
                    $resultadoEvento
                );

                $capacidad = intval(
                    $evento["capacidad"] ?? 0
                );

                $fechaEvento =
                    $evento["fecha"] ?? "";

                if ($capacidad <= 0) {

                    $reservaError =
                        "El evento no tiene una capacidad válida.";
                }

                /* -----------------------------------------
                   RESERVAS ACTUALES
                ----------------------------------------- */

                if ($reservaError === "") {

                    $sqlCantidad = "
                        SELECT
                            COALESCE(
                                SUM(cantidad),
                                0
                            ) AS personas_reservadas
                        FROM reservas
                        WHERE evento_id = $eventoId
                        AND estado != 'cancelada'
                    ";

                    $resultadoCantidad =
                        mysqli_query(
                            $conexion,
                            $sqlCantidad
                        );

                    $personasReservadas = 0;

                    if (
                        $resultadoCantidad &&
                        mysqli_num_rows(
                            $resultadoCantidad
                        ) > 0
                    ) {

                        $datosCantidad =
                            mysqli_fetch_assoc(
                                $resultadoCantidad
                            );

                        $personasReservadas =
                            intval(
                                $datosCantidad[
                                    "personas_reservadas"
                                ]
                            );
                    }

                    /* -------------------------------------
                       CONTROLAR CAPACIDAD
                    ------------------------------------- */

                    $disponibles =
                        $capacidad -
                        $personasReservadas;

                    if ($cantidad > $disponibles) {

                        if ($disponibles < 0) {
                            $disponibles = 0;
                        }

                        $reservaError =
                            "No hay capacidad suficiente para ese evento. " .
                            "Quedan " .
                            $disponibles .
                            " lugares disponibles.";
                    }
                }

                /* -----------------------------------------
                   GUARDAR RESERVA
                ----------------------------------------- */

                if ($reservaError === "") {

                    $usuarioId = intval(
                        $_SESSION["usuario_id"]
                    );

                    $fechaEscapada =
                        mysqli_real_escape_string(
                            $conexion,
                            $fechaEvento
                        );

                    $codigo =
                        "EV" .
                        date(
                            "ymd",
                            strtotime($fechaEvento)
                        ) .
                        strtoupper(
                            substr(
                                md5(
                                    uniqid(
                                        rand(),
                                        true
                                    )
                                ),
                                0,
                                6
                            )
                        );

                    $codigoEscapado =
                        mysqli_real_escape_string(
                            $conexion,
                            $codigo
                        );

                    $horarioReserva =
                        "00:00:00";

                    $sqlReserva = "
                        INSERT INTO reservas
                        (
                            usuario_id,
                            actividad_id,
                            evento_id,
                            lugar_id,
                            fecha,
                            horario,
                            cantidad,
                            total,
                            estado,
                            codigo
                        )
                        VALUES
                        (
                            $usuarioId,
                            NULL,
                            $eventoId,
                            NULL,
                            '$fechaEscapada',
                            '$horarioReserva',
                            $cantidad,
                            0,
                            'confirmada',
                            '$codigoEscapado'
                        )
                    ";

                    $resultadoReserva =
                        mysqli_query(
                            $conexion,
                            $sqlReserva
                        );

                    if ($resultadoReserva) {

                        $reservaConfirmada = true;

                        $reservaRealizada = [

                            "codigo" =>
                                $codigo,

                            "evento" =>
                                $evento["nombre"],

                            "fecha" =>
                                date(
                                    "d/m/Y",
                                    strtotime($fechaEvento)
                                ),

                            "cantidad" =>
                                $cantidad
                        ];

                    } else {

                        $reservaError =
                            "No se pudo guardar la reserva: " .
                            mysqli_error($conexion);
                    }
                }
            }
        }
    }
}

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
        Eventos Especiales · Laguna Experience
    </title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {

            --deep: #2E1F4D;
            --mid: #5C3E8C;

            --sand: #F6F3FA;
            --sand2: #ECE4F5;

            --ink: #221933;

            --accent: #7A5C9E;
            --accent-dark: #61437F;

            --ok: #4C9A6A;
            --bad: #C24A3A;
            --warn: #C98A2B;

            --line: #DED3EC;

            --white: #fff;

            --radius: 20px;

            --font-body:
                "DM Sans",
                sans-serif;

            --font-display:
                "Manrope",
                sans-serif;

            --shadow:
                0 24px 70px
                rgba(46,31,77,.12);
        }


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            min-height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    var(--sand) 0%,
                    #f9f7fc 100%
                );

            color: var(--ink);

            font-family:
                var(--font-body);

            overflow-x: hidden;
        }


        a {

            color: inherit;

            text-decoration: none;
        }


        button,
        input,
        select {

            font-family: inherit;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        header {

            position: relative;

            min-height: 510px;

            display: flex;

            align-items: center;

            padding:
                80px 8%;

            overflow: hidden;

            color: white;

            background:

                linear-gradient(
                    90deg,
                    rgba(46,31,77,.98),
                    rgba(92,62,140,.88),
                    rgba(122,92,158,.68)
                ),

                url("https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=2000&q=90")
                center/cover;
        }


        header::before {

            content: "";

            position: absolute;

            width: 540px;
            height: 540px;

            top: -280px;
            right: -120px;

            border-radius: 50%;

            background:
                rgba(255,255,255,.08);

            filter: blur(8px);
        }


        header::after {

            content: "";

            position: absolute;

            inset: 0;

            pointer-events: none;

            background:

                linear-gradient(
                    180deg,
                    transparent 60%,
                    rgba(46,31,77,.45)
                );
        }


        .header-content {

            position: relative;

            z-index: 2;

            max-width: 760px;
        }


        .eyebrow {

            margin-bottom: 17px;

            color:
                #ded0ef;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: .2em;

            text-transform: uppercase;
        }


        header h1 {

            margin-bottom: 22px;

            color: white;

            font-family:
                var(--font-display);

            font-size:
                clamp(52px, 8vw, 100px);

            font-weight: 800;

            line-height: .92;

            letter-spacing: -.07em;
        }


        header h1 span {

            display: block;

            color:
                #d9c9eb;
        }


        header p {

            max-width: 650px;

            margin-bottom: 30px;

            color:
                rgba(255,255,255,.82);

            font-size: 16px;

            line-height: 1.7;
        }


        /* =====================================================
           BOTÓN VOLVER
        ===================================================== */

        .back-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 48px;

            padding:
                0 22px;

            border-radius: 14px;

            background:
                white;

            color:
                var(--deep);

            font-size: 13px;

            font-weight: 800;

            box-shadow:
                0 15px 35px
                rgba(0,0,0,.18);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                background .25s ease;
        }


        .back-button:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 20px 45px
                rgba(0,0,0,.25);

            background:
                #f9f9ff;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        main {

            position: relative;

            z-index: 3;

            max-width: 1250px;

            margin:
                -45px auto 80px;

            padding:
                0 5%;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero-card {

            margin-bottom: 25px;

            padding: 32px;

            background:
                rgba(255,255,255,.96);

            border:
                1px solid
                var(--line);

            border-radius:
                26px;

            box-shadow:
                var(--shadow);

            transition:
                transform .3s ease,
                box-shadow .3s ease;
        }


        .hero-card:hover {

            transform:
                translateY(-4px);

            box-shadow:
                0 30px 75px
                rgba(46,31,77,.15);
        }


        .hero-card h2 {

            margin-bottom: 10px;

            color:
                var(--deep);

            font-family:
                var(--font-display);

            font-size: 34px;

            font-weight: 800;

            letter-spacing: -.05em;
        }


        .hero-card p {

            max-width: 850px;

            color:
                rgba(34,25,51,.65);

            line-height: 1.75;

            font-size: 14px;
        }


        /* =====================================================
           INFORMACIÓN
        ===================================================== */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 16px;

            margin-bottom: 25px;
        }


        .stat {

            padding: 25px;

            background:
                white;

            border:
                1px solid
                var(--line);

            border-radius:
                var(--radius);

            box-shadow:
                0 15px 45px
                rgba(46,31,77,.07);
        }


        .stat-number {

            margin-bottom: 5px;

            color:
                var(--mid);

            font-family:
                var(--font-display);

            font-size: 32px;

            font-weight: 800;
        }


        .stat-label {

            color:
                rgba(34,25,51,.62);

            font-size: 13px;
        }


        /* =====================================================
           SECCIONES
        ===================================================== */

        .section {

            margin-bottom: 25px;

            padding: 32px;

            background:
                rgba(255,255,255,.96);

            border:
                1px solid
                var(--line);

            border-radius:
                26px;

            box-shadow:
                var(--shadow);
        }


        .section-title {

            margin-bottom: 8px;

            color:
                var(--deep);

            font-family:
                var(--font-display);

            font-size: 30px;

            font-weight: 800;

            letter-spacing: -.04em;
        }


        .section-description {

            margin-bottom: 22px;

            color:
                rgba(34,25,51,.62);

            font-size: 14px;

            line-height: 1.7;
        }


        /* =====================================================
           PASOS
        ===================================================== */

        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;
        }


        .info {

            padding: 21px;

            background:
                var(--sand);

            border:
                1px solid
                var(--line);

            border-radius:
                17px;
        }


        .info strong {

            display: block;

            margin-bottom: 8px;

            color:
                var(--deep);

            font-size: 14px;

            font-weight: 800;
        }


        .info span {

            display: block;

            color:
                rgba(34,25,51,.62);

            font-size: 13px;

            line-height: 1.6;
        }


        /* =====================================================
           EVENTOS
        ===================================================== */

        .events-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 18px;
        }


        .event-card {

            position: relative;

            padding: 24px;

            background:
                white;

            border:
                1px solid
                var(--line);

            border-radius:
                20px;

            box-shadow:
                0 15px 45px
                rgba(46,31,77,.07);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                border-color .25s ease;
        }


        .event-card:hover {

            transform:
                translateY(-5px);

            border-color:
                #cdbbe1;

            box-shadow:
                0 25px 55px
                rgba(46,31,77,.13);
        }


        .event-card h3 {

            margin-bottom: 10px;

            color:
                var(--deep);

            font-family:
                var(--font-display);

            font-size: 21px;

            font-weight: 800;
        }


        .event-card p {

            margin-bottom: 18px;

            color:
                rgba(34,25,51,.63);

            font-size: 13px;

            line-height: 1.65;
        }


        .event-data {

            display: grid;

            gap: 9px;

            margin-bottom: 20px;
        }


        .event-data span {

            display: block;

            padding:
                9px 11px;

            background:
                var(--sand);

            border-radius: 10px;

            color:
                rgba(34,25,51,.72);

            font-size: 12px;

            font-weight: 600;
        }


        .event-button {

            width: 100%;

            min-height: 45px;

            border: none;

            border-radius: 12px;

            background:
                var(--deep);

            color: white;

            font-size: 13px;

            font-weight: 800;

            cursor: pointer;

            transition:
                transform .2s ease,
                background .2s ease;
        }


        .event-button:hover {

            transform:
                translateY(-2px);

            background:
                var(--accent-dark);
        }


        .empty-events {

            padding: 25px;

            background:
                var(--sand);

            border:
                1px solid
                var(--line);

            border-radius:
                15px;

            color:
                rgba(34,25,51,.65);

            text-align: center;
        }


        /* =====================================================
           RESERVA
        ===================================================== */

        .reservation {

            margin-bottom: 25px;

            padding: 32px;

            background:
                white;

            border:
                1px solid
                var(--line);

            border-radius:
                26px;

            box-shadow:
                var(--shadow);
        }


        .reservation h2 {

            margin-bottom: 8px;

            color:
                var(--deep);

            font-family:
                var(--font-display);

            font-size: 30px;

            font-weight: 800;
        }


        .reservation > p {

            margin-bottom: 22px;

            color:
                rgba(34,25,51,.62);

            font-size: 14px;

            line-height: 1.7;
        }


        .logged-box {

            margin-bottom: 20px;

            padding:
                17px 19px;

            background:
                #edf7f0;

            border:
                1px solid
                #c9e5d2;

            border-radius:
                15px;

            color:
                #376b4b;

            font-size: 13px;

            line-height: 1.6;
        }


        .logged-box strong {

            color:
                #286c47;
        }


        .logged-box a {

            color:
                var(--mid);

            font-weight: 800;
        }


        .form-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 17px;

            margin-bottom: 18px;
        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            color:
                var(--deep);

            font-size: 12px;

            font-weight: 800;
        }


        .form-group input,
        .form-group select {

            width: 100%;

            min-height: 47px;

            padding:
                11px 13px;

            background:
                white;

            border:
                1px solid
                var(--line);

            border-radius:
                12px;

            color:
                var(--ink);

            font-size: 14px;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .form-group input:focus,
        .form-group select:focus {

            border-color:
                var(--accent);

            box-shadow:
                0 0 0 4px
                rgba(122,92,158,.10);
        }


        .selected-event {

            display: none;

            margin-bottom: 18px;

            padding: 17px;

            background:
                var(--sand);

            border:
                1px solid
                var(--line);

            border-radius:
                15px;
        }


        .selected-event.visible {

            display: block;
        }


        .selected-event strong {

            display: block;

            margin-bottom: 6px;

            color:
                var(--deep);

            font-size: 14px;
        }


        .selected-event span {

            color:
                rgba(34,25,51,.62);

            font-size: 13px;
        }


        .primary {

            min-height: 48px;

            padding:
                0 22px;

            border: none;

            border-radius: 13px;

            background:
                var(--deep);

            color: white;

            font-size: 13px;

            font-weight: 800;

            cursor: pointer;

            transition:
                transform .2s ease,
                background .2s ease,
                box-shadow .2s ease;
        }


        .primary:hover {

            transform:
                translateY(-2px);

            background:
                var(--accent-dark);

            box-shadow:
                0 12px 30px
                rgba(46,31,77,.18);
        }


        .reserve-button {

            width: 100%;

            margin-top: 5px;
        }


        .login-box {

            margin-top: 22px;

            padding: 22px;

            background:
                var(--sand);

            border:
                1px solid
                var(--line);

            border-radius:
                18px;
        }


        .login-box h3 {

            margin-bottom: 7px;

            color:
                var(--deep);

            font-family:
                var(--font-display);

            font-size: 21px;

            font-weight: 800;
        }


        .login-box > p {

            margin-bottom: 17px;

            color:
                rgba(34,25,51,.62);

            font-size: 13px;

            line-height: 1.6;
        }


        .login-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr auto;

            gap: 13px;

            align-items: end;
        }


        .login-grid label {

            display: block;

            margin-bottom: 6px;

            color:
                var(--deep);

            font-size: 12px;

            font-weight: 800;
        }


        .login-grid input {

            width: 100%;

            min-height: 45px;

            padding:
                11px 13px;

            background:
                white;

            border:
                1px solid
                var(--line);

            border-radius:
                11px;

            color:
                var(--ink);

            outline: none;
        }


        .message {

            margin-bottom: 20px;

            padding:
                15px 17px;

            border-radius:
                13px;

            font-size: 13px;

            font-weight: 600;
        }


        .message.error {

            background:
                #fff0ee;

            border:
                1px solid
                #efc9c4;

            color:
                var(--bad);
        }


        .confirmation {

            margin-top: 20px;

            padding: 25px;

            background:
                #edf7f0;

            border:
                1px solid
                #c9e5d2;

            border-radius:
                18px;
        }


        .confirmation h3 {

            margin-bottom: 9px;

            color:
                #286c47;

            font-family:
                var(--font-display);

            font-size: 23px;

            font-weight: 800;
        }


        .confirmation p {

            margin-bottom: 8px;

            color:
                #41664e;

            font-size: 13px;

            line-height: 1.6;
        }


        .confirmation strong {

            color:
                #286c47;
        }


        /* =====================================================
           ANIMACIÓN
        ===================================================== */

        .hero-card,
        .stat,
        .section,
        .reservation {

            animation:
                aparecer .6s ease both;
        }


        @keyframes aparecer {

            from {

                opacity: 0;

                transform:
                    translateY(15px);
            }

            to {

                opacity: 1;

                transform:
                    translateY(0);
            }
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 950px) {

            .events-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .info-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .login-grid {

                grid-template-columns:
                    1fr 1fr;
            }


            .login-grid .primary {

                grid-column:
                    1 / -1;
            }
        }


        @media (max-width: 750px) {

            header {

                min-height: 540px;

                padding:
                    70px 24px 100px;
            }


            header h1 {

                font-size:
                    60px;
            }


            main {

                padding:
                    0 18px;
            }


            .hero-card,
            .section,
            .reservation {

                padding:
                    23px;
            }


            .stats {

                grid-template-columns:
                    1fr;
            }


            .events-grid {

                grid-template-columns:
                    1fr;
            }


            .info-grid {

                grid-template-columns:
                    1fr;
            }


            .form-grid {

                grid-template-columns:
                    1fr;
            }


            .login-grid {

                grid-template-columns:
                    1fr;
            }


            .login-grid .primary {

                grid-column:
                    auto;
            }
        }


        @media (max-width: 450px) {

            header {

                min-height: 500px;

                padding:
                    60px 18px 90px;
            }


            header h1 {

                font-size:
                    46px;
            }


            header p {

                font-size:
                    14px;
            }


            main {

                padding:
                    0 12px;
            }


            .hero-card,
            .section,
            .reservation {

                padding:
                    19px;
            }


            .back-button {

                width:
                    100%;
            }
        }

    </style>

</head>


<body>


<header>

    <div class="header-content">

        <div class="eyebrow">
            Laguna Experience · Experiencias
        </div>


        <h1>

            Eventos

            <span>
                Especiales
            </span>

        </h1>


        <p>
            Viví experiencias únicas, celebraciones
            y actividades especiales dentro de
            Laguna Experience.
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


    <section class="hero-card">

        <h2>
            Experiencias para recordar
        </h2>

        <p>
            Descubrí los eventos especiales que
            se realizan en Laguna Experience y
            reservá tu lugar para disfrutar de
            cada propuesta.
        </p>

    </section>


    <section class="stats">

        <div class="stat">

            <div class="stat-number">
                <?= count($eventos) ?>
            </div>

            <div class="stat-label">
                Eventos disponibles
            </div>

        </div>


        <div class="stat">

            <div class="stat-number">
                Reserva
            </div>

            <div class="stat-label">
                Acceso mediante reserva
            </div>

        </div>


        <div class="stat">

            <div class="stat-number">
                Laguna
            </div>

            <div class="stat-label">
                Experiencias dentro del parque
            </div>

        </div>

    </section>


    <section class="section">

        <h2 class="section-title">
            ¿Cómo funciona?
        </h2>

        <p class="section-description">
            Reservar tu lugar es simple y permite
            organizar el acceso al evento.
        </p>


        <div class="info-grid">

            <div class="info">

                <strong>
                    1. Elegí
                </strong>

                <span>
                    Seleccioná el evento que
                    querés disfrutar.
                </span>

            </div>


            <div class="info">

                <strong>
                    2. Revisá
                </strong>

                <span>
                    Consultá la fecha y los
                    lugares disponibles.
                </span>

            </div>


            <div class="info">

                <strong>
                    3. Reservá
                </strong>

                <span>
                    Indicá cuántas personas
                    asistirán.
                </span>

            </div>


            <div class="info">

                <strong>
                    4. Disfrutá
                </strong>

                <span>
                    Presentá tu reserva al
                    momento de ingresar.
                </span>

            </div>

        </div>

    </section>


    <section class="section">

        <h2 class="section-title">
            Próximos eventos
        </h2>

        <p class="section-description">
            Elegí una propuesta para consultar
            su disponibilidad y reservar.
        </p>


        <?php if (count($eventos) > 0): ?>

            <div class="events-grid">


                <?php foreach ($eventos as $evento): ?>

                    <?php

                    $eventoId =
                        intval(
                            $evento["id"]
                        );

                    $fechaEvento =
                        $evento["fecha"] ?? "";

                    $fechaCompleta =
                        fechaCompletaEspanol(
                            $fechaEvento
                        );

                    $capacidad =
                        intval(
                            $evento["capacidad"] ?? 0
                        );

                    $personasReservadas = 0;


                    $sqlReservasEvento = "
                        SELECT
                            COALESCE(
                                SUM(cantidad),
                                0
                            ) AS personas_reservadas
                        FROM reservas
                        WHERE evento_id = $eventoId
                        AND estado != 'cancelada'
                    ";


                    $resultadoReservasEvento =
                        mysqli_query(
                            $conexion,
                            $sqlReservasEvento
                        );


                    if (
                        $resultadoReservasEvento &&
                        mysqli_num_rows(
                            $resultadoReservasEvento
                        ) > 0
                    ) {

                        $datosReservasEvento =
                            mysqli_fetch_assoc(
                                $resultadoReservasEvento
                            );

                        $personasReservadas =
                            intval(
                                $datosReservasEvento[
                                    "personas_reservadas"
                                ]
                            );
                    }


                    $disponibles =
                        $capacidad -
                        $personasReservadas;


                    if ($disponibles < 0) {

                        $disponibles = 0;
                    }

                    ?>


                    <article class="event-card">

                        <h3>

                            <?= htmlspecialchars(
                                $evento["nombre"]
                            ) ?>

                        </h3>


                        <?php if (
                            isset(
                                $evento["descripcion"]
                            ) &&
                            $evento["descripcion"] !== ""
                        ): ?>

                            <p>

                                <?= htmlspecialchars(
                                    $evento["descripcion"]
                                ) ?>

                            </p>

                        <?php else: ?>

                            <p>

                                Una experiencia especial
                                para disfrutar dentro de
                                Laguna Experience.

                            </p>

                        <?php endif; ?>


                        <div class="event-data">

                            <span>

                                Fecha:

                                <?= htmlspecialchars(
                                    $fechaCompleta
                                ) ?>

                            </span>


                            <span>

                                <?= $disponibles ?>

                                lugares disponibles

                            </span>


                            <span>

                                Capacidad:

                                <?= $capacidad ?>

                                personas

                            </span>

                        </div>


                        <?php if ($disponibles > 0): ?>

                            <button
                                type="button"
                                class="event-button"
                                onclick="seleccionarEvento(
                                    <?= $eventoId ?>
                                )"
                            >

                                Reservar este evento

                            </button>

                        <?php else: ?>

                            <button
                                type="button"
                                class="event-button"
                                disabled
                                style="
                                    opacity:.55;
                                    cursor:not-allowed;
                                "
                            >

                                Evento completo

                            </button>

                        <?php endif; ?>

                    </article>


                <?php endforeach; ?>

            </div>


        <?php else: ?>

            <div class="empty-events">

                Actualmente no hay eventos
                disponibles para mostrar.

            </div>

        <?php endif; ?>

    </section>


    <section
        class="reservation"
        id="reserva"
    >

        <h2>
            Reservá tu lugar
        </h2>


        <p>
            Elegí un evento, indicá la cantidad
            de personas y confirmá tu reserva.
        </p>


        <?php if ($usuarioLogueado): ?>

            <div class="logged-box">

                Sesión iniciada como

                <strong>
                    <?= htmlspecialchars(
                        $nombreUsuario
                    ) ?>
                </strong>.

                Ya podés realizar tu reserva.

                <br>
                <br>

                <a
                    href="eventos-especiales.php?cerrar=1"
                >
                    Cerrar sesión
                </a>

            </div>

        <?php endif; ?>


        <?php if ($reservaError !== ""): ?>

            <div class="message error">

                <?= htmlspecialchars(
                    $reservaError
                ) ?>

            </div>

        <?php endif; ?>


        <?php if (!$reservaConfirmada): ?>


            <form
                id="reservaForm"
                method="POST"
                action="eventos-especiales.php#reserva"
            >

                <input
                    type="hidden"
                    name="accion"
                    value="reservar"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label for="evento_id">
                            Evento
                        </label>


                        <select
                            id="evento_id"
                            name="evento_id"
                            required
                            onchange="actualizarEvento()"
                        >

                            <option value="">
                                Seleccioná un evento
                            </option>


                            <?php foreach (
                                $eventos
                                as $evento
                            ): ?>


                                <?php

                                $idEvento =
                                    intval(
                                        $evento["id"]
                                    );

                                $capacidadEvento =
                                    intval(
                                        $evento["capacidad"] ?? 0
                                    );

                                $reservadosEvento = 0;


                                $sqlReservados = "
                                    SELECT
                                        COALESCE(
                                            SUM(cantidad),
                                            0
                                        ) AS total
                                    FROM reservas
                                    WHERE evento_id =
                                        $idEvento
                                    AND estado !=
                                        'cancelada'
                                ";


                                $resultadoReservados =
                                    mysqli_query(
                                        $conexion,
                                        $sqlReservados
                                    );


                                if (
                                    $resultadoReservados &&
                                    mysqli_num_rows(
                                        $resultadoReservados
                                    ) > 0
                                ) {

                                    $datosReservados =
                                        mysqli_fetch_assoc(
                                            $resultadoReservados
                                        );

                                    $reservadosEvento =
                                        intval(
                                            $datosReservados[
                                                "total"
                                            ]
                                        );
                                }


                                $disponiblesEvento =
                                    $capacidadEvento -
                                    $reservadosEvento;


                                if (
                                    $disponiblesEvento < 0
                                ) {

                                    $disponiblesEvento = 0;
                                }

                                ?>


                                <?php if (
                                    $disponiblesEvento > 0
                                ): ?>

                                    <option
                                        value="<?= $idEvento ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $evento["nombre"]
                                        ) ?>

                                        -

                                        <?= date(
                                            "d/m/Y",
                                            strtotime(
                                                $evento["fecha"]
                                            )
                                        ) ?>

                                    </option>

                                <?php endif; ?>


                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="nombre">
                            Nombre del titular
                        </label>


                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            value="<?= htmlspecialchars(
                                $nombreUsuario
                            ) ?>"
                            placeholder="Nombre completo"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="cantidad">
                            Cantidad de personas
                        </label>


                        <input
                            type="number"
                            id="cantidad"
                            name="cantidad"
                            min="1"
                            max="100"
                            value="1"
                            required
                        >

                    </div>

                </div>


                <div
                    id="selectedEvent"
                    class="selected-event"
                >

                    <strong id="selectedEventName">
                        Evento seleccionado
                    </strong>


                    <span id="selectedEventInfo">
                        Seleccioná un evento para
                        consultar la información.
                    </span>

                </div>


                <button
                    type="submit"
                    class="primary reserve-button"
                >
                    Confirmar reserva
                </button>

            </form>


            <?php if (!$usuarioLogueado): ?>

                <div class="login-box">

                    <h3>
                        Iniciá sesión para continuar
                    </h3>


                    <p>
                        Antes de confirmar tu reserva
                        necesitamos identificarte.
                    </p>


                    <form
                        method="POST"
                        action="eventos-especiales.php#reserva"
                    >

                        <input
                            type="hidden"
                            name="accion"
                            value="login"
                        >


                        <div class="login-grid">


                            <div>

                                <label for="usuario">
                                    Usuario o email
                                </label>


                                <input
                                    type="text"
                                    id="usuario"
                                    name="usuario"
                                    placeholder="Usuario o email"
                                    required
                                >

                            </div>


                            <div>

                                <label for="password">
                                    Contraseña
                                </label>


                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Contraseña"
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


                    <?php if ($loginError !== ""): ?>

                        <div
                            class="message error"
                            style="
                                margin-top:15px;
                                margin-bottom:0;
                            "
                        >

                            <?= htmlspecialchars(
                                $loginError
                            ) ?>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


        <?php endif; ?>


        <?php if ($reservaConfirmada): ?>

            <div class="confirmation">

                <h3>
                    Reserva confirmada
                </h3>


                <p>
                    Tu reserva para el evento
                    fue registrada correctamente.
                </p>


                <p>

                    <strong>
                        Evento:
                    </strong>

                    <?= htmlspecialchars(
                        $reservaRealizada["evento"]
                    ) ?>

                </p>


                <p>

                    <strong>
                        Código:
                    </strong>

                    <?= htmlspecialchars(
                        $reservaRealizada["codigo"]
                    ) ?>

                </p>


                <p>

                    <strong>
                        Fecha:
                    </strong>

                    <?= htmlspecialchars(
                        $reservaRealizada["fecha"]
                    ) ?>

                </p>


                <p>

                    <strong>
                        Personas:
                    </strong>

                    <?= intval(
                        $reservaRealizada["cantidad"]
                    ) ?>

                </p>


                <a
                    href="../index.php"
                    class="back-button"
                    style="
                        margin-top:18px;
                    "
                >
                    ← Volver al inicio
                </a>

            </div>

        <?php endif; ?>

    </section>


    <section class="section">

        <h2 class="section-title">
            Antes de venir
        </h2>


        <p class="section-description">
            Tené en cuenta estos datos para
            disfrutar mejor tu experiencia.
        </p>


        <div class="info-grid">


            <div class="info">

                <strong>
                    Reserva
                </strong>

                <span>
                    Tené disponible el código de
                    tu reserva al momento de ingresar.
                </span>

            </div>


            <div class="info">

                <strong>
                    Fecha
                </strong>

                <span>
                    Revisá la fecha correspondiente
                    al evento que reservaste.
                </span>

            </div>


            <div class="info">

                <strong>
                    Personas
                </strong>

                <span>
                    La cantidad de asistentes debe
                    coincidir con la reserva.
                </span>

            </div>


            <div class="info">

                <strong>
                    Acceso
                </strong>

                <span>
                    Presentá tu reserva para
                    gestionar el ingreso.
                </span>

            </div>

        </div>

    </section>


</main>


<script>

    const eventos =
        <?= json_encode(
            $eventos,
            JSON_UNESCAPED_UNICODE |
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_AMP |
            JSON_HEX_QUOT
        ) ?>;


    function seleccionarEvento(id)
    {

        const select =
            document.getElementById(
                "evento_id"
            );


        if (!select) {
            return;
        }


        select.value =
            String(id);


        actualizarEvento();


        document
            .getElementById("reserva")
            .scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
    }


    function actualizarEvento()
    {

        const select =
            document.getElementById(
                "evento_id"
            );


        const selectedBox =
            document.getElementById(
                "selectedEvent"
            );


        const selectedName =
            document.getElementById(
                "selectedEventName"
            );


        const selectedInfo =
            document.getElementById(
                "selectedEventInfo"
            );


        if (
            !select ||
            !selectedBox ||
            !selectedName ||
            !selectedInfo
        ) {

            return;
        }


        const id =
            select.value;


        if (!id) {

            selectedBox.classList.remove(
                "visible"
            );

            return;
        }


        const evento =
            eventos.find(
                function(item)
                {
                    return String(item.id) ===
                        String(id);
                }
            );


        if (!evento) {

            selectedBox.classList.remove(
                "visible"
            );

            return;
        }


        const fecha =
            new Date(
                evento.fecha +
                "T00:00:00"
            );


        const fechaFormateada =
            fecha.toLocaleDateString(
                "es-AR",
                {
                    weekday: "long",
                    day: "numeric",
                    month: "long"
                }
            );


        selectedName.textContent =
            evento.nombre;


        selectedInfo.textContent =
            fechaFormateada +
            " · Capacidad: " +
            evento.capacidad +
            " personas";


        selectedBox.classList.add(
            "visible"
        );
    }


    document.addEventListener(
        "DOMContentLoaded",
        function()
        {
            actualizarEvento();
        }
    );

</script>


</body>

</html>