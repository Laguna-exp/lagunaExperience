<?php

session_start();

require_once __DIR__ . "/../conexion.php";

$mensajeReserva = "";
$tipoMensajeReserva = "";

$reservaConfirmada = false;

$reservaCodigo = "";
$reservaActividad = "";
$reservaFecha = "";
$reservaHorario = "";
$reservaPersonas = "";
$reservaUsuario = "";
$reservaEdades = "";

$edadesIngresadas = [];


// ======================================================
// CERRAR SESIÓN
// ======================================================

if (isset($_GET["cerrar"])) {

    session_unset();
    session_destroy();

    header("Location: actividades-acuaticas.php");
    exit;
}


// ======================================================
// OBTENER ACTIVIDADES
// ======================================================

$actividades = [];

$sqlActividades = "
    SELECT *
    FROM actividades
    WHERE lugar_id = 2
    ORDER BY id ASC
";

$resultadoActividades = mysqli_query(
    $conexion,
    $sqlActividades
);

if ($resultadoActividades) {

    while ($fila = mysqli_fetch_assoc($resultadoActividades)) {

        $actividades[] = $fila;
    }
}


// ======================================================
// RESERVAR
// ======================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["accion"]) &&
    $_POST["accion"] === "reservar"
) {

    // --------------------------------------------------
    // VERIFICAR SESIÓN
    // --------------------------------------------------

    if (!isset($_SESSION["usuario_id"])) {

        header(
            "Location: ../login.php?volver=diezPaginas/actividades-acuaticas.php"
        );

        exit;
    }


    // --------------------------------------------------
    // DATOS DEL FORMULARIO
    // --------------------------------------------------

    $actividadId = intval(
        $_POST["actividad_id"] ?? 0
    );

    $fecha = trim(
        $_POST["fecha"] ?? ""
    );

    $horario = trim(
        $_POST["horario"] ?? ""
    );

    $personas = intval(
        $_POST["personas"] ?? 0
    );


    // --------------------------------------------------
    // EDADES
    // --------------------------------------------------

    if (
        isset($_POST["edades"]) &&
        is_array($_POST["edades"])
    ) {

        foreach ($_POST["edades"] as $edad) {

            $edadesIngresadas[] = intval($edad);
        }
    }


    // --------------------------------------------------
    // VALIDACIÓN GENERAL
    // --------------------------------------------------

    if (
        $actividadId <= 0 ||
        $fecha === "" ||
        $horario === "" ||
        $personas <= 0
    ) {

        $mensajeReserva =
            "Completá todos los datos de la reserva.";

        $tipoMensajeReserva = "bad";

    } elseif (
        count($edadesIngresadas) !== $personas
    ) {

        $mensajeReserva =
            "Ingresá la edad de todas las personas.";

        $tipoMensajeReserva = "bad";

    } else {


        // --------------------------------------------------
        // VALIDAR FECHA
        // --------------------------------------------------

        $fechaValida = DateTime::createFromFormat(
            "Y-m-d",
            $fecha
        );

        if (
            !$fechaValida ||
            $fechaValida->format("Y-m-d") !== $fecha
        ) {

            $mensajeReserva =
                "La fecha seleccionada no es válida.";

            $tipoMensajeReserva = "bad";

        } elseif (
            $fecha < date("Y-m-d")
        ) {

            $mensajeReserva =
                "No podés reservar una fecha anterior a hoy.";

            $tipoMensajeReserva = "bad";

        } else {


            // --------------------------------------------------
            // BUSCAR ACTIVIDAD
            // --------------------------------------------------

            $sqlActividad = "
                SELECT *
                FROM actividades
                WHERE id = $actividadId
                AND lugar_id = 2
                LIMIT 1
            ";

            $resultadoActividad = mysqli_query(
                $conexion,
                $sqlActividad
            );


            if (
                !$resultadoActividad ||
                mysqli_num_rows($resultadoActividad) === 0
            ) {

                $mensajeReserva =
                    "La actividad seleccionada no existe.";

                $tipoMensajeReserva = "bad";

            } else {

                $actividad =
                    mysqli_fetch_assoc(
                        $resultadoActividad
                    );


                // --------------------------------------------------
                // VALIDAR HORARIO
                // --------------------------------------------------

                $horariosDisponibles =
                    preg_split(
                        '/\s*\/\s*/',
                        $actividad["horario"]
                    );

                $horarioValido = false;

                foreach (
                    $horariosDisponibles
                    as $horarioDisponible
                ) {

                    if (
                        $horario ===
                        trim($horarioDisponible)
                    ) {

                        $horarioValido = true;

                        break;
                    }
                }


                if (!$horarioValido) {

                    $mensajeReserva =
                        "El horario seleccionado no está disponible para esta actividad.";

                    $tipoMensajeReserva = "bad";


                } elseif (
                    $actividad["estado"] === "cerrado" ||
                    $actividad["estado"] === "mantenimiento"
                ) {

                    $mensajeReserva =
                        "Esta actividad no está disponible en este momento.";

                    $tipoMensajeReserva = "bad";


                } elseif (
                    $personas > $actividad["capacidad"]
                ) {

                    $mensajeReserva =
                        "La actividad permite hasta " .
                        $actividad["capacidad"] .
                        " personas.";

                    $tipoMensajeReserva = "bad";


                } elseif (
                    $personas > $actividad["cupos_disponibles"]
                ) {

                    $mensajeReserva =
                        "No hay suficientes cupos disponibles. Actualmente quedan " .
                        $actividad["cupos_disponibles"] .
                        ".";

                    $tipoMensajeReserva = "bad";


                } else {


                    // --------------------------------------------------
                    // VALIDAR EDAD DE CADA PARTICIPANTE
                    // --------------------------------------------------

                    $edadesCorrectas = true;

                    foreach (
                        $edadesIngresadas
                        as $indice => $edad
                    ) {

                        if (
                            $edad <= 0 ||
                            $edad > 99
                        ) {

                            $mensajeReserva =
                                "La edad de la persona " .
                                ($indice + 1) .
                                " no es válida.";

                            $tipoMensajeReserva = "bad";

                            $edadesCorrectas = false;

                            break;
                        }


                        if (
                            $edad <
                            $actividad["edad_minima"]
                        ) {

                            $mensajeReserva =
                                "La persona " .
                                ($indice + 1) .
                                " tiene " .
                                $edad .
                                " años y " .
                                $actividad["nombre"] .
                                " requiere una edad mínima de " .
                                $actividad["edad_minima"] .
                                " años.";

                            $tipoMensajeReserva = "bad";

                            $edadesCorrectas = false;

                            break;
                        }
                    }


                    // --------------------------------------------------
                    // SI TODAS LAS EDADES SON CORRECTAS
                    // --------------------------------------------------

                    if ($edadesCorrectas) {


                        // --------------------------------------------------
                        // CALCULAR TOTAL
                        // --------------------------------------------------

                        $total =
                            $actividad["precio"] *
                            $personas;


                        // --------------------------------------------------
                        // GENERAR CÓDIGO
                        // --------------------------------------------------

                        $codigo =
                            "LX" .
                            date("ymd") .
                            strtoupper(
                                substr(
                                    md5(uniqid()),
                                    0,
                                    6
                                )
                            );


                        $usuarioId =
                            intval(
                                $_SESSION["usuario_id"]
                            );

                        $lugarId =
                            intval(
                                $actividad["lugar_id"]
                            );


                        // --------------------------------------------------
                        // VALIDAR HORARIO PARA MYSQL
                        // --------------------------------------------------

                        if (
                            preg_match(
                                '/^\d{2}:\d{2}$/',
                                $horario
                            )
                        ) {

                            $horarioBD =
                                $horario . ":00";

                        } else {

                            $mensajeReserva =
                                "Esta actividad necesita un horario específico para poder reservar.";

                            $tipoMensajeReserva = "bad";

                            $horarioBD = "";
                        }


                        if (
                            $mensajeReserva === ""
                        ) {


                            // --------------------------------------------------
                            // INICIAR TRANSACCIÓN
                            // --------------------------------------------------

                            mysqli_begin_transaction(
                                $conexion
                            );


                            try {


                                // --------------------------------------------------
                                // DESCONTAR CUPOS
                                // --------------------------------------------------

                                $sqlActualizar = "
                                    UPDATE actividades
                                    SET cupos_disponibles =
                                        cupos_disponibles - $personas
                                    WHERE id = $actividadId
                                    AND cupos_disponibles >= $personas
                                ";

                                $actualizacion =
                                    mysqli_query(
                                        $conexion,
                                        $sqlActualizar
                                    );


                                if (
                                    !$actualizacion ||
                                    mysqli_affected_rows(
                                        $conexion
                                    ) !== 1
                                ) {

                                    throw new Exception(
                                        "No hay suficientes cupos disponibles."
                                    );
                                }


                                // --------------------------------------------------
                                // ESCAPAR DATOS
                                // --------------------------------------------------

                                $codigoEscapado =
                                    mysqli_real_escape_string(
                                        $conexion,
                                        $codigo
                                    );

                                $fechaEscapada =
                                    mysqli_real_escape_string(
                                        $conexion,
                                        $fecha
                                    );

                                $horarioEscapado =
                                    mysqli_real_escape_string(
                                        $conexion,
                                        $horarioBD
                                    );


                                // --------------------------------------------------
                                // INSERTAR RESERVA
                                // --------------------------------------------------

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
                                        '$fechaEscapada',
                                        '$horarioEscapado',
                                        $personas,
                                        $total,
                                        'confirmada',
                                        '$codigoEscapado'
                                    )
                                ";


                                $insertarReserva =
                                    mysqli_query(
                                        $conexion,
                                        $sqlReserva
                                    );


                                if (
                                    !$insertarReserva
                                ) {

                                    throw new Exception(
                                        "No se pudo guardar la reserva."
                                    );
                                }


                                // --------------------------------------------------
                                // CONFIRMAR TRANSACCIÓN
                                // --------------------------------------------------

                                mysqli_commit(
                                    $conexion
                                );


                                // --------------------------------------------------
                                // DATOS PARA CONFIRMACIÓN
                                // --------------------------------------------------

                                $reservaConfirmada =
                                    true;

                                $reservaCodigo =
                                    $codigo;

                                $reservaActividad =
                                    $actividad["nombre"];

                                $reservaFecha =
                                    $fecha;

                                $reservaHorario =
                                    $horario;

                                $reservaPersonas =
                                    $personas;

                                $reservaUsuario =
                                    $_SESSION["usuario"];

                                $reservaEdades =
                                    implode(
                                        ", ",
                                        $edadesIngresadas
                                    );


                            } catch (
                                Exception $e
                            ) {

                                mysqli_rollback(
                                    $conexion
                                );

                                $mensajeReserva =
                                    $e->getMessage();

                                $tipoMensajeReserva =
                                    "bad";
                            }
                        }
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
        Actividades Acuáticas · Laguna Experience
    </title>


    <!-- GOOGLE FONTS -->

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

        /* =====================================================
           VARIABLES
        ===================================================== */

        :root {

            --deep: #0B3B44;

            --mid: #146C74;

            --accent: #1E9AA3;

            --accent-dark: #157A82;

            --sand: #F7F2E7;

            --sand2: #EAF3F2;

            --ink: #0E2A2E;

            --line: #D6E3E0;

            --ok: #4C9A6A;

            --bad: #C24A3A;

            --white: #FFFFFF;

            --radius: 18px;


            --font-body:
                "DM Sans",
                sans-serif;

            --font-display:
                "Manrope",
                sans-serif;


            --shadow:
                0 24px 70px
                rgba(11, 59, 68, .12);
        }


        /* =====================================================
           RESET
        ===================================================== */

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
                    var(--sand2) 100%
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

            min-height: 500px;

            display: flex;

            align-items: center;

            padding:
                80px 8%;

            overflow: hidden;

            color: white;

            background:

                linear-gradient(
                    90deg,
                    rgba(11, 59, 68, .97),
                    rgba(20, 108, 116, .78),
                    rgba(21, 122, 130, .45)
                ),

                url("https://images.unsplash.com/photo-1439066615861-d1af74d74000?auto=format&fit=crop&w=2000&q=90")
                center / cover no-repeat;
        }


        header::before {

            content: "";

            position: absolute;

            width: 520px;

            height: 520px;

            top: -300px;

            right: -120px;

            border-radius: 50%;

            background:
                rgba(30, 154, 163, .22);

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
                    rgba(11, 59, 68, .45)
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
                #82d8df;

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
                #82d8df;
        }


        header p {

            max-width: 620px;

            margin-bottom: 30px;

            color:
                rgba(255,255,255,.76);

            font-size: 16px;

            line-height: 1.7;
        }


        /* =====================================================
           BOTÓN VOLVER
        ===================================================== */

        .header-actions {

            display: flex;

            flex-wrap: wrap;

            gap: 12px;
        }


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
           INFO
        ===================================================== */

        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 17px;

            margin-bottom: 28px;
        }


        .info {

            padding: 23px;

            background:
                rgba(255,255,255,.94);

            border:
                1px solid
                var(--line);

            border-radius:
                var(--radius);

            box-shadow:
                var(--shadow);

            transition:
                transform .3s ease,
                box-shadow .3s ease;
        }


        .info:hover {

            transform:
                translateY(-5px);

            box-shadow:
                0 28px 65px
                rgba(11,59,68,.16);
        }


        .info span {

            display: block;

            margin-bottom: 8px;

            color:
                rgba(14,42,46,.55);

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .15em;

            text-transform: uppercase;
        }


        .info strong {

            color:
                var(--deep);

            font-family:
                var(--font-display);

            font-size: 18px;

            font-weight: 800;
        }


        /* =====================================================
           PANEL
        ===================================================== */

        .panel {

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
                box-shadow .3s ease;
        }


        .panel:hover {

            box-shadow:
                0 30px 75px
                rgba(11,59,68,.15);
        }


        .panel h2 {

            margin-bottom: 9px;

            color:
                var(--deep);

            font-family:
                var(--font-display);

            font-size: 34px;

            font-weight: 800;

            line-height: 1;

            letter-spacing: -.05em;
        }


        .description {

            max-width: 760px;

            margin-bottom: 27px;

            color:
                rgba(14,42,46,.62);

            font-size: 14px;

            line-height: 1.75;
        }


        /* =====================================================
           ACTIVIDADES
        ===================================================== */

        .activities {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }


        .activity {

            padding: 23px;

            background:
                linear-gradient(
                    145deg,
                    #ffffff,
                    var(--sand2)
                );

            border:
                1px solid
                var(--line);

            border-radius:
                var(--radius);

            transition:
                transform .3s ease,
                box-shadow .3s ease,
                border-color .3s ease;
        }


        .activity:hover {

            transform:
                translateY(-6px);

            border-color:
                rgba(30,154,163,.45);

            box-shadow:
                0 20px 48px
                rgba(11,59,68,.11);
        }


        .activity h3 {

            margin-bottom: 10px;

            color:
                var(--deep);

            font-family:
                var(--font-display);

            font-size: 21px;

            font-weight: 800;

            letter-spacing: -.035em;
        }


        .activity p {

            margin-bottom: 15px;

            color:
                rgba(14,42,46,.62);

            font-size: 13px;

            line-height: 1.65;
        }


        .activity-info {

            display: flex;

            flex-wrap: wrap;

            gap: 7px;
        }


        .tag {

            padding:
                7px 10px;

            background:
                white;

            border:
                1px solid
                var(--line);

            border-radius:
                999px;

            color:
                var(--mid);

            font-size: 10px;

            font-weight: 800;
        }


        /* =====================================================
           USUARIO
        ===================================================== */

        .user-status {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 23px;

            padding:
                15px 17px;

            background:
                var(--sand2);

            border:
                1px solid
                var(--line);

            border-radius:
                14px;

            color:
                rgba(14,42,46,.70);

            font-size: 13px;
        }


        .user-status strong {

            color:
                var(--deep);
        }


        .logout {

            padding:
                8px 11px;

            border-radius:
                10px;

            color:
                var(--accent-dark);

            font-size: 12px;

            font-weight: 800;

            transition:
                background .2s ease,
                transform .2s ease;
        }


        .logout:hover {

            background:
                rgba(30,154,163,.10);

            transform:
                translateY(-1px);
        }


        /* =====================================================
           FORMULARIO
        ===================================================== */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }


        .form-group {

            min-width: 0;
        }


        label {

            display: block;

            margin-bottom: 7px;

            color:
                var(--deep);

            font-size: 12px;

            font-weight: 800;
        }


        input,
        select {

            width: 100%;

            min-height: 46px;

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
                box-shadow .2s ease,
                transform .2s ease;
        }


        input:hover,
        select:hover {

            border-color:
                rgba(30,154,163,.40);
        }


        input:focus,
        select:focus {

            border-color:
                var(--accent);

            box-shadow:
                0 0 0 4px
                rgba(30,154,163,.11);
        }


        .full {

            grid-column:
                1 / -1;
        }


        /* =====================================================
           EDADES
        ===================================================== */

        .ages-section {

            grid-column:
                1 / -1;

            padding:
                21px;

            background:
                linear-gradient(
                    145deg,
                    #f8ffff,
                    var(--sand2)
                );

            border:
                1px solid
                var(--line);

            border-radius:
                var(--radius);

            animation:
                fadeUp .35s ease;
        }


        .ages-title {

            margin-bottom: 5px;

            color:
                var(--deep);

            font-family:
                var(--font-display);

            font-size: 19px;

            font-weight: 800;
        }


        .ages-description {

            margin-bottom: 18px;

            color:
                rgba(14,42,46,.58);

            font-size: 12px;

            line-height: 1.6;
        }


        .ages-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 12px;
        }


        .age-item {

            animation:
                fadeUp .35s ease both;
        }


        .age-item label {

            margin-bottom: 5px;

            color:
                var(--mid);

            font-size: 11px;
        }


        .age-item input {

            min-height: 43px;
        }


        /* =====================================================
           BOTÓN RESERVAR
        ===================================================== */

        .primary {

            width: 100%;

            min-height: 51px;

            padding:
                0 24px;

            border:
                none;

            border-radius:
                14px;

            background:
                linear-gradient(
                    135deg,
                    var(--accent),
                    var(--accent-dark)
                );

            color:
                white;

            font-size: 13px;

            font-weight: 800;

            cursor: pointer;

            box-shadow:
                0 15px 32px
                rgba(21,122,130,.22);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                filter .25s ease;
        }


        .primary:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 21px 42px
                rgba(21,122,130,.30);

            filter:
                brightness(1.05);
        }


        .primary:active {

            transform:
                translateY(-1px);
        }


        /* =====================================================
           REGISTRO
        ===================================================== */

        .register-link {

            margin-top: 17px;

            color:
                rgba(14,42,46,.58);

            font-size: 12px;
        }


        .register-link a {

            color:
                var(--accent-dark);

            font-weight: 800;
        }


        .register-link a:hover {

            text-decoration:
                underline;
        }


        /* =====================================================
           MENSAJES
        ===================================================== */

        .message {

            margin-top: 20px;

            padding:
                14px 16px;

            border-radius:
                13px;

            font-size: 13px;

            line-height: 1.5;

            animation:
                fadeUp .35s ease;
        }


        .message.ok {

            background:
                #E5F3EA;

            color:
                var(--ok);

            border:
                1px solid
                #C1E2CD;
        }


        .message.bad {

            background:
                #FBE8E4;

            color:
                var(--bad);

            border:
                1px solid
                #F1C3BA;
        }


        /* =====================================================
           CONFIRMACIÓN
        ===================================================== */

        .confirmation {

            margin-top: 23px;

            padding:
                25px;

            background:
                linear-gradient(
                    145deg,
                    #EAF3F2,
                    #DDEDEC
                );

            border:
                1px solid
                #BFD9D6;

            border-radius:
                var(--radius);

            animation:
                confirmationIn .5s ease;
        }


        .confirmation h3 {

            margin-bottom: 15px;

            color:
                var(--mid);

            font-family:
                var(--font-display);

            font-size: 23px;

            font-weight: 800;
        }


        .confirmation p {

            margin:
                9px 0;

            color:
                rgba(14,42,46,.72);

            font-size: 13px;
        }


        .confirmation strong {

            color:
                var(--deep);
        }


        @keyframes confirmationIn {

            from {

                opacity: 0;

                transform:
                    scale(.97)
                    translateY(10px);
            }

            to {

                opacity: 1;

                transform:
                    scale(1)
                    translateY(0);
            }
        }


        @keyframes fadeUp {

            from {

                opacity: 0;

                transform:
                    translateY(9px);
            }

            to {

                opacity: 1;

                transform:
                    translateY(0);
            }
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {

            padding:
                36px 20px;

            background:
                var(--deep);

            color:
                rgba(255,255,255,.55);

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
           TABLET
        ===================================================== */

        @media (max-width: 1000px) {

            .info-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .ages-grid {

                grid-template-columns:
                    repeat(3, 1fr);
            }
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 700px) {

            header {

                min-height: 570px;

                padding:
                    70px 24px 100px;
            }


            header h1 {

                font-size:
                    58px;
            }


            main {

                padding:
                    0 18px;
            }


            .info-grid {

                grid-template-columns:
                    1fr 1fr;
            }


            .panel {

                padding:
                    23px;

                border-radius:
                    22px;
            }


            .panel h2 {

                font-size:
                    30px;
            }


            .activities {

                grid-template-columns:
                    1fr;
            }


            .form-grid {

                grid-template-columns:
                    1fr;
            }


            .full {

                grid-column:
                    auto;
            }


            .ages-section {

                grid-column:
                    auto;
            }


            .ages-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .user-status {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }
        }


        /* =====================================================
           MOBILE PEQUEÑO
        ===================================================== */

        @media (max-width: 450px) {

            header {

                min-height: 520px;

                padding:
                    60px 18px 90px;
            }


            header h1 {

                font-size:
                    45px;
            }


            header p {

                font-size:
                    14px;
            }


            main {

                padding:
                    0 12px;
            }


            .info-grid {

                grid-template-columns:
                    1fr;
            }


            .panel {

                padding:
                    19px;
            }


            .ages-grid {

                grid-template-columns:
                    1fr 1fr;
            }


            .back-button {

                width:
                    100%;
            }
        }

    </style>

</head>


<body>


<!-- ======================================================
     HEADER
====================================================== -->

<header>

    <div class="header-content">

        <div class="eyebrow">
            Laguna Experience · Experiencias
        </div>


        <h1>
            Actividades
            <span>Acuáticas</span>
        </h1>


        <p>

            Viví Laguna desde el agua.
            Elegí entre kayak, paddle, botes
            y motos de agua y disfrutá una
            experiencia adaptada a diferentes
            edades.

        </p>


        <div class="header-actions">

            <a
                href="../index.php"
                class="back-button"
            >
                ← Volver al inicio
            </a>

        </div>

    </div>

</header>


<!-- ======================================================
     MAIN
====================================================== -->

<main>


    <!-- ==================================================
         INFORMACIÓN
    ================================================== -->

    <div class="info-grid">


        <div class="info">

            <span>
                Experiencias
            </span>

            <strong>

                <?php

                echo count($actividades);

                ?>

                actividades

            </strong>

        </div>


        <div class="info">

            <span>
                Horarios
            </span>

            <strong>
                Según actividad
            </strong>

        </div>


        <div class="info">

            <span>
                Edad
            </span>

            <strong>
                Desde 4 años
            </strong>

        </div>


        <div class="info">

            <span>
                Reserva
            </span>

            <strong>
                Online/Presencial
            </strong>

        </div>


    </div>


    <!-- ==================================================
         ACTIVIDADES
    ================================================== -->

    <section class="panel">


        <h2>
            ¿Qué podés hacer?
        </h2>


        <p class="description">

            Elegí la experiencia que más te guste.
            Cada actividad cuenta con horarios,
            capacidad y requisitos específicos.

        </p>


        <div class="activities">


            <?php foreach (
                $actividades
                as $actividad
            ): ?>


                <div class="activity">


                    <h3>

                        <?php

                        echo htmlspecialchars(
                            $actividad["nombre"]
                        );

                        ?>

                    </h3>


                    <p>

                        <?php

                        echo htmlspecialchars(
                            $actividad["descripcion"]
                        );

                        ?>

                    </p>


                    <div class="activity-info">


                        <span class="tag">

                            Desde

                            <?php

                            echo htmlspecialchars(
                                $actividad["edad_minima"]
                            );

                            ?>

                            años

                        </span>


                        <span class="tag">

                            Capacidad:

                            <?php

                            echo htmlspecialchars(
                                $actividad["capacidad"]
                            );

                            ?>

                        </span>


                        <span class="tag">

                            <?php

                            echo htmlspecialchars(
                                $actividad["horario"]
                            );

                            ?>

                        </span>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    </section>


    <!-- ==================================================
         RESERVA
    ================================================== -->

    <section class="panel">


        <h2>
            Reservá tu experiencia
        </h2>


        <p class="description">

            Elegí la actividad, el día, el horario,
            la cantidad de personas y la edad
            de cada participante.

        </p>


        <?php if (
            isset($_SESSION["usuario_id"])
        ): ?>


            <div class="user-status">

                <span>

                    Sesión iniciada como

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $_SESSION["usuario"]
                        );

                        ?>

                    </strong>

                </span>


                <a
                    href="actividades-acuaticas.php?cerrar=1"
                    class="logout"
                >
                    Cerrar sesión
                </a>

            </div>


        <?php endif; ?>


        <form
            id="reservaForm"
            method="POST"
        >


            <input
                type="hidden"
                name="accion"
                value="reservar"
            >


            <div class="form-grid">


                <!-- ACTIVIDAD -->

                <div class="form-group">

                    <label for="actividad">
                        Actividad
                    </label>


                    <select
                        id="actividad"
                        name="actividad_id"
                        required
                    >


                        <?php foreach (
                            $actividades
                            as $actividad
                        ): ?>


                            <option
                                value="<?php echo $actividad["id"]; ?>"
                                data-horarios="<?php echo htmlspecialchars($actividad["horario"]); ?>"
                                data-capacidad="<?php echo $actividad["capacidad"]; ?>"
                                data-edad-minima="<?php echo $actividad["edad_minima"]; ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $actividad["nombre"]
                                );

                                ?>

                            </option>


                        <?php endforeach; ?>


                    </select>

                </div>


                <!-- FECHA -->

                <div class="form-group">

                    <label for="fecha">
                        Día de la reserva
                    </label>


                    <input
                        type="date"
                        id="fecha"
                        name="fecha"
                        min="<?php echo date("Y-m-d"); ?>"
                        required
                    >

                </div>


                <!-- HORARIO -->

                <div class="form-group">

                    <label for="horario">
                        Horario
                    </label>


                    <select
                        id="horario"
                        name="horario"
                        required
                    >
                    </select>

                </div>


                <!-- PERSONAS -->

                <div class="form-group">

                    <label for="personas">
                        Cantidad de personas
                    </label>


                    <input
                        type="number"
                        id="personas"
                        name="personas"
                        min="1"
                        max="30"
                        value="1"
                        required
                    >

                </div>


                <!-- EDADES -->

                <div
                    class="ages-section"
                >


                    <div class="ages-title">

                        Edad de los participantes

                    </div>


                    <p class="ages-description">

                        Ingresá la edad de cada persona.
                        Se generará un campo por cada
                        participante y se verificará
                        la edad mínima de la actividad.

                    </p>


                    <div
                        id="edadesGrid"
                        class="ages-grid"
                    >
                    </div>


                </div>


                <!-- BOTÓN -->

                <div class="full">

                    <button
                        type="submit"
                        class="primary"
                    >

                        Reservar experiencia

                    </button>

                </div>


            </div>


        </form>


        <?php if (
            !isset($_SESSION["usuario_id"])
        ): ?>


            <div class="register-link">

                Para realizar una reserva tenés que
                iniciar sesión.

                <a href="../login.php">
                    Iniciar sesión
                </a>

            </div>


        <?php endif; ?>


        <!-- ==================================================
             MENSAJE DE ERROR
        ================================================== -->

        <?php if (
            $mensajeReserva !== ""
        ): ?>


            <div
                class="message <?php echo htmlspecialchars($tipoMensajeReserva); ?>"
            >

                <?php

                echo htmlspecialchars(
                    $mensajeReserva
                );

                ?>

            </div>


        <?php endif; ?>


        <!-- ==================================================
             CONFIRMACIÓN
             SOLO APARECE SI LA RESERVA FUE EXITOSA
        ================================================== -->

        <?php if (
            $reservaConfirmada
        ): ?>


            <div class="confirmation">


                <h3>
                    ✓ Reserva confirmada
                </h3>


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
                        Actividad:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaActividad
                    );

                    ?>

                </p>


                <p>

                    <strong>
                        Día:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaFecha
                    );

                    ?>

                </p>


                <p>

                    <strong>
                        Horario:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaHorario
                    );

                    ?>

                </p>


                <p>

                    <strong>
                        Personas:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaPersonas
                    );

                    ?>

                </p>


                <p>

                    <strong>
                        Edades:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaEdades
                    );

                    ?>

                </p>


                <p>

                    <strong>
                        Usuario:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $reservaUsuario
                    );

                    ?>

                </p>


            </div>


        <?php endif; ?>


    </section>


    <!-- ==================================================
         INFORMACIÓN FINAL
    ================================================== -->

    <section class="panel">


        <h2>
            Antes de venir
        </h2>


        <p class="description">

            Para disfrutar de las actividades acuáticas
            tené en cuenta las condiciones de seguridad
            y los requisitos de edad correspondientes
            a cada experiencia.

        </p>


        <div class="activities">


            <div class="activity">

                <h3>
                    Edad mínima
                </h3>

                <p>

                    Cada actividad tiene una edad mínima
                    diferente. El sistema verifica la edad
                    de cada participante automáticamente.

                </p>

            </div>


            <div class="activity">

                <h3>
                    Cupos limitados
                </h3>

                <p>

                    Cada actividad cuenta con una
                    capacidad determinada y los cupos
                    se actualizan al realizar una reserva.

                </p>

            </div>


            <div class="activity">

                <h3>
                    Seguridad
                </h3>

                <p>

                    El acceso a las actividades está
                    sujeto al cumplimiento de los
                    requisitos correspondientes.

                </p>

            </div>


            <div class="activity">

                <h3>
                    Llegá con anticipación
                </h3>

                <p>

                    Recomendamos llegar unos minutos
                    antes del horario reservado.

                </p>

            </div>


        </div>


    </section>


