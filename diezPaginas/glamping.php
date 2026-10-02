<?php

session_start();

require_once "../conexion.php";

$mensaje = "";
$tipoMensaje = "";
$usuarioActual = "";

if (isset($_SESSION["usuario"])) {
    $usuarioActual = $_SESSION["usuario"];
}

/* =========================
   CREAR TABLAS
========================= */

mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS glamping_unidades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        tipo VARCHAR(50) NOT NULL,
        capacidad INT NOT NULL,
        precio DECIMAL(10,2) NOT NULL,
        descripcion TEXT,
        disponible TINYINT(1) DEFAULT 1
    )
");

mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS glamping_reservas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(30) NOT NULL UNIQUE,
        usuario_id INT NULL,
        usuario VARCHAR(100) NOT NULL,
        unidad_id INT NOT NULL,
        unidad VARCHAR(100) NOT NULL,
        fecha_entrada DATE NOT NULL,
        fecha_salida DATE NOT NULL,
        huespedes INT NOT NULL,
        noches INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

/* =========================
   DATOS INICIALES
========================= */

$consultaCantidad = mysqli_query(
    $conexion,
    "SELECT COUNT(*) AS cantidad FROM glamping_unidades"
);

$filaCantidad = mysqli_fetch_assoc($consultaCantidad);

if ($filaCantidad["cantidad"] == 0) {

    mysqli_query($conexion, "
        INSERT INTO glamping_unidades
        (
            nombre,
            tipo,
            capacidad,
            precio,
            descripcion,
            disponible
        )
        VALUES

        (
            'Domo Estrella',
            'Domo',
            2,
            47000,
            'Una experiencia íntima para disfrutar de una estadía rodeada de naturaleza.',
            1
        ),

        (
            'Domo Luna',
            'Domo',
            2,
            47000,
            'Un espacio pensado para descansar y disfrutar del entorno natural.',
            1
        ),

        (
            'Carpa Safari Río',
            'Carpa Safari',
            4,
            61000,
            'Una alternativa amplia para compartir la experiencia con familia o amigos.',
            1
        ),

        (
            'Carpa Safari Bosque',
            'Carpa Safari',
            4,
            61000,
            'Una carpa de lujo ubicada en un entorno natural de bosque.',
            0
        ),

        (
            'Domo Panorámico',
            'Domo',
            3,
            55000,
            'Una unidad para disfrutar de una estadía con una vista privilegiada.',
            1
        )
    ");
}

/* =========================
   LOGIN
========================= */

if (
    isset($_POST["identificar"]) &&
    isset($_POST["usuario"]) &&
    isset($_POST["password"])
) {

    $usuario = trim($_POST["usuario"]);
    $password = trim($_POST["password"]);

    if ($usuario == "" || $password == "") {

        $mensaje = "Ingresá tu usuario y contraseña para continuar.";
        $tipoMensaje = "err";

    } else {

        $usuarioEscapado = mysqli_real_escape_string(
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
                nombre = '$usuarioEscapado'
                OR email = '$usuarioEscapado'
            )
            LIMIT 1
            "
        );

        if (mysqli_num_rows($consultaUsuario) == 1) {

            $datosUsuario = mysqli_fetch_assoc($consultaUsuario);

            $passwordCorrecta = false;

            if (
                isset($datosUsuario["password_hash"]) &&
                password_verify(
                    $password,
                    $datosUsuario["password_hash"]
                )
            ) {
                $passwordCorrecta = true;
            }

            if (
                isset($datosUsuario["password"]) &&
                $password === $datosUsuario["password"]
            ) {
                $passwordCorrecta = true;
            }

            if (
                isset($datosUsuario["password_hash"]) &&
                $password === $datosUsuario["password_hash"]
            ) {
                $passwordCorrecta = true;
            }

            if ($passwordCorrecta) {

                $_SESSION["usuario_id"] = $datosUsuario["id"];
                $_SESSION["usuario"] = $datosUsuario["nombre"];
                $_SESSION["email"] = $datosUsuario["email"];

                if (isset($datosUsuario["rol"])) {
                    $_SESSION["rol"] = $datosUsuario["rol"];
                }

                $usuarioActual = $datosUsuario["nombre"];

                $mensaje = "Inicio de sesión correcto. Ya podés reservar.";
                $tipoMensaje = "ok";

            } else {

                $mensaje = "Usuario o contraseña incorrectos.";
                $tipoMensaje = "err";
            }

        } else {

            $mensaje = "Usuario o contraseña incorrectos.";
            $tipoMensaje = "err";
        }
    }
}

