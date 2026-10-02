<?php

session_start();

require_once "conexion.php";


/* =========================================================
   SESIÓN
========================================================= */

if (!isset($_SESSION["usuario_id"])) {

    header("Location: login.php");
    exit;
}


$usuarioId = intval($_SESSION["usuario_id"]);

$nombreSesion = $_SESSION["usuario"] ?? "";

$reservas = [];


/* =========================================================
   DATOS DEL USUARIO
========================================================= */

$sqlUsuario = "
    SELECT
        id,
        nombre,
        email,
        foto
    FROM usuarios
    WHERE id = $usuarioId
    LIMIT 1
";

$resultadoUsuario = mysqli_query(
    $conexion,
    $sqlUsuario
);

$usuario = null;

if (
    $resultadoUsuario &&
    mysqli_num_rows($resultadoUsuario) > 0
) {

    $usuario = mysqli_fetch_assoc(
        $resultadoUsuario
    );
}


/* =========================================================
   RESERVAS GENERALES
========================================================= */

$sqlReservas = "
    SELECT
        r.id,
        r.actividad_id,
        r.fecha,
        r.fecha_salida,
        r.horario,
        r.cantidad,
        r.total,
        r.estado,
        r.codigo,
        r.creado_en,

        l.nombre AS lugar_nombre,

        a.nombre AS actividad_nombre,
        a.descripcion AS actividad_descripcion,

        e.nombre AS evento_nombre,
        e.descripcion AS evento_descripcion,

        h.tipo AS hospedaje_tipo

    FROM reservas r

    LEFT JOIN lugares l
        ON l.id = r.lugar_id

    LEFT JOIN actividades a
        ON a.id = r.actividad_id

    LEFT JOIN eventos e
        ON e.id = r.evento_id

    LEFT JOIN hospedajes h
        ON h.id = r.hospedaje_id

    WHERE r.usuario_id = $usuarioId

    ORDER BY
        r.fecha DESC,
        r.horario DESC,
        r.id DESC
";

$resultadoReservas = mysqli_query(
    $conexion,
    $sqlReservas
);


if ($resultadoReservas) {

    while (
        $fila = mysqli_fetch_assoc(
            $resultadoReservas
        )
    ) {

        $nombre = "Reserva";

        $tipo = "general";

        $descripcion = "";


        if (
            !empty(
                $fila["actividad_nombre"]
            )
        ) {

            $nombre =
                $fila["actividad_nombre"];

            $tipo =
                "actividad";

            $descripcion =
                $fila["actividad_descripcion"]
                ?? "";


            if (
                intval(
                    $fila["actividad_id"]
                ) >= 11 &&
                intval(
                    $fila["actividad_id"]
                ) <= 14
            ) {

                $tipo =
                    "recreacion";
            }
        }

        elseif (
            !empty(
                $fila["evento_nombre"]
            )
        ) {

            $nombre =
                $fila["evento_nombre"];

            $tipo =
                "evento";

            $descripcion =
                $fila["evento_descripcion"]
                ?? "";
        }

        elseif (
            !empty(
                $fila["hospedaje_tipo"]
            )
        ) {

            $nombre =
                $fila["hospedaje_tipo"];

            $tipo =
                "hospedaje";
        }

        elseif (
            !empty(
                $fila["lugar_nombre"]
            )
        ) {

            $nombre =
                $fila["lugar_nombre"];

            $tipo =
                "lugar";
        }


        $lugar = strtolower(
            $fila["lugar_nombre"] ?? ""
        );


        if (
            strpos(
                $lugar,
                "acuática"
            ) !== false ||
            strpos(
                $lugar,
                "acuatic"
            ) !== false
        ) {

            $tipo = "acuaticas";
        }


        if (
            strpos(
                $lugar,
                "aventura"
            ) !== false
        ) {

            $tipo = "aventura";
        }


        if (
            strpos(
                $lugar,
                "beach"
            ) !== false
        ) {

            $tipo = "beach";
        }


        if (
            strpos(
                $lugar,
                "cabaña"
            ) !== false
        ) {

            $tipo = "cabanas";
        }


        if (
            intval(
                $fila["actividad_id"]
            ) >= 11 &&
            intval(
                $fila["actividad_id"]
            ) <= 14
        ) {

            $tipo = "recreacion";
        }


        $reservas[] = [

            "tipo_registro" =>
                "reserva",

            "tipo" =>
                $tipo,

            "nombre" =>
                $nombre,

            "descripcion" =>
                $descripcion,

            "fecha" =>
                $fila["fecha"],

            "fecha_salida" =>
                $fila["fecha_salida"],

            "hora" =>
                $fila["horario"],

            "cantidad" =>
                $fila["cantidad"],

            "total" =>
                $fila["total"],

            "estado" =>
                $fila["estado"],

            "codigo" =>
                $fila["codigo"],

            "creado_en" =>
                $fila["creado_en"],

            "lugar" =>
                $fila["lugar_nombre"],

            "id" =>
                $fila["id"]
        ];
    }
}


/* =========================================================
   SECTOR INFANTIL
========================================================= */

$sqlInfantil = "
    SELECT
        ir.id,
        ir.codigo,
        ir.usuario,
        ir.actividad_id,
        ir.participante,
        ir.edad,
        ir.creado_en,

        ia.nombre AS actividad_nombre

    FROM infantil_reservas ir

    LEFT JOIN infantil_actividades ia
        ON ia.id = ir.actividad_id

    WHERE ir.usuario_id = $usuarioId

    ORDER BY
        ir.creado_en DESC
