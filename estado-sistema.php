<?php

session_start();

require_once __DIR__ . "/conexion.php";


/* =========================================================
   CONTROL DE ACCESO
   ========================================================= */

if (
    !isset($_SESSION["usuario_id"]) ||
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] != "admin"
) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   FUNCIONES
   ========================================================= */

function tablaExiste($conexion, $tabla)
{
    $tabla = mysqli_real_escape_string($conexion, $tabla);

    $resultado = mysqli_query(
        $conexion,
        "SHOW TABLES LIKE '$tabla'"
    );

    return $resultado && mysqli_num_rows($resultado) > 0;
}


function cantidadRegistros($conexion, $tabla)
{
    if (!tablaExiste($conexion, $tabla)) {
        return 0;
    }

    $resultado = mysqli_query(
        $conexion,
        "SELECT COUNT(*) AS cantidad FROM `$tabla`"
    );

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);
        return (int)$fila["cantidad"];
    }

    return 0;
}


function escapar($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   FINALIZAR ACTIVIDAD / RESERVA
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (
        isset($_POST["accion"]) &&
        $_POST["accion"] == "finalizar_reserva" &&
        isset($_POST["reserva_id"])
    ) {

        $reservaId = (int)$_POST["reserva_id"];

        if ($reservaId > 0 && tablaExiste($conexion, "reservas")) {

            mysqli_query(
                $conexion,
                "UPDATE reservas
                 SET estado = 'finalizada'
                 WHERE id = $reservaId
                 AND estado = 'confirmada'"
            );
        }

        $seccionRedireccion = $_POST["seccion"] ?? "reservas";

        header(
            "Location: " . $_SERVER["PHP_SELF"] .
            "?seccion=" . urlencode($seccionRedireccion)
        );
        exit;
    }
}


/* =========================================================
   SECCIÓN ACTUAL
   ========================================================= */

$seccion = $_GET["seccion"] ?? "inicio";


/* =========================================================
   DATOS GENERALES
   ========================================================= */

$totalUsuarios = cantidadRegistros($conexion, "usuarios");
$totalReservas = cantidadRegistros($conexion, "reservas");
$totalActividades = cantidadRegistros($conexion, "actividades");
$totalEventos = cantidadRegistros($conexion, "eventos");
$totalEmprendimientos = cantidadRegistros($conexion, "emprendimientos");
$totalHospedajes = cantidadRegistros($conexion, "hospedajes");


/* =========================================================
   RESERVAS DE HOY
   ========================================================= */

$reservasHoy = 0;

if (tablaExiste($conexion, "reservas")) {

    $resultado = mysqli_query(
        $conexion,
        "SELECT COUNT(*) AS cantidad
         FROM reservas
         WHERE fecha = CURDATE()"
    );

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);
        $reservasHoy = (int)$fila["cantidad"];
    }
}


/* =========================================================
   RESERVAS CONFIRMADAS
   ========================================================= */

$reservasConfirmadas = 0;

if (tablaExiste($conexion, "reservas")) {

    $resultado = mysqli_query(
        $conexion,
        "SELECT COUNT(*) AS cantidad
         FROM reservas
         WHERE estado = 'confirmada'"
    );

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);
        $reservasConfirmadas = (int)$fila["cantidad"];
    }
}


/* =========================================================
   RESERVAS PENDIENTES
   ========================================================= */

$reservasPendientes = 0;

if (tablaExiste($conexion, "reservas")) {

    $resultado = mysqli_query(
        $conexion,
        "SELECT COUNT(*) AS cantidad
         FROM reservas
         WHERE estado = 'pendiente'"
    );

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);
        $reservasPendientes = (int)$fila["cantidad"];
    }
}


/* =========================================================
   ACTIVIDADES
   ========================================================= */

$actividades = [];

if (tablaExiste($conexion, "actividades")) {

    $resultado = mysqli_query(
        $conexion,
        "SELECT *
         FROM actividades
         ORDER BY nombre ASC"
    );

    if ($resultado) {

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $actividades[] = $fila;
        }
    }
}


/* =========================================================
   ACTIVIDADES CON POCOS CUPOS
   ========================================================= */

$actividadesPocosCupos = 0;

if (tablaExiste($conexion, "actividades")) {

    $resultado = mysqli_query(
        $conexion,
        "SELECT COUNT(*) AS cantidad
         FROM actividades
         WHERE cupos_disponibles > 0
         AND cupos_disponibles <= 5"
    );

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);
        $actividadesPocosCupos = (int)$fila["cantidad"];
    }
}


/* =========================================================
   ACTIVIDADES COMPLETAS
   ========================================================= */

$actividadesCompletas = 0;

if (tablaExiste($conexion, "actividades")) {

    $resultado = mysqli_query(
        $conexion,
        "SELECT COUNT(*) AS cantidad
         FROM actividades
         WHERE cupos_disponibles = 0
         OR estado = 'completo'"
    );

    if ($resultado) {
        $fila = mysqli_fetch_assoc($resultado);
        $actividadesCompletas = (int)$fila["cantidad"];
    }
}


/* =========================================================
   RESERVAS
   ========================================================= */

$reservas = [];

if (
    tablaExiste($conexion, "reservas") &&
    tablaExiste($conexion, "usuarios") &&
    tablaExiste($conexion, "actividades")
) {

    $resultado = mysqli_query(
        $conexion,
        "SELECT
            r.id,
            r.codigo,
            r.fecha,
            r.horario,
            r.cantidad,
            r.total,
            r.estado,
            u.nombre AS usuario,
            a.nombre AS actividad,
            a.lugar_id,
            l.nombre AS lugar
         FROM reservas r

         LEFT JOIN usuarios u
            ON r.usuario_id = u.id

         LEFT JOIN actividades a
            ON r.actividad_id = a.id

         LEFT JOIN lugares l
            ON a.lugar_id = l.id

         ORDER BY r.fecha DESC, r.horario DESC, r.id DESC"
    );

    if ($resultado) {

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $reservas[] = $fila;
        }
    }
}


/* =========================================================
   RESERVAS RECIENTES
   ========================================================= */

$reservasRecientes = array_slice($reservas, 0, 10);


/* =========================================================
   EVENTOS
   ========================================================= */

$eventos = [];

if (tablaExiste($conexion, "eventos")) {

    $resultado = mysqli_query(
        $conexion,
        "SELECT *
         FROM eventos
         ORDER BY fecha ASC"
    );

    if ($resultado) {

        while ($fila = mysqli_fetch_assoc($resultado)) {
            $eventos[] = $fila;
        }
    }
}


/* =========================================================
   NOMBRE DEL ADMIN
   ========================================================= */

$nombreUsuario = "Administrador";

if (isset($_SESSION["usuario"])) {
    $nombreUsuario = $_SESSION["usuario"];
}

if (
    isset($_SESSION["nombre"]) &&
    $_SESSION["nombre"] != ""
) {
    $nombreUsuario = $_SESSION["nombre"];
}


/* =========================================================
   TÍTULO
   ========================================================= */

$tituloSeccion = "Panel general";