</main>


<!-- ======================================================
     FOOTER
====================================================== -->

<footer>

    <strong>
        Laguna Experience
    </strong>

    Smart Experience Park

</footer>


<!-- ======================================================
     JAVASCRIPT
====================================================== -->

<script>


const actividadSelect =
    document.getElementById(
        "actividad"
    );


const horarioSelect =
    document.getElementById(
        "horario"
    );


const personasInput =
    document.getElementById(
        "personas"
    );


const edadesGrid =
    document.getElementById(
        "edadesGrid"
    );


// ======================================================
// ACTUALIZAR HORARIOS
// ======================================================

function actualizarHorarios() {


    const opcion =
        actividadSelect.options[
            actividadSelect.selectedIndex
        ];


    if (!opcion) {
        return;
    }


    const horariosTexto =
        opcion.dataset.horarios;


    const horarios =
        horariosTexto
            .split("/")
            .map(function(horario) {

                return horario.trim();

            });


    horarioSelect.innerHTML = "";


    horarios.forEach(
        function(horario) {


            const option =
                document.createElement(
                    "option"
                );


            option.value =
                horario;


            option.textContent =
                horario;


            horarioSelect.appendChild(
                option
            );

        }
    );


    // Capacidad máxima

    const capacidad =
        parseInt(
            opcion.dataset.capacidad
        );


    personasInput.max =
        capacidad;


    // Si había más personas que
    // la capacidad nueva

    if (
        parseInt(
            personasInput.value
        ) > capacidad
    ) {

        personasInput.value =
            capacidad;
    }


    actualizarEdades();

}