/* =========================
   CERRAR SESIÓN
========================= */

if (isset($_GET["cerrar"])) {

    session_unset();
    session_destroy();

    header("Location: glamping.php");
    exit;
}

/* =========================
   CREAR RESERVA
========================= */

if (isset($_POST["reservar"])) {

    if (!isset($_SESSION["usuario_id"])) {

        $mensaje = "Primero iniciá sesión con tu usuario y contraseña.";
        $tipoMensaje = "err";

    } else {

        $usuarioId = intval($_SESSION["usuario_id"]);
        $usuario = $_SESSION["usuario"];

        $unidadId = intval($_POST["unidad"]);

        $fechaEntrada = $_POST["fecha_entrada"];
        $fechaSalida = $_POST["fecha_salida"];

        $huespedes = intval($_POST["huespedes"]);

        $consultaUnidad = mysqli_query(
            $conexion,
            "
            SELECT *
            FROM glamping_unidades
            WHERE id = $unidadId
            AND disponible = 1
            LIMIT 1
            "
        );

        if (mysqli_num_rows($consultaUnidad) == 0) {

            $mensaje = "La unidad seleccionada no está disponible.";
            $tipoMensaje = "err";

        } else {

            $unidad = mysqli_fetch_assoc($consultaUnidad);

            if (
                $fechaEntrada == "" ||
                $fechaSalida == ""
            ) {

                $mensaje = "Seleccioná las fechas de entrada y salida.";
                $tipoMensaje = "err";

            } elseif (
                $fechaEntrada < date("Y-m-d")
            ) {

                $mensaje = "La fecha de entrada no puede ser anterior a hoy.";
                $tipoMensaje = "err";

            } elseif (
                $fechaSalida <= $fechaEntrada
            ) {

                $mensaje = "La fecha de salida debe ser posterior a la fecha de entrada.";
                $tipoMensaje = "err";

            } elseif (
                $huespedes < 1 ||
                $huespedes > $unidad["capacidad"]
            ) {

                $mensaje =
                    "La capacidad máxima de " .
                    $unidad["nombre"] .
                    " es de " .
                    $unidad["capacidad"] .
                    " huéspedes.";

                $tipoMensaje = "err";

            } else {

                $entrada = new DateTime($fechaEntrada);
                $salida = new DateTime($fechaSalida);

                $diferencia = $entrada->diff($salida);

                $noches = $diferencia->days;

                $entradaEscapada = mysqli_real_escape_string(
                    $conexion,
                    $fechaEntrada
                );

                $salidaEscapada = mysqli_real_escape_string(
                    $conexion,
                    $fechaSalida
                );

                $consultaDisponibilidad = mysqli_query(
                    $conexion,
                    "
                    SELECT id
                    FROM glamping_reservas
                    WHERE unidad_id = $unidadId
                    AND fecha_entrada < '$salidaEscapada'
                    AND fecha_salida > '$entradaEscapada'
                    LIMIT 1
                    "
                );

                if (
                    mysqli_num_rows($consultaDisponibilidad) > 0
                ) {

                    $mensaje = "La unidad ya está reservada para esas fechas.";
                    $tipoMensaje = "err";

                } else {

                    $total = $noches * $unidad["precio"];

                    do {

                        $codigo =
                            "GLP-" .
                            rand(10000, 99999);

                        $codigoEscapado =
                            mysqli_real_escape_string(
                                $conexion,
                                $codigo
                            );

                        $consultaCodigo = mysqli_query(
                            $conexion,
                            "
                            SELECT id
                            FROM glamping_reservas
                            WHERE codigo = '$codigoEscapado'
                            "
                        );

                    } while (
                        mysqli_num_rows($consultaCodigo) > 0
                    );

                    $usuarioEscapado =
                        mysqli_real_escape_string(
                            $conexion,
                            $usuario
                        );

                    $nombreUnidadEscapado =
                        mysqli_real_escape_string(
                            $conexion,
                            $unidad["nombre"]
                        );

                    $guardar = mysqli_query(
                        $conexion,
                        "
                        INSERT INTO glamping_reservas
                        (
                            codigo,
                            usuario_id,
                            usuario,
                            unidad_id,
                            unidad,
                            fecha_entrada,
                            fecha_salida,
                            huespedes,
                            noches,
                            total
                        )
                        VALUES
                        (
                            '$codigoEscapado',
                            $usuarioId,
                            '$usuarioEscapado',
                            $unidadId,
                            '$nombreUnidadEscapado',
                            '$fechaEntrada',
                            '$fechaSalida',
                            $huespedes,
                            $noches,
                            $total
                        )
                        "
                    );

                    if ($guardar) {

                        $_SESSION["ultima_reserva_glamping"] = [
                            "codigo" => $codigo,
                            "unidad" => $unidad["nombre"],
                            "entrada" => $fechaEntrada,
                            "salida" => $fechaSalida,
                            "huespedes" => $huespedes,
                            "noches" => $noches,
                            "total" => $total
                        ];

                        $mensaje = "Reserva registrada correctamente.";
                        $tipoMensaje = "ok";

                    } else {

                        $mensaje = "Ocurrió un error al guardar la reserva.";
                        $tipoMensaje = "err";
                    }
                }
            }
        }
    }
}