if ($seccion == "reservas") {
    $tituloSeccion = "Reservas";
} elseif ($seccion == "acuaticas") {
    $tituloSeccion = "Actividades acuáticas";
} elseif ($seccion == "aventura") {
    $tituloSeccion = "Parque de aventura";
} elseif ($seccion == "recreacion") {
    $tituloSeccion = "Experiencias y recreación";
} elseif ($seccion == "infantil") {
    $tituloSeccion = "Sector infantil";
} elseif ($seccion == "eventos") {
    $tituloSeccion = "Eventos especiales";
} elseif ($seccion == "hospedaje") {
    $tituloSeccion = "Hospedaje";
} elseif ($seccion == "gastronomia") {
    $tituloSeccion = "Gastronomía";
} elseif ($seccion == "emprendedores") {
    $tituloSeccion = "Paseo de emprendedores";
}


/* =========================================================
   FUNCIONES PARA RESERVAS
   ========================================================= */

function mostrarEstado($estado)
{
    $estado = strtolower((string)$estado);

    if ($estado == "confirmada") {
        return "confirmada";
    }

    if ($estado == "pendiente") {
        return "pendiente";
    }

    if ($estado == "cancelada") {
        return "cancelada";
    }

    if ($estado == "finalizada") {
        return "finalizada";
    }

    return "otro";
}