// ======================================================
// CREAR CAMPOS DE EDAD
// ======================================================

function actualizarEdades() {


    let cantidad =
        parseInt(
            personasInput.value
        );


    if (
        isNaN(cantidad) ||
        cantidad < 1
    ) {

        cantidad = 1;

        personasInput.value = 1;
    }


    const opcion =
        actividadSelect.options[
            actividadSelect.selectedIndex
        ];


    const edadMinima =
        parseInt(
            opcion.dataset.edadMinima
        ) || 1;


    // Guardar las edades que
    // ya estaban escritas

    const edadesActuales =
        Array.from(
            document.querySelectorAll(
                ".edad-participante"
            )
        ).map(function(input) {

            return input.value;

        });


    edadesGrid.innerHTML = "";


    // Crear un campo por participante

    for (
        let i = 1;
        i <= cantidad;
        i++
    ) {


        const contenedor =
            document.createElement(
                "div"
            );


        contenedor.className =
            "age-item";


        contenedor.style.animationDelay =
            ((i - 1) * 0.04) +
            "s";


        const label =
            document.createElement(
                "label"
            );


        label.htmlFor =
            "edad_" + i;


        label.textContent =
            "Participante " + i;


        const input =
            document.createElement(
                "input"
            );


        input.type =
            "number";


        input.id =
            "edad_" + i;


        input.name =
            "edades[]";


        input.className =
            "edad-participante";


        input.min =
            edadMinima;


        input.max =
            99;


        input.placeholder =
            "Edad";


        input.required =
            true;


        // Mantener edades ya escritas

        if (
            edadesActuales[i - 1]
        ) {

            input.value =
                edadesActuales[i - 1];
        }


        contenedor.appendChild(
            label
        );


        contenedor.appendChild(
            input
        );


        edadesGrid.appendChild(
            contenedor
        );

    }

}


// ======================================================
// CAMBIO DE ACTIVIDAD
// ======================================================

actividadSelect.addEventListener(
    "change",
    actualizarHorarios
);


// ======================================================
// CAMBIO DE CANTIDAD
// ======================================================

personasInput.addEventListener(
    "input",
    actualizarEdades
);


// ======================================================
// INICIALIZAR
// ======================================================

actualizarHorarios();

actualizarEdades();

</script>


</body>

</html>