";

$resultadoInfantil = mysqli_query(
    $conexion,
    $sqlInfantil
);


if ($resultadoInfantil) {

    while (
        $fila = mysqli_fetch_assoc(
            $resultadoInfantil
        )
    ) {

        $reservas[] = [

            "tipo_registro" =>
                "infantil",

            "tipo" =>
                "infantil",

            "nombre" =>
                $fila["actividad_nombre"]
                ?? "Actividad infantil",

            "descripcion" =>
                "Participante: " .
                ($fila["participante"] ?? ""),

            "fecha" =>
                date(
                    "Y-m-d",
                    strtotime(
                        $fila["creado_en"]
                    )
                ),

            "fecha_salida" =>
                null,

            "hora" =>
                null,

            "cantidad" =>
                1,

            "total" =>
                0,

            "estado" =>
                "confirmada",

            "codigo" =>
                $fila["codigo"],

            "creado_en" =>
                $fila["creado_en"],

            "lugar" =>
                "Sector Infantil",

            "participante" =>
                $fila["participante"],

            "edad" =>
                $fila["edad"],

            "id" =>
                $fila["id"]
        ];
    }
}


/* =========================================================
   GLAMPING
========================================================= */

$sqlGlamping = "
    SELECT
        id,
        codigo,
        usuario_id,
        usuario,
        unidad_id,
        unidad,
        fecha_entrada,
        fecha_salida,
        huespedes,
        noches,
        total,
        creado_en

    FROM glamping_reservas

    WHERE usuario_id = $usuarioId

    ORDER BY
        creado_en DESC
";

$resultadoGlamping = mysqli_query(
    $conexion,
    $sqlGlamping
);


if ($resultadoGlamping) {

    while (
        $fila = mysqli_fetch_assoc(
            $resultadoGlamping
        )
    ) {

        $reservas[] = [

            "tipo_registro" =>
                "glamping",

            "tipo" =>
                "glamping",

            "nombre" =>
                $fila["unidad"],

            "descripcion" =>
                "Glamping Norte",

            "fecha" =>
                $fila["fecha_entrada"],

            "fecha_salida" =>
                $fila["fecha_salida"],

            "hora" =>
                null,

            "cantidad" =>
                $fila["huespedes"],

            "total" =>
                $fila["total"],

            "estado" =>
                "confirmada",

            "codigo" =>
                $fila["codigo"],

            "creado_en" =>
                $fila["creado_en"],

            "lugar" =>
                "Glamping Norte",

            "noches" =>
                $fila["noches"],

            "id" =>
                $fila["id"]
        ];
    }
}


/* =========================================================
   RESTAURANTE
========================================================= */

$usuarioRestaurante =
    mysqli_real_escape_string(
        $conexion,
        $nombreSesion
    );


$sqlRestaurante = "
    SELECT
        id,
        codigo,
        usuario,
        mesa_id,
        comensales,
        fecha,
        hora,
        estado,
        creado_en

    FROM restaurante_reservas

    WHERE usuario = '$usuarioRestaurante'

    ORDER BY
        fecha DESC,
        hora DESC,
        id DESC
";

$resultadoRestaurante = mysqli_query(
    $conexion,
    $sqlRestaurante
);


if ($resultadoRestaurante) {

    while (
        $fila = mysqli_fetch_assoc(
            $resultadoRestaurante
        )
    ) {

        $reservas[] = [

            "tipo_registro" =>
                "restaurante",

            "tipo" =>
                "restaurante",

            "nombre" =>
                "Restaurante Panorámico",

            "descripcion" =>
                "Mesa " .
                $fila["mesa_id"] .
                " · " .
                $fila["comensales"] .
                " comensales",

            "fecha" =>
                $fila["fecha"],

            "fecha_salida" =>
                null,

            "hora" =>
                $fila["hora"],

            "cantidad" =>
                $fila["comensales"],

            "total" =>
                0,

            "estado" =>
                $fila["estado"],

            "codigo" =>
                $fila["codigo"],

            "creado_en" =>
                $fila["creado_en"],

            "lugar" =>
                "Restaurante Panorámico",

            "mesa_id" =>
                $fila["mesa_id"],

            "id" =>
                $fila["id"]
        ];
    }
}


/* =========================================================
   SPA
========================================================= */

$sqlSpa = "
    SELECT
        sr.id,
        sr.usuario_id,
        sr.servicio_id,
        sr.fecha,
        sr.horario,
        sr.codigo,
        sr.estado,
        sr.creado_en,

        ss.nombre AS servicio_nombre

    FROM spa_reservas sr

    LEFT JOIN spa_servicios ss
        ON ss.id = sr.servicio_id

    WHERE sr.usuario_id = $usuarioId

    ORDER BY
        sr.fecha DESC,
        sr.horario DESC,
        sr.id DESC
";

$resultadoSpa = mysqli_query(
    $conexion,
    $sqlSpa
);


