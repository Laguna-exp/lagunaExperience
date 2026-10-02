<?php

session_start();

require_once __DIR__ . "/../conexion.php";


/* =========================================================
   CERRAR SESIÓN
========================================================= */

if (isset($_GET["cerrar"])) {

    session_unset();

    session_destroy();

    header(
        "Location: cabanas.php"
    );

    exit;
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
            WHERE (
                email = '$usuarioEscapado'
                OR nombre = '$usuarioEscapado'
            )
            AND activo = 1
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


                header(
                    "Location: cabanas.php#reserva"
                );

                exit;

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
   HOSPEDAJES
========================================================= */

$hospedajes = [];


$sqlHospedajes = "
    SELECT
        id,
        tipo,
        capacidad,
        servicios,
        precio_noche,
        unidades_disponibles,
        lugar_id
    FROM hospedajes
    WHERE unidades_disponibles > 0
    ORDER BY id
";


$resultadoHospedajes =
    mysqli_query(
        $conexion,
        $sqlHospedajes
    );


if ($resultadoHospedajes) {

    while (
        $hospedaje =
        mysqli_fetch_assoc(
            $resultadoHospedajes
        )
    ) {

        $hospedajes[] =
            $hospedaje;
    }
}


/* =========================================================
   RESERVA
========================================================= */

$reservaConfirmada = false;

$reservaCodigo = "";

$reservaCabana = "";

$reservaEntrada = "";

$reservaSalida = "";

$reservaHuespedes = 0;

$reservaNoches = 0;

$reservaTotal = 0;

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

    } else {


        $usuario_id =
            (int) $_SESSION["usuario_id"];


        $hospedaje_id =
            (int) (
                $_POST["hospedaje_id"] ?? 0
            );


        $huespedes =
            (int) (
                $_POST["huespedes"] ?? 0
            );


        $entrada =
            trim(
                $_POST["entrada"] ?? ""
            );


        $salida =
            trim(
                $_POST["salida"] ?? ""
            );


        /* -------------------------------------------------
           BUSCAR CABAÑA
        ------------------------------------------------- */

        $hospedajeSeleccionado = null;


        foreach (
            $hospedajes as $hospedaje
        ) {

            if (
                (int)$hospedaje["id"] ===
                $hospedaje_id
            ) {

                $hospedajeSeleccionado =
                    $hospedaje;

                break;
            }
        }


        if (
            !$hospedajeSeleccionado
        ) {

            $reservaError =
                "Seleccioná una cabaña válida.";

        }


        /* -------------------------------------------------
           VALIDAR HUÉSPEDES
        ------------------------------------------------- */

        elseif (
            $huespedes < 1
        ) {

            $reservaError =
                "La cantidad de huéspedes debe ser mayor a 0.";

        }

        elseif (
            $huespedes >
            (int)$hospedajeSeleccionado["capacidad"]
        ) {

            $reservaError =
                "La cabaña seleccionada permite hasta " .
                $hospedajeSeleccionado["capacidad"] .
                " huéspedes.";

        }


        /* -------------------------------------------------
           VALIDAR FECHAS
        ------------------------------------------------- */

        elseif (
            $entrada === "" ||
            $salida === ""
        ) {

            $reservaError =
                "Seleccioná la fecha de entrada y de salida.";

        }


        else {

            $fechaEntrada =
                DateTime::createFromFormat(
                    "Y-m-d",
                    $entrada
                );


            $fechaSalida =
                DateTime::createFromFormat(
                    "Y-m-d",
                    $salida
                );


            if (
                !$fechaEntrada ||
                !$fechaSalida
            ) {

                $reservaError =
                    "Las fechas seleccionadas no son válidas.";

            }

            elseif (
                $fechaSalida <=
                $fechaEntrada
            ) {

                $reservaError =
                    "La fecha de salida debe ser posterior a la fecha de entrada.";

            }

            else {


                /* -----------------------------------------
                   CALCULAR NOCHES
                ----------------------------------------- */

                $diferencia =
                    $fechaEntrada->diff(
                        $fechaSalida
                    );


                $noches =
                    (int)$diferencia->days;


                $precioNoche =
                    (float)
                    $hospedajeSeleccionado[
                        "precio_noche"
                    ];


                $total =
                    $noches *
                    $precioNoche;


                /* -----------------------------------------
                   CONTROLAR DISPONIBILIDAD
                ----------------------------------------- */

                $sqlCantidad = "
                    SELECT COUNT(*) AS cantidad
                    FROM reservas
                    WHERE hospedaje_id = ?
                    AND estado = 'confirmada'
                    AND fecha < ?
                    AND fecha_salida > ?
                ";


                $stmtCantidad =
                    mysqli_prepare(
                        $conexion,
                        $sqlCantidad
                    );


                mysqli_stmt_bind_param(
                    $stmtCantidad,
                    "iss",
                    $hospedaje_id,
                    $salida,
                    $entrada
                );


                mysqli_stmt_execute(
                    $stmtCantidad
                );


                $resultadoCantidad =
                    mysqli_stmt_get_result(
                        $stmtCantidad
                    );


                $datosCantidad =
                    mysqli_fetch_assoc(
                        $resultadoCantidad
                    );


                $reservasExistentes =
                    (int)
                    $datosCantidad["cantidad"];


                mysqli_stmt_close(
                    $stmtCantidad
                );


                $unidadesDisponibles =
                    (int)
                    $hospedajeSeleccionado[
                        "unidades_disponibles"
                    ];


                if (
                    $reservasExistentes >=
                    $unidadesDisponibles
                ) {

                    $reservaError =
                        "No hay cabañas disponibles para esas fechas.";

                } else {


                    /* -------------------------------------
                       GENERAR CÓDIGO
                    ------------------------------------- */

                    $codigo =
                        "CAB" .
                        date("ymd") .
                        strtoupper(
                            substr(
                                md5(
                                    uniqid(
                                        "",
                                        true
                                    )
                                ),
                                0,
                                6
                            )
                        );


                    $horario =
                        "15:00:00";


                    $lugarId =
                        (int)
                        $hospedajeSeleccionado[
                            "lugar_id"
                        ];


                    /* -------------------------------------
                       GUARDAR RESERVA
                    ------------------------------------- */

                    $sqlReserva = "
                        INSERT INTO reservas
                        (
                            usuario_id,
                            actividad_id,
                            evento_id,
                            hospedaje_id,
                            lugar_id,
                            fecha,
                            fecha_salida,
                            horario,
                            cantidad,
                            total,
                            estado,
                            codigo
                        )
                        VALUES
                        (
                            ?,
                            NULL,
                            NULL,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            'confirmada',
                            ?
                        )
                    ";


                    $stmtReserva =
                        mysqli_prepare(
                            $conexion,
                            $sqlReserva
                        );


                    mysqli_stmt_bind_param(
                        $stmtReserva,
                        "iiisssids",
                        $usuario_id,
                        $hospedaje_id,
                        $lugarId,
                        $entrada,
                        $salida,
                        $horario,
                        $huespedes,
                        $total,
                        $codigo
                    );


                    if (
                        mysqli_stmt_execute(
                            $stmtReserva
                        )
                    ) {

                        $reservaConfirmada =
                            true;


                        $reservaCodigo =
                            $codigo;


                        $reservaCabana =
                            $hospedajeSeleccionado[
                                "tipo"
                            ];


                        $reservaEntrada =
                            date(
                                "d/m/Y",
                                strtotime($entrada)
                            );


                        $reservaSalida =
                            date(
                                "d/m/Y",
                                strtotime($salida)
                            );


                        $reservaHuespedes =
                            $huespedes;


                        $reservaNoches =
                            $noches;


                        $reservaTotal =
                            $total;

                    } else {

                        $reservaError =
                            "No se pudo guardar la reserva: " .
                            mysqli_error(
                                $conexion
                            );
                    }


                    mysqli_stmt_close(
                        $stmtReserva
                    );
                }
            }
        }
    }
}