function nombreEstado($estado)
{
    $estado = strtolower((string)$estado);

    if ($estado == "confirmada") {
        return "Confirmada";
    }

    if ($estado == "pendiente") {
        return "Pendiente";
    }

    if ($estado == "cancelada") {
        return "Cancelada";
    }

    if ($estado == "finalizada") {
        return "Finalizada";
    }

    return ucfirst($estado);
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
        Estado del sistema · Laguna Experience
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

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        :root {

            --ink: #174a36;
            --ink-deep: #0d3829;

            --water: #36a66f;
            --water-dark: #247d52;

            --turquoise: #82d8a7;

            --sand: #edf3e8;
            --cream: #f8faf5;

            --white: #ffffff;

            --line: rgba(23, 74, 54, .12);

            --shadow:
                0 24px 70px rgba(13, 56, 41, .12);

            --font-body: "DM Sans", sans-serif;
            --font-display: "Manrope", sans-serif;
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

            font-family: var(--font-body);

            overflow-x: hidden;
        }


        a {
            text-decoration: none;
        }


        /* =====================================================
           ESTRUCTURA
           ===================================================== */

        .layout {
            min-height: 100vh;
            display: flex;
        }


        /* =====================================================
           SIDEBAR
           ===================================================== */

        .sidebar {

            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            width: 260px;

            padding: 28px 18px;

            background:
                rgba(248, 250, 245, .92);

            border-right:
                1px solid var(--line);

            overflow-y: auto;

            z-index: 100;
        }


        .logo {

            padding:
                6px 10px
                25px;

            border-bottom:
                1px solid var(--line);

            margin-bottom: 22px;
        }


        .logo-title {

            color: var(--ink);

            font-family:
                var(--font-display);

            font-size: 20px;

            font-weight: 800;

            letter-spacing: -.04em;
        }


        .logo-title span {
            color: var(--water);
        }


        .logo-subtitle {

            color: #759080;

            font-size: 11px;

            margin-top: 6px;
        }


        .menu-title {

            color: #7a9587;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 1.3px;

            padding:
                10px 10px;

            margin-top: 5px;
        }


        .menu {

            display: flex;

            flex-direction: column;

            gap: 4px;

            margin-bottom: 13px;
        }


        .menu a {

            display: flex;

            align-items: center;

            width: 100%;

            padding:
                11px 12px;

            border-radius: 12px;

            color: #577264;

            font-size: 12px;

            font-weight: 700;

            transition:
                background .2s ease,
                color .2s ease,
                transform .2s ease;
        }


        .menu a:hover {

            background:
                rgba(54, 166, 111, .09);

            color:
                var(--water-dark);

            transform:
                translateX(2px);
        }


        .menu a.active {

            background:
                rgba(54, 166, 111, .13);

            color:
                var(--water-dark);

            border:
                1px solid
                rgba(54, 166, 111, .14);
        }


        .menu a.logout {

            color: #a75b5b;
        }


        .menu a.logout:hover {

            background:
                rgba(190, 75, 75, .08);

            color: #a64545;
        }


        .menu-icon {

            width: 26px;

            color:
                var(--water);

            font-size: 13px;

            font-weight: 800;
        }


        /* =====================================================
           USUARIO
           ===================================================== */

        .user-box {

            margin-top: 24px;

            padding: 14px;

            border-radius: 15px;

            background:
                rgba(54, 166, 111, .07);

            border:
                1px solid
                rgba(54, 166, 111, .14);
        }


        .user-label {

            color: #789184;

            font-size: 9px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 7px;
        }


        .user-name {

            color: var(--ink);

            font-size: 13px;

            font-weight: 700;
        }


        .user-role {

            color:
                var(--water-dark);

            font-size: 10px;

            margin-top: 3px;
        }


        /* =====================================================
           CONTENIDO
           ===================================================== */

        .content {

            margin-left: 260px;

            width:
                calc(100% - 260px);

            min-height: 100vh;

            padding: 38px;
        }


        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 25px;

            margin-bottom: 30px;
        }


        .eyebrow {

            color:
                var(--water-dark);

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 1.5px;

            font-weight: 800;

            margin-bottom: 8px;
        }


        h1 {

            color: var(--ink);

            font-family:
                var(--font-display);

            font-size: 31px;

            line-height: 1.1;

            letter-spacing: -.04em;
        }


        .system-status {

            display: flex;

            align-items: center;

            gap: 9px;

            padding:
                10px 15px;

            border-radius: 999px;

            background:
                rgba(54, 166, 111, .09);

            border:
                1px solid
                rgba(54, 166, 111, .16);

            color:
                var(--water-dark);

            font-size: 11px;

            font-weight: 800;

            white-space: nowrap;
        }


        .status-dot {

            width: 8px;
            height: 8px;

            border-radius: 50%;

            background:
                var(--water);

            box-shadow:
                0 0 0 4px
                rgba(54, 166, 111, .12);
        }


        /* =====================================================
           TARJETAS
           ===================================================== */

        .cards {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;

            margin-bottom: 22px;
        }


        .card {

            background:
                rgba(255,255,255,.72);

            border:
                1px solid
                rgba(23, 74, 54, .10);

            border-radius: 17px;

            padding: 20px;

            box-shadow:
                0 12px 35px
                rgba(13, 56, 41, .06);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 18px 42px
                rgba(13, 56, 41, .09);
        }


        .card-label {

            color: #719080;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: .8px;

            font-weight: 800;

            margin-bottom: 10px;
        }


        .card-number {

            color: var(--ink);

            font-family:
                var(--font-display);

            font-size: 29px;

            font-weight: 800;
        }


        .card-description {

            color: #789184;

            font-size: 10px;

            margin-top: 5px;
        }


        /* =====================================================
           PANELES
           ===================================================== */

        .grid {

            display: grid;

            grid-template-columns:
                1.5fr 1fr;

            gap: 20px;
        }


        .panel {

            background:
                rgba(255,255,255,.68);

            border:
                1px solid
                rgba(23, 74, 54, .10);

            border-radius: 18px;

            overflow: hidden;

            box-shadow:
                0 14px 42px
                rgba(13, 56, 41, .06);
        }


        .panel-header {

            padding:
                20px 22px;

            border-bottom:
                1px solid
                rgba(23, 74, 54, .08);
        }


        .panel-header h2 {

            color: var(--ink);

            font-family:
                var(--font-display);

            font-size: 16px;

            letter-spacing: -.02em;
        }


        .panel-header p {

            color: #789184;

            font-size: 11px;

            margin-top: 5px;
        }


        .panel-body {

            padding: 22px;
        }


        /* =====================================================
           LISTA DE ESTADOS
           ===================================================== */

        .status-list {

            display: flex;

            flex-direction: column;

            gap: 10px;
        }


        .status-item {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 13px;

            border-radius: 13px;

            background:
                rgba(54, 166, 111, .045);

            border:
                1px solid
                rgba(23, 74, 54, .06);
        }


        .status-info {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .status-icon {

            width: 35px;
            height: 35px;

            display: flex;

            justify-content: center;

            align-items: center;

            border-radius: 10px;

            background:
                rgba(54, 166, 111, .10);

            color:
                var(--water-dark);

            font-size: 10px;

            font-weight: 800;
        }


        .status-name {

            color: var(--ink);

            font-size: 12px;

            font-weight: 700;
        }


        .status-detail {

            color: #789184;

            font-size: 10px;

            margin-top: 3px;
        }


        /* =====================================================
           BADGES
           ===================================================== */

        .badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 999px;

            padding:
                6px 10px;

            font-size: 9px;

            font-weight: 800;

            white-space: nowrap;
        }


        .badge.green {

            background:
                rgba(54, 166, 111, .11);

            color:
                var(--water-dark);
        }


        .badge.yellow {

            background:
                rgba(208, 165, 48, .12);

            color:
                #9a7314;
        }


        .badge.red {

            background:
                rgba(190, 75, 75, .10);

            color:
                #a64545;
        }


        .badge.gray {

            background:
                rgba(23, 74, 54, .07);

            color:
                #6d8075;
        }


        /* =====================================================
           ACTIVIDADES
           ===================================================== */

        .activity-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 13px;
        }


        .activity {

            padding: 17px;

            border-radius: 14px;

            background:
                rgba(54, 166, 111, .035);

            border:
                1px solid
                rgba(23, 74, 54, .08);
        }


        .activity-top {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 10px;
        }


        .activity-name {

            color: var(--ink);

            font-family:
                var(--font-display);

            font-size: 13px;

            font-weight: 800;
        }


        .activity-place {

            color: #789184;

            font-size: 10px;

            margin-top: 5px;
        }


        .cupos {

            margin-top: 17px;
        }


        .cupos-text {

            display: flex;

            justify-content: space-between;

            color: #789184;

            font-size: 10px;

            margin-bottom: 7px;
        }


        .cupos-text strong {

            color: var(--ink);
        }


        .bar {

            width: 100%;

            height: 6px;

            border-radius: 999px;

            background:
                rgba(23, 74, 54, .08);

            overflow: hidden;
        }


        .bar-fill {

            height: 100%;

            border-radius: 999px;

            background:
                var(--water);
        }


        .bar-fill.warning {

            background:
                #d5aa35;
        }


        .bar-fill.danger {

            background:
                #c65a5a;
        }


        /* =====================================================
           RESERVAS
           ===================================================== */

        .reservas-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 15px;
        }


        .reserva-card {

            background:
                rgba(255,255,255,.78);

            border:
                1px solid
                rgba(23, 74, 54, .10);

            border-radius: 16px;

            padding: 18px;

            box-shadow:
                0 8px 25px
                rgba(13, 56, 41, .05);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .reserva-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 14px 35px
                rgba(13, 56, 41, .08);
        }


        .reserva-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 12px;

            padding-bottom: 13px;

            margin-bottom: 13px;

            border-bottom:
                1px solid
                rgba(23, 74, 54, .08);
        }


        .reserva-title {

            color: var(--ink);

            font-family:
                var(--font-display);

            font-size: 14px;

            font-weight: 800;
        }


        .reserva-place {

            color:
                var(--water-dark);

            font-size: 10px;

            font-weight: 700;

            margin-top: 4px;
        }


        .reserva-code {

            padding:
                6px 9px;

            border-radius: 8px;

            background:
                rgba(54, 166, 111, .09);

            color:
                var(--water-dark);

            font-size: 9px;

            font-weight: 800;

            white-space: nowrap;
        }


        .reserva-info {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 10px;
        }


        .info-item {

            padding: 9px;

            border-radius: 10px;

            background:
                rgba(23, 74, 54, .035);
        }


        .info-label {

            color: #789184;

            font-size: 9px;

            text-transform: uppercase;

            letter-spacing: .5px;

            margin-bottom: 4px;
        }


        .info-value {

            color: var(--ink);

            font-size: 11px;

            font-weight: 700;

            word-break: break-word;
        }


        .reserva-footer {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 10px;

            margin-top: 13px;

            padding-top: 12px;

            border-top:
                1px solid
                rgba(23, 74, 54, .08);
        }


        .reserva-total {

            color: var(--ink);

            font-family:
                var(--font-display);

            font-size: 15px;

            font-weight: 800;
        }


        .reserva-actions {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-top: 12px;
        }


        .btn-finalizar {

            border: none;

            padding: 9px 13px;

            border-radius: 10px;

            background: var(--water);

            color: white;

            font-family: var(--font-body);

            font-size: 10px;

            font-weight: 800;

            cursor: pointer;

            transition: background .2s ease, transform .2s ease;
        }


        .btn-finalizar:hover {

            background: var(--water-dark);

            transform: translateY(-1px);
        }


        .estado-finalizada {

            color: var(--water-dark);

            font-size: 10px;

            font-weight: 800;
        }


        /* =====================================================
           TÍTULO DE CATEGORÍA
           ===================================================== */

        .category-title {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin:
                25px 0 13px;
        }


        .category-title:first-child {
            margin-top: 0;
        }


        .category-title h3 {

            color: var(--ink);

            font-family:
                var(--font-display);

            font-size: 16px;

            letter-spacing: -.02em;
        }


        .category-title span {

            color: #789184;

            font-size: 10px;
        }


        /* =====================================================
           TABLA
           ===================================================== */

        .table-container {

            width: 100%;

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 750px;
        }


        th {

            text-align: left;

            color: #789184;

            font-size: 9px;

            text-transform: uppercase;

            letter-spacing: .7px;

            font-weight: 800;

            padding:
                12px 14px;

            border-bottom:
                1px solid
                rgba(23, 74, 54, .09);
        }


        td {

            color: #405e50;

            font-size: 11px;

            padding:
                13px 14px;

            border-bottom:
                1px solid
                rgba(23, 74, 54, .06);
        }


        tr:last-child td {
            border-bottom: none;
        }


        tbody tr {

            transition:
                background .15s ease;
        }


        tbody tr:hover {

            background:
                rgba(54, 166, 111, .04);
        }


        .table-code {

            color:
                var(--water-dark);

            font-weight: 800;
        }


        /* =====================================================
           VACÍO
           ===================================================== */

        .empty {

            padding:
                45px 20px;

            text-align: center;

            color: #789184;

            font-size: 12px;
        }


        /* =====================================================
           BOTONES
           ===================================================== */

        .back-home {

            display: block;

            margin-top: 15px;

            padding:
                11px 12px;

            border-radius: 12px;

            color:
                var(--water-dark);

            background:
                rgba(54, 166, 111, .07);

            border:
                1px solid
                rgba(54, 166, 111, .12);

            font-size: 11px;

            font-weight: 800;

            text-align: center;

            transition: .2s ease;
        }


        .back-home:hover {

            background:
                rgba(54, 166, 111, .13);
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1150px) {

            .cards {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .grid {

                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 900px) {

            .sidebar {

                width: 220px;
            }


            .content {

                margin-left: 220px;

                width:
                    calc(100% - 220px);

                padding: 25px;
            }


            .reservas-grid {

                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 650px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

                min-height: auto;
            }


            .layout {

                display: block;
            }


            .content {

                margin-left: 0;

                width: 100%;

                padding: 20px;
            }


            .topbar {

                flex-direction: column;
            }


            .cards {

                grid-template-columns: 1fr;
            }


            .activity-grid {

                grid-template-columns: 1fr;
            }


            .reserva-info {

                grid-template-columns: 1fr;
            }
        }

    </style>

</head>


<body>


<div class="layout">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar">


        <div class="logo">

            <div class="logo-title">
                Laguna <span>Experience</span>
            </div>

            <div class="logo-subtitle">
                Panel de administración
            </div>

        </div>


        <div class="menu-title">
            Principal
        </div>


        <nav class="menu">

            <a
                href="?seccion=inicio"
                class="<?= $seccion == "inicio" ? "active" : "" ?>"
            >

                <span class="menu-icon">
                    01
                </span>

                Panel general

            </a>


            <a
                href="?seccion=reservas"
                class="<?= $seccion == "reservas" ? "active" : "" ?>"
            >

                <span class="menu-icon">
                    02
                </span>

                Reservas

            </a>

        </nav>


        <div class="menu-title">
            Experiencias
        </div>


        <nav class="menu">

            <a
                href="?seccion=acuaticas"
                class="<?= $seccion == "acuaticas" ? "active" : "" ?>"
            >

                <span class="menu-icon">
                    03
                </span>

                Actividades acuáticas

            </a>


            <a
                href="?seccion=aventura"
                class="<?= $seccion == "aventura" ? "active" : "" ?>"
            >

                <span class="menu-icon">
                    04
                </span>

                Parque de aventura

            </a>


            <a
                href="?seccion=recreacion"
                class="<?= $seccion == "recreacion" ? "active" : "" ?>"
            >

                <span class="menu-icon">
                    05
                </span>

                Experiencias y recreación

            </a>


            <a
                href="?seccion=infantil"
                class="<?= $seccion == "infantil" ? "active" : "" ?>"
            >

                <span class="menu-icon">
                    06
                </span>

                Sector infantil

            </a>


            <a
                href="?seccion=eventos"
                class="<?= $seccion == "eventos" ? "active" : "" ?>"
            >

                <span class="menu-icon">
                    07
                </span>

                Eventos especiales

            </a>

        </nav>


        <div class="menu-title">
            Servicios
        </div>


        <nav class="menu">

            <a
                href="?seccion=hospedaje"
                class="<?= $seccion == "hospedaje" ? "active" : "" ?>"
            >

                <span class="menu-icon">
                    08
                </span>

                Cabañas y glamping

            </a>


            <a
                href="?seccion=gastronomia"
                class="<?= $seccion == "gastronomia" ? "active" : "" ?>"
            >

                <span class="menu-icon">
                    09
                </span>

                Gastronomía

            </a>


            <a
                href="?seccion=emprendedores"
                class="<?= $seccion == "emprendedores" ? "active" : "" ?>"
            >

                <span class="menu-icon">
                    10
                </span>

                Emprendedores

            </a>

        </nav>


        <div class="menu-title">
            Sistema
        </div>


        <a
            href="index.php"
            class="back-home"
        >
            Volver al inicio
        </a>


        <a
            href="logout.php"
            class="back-home logout"
        >
            Cerrar sesión
        </a>


        <div class="user-box">

            <div class="user-label">
                Sesión actual
            </div>


            <div class="user-name">
                <?= escapar($nombreUsuario) ?>
            </div>


            <div class="user-role">
                Administrador
            </div>

        </div>


    </aside>



    <!-- =====================================================
         CONTENIDO
         ===================================================== -->

    <main class="content">


        <div class="topbar">


            <div>

                <div class="eyebrow">
                    Laguna Experience · Administración
                </div>


                <h1>
                    <?= escapar($tituloSeccion) ?>
                </h1>

            </div>


            <div class="system-status">

                <span class="status-dot"></span>

                Sistema operativo

            </div>


        </div>



        <!-- =================================================
             PANEL GENERAL
             ================================================= -->

        <?php if ($seccion == "inicio"): ?>


            <div class="cards">


                <div class="card">

                    <div class="card-label">
                        Usuarios
                    </div>

                    <div class="card-number">
                        <?= $totalUsuarios ?>
                    </div>

                    <div class="card-description">
                        Usuarios registrados
                    </div>

                </div>


                <div class="card">

                    <div class="card-label">
                        Reservas
                    </div>

                    <div class="card-number">
                        <?= $totalReservas ?>
                    </div>

                    <div class="card-description">
                        Reservas registradas
                    </div>

                </div>


                <div class="card">

                    <div class="card-label">
                        Reservas hoy
                    </div>

                    <div class="card-number">
                        <?= $reservasHoy ?>
                    </div>

                    <div class="card-description">
                        Reservas para hoy
                    </div>

                </div>


                <div class="card">

                    <div class="card-label">
                        Actividades
                    </div>

                    <div class="card-number">
                        <?= $totalActividades ?>
                    </div>

                    <div class="card-description">
                        Experiencias registradas
                    </div>

                </div>


            </div>



            <div class="grid">


                <!-- ESTADO DE ACTIVIDADES -->

                <div class="panel">


                    <div class="panel-header">

                        <h2>
                            Estado de las experiencias
                        </h2>

                        <p>
                            Disponibilidad actual de cada actividad.
                        </p>

                    </div>


                    <div class="panel-body">


                        <?php if (count($actividades) > 0): ?>


                            <div class="activity-grid">


                                <?php foreach ($actividades as $actividad): ?>


                                    <?php

                                    $capacidad =
                                        (int)$actividad["capacidad"];

                                    $cupos =
                                        (int)$actividad["cupos_disponibles"];

                                    $porcentaje = 0;

                                    if ($capacidad > 0) {
                                        $porcentaje =
                                            ($cupos / $capacidad) * 100;
                                    }


                                    if ($cupos == 0) {

                                        $claseBarra = "danger";
                                        $claseBadge = "red";
                                        $textoEstado = "Completo";

                                    } elseif ($cupos <= 5) {

                                        $claseBarra = "warning";
                                        $claseBadge = "yellow";
                                        $textoEstado = "Pocos cupos";

                                    } else {

                                        $claseBarra = "";
                                        $claseBadge = "green";
                                        $textoEstado = "Disponible";
                                    }

                                    ?>


                                    <div class="activity">


                                        <div class="activity-top">


                                            <div>

                                                <div class="activity-name">

                                                    <?= escapar(
                                                        $actividad["nombre"]
                                                    ) ?>

                                                </div>


                                                <div class="activity-place">

                                                    Horario:
                                                    <?= escapar(
                                                        $actividad["horario"]
                                                        ?? "-"
                                                    ) ?>

                                                </div>

                                            </div>


                                            <span
                                                class="badge <?= $claseBadge ?>"
                                            >
                                                <?= $textoEstado ?>
                                            </span>


                                        </div>


                                        <div class="cupos">


                                            <div class="cupos-text">

                                                <span>
                                                    Cupos disponibles
                                                </span>

                                                <strong>
                                                    <?= $cupos ?>
                                                    /
                                                    <?= $capacidad ?>
                                                </strong>

                                            </div>


                                            <div class="bar">

                                                <div
                                                    class="bar-fill <?= $claseBarra ?>"
                                                    style="width: <?= max(0, min(100, $porcentaje)) ?>%;"
                                                ></div>

                                            </div>


                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            </div>


                        <?php else: ?>


                            <div class="empty">
                                No hay actividades registradas.
                            </div>


                        <?php endif; ?>


                    </div>

                </div>



                <!-- RESUMEN -->

                <div class="panel">


                    <div class="panel-header">

                        <h2>
                            Resumen del sistema
                        </h2>

                        <p>
                            Indicadores principales.
                        </p>

                    </div>


                    <div class="panel-body">


                        <div class="status-list">


                            <div class="status-item">

                                <div class="status-info">

                                    <div class="status-icon">
                                        01
                                    </div>

                                    <div>

                                        <div class="status-name">
                                            Reservas confirmadas
                                        </div>

                                        <div class="status-detail">
                                            Reservas activas
                                        </div>

                                    </div>

                                </div>


                                <span class="badge green">
                                    <?= $reservasConfirmadas ?>
                                </span>

                            </div>



                            <div class="status-item">

                                <div class="status-info">

                                    <div class="status-icon">
                                        02
                                    </div>

                                    <div>

                                        <div class="status-name">
                                            Reservas pendientes
                                        </div>

                                        <div class="status-detail">
                                            Requieren atención
                                        </div>

                                    </div>

                                </div>


                                <span class="badge yellow">
                                    <?= $reservasPendientes ?>
                                </span>

                            </div>



                            <div class="status-item">

                                <div class="status-info">

                                    <div class="status-icon">
                                        03
                                    </div>

                                    <div>

                                        <div class="status-name">
                                            Pocos cupos
                                        </div>

                                        <div class="status-detail">
                                            Hasta 5 cupos disponibles
                                        </div>

                                    </div>

                                </div>


                                <span class="badge yellow">
                                    <?= $actividadesPocosCupos ?>
                                </span>

                            </div>



                            <div class="status-item">

                                <div class="status-info">

                                    <div class="status-icon">
                                        04
                                    </div>

                                    <div>

                                        <div class="status-name">
                                            Actividades completas
                                        </div>

                                        <div class="status-detail">
                                            Sin cupos disponibles
                                        </div>

                                    </div>

                                </div>


                                <span class="badge red">
                                    <?= $actividadesCompletas ?>
                                </span>

                            </div>



                            <div class="status-item">

                                <div class="status-info">

                                    <div class="status-icon">
                                        05
                                    </div>

                                    <div>

                                        <div class="status-name">
                                            Eventos
                                        </div>

                                        <div class="status-detail">
                                            Eventos registrados
                                        </div>

                                    </div>

                                </div>


                                <span class="badge green">
                                    <?= $totalEventos ?>
                                </span>

                            </div>



                            <div class="status-item">

                                <div class="status-info">

                                    <div class="status-icon">
                                        06
                                    </div>

                                    <div>

                                        <div class="status-name">
                                            Hospedajes
                                        </div>

                                        <div class="status-detail">
                                            Alojamientos registrados
                                        </div>

                                    </div>

                                </div>


                                <span class="badge green">
                                    <?= $totalHospedajes ?>
                                </span>

                            </div>


                        </div>

                    </div>

                </div>


            </div>



            <!-- RESERVAS RECIENTES -->

            <div
                class="panel"
                style="margin-top:20px;"
            >


                <div class="panel-header">

                    <h2>
                        Reservas recientes
                    </h2>

                    <p>
                        Últimas reservas registradas en Laguna Experience.
                    </p>

                </div>


                <?php if (count($reservasRecientes) > 0): ?>


                    <div class="reservas-grid" style="padding:20px;">


                        <?php foreach ($reservasRecientes as $reserva): ?>


                            <div class="reserva-card">


                                <div class="reserva-header">


                                    <div>

                                        <div class="reserva-title">

                                            <?= escapar(
                                                $reserva["actividad"]
                                                ?? "Reserva general"
                                            ) ?>

                                        </div>


                                        <div class="reserva-place">

                                            <?= escapar(
                                                $reserva["lugar"]
                                                ?? "Lugar no especificado"
                                            ) ?>

                                        </div>

                                    </div>


                                    <div class="reserva-code">

                                        <?= escapar(
                                            $reserva["codigo"]
                                        ) ?>

                                    </div>


                                </div>


                                <div class="reserva-info">


                                    <div class="info-item">

                                        <div class="info-label">
                                            Usuario
                                        </div>

                                        <div class="info-value">
                                            <?= escapar(
                                                $reserva["usuario"]
                                                ?? "Sin usuario"
                                            ) ?>
                                        </div>

                                    </div>


                                    <div class="info-item">

                                        <div class="info-label">
                                            Fecha
                                        </div>

                                        <div class="info-value">
                                            <?= escapar(
                                                $reserva["fecha"]
                                            ) ?>
                                        </div>

                                    </div>


                                    <div class="info-item">

                                        <div class="info-label">
                                            Horario
                                        </div>

                                        <div class="info-value">
                                            <?= escapar(
                                                $reserva["horario"]
                                                ?? "-"
                                            ) ?>
                                        </div>

                                    </div>


                                    <div class="info-item">

                                        <div class="info-label">
                                            Cantidad
                                        </div>

                                        <div class="info-value">
                                            <?= (int)$reserva["cantidad"] ?>
                                        </div>

                                    </div>


                                </div>


                                <div class="reserva-actions">

                                    <?php if (mostrarEstado($reserva["estado"]) == "confirmada"): ?>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="accion"
                                                value="finalizar_reserva"
                                            >

                                            <input
                                                type="hidden"
                                                name="reserva_id"
                                                value="<?= (int)$reserva["id"] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="seccion"
                                                value="<?= escapar($seccion) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn-finalizar"
                                                onclick="return confirm('¿Confirmar que la actividad fue finalizada?');"
                                            >
                                                Finalizar actividad
                                            </button>

                                        </form>

                                    <?php elseif (mostrarEstado($reserva["estado"]) == "finalizada"): ?>

                                        <span class="estado-finalizada">
                                            ✓ Actividad finalizada
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div class="reserva-footer">


                                    <div class="reserva-total">

                                        $<?= number_format(
                                            (float)$reserva["total"],
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                    </div>


                                    <span
                                        class="badge <?= mostrarEstado($reserva["estado"]) == "confirmada" ? "green" : (mostrarEstado($reserva["estado"]) == "pendiente" ? "yellow" : (mostrarEstado($reserva["estado"]) == "cancelada" ? "red" : "gray")) ?>"
                                    >

                                        <?= escapar(
                                            nombreEstado(
                                                $reserva["estado"]
                                            )
                                        ) ?>

                                    </span>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="empty">
                        No hay reservas registradas.
                    </div>


                <?php endif; ?>


            </div>



        <!-- =================================================
             RESERVAS
             ================================================= -->

        <?php elseif ($seccion == "reservas"): ?>


            <div class="cards">


                <div class="card">

                    <div class="card-label">
                        Total
                    </div>

                    <div class="card-number">
                        <?= $totalReservas ?>
                    </div>

                    <div class="card-description">
                        Reservas registradas
                    </div>

                </div>


                <div class="card">

                    <div class="card-label">
                        Hoy
                    </div>

                    <div class="card-number">
                        <?= $reservasHoy ?>
                    </div>

                    <div class="card-description">
                        Reservas del día
                    </div>

                </div>


                <div class="card">

                    <div class="card-label">
                        Confirmadas
                    </div>

                    <div class="card-number">
                        <?= $reservasConfirmadas ?>
                    </div>

                    <div class="card-description">
                        Reservas activas
                    </div>

                </div>


                <div class="card">

                    <div class="card-label">
                        Pendientes
                    </div>

                    <div class="card-number">
                        <?= $reservasPendientes ?>
                    </div>

                    <div class="card-description">
                        Para revisar
                    </div>

                </div>


            </div>



            <div class="panel">


                <div class="panel-header">

                    <h2>
                        Todas las reservas
                    </h2>

                    <p>
                        Cada reserva muestra su experiencia, lugar y código.
                    </p>

                </div>


                <?php if (count($reservas) > 0): ?>


                    <div class="reservas-grid" style="padding:20px;">


                        <?php foreach ($reservas as $reserva): ?>


                            <div class="reserva-card">


                                <div class="reserva-header">


                                    <div>

                                        <div class="reserva-title">

                                            <?= escapar(
                                                $reserva["actividad"]
                                                ?? "Reserva general"
                                            ) ?>

                                        </div>


                                        <div class="reserva-place">

                                            <?= escapar(
                                                $reserva["lugar"]
                                                ?? "Lugar no especificado"
                                            ) ?>

                                        </div>

                                    </div>


                                    <div class="reserva-code">

                                        <?= escapar(
                                            $reserva["codigo"]
                                        ) ?>

                                    </div>


                                </div>


                                <div class="reserva-info">


                                    <div class="info-item">

                                        <div class="info-label">
                                            Usuario
                                        </div>

                                        <div class="info-value">
                                            <?= escapar(
                                                $reserva["usuario"]
                                                ?? "Sin usuario"
                                            ) ?>
                                        </div>

                                    </div>


                                    <div class="info-item">

                                        <div class="info-label">
                                            Fecha
                                        </div>

                                        <div class="info-value">
                                            <?= escapar(
                                                $reserva["fecha"]
                                            ) ?>
                                        </div>

                                    </div>


                                    <div class="info-item">

                                        <div class="info-label">
                                            Horario
                                        </div>

                                        <div class="info-value">
                                            <?= escapar(
                                                $reserva["horario"]
                                                ?? "-"
                                            ) ?>
                                        </div>

                                    </div>


                                    <div class="info-item">

                                        <div class="info-label">
                                            Cantidad
                                        </div>

                                        <div class="info-value">
                                            <?= (int)$reserva["cantidad"] ?>
                                        </div>

                                    </div>


                                </div>


                                <div class="reserva-actions">

                                    <?php if (mostrarEstado($reserva["estado"]) == "confirmada"): ?>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="accion"
                                                value="finalizar_reserva"
                                            >

                                            <input
                                                type="hidden"
                                                name="reserva_id"
                                                value="<?= (int)$reserva["id"] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="seccion"
                                                value="<?= escapar($seccion) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn-finalizar"
                                                onclick="return confirm('¿Confirmar que la actividad fue finalizada?');"
                                            >
                                                Finalizar actividad
                                            </button>

                                        </form>

                                    <?php elseif (mostrarEstado($reserva["estado"]) == "finalizada"): ?>

                                        <span class="estado-finalizada">
                                            ✓ Actividad finalizada
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div class="reserva-footer">


                                    <div class="reserva-total">

                                        $<?= number_format(
                                            (float)$reserva["total"],
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                    </div>


                                    <span
                                        class="badge <?= mostrarEstado($reserva["estado"]) == "confirmada" ? "green" : (mostrarEstado($reserva["estado"]) == "pendiente" ? "yellow" : (mostrarEstado($reserva["estado"]) == "cancelada" ? "red" : "gray")) ?>"
                                    >

                                        <?= escapar(
                                            nombreEstado(
                                                $reserva["estado"]
                                            )
                                        ) ?>

                                    </span>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="empty">
                        No hay reservas para mostrar.
                    </div>


                <?php endif; ?>


            </div>



        <!-- =================================================
             EXPERIENCIAS
             ================================================= -->

        <?php elseif (
            $seccion == "acuaticas" ||
            $seccion == "aventura" ||
            $seccion == "recreacion" ||
            $seccion == "infantil"
        ): ?>


            <?php

            $actividadesMostrar = [];


            if ($seccion == "acuaticas") {

                foreach ($actividades as $actividad) {

                    if (
                        isset($actividad["lugar_id"]) &&
                        $actividad["lugar_id"] == 2
                    ) {
                        $actividadesMostrar[] = $actividad;
                    }
                }

            } elseif ($seccion == "aventura") {

                foreach ($actividades as $actividad) {

                    if (
                        isset($actividad["lugar_id"]) &&
                        $actividad["lugar_id"] == 3
                    ) {
                        $actividadesMostrar[] = $actividad;
                    }
                }

            } elseif ($seccion == "infantil") {

                foreach ($actividades as $actividad) {

                    if (
                        isset($actividad["lugar_id"]) &&
                        $actividad["lugar_id"] == 11
                    ) {
                        $actividadesMostrar[] = $actividad;
                    }
                }

            } else {

                $actividadesMostrar = $actividades;
            }


            ?>


            <div class="cards">


                <div class="card">

                    <div class="card-label">
                        Actividades
                    </div>

                    <div class="card-number">
                        <?= count($actividadesMostrar) ?>
                    </div>

                    <div class="card-description">
                        Experiencias registradas
                    </div>

                </div>


                <div class="card">

                    <div class="card-label">
                        Cupos
                    </div>

                    <div class="card-number">

                        <?php

                        $totalCupos = 0;

                        foreach ($actividadesMostrar as $actividad) {

                            $totalCupos +=
                                (int)$actividad["cupos_disponibles"];
                        }

                        echo $totalCupos;

                        ?>

                    </div>

                    <div class="card-description">
                        Cupos disponibles
                    </div>

                </div>


                <div class="card">

                    <div class="card-label">
                        Pocos cupos
                    </div>

                    <div class="card-number">

                        <?php

                        $pocos = 0;

                        foreach ($actividadesMostrar as $actividad) {

                            $cupos =
                                (int)$actividad["cupos_disponibles"];

                            if (
                                $cupos > 0 &&
                                $cupos <= 5
                            ) {
                                $pocos++;
                            }
                        }

                        echo $pocos;

                        ?>

                    </div>

                    <div class="card-description">
                        Hasta 5 cupos
                    </div>

                </div>


                <div class="card">

                    <div class="card-label">
                        Completas
                    </div>

                    <div class="card-number">

                        <?php

                        $completas = 0;

                        foreach ($actividadesMostrar as $actividad) {

                            if (
                                (int)$actividad["cupos_disponibles"] == 0 ||
                                ($actividad["estado"] ?? "") == "completo"
                            ) {
                                $completas++;
                            }
                        }

                        echo $completas;

                        ?>

                    </div>

                    <div class="card-description">
                        Sin cupos
                    </div>

                </div>


            </div>



            <!-- ACTIVIDADES -->

            <div class="panel">


                <div class="panel-header">

                    <h2>
                        Actividades disponibles
                    </h2>

                    <p>
                        Nombre, lugar y disponibilidad actual.
                    </p>

                </div>


                <div class="panel-body">


                    <?php if (count($actividadesMostrar) > 0): ?>


                        <div class="activity-grid">


                            <?php foreach ($actividadesMostrar as $actividad): ?>


                                <?php

                                $capacidad =
                                    (int)$actividad["capacidad"];

                                $cupos =
                                    (int)$actividad["cupos_disponibles"];

                                $porcentaje = 0;

                                if ($capacidad > 0) {

                                    $porcentaje =
                                        ($cupos / $capacidad) * 100;
                                }


                                if ($cupos == 0) {

                                    $claseBarra = "danger";
                                    $claseBadge = "red";
                                    $textoEstado = "Completo";

                                } elseif ($cupos <= 5) {

                                    $claseBarra = "warning";
                                    $claseBadge = "yellow";
                                    $textoEstado = "Pocos cupos";

                                } else {

                                    $claseBarra = "";
                                    $claseBadge = "green";
                                    $textoEstado = "Disponible";
                                }

                                ?>


                                <div class="activity">


                                    <div class="activity-top">


                                        <div>

                                            <div class="activity-name">

                                                <?= escapar(
                                                    $actividad["nombre"]
                                                ) ?>

                                            </div>


                                            <div class="activity-place">

                                                Lugar asociado:
                                                <?= escapar(
                                                    $actividad["lugar_id"]
                                                    ?? "-"
                                                ) ?>

                                            </div>


                                            <div class="activity-place">

                                                Edad mínima:
                                                <?= (int)(
                                                    $actividad["edad_minima"]
                                                    ?? 0
                                                ) ?>

                                                ·

                                                <?= escapar(
                                                    $actividad["dificultad"]
                                                    ?? "-"
                                                ) ?>

                                            </div>

                                        </div>


                                        <span
                                            class="badge <?= $claseBadge ?>"
                                        >
                                            <?= $textoEstado ?>
                                        </span>


                                    </div>


                                    <div class="cupos">


                                        <div class="cupos-text">

                                            <span>
                                                Cupos disponibles
                                            </span>

                                            <strong>
                                                <?= $cupos ?>
                                                /
                                                <?= $capacidad ?>
                                            </strong>

                                        </div>


                                        <div class="bar">

                                            <div
                                                class="bar-fill <?= $claseBarra ?>"
                                                style="width: <?= max(0, min(100, $porcentaje)) ?>%;"
                                            ></div>

                                        </div>


                                    </div>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <div class="empty">

                            No hay actividades registradas
                            para esta sección.

                        </div>


                    <?php endif; ?>


                </div>

            </div>



            <!-- RESERVAS DE LA CATEGORÍA -->

            <div
                class="panel"
                style="margin-top:20px;"
            >


                <div class="panel-header">

                    <h2>
                        Reservas de esta experiencia
                    </h2>

                    <p>
                        Reservas asociadas a las actividades mostradas.
                    </p>

                </div>


                <?php


                $reservasCategoria = [];


                foreach ($reservas as $reserva) {

                    foreach ($actividadesMostrar as $actividad) {

                        if (
                            isset($actividad["nombre"]) &&
                            isset($reserva["actividad"]) &&
                            $actividad["nombre"] ==
                            $reserva["actividad"]
                        ) {

                            $reservasCategoria[] =
                                $reserva;

                            break;
                        }
                    }
                }


                ?>


                <?php if (count($reservasCategoria) > 0): ?>


                    <div
                        class="reservas-grid"
                        style="padding:20px;"
                    >


                        <?php foreach ($reservasCategoria as $reserva): ?>


                            <div class="reserva-card">


                                <div class="reserva-header">


                                    <div>

                                        <div class="reserva-title">

                                            <?= escapar(
                                                $reserva["actividad"]
                                            ) ?>

                                        </div>


                                        <div class="reserva-place">

                                            <?= escapar(
                                                $reserva["lugar"]
                                                ?? "Lugar no especificado"
                                            ) ?>

                                        </div>

                                    </div>


                                    <div class="reserva-code">

                                        <?= escapar(
                                            $reserva["codigo"]
                                        ) ?>

                                    </div>


                                </div>


                                <div class="reserva-info">


                                    <div class="info-item">

                                        <div class="info-label">
                                            Usuario
                                        </div>

                                        <div class="info-value">
                                            <?= escapar(
                                                $reserva["usuario"]
                                                ?? "Sin usuario"
                                            ) ?>
                                        </div>

                                    </div>


                                    <div class="info-item">

                                        <div class="info-label">
                                            Fecha
                                        </div>

                                        <div class="info-value">
                                            <?= escapar(
                                                $reserva["fecha"]
                                            ) ?>
                                        </div>

                                    </div>


                                    <div class="info-item">

                                        <div class="info-label">
                                            Horario
                                        </div>

                                        <div class="info-value">
                                            <?= escapar(
                                                $reserva["horario"]
                                                ?? "-"
                                            ) ?>
                                        </div>

                                    </div>


                                    <div class="info-item">

                                        <div class="info-label">
                                            Cantidad
                                        </div>

                                        <div class="info-value">
                                            <?= (int)$reserva["cantidad"] ?>
                                        </div>

                                    </div>


                                </div>


                                <div class="reserva-actions">

                                    <?php if (mostrarEstado($reserva["estado"]) == "confirmada"): ?>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="accion"
                                                value="finalizar_reserva"
                                            >

                                            <input
                                                type="hidden"
                                                name="reserva_id"
                                                value="<?= (int)$reserva["id"] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="seccion"
                                                value="<?= escapar($seccion) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn-finalizar"
                                                onclick="return confirm('¿Confirmar que la actividad fue finalizada?');"
                                            >
                                                Finalizar actividad
                                            </button>

                                        </form>

                                    <?php elseif (mostrarEstado($reserva["estado"]) == "finalizada"): ?>

                                        <span class="estado-finalizada">
                                            ✓ Actividad finalizada
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div class="reserva-footer">


                                    <div class="reserva-total">

                                        $<?= number_format(
                                            (float)$reserva["total"],
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                    </div>


                                    <span
                                        class="badge <?= mostrarEstado($reserva["estado"]) == "confirmada" ? "green" : (mostrarEstado($reserva["estado"]) == "pendiente" ? "yellow" : (mostrarEstado($reserva["estado"]) == "cancelada" ? "red" : "gray")) ?>"
                                    >

                                        <?= escapar(
                                            nombreEstado(
                                                $reserva["estado"]
                                            )
                                        ) ?>

                                    </span>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="empty">

                        No hay reservas para las actividades
                        de esta sección.

                    </div>


                <?php endif; ?>


            </div>



        <!-- =================================================
             EVENTOS
             ================================================= -->

        <?php elseif ($seccion == "eventos"): ?>


            <div class="cards">

                <div class="card">

                    <div class="card-label">
                        Eventos
                    </div>

                    <div class="card-number">
                        <?= $totalEventos ?>
                    </div>

                    <div class="card-description">
                        Eventos registrados
                    </div>

                </div>

            </div>



            <div class="panel">


                <div class="panel-header">

                    <h2>
                        Eventos especiales
                    </h2>

                    <p>
                        Eventos cargados en el sistema.
                    </p>

                </div>


                <div class="table-container">


                    <?php if (count($eventos) > 0): ?>


                        <table>


                            <thead>

                                <tr>

                                    <th>
                                        Evento
                                    </th>

                                    <th>
                                        Fecha
                                    </th>

                                    <th>
                                        Horario
                                    </th>

                                    <th>
                                        Capacidad
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach ($eventos as $evento): ?>


                                    <tr>

                                        <td>

                                            <?= escapar(
                                                $evento["nombre"]
                                                ?? $evento["titulo"]
                                                ?? "Evento"
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= escapar(
                                                $evento["fecha"]
                                                ?? "-"
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= escapar(
                                                $evento["horario"]
                                                ?? $evento["hora"]
                                                ?? "-"
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= escapar(
                                                $evento["capacidad"]
                                                ?? "-"
                                            ) ?>

                                        </td>

                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    <?php else: ?>


                        <div class="empty">
                            No hay eventos registrados.
                        </div>


                    <?php endif; ?>


                </div>


            </div>



        <!-- =================================================
             HOSPEDAJE
             ================================================= -->

        <?php elseif ($seccion == "hospedaje"): ?>


            <div class="cards">


                <div class="card">

                    <div class="card-label">
                        Hospedajes
                    </div>

                    <div class="card-number">
                        <?= $totalHospedajes ?>
                    </div>

                    <div class="card-description">
                        Alojamientos registrados
                    </div>

                </div>


            </div>



            <div class="panel">


                <div class="panel-header">

                    <h2>
                        Hospedaje
                    </h2>

                    <p>
                        Estado de los alojamientos registrados.
                    </p>

                </div>


                <div class="panel-body">


                    <div class="empty">

                        <?= $totalHospedajes > 0
                            ? "Hay " . $totalHospedajes . " alojamientos registrados en el sistema."
                            : "No hay alojamientos registrados."
                        ?>

                    </div>


                </div>


            </div>



        <!-- =================================================
             GASTRONOMÍA
             ================================================= -->

        <?php elseif ($seccion == "gastronomia"): ?>


            <div class="panel">


                <div class="panel-header">

                    <h2>
                        Gastronomía
                    </h2>

                    <p>
                        Estado de los servicios gastronómicos.
                    </p>

                </div>


                <div class="panel-body">


                    <div class="status-list">


                        <div class="status-item">


                            <div class="status-info">

                                <div class="status-icon">
                                    01
                                </div>

                                <div>

                                    <div class="status-name">
                                        Restaurante Panorámico
                                    </div>

                                    <div class="status-detail">
                                        Servicio gastronómico
                                    </div>

                                </div>

                            </div>


                            <span class="badge green">
                                Activo
                            </span>


                        </div>



                        <div class="status-item">


                            <div class="status-info">

                                <div class="status-icon">
                                    02
                                </div>

                                <div>

                                    <div class="status-name">
                                        Beach Club
                                    </div>

                                    <div class="status-detail">
                                        Servicio gastronómico
                                    </div>

                                </div>

                            </div>


                            <span class="badge green">
                                Activo
                            </span>


                        </div>


                    </div>


                </div>


            </div>



        <!-- =================================================
             EMPRENDEDORES
             ================================================= -->

        <?php elseif ($seccion == "emprendedores"): ?>


            <div class="cards">


                <div class="card">

                    <div class="card-label">
                        Emprendimientos
                    </div>

                    <div class="card-number">
                        <?= $totalEmprendimientos ?>
                    </div>

                    <div class="card-description">
                        Locales registrados
                    </div>

                </div>


            </div>



            <div class="panel">


                <div class="panel-header">

                    <h2>
                        Paseo de emprendedores
                    </h2>

                    <p>
                        Emprendimientos registrados en Laguna Experience.
                    </p>

                </div>


                <div class="panel-body">


                    <?php if ($totalEmprendimientos > 0): ?>


                        <div class="status-list">


                            <?php

                            $resultadoEmprendimientos =
                                mysqli_query(
                                    $conexion,
                                    "SELECT *
                                     FROM emprendimientos
                                     LIMIT 20"
                                );


                            if ($resultadoEmprendimientos):


                                while (
                                    $emprendimiento =
                                    mysqli_fetch_assoc(
                                        $resultadoEmprendimientos
                                    )
                                ):

                            ?>


                                <div class="status-item">


                                    <div class="status-info">


                                        <div class="status-icon">
                                            <?= escapar(
                                                str_pad(
                                                    (string)(
                                                        $emprendimiento["id"]
                                                        ?? ""
                                                    ),
                                                    2,
                                                    "0",
                                                    STR_PAD_LEFT
                                                )
                                            ) ?>
                                        </div>


                                        <div>


                                            <div class="status-name">

                                                <?= escapar(
                                                    $emprendimiento["nombre"]
                                                    ?? "Emprendimiento"
                                                ) ?>

                                            </div>


                                            <div class="status-detail">

                                                <?= escapar(
                                                    $emprendimiento["categoria"]
                                                    ?? "Local"
                                                ) ?>

                                            </div>


                                        </div>


                                    </div>


                                    <span class="badge green">
                                        Activo
                                    </span>


                                </div>


                            <?php

                                endwhile;

                            endif;

                            ?>


                        </div>


                    <?php else: ?>


                        <div class="empty">
                            No hay emprendimientos registrados.
                        </div>


                    <?php endif; ?>


                </div>


            </div>


        <?php endif; ?>


    </main>


</div>


</body>

</html>