if ($resultadoSpa) {

    while (
        $fila = mysqli_fetch_assoc(
            $resultadoSpa
        )
    ) {

        $reservas[] = [

            "tipo_registro" =>
                "spa",

            "tipo" =>
                "spa",

            "nombre" =>
                $fila["servicio_nombre"]
                ?? "Servicio de Spa",

            "descripcion" =>
                "Servicio de bienestar",

            "fecha" =>
                $fila["fecha"],

            "fecha_salida" =>
                null,

            "hora" =>
                $fila["horario"],

            "cantidad" =>
                1,

            "total" =>
                0,

            "estado" =>
                $fila["estado"],

            "codigo" =>
                $fila["codigo"],

            "creado_en" =>
                $fila["creado_en"],

            "lugar" =>
                "Spa Bosque",

            "id" =>
                $fila["id"]
        ];
    }
}


/* =========================================================
   MUNDO COOKIE
========================================================= */

$sqlCookie = "
    SELECT
        id,
        usuario_id,
        visitante,
        pulsera,
        taller_id,
        taller,
        horario,
        precio,
        puntos,
        asistencia,
        creado_en

    FROM mc_reservas_taller

    WHERE usuario_id = $usuarioId

    ORDER BY
        creado_en DESC,
        id DESC
";

$resultadoCookie = mysqli_query(
    $conexion,
    $sqlCookie
);


if ($resultadoCookie) {

    while (
        $fila = mysqli_fetch_assoc(
            $resultadoCookie
        )
    ) {

        $reservas[] = [

            "tipo_registro" =>
                "mundo_cookie",

            "tipo" =>
                "cookie",

            "nombre" =>
                $fila["taller"]
                ?? "Taller Mundo Cookie",

            "descripcion" =>
                "Participante: " .
                ($fila["visitante"] ?? ""),

            "fecha" =>
                date(
                    "Y-m-d",
                    strtotime(
                        $fila["creado_en"]
                    )
                ),

            "fecha_salida" =>
                null,

            "hora" =>
                $fila["horario"],

            "cantidad" =>
                1,

            "total" =>
                $fila["precio"] ?? 0,

            "estado" =>
                "confirmada",

            "codigo" =>
                $fila["pulsera"]
                ?? "",

            "creado_en" =>
                $fila["creado_en"],

            "lugar" =>
                "Mundo Cookie",

            "id" =>
                $fila["id"]
        ];
    }
}


/* =========================================================
   ORDENAR
========================================================= */

usort(
    $reservas,
    function (
        $a,
        $b
    ) {

        $fechaA =
            $a["fecha"] ?? "";

        $fechaB =
            $b["fecha"] ?? "";

        $horaA =
            $a["hora"] ?? "00:00:00";

        $horaB =
            $b["hora"] ?? "00:00:00";


        $timestampA =
            strtotime(
                $fechaA .
                " " .
                $horaA
            );


        $timestampB =
            strtotime(
                $fechaB .
                " " .
                $horaB
            );


        return
            $timestampB <=>
            $timestampA;
    }
);


/* =========================================================
   SEPARAR ACTIVAS Y CANCELADAS
========================================================= */

$reservasActivas = [];

$reservasCanceladas = [];


foreach (
    $reservas
    as $reserva
) {

    $estado =
        strtolower(
            trim(
                $reserva["estado"]
                ?? ""
            )
        );


    if (
        $estado == "cancelada"
    ) {

        $reservasCanceladas[] =
            $reserva;

    } else {

        $reservasActivas[] =
            $reserva;
    }
}


/* =========================================================
   FUNCIONES
========================================================= */

function nombreTipoReserva(
    $tipo
) {

    if (
        $tipo == "acuaticas"
    ) {
        return "Actividades Acuáticas";
    }


    if (
        $tipo == "aventura"
    ) {
        return "Parque de Aventura";
    }


    if (
        $tipo == "beach"
    ) {
        return "Beach Club";
    }


    if (
        $tipo == "cabanas" ||
        $tipo == "hospedaje"
    ) {
        return "Cabañas";
    }


    if (
        $tipo == "actividad"
    ) {
        return "Experiencia";
    }


    if (
        $tipo == "recreacion"
    ) {
        return "Experiencias y Recreación";
    }


    if (
        $tipo == "evento"
    ) {
        return "Evento especial";
    }


    if (
        $tipo == "glamping"
    ) {
        return "Glamping";
    }


    if (
        $tipo == "cookie"
    ) {
        return "Mundo Cookie";
    }


    if (
        $tipo == "restaurante"
    ) {
        return "Restaurante Panorámico";
    }


    if (
        $tipo == "infantil"
    ) {
        return "Sector Infantil";
    }


    if (
        $tipo == "spa"
    ) {
        return "Spa & Wellness";
    }


    return "Reserva";
}


function textoEstado(
    $estado
) {

    if (
        $estado == "confirmada"
    ) {
        return "Confirmada";
    }


    if (
        $estado == "cancelada"
    ) {
        return "Cancelada";
    }


    if (
        $estado == "pendiente"
    ) {
        return "Pendiente";
    }


    return ucfirst(
        $estado
    );
}


function claseEstado(
    $estado
) {

    if (
        $estado == "confirmada"
    ) {
        return "estado-confirmada";
    }


    if (
        $estado == "cancelada"
    ) {
        return "estado-cancelada";
    }


    return "estado-pendiente";
}


function formatearFecha(
    $fecha
) {

    if (!$fecha) {
        return "-";
    }


    $timestamp =
        strtotime(
            $fecha
        );


    if (!$timestamp) {
        return $fecha;
    }


    return date(
        "d/m/Y",
        $timestamp
    );
}