/* =========================================================
   SESIÓN ACTUAL
========================================================= */

$usuarioLogueado =
    isset(
        $_SESSION["usuario_id"]
    );


$nombreUsuario =
    $_SESSION["usuario"] ?? "";


$fechaHoy =
    date("Y-m-d");


/* =========================================================
   ESTADÍSTICAS
========================================================= */

$totalHospedajes =
    count($hospedajes);


$capacidadMaxima = 0;

$unidadesTotales = 0;


foreach (
    $hospedajes as $hospedaje
) {

    if (
        (int)$hospedaje["capacidad"] >
        $capacidadMaxima
    ) {

        $capacidadMaxima =
            (int)$hospedaje["capacidad"];
    }


    $unidadesTotales +=
        (int)$hospedaje[
            "unidades_disponibles"
        ];
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
        Cabañas · Laguna Experience
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
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <style>

        :root {

            --green:
                #0e6b5c;

            --green-deep:
                #0a4c42;

            --green-light:
                #e4f2ee;

            --sand:
                #f3ead6;

            --paper:
                #fffdf8;

            --coral:
                #d9633b;

            --coral-light:
                #fbe6dc;

            --gold:
                #c9971f;

            --gold-light:
                #f8efd7;

            --line:
                #e2d9c4;

            --ok:
                #1c7a45;

            --font-body:
                "DM Sans",
                sans-serif;

            --font-display:
                "Manrope",
                sans-serif;

            --shadow:
                0 24px 70px
                rgba(30, 65, 56, .12);
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
                    var(--green-light) 100%
                );

            color:
                #182823;

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
                    rgba(10,76,66,.97),
                    rgba(14,107,92,.82),
                    rgba(76,112,73,.48)
                ),

                url("https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=2000&q=90")
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
                rgba(201,151,31,.18);

            filter:
                blur(10px);
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
                    rgba(10,76,66,.55)
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
                #dcebd8;

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
                #d8e8c5;
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
                var(--green-deep);

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
                #fffffc;
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
                rgba(30,65,56,.15);
        }


        .hero-card h2 {

            margin-bottom: 10px;

            color:
                var(--green-deep);

            font-family:
                var(--font-display);

            font-size: 34px;

            font-weight: 800;

            letter-spacing: -.05em;
        }


        .hero-card p {

            max-width: 850px;

            color:
                rgba(24,40,35,.65);

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
                rgba(30,65,56,.15);
        }


        .stat-number {

            color:
                var(--green);

            font-family:
                var(--font-display);

            font-size: 35px;

            font-weight: 800;
        }


        .stat-label {

            margin-top: 5px;

            color:
                rgba(24,40,35,.55);

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
                rgba(30,65,56,.14);
        }


        .section-title {

            margin:
                0 0 8px;

            color:
                var(--green-deep);

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
                rgba(24,40,35,.62);

            font-size: 14px;

            line-height: 1.7;
        }


        /* =====================================================
           CABAÑAS
        ===================================================== */

        .cabana-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 16px;
        }


        .cabana-card {

            padding: 22px;

            background:
                linear-gradient(
                    145deg,
                    #ffffff,
                    var(--green-light)
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


        .cabana-card:hover {

            transform:
                translateY(-6px);

            border-color:
                rgba(14,107,92,.45);

            box-shadow:
                0 20px 48px
                rgba(30,65,56,.11);
        }


        .cabana-card h3 {

            margin-bottom: 9px;

            color:
                var(--green-deep);

            font-family:
                var(--font-display);

            font-size: 21px;

            font-weight: 800;
        }


        .cabana-card p {

            margin-bottom: 15px;

            color:
                rgba(24,40,35,.62);

            font-size: 13px;

            line-height: 1.65;
        }


        .cabana-tags {

            display: flex;

            flex-wrap: wrap;

            gap: 7px;

            margin-bottom: 17px;
        }


        .tag {

            display: inline-flex;

            align-items: center;

            min-height: 27px;

            padding:
                0 10px;

            border-radius: 999px;

            background:
                var(--sand);

            color:
                var(--green-deep);

            font-size: 10px;

            font-weight: 800;
        }


        .tag.green {

            background:
                var(--green-light);
        }


        .cabana-price {

            padding-top: 15px;

            border-top:
                1px solid
                var(--line);
        }


        .cabana-price span {

            display: block;

            margin-bottom: 3px;

            color:
                rgba(24,40,35,.50);

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .08em;

            text-transform: uppercase;
        }


        .cabana-price strong {

            color:
                var(--coral);

            font-family:
                var(--font-display);

            font-size: 23px;

            font-weight: 800;
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
                    var(--green-light)
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
                rgba(14,107,92,.45);

            box-shadow:
                0 20px 48px
                rgba(30,65,56,.11);
        }


        .card h3 {

            margin-bottom: 8px;

            color:
                var(--green-deep);

            font-family:
                var(--font-display);

            font-size: 19px;

            font-weight: 800;
        }


        .card p {

            color:
                rgba(24,40,35,.62);

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
                rgba(30,65,56,.09);
        }


        .info strong {

            display: block;

            margin-bottom: 7px;

            color:
                var(--green);

            font-family:
                var(--font-display);

            font-size: 19px;

            font-weight: 800;
        }


        .info span {

            color:
                rgba(24,40,35,.62);

            font-size: 13px;

            line-height: 1.6;
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
                    var(--green-light),
                    #ffffff
                );

            border:
                1px solid
                #c9dfd8;

            border-radius:
                26px;

            box-shadow:
                var(--shadow);
        }


        .reservation h2 {

            margin-bottom: 8px;

            color:
                var(--green-deep);

            font-family:
                var(--font-display);

            font-size: 34px;

            font-weight: 800;

            letter-spacing: -.05em;
        }


        .reservation > p {

            margin-bottom: 25px;

            color:
                rgba(24,40,35,.62);

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
        }


        .logged-box strong {

            color:
                #286c47;
        }


        .login-box {

            margin-top: 21px;

            padding: 22px;

            background:
                rgba(243,234,214,.75);

            border:
                1px solid
                #e4d8bd;

            border-radius:
                18px;
        }


        .login-box h3 {

            margin-bottom: 7px;

            color:
                var(--green-deep);

            font-family:
                var(--font-display);

            font-size: 21px;

            font-weight: 800;
        }


        .login-box > p {

            margin-bottom: 17px;

            color:
                rgba(24,40,35,.62);

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
                var(--green-deep);

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
                #182823;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .login-grid input:focus {

            border-color:
                var(--green);

            box-shadow:
                0 0 0 4px
                rgba(14,107,92,.10);
        }


        /* =====================================================
           FORMULARIO
        ===================================================== */

        .form-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 17px;
        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            color:
                var(--green-deep);

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
                #182823;

            font-size: 14px;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .form-group input:focus,
        .form-group select:focus {

            border-color:
                var(--green);

            box-shadow:
                0 0 0 4px
                rgba(14,107,92,.11);
        }


        .capacity-info {

            margin-top: 8px;

            color:
                rgba(24,40,35,.55);

            font-size: 11px;
        }


        /* =====================================================
           TOTAL
        ===================================================== */

        .total-box {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-top: 20px;

            padding:
                17px 19px;

            background:
                var(--sand);

            border:
                1px solid
                var(--line);

            border-radius:
                15px;
        }


        .total-box span {

            color:
                rgba(24,40,35,.58);

            font-size: 12px;

            font-weight: 700;
        }


        .total-box strong {

            color:
                var(--coral);

            font-family:
                var(--font-display);

            font-size: 25px;

            font-weight: 800;
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
                    var(--green),
                    var(--green-deep)
                );

            color:
                white;

            font-size: 13px;

            font-weight: 800;

            box-shadow:
                0 14px 30px
                rgba(14,107,92,.20);

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
                rgba(14,107,92,.29);

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
                rgba(24,40,35,.70);

            font-size: 13px;
        }


        .confirmation strong {

            color:
                var(--green-deep);
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
                var(--green-light);

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
                rgba(30,65,56,.08);
        }


        .before h3 {

            margin-bottom: 7px;

            color:
                var(--green-deep);

            font-family:
                var(--font-display);

            font-size: 18px;

            font-weight: 800;
        }


        .before p {

            color:
                rgba(24,40,35,.62);

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
                var(--green-deep);

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

            .cabana-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .cards {

                grid-template-columns:
                    repeat(2, 1fr);
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

                padding: 23px;
            }


            .stats {

                grid-template-columns:
                    1fr;
            }


            .cabana-grid {

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


            .total-box {

                align-items:
                    flex-start;

                flex-direction:
                    column;

                gap: 5px;
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

                padding: 19px;
            }


            .back-button {

                width: 100%;
            }
        }

    </style>

</head>


<body>


<header>

    <div class="header-content">

        <div class="eyebrow">

            Laguna Experience · Hospedaje

        </div>


        <h1>

            Cabañas

            <span>

                Laguna

            </span>

        </h1>


        <p>

            Un espacio para descansar rodeado
            de naturaleza y disfrutar Laguna
            Experience durante una estadía completa.

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

            Tu lugar para desconectar

        </h2>


        <p>

            Elegí una cabaña, organizá tu estadía
            y disfrutá de un espacio rodeado de
            naturaleza. Una experiencia pensada
            para descansar, compartir y aprovechar
            todo lo que ofrece Laguna Experience.

        </p>

    </section>


    <section class="stats">


        <div class="stat">

            <div class="stat-number">

                <?php

                echo $totalHospedajes;

                ?>

            </div>

            <div class="stat-label">

                Opciones disponibles

            </div>

        </div>


        <div class="stat">

            <div class="stat-number">

                <?php

                echo $capacidadMaxima;

                ?>

            </div>

            <div class="stat-label">

                Huéspedes máximos

            </div>

        </div>


        <div class="stat">

            <div class="stat-number">

                <?php

                echo $unidadesTotales;

                ?>

            </div>

            <div class="stat-label">

                Unidades disponibles

            </div>

        </div>


    </section>


    <section class="section">

        <h2 class="section-title">

            Elegí tu cabaña

        </h2>


        <p class="section-description">

            Conocé las opciones disponibles y
            elegí la que mejor se adapte a tu
            estadía.

        </p>


        <div class="cabana-grid">


            <?php if (count($hospedajes) > 0): ?>


                <?php foreach (
                    $hospedajes as $hospedaje
                ): ?>


                    <div class="cabana-card">


                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $hospedaje["tipo"]
                            );

                            ?>

                        </h3>


                        <p>

                            <?php

                            echo htmlspecialchars(
                                $hospedaje["servicios"]
                            );

                            ?>

                        </p>


                        <div class="cabana-tags">

                            <span class="tag">

                                Hasta

                                <?php

                                echo
                                $hospedaje["capacidad"];

                                ?>

                                huéspedes

                            </span>


                            <span class="tag green">

                                Naturaleza

                            </span>

                        </div>


                        <div class="cabana-price">

                            <span>

                                Por noche

                            </span>


                            <strong>

                                $

                                <?php

                                echo number_format(
                                    $hospedaje["precio_noche"],
                                    0,
                                    ",",
                                    "."
                                );

                                ?>

                            </strong>

                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="card">

                    <h3>

                        Sin disponibilidad

                    </h3>

                    <p>

                        Actualmente no hay cabañas
                        disponibles para reservar.

                    </p>

                </div>


            <?php endif; ?>


        </div>

    </section>


    <section class="section">

        <h2 class="section-title">

            Viví más que una estadía

        </h2>


        <p class="section-description">

            La experiencia combina descanso,
            naturaleza y diferentes actividades
            para disfrutar durante tu visita.

        </p>


        <div class="cards">


            <div class="card">

                <h3>

                    Naturaleza

                </h3>

                <p>

                    Disfrutá de un entorno natural
                    pensado para desconectar de
                    la rutina.

                </p>

            </div>


            <div class="card">

                <h3>

                    Actividades

                </h3>

                <p>

                    Aprovechá las distintas
                    propuestas disponibles
                    dentro de Laguna Experience.

                </p>

            </div>


            <div class="card">

                <h3>

                    Gastronomía

                </h3>

                <p>

                    Completá tu estadía con
                    diferentes opciones
                    gastronómicas.

                </p>

            </div>


            <div class="card">

                <h3>

                    Descanso

                </h3>

                <p>

                    Un espacio cómodo para
                    descansar después de
                    disfrutar el parque.

                </p>

            </div>


        </div>

    </section>


    <section class="section">

        <h2 class="section-title">

            ¿Cómo funciona?

        </h2>


        <p class="section-description">

            Reservar tu estadía es simple y
            podés organizar todo desde esta página.

        </p>


        <div class="info-grid">


            <div class="info">

                <strong>

                    1. Elegí tu cabaña

                </strong>

                <span>

                    Seleccioná la opción que mejor
                    se adapte a la cantidad de
                    huéspedes y servicios que buscás.

                </span>

            </div>


            <div class="info">

                <strong>

                    2. Seleccioná las fechas

                </strong>

                <span>

                    Indicá la fecha de entrada y
                    la fecha de salida de tu estadía.

                </span>

            </div>


            <div class="info">

                <strong>

                    3. Confirmá tu reserva

                </strong>

                <span>

                    Iniciá sesión y completá los
                    datos necesarios para registrar
                    la reserva.

                </span>

            </div>


            <div class="info">

                <strong>

                    4. Disfrutá tu estadía

                </strong>

                <span>

                    Al finalizar la reserva recibirás
                    un código que identifica tu estadía.

                </span>

            </div>


        </div>

    </section>


    <section
        class="reservation"
        id="reserva"
    >


        <h2>

            Reservá tu cabaña

        </h2>


        <p>

            Elegí tu alojamiento, seleccioná
            las fechas y prepará tu estadía
            en Laguna Experience.

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
                    href="cabanas.php?cerrar=1"
                    style="
                        color:var(--green);
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
                action="cabanas.php#reserva"
            >


                <input
                    type="hidden"
                    name="accion"
                    value="reservar"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label for="hospedaje_id">

                            Cabaña

                        </label>


                        <select
                            id="hospedaje_id"
                            name="hospedaje_id"
                            required
                        >

                            <option value="">

                                Seleccioná una cabaña

                            </option>


                            <?php foreach (
                                $hospedajes as $hospedaje
                            ): ?>


                                <option
                                    value="<?php echo $hospedaje["id"]; ?>"
                                    data-capacidad="<?php echo $hospedaje["capacidad"]; ?>"
                                    data-precio="<?php echo $hospedaje["precio_noche"]; ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $hospedaje["tipo"]
                                    );

                                    ?>

                                    -

                                    $

                                    <?php

                                    echo number_format(
                                        $hospedaje["precio_noche"],
                                        0,
                                        ",",
                                        "."
                                    );

                                    ?>

                                    / noche

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <div
                            class="capacity-info"
                            id="capacidadTexto"
                        >

                            Seleccioná una cabaña
                            para consultar su capacidad.

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="huespedes">

                            Cantidad de huéspedes

                        </label>


                        <input
                            type="number"
                            id="huespedes"
                            name="huespedes"
                            min="1"
                            value="1"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="entrada">

                            Fecha de entrada

                        </label>


                        <input
                            type="date"
                            id="entrada"
                            name="entrada"
                            min="<?php echo $fechaHoy; ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="salida">

                            Fecha de salida

                        </label>


                        <input
                            type="date"
                            id="salida"
                            name="salida"
                            min="<?php echo $fechaHoy; ?>"
                            required
                        >

                    </div>


                </div>


                <div class="total-box">

                    <span id="detalleNoches">

                        Seleccioná las fechas
                        para calcular el total.

                    </span>


                    <strong id="totalPrecio">

                        $0

                    </strong>

                </div>


                <button
                    type="submit"
                    class="primary reserve-button"
                >

                    Reservar cabaña

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
                        action="cabanas.php#reserva"
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

                    Tu reserva de alojamiento
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

                        Cabaña:

                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaCabana
                    );

                    ?>

                </p>


                <p>

                    <strong>

                        Entrada:

                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaEntrada
                    );

                    ?>

                </p>


                <p>

                    <strong>

                        Salida:

                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaSalida
                    );

                    ?>

                </p>


                <p>

                    <strong>

                        Huéspedes:

                    </strong>

                    <?php

                    echo $reservaHuespedes;

                    ?>

                </p>


                <p>

                    <strong>

                        Noches:

                    </strong>

                    <?php

                    echo $reservaNoches;

                    ?>

                </p>


                <p>

                    <strong>

                        Total:

                    </strong>

                    $

                    <?php

                    echo number_format(
                        $reservaTotal,
                        0,
                        ",",
                        "."
                    );

                    ?>

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
            organizar mejor tu estadía.

        </p>


        <div class="before-grid">


            <div class="before">

                <h3>

                    Fechas

                </h3>

                <p>

                    Verificá correctamente las
                    fechas de entrada y salida
                    antes de confirmar.

                </p>

            </div>


            <div class="before">

                <h3>

                    Huéspedes

                </h3>

                <p>

                    La cantidad de huéspedes
                    debe respetar la capacidad
                    máxima de la cabaña elegida.

                </p>

            </div>


            <div class="before">

                <h3>

                    Ingreso

                </h3>

                <p>

                    Conservá el código de reserva
                    para presentarlo al momento
                    de ingresar.

                </p>

            </div>


        </div>


    </section>


