<?php

session_start();

require_once __DIR__ . "/../conexion.php";


/* =========================================================
   BUSCAR BEACH CLUB
========================================================= */

$lugar_id = 0;

$sqlLugar = "
    SELECT id
    FROM lugares
    WHERE slug = 'beach-club'
       OR nombre = 'Beach Club'
    LIMIT 1
";

$resultadoLugar = mysqli_query(
    $conexion,
    $sqlLugar
);

if (
    $resultadoLugar &&
    mysqli_num_rows($resultadoLugar) > 0
) {

    $lugar = mysqli_fetch_assoc(
        $resultadoLugar
    );

    $lugar_id = $lugar["id"];
}


/* =========================================================
   SESIÓN
========================================================= */

$usuarioLogueado = false;
$nombreUsuario = "";

if (isset($_SESSION["usuario_id"])) {

    $usuarioLogueado = true;

    if (isset($_SESSION["usuario"])) {

        $nombreUsuario =
            $_SESSION["usuario"];
    }
}


/* =========================================================
   LOGIN
========================================================= */

$loginError = "";

if (
    isset($_POST["accion"]) &&
    $_POST["accion"] === "login"
) {

    $usuario = trim(
        $_POST["usuario"] ?? ""
    );

    $password =
        $_POST["password"] ?? "";


    if (
        $usuario === "" ||
        $password === ""
    ) {

        $loginError =
            "Completá usuario y contraseña.";

    } else {

        $usuarioEscapado =
            mysqli_real_escape_string(
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


        $resultadoLogin =
            mysqli_query(
                $conexion,
                $sqlLogin
            );


        if (
            $resultadoLogin &&
            mysqli_num_rows($resultadoLogin) > 0
        ) {

            $usuarioBD =
                mysqli_fetch_assoc(
                    $resultadoLogin
                );


            $passwordCorrecta = false;


            if (
                isset(
                    $usuarioBD["password_hash"]
                ) &&
                password_verify(
                    $password,
                    $usuarioBD["password_hash"]
                )
            ) {

                $passwordCorrecta = true;
            }


            /*
             * Permite también el usuario demo
             * que tiene la contraseña guardada
             * directamente en la base de datos.
             */

            if (
                isset(
                    $usuarioBD["password_hash"]
                ) &&
                $password ===
                $usuarioBD["password_hash"]
            ) {

                $passwordCorrecta = true;
            }


            if ($passwordCorrecta) {

                $_SESSION["usuario_id"] =
                    $usuarioBD["id"];

                $_SESSION["usuario"] =
                    $usuarioBD["nombre"];

                $_SESSION["email"] =
                    $usuarioBD["email"];


                $usuarioLogueado = true;

                $nombreUsuario =
                    $usuarioBD["nombre"];

            } else {

                $loginError =
                    "Usuario o contraseña incorrectos.";
            }

        } else {

            $loginError =
                "Usuario o contraseña incorrectos.";
        }
    }
}


/* =========================================================
   CERRAR SESIÓN
========================================================= */

if (isset($_GET["cerrar"])) {

    session_unset();

    session_destroy();

    header(
        "Location: beach-club.php"
    );

    exit;
}


/* =========================================================
   RESERVA
========================================================= */

$reservaConfirmada = false;

$reservaCodigo = "";
$reservaFecha = "";
$reservaPersonas = 0;
$reservaServicios = "";

$reservaError = "";


if (
    isset($_POST["accion"]) &&
    $_POST["accion"] === "reservar"
) {


    /* -----------------------------------------------------
       VERIFICAR LOGIN
    ----------------------------------------------------- */

    if (
        !isset($_SESSION["usuario_id"])
    ) {

        $reservaError =
            "Tenés que iniciar sesión para realizar la reserva.";

    }


    /* -----------------------------------------------------
       VERIFICAR BEACH CLUB
    ----------------------------------------------------- */

    elseif ($lugar_id <= 0) {

        $reservaError =
            "No se encontró el Beach Club en la base de datos.";

    }


    else {

        $usuario_id =
            (int) $_SESSION["usuario_id"];


        $fecha =
            trim(
                $_POST["fecha"] ?? ""
            );


        $personas =
            (int) (
                $_POST["personas"] ?? 0
            );


        $piscina =
            isset(
                $_POST["piscina"]
            );


        $reposera =
            isset(
                $_POST["reposera"]
            );


        $gastronomia =
            isset(
                $_POST["gastronomia"]
            );


        /* -------------------------------------------------
           VALIDAR FECHA
        ------------------------------------------------- */

        if ($fecha === "") {

            $reservaError =
                "Seleccioná una fecha.";

        } else {

            $fechaObjeto =
                DateTime::createFromFormat(
                    "Y-m-d",
                    $fecha
                );


            if (
                !$fechaObjeto ||
                $fechaObjeto->format("Y-m-d")
                !== $fecha
            ) {

                $reservaError =
                    "La fecha seleccionada no es válida.";

            } elseif (
                $fecha < date("Y-m-d")
            ) {

                $reservaError =
                    "No podés reservar una fecha anterior a hoy.";
            }
        }


        /* -------------------------------------------------
           VALIDAR CANTIDAD
        ------------------------------------------------- */

        if (
            $reservaError === "" &&
            (
                $personas < 1 ||
                $personas > 40
            )
        ) {

            $reservaError =
                "La cantidad de personas debe estar entre 1 y 40.";
        }


        /* -------------------------------------------------
           CONTINUAR
        ------------------------------------------------- */

        if ($reservaError === "") {


            /* ---------------------------------------------
               ARMAR SERVICIOS
            --------------------------------------------- */

            $servicios = [];


            if ($piscina) {

                $servicios[] =
                    "Piscina";
            }


            if ($reposera) {

                $servicios[] =
                    "Reposera";
            }


            if ($gastronomia) {

                $servicios[] =
                    "Gastronomía";
            }


            if (
                count($servicios) > 0
            ) {

                $reservaServicios =
                    implode(
                        ", ",
                        $servicios
                    );

            } else {

                $reservaServicios =
                    "Experiencia general";
            }


            /* ---------------------------------------------
               GENERAR CÓDIGO
            --------------------------------------------- */

            $reservaCodigo =
                "BC" .
                date(
                    "ymd",
                    strtotime($fecha)
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


            /* ---------------------------------------------
               ESCAPAR FECHA
            --------------------------------------------- */

            $fechaEscapada =
                mysqli_real_escape_string(
                    $conexion,
                    $fecha
                );


            $codigoEscapado =
                mysqli_real_escape_string(
                    $conexion,
                    $reservaCodigo
                );


            /* ---------------------------------------------
               RESERVAS DEL MISMO DÍA
            --------------------------------------------- */

            $sqlCantidad = "
                SELECT COALESCE(
                    SUM(cantidad),
                    0
                ) AS personas_reservadas

                FROM reservas

                WHERE lugar_id = $lugar_id
                AND fecha = '$fechaEscapada'
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
                    (int)
                    $datosCantidad[
                        "personas_reservadas"
                    ];
            }


            /* ---------------------------------------------
               CONTROLAR CAPACIDAD
            --------------------------------------------- */

            if (
                $personasReservadas +
                $personas > 40
            ) {

                $disponibles =
                    40 -
                    $personasReservadas;


                if (
                    $disponibles < 0
                ) {

                    $disponibles = 0;
                }


                $reservaError =
                    "No hay capacidad suficiente para esa fecha. " .
                    "Quedan " .
                    $disponibles .
                    " lugares disponibles.";

            } else {


                /* -----------------------------------------
                   GUARDAR RESERVA
                ----------------------------------------- */

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
                        $usuario_id,
                        NULL,
                        NULL,
                        $lugar_id,
                        '$fechaEscapada',
                        '00:00:00',
                        $personas,
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


                if (
                    $resultadoReserva
                ) {

                    $reservaConfirmada =
                        true;


                    $reservaFecha =
                        date(
                            "d/m/Y",
                            strtotime($fecha)
                        );


                    $reservaPersonas =
                        $personas;

                } else {

                    $reservaError =
                        "No se pudo guardar la reserva: " .
                        mysqli_error(
                            $conexion
                        );
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
        Beach Club · Laguna Experience
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

            --turq: #0e879a;

            --turq-deep: #0a5e6c;

            --turq-light: #e1f2f4;

            --sand: #fdf3e3;

            --paper: #fffdf7;

            --sun: #e8963a;

            --sun-light: #fdf0dd;

            --coral: #d9603c;

            --coral-light: #fbe4da;

            --line: #ecdfc4;

            --ok: #1c7a45;

            --font-body:
                "DM Sans",
                sans-serif;

            --font-display:
                "Manrope",
                sans-serif;

            --shadow:
                0 24px 70px
                rgba(20, 60, 65, .12);
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
                    var(--turq-light) 100%
                );

            color:
                #183236;

            font-family:
                var(--font-body);

            overflow-x: hidden;
        }


        a {

            color: inherit;

            text-decoration: none;
        }


        button,
        input {

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
                    rgba(10,94,108,.97),
                    rgba(14,135,154,.82),
                    rgba(63,139,112,.52)
                ),

                url("https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2000&q=90")
                center / cover no-repeat;
        }


        header::before {

            content: "";

            position: absolute;

            width: 520px;

            height: 520px;

            top: -290px;

            right: -130px;

            border-radius: 50%;

            background:
                rgba(30,154,163,.22);

            filter: blur(10px);
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
                    rgba(10,94,108,.50)
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
                #b8edf0;

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
                #b8edf0;
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
                var(--turq-deep);

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
                #f9ffff;
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
                rgba(255,255,255,.95);

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
                rgba(20,60,65,.15);
        }


        .hero-card h2 {

            margin-bottom: 10px;

            color:
                var(--turq-deep);

            font-family:
                var(--font-display);

            font-size: 34px;

            font-weight: 800;

            letter-spacing: -.05em;
        }


        .hero-card p {

            max-width: 850px;

            color:
                rgba(24,50,54,.65);

            line-height: 1.75;

            font-size: 14px;
        }


        /* =====================================================
           STATS
        ===================================================== */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 16px;

            margin-bottom: 25px;
        }


        .stat {

            padding: 24px;

            text-align: center;

            background:
                rgba(255,255,255,.95);

            border:
                1px solid
                var(--line);

            border-radius:
                18px;

            box-shadow:
                var(--shadow);

            transition:
                transform .3s ease,
                box-shadow .3s ease;
        }


        .stat:hover {

            transform:
                translateY(-5px);

            box-shadow:
                0 27px 60px
                rgba(20,60,65,.15);
        }


        .stat-number {

            color:
                var(--turq);

            font-family:
                var(--font-display);

            font-size: 35px;

            font-weight: 800;
        }


        .stat-label {

            margin-top: 5px;

            color:
                rgba(24,50,54,.55);

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .12em;

            text-transform: uppercase;
        }


        /* =====================================================
           SECCIONES
        ===================================================== */

        .section {

            margin-bottom: 25px;

            padding: 30px;

            background:
                rgba(255,255,255,.95);

            border:
                1px solid
                var(--line);

            border-radius:
                26px;

            box-shadow:
                var(--shadow);

            transition:
                box-shadow .3s ease;
        }


        .section:hover {

            box-shadow:
                0 30px 75px
                rgba(20,60,65,.14);
        }


        .section-title {

            margin:
                0 0 8px;

            color:
                var(--turq-deep);

            font-family:
                var(--font-display);

            font-size: 30px;

            font-weight: 800;

            letter-spacing: -.05em;
        }


        .section-description {

            max-width: 850px;

            margin-bottom: 24px;

            color:
                rgba(24,50,54,.62);

            font-size: 14px;

            line-height: 1.7;
        }


        /* =====================================================
           CARDS
        ===================================================== */

        .cards {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 16px;
        }


        .card {

            padding: 22px;

            background:
                linear-gradient(
                    145deg,
                    #ffffff,
                    var(--turq-light)
                );

            border:
                1px solid
                var(--line);

            border-radius:
                18px;

            transition:
                transform .3s ease,
                box-shadow .3s ease,
                border-color .3s ease;
        }


        .card:hover {

            transform:
                translateY(-6px);

            border-color:
                rgba(14,135,154,.45);

            box-shadow:
                0 20px 48px
                rgba(20,60,65,.11);
        }


        .card h3 {

            margin-bottom: 8px;

            color:
                var(--turq-deep);

            font-family:
                var(--font-display);

            font-size: 19px;

            font-weight: 800;
        }


        .card p {

            color:
                rgba(24,50,54,.62);

            font-size: 13px;

            line-height: 1.65;
        }


        /* =====================================================
           INFO
        ===================================================== */

        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 15px;
        }


        .info {

            padding: 20px;

            background:
                white;

            border:
                1px solid
                var(--line);

            border-radius:
                16px;

            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }


        .info:hover {

            transform:
                translateY(-4px);

            box-shadow:
                0 15px 35px
                rgba(20,60,65,.09);
        }


        .info strong {

            display: block;

            margin-bottom: 7px;

            color:
                var(--turq);

            font-family:
                var(--font-display);

            font-size: 19px;

            font-weight: 800;
        }


        .info span {

            color:
                rgba(24,50,54,.62);

            font-size: 13px;

            line-height: 1.6;
        }


        /* =====================================================
           GASTRONOMÍA
        ===================================================== */

        .food-grid {

            display: grid;

            grid-template-columns:
                repeat(5, 1fr);

            gap: 13px;
        }


        .food {

            padding: 18px;

            background:
                white;

            border:
                1px solid
                var(--line);

            border-radius:
                15px;

            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }


        .food:hover {

            transform:
                translateY(-4px);

            box-shadow:
                0 15px 35px
                rgba(20,60,65,.09);
        }


        .food h3 {

            margin-bottom: 8px;

            color:
                var(--turq-deep);

            font-family:
                var(--font-display);

            font-size: 16px;

            font-weight: 800;
        }


        .food .price {

            color:
                var(--turq);

            font-size: 13px;

            font-weight: 700;
        }


        /* =====================================================
           RESERVA
        ===================================================== */

        .reservation {

            margin-bottom: 25px;

            padding: 32px;

            background:
                linear-gradient(
                    145deg,
                    var(--turq-light),
                    #ffffff
                );

            border:
                1px solid
                #c9e2df;

            border-radius:
                26px;

            box-shadow:
                var(--shadow);
        }


        .reservation h2 {

            margin-bottom: 8px;

            color:
                var(--turq-deep);

            font-family:
                var(--font-display);

            font-size: 34px;

            font-weight: 800;

            letter-spacing: -.05em;
        }


        .reservation > p {

            margin-bottom: 25px;

            color:
                rgba(24,50,54,.62);

            font-size: 14px;

            line-height: 1.7;
        }


        /* =====================================================
           LOGIN
        ===================================================== */

        .logged-box {

            margin-bottom: 20px;

            padding:
                14px 17px;

            background:
                #e5f3ea;

            border:
                1px solid
                #c1e2cd;

            border-radius:
                14px;

            color:
                var(--ok);

            font-size: 13px;

            animation:
                fadeUp .35s ease;
        }


        .logged-box strong {

            color:
                #286c47;
        }


        .login-box {

            margin-top: 21px;

            padding: 22px;

            background:
                rgba(253,243,227,.78);

            border:
                1px solid
                #e4d8bd;

            border-radius:
                18px;

            animation:
                fadeUp .35s ease;
        }


        .login-box h3 {

            margin-bottom: 7px;

            color:
                var(--turq-deep);

            font-family:
                var(--font-display);

            font-size: 21px;

            font-weight: 800;
        }


        .login-box > p {

            margin-bottom: 17px;

            color:
                rgba(24,50,54,.62);

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
                var(--turq-deep);

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
                #183236;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .login-grid input:focus {

            border-color:
                var(--turq);

            box-shadow:
                0 0 0 4px
                rgba(14,135,154,.10);
        }


        /* =====================================================
           FORMULARIO
        ===================================================== */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 17px;
        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            color:
                var(--turq-deep);

            font-size: 12px;

            font-weight: 800;
        }


        .form-group input {

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
                #183236;

            font-size: 14px;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .form-group input:focus {

            border-color:
                var(--turq);

            box-shadow:
                0 0 0 4px
                rgba(14,135,154,.11);
        }


        /* =====================================================
           OPCIONES
        ===================================================== */

        .services-title {

            display: block;

            margin-bottom: 9px;

            color:
                var(--turq-deep);

            font-size: 12px;

            font-weight: 800;
        }


        .options {

            display: flex;

            flex-wrap: wrap;

            gap: 12px;
        }


        .option {

            flex: 1;

            min-width: 190px;
        }


        .option input {

            display: none;
        }


        .option label {

            display: block;

            padding: 17px;

            background:
                white;

            border:
                1px solid
                var(--line);

            border-radius:
                14px;

            cursor: pointer;

            transition:
                transform .25s ease,
                border-color .25s ease,
                box-shadow .25s ease,
                background .25s ease;
        }


        .option label:hover {

            transform:
                translateY(-3px);

            border-color:
                rgba(14,135,154,.42);

            box-shadow:
                0 13px 28px
                rgba(20,60,65,.08);
        }


        .option label strong {

            display: block;

            margin-bottom: 4px;

            color:
                var(--turq-deep);

            font-size: 13px;
        }


        .option label span {

            color:
                rgba(24,50,54,.56);

            font-size: 11px;

            line-height: 1.5;
        }


        .option input:checked + label {

            background:
                var(--turq-light);

            border-color:
                var(--turq);

            box-shadow:
                0 0 0 3px
                rgba(14,135,154,.08);
        }


        /* =====================================================
           BOTONES
        ===================================================== */

        button {

            border: none;

            cursor: pointer;

            font-family:
                var(--font-body);
        }


        .primary {

            min-height: 48px;

            padding:
                0 22px;

            border-radius:
                13px;

            background:
                linear-gradient(
                    135deg,
                    var(--turq),
                    var(--turq-deep)
                );

            color:
                white;

            font-size: 13px;

            font-weight: 800;

            box-shadow:
                0 14px 30px
                rgba(14,135,154,.20);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                filter .25s ease;
        }


        .primary:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 20px 40px
                rgba(14,135,154,.29);

            filter:
                brightness(1.05);
        }


        .reserve-button {

            width: 100%;

            margin-top: 20px;

            min-height: 53px;

            font-size: 14px;
        }


        /* =====================================================
           MENSAJES
        ===================================================== */

        .message {

            margin-top: 17px;

            padding:
                13px 15px;

            border-radius:
                12px;

            font-size: 13px;

            line-height: 1.5;

            animation:
                fadeUp .35s ease;
        }


        .message.error {

            background:
                var(--coral-light);

            border:
                1px solid
                #efc4b8;

            color:
                var(--coral);
        }


        /* =====================================================
           CONFIRMACIÓN
        ===================================================== */

        .confirmation {

            margin-top: 23px;

            padding: 25px;

            background:
                linear-gradient(
                    145deg,
                    #e5f3ea,
                    #f4faf6
                );

            border:
                1px solid
                #c1e2cd;

            border-radius:
                18px;

            color:
                var(--ok);

            animation:
                confirmationIn .5s ease;
        }


        .confirmation h3 {

            margin-bottom: 15px;

            color:
                #286c47;

            font-family:
                var(--font-display);

            font-size: 24px;

            font-weight: 800;
        }


        .confirmation p {

            margin:
                8px 0;

            color:
                rgba(24,50,54,.70);

            font-size: 13px;
        }


        .confirmation strong {

            color:
                var(--turq-deep);
        }


        /* =====================================================
           ANIMACIONES
        ===================================================== */

        @keyframes fadeUp {

            from {

                opacity: 0;

                transform:
                    translateY(10px);
            }

            to {

                opacity: 1;

                transform:
                    translateY(0);
            }
        }


        @keyframes confirmationIn {

            from {

                opacity: 0;

                transform:
                    translateY(14px)
                    scale(.98);
            }

            to {

                opacity: 1;

                transform:
                    translateY(0)
                    scale(1);
            }
        }


        /* =====================================================
           ANTES DE VENIR
        ===================================================== */

        .before-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;
        }


        .before {

            padding: 20px;

            background:
                var(--turq-light);

            border:
                1px solid
                var(--line);

            border-radius:
                16px;

            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }


        .before:hover {

            transform:
                translateY(-4px);

            box-shadow:
                0 15px 35px
                rgba(20,60,65,.08);
        }


        .before h3 {

            margin-bottom: 7px;

            color:
                var(--turq-deep);

            font-family:
                var(--font-display);

            font-size: 18px;

            font-weight: 800;
        }


        .before p {

            color:
                rgba(24,50,54,.62);

            font-size: 13px;

            line-height: 1.65;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {

            padding:
                36px 20px;

            background:
                var(--turq-deep);

            color:
                rgba(255,255,255,.60);

            text-align:
                center;

            font-size: 12px;
        }


        footer strong {

            display: block;

            margin-bottom: 5px;

            color:
                white;

            font-family:
                var(--font-display);

            font-size: 15px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1000px) {

            .cards {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .food-grid {

                grid-template-columns:
                    repeat(3, 1fr);
            }


            .before-grid {

                grid-template-columns:
                    repeat(2, 1fr);
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


            .cards {

                grid-template-columns:
                    1fr;
            }


            .info-grid {

                grid-template-columns:
                    1fr;
            }


            .food-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .before-grid {

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


            .option {

                min-width:
                    100%;
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


            .food-grid {

                grid-template-columns:
                    1fr;
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
            Beach
            <span>Club</span>
        </h1>


        <p>

            Un espacio junto a la laguna para
            relajarte, disfrutar de la piscina,
            descansar y acompañar tu experiencia
            con gastronomía.

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
            Disfrutá tu día junto a la laguna
        </h2>

        <p>

            El Beach Club combina descanso,
            piscina y gastronomía en un mismo
            espacio. Reservá tu lugar y disfrutá
            una experiencia pensada para pasar
            el día sin preocuparte por la
            organización.

        </p>

    </section>


    <section class="stats">


        <div class="stat">

            <div class="stat-number">
                40
            </div>

            <div class="stat-label">
                Capacidad del club
            </div>

        </div>


        <div class="stat">

            <div class="stat-number">
                15
            </div>

            <div class="stat-label">
                Capacidad piscina
            </div>

        </div>


        <div class="stat">

            <div class="stat-number">
                12
            </div>

            <div class="stat-label">
                Reposeras
            </div>

        </div>


    </section>


    <section class="section">

        <h2 class="section-title">
            ¿Qué podés disfrutar?
        </h2>

        <p class="section-description">

            Todo lo necesario para pasar un
            momento de descanso y diversión
            dentro de Laguna Experience.

        </p>


        <div class="cards">


            <div class="card">

                <h3>
                    Piscina
                </h3>

                <p>

                    Refrescate y disfrutá de la
                    piscina durante tu visita
                    al Beach Club.

                </p>

            </div>


            <div class="card">

                <h3>
                    Reposera
                </h3>

                <p>

                    Contás con espacios de
                    descanso para relajarte
                    junto a la piscina y la laguna.

                </p>

            </div>


            <div class="card">

                <h3>
                    Gastronomía
                </h3>

                <p>

                    Elegí entre distintas opciones
                    de comidas y bebidas para
                    acompañar tu experiencia.

                </p>

            </div>


            <div class="card">

                <h3>
                    Relax
                </h3>

                <p>

                    Un espacio pensado para
                    desconectarte y disfrutar
                    de un día tranquilo.

                </p>

            </div>


        </div>

    </section>


    <section class="section">

        <h2 class="section-title">
            ¿Cómo funciona?
        </h2>

        <p class="section-description">

            Tu reserva organiza el acceso y los
            servicios disponibles durante tu visita.

        </p>


        <div class="info-grid">


            <div class="info">

                <strong>
                    1. Reservá
                </strong>

                <span>

                    Elegí la fecha y la cantidad
                    de personas con las que querés
                    disfrutar del Beach Club.

                </span>

            </div>


            <div class="info">

                <strong>
                    2. Ingresá
                </strong>

                <span>

                    Al llegar, tu reserva permite
                    identificar tu acceso al
                    Beach Club.

                </span>

            </div>


            <div class="info">

                <strong>
                    3. Disfrutá
                </strong>

                <span>

                    Podés utilizar los espacios
                    disponibles y disfrutar de
                    la piscina y el club.

                </span>

            </div>


            <div class="info">

                <strong>
                    4. Consumí
                </strong>

                <span>

                    Tus consumos pueden asociarse
                    a tu cuenta durante la visita.

                </span>

            </div>


        </div>

    </section>


    <section class="section">

        <h2 class="section-title">
            Servicios disponibles
        </h2>

        <p class="section-description">

            Estas son algunas de las opciones
            disponibles dentro del Beach Club.

        </p>


        <div class="info-grid">


            <div class="info">

                <strong>
                    Piscina
                </strong>

                <span>
                    Capacidad máxima de 15 personas.
                </span>

            </div>


            <div class="info">

                <strong>
                    Reposeras
                </strong>

                <span>
                    El espacio cuenta con 12 reposeras.
                </span>

            </div>


            <div class="info">

                <strong>
                    Gastronomía
                </strong>

                <span>
                    Comidas y bebidas disponibles.
                </span>

            </div>


            <div class="info">

                <strong>
                    Acceso
                </strong>

                <span>
                    La reserva permite gestionar el acceso.
                </span>

            </div>


        </div>

    </section>


    <section class="section">

        <h2 class="section-title">
            Gastronomía
        </h2>

        <p class="section-description">

            Algunas de las opciones disponibles
            para acompañar tu visita.

        </p>


        <div class="food-grid">


            <div class="food">

                <h3>
                    Agua mineral
                </h3>

                <div class="price">
                    $2.500
                </div>

            </div>


            <div class="food">

                <h3>
                    Tabla de picada
                </h3>

                <div class="price">
                    $14.500
                </div>

            </div>


            <div class="food">

                <h3>
                    Smoothie tropical
                </h3>

                <div class="price">
                    $4.800
                </div>

            </div>


            <div class="food">

                <h3>
                    Ensalada Laguna
                </h3>

                <div class="price">
                    $9.800
                </div>

            </div>


            <div class="food">

                <h3>
                    Bebidas
                </h3>

                <div class="price">
                    Disponibles en el club
                </div>

            </div>


        </div>

    </section>


    <section
        class="reservation"
        id="reserva"
    >


        <h2>
            Reservá tu experiencia
        </h2>


        <p>

            Elegí cuándo querés visitar el
            Beach Club y qué servicios querés
            disfrutar.

        </p>


        <?php if ($usuarioLogueado): ?>


            <div class="logged-box">

                Sesión iniciada como

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $nombreUsuario
                    );

                    ?>

                </strong>.

                Ya podés realizar tu reserva.

                <br><br>

                <a
                    href="beach-club.php?cerrar=1"
                    style="
                        color:var(--turq);
                        font-weight:800;
                    "
                >
                    Cerrar sesión
                </a>

            </div>


        <?php endif; ?>


        <?php if (
            $reservaError !== ""
        ): ?>


            <div class="message error">

                <?php

                echo htmlspecialchars(
                    $reservaError
                );

                ?>

            </div>


        <?php endif; ?>


        <?php if (
            !$reservaConfirmada
        ): ?>


            <form
                id="reservaForm"
                method="POST"
                action="beach-club.php#reserva"
            >


                <input
                    type="hidden"
                    name="accion"
                    value="reservar"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label for="fecha">
                            Fecha
                        </label>

                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            min="<?php echo date("Y-m-d"); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="personas">
                            Cantidad de personas
                        </label>

                        <input
                            type="number"
                            id="personas"
                            name="personas"
                            min="1"
                            max="40"
                            value="1"
                            required
                        >

                    </div>


                </div>


                <div
                    style="
                        margin-top:21px;
                    "
                >


                    <label class="services-title">
                        ¿Qué querés disfrutar?
                    </label>


                    <div class="options">


                        <div class="option">

                            <input
                                type="checkbox"
                                id="piscina"
                                name="piscina"
                                checked
                            >

                            <label for="piscina">

                                <strong>
                                    Piscina
                                </strong>

                                <span>
                                    Acceso a la piscina durante tu visita.
                                </span>

                            </label>

                        </div>


                        <div class="option">

                            <input
                                type="checkbox"
                                id="reposera"
                                name="reposera"
                                checked
                            >

                            <label for="reposera">

                                <strong>
                                    Reposera
                                </strong>

                                <span>
                                    Solicitar espacio de descanso.
                                </span>

                            </label>

                        </div>


                        <div class="option">

                            <input
                                type="checkbox"
                                id="gastronomia"
                                name="gastronomia"
                            >

                            <label for="gastronomia">

                                <strong>
                                    Gastronomía
                                </strong>

                                <span>
                                    Consultar opciones disponibles.
                                </span>

                            </label>

                        </div>


                    </div>


                </div>


                <button
                    type="submit"
                    class="primary reserve-button"
                >

                    Reservar Beach Club

                </button>


            </form>


            <?php if (
                !$usuarioLogueado
            ): ?>


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
                        action="beach-club.php#reserva"
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


                    <?php if (
                        $loginError !== ""
                    ): ?>


                        <div class="message error">

                            <?php

                            echo htmlspecialchars(
                                $loginError
                            );

                            ?>

                        </div>


                    <?php endif; ?>


                </div>


            <?php endif; ?>


        <?php endif; ?>


        <?php if (
            $reservaConfirmada
        ): ?>


            <div class="confirmation">


                <h3>
                    Reserva confirmada
                </h3>


                <p>

                    Tu reserva para el Beach Club
                    fue registrada correctamente.

                </p>


                <p>

                    <strong>
                        Código:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaCodigo
                    );

                    ?>

                </p>


                <p>

                    <strong>
                        Fecha:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaFecha
                    );

                    ?>

                </p>


                <p>

                    <strong>
                        Personas:
                    </strong>

                    <?php

                    echo $reservaPersonas;

                    ?>

                </p>


                <p>

                    <strong>
                        Servicios:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaServicios
                    );

                    ?>

                </p>


                <p
                    style="
                        margin-top:15px;
                    "
                >

                    Al llegar, presentá tu reserva
                    para gestionar el acceso al
                    Beach Club.

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

            Algunos datos importantes para
            disfrutar mejor tu experiencia.

        </p>


        <div class="before-grid">


            <div class="before">

                <h3>
                    Acceso
                </h3>

                <p>

                    Tené disponible tu reserva
                    al momento de ingresar.

                </p>

            </div>


            <div class="before">

                <h3>
                    Piscina
                </h3>

                <p>

                    La piscina cuenta con una
                    capacidad máxima de 15 personas.

                </p>

            </div>


            <div class="before">

                <h3>
                    Reposeras
                </h3>

                <p>

                    La disponibilidad de reposeras
                    es limitada.

                </p>

            </div>


        </div>


    </section>


</main>


<footer>

    <strong>
        Laguna Experience
    </strong>

    Beach Club · Smart Experience Park

</footer>


<script>


/* =========================================================
   FECHA MÍNIMA
========================================================= */

const fechaInput =
    document.getElementById(
        "fecha"
    );


if (fechaInput) {

    const hoy =
        new Date();


    const año =
        hoy.getFullYear();


    const mes =
        String(
            hoy.getMonth() + 1
        ).padStart(
            2,
            "0"
        );


    const dia =
        String(
            hoy.getDate()
        ).padStart(
            2,
            "0"
        );


    fechaInput.min =
        año +
        "-" +
        mes +
        "-" +
        dia;
}


/* =========================================================
   VALIDAR FORMULARIO
========================================================= */

const reservaForm =
    document.getElementById(
        "reservaForm"
    );


if (reservaForm) {

    reservaForm.addEventListener(
        "submit",
        function(event) {


            const personas =
                Number(
                    document.getElementById(
                        "personas"
                    ).value
                );


            if (
                personas < 1 ||
                personas > 40
            ) {

                event.preventDefault();


                alert(
                    "La cantidad de personas debe estar entre 1 y 40."
                );
            }


            if (
                fechaInput &&
                fechaInput.value === ""
            ) {

                event.preventDefault();


                alert(
                    "Seleccioná una fecha."
                );
            }

        }
    );
}


</script>


</body>

</html>