function formatearHora(
    $hora
) {

    if (!$hora) {
        return "";
    }


    $timestamp =
        strtotime(
            $hora
        );


    if (!$timestamp) {
        return $hora;
    }


    return date(
        "H:i",
        $timestamp
    );
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
        Mis reservas | Laguna Experience
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

            --ink: #174a36;
            --ink-deep: #0d3829;
            --water: #36a66f;
            --water-dark: #247d52;
            --turquoise: #82d8a7;
            --sand: #edf3e8;
            --cream: #f8faf5;
            --white: #ffffff;

            --line:
                rgba(23, 74, 54, .12);

            --shadow:
                0 24px 70px
                rgba(13, 56, 41, .12);

            --font-body:
                "DM Sans", sans-serif;

            --font-display:
                "Manrope", sans-serif;
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
                    #f8faf5 0%,
                    #edf3e8 100%
                );

            color: var(--ink);

            font-family:
                var(--font-body);

            overflow-x: hidden;

            padding-top: 72px;
        }


        a {
            text-decoration: none;
            color: inherit;
        }


        /* NAVBAR */

        .main-navbar {

            position: fixed;

            top: 0;
            left: 0;

            width: 100%;

            z-index: 1000;

            background:
                rgba(
                    248,
                    250,
                    245,
                    .86
                );

            backdrop-filter:
                blur(16px);

            -webkit-backdrop-filter:
                blur(16px);

            border-bottom:
                1px solid
                rgba(
                    23,
                    74,
                    54,
                    .10
                );

            transition:
                background .25s ease,
                box-shadow .25s ease;
        }


        .main-navbar.scrolled {

            background:
                rgba(
                    248,
                    250,
                    245,
                    .97
                );

            box-shadow:
                0 8px 30px
                rgba(
                    13,
                    56,
                    41,
                    .12
                );
        }


        .nav-container {

            width: 100%;

            max-width: 1380px;

            min-height: 72px;

            margin: 0 auto;

            padding: 0 5%;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 30px;
        }


        .nav-logo {

            color: var(--ink);

            font-family:
                var(--font-display);

            font-size: 20px;

            font-weight: 800;

            letter-spacing: -.04em;

            white-space: nowrap;
        }


        .nav-logo span {
            color: var(--water);
        }


        .nav-logo:hover {
            color: var(--water-dark);
        }


        .nav-links {

            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 5px;
        }


        .nav-links a {

            position: relative;

            padding:
                10px 15px;

            border-radius: 12px;

            color: var(--ink);

            font-size: 12px;

            font-weight: 700;

            transition:
                background .2s ease,
                color .2s ease,
                transform .2s ease;
        }


        .nav-links a:hover {

            background:
                rgba(
                    54,
                    166,
                    111,
                    .10
                );

            color:
                var(--water-dark);

            transform:
                translateY(-1px);
        }


        .nav-links .activo {

            background:
                rgba(
                    54,
                    166,
                    111,
                    .10
                );

            color:
                var(--water-dark);
        }


        .usuario-nombre {

            display: flex;

            align-items: center;

            gap: 9px;

            padding:
                8px 14px;

            border-radius: 12px;

            background:
                rgba(
                    20,
                    184,
                    166,
                    .10
                );

            border:
                1px solid
                rgba(
                    20,
                    184,
                    166,
                    .22
                );

            color:
                #147d73;

            font-size: 12px;

            font-weight: 600;

            white-space: nowrap;
        }


        .usuario-nombre::before {

            content: "";

            width: 8px;

            height: 8px;

            border-radius: 50%;

            background:
                #20b486;

            box-shadow:
                0 0 0 4px
                rgba(
                    32,
                    180,
                    134,
                    .12
                );
        }


        .logout-link {

            color:
                #8c4d4d !important;
        }


        .logout-link:hover {

            background:
                rgba(
                    160,
                    61,
                    61,
                    .07
                ) !important;

            color:
                #8f3f3f !important;
        }


        /* CONTENIDO */

        .pagina {

            width: 100%;

            max-width: 1280px;

            margin: 0 auto;

            padding:
                55px 5% 90px;
        }


        .encabezado {

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 40px;

            margin-bottom: 35px;
        }


        .encabezado-texto {
            max-width: 720px;
        }


        .encabezado-kicker {

            display: block;

            margin-bottom: 13px;

            color:
                var(--water-dark);

            font-size: 11px;

            font-weight: 800;

            letter-spacing: .18em;

            text-transform: uppercase;
        }


        .encabezado h1 {

            margin-bottom: 15px;

            color:
                var(--ink-deep);

            font-family:
                var(--font-display);

            font-size:
                clamp(
                    42px,
                    6vw,
                    72px
                );

            font-weight: 800;

            line-height: .95;

            letter-spacing: -.06em;
        }


        .encabezado p {

            max-width: 620px;

            color:
                rgba(
                    23,
                    74,
                    54,
                    .62
                );

            font-size: 15px;

            line-height: 1.7;
        }


        .contador {

            flex-shrink: 0;

            min-width: 160px;

            padding:
                18px 20px;

            border:
                1px solid
                rgba(
                    54,
                    166,
                    111,
                    .16
                );

            border-radius: 18px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .68
                );

            box-shadow:
                0 12px 35px
                rgba(
                    13,
                    56,
                    41,
                    .06
                );
        }


        .contador-numero {

            display: block;

            margin-bottom: 3px;

            color:
                var(--water-dark);

            font-family:
                var(--font-display);

            font-size: 28px;

            font-weight: 800;
        }


        .contador-texto {

            color:
                rgba(
                    23,
                    74,
                    54,
                    .55
                );

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .08em;
        }


        /* PERFIL */

        .perfil {

            display: flex;

            align-items: center;

            gap: 17px;

            margin-bottom: 55px;

            padding:
                18px 20px;

            border:
                1px solid
                rgba(
                    23,
                    74,
                    54,
                    .08
                );

            border-radius: 20px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .62
                );

            box-shadow:
                0 15px 45px
                rgba(
                    13,
                    56,
                    41,
                    .06
                );
        }


        .perfil-foto {

            width: 56px;

            height: 56px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 17px;

            background:
                linear-gradient(
                    135deg,
                    rgba(
                        54,
                        166,
                        111,
                        .16
                    ),
                    rgba(
                        130,
                        216,
                        167,
                        .28
                    )
                );

            border:
                1px solid
                rgba(
                    54,
                    166,
                    111,
                    .20
                );

            color:
                #247d52;

            box-shadow:
                0 8px 22px
                rgba(
                    36,
                    125,
                    82,
                    .10
                );
        }


        .perfil-foto svg {

            width: 30px;

            height: 30px;
        }


        .perfil-info h2 {

            margin-bottom: 3px;

            color:
                var(--ink);

            font-family:
                var(--font-display);

            font-size: 16px;

            font-weight: 800;
        }


        .perfil-info p {

            color:
                rgba(
                    23,
                    74,
                    54,
                    .52
                );

            font-size: 12px;
        }


        /* SECCIONES */

        .reservas-seccion {

            margin-bottom: 65px;
        }


        .seccion-header {

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 25px;

            margin-bottom: 20px;

            padding-bottom: 15px;

            border-bottom:
                1px solid
                var(--line);
        }


        .seccion-titulo {

            color:
                var(--ink-deep);

            font-family:
                var(--font-display);

            font-size: 25px;

            font-weight: 800;

            letter-spacing: -.04em;
        }


        .seccion-subtitulo {

            color:
                rgba(
                    23,
                    74,
                    54,
                    .52
                );

            font-size: 12px;
        }


        /* TARJETAS */

        .reservas-lista {

            display: grid;

            gap: 14px;
        }


        .reserva-card {

            display: grid;

            grid-template-columns:
                5px
                minmax(0, 1fr)
                auto;

            overflow: hidden;

            border:
                1px solid
                rgba(
                    23,
                    74,
                    54,
                    .08
                );

            border-radius: 20px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .72
                );

            box-shadow:
                0 15px 40px
                rgba(
                    13,
                    56,
                    41,
                    .055
                );

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                border-color .25s ease;
        }


        .reserva-card:hover {

            transform:
                translateY(-3px);

            border-color:
                rgba(
                    54,
                    166,
                    111,
                    .18
                );

            box-shadow:
                0 22px 50px
                rgba(
                    13,
                    56,
                    41,
                    .10
                );
        }


        .reserva-linea {

            width: 5px;

            background:
                linear-gradient(
                    180deg,
                    var(--water),
                    var(--turquoise)
                );
        }


        .reserva-principal {

            min-width: 0;

            padding:
                23px 25px;
        }


        .reserva-tipo {

            display: block;

            margin-bottom: 6px;

            color:
                var(--water-dark);

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .13em;

            text-transform: uppercase;
        }


        .reserva-nombre {

            margin-bottom: 6px;

            color:
                var(--ink);

            font-family:
                var(--font-display);

            font-size: 20px;

            font-weight: 800;

            letter-spacing: -.035em;
        }


        .reserva-descripcion {

            margin-bottom: 15px;

            color:
                rgba(
                    23,
                    74,
                    54,
                    .54
                );

            font-size: 12px;

            line-height: 1.5;
        }


        .datos-reserva {

            display: flex;

            flex-wrap: wrap;

            gap: 7px;
        }


        .dato {

            display: inline-flex;

            align-items: center;

            padding:
                7px 10px;

            border:
                1px solid
                rgba(
                    23,
                    74,
                    54,
                    .06
                );

            border-radius: 9px;

            background:
                rgba(
                    237,
                    243,
                    232,
                    .70
                );

            color:
                rgba(
                    23,
                    74,
                    54,
                    .68
                );

            font-size: 10px;

            font-weight: 700;
        }


        .reserva-lateral {

            min-width: 205px;

            display: flex;

            flex-direction: column;

            align-items: flex-end;

            justify-content: center;

            gap: 8px;

            padding:
                20px 24px;

            border-left:
                1px solid
                rgba(
                    23,
                    74,
                    54,
                    .07
                );

            background:
                rgba(
                    248,
                    250,
                    245,
                    .45
                );
        }


        .estado {

            display: inline-flex;

            padding:
                6px 10px;

            border-radius: 30px;

            font-size: 9px;

            font-weight: 800;

            letter-spacing: .06em;

            text-transform: uppercase;
        }


        .estado-confirmada {

            background:
                rgba(
                    54,
                    166,
                    111,
                    .11
                );

            color:
                var(--water-dark);
        }


        .estado-cancelada {

            background:
                rgba(
                    160,
                    61,
                    61,
                    .09
                );

            color:
                #9b4141;
        }


        .estado-pendiente {

            background:
                rgba(
                    190,
                    142,
                    37,
                    .11
                );

            color:
                #956d16;
        }


        .precio {

            color:
                var(--ink);

            font-family:
                var(--font-display);

            font-size: 16px;

            font-weight: 800;
        }


        /* =================================================
           CÓDIGO DE RESERVA
        ================================================= */

        .codigo-contenedor {

            display: flex;

            flex-direction: column;

            align-items: flex-end;

            gap: 5px;

            margin-top: 3px;
        }


        .codigo-label {

            color:
                rgba(
                    23,
                    74,
                    54,
                    .42
                );

            font-size: 9px;

            font-weight: 800;

            letter-spacing: .10em;

            text-transform: uppercase;
        }


        .codigo {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                11px 16px;

            border:
                1px solid
                rgba(
                    54,
                    166,
                    111,
                    .22
                );

            border-radius: 11px;

            background:
                rgba(
                    54,
                    166,
                    111,
                    .08
                );

            color:
                var(--water-dark);

            font-family:
                var(--font-display);

            font-size: 16px;

            font-weight: 800;

            letter-spacing: .08em;

            line-height: 1;

            box-shadow:
                0 6px 18px
                rgba(
                    36,
                    125,
                    82,
                    .07
                );
        }


        .btn-cancelar {

            margin-top: 3px;

            padding:
                8px 12px;

            border:
                1px solid
                rgba(
                    160,
                    61,
                    61,
                    .14
                );

            border-radius: 9px;

            background:
                rgba(
                    160,
                    61,
                    61,
                    .05
                );

            color:
                #9b4141;

            cursor: pointer;

            font-size: 9px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .04em;

            transition:
                background .2s ease,
                border-color .2s ease,
                transform .2s ease;
        }


        .btn-cancelar:hover {

            background:
                rgba(
                    160,
                    61,
                    61,
                    .10
                );

            border-color:
                rgba(
                    160,
                    61,
                    61,
                    .25
                );

            transform:
                translateY(-1px);
        }


        /* CANCELADAS */

        .canceladas-seccion
        .seccion-header {

            border-bottom-color:
                rgba(
                    160,
                    61,
                    61,
                    .13
                );
        }


        .canceladas-seccion
        .seccion-titulo {

            color:
                #704343;
        }


        .canceladas-seccion
        .reserva-card {

            opacity: .78;

            background:
                rgba(
                    255,
                    252,
                    252,
                    .70
                );
        }


        .canceladas-seccion
        .reserva-linea {

            background:
                #b96a6a;
        }


        .canceladas-seccion
        .codigo {

            border-color:
                rgba(
                    160,
                    61,
                    61,
                    .18
                );

            background:
                rgba(
                    160,
                    61,
                    61,
                    .06
                );

            color:
                #8f4a4a;
        }


        /* VACÍO */

        .vacio {

            padding:
                65px 25px;

            border:
                1px solid
                rgba(
                    23,
                    74,
                    54,
                    .08
                );

            border-radius: 22px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .64
                );

            text-align: center;

            box-shadow:
                0 15px 40px
                rgba(
                    13,
                    56,
                    41,
                    .05
                );
        }


        .vacio-linea {

            width: 45px;

            height: 3px;

            margin:
                0 auto 18px;

            border-radius: 5px;

            background:
                var(--water);
        }


        .vacio h2 {

            margin-bottom: 8px;

            color:
                var(--ink);

            font-family:
                var(--font-display);

            font-size: 21px;

            font-weight: 800;
        }


        .vacio p {

            max-width: 430px;

            margin:
                0 auto 20px;

            color:
                rgba(
                    23,
                    74,
                    54,
                    .55
                );

            font-size: 13px;

            line-height: 1.6;
        }


        .btn-explorar {

            display: inline-flex;

            padding:
                11px 17px;

            border-radius: 11px;

            background:
                var(--water);

            color: white;

            font-size: 11px;

            font-weight: 800;

            transition:
                background .2s ease,
                transform .2s ease;
        }


        .btn-explorar:hover {

            background:
                var(--water-dark);

            transform:
                translateY(-2px);
        }


        /* RESPONSIVE */

        @media (max-width: 900px) {

            .nav-container {

                min-height: 64px;
            }


            body {

                padding-top: 64px;
            }


            .main-navbar {

                overflow-x: auto;
            }


            .nav-container {

                min-width: max-content;

                padding:
                    0 18px;
            }


            .nav-links {

                gap: 2px;
            }


            .nav-links a {

                padding:
                    9px 11px;
            }


            .encabezado {

                align-items: flex-start;

                flex-direction: column;
            }


            .reserva-card {

                grid-template-columns:
                    5px
                    minmax(0, 1fr);
            }


            .reserva-lateral {

                grid-column: 2;

                align-items: flex-start;

                border-left: 0;

                border-top:
                    1px solid
                    rgba(
                        23,
                        74,
                        54,
                        .07
                    );
            }


            .codigo-contenedor {

                align-items: flex-start;
            }
        }


        @media (max-width: 600px) {

            .pagina {

                padding:
                    35px 18px 65px;
            }


            .encabezado h1 {

                font-size: 46px;
            }


            .perfil {

                margin-bottom: 40px;
            }


            .seccion-header {

                align-items: flex-start;

                flex-direction: column;

                gap: 5px;
            }


            .reserva-principal {

                padding:
                    19px 18px;
            }


            .reserva-lateral {

                padding:
                    14px 18px;
            }


            .reserva-nombre {

                font-size: 18px;
            }


            .codigo {

                font-size: 14px;

                padding:
                    10px 13px;

                letter-spacing: .06em;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="main-navbar">

    <div class="nav-container">


        <a
            href="index.php#inicio"
            class="nav-logo"
        >

            Laguna
            <span>
                Experience
            </span>

        </a>


        <div class="nav-links">


            <a href="index.php#inicio">
                Inicio
            </a>


            <?php if (
                isset(
                    $_SESSION["usuario_id"]
                )
            ): ?>


                <a
                    href="mis-reservas.php"
                    class="activo"
                >
                    Mis reservas
                </a>


                <a
                    href="logout.php"
                    class="logout-link"
                >
                    Cerrar sesión
                </a>


                <span class="usuario-nombre">

                    Hola,
                    <?= htmlspecialchars(
                        $nombreSesion
                    ) ?>

                </span>


            <?php else: ?>


                <a href="login.php">
                    Iniciar sesión
                </a>


            <?php endif; ?>


        </div>

    </div>

</nav>


<!-- =====================================================
     CONTENIDO
===================================================== -->

<main class="pagina">


    <div class="encabezado">


        <div class="encabezado-texto">

            <span class="encabezado-kicker">
                Laguna Experience
            </span>


            <h1>
                Mis reservas
            </h1>


            <p>
                Consultá todas las experiencias,
                actividades y servicios que reservaste
                desde tu cuenta.
            </p>

        </div>


        <div class="contador">

            <span class="contador-numero">

                <?= count(
                    $reservasActivas
                ) ?>

            </span>


            <span class="contador-texto">
                reservas activas
            </span>

        </div>

    </div>


    <?php if ($usuario): ?>

        <div class="perfil">


            <div class="perfil-foto">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >

                    <circle
                        cx="12"
                        cy="8"
                        r="4"
                        stroke="currentColor"
                        stroke-width="1.8"
                    />

                    <path
                        d="M4.5 20C5.2 16.5 8 14.5 12 14.5C16 14.5 18.8 16.5 19.5 20"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />

                </svg>

            </div>


            <div class="perfil-info">

                <h2>

                    <?= htmlspecialchars(
                        $usuario["nombre"]
                    ) ?>

                </h2>


                <p>

                    <?= htmlspecialchars(
                        $usuario["email"]
                    ) ?>

                </p>

            </div>

        </div>

    <?php endif; ?>


    <!-- =================================================
         RESERVAS ACTIVAS
    ================================================= -->

    <section class="reservas-seccion">


        <div class="seccion-header">


            <div>

                <h2 class="seccion-titulo">
                    Reservas activas
                </h2>


                <p class="seccion-subtitulo">
                    Tus reservas actuales y pendientes.
                </p>

            </div>


            <span class="seccion-subtitulo">

                <?= count(
                    $reservasActivas
                ) ?>

                reservas

            </span>

        </div>


        <?php if (
            count(
                $reservasActivas
            ) > 0
        ): ?>


            <div class="reservas-lista">


                <?php foreach (
                    $reservasActivas
                    as $reserva
                ): ?>


                    <?php

                    $estado =
                        strtolower(
                            $reserva["estado"]
                            ?? "confirmada"
                        );


                    $total =
                        floatval(
                            $reserva["total"]
                            ?? 0
                        );

                    ?>


                    <div class="reserva-card">


                        <div class="reserva-linea"></div>


                        <div class="reserva-principal">


                            <span class="reserva-tipo">

                                <?= htmlspecialchars(
                                    nombreTipoReserva(
                                        $reserva["tipo"]
                                    )
                                ) ?>

                            </span>


                            <h3 class="reserva-nombre">

                                <?= htmlspecialchars(
                                    $reserva["nombre"]
                                    ?? "Reserva"
                                ) ?>

                            </h3>


                            <?php if (
                                !empty(
                                    $reserva[
                                        "descripcion"
                                    ]
                                )
                            ): ?>

                                <p class="reserva-descripcion">

                                    <?= htmlspecialchars(
                                        $reserva[
                                            "descripcion"
                                        ]
                                    ) ?>

                                </p>

                            <?php endif; ?>


                            <div class="datos-reserva">


                                <?php if (
                                    !empty(
                                        $reserva["fecha"]
                                    )
                                ): ?>

                                    <span class="dato">

                                        Fecha:
                                        <?= formatearFecha(
                                            $reserva["fecha"]
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $reserva["hora"]
                                    )
                                ): ?>

                                    <span class="dato">

                                        Horario:
                                        <?= formatearHora(
                                            $reserva["hora"]
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    isset(
                                        $reserva[
                                            "cantidad"
                                        ]
                                    ) &&
                                    $reserva[
                                        "cantidad"
                                    ] > 0
                                ): ?>

                                    <span class="dato">

                                        Cantidad:
                                        <?= intval(
                                            $reserva[
                                                "cantidad"
                                            ]
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $reserva[
                                            "fecha_salida"
                                        ]
                                    )
                                ): ?>

                                    <span class="dato">

                                        Salida:
                                        <?= formatearFecha(
                                            $reserva[
                                                "fecha_salida"
                                            ]
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $reserva["noches"]
                                    )
                                ): ?>

                                    <span class="dato">

                                        <?= intval(
                                            $reserva[
                                                "noches"
                                            ]
                                        ) ?>

                                        noches

                                    </span>

                                <?php endif; ?>


                            </div>

                        </div>


                        <div class="reserva-lateral">


                            <span
                                class="estado
                                <?= claseEstado(
                                    $estado
                                ) ?>"
                            >

                                <?= textoEstado(
                                    $estado
                                ) ?>

                            </span>


                            <?php if (
                                $total > 0
                            ): ?>

                                <span class="precio">

                                    $

                                    <?= number_format(
                                        $total,
                                        0,
                                        ",",
                                        "."
                                    ) ?>

                                </span>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $reserva[
                                        "codigo"
                                    ]
                                )
                            ): ?>

                                <div class="codigo-contenedor">

                                    <span class="codigo-label">
                                        Código de reserva
                                    </span>

                                    <span class="codigo">

                                        <?= htmlspecialchars(
                                            $reserva[
                                                "codigo"
                                            ]
                                        ) ?>

                                    </span>

                                </div>

                            <?php endif; ?>


                            <form
                                method="POST"
                                action="cancelar-reserva.php"
                                onsubmit="return confirmarCancelacion();"
                            >

                                <input
                                    type="hidden"
                                    name="reserva_id"
                                    value="<?= intval(
                                        $reserva["id"]
                                    ) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="tipo"
                                    value="<?= htmlspecialchars(
                                        $reserva[
                                            "tipo_registro"
                                        ]
                                    ) ?>"
                                >


                                <button
                                    type="submit"
                                    class="btn-cancelar"
                                >
                                    Cancelar reserva
                                </button>

                            </form>


                        </div>

                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <div class="vacio">

                <div class="vacio-linea"></div>


                <h2>
                    No tenés reservas activas
                </h2>


                <p>
                    Cuando reserves una experiencia,
                    actividad o servicio,
                    aparecerá en esta sección.
                </p>


                <a
                    href="index.php#experiencias"
                    class="btn-explorar"
                >
                    Explorar experiencias
                </a>

            </div>


        <?php endif; ?>


    </section>


    <!-- =================================================
         RESERVAS CANCELADAS
    ================================================= -->

    <?php if (
        count(
            $reservasCanceladas
        ) > 0
    ): ?>


        <section
            class="reservas-seccion
            canceladas-seccion"
        >


            <div class="seccion-header">


                <div>

                    <h2 class="seccion-titulo">
                        Reservas canceladas
                    </h2>


                    <p class="seccion-subtitulo">
                        Historial de reservas canceladas.
                    </p>

                </div>


                <span class="seccion-subtitulo">

                    <?= count(
                        $reservasCanceladas
                    ) ?>

                    canceladas

                </span>

            </div>


            <div class="reservas-lista">


                <?php foreach (
                    $reservasCanceladas
                    as $reserva
                ): ?>


                    <?php

                    $total =
                        floatval(
                            $reserva["total"]
                            ?? 0
                        );

                    ?>


                    <div class="reserva-card">


                        <div class="reserva-linea"></div>


                        <div class="reserva-principal">


                            <span class="reserva-tipo">

                                <?= htmlspecialchars(
                                    nombreTipoReserva(
                                        $reserva["tipo"]
                                    )
                                ) ?>

                            </span>


                            <h3 class="reserva-nombre">

                                <?= htmlspecialchars(
                                    $reserva["nombre"]
                                    ?? "Reserva"
                                ) ?>

                            </h3>


                            <?php if (
                                !empty(
                                    $reserva[
                                        "descripcion"
                                    ]
                                )
                            ): ?>

                                <p class="reserva-descripcion">

                                    <?= htmlspecialchars(
                                        $reserva[
                                            "descripcion"
                                        ]
                                    ) ?>

                                </p>

                            <?php endif; ?>


                            <div class="datos-reserva">


                                <?php if (
                                    !empty(
                                        $reserva["fecha"]
                                    )
                                ): ?>

                                    <span class="dato">

                                        Fecha:
                                        <?= formatearFecha(
                                            $reserva["fecha"]
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $reserva["hora"]
                                    )
                                ): ?>

                                    <span class="dato">

                                        Horario:
                                        <?= formatearHora(
                                            $reserva["hora"]
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                            </div>

                        </div>


                        <div class="reserva-lateral">


                            <span
                                class="estado estado-cancelada"
                            >
                                Cancelada
                            </span>


                            <?php if (
                                $total > 0
                            ): ?>

                                <span class="precio">

                                    $

                                    <?= number_format(
                                        $total,
                                        0,
                                        ",",
                                        "."
                                    ) ?>

                                </span>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $reserva[
                                        "codigo"
                                    ]
                                )
                            ): ?>

                                <div class="codigo-contenedor">

                                    <span class="codigo-label">
                                        Código de reserva
                                    </span>

                                    <span class="codigo">

                                        <?= htmlspecialchars(
                                            $reserva[
                                                "codigo"
                                            ]
                                        ) ?>

                                    </span>

                                </div>

                            <?php endif; ?>


                        </div>

                    </div>


                <?php endforeach; ?>


            </div>


        </section>


    <?php endif; ?>


</main>


<script>

window.addEventListener(
    "scroll",
    function () {

        const navbar =
            document.querySelector(
                ".main-navbar"
            );


        if (
            window.scrollY > 10
        ) {

            navbar.classList.add(
                "scrolled"
            );

        } else {

            navbar.classList.remove(
                "scrolled"
            );
        }

    }
);


function confirmarCancelacion() {

    return confirm(
        "¿Estás seguro de que querés cancelar esta reserva?"
    );

}

</script>


</body>

</html>