</main>


<footer>

    <strong>

        Laguna Experience

    </strong>

    Cabañas · Smart Experience Park

</footer>


<script>


/* =========================================================
   ELEMENTOS
========================================================= */

const hospedajeInput =
    document.getElementById(
        "hospedaje_id"
    );


const huespedesInput =
    document.getElementById(
        "huespedes"
    );


const entradaInput =
    document.getElementById(
        "entrada"
    );


const salidaInput =
    document.getElementById(
        "salida"
    );


const totalPrecio =
    document.getElementById(
        "totalPrecio"
    );


const detalleNoches =
    document.getElementById(
        "detalleNoches"
    );


const capacidadTexto =
    document.getElementById(
        "capacidadTexto"
    );


/* =========================================================
   HOSPEDAJES
========================================================= */

const hospedajes =
    <?php

    echo json_encode(
        $hospedajes
    );

    ?>;


/* =========================================================
   OBTENER CABAÑA
========================================================= */

function obtenerHospedaje() {

    if (!hospedajeInput) {

        return null;
    }


    const id =
        Number(
            hospedajeInput.value
        );


    for (
        let i = 0;
        i < hospedajes.length;
        i++
    ) {

        if (
            Number(
                hospedajes[i].id
            ) === id
        ) {

            return hospedajes[i];
        }
    }


    return null;
}