/* =========================
   OBTENER UNIDADES
========================= */

$consultaUnidades = mysqli_query(
    $conexion,
    "
    SELECT *
    FROM glamping_unidades
    ORDER BY id ASC
    "
);

$unidades = [];

while ($fila = mysqli_fetch_assoc($consultaUnidades)) {

    $unidades[] = $fila;
}

/* =========================
   ÚLTIMA RESERVA
========================= */

$ultimaReserva = null;

if (isset($_SESSION["ultima_reserva_glamping"])) {

    $ultimaReserva =
        $_SESSION["ultima_reserva_glamping"];
}

$cantidadUnidades = count($unidades);

$cantidadDisponibles = 0;

$capacidadTotal = 0;

foreach ($unidades as $unidad) {

    if ($unidad["disponible"] == 1) {
        $cantidadDisponibles++;
    }

    $capacidadTotal += intval($unidad["capacidad"]);
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
        Glamping | Laguna Experience
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
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap"
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
            --font-body: "DM Sans", sans-serif;
            --font-display: "Manrope", sans-serif;
            --shadow:
                0 24px 70px
                rgba(20, 60, 65, .12);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--paper);
            color: #21444a;
            font-family: var(--font-body);
        }

        a {
            color: inherit;
        }

        button,
        input,
        select {
            font-family: inherit;
        }

        /* =========================
           HEADER
        ========================= */

        header {
            position: relative;
            min-height: 510px;
            padding: 80px 8%;
            display: flex;
            align-items: center;
            overflow: hidden;
            color: white;

            background:
                linear-gradient(
                    90deg,
                    rgba(10, 94, 108, .94),
                    rgba(14, 135, 154, .72)
                ),
                url("../img/glamping.jpg");

            background-size: cover;
            background-position: center;
        }

        header::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
            right: -130px;
            top: -120px;
        }

        header::after {
            content: "";
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(255,255,255,.07);
            right: 15%;
            bottom: -170px;
        }

        .header-content {
            position: relative;
            z-index: 2;
            max-width: 760px;
        }

        .eyebrow {
            margin: 0 0 18px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 13px;
            font-weight: 800;
            opacity: .9;
        }

        header h1 {
            margin: 0;
            font-family: var(--font-display);
            font-size: clamp(48px, 7vw, 88px);
            line-height: .98;
            letter-spacing: -3px;
        }

        header .sub {
            max-width: 670px;
            margin: 28px 0 0;
            font-size: 18px;
            line-height: 1.7;
            color: rgba(255,255,255,.92);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 20px;
            margin-top: 26px;
            border-radius: 14px;
            background: white;
            color: var(--turq-deep);
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow:
                0 12px 30px
                rgba(0,0,0,.15);
        }

        /* =========================
           MAIN
        ========================= */

        main {
            max-width: 1250px;
            margin: -45px auto 80px;
            padding: 0 5%;
            position: relative;
            z-index: 3;
        }

        .hero-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 38px;
            box-shadow: var(--shadow);
        }

        .hero-card h2 {
            margin: 0 0 12px;
            font-family: var(--font-display);
            font-size: 30px;
            color: var(--turq-deep);
        }

        .hero-card p {
            margin: 0;
            max-width: 800px;
            line-height: 1.7;
            color: #60777b;
        }

        /* =========================
           STATS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 18px;
            margin-top: 24px;
        }

        .stat {
            background: white;
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 26px;
        }

        .stat strong {
            display: block;
            font-family: var(--font-display);
            color: var(--turq-deep);
            font-size: 32px;
        }

        .stat span {
            display: block;
            margin-top: 5px;
            color: #718589;
            font-size: 14px;
        }

        /* =========================
           SECTIONS
        ========================= */

        .section {
            margin-top: 70px;
        }

        .section-title {
            margin-bottom: 28px;
        }

        .section-title span {
            display: block;
            margin-bottom: 8px;
            color: var(--turq);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 12px;
            font-weight: 800;
        }

        .section-title h2 {
            margin: 0;
            font-family: var(--font-display);
            font-size: 36px;
            color: var(--turq-deep);
        }

        .section-title p {
            max-width: 700px;
            margin: 12px 0 0;
            color: #718589;
            line-height: 1.7;
        }

        /* =========================
           INFO
        ========================= */

        .info-grid {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 20px;
        }

        .info-card {
            padding: 28px;
            border-radius: 24px;
            background: var(--turq-light);
            border: 1px solid rgba(14,135,154,.12);
        }

        .info-card h3 {
            margin: 0 0 10px;
            font-family: var(--font-display);
            color: var(--turq-deep);
        }

        .info-card p {
            margin: 0;
            color: #587177;
            line-height: 1.65;
        }

        /* =========================
           UNIDADES
        ========================= */

        .cards {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 22px;
        }

        .unit-card {
            overflow: hidden;
            background: white;
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow:
                0 12px 35px
                rgba(20,60,65,.07);
            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }

        .unit-card:hover {
            transform: translateY(-5px);
            box-shadow:
                0 20px 45px
                rgba(20,60,65,.12);
        }

        .unit-top {
            padding: 28px;
            background:
                linear-gradient(
                    135deg,
                    var(--turq-light),
                    white
                );
        }

        .unit-type {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 999px;
            background: white;
            color: var(--turq-deep);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .unit-top h3 {
            margin: 18px 0 8px;
            font-family: var(--font-display);
            font-size: 24px;
            color: var(--turq-deep);
        }

        .unit-description {
            min-height: 75px;
            color: #60777b;
            line-height: 1.6;
            font-size: 14px;
        }

        .unit-body {
            padding: 24px 28px 28px;
        }

        .unit-details {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 22px;
        }

        .detail strong {
            display: block;
            color: var(--turq-deep);
            font-size: 18px;
        }

        .detail span {
            color: #87999c;
            font-size: 12px;
        }

        .price {
            margin-bottom: 20px;
        }

        .price strong {
            color: var(--coral);
            font-family: var(--font-display);
            font-size: 25px;
        }

        .price span {
            color: #87999c;
            font-size: 13px;
        }

        .unavailable {
            display: inline-flex;
            padding: 9px 13px;
            border-radius: 999px;
            background: var(--coral-light);
            color: var(--coral);
            font-size: 12px;
            font-weight: 800;
        }

        .available {
            display: inline-flex;
            padding: 9px 13px;
            border-radius: 999px;
            background: #e4f4ea;
            color: var(--ok);
            font-size: 12px;
            font-weight: 800;
        }

        /* =========================
           RESERVA
        ========================= */

        .reservation {
            background: white;
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 36px;
            box-shadow: var(--shadow);
        }

        .reservation h2 {
            margin: 0 0 8px;
            font-family: var(--font-display);
            color: var(--turq-deep);
            font-size: 32px;
        }

        .reservation > p {
            margin: 0 0 28px;
            color: #718589;
        }

        .form-grid {
            display: grid;
            grid-template-columns:
                repeat(2, 1fr);
            gap: 18px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            color: var(--turq-deep);
            font-size: 13px;
            font-weight: 800;
        }

        .field input,
        .field select {
            width: 100%;
            min-height: 50px;
            padding: 0 15px;
            border: 1px solid var(--line);
            border-radius: 13px;
            background: #fffdf9;
            color: #36575d;
            outline: none;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--turq);
            box-shadow:
                0 0 0 3px
                rgba(14,135,154,.10);
        }

        .primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
            padding: 0 24px;
            border: 0;
            border-radius: 14px;
            background: var(--turq-deep);
            color: white;
            font-weight: 800;
            cursor: pointer;
            transition:
                transform .25s ease,
                background .25s ease;
        }

        .primary:hover {
            background: var(--turq);
            transform: translateY(-2px);
        }

        /* =========================
           LOGIN
        ========================= */

        .login-box {
            margin-top: 28px;
            padding: 28px;
            border-radius: 22px;
            background: var(--sand);
            border: 1px solid var(--line);
        }

        .login-box h3 {
            margin: 0 0 8px;
            font-family: var(--font-display);
            color: var(--turq-deep);
        }

        .login-box p {
            margin: 0 0 22px;
            color: #718589;
            line-height: 1.6;
        }

        .login-actions {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .logged-user {
            padding: 15px 18px;
            border-radius: 14px;
            background: var(--turq-light);
            color: var(--turq-deep);
            font-size: 14px;
            font-weight: 700;
        }

        .logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 45px;
            padding: 0 18px;
            border-radius: 13px;
            background: var(--coral-light);
            color: var(--coral);
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
        }

        /* =========================
           MENSAJES
        ========================= */

        .message {
            margin-bottom: 24px;
            padding: 17px 20px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 700;
        }

        .message.ok {
            background: #e4f4ea;
            color: var(--ok);
            border: 1px solid #cce8d6;
        }

        .message.err {
            background: var(--coral-light);
            color: var(--coral);
            border: 1px solid #f2cbbd;
        }

        /* =========================
           CONFIRMACIÓN
        ========================= */

        .confirmation {
            margin-top: 30px;
            padding: 30px;
            border-radius: 24px;
            background:
                linear-gradient(
                    135deg,
                    var(--turq-light),
                    white
                );
            border: 1px solid rgba(14,135,154,.15);
        }

        .confirmation h3 {
            margin: 0 0 18px;
            color: var(--turq-deep);
            font-family: var(--font-display);
            font-size: 25px;
        }

        .confirmation-grid {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 15px;
        }

        .confirmation-item {
            padding: 15px;
            border-radius: 14px;
            background: rgba(255,255,255,.8);
        }

        .confirmation-item span {
            display: block;
            color: #7b8e91;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: .6px;
            margin-bottom: 5px;
        }

        .confirmation-item strong {
            color: var(--turq-deep);
        }

        /* =========================
           ANTES DE IR
        ========================= */

        .before-grid {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 20px;
        }

        .before-card {
            padding: 26px;
            border-radius: 22px;
            background: white;
            border: 1px solid var(--line);
        }

        .before-card h3 {
            margin: 0 0 10px;
            color: var(--turq-deep);
            font-family: var(--font-display);
        }

        .before-card p {
            margin: 0;
            color: #718589;
            line-height: 1.65;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            margin-top: 80px;
            padding: 35px 5%;
            background: var(--turq-deep);
            color: rgba(255,255,255,.8);
            text-align: center;
        }

        footer strong {
            color: white;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .cards,
            .info-grid,
            .before-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 750px) {

            header {
                min-height: 470px;
                padding: 65px 7%;
            }

            header h1 {
                font-size: 55px;
            }

            main {
                padding: 0 4%;
            }

            .stats,
            .cards,
            .info-grid,
            .before-grid,
            .confirmation-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }

            .reservation,
            .hero-card {
                padding: 25px;
            }

        }

        @media (max-width: 450px) {

            header {
                padding: 55px 6%;
            }

            header h1 {
                font-size: 45px;
                letter-spacing: -2px;
            }

            header .sub {
                font-size: 16px;
            }

            main {
                margin-top: -30px;
            }

            .section {
                margin-top: 55px;
            }

            .section-title h2 {
                font-size: 29px;
            }

        }

    </style>

