<?php

session_start();

require_once "../conexion.php";


/* =========================================================
   VARIABLES DE SESIÓN
========================================================= */

$usuarioLogueado = isset($_SESSION["usuario_id"]);
$nombreUsuario = "";

if ($usuarioLogueado) {
    $nombreUsuario = $_SESSION["usuario"] ?? "";
}


/* =========================================================
   CERRAR SESIÓN
========================================================= */

if (isset($_GET["cerrar"])) {

    session_unset();
    session_destroy();

    header("Location: experiencias-recreacion.php");
    exit;
}


/* =========================================================
   LOGIN
========================================================= */

$loginError = "";
$reservaError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";


    if ($accion === "login") {

        $usuario = trim($_POST["usuario"] ?? "");
        $password = trim($_POST["password"] ?? "");


        if ($usuario === "" || $password === "") {

            $loginError = "Completá todos los campos.";

        } else {

            $usuarioSeguro = mysqli_real_escape_string(
                $conexion,
                $usuario
            );


            $sqlUsuario = "
                SELECT *
                FROM usuarios
                WHERE email = '$usuarioSeguro'
                OR nombre = '$usuarioSeguro'
                LIMIT 1
            ";


            $resultadoUsuario = mysqli_query(
                $conexion,
                $sqlUsuario
            );


            if (
                $resultadoUsuario &&
                mysqli_num_rows($resultadoUsuario) > 0
            ) {

                $datosUsuario = mysqli_fetch_assoc(
                    $resultadoUsuario
                );


                $passwordCorrecta = false;


                if (
                    isset($datosUsuario["password_hash"]) &&
                    password_verify(
                        $password,
                        $datosUsuario["password_hash"]
                    )
                ) {

                    $passwordCorrecta = true;

                } elseif (
                    isset($datosUsuario["password"]) &&
                    $password === $datosUsuario["password"]
                ) {

                    $passwordCorrecta = true;
                }


                if ($passwordCorrecta) {

                    $_SESSION["usuario_id"] =
                        $datosUsuario["id"];

                    $_SESSION["usuario"] =
                        $datosUsuario["nombre"];

                    $_SESSION["email"] =
                        $datosUsuario["email"];


                    header(
                        "Location: experiencias-recreacion.php#reserva"
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
}


/* =========================================================
   BUSCAR EXPERIENCIAS
========================================================= */

$experiencias = [];

$sql = "
    SELECT
        id,
        nombre,
        descripcion,
        edad_minima AS edad_min,
        horario AS modalidad,
        dificultad AS tipo,
        capacidad AS cupo_total,
        cupos_disponibles AS cupo_restante,
        1 AS requiere_reserva,
        CASE
            WHEN estado IN ('cerrado', 'mantenimiento') THEN 1
            ELSE 0
        END AS suspendida
    FROM actividades
    ORDER BY id ASC
";


$resultado = mysqli_query(
    $conexion,
    $sql
);


if ($resultado) {

    while ($fila = mysqli_fetch_assoc($resultado)) {

        $experiencias[] = $fila;
    }
}


/* =========================================================
   RESERVAR
========================================================= */

$mensaje = "";
$tipoMensaje = "";
$confirmacion = null;


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";


    if ($accion === "reservar") {

        /* =====================================================
           VALIDAR SESIÓN
        ===================================================== */

        if (!isset($_SESSION["usuario_id"])) {

            $reservaError =
                "Tenés que iniciar sesión para realizar una reserva.";

        } else {

            $experienciaId =
                intval($_POST["experiencia_id"] ?? 0);

            $nombre =
                trim($_POST["nombre"] ?? "");

            $edad =
                intval($_POST["edad"] ?? 0);


            /* =================================================
               BUSCAR EXPERIENCIA
            ================================================= */

            $experienciaEncontrada = null;


            foreach ($experiencias as $experiencia) {

                if (
                    intval($experiencia["id"])
                    === $experienciaId
                ) {

                    $experienciaEncontrada =
                        $experiencia;

                    break;
                }
            }


            if (!$experienciaEncontrada) {

                $reservaError =
                    "Seleccioná una experiencia válida.";
            }


            /* =================================================
               VALIDAR NOMBRE
            ================================================= */

            if (
                $reservaError === "" &&
                $nombre === ""
            ) {

                $reservaError =
                    "Ingresá el nombre del participante.";
            }


            /* =================================================
               VALIDAR EDAD
            ================================================= */

            if (
                $reservaError === "" &&
                ($edad < 1 || $edad > 99)
            ) {

                $reservaError =
                    "Ingresá una edad válida.";
            }


            /* =================================================
               VALIDAR SUSPENDIDA
            ================================================= */

            if ($reservaError === "") {

                $suspendida =
                    intval(
                        $experienciaEncontrada["suspendida"]
                    );


                if ($suspendida === 1) {

                    $reservaError =
                        'La experiencia "' .
                        $experienciaEncontrada["nombre"] .
                        '" se encuentra temporalmente no disponible debido a las condiciones climáticas.';
                }
            }


            /* =================================================
               VALIDAR EDAD MÍNIMA
            ================================================= */

            if ($reservaError === "") {

                $edadMinima =
                    intval(
                        $experienciaEncontrada["edad_min"]
                    );


                if ($edad < $edadMinima) {

                    $reservaError =
                        "Esta experiencia requiere una edad mínima de " .
                        $edadMinima .
                        " años.";
                }
            }


            /* =================================================
               VALIDAR CUPOS
            ================================================= */

            if ($reservaError === "") {

                $requiereReserva =
                    intval(
                        $experienciaEncontrada["requiere_reserva"]
                    );


                $cupoRestante =
                    intval(
                        $experienciaEncontrada["cupo_restante"]
                    );


                if (
                    $requiereReserva === 1 &&
                    $cupoRestante <= 0
                ) {

                    $reservaError =
                        "No quedan cupos disponibles para esta experiencia.";
                }
            }


            /* =================================================
               GUARDAR RESERVA
            ================================================= */

            if ($reservaError === "") {

                $usuarioId =
                    intval(
                        $_SESSION["usuario_id"]
                    );


                $requiereReserva =
                    intval(
                        $experienciaEncontrada["requiere_reserva"]
                    );


                if ($requiereReserva === 0) {

                    $mensaje =
                        "Esta experiencia no requiere reserva previa.";

                    $tipoMensaje = "ok";

                } else {

                    $nombreSeguro =
                        mysqli_real_escape_string(
                            $conexion,
                            $nombre
                        );


                    $modalidad =
                        mysqli_real_escape_string(
                            $conexion,
                            $experienciaEncontrada["modalidad"]
                        );


                    $numero =
                        rand(1000, 9999);


                    $codigo =
                        "EXP-" . $numero;


                    /* =========================================
                       BUSCAR LUGAR
                    ========================================= */

                    $lugarId = 0;


                    $sqlLugar = "
                        SELECT id
                        FROM lugares
                        WHERE nombre LIKE '%Experiencias%'
                        OR nombre LIKE '%Recreación%'
                        OR slug LIKE '%experiencia%'
                        LIMIT 1
                    ";


                    $resultadoLugar =
                        mysqli_query(
                            $conexion,
                            $sqlLugar
                        );


                    if (
                        $resultadoLugar &&
                        mysqli_num_rows($resultadoLugar) > 0
                    ) {

                        $lugar =
                            mysqli_fetch_assoc(
                                $resultadoLugar
                            );


                        $lugarId =
                            intval(
                                $lugar["id"]
                            );
                    }


                    if ($lugarId <= 0) {

                        $lugarId = 1;
                    }


                    /* =========================================
                       FECHA
                    ========================================= */

                    $fecha =
                        date("Y-m-d");


                    /* =========================================
                       HORARIO
                    ========================================= */

                    $horario =
                        "00:00:00";


                    if (
                        preg_match(
                            '/([0-9]{2}):([0-9]{2})/',
                            $experienciaEncontrada["modalidad"],
                            $coincidencia
                        )
                    ) {

                        $horario =
                            $coincidencia[1] .
                            ":" .
                            $coincidencia[2] .
                            ":00";
                    }


                    /* =========================================
                       ACTIVIDAD
                    ========================================= */

                    $actividadId =
                        intval(
                            $experienciaEncontrada["id"]
                        );


                    /* =========================================
                       INSERTAR RESERVA
                    ========================================= */

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
                            $actividadId,
                            NULL,
                            $lugarId,
                            '$fecha',
                            '$horario',
                            1,
                            0,
                            'confirmada',
                            '$codigo'
                        )
                    ";


                    $reservaGuardada =
                        mysqli_query(
                            $conexion,
                            $sqlReserva
                        );


                    if ($reservaGuardada) {

                        /* =====================================
                           DESCONTAR CUPO
                        ===================================== */

                        $experienciaDbId =
                            intval(
                                $experienciaEncontrada["id"]
                            );


                        $sqlCupo = "
                            UPDATE actividades
                            SET cupos_disponibles =
                                cupos_disponibles - 1
                            WHERE id = $experienciaDbId
                            AND cupos_disponibles > 0
                        ";


                        mysqli_query(
                            $conexion,
                            $sqlCupo
                        );


                        $mensaje =
                            "Participación confirmada correctamente.";

                        $tipoMensaje =
                            "ok";


                        $confirmacion = [

                            "nombre" =>
                                $nombre,

                            "experiencia" =>
                                $experienciaEncontrada["nombre"],

                            "modalidad" =>
                                $experienciaEncontrada["modalidad"],

                            "codigo" =>
                                $codigo
                        ];


                        /* =====================================
                           RECARGAR EXPERIENCIAS
                        ===================================== */

                        $experiencias = [];


                        $resultado =
                            mysqli_query(
                                $conexion,
                                "
                                SELECT
                                    id,
                                    nombre,
                                    descripcion,
                                    edad_minima AS edad_min,
                                    horario AS modalidad,
                                    dificultad AS tipo,
                                    capacidad AS cupo_total,
                                    cupos_disponibles AS cupo_restante,
                                    1 AS requiere_reserva,
                                    CASE
                                        WHEN estado IN ('cerrado', 'mantenimiento') THEN 1
                                        ELSE 0
                                    END AS suspendida
                                FROM actividades
                                ORDER BY id ASC
                                "
                            );


                        if ($resultado) {

                            while (
                                $fila =
                                mysqli_fetch_assoc(
                                    $resultado
                                )
                            ) {

                                $experiencias[] =
                                    $fila;
                            }
                        }

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
        Experiencias & Recreación · Laguna Experience
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

            --deep: #153A31;
            --mid: #2E6B57;
            --sand: #F5F6EE;
            --sand2: #E6EFE4;
            --ink: #122420;
            --accent: #3D8A72;
            --accent-dark: #2E6B57;

            --ok: #4C9A6A;
            --bad: #C24A3A;
            --warn: #C98A2B;

            --line: #DCE6D6;
            --white: #FFFFFF;

            --radius: 20px;

            --shadow:
                0 18px 45px rgba(21, 58, 49, .09);

            --shadow-hover:
                0 24px 55px rgba(21, 58, 49, .14);
        }


        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            margin: 0;

            background:
                linear-gradient(
                    180deg,
                    var(--sand) 0%,
                    #F8FAF5 45%,
                    var(--sand) 100%
                );

            color: var(--ink);

            font-family: "DM Sans", sans-serif;

            line-height: 1.5;
        }


        a {
            text-decoration: none;
        }


        button,
        input,
        select {
            font-family: "DM Sans", sans-serif;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        header {

            min-height: 510px;

            display: flex;

            align-items: center;

            padding: 80px 8% 120px;

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    rgba(21, 58, 49, .99),
                    rgba(46, 107, 87, .92)
                );

            color: #FFFFFF;
        }


        header::before {

            content: "";

            position: absolute;

            width: 520px;
            height: 520px;

            border-radius: 50%;

            background:
                rgba(255, 255, 255, .05);

            right: -170px;
            top: -240px;
        }


        header::after {

            content: "";

            position: absolute;

            width: 340px;
            height: 340px;

            border-radius: 50%;

            background:
                rgba(255, 255, 255, .04);

            left: -150px;
            bottom: -240px;
        }


        .header-content {

            max-width: 1150px;

            width: 100%;

            margin: 0 auto;

            position: relative;

            z-index: 2;
        }


        .eyebrow {

            font-size: 11px;

            font-weight: 700;

            letter-spacing: .16em;

            text-transform: uppercase;

            opacity: .72;

            margin: 0 0 10px;
        }


        header h1 {

            font-family: "Manrope", sans-serif;

            font-size:
                clamp(34px, 5vw, 56px);

            line-height: 1.05;

            font-weight: 800;

            letter-spacing: -.035em;

            margin: 0;

            max-width: 760px;
        }


        header h1 span {
            color: #D9EBDD;
        }


        header .sub {

            max-width: 690px;

            margin: 16px 0 0;

            font-size: 16px;

            line-height: 1.7;

            opacity: .86;
        }


        .back-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 48px;

            padding: 0 22px;

            margin-top: 25px;

            border-radius: 14px;

            background: white;

            color: var(--deep);

            text-decoration: none;

            font-size: 13px;

            font-weight: 800;

            box-shadow:
                0 15px 35px
                rgba(0, 0, 0, .18);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                background .25s ease;
        }


        .back-button:hover {

            transform: translateY(-3px);

            box-shadow:
                0 20px 45px
                rgba(0, 0, 0, .25);

            background: #F9FFFF;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        main {

            max-width: 1250px;

            margin: -52px auto 65px;

            padding: 0 5%;

            position: relative;

            z-index: 3;
        }


        /* =====================================================
           QUICK INFO
        ===================================================== */

        .quick-info {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 17px;

            margin-bottom: 28px;
        }


        .info-card {

            background: var(--white);

            border: 1px solid var(--line);

            border-radius: var(--radius);

            padding: 23px;

            box-shadow: var(--shadow);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .info-card:hover {

            transform: translateY(-4px);

            box-shadow: var(--shadow-hover);
        }


        .info-icon {

            width: 45px;

            height: 45px;

            border-radius: 14px;

            background: var(--sand2);

            border:
                1px solid
                rgba(61, 138, 114, .12);

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--accent);

            font-family: "Manrope", sans-serif;

            font-size: 15px;

            font-weight: 800;

            margin-bottom: 15px;
        }


        .info-card h3 {

            font-family: "Manrope", sans-serif;

            font-size: 18px;

            line-height: 1.25;

            margin: 0 0 6px;

            color: var(--deep);

            font-weight: 700;
        }


        .info-card p {

            margin: 0;

            font-size: 13px;

            line-height: 1.65;

            color: #68806F;
        }


        /* =====================================================
           SECCIONES
        ===================================================== */

        section {

            background: var(--white);

            border: 1px solid var(--line);

            border-radius: var(--radius);

            padding: 30px;

            margin-bottom: 24px;

            box-shadow: var(--shadow);
        }


        .section-title {

            font-family: "Manrope", sans-serif;

            color: var(--deep);

            font-size:
                clamp(23px, 3vw, 29px);

            line-height: 1.2;

            letter-spacing: -.025em;

            margin: 0;

            font-weight: 800;
        }


        .section-description {

            color: #68806F;

            font-size: 14px;

            line-height: 1.65;

            margin: 7px 0 23px;

            max-width: 760px;
        }


        /* =====================================================
           PASOS
        ===================================================== */

        .steps {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 16px;
        }


        .step {

            padding: 21px;

            border-radius: 17px;

            background: var(--sand);

            border: 1px solid var(--line);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .step:hover {

            transform: translateY(-3px);

            box-shadow:
                0 14px 32px
                rgba(21, 58, 49, .08);
        }


        .step-number {

            width: 38px;

            height: 38px;

            border-radius: 12px;

            background: var(--accent);

            color: #FFFFFF;

            display: flex;

            align-items: center;

            justify-content: center;

            font-family: "Manrope", sans-serif;

            font-size: 13px;

            font-weight: 800;

            margin-bottom: 14px;
        }


        .step h3 {

            font-family: "Manrope", sans-serif;

            color: var(--deep);

            font-size: 18px;

            line-height: 1.25;

            margin: 0 0 7px;

            font-weight: 700;
        }


        .step p {

            color: #68806F;

            font-size: 13px;

            line-height: 1.65;

            margin: 0;
        }


        /* =====================================================
           EXPERIENCIAS
        ===================================================== */

        .experiences-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }


        .experience-card {

            border:
                1px solid
                var(--line);

            border-radius: 19px;

            overflow: hidden;

            background: #FFFFFF;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;
        }


        .experience-card:hover {

            transform: translateY(-4px);

            box-shadow: var(--shadow-hover);

            border-color:
                rgba(61, 138, 114, .22);
        }


        .experience-header {

            padding: 23px;

            background:
                linear-gradient(
                    135deg,
                    var(--deep),
                    var(--mid)
                );

            color: #FFFFFF;

            position: relative;

            overflow: hidden;
        }


        .experience-header::after {

            content: "";

            position: absolute;

            width: 170px;

            height: 170px;

            border-radius: 50%;

            right: -70px;

            bottom: -90px;

            background:
                rgba(255, 255, 255, .07);
        }


        .experience-type {

            display: inline-flex;

            align-items: center;

            background:
                rgba(255, 255, 255, .12);

            border:
                1px solid
                rgba(255, 255, 255, .18);

            border-radius: 999px;

            padding: 6px 10px;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: .08em;

            text-transform: uppercase;

            margin-bottom: 14px;

            position: relative;

            z-index: 1;
        }


        .experience-header h3 {

            font-family: "Manrope", sans-serif;

            font-size: 22px;

            line-height: 1.2;

            letter-spacing: -.02em;

            margin: 0;

            max-width: 310px;

            font-weight: 800;

            position: relative;

            z-index: 1;
        }


        .experience-body {

            padding: 21px;
        }


        .experience-meta {

            display: grid;

            gap: 10px;

            color: #61766A;

            font-size: 13px;

            line-height: 1.45;

            margin-bottom: 19px;
        }


        .meta-item {

            display: flex;

            align-items: flex-start;

            gap: 10px;
        }


        .meta-mark {

            width: 8px;

            height: 8px;

            border-radius: 50%;

            background: var(--accent);

            margin-top: 6px;

            flex: 0 0 auto;
        }


        .experience-footer {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;
        }


        .badge {

            display: inline-flex;

            align-items: center;

            padding: 6px 11px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: .04em;
        }


        .badge.available {

            background: #E5F3EA;

            color: var(--ok);
        }


        .badge.full {

            background: #FBE8E4;

            color: var(--bad);
        }


        .badge.warn {

            background: #FDF0DD;

            color: var(--warn);
        }


        .experience-button {

            border: none;

            border-radius: 11px;

            padding: 11px 16px;

            background: var(--accent);

            color: #FFFFFF;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

            transition:
                transform .2s ease,
                background .2s ease,
                box-shadow .2s ease;
        }


        .experience-button:hover {

            background: var(--accent-dark);

            transform: translateY(-2px);

            box-shadow:
                0 8px 18px
                rgba(46, 107, 87, .18);
        }


        .experience-button:disabled {

            background: #C8D0C9;

            cursor: not-allowed;

            transform: none;

            box-shadow: none;
        }


        /* =====================================================
           RESERVA
        ===================================================== */

        .reservation-layout {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 22px;
        }


        .login-box,
        .reservation-box {

            border:
                1px solid
                var(--line);

            border-radius: 18px;

            padding: 23px;

            background: var(--sand);
        }


        .login-box h3,
        .reservation-box h3 {

            font-family: "Manrope", sans-serif;

            font-size: 20px;

            line-height: 1.25;

            color: var(--deep);

            margin: 0 0 6px;

            font-weight: 800;
        }


        .login-box p,
        .reservation-box p {

            color: #68806F;

            font-size: 13px;

            line-height: 1.6;

            margin: 0 0 18px;
        }


        .logged-box {

            background: #E5F3EA;

            border:
                1px solid
                #C1E2CD;

            border-radius: 14px;

            padding: 15px;

            margin-bottom: 18px;

            color: var(--ok);

            font-size: 13px;
        }


        .logged-box strong {

            display: block;

            color: #28613F;

            margin-bottom: 4px;
        }


        .logout-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            margin-top: 10px;

            padding: 9px 14px;

            border-radius: 10px;

            background: #FFFFFF;

            border: 1px solid #C1E2CD;

            color: #28613F;

            font-size: 12px;

            font-weight: 700;

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .logout-button:hover {

            transform: translateY(-2px);

            box-shadow:
                0 7px 16px
                rgba(21, 58, 49, .10);
        }


        .login-status {

            padding: 11px 13px;

            border-radius: 11px;

            font-size: 13px;

            line-height: 1.5;

            margin-bottom: 15px;
        }


        .login-status.bad {

            background: #FBE8E4;

            color: var(--bad);

            border: 1px solid #F1C3BA;
        }


        .login-status.ok {

            background: #E5F3EA;

            color: var(--ok);

            border: 1px solid #C1E2CD;
        }


        label {

            display: block;

            font-size: 12px;

            font-weight: 700;

            color: var(--deep);

            margin: 14px 0 6px;
        }


        input,
        select {

            width: 100%;

            padding: 12px 13px;

            border:
                1px solid
                var(--line);

            border-radius: 11px;

            font-size: 14px;

            color: var(--ink);

            background: #FFFFFF;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        input::placeholder {

            color: #9AA9A0;
        }


        input:focus,
        select:focus {

            outline: none;

            border-color:
                var(--accent);

            box-shadow:
                0 0 0 3px
                rgba(61, 138, 114, .12);
        }


        .form-row {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 14px;
        }


        .action {

            width: 100%;

            margin-top: 18px;

            border: none;

            border-radius: 11px;

            padding: 13px 18px;

            background: var(--accent);

            color: #FFFFFF;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            transition:
                transform .2s ease,
                background .2s ease,
                box-shadow .2s ease;
        }


        .action:hover {

            background: var(--accent-dark);

            transform: translateY(-2px);

            box-shadow:
                0 10px 22px
                rgba(46, 107, 87, .18);
        }


        .selected-experience {

            display: none;

            background: #FFFFFF;

            border:
                1px solid
                var(--line);

            border-radius: 13px;

            padding: 15px;

            margin-top: 15px;
        }


        .selected-experience.show {

            display: block;
        }


        .selected-experience strong {

            display: block;

            font-family: "Manrope", sans-serif;

            color: var(--deep);

            font-size: 17px;

            line-height: 1.3;

            margin-bottom: 4px;
        }


        .selected-experience span {

            color: #68806F;

            font-size: 12px;

            line-height: 1.5;
        }


        .msg {

            padding: 12px 14px;

            border-radius: 11px;

            margin-top: 14px;

            font-size: 13px;
        }


        .msg.ok {

            background: #E5F3EA;

            color: var(--ok);

            border: 1px solid #C1E2CD;
        }


        .msg.bad {

            background: #FBE8E4;

            color: var(--bad);

            border: 1px solid #F1C3BA;
        }


        /* =====================================================
           CONFIRMACIÓN
        ===================================================== */

        .confirmation {

            background:
                linear-gradient(
                    135deg,
                    #EEF5EB,
                    #FFFFFF
                );

            border:
                1px solid
                var(--line);

            border-radius: 19px;

            padding: 26px;

            margin-top: 23px;
        }


        .confirmation-icon {

            width: 48px;

            height: 48px;

            border-radius: 14px;

            background: #E5F3EA;

            color: var(--ok);

            display: flex;

            align-items: center;

            justify-content: center;

            font-family: "Manrope", sans-serif;

            font-size: 20px;

            font-weight: 800;

            margin-bottom: 14px;
        }


        .confirmation h3 {

            font-family: "Manrope", sans-serif;

            color: var(--deep);

            font-size: 23px;

            line-height: 1.25;

            margin: 0 0 8px;

            font-weight: 800;
        }


        .confirmation p {

            color: #61766A;

            font-size: 13.5px;

            line-height: 1.65;

            margin: 6px 0;
        }


        .reservation-code {

            display: inline-flex;

            margin-top: 16px;

            padding: 10px 14px;

            background: var(--deep);

            color: #FFFFFF;

            border-radius: 10px;

            font-size: 13px;

            font-weight: 700;

            letter-spacing: .04em;
        }


        /* =====================================================
           ANTES DE PARTICIPAR
        ===================================================== */

        .before-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 16px;
        }


        .before-item {

            padding: 19px;

            border-left:
                4px solid
                var(--accent);

            background: var(--sand);

            border-radius:
                0 14px 14px 0;

            border-top:
                1px solid
                var(--line);

            border-right:
                1px solid
                var(--line);

            border-bottom:
                1px solid
                var(--line);
        }


        .before-mark {

            width: 8px;

            height: 8px;

            border-radius: 50%;

            background: var(--accent);

            margin-bottom: 12px;
        }


        .before-item h3 {

            font-family: "Manrope", sans-serif;

            color: var(--deep);

            font-size: 17px;

            line-height: 1.3;

            margin: 0 0 6px;

            font-weight: 700;
        }


        .before-item p {

            color: #68806F;

            font-size: 12.5px;

            line-height: 1.65;

            margin: 0;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {

            text-align: center;

            font-size: 12px;

            color: #7F9587;

            padding:
                20px 24px 30px;

            font-family:
                "DM Sans", sans-serif;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1000px) {

            .quick-info {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .experiences-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .steps {

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

                min-height: 480px;

                padding:
                    60px 6% 100px;
            }


            main {

                padding: 0 20px;

                margin-top: -42px;
            }


            .quick-info {

                grid-template-columns: 1fr;
            }


            .experiences-grid {

                grid-template-columns: 1fr;
            }


            .reservation-layout {

                grid-template-columns: 1fr;
            }


            .steps {

                grid-template-columns: 1fr;
            }


            .before-grid {

                grid-template-columns: 1fr;
            }


            section {

                padding: 24px;
            }
        }


        @media (max-width: 450px) {

            header {

                padding:
                    45px 20px 90px;
            }


            header h1 {

                font-size: 34px;
            }


            header .sub {

                font-size: 14px;
            }


            main {

                padding: 0 14px;
            }


            section {

                padding: 20px;

                border-radius: 17px;
            }


            .form-row {

                grid-template-columns: 1fr;
            }


            .experience-footer {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .experience-button {

                width: 100%;
            }


            .back-button {

                width: 100%;
            }


            .confirmation {

                padding: 21px;
            }
        }

    </style>

</head>


<body>


<header>

    <div class="header-content">

        <p class="eyebrow">
            Laguna Experience · Experiencias
        </p>

        <h1>
            Experiencias <span>& Recreación</span>
        </h1>

        <p class="sub">
            Explorá senderos, recorridos en bicicleta y experiencias de naturaleza
            pensadas para descubrir Laguna Experience de una manera diferente.
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


    <!-- INFORMACIÓN RÁPIDA -->

    <div class="quick-info">

        <div class="info-card">

            <div class="info-icon">
                01
            </div>

            <h3>
                Naturaleza
            </h3>

            <p>
                Recorridos y actividades para disfrutar del entorno natural.
            </p>

        </div>


        <div class="info-card">

            <div class="info-icon">
                02
            </div>

            <h3>
                Actividades
            </h3>

            <p>
                Elegí entre experiencias libres y actividades con cupos limitados.
            </p>

        </div>


        <div class="info-card">

            <div class="info-icon">
                03
            </div>

            <h3>
                Momentos únicos
            </h3>

            <p>
                También podés descubrir Laguna durante el amanecer o la noche.
            </p>

        </div>

    </div>


    <!-- CÓMO FUNCIONA -->

    <section>

        <h2 class="section-title">
            ¿Cómo funciona?
        </h2>

        <p class="section-description">
            Descubrí cómo participar en las experiencias de Laguna Experience.
        </p>


        <div class="steps">

            <div class="step">

                <div class="step-number">
                    1
                </div>

                <h3>
                    Explorá
                </h3>

                <p>
                    Conocé las distintas experiencias, horarios, edades y cupos disponibles.
                </p>

            </div>


            <div class="step">

                <div class="step-number">
                    2
                </div>

                <h3>
                    Elegí
                </h3>

                <p>
                    Seleccioná la experiencia que querés realizar y revisá sus condiciones.
                </p>

            </div>


            <div class="step">

                <div class="step-number">
                    3
                </div>

                <h3>
                    Reservá
                </h3>

                <p>
                    Si requiere reserva, iniciá sesión y confirmá tu participación.
                </p>

            </div>

        </div>

    </section>


    <!-- EXPERIENCIAS DISPONIBLES -->

    <section>

        <h2 class="section-title">
            Experiencias disponibles
        </h2>

        <p class="section-description">
            Elegí una experiencia para conocer sus horarios, requisitos y disponibilidad.
        </p>


        <div class="experiences-grid">


            <?php foreach ($experiencias as $experiencia): ?>

                <?php

                $requiereReserva =
                    intval(
                        $experiencia["requiere_reserva"]
                    );

                $suspendida =
                    intval(
                        $experiencia["suspendida"]
                    );

                $cupoRestante =
                    intval(
                        $experiencia["cupo_restante"]
                    );

                $cupoTotal =
                    intval(
                        $experiencia["cupo_total"]
                    );

                $disponible =
                    !$suspendida &&
                    (
                        !$requiereReserva ||
                        $cupoRestante > 0
                    );

                ?>


                <div class="experience-card">


                    <div class="experience-header">

                        <span class="experience-type">

                            <?= htmlspecialchars(
                                $experiencia["tipo"]
                            ) ?>

                        </span>


                        <h3>

                            <?= htmlspecialchars(
                                $experiencia["nombre"]
                            ) ?>

                        </h3>

                    </div>


                    <div class="experience-body">


                        <div class="experience-meta">


                            <div class="meta-item">

                                <span class="meta-mark"></span>

                                <span>

                                    <?= htmlspecialchars(
                                        $experiencia["modalidad"]
                                    ) ?>

                                </span>

                            </div>


                            <div class="meta-item">

                                <span class="meta-mark"></span>

                                <span>

                                    <?php

                                    if (
                                        intval(
                                            $experiencia["edad_min"]
                                        ) > 0
                                    ) {

                                        echo
                                            "Edad mínima: " .
                                            intval(
                                                $experiencia["edad_min"]
                                            ) .
                                            " años";

                                    } else {

                                        echo
                                            "Sin restricción de edad";
                                    }

                                    ?>

                                </span>

                            </div>


                            <div class="meta-item">

                                <span class="meta-mark"></span>

                                <span>

                                    <?php

                                    if ($requiereReserva) {

                                        echo
                                            $cupoRestante .
                                            " de " .
                                            $cupoTotal .
                                            " cupos disponibles";

                                    } else {

                                        echo
                                            "No requiere reserva previa";
                                    }

                                    ?>

                                </span>

                            </div>


                        </div>


                        <div class="experience-footer">


                            <?php if ($suspendida): ?>

                                <span class="badge warn">
                                    No disponible temporalmente
                                </span>


                            <?php elseif (
                                $requiereReserva &&
                                $cupoRestante <= 0
                            ): ?>

                                <span class="badge full">
                                    Sin cupos
                                </span>


                            <?php else: ?>

                                <span class="badge available">
                                    Disponible
                                </span>

                            <?php endif; ?>


                            <?php if ($disponible): ?>

                                <button
                                    class="experience-button"
                                    type="button"
                                    onclick="seleccionarExperiencia(<?= intval($experiencia["id"]) ?>)"
                                >

                                    <?php

                                    if ($requiereReserva) {

                                        echo "Reservar";

                                    } else {

                                        echo "Ver experiencia";
                                    }

                                    ?>

                                </button>

                            <?php endif; ?>


                        </div>

                    </div>

                </div>


            <?php endforeach; ?>


        </div>

    </section>


    <!-- RESERVA -->

    <section id="reserva">

        <h2 class="section-title">
            Reservá tu experiencia
        </h2>

        <p class="section-description">
            Seleccioná una experiencia y completá tus datos para confirmar tu participación.
        </p>


        <?php if ($usuarioLogueado): ?>


            <!-- USUARIO LOGUEADO -->

            <div class="logged-box">

                <strong>
                    Sesión iniciada
                </strong>

                Estás conectado como
                <strong>
                    <?= htmlspecialchars($nombreUsuario) ?>
                </strong>

                <a
                    href="experiencias-recreacion.php?cerrar=1"
                    class="logout-button"
                >
                    Cerrar sesión
                </a>

            </div>


            <form
                method="POST"
                id="formReserva"
            >

                <input
                    type="hidden"
                    name="accion"
                    value="reservar"
                >


                <div class="reservation-layout">


                    <div class="reservation-box">

                        <h3>
                            Datos de participación
                        </h3>

                        <p>
                            Seleccioná la experiencia y completá tus datos.
                        </p>


                        <?php if ($reservaError !== ""): ?>

                            <div class="login-status bad">

                                <?= htmlspecialchars(
                                    $reservaError
                                ) ?>

                            </div>

                        <?php endif; ?>


                        <label for="experienciaReserva">
                            Experiencia
                        </label>


                        <select
                            id="experienciaReserva"
                            name="experiencia_id"
                            onchange="mostrarExperienciaSeleccionada()"
                            required
                        >

                            <?php foreach ($experiencias as $experiencia): ?>

                                <?php

                                $deshabilitada =
                                    intval(
                                        $experiencia["suspendida"]
                                    ) === 1 ||

                                    (
                                        intval(
                                            $experiencia["requiere_reserva"]
                                        ) === 1 &&

                                        intval(
                                            $experiencia["cupo_restante"]
                                        ) <= 0
                                    );

                                ?>

                                <option
                                    value="<?= intval($experiencia["id"]) ?>"
                                    <?= $deshabilitada ? "disabled" : "" ?>
                                >

                                    <?= htmlspecialchars(
                                        $experiencia["nombre"]
                                    ) ?>

                                    <?php

                                    if (
                                        intval(
                                            $experiencia["suspendida"]
                                        ) === 1
                                    ) {

                                        echo
                                            " — No disponible";
                                    }

                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>


                        <div
                            class="selected-experience show"
                            id="selectedExperience"
                        >

                            <strong
                                id="selectedName"
                            ></strong>

                            <span
                                id="selectedInfo"
                            ></span>

                        </div>


                        <div class="form-row">


                            <div>

                                <label for="nombre">
                                    Nombre del participante
                                </label>

                                <input
                                    type="text"
                                    id="nombre"
                                    name="nombre"
                                    placeholder="Nombre y apellido"
                                    value="<?= htmlspecialchars(
                                        $_POST["nombre"] ?? ""
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div>

                                <label for="edad">
                                    Edad
                                </label>

                                <input
                                    type="number"
                                    id="edad"
                                    name="edad"
                                    min="1"
                                    max="99"
                                    placeholder="Ej: 20"
                                    value="<?= htmlspecialchars(
                                        $_POST["edad"] ?? ""
                                    ) ?>"
                                    required
                                >

                            </div>


                        </div>


                        <button
                            class="action"
                            type="submit"
                        >
                            Confirmar participación
                        </button>


                    </div>


                </div>

            </form>


        <?php else: ?>


            <!-- LOGIN -->

            <div class="reservation-layout">


                <div class="login-box">

                    <h3>
                        Iniciá sesión
                    </h3>

                    <p>
                        Para reservar una experiencia primero tenés que ingresar con tu usuario y contraseña.
                    </p>


                    <?php if ($loginError !== ""): ?>

                        <div class="login-status bad">

                            <?= htmlspecialchars(
                                $loginError
                            ) ?>

                        </div>

                    <?php endif; ?>


                    <form
                        method="POST"
                        action="experiencias-recreacion.php#reserva"
                    >

                        <input
                            type="hidden"
                            name="accion"
                            value="login"
                        >


                        <label for="usuario">
                            Usuario o email
                        </label>

                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            placeholder="Ingresá tu usuario o email"
                            required
                        >


                        <label for="password">
                            Contraseña
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingresá tu contraseña"
                            required
                        >


                        <button
                            class="action"
                            type="submit"
                        >
                            Iniciar sesión
                        </button>

                    </form>

                </div>


                <div class="reservation-box">

                    <h3>
                        ¿Querés reservar?
                    </h3>

                    <p>
                        Iniciá sesión con tu cuenta y después vas a poder seleccionar una experiencia, indicar los datos del participante y confirmar la reserva.
                    </p>

                </div>


            </div>


        <?php endif; ?>


        <?php if ($confirmacion): ?>


            <div
                class="confirmation"
                id="confirmation"
            >

                <div class="confirmation-icon">
                    ✓
                </div>


                <h3>
                    Participación confirmada
                </h3>


                <p>

                    <?= htmlspecialchars(
                        $confirmacion["nombre"]
                    ) ?>,

                    tu participación en

                    <strong>

                        <?= htmlspecialchars(
                            $confirmacion["experiencia"]
                        ) ?>

                    </strong>

                    quedó confirmada.

                </p>


                <p>

                    Horario:

                    <?= htmlspecialchars(
                        $confirmacion["modalidad"]
                    ) ?>

                </p>


                <p>
                    Guardá el código de reserva para consultarlo al llegar a Laguna Experience.
                </p>


                <div class="reservation-code">

                    <?= htmlspecialchars(
                        $confirmacion["codigo"]
                    ) ?>

                </div>

            </div>


        <?php endif; ?>


    </section>


    <!-- ANTES DE PARTICIPAR -->

    <section>

        <h2 class="section-title">
            Antes de participar
        </h2>

        <p class="section-description">
            Algunas recomendaciones para disfrutar mejor la experiencia.
        </p>


        <div class="before-grid">


            <div class="before-item">

                <div class="before-mark"></div>

                <h3>
                    Vestimenta cómoda
                </h3>

                <p>
                    Para los recorridos y actividades al aire libre recomendamos utilizar ropa y calzado cómodos.
                </p>

            </div>


            <div class="before-item">

                <div class="before-mark"></div>

                <h3>
                    Condiciones climáticas
                </h3>

                <p>
                    Algunas actividades pueden suspenderse temporalmente si las condiciones climáticas no permiten realizarlas.
                </p>

            </div>


            <div class="before-item">

                <div class="before-mark"></div>

                <h3>
                    Respetá el horario
                </h3>

                <p>
                    En las experiencias con horario de salida es importante llegar con anticipación.
                </p>

            </div>


        </div>

    </section>


</main>


<footer>

    Laguna Experience — Experiencias & Recreación

</footer>


<script>


/* =========================================================
   EXPERIENCIAS DESDE MYSQL
========================================================= */

const experiencias =
<?= json_encode(
    $experiencias,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;


/* =========================================================
   SELECCIONAR EXPERIENCIA
========================================================= */

function seleccionarExperiencia(id) {

    const select =
        document.getElementById(
            "experienciaReserva"
        );


    if (!select) {

        document
            .getElementById("reserva")
            .scrollIntoView({
                behavior: "smooth"
            });

        return;
    }


    select.value = id;


    mostrarExperienciaSeleccionada();


    document
        .getElementById("reserva")
        .scrollIntoView({
            behavior: "smooth"
        });
}


/* =========================================================
   MOSTRAR EXPERIENCIA
========================================================= */

function mostrarExperienciaSeleccionada() {

    const select =
        document.getElementById(
            "experienciaReserva"
        );


    const box =
        document.getElementById(
            "selectedExperience"
        );


    if (!select || !box) {
        return;
    }


    const id =
        Number(
            select.value
        );


    const experiencia =
        experiencias.find(
            function(item) {

                return Number(item.id) === id;
            }
        );


    if (!experiencia) {

        box.classList.remove("show");

        return;
    }


    document.getElementById(
        "selectedName"
    ).textContent =
        experiencia.nombre;


    document.getElementById(
        "selectedInfo"
    ).textContent =

        experiencia.modalidad +
        " · " +

        (
            Number(experiencia.edad_min) > 0

                ? "edad mínima " +
                  experiencia.edad_min +
                  " años"

                : "sin restricción de edad"
        );


    box.classList.add("show");
}


/* =========================================================
   MENSAJE DE PHP
========================================================= */

<?php if (
    $mensaje !== "" ||
    $reservaError !== "" ||
    $loginError !== ""
): ?>

window.addEventListener(
    "load",
    function() {

        <?php if ($confirmacion): ?>

            document
                .getElementById("confirmation")
                .scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });

        <?php else: ?>

            document
                .getElementById("reserva")
                .scrollIntoView({
                    behavior: "smooth"
                });

        <?php endif; ?>

    }
);

<?php endif; ?>


/* =========================================================
   INICIO
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function() {

        mostrarExperienciaSeleccionada();

    }
);

</script>


</body>

</html>