/* =========================================================
   ACTUALIZAR CAPACIDAD
========================================================= */

function actualizarCapacidad() {

    const hospedaje =
        obtenerHospedaje();


    if (
        !capacidadTexto ||
        !huespedesInput
    ) {

        return;
    }


    if (!hospedaje) {

        capacidadTexto.textContent =
            "Seleccioná una cabaña para consultar su capacidad.";

        huespedesInput.max =
            "";

        return;
    }


    capacidadTexto.textContent =
        "Capacidad máxima: " +
        hospedaje.capacidad +
        " huéspedes.";


    huespedesInput.max =
        hospedaje.capacidad;


    if (
        Number(
            huespedesInput.value
        ) >
        Number(
            hospedaje.capacidad
        )
    ) {

        huespedesInput.value =
            hospedaje.capacidad;
    }


    calcularTotal();
}


/* =========================================================
   CALCULAR NOCHES
========================================================= */

function calcularNoches() {

    if (
        !entradaInput ||
        !salidaInput
    ) {

        return 0;
    }


    if (
        entradaInput.value === "" ||
        salidaInput.value === ""
    ) {

        return 0;
    }


    const entrada =
        new Date(
            entradaInput.value +
            "T00:00:00"
        );


    const salida =
        new Date(
            salidaInput.value +
            "T00:00:00"
        );


    const diferencia =
        salida - entrada;


    const noches =
        diferencia /
        (
            1000 *
            60 *
            60 *
            24
        );


    if (noches <= 0) {

        return 0;
    }


    return noches;
}


/* =========================================================
   CALCULAR TOTAL
========================================================= */

function calcularTotal() {

    if (
        !totalPrecio ||
        !detalleNoches
    ) {

        return;
    }


    const hospedaje =
        obtenerHospedaje();


    const noches =
        calcularNoches();


    if (
        !hospedaje ||
        noches <= 0
    ) {

        totalPrecio.textContent =
            "$0";


        detalleNoches.textContent =
            "Seleccioná las fechas para calcular el total.";

        return;
    }


    const precio =
        Number(
            hospedaje.precio_noche
        );


    const total =
        precio *
        noches;


    totalPrecio.textContent =
        "$" +
        total.toLocaleString(
            "es-AR"
        );


    detalleNoches.textContent =
        noches +
        (
            noches === 1
                ? " noche"
                : " noches"
        ) +
        " × $" +
        precio.toLocaleString(
            "es-AR"
        );
}


/* =========================================================
   EVENTOS
========================================================= */

if (hospedajeInput) {

    hospedajeInput.addEventListener(
        "change",
        actualizarCapacidad
    );
}


if (huespedesInput) {

    huespedesInput.addEventListener(
        "input",
        function() {

            const hospedaje =
                obtenerHospedaje();


            if (
                hospedaje &&
                Number(
                    huespedesInput.value
                ) >
                Number(
                    hospedaje.capacidad
                )
            ) {

                huespedesInput.value =
                    hospedaje.capacidad;
            }

        }
    );
}


if (entradaInput) {

    entradaInput.addEventListener(
        "change",
        function() {

            if (salidaInput) {

                salidaInput.min =
                    entradaInput.value;
            }


            calcularTotal();
        }
    );
}


if (salidaInput) {

    salidaInput.addEventListener(
        "change",
        calcularTotal
    );
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


            const hospedaje =
                obtenerHospedaje();


            const huespedes =
                Number(
                    huespedesInput.value
                );


            const noches =
                calcularNoches();


            if (!hospedaje) {

                event.preventDefault();

                alert(
                    "Seleccioná una cabaña."
                );

                return;
            }


            if (
                huespedes < 1 ||
                huespedes >
                Number(
                    hospedaje.capacidad
                )
            ) {

                event.preventDefault();

                alert(
                    "La cantidad de huéspedes supera la capacidad de la cabaña."
                );

                return;
            }


            if (
                entradaInput.value === "" ||
                salidaInput.value === ""
            ) {

                event.preventDefault();

                alert(
                    "Seleccioná las fechas de entrada y salida."
                );

                return;
            }


            if (noches <= 0) {

                event.preventDefault();

                alert(
                    "La fecha de salida debe ser posterior a la fecha de entrada."
                );

                return;
            }

        }
    );
}


/* =========================================================
   INICIO
========================================================= */

actualizarCapacidad();

calcularTotal();


</script>


</body>

</html>