</head>

<body>

<header>

    <div class="header-content">

        <p class="eyebrow">
            Laguna Experience · Alojamiento
        </p>

        <h1>
            Glamping
        </h1>

        <p class="sub">
            Viví una estadía diferente rodeado de naturaleza,
            comodidad y una experiencia diseñada para desconectarte
            de la rutina.
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

    <?php if ($mensaje != ""): ?>

        <div class="message <?php echo $tipoMensaje; ?>">

            <?php echo htmlspecialchars($mensaje); ?>

        </div>

    <?php endif; ?>


    <div class="hero-card">

        <h2>
            Una forma diferente de hospedarte
        </h2>

        <p>
            En Laguna Experience podés disfrutar de una estadía
            rodeada de naturaleza sin dejar de lado la comodidad.
            Elegí entre domos y carpas safari y preparate para
            vivir una experiencia diferente.
        </p>

    </div>


    <div class="stats">

        <div class="stat">

            <strong>
                <?php echo $cantidadUnidades; ?>
            </strong>

            <span>
                Unidades disponibles en el complejo
            </span>

        </div>

        <div class="stat">

            <strong>
                <?php echo $cantidadDisponibles; ?>
            </strong>

            <span>
                Unidades habilitadas para reservar
            </span>

        </div>

        <div class="stat">

            <strong>
                <?php echo $capacidadTotal; ?>
            </strong>

            <span>
                Huéspedes de capacidad total
            </span>

        </div>

    </div>


    <section class="section">

        <div class="section-title">

            <span>
                La experiencia
            </span>

            <h2>
                Todo preparado para tu estadía
            </h2>

            <p>
                El glamping combina el contacto con la naturaleza
                con espacios preparados para que puedas descansar
                y disfrutar de Laguna Experience.
            </p>

        </div>


        <div class="info-grid">

            <div class="info-card">

                <h3>
                    Naturaleza
                </h3>

                <p>
                    Disfrutá de un entorno natural pensado para
                    desconectarte de la rutina y descansar.
                </p>

            </div>


            <div class="info-card">

                <h3>
                    Comodidad
                </h3>

                <p>
                    Las unidades están preparadas para ofrecer
                    una estadía cómoda y diferente.
                </p>

            </div>


            <div class="info-card">

                <h3>
                    Experiencia
                </h3>

                <p>
                    Combiná alojamiento, actividades y espacios
                    recreativos dentro de Laguna Experience.
                </p>

            </div>

        </div>

    </section>


    <section class="section">

        <div class="section-title">

            <span>
                Alojamiento
            </span>

            <h2>
                Elegí tu experiencia
            </h2>

            <p>
                Conocé las unidades disponibles y elegí la que
                mejor se adapte a tu estadía.
            </p>

        </div>


        <div class="cards">

            <?php foreach ($unidades as $unidad): ?>

                <div class="unit-card">

                    <div class="unit-top">

                        <span class="unit-type">

                            <?php
                            echo htmlspecialchars(
                                $unidad["tipo"]
                            );
                            ?>

                        </span>

                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $unidad["nombre"]
                            );
                            ?>

                        </h3>

                        <p class="unit-description">

                            <?php
                            echo htmlspecialchars(
                                $unidad["descripcion"]
                            );
                            ?>

                        </p>

                    </div>


                    <div class="unit-body">

                        <div class="unit-details">

                            <div class="detail">

                                <strong>
                                    <?php
                                    echo $unidad["capacidad"];
                                    ?>
                                </strong>

                                <span>
                                    huéspedes
                                </span>

                            </div>


                            <div class="detail">

                                <strong>
                                    <?php
                                    echo $unidad["disponible"]
                                        ? "Disponible"
                                        : "No disponible";
                                    ?>
                                </strong>

                                <span>
                                    estado
                                </span>

                            </div>

                        </div>


                        <div class="price">

                            <strong>
                                $
                                <?php
                                echo number_format(
                                    $unidad["precio"],
                                    0,
                                    ",",
                                    "."
                                );
                                ?>
                            </strong>

                            <span>
                                por noche
                            </span>

                        </div>


                        <?php if ($unidad["disponible"] == 1): ?>

                            <span class="available">
                                Disponible para reservar
                            </span>

                        <?php else: ?>

                            <span class="unavailable">
                                No disponible actualmente
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <section
        class="section"
        id="reserva"
    >

        <div class="reservation">

            <h2>
                Reservá tu estadía
            </h2>

            <p>
                Seleccioná tu unidad, las fechas de tu estadía
                y la cantidad de huéspedes.
            </p>


            <?php if (isset($_SESSION["usuario_id"])): ?>

                <div class="login-box">

                    <h3>
                        Sesión iniciada
                    </h3>

                    <p>
                        Estás reservando como
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $_SESSION["usuario"]
                            );
                            ?>
                        </strong>.
                    </p>

                    <div class="login-actions">

                        <div class="logged-user">

                            Usuario:
                            <?php
                            echo htmlspecialchars(
                                $_SESSION["usuario"]
                            );
                            ?>

                        </div>

                        <a
                            href="glamping.php?cerrar=1"
                            class="logout"
                        >
                            Cerrar sesión
                        </a>

                    </div>

                </div>


                <form
                    method="POST"
                    class="form-grid"
                    style="margin-top: 28px;"
                >

                    <div class="field full">

                        <label for="unidad">
                            Unidad
                        </label>

                        <select
                            name="unidad"
                            id="unidad"
                            required
                        >

                            <option value="">
                                Seleccioná una unidad
                            </option>

                            <?php foreach ($unidades as $unidad): ?>

                                <?php if ($unidad["disponible"] == 1): ?>

                                    <option
                                        value="<?php echo $unidad["id"]; ?>"
                                        data-capacidad="<?php echo $unidad["capacidad"]; ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $unidad["nombre"]
                                        );
                                        ?>

                                        -
                                        $
                                        <?php
                                        echo number_format(
                                            $unidad["precio"],
                                            0,
                                            ",",
                                            "."
                                        );
                                        ?>

                                        por noche

                                    </option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="field">

                        <label for="fecha_entrada">
                            Fecha de entrada
                        </label>

                        <input
                            type="date"
                            name="fecha_entrada"
                            id="fecha_entrada"
                            required
                        >

                    </div>


                    <div class="field">

                        <label for="fecha_salida">
                            Fecha de salida
                        </label>

                        <input
                            type="date"
                            name="fecha_salida"
                            id="fecha_salida"
                            required
                        >

                    </div>


                    <div class="field">

                        <label for="huespedes">
                            Cantidad de huéspedes
                        </label>

                        <input
                            type="number"
                            name="huespedes"
                            id="huespedes"
                            min="1"
                            max="4"
                            value="1"
                            required
                        >

                    </div>


                    <div class="field">

                        <label>
                            Información
                        </label>

                        <div
                            style="
                                min-height:50px;
                                display:flex;
                                align-items:center;
                                color:#718589;
                                font-size:13px;
                            "
                            id="capacidadTexto"
                        >
                            Seleccioná una unidad para consultar
                            su capacidad.
                        </div>

                    </div>


                    <div class="field full">

                        <button
                            type="submit"
                            name="reservar"
                            class="primary"
                        >
                            Confirmar reserva
                        </button>

                    </div>

                </form>


            <?php else: ?>


                <div class="login-box">

                    <h3>
                        Iniciá sesión para reservar
                    </h3>

                    <p>
                        Para confirmar una estadía necesitás
                        ingresar con tu usuario o email y contraseña.
                    </p>


                    <form
                        method="POST"
                        class="form-grid"
                    >

                        <div class="field">

                            <label for="usuario">
                                Usuario o email
                            </label>

                            <input
                                type="text"
                                name="usuario"
                                id="usuario"
                                placeholder="Ingresá tu usuario o email"
                                required
                            >

                        </div>


                        <div class="field">

                            <label for="password">
                                Contraseña
                            </label>

                            <input
                                type="password"
                                name="password"
                                id="password"
                                placeholder="Ingresá tu contraseña"
                                required
                            >

                        </div>


                        <div class="field full">

                            <button
                                type="submit"
                                name="identificar"
                                class="primary"
                            >
                                Iniciar sesión
                            </button>

                        </div>

                    </form>

                </div>

            <?php endif; ?>


            <?php if ($ultimaReserva != null): ?>

                <div class="confirmation">

                    <h3>
                        Reserva confirmada
                    </h3>


                    <div class="confirmation-grid">

                        <div class="confirmation-item">

                            <span>
                                Código
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $ultimaReserva["codigo"]
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="confirmation-item">

                            <span>
                                Unidad
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $ultimaReserva["unidad"]
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="confirmation-item">

                            <span>
                                Huéspedes
                            </span>

                            <strong>
                                <?php
                                echo $ultimaReserva["huespedes"];
                                ?>
                            </strong>

                        </div>


                        <div class="confirmation-item">

                            <span>
                                Entrada
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $ultimaReserva["entrada"]
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="confirmation-item">

                            <span>
                                Salida
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $ultimaReserva["salida"]
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="confirmation-item">

                            <span>
                                Total
                            </span>

                            <strong>
                                $
                                <?php
                                echo number_format(
                                    $ultimaReserva["total"],
                                    0,
                                    ",",
                                    "."
                                );
                                ?>
                            </strong>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <section class="section">

        <div class="section-title">

            <span>
                Antes de tu llegada
            </span>

            <h2>
                Prepará tu estadía
            </h2>

        </div>


        <div class="before-grid">

            <div class="before-card">

                <h3>
                    Fechas
                </h3>

                <p>
                    La fecha de salida debe ser posterior
                    a la fecha de entrada y la llegada no
                    puede ser anterior al día actual.
                </p>

            </div>


            <div class="before-card">

                <h3>
                    Capacidad
                </h3>

                <p>
                    Cada unidad tiene una capacidad máxima
                    diferente. Seleccioná la cantidad correcta
                    de huéspedes al realizar la reserva.
                </p>

            </div>


            <div class="before-card">

                <h3>
                    Reserva
                </h3>

                <p>
                    Una vez confirmada tu estadía recibirás
                    un código de reserva para identificarla.
                </p>

            </div>

        </div>

    </section>

</main>


<footer>

    <strong>
        Laguna Experience
    </strong>

    <br>

    Smart Experience Park

</footer>


<script>

    const fechaEntrada =
        document.getElementById("fecha_entrada");

    const fechaSalida =
        document.getElementById("fecha_salida");

    const unidad =
        document.getElementById("unidad");

    const huespedes =
        document.getElementById("huespedes");

    const capacidadTexto =
        document.getElementById("capacidadTexto");


    const hoy =
        new Date().toISOString().split("T")[0];


    if (fechaEntrada) {

        fechaEntrada.min = hoy;

        fechaEntrada.addEventListener(
            "change",
            function () {

                if (fechaSalida) {

                    fechaSalida.min =
                        fechaEntrada.value;

                    if (
                        fechaSalida.value &&
                        fechaSalida.value <= fechaEntrada.value
                    ) {

                        fechaSalida.value = "";
                    }
                }

            }
        );
    }


    if (unidad) {

        unidad.addEventListener(
            "change",
            function () {

                const opcion =
                    unidad.options[
                        unidad.selectedIndex
                    ];

                const capacidad =
                    opcion.getAttribute(
                        "data-capacidad"
                    );

                if (capacidad) {

                    huespedes.max = capacidad;

                    if (
                        parseInt(huespedes.value) >
                        parseInt(capacidad)
                    ) {

                        huespedes.value = capacidad;
                    }

                    capacidadTexto.textContent =
                        "Esta unidad tiene capacidad para " +
                        capacidad +
                        " huésped" +
                        (
                            capacidad == 1
                                ? ""
                                : "es"
                        ) +
                        ".";

                } else {

                    huespedes.max = 4;

                    capacidadTexto.textContent =
                        "Seleccioná una unidad para consultar su capacidad.";
                }

            }
        );
    }

</script>

</body>

</html>