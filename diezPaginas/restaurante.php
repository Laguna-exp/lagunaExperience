<?php

session_start();

require_once "../conexion.php";

/* =========================
   CERRAR SESIÓN
========================= */

if (isset($_GET["cerrar"]) && $_GET["cerrar"] == "1") {

    $_SESSION = array();

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: restaurante.php");

    exit;
}

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
    CREATE TABLE IF NOT EXISTS restaurante_mesas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        capacidad INT NOT NULL,
        activo TINYINT(1) DEFAULT 1
    )
");

mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS restaurante_productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        categoria VARCHAR(50) NOT NULL,
        precio DECIMAL(10,2) NOT NULL,
        descripcion TEXT,
        activo TINYINT(1) DEFAULT 1
    )
");

mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS restaurante_reservas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(30) NOT NULL UNIQUE,
        usuario VARCHAR(100) NOT NULL,
        mesa_id INT NOT NULL,
        comensales INT NOT NULL,
        fecha DATE NOT NULL,
        hora TIME NOT NULL,
        estado VARCHAR(30) DEFAULT 'confirmada',
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mesa_id)
        REFERENCES restaurante_mesas(id)
    )
");

/* =========================
   DATOS INICIALES
========================= */

$consultaCantidad = mysqli_query(
    $conexion,
    "SELECT COUNT(*) AS cantidad FROM restaurante_mesas"
);

$filaCantidad = mysqli_fetch_assoc($consultaCantidad);

if ($filaCantidad["cantidad"] == 0) {

    mysqli_query($conexion, "
        INSERT INTO restaurante_mesas
        (nombre, capacidad, activo)
        VALUES
        ('Mesa 1', 2, 1),
        ('Mesa 2', 2, 1),
        ('Mesa 3', 4, 1),
        ('Mesa 4', 4, 1),
        ('Mesa 5', 6, 1),
        ('Mesa 6 - Terraza', 8, 1)
    ");
}

$consultaProductosCantidad = mysqli_query(
    $conexion,
    "SELECT COUNT(*) AS cantidad FROM restaurante_productos"
);

$filaProductosCantidad = mysqli_fetch_assoc(
    $consultaProductosCantidad
);

if ($filaProductosCantidad["cantidad"] == 0) {

    mysqli_query($conexion, "
        INSERT INTO restaurante_productos
        (nombre, categoria, precio, descripcion, activo)
        VALUES
        (
            'Tabla de quesos',
            'Entradas',
            9800,
            'Selección de quesos para compartir.',
            1
        ),
        (
            'Trucha a la parrilla',
            'Principales',
            18500,
            'Trucha preparada a la parrilla.',
            1
        ),
        (
            'Risotto de hongos',
            'Principales',
            15200,
            'Risotto cremoso con hongos.',
            1
        ),
        (
            'Volcán de chocolate',
            'Postres',
            6800,
            'Postre de chocolate con centro fundido.',
            1
        ),
        (
            'Copa de vino Malbec',
            'Bebidas',
            5600,
            'Copa de vino Malbec.',
            1
        ),
        (
            'Agua saborizada',
            'Bebidas',
            2200,
            'Bebida fresca saborizada.',
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

            $datosUsuario = mysqli_fetch_assoc(
                $consultaUsuario
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

                $usuarioActual =
                    $datosUsuario["nombre"];

                $mensaje =
                    "Inicio de sesión correcto. Ya podés reservar tu mesa.";

                $tipoMensaje = "ok";

            } else {

                $mensaje =
                    "Usuario o contraseña incorrectos.";

                $tipoMensaje = "err";
            }

        } else {

            $mensaje =
                "Usuario o contraseña incorrectos.";

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

    header("Location: restaurante-panoramico.php");
    exit;
}

/* =========================
   CREAR RESERVA
========================= */

if (isset($_POST["reservar"])) {

    if (!isset($_SESSION["usuario_id"])) {

        $mensaje =
            "Primero iniciá sesión con tu usuario y contraseña.";

        $tipoMensaje = "err";

    } else {

        $usuarioId = intval($_SESSION["usuario_id"]);
        $usuario = $_SESSION["usuario"];

        $mesaId = intval($_POST["mesa_id"]);
        $comensales = intval($_POST["comensales"]);
        $fecha = $_POST["fecha"];
        $hora = $_POST["hora"];

        if (
            $mesaId <= 0 ||
            $comensales <= 0 ||
            $fecha == "" ||
            $hora == ""
        ) {

            $mensaje =
                "Completá todos los datos para reservar la mesa.";

            $tipoMensaje = "err";

        } else {

            $consultaMesa = mysqli_query(
                $conexion,
                "
                SELECT *
                FROM restaurante_mesas
                WHERE id = $mesaId
                AND activo = 1
                LIMIT 1
                "
            );

            if (mysqli_num_rows($consultaMesa) == 0) {

                $mensaje =
                    "La mesa seleccionada no está disponible.";

                $tipoMensaje = "err";

            } else {

                $mesa = mysqli_fetch_assoc($consultaMesa);

                if ($fecha < date("Y-m-d")) {

                    $mensaje =
                        "La fecha no puede ser anterior a hoy.";

                    $tipoMensaje = "err";

                } elseif ($comensales < 1) {

                    $mensaje =
                        "La cantidad de personas debe ser mayor a cero.";

                    $tipoMensaje = "err";

                } elseif (
                    $comensales > intval($mesa["capacidad"])
                ) {

                    $mensaje =
                        "La mesa seleccionada tiene capacidad para " .
                        $mesa["capacidad"] .
                        " personas.";

                    $tipoMensaje = "err";

                } elseif (
                    $hora < "11:00" ||
                    $hora > "21:00"
                ) {

                    $mensaje =
                        "El horario para reservar una mesa es de 11:00 a 21:00.";

                    $tipoMensaje = "err";

                } else {

                    $fechaEscapada =
                        mysqli_real_escape_string(
                            $conexion,
                            $fecha
                        );

                    $horaEscapada =
                        mysqli_real_escape_string(
                            $conexion,
                            $hora
                        );

                    /*
                        Se considera una reserva de dos horas.
                        Por eso se controlan reservas dentro
                        de ese período.
                    */

                    $consultaDisponibilidad = mysqli_query(
                        $conexion,
                        "
                        SELECT id
                        FROM restaurante_reservas
                        WHERE mesa_id = $mesaId
                        AND fecha = '$fechaEscapada'
                        AND estado = 'confirmada'
                        AND hora < ADDTIME('$horaEscapada', '02:00:00')
                        AND ADDTIME(hora, '02:00:00') > '$horaEscapada'
                        LIMIT 1
                        "
                    );

                    if (
                        mysqli_num_rows(
                            $consultaDisponibilidad
                        ) > 0
                    ) {

                        $mensaje =
                            "La mesa ya está reservada para ese horario.";

                        $tipoMensaje = "err";

                    } else {

                        /*
                            Control general para evitar
                            superar la capacidad del restaurante.
                        */

                        $consultaCapacidad = mysqli_query(
                            $conexion,
                            "
                            SELECT
                                COALESCE(
                                    SUM(comensales),
                                    0
                                ) AS ocupados
                            FROM restaurante_reservas
                            WHERE fecha = '$fechaEscapada'
                            AND estado = 'confirmada'
                            AND hora < ADDTIME('$horaEscapada', '02:00:00')
                            AND ADDTIME(hora, '02:00:00') > '$horaEscapada'
                            "
                        );

                        $datosCapacidad =
                            mysqli_fetch_assoc(
                                $consultaCapacidad
                            );

                        $ocupados =
                            intval(
                                $datosCapacidad["ocupados"]
                            );

                        if (
                            $ocupados + $comensales > 60
                        ) {

                            $mensaje =
                                "No hay capacidad suficiente para ese horario.";

                            $tipoMensaje = "err";

                        } else {

                            do {

                                $codigo =
                                    "REST-" .
                                    date("ymd") .
                                    rand(100, 999);

                                $codigoEscapado =
                                    mysqli_real_escape_string(
                                        $conexion,
                                        $codigo
                                    );

                                $consultaCodigo =
                                    mysqli_query(
                                        $conexion,
                                        "
                                        SELECT id
                                        FROM restaurante_reservas
                                        WHERE codigo = '$codigoEscapado'
                                        "
                                    );

                            } while (
                                mysqli_num_rows(
                                    $consultaCodigo
                                ) > 0
                            );

                            $usuarioEscapado =
                                mysqli_real_escape_string(
                                    $conexion,
                                    $usuario
                                );

                            $guardar =
                                mysqli_query(
                                    $conexion,
                                    "
                                    INSERT INTO restaurante_reservas
                                    (
                                        codigo,
                                        usuario,
                                        mesa_id,
                                        comensales,
                                        fecha,
                                        hora,
                                        estado
                                    )
                                    VALUES
                                    (
                                        '$codigoEscapado',
                                        '$usuarioEscapado',
                                        $mesaId,
                                        $comensales,
                                        '$fechaEscapada',
                                        '$horaEscapada',
                                        'confirmada'
                                    )
                                    "
                                );

                            if ($guardar) {

                                $_SESSION[
                                    "ultima_reserva_restaurante"
                                ] = [

                                    "codigo" =>
                                        $codigo,

                                    "mesa" =>
                                        $mesa["nombre"],

                                    "comensales" =>
                                        $comensales,

                                    "fecha" =>
                                        $fecha,

                                    "hora" =>
                                        $hora
                                ];

                                $mensaje =
                                    "Mesa reservada correctamente.";

                                $tipoMensaje = "ok";

                            } else {

                                $mensaje =
                                    "Ocurrió un error al guardar la reserva.";

                                $tipoMensaje = "err";
                            }
                        }
                    }
                }
            }
        }
    }
}

/* =========================
   OBTENER MESAS
========================= */

$consultaMesas = mysqli_query(
    $conexion,
    "
    SELECT *
    FROM restaurante_mesas
    ORDER BY capacidad ASC, id ASC
    "
);

$mesas = [];

while ($fila = mysqli_fetch_assoc($consultaMesas)) {

    $mesas[] = $fila;
}

/* =========================
   OBTENER PRODUCTOS
========================= */

$consultaProductos = mysqli_query(
    $conexion,
    "
    SELECT *
    FROM restaurante_productos
    WHERE activo = 1
    ORDER BY categoria, id
    "
);

$productos = [];

while ($fila = mysqli_fetch_assoc($consultaProductos)) {

    $productos[] = $fila;
}

/* =========================
   ÚLTIMA RESERVA
========================= */

$ultimaReserva = null;

if (
    isset(
        $_SESSION["ultima_reserva_restaurante"]
    )
) {

    $ultimaReserva =
        $_SESSION[
            "ultima_reserva_restaurante"
        ];
}

/* =========================
   ESTADÍSTICAS
========================= */

$cantidadMesas = count($mesas);

$capacidadTotal = 0;

$mesasDisponibles = 0;

foreach ($mesas as $mesa) {

    if ($mesa["activo"] == 1) {

        $mesasDisponibles++;
    }

    $capacidadTotal +=
        intval($mesa["capacidad"]);
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
        Restaurante Panorámico | Laguna Experience
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

        :root{
            --ink:#2b1c1a;
            --ink-soft:#6b524c;
            --sand:#f6ece2;
            --paper:#fffbf6;
            --wine:#7a2b2b;
            --wine-deep:#521a1a;
            --wine-light:#f3e0dd;
            --gold:#b5872b;
            --gold-light:#f7ecd4;
            --coral:#c2482c;
            --coral-light:#f9ddd2;
            --green:#4c9a6a;
            --green-light:#dff3e6;
            --line:#ecdcd0;
            --radius:18px;
            --shadow:0 8px 25px rgba(50,20,15,.08);
        }

        *{
            box-sizing:border-box;
        }

        html{
            scroll-behavior:smooth;
        }

        body{
            margin:0;
            background:var(--paper);
            color:var(--ink);
            font-family:"DM Sans",sans-serif;
        }

        a{
            color:inherit;
        }

        button,
        input,
        select{
            font-family:inherit;
        }

        /* HEADER */

        header{
            position:relative;
            min-height:520px;
            padding:80px 8%;
            display:flex;
            align-items:center;
            overflow:hidden;
            color:white;

            background:
                linear-gradient(
                    90deg,
                    rgba(82,26,26,.94),
                    rgba(122,43,43,.70)
                ),
                url("../img/restaurante-panoramico.jpg");

            background-size:cover;
            background-position:center;
        }

        header::before{
            content:"";
            position:absolute;
            width:430px;
            height:430px;
            border-radius:50%;
            background:rgba(255,255,255,.08);
            right:-130px;
            top:-130px;
        }

        header::after{
            content:"";
            position:absolute;
            width:270px;
            height:270px;
            border-radius:50%;
            background:rgba(255,255,255,.07);
            right:16%;
            bottom:-180px;
        }

        .header-content{
            position:relative;
            z-index:2;
            max-width:780px;
        }

        .eyebrow{
            margin:0 0 18px;
            text-transform:uppercase;
            letter-spacing:2px;
            font-size:13px;
            font-weight:800;
            opacity:.9;
        }

        header h1{
            margin:0;
            font-family:"Manrope",sans-serif;
            font-size:clamp(48px,7vw,86px);
            line-height:.98;
            letter-spacing:-3px;
        }

        header .sub{
            max-width:700px;
            margin:28px 0 0;
            font-size:18px;
            line-height:1.7;
            color:rgba(255,255,255,.92);
        }

        .back-button{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:48px;
            padding:0 20px;
            margin-top:26px;
            border-radius:14px;
            background:white;
            color:var(--wine-deep);
            text-decoration:none;
            font-size:14px;
            font-weight:800;
            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }

        .back-button:hover{
            transform:translateY(-2px);
            box-shadow:
                0 12px 30px
                rgba(0,0,0,.15);
        }

        /* MAIN */

        main{
            max-width:1250px;
            margin:-45px auto 80px;
            padding:0 5%;
            position:relative;
            z-index:3;
        }

        .hero-card{
            background:white;
            border:1px solid var(--line);
            border-radius:28px;
            padding:38px;
            box-shadow:var(--shadow);
        }

        .hero-card h2{
            margin:0 0 12px;
            font-family:"Manrope",sans-serif;
            font-size:30px;
            color:var(--wine-deep);
        }

        .hero-card p{
            margin:0;
            max-width:850px;
            line-height:1.7;
            color:var(--ink-soft);
        }

        /* MENSAJES */

        .message{
            margin-bottom:24px;
            padding:17px 20px;
            border-radius:15px;
            font-size:14px;
            font-weight:700;
        }

        .message.ok{
            background:var(--green-light);
            color:var(--green);
            border:1px solid #cce8d6;
        }

        .message.err{
            background:var(--coral-light);
            color:var(--coral);
            border:1px solid #f2cbbd;
        }

        /* STATS */

        .stats{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:18px;
            margin-top:24px;
        }

        .stat{
            background:white;
            border:1px solid var(--line);
            border-radius:22px;
            padding:26px;
        }

        .stat strong{
            display:block;
            font-family:"Manrope",sans-serif;
            color:var(--wine-deep);
            font-size:32px;
        }

        .stat span{
            display:block;
            margin-top:5px;
            color:var(--ink-soft);
            font-size:14px;
        }

        /* SECTIONS */

        .section{
            margin-top:70px;
        }

        .section-title{
            margin-bottom:28px;
        }

        .section-title span{
            display:block;
            margin-bottom:8px;
            color:var(--gold);
            text-transform:uppercase;
            letter-spacing:1.5px;
            font-size:12px;
            font-weight:800;
        }

        .section-title h2{
            margin:0;
            font-family:"Manrope",sans-serif;
            font-size:36px;
            color:var(--wine-deep);
        }

        .section-title p{
            max-width:720px;
            margin:12px 0 0;
            color:var(--ink-soft);
            line-height:1.7;
        }

        /* INFO */

        .info-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:20px;
        }

        .info-card{
            padding:28px;
            border-radius:24px;
            background:var(--wine-light);
            border:1px solid rgba(122,43,43,.12);
        }

        .info-card h3{
            margin:0 0 10px;
            font-family:"Manrope",sans-serif;
            color:var(--wine-deep);
        }

        .info-card p{
            margin:0;
            color:var(--ink-soft);
            line-height:1.65;
        }

        /* MESAS */

        .cards{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:22px;
        }

        .table-card{
            overflow:hidden;
            background:white;
            border:1px solid var(--line);
            border-radius:24px;
            box-shadow:
                0 12px 35px
                rgba(50,20,15,.07);
            transition:
                transform .25s ease,
                box-shadow .25s ease;
        }

        .table-card:hover{
            transform:translateY(-5px);
            box-shadow:
                0 20px 45px
                rgba(50,20,15,.12);
        }

        .table-top{
            padding:28px;
            background:
                linear-gradient(
                    135deg,
                    var(--wine-light),
                    white
                );
        }

        .table-type{
            display:inline-block;
            padding:7px 11px;
            border-radius:999px;
            background:white;
            color:var(--wine-deep);
            font-size:11px;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.7px;
        }

        .table-top h3{
            margin:18px 0 8px;
            font-family:"Manrope",sans-serif;
            font-size:24px;
            color:var(--wine-deep);
        }

        .table-description{
            min-height:55px;
            color:var(--ink-soft);
            line-height:1.6;
            font-size:14px;
        }

        .table-body{
            padding:24px 28px 28px;
        }

        .table-details{
            display:flex;
            justify-content:space-between;
            gap:15px;
        }

        .detail strong{
            display:block;
            color:var(--wine-deep);
            font-size:18px;
        }

        .detail span{
            color:#8c7770;
            font-size:12px;
        }

        .available{
            display:inline-flex;
            margin-top:20px;
            padding:9px 13px;
            border-radius:999px;
            background:var(--green-light);
            color:var(--green);
            font-size:12px;
            font-weight:800;
        }

        /* GASTRONOMÍA */

        .food-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:20px;
        }

        .food-card{
            background:white;
            border:1px solid var(--line);
            border-radius:22px;
            padding:25px;
        }

        .food-category{
            display:inline-block;
            padding:6px 10px;
            border-radius:999px;
            background:var(--gold-light);
            color:var(--gold);
            font-size:11px;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.7px;
        }

        .food-card h3{
            margin:17px 0 9px;
            font-family:"Manrope",sans-serif;
            color:var(--wine-deep);
        }

        .food-card p{
            margin:0;
            color:var(--ink-soft);
            line-height:1.6;
            font-size:14px;
        }

        /* RESERVA */

        .reservation{
            background:white;
            border:1px solid var(--line);
            border-radius:28px;
            padding:36px;
            box-shadow:var(--shadow);
        }

        .reservation h2{
            margin:0 0 8px;
            font-family:"Manrope",sans-serif;
            color:var(--wine-deep);
            font-size:32px;
        }

        .reservation > p{
            margin:0 0 28px;
            color:var(--ink-soft);
        }

        .form-grid{
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:18px;
        }

        .field{
            display:flex;
            flex-direction:column;
            gap:8px;
        }

        .field.full{
            grid-column:1 / -1;
        }

        .field label{
            color:var(--wine-deep);
            font-size:13px;
            font-weight:800;
        }

        .field input,
        .field select{
            width:100%;
            min-height:50px;
            padding:0 15px;
            border:1px solid var(--line);
            border-radius:13px;
            background:#fffdf9;
            color:#4b3834;
            outline:none;
        }

        .field input:focus,
        .field select:focus{
            border-color:var(--gold);
            box-shadow:
                0 0 0 3px
                rgba(181,135,43,.10);
        }

        .primary{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:50px;
            padding:0 24px;
            border:0;
            border-radius:14px;
            background:var(--wine-deep);
            color:white;
            font-weight:800;
            cursor:pointer;
            transition:
                transform .25s ease,
                background .25s ease;
        }

        .primary:hover{
            background:var(--wine);
            transform:translateY(-2px);
        }

        /* LOGIN */

        .login-box{
            margin-top:28px;
            padding:28px;
            border-radius:22px;
            background:var(--sand);
            border:1px solid var(--line);
        }

        .login-box h3{
            margin:0 0 8px;
            font-family:"Manrope",sans-serif;
            color:var(--wine-deep);
        }

        .login-box p{
            margin:0 0 22px;
            color:var(--ink-soft);
            line-height:1.6;
        }

        .login-actions{
            display:flex;
            align-items:center;
            gap:15px;
            flex-wrap:wrap;
        }

        .logged-user{
            padding:15px 18px;
            border-radius:14px;
            background:var(--wine-light);
            color:var(--wine-deep);
            font-size:14px;
            font-weight:700;
        }

        .logout{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:45px;
            padding:0 18px;
            border-radius:13px;
            background:var(--coral-light);
            color:var(--coral);
            text-decoration:none;
            font-size:13px;
            font-weight:800;
        }

        /* CONFIRMACIÓN */

        .confirmation{
            margin-top:30px;
            padding:30px;
            border-radius:24px;
            background:
                linear-gradient(
                    135deg,
                    var(--wine-light),
                    white
                );
            border:1px solid rgba(122,43,43,.15);
        }

        .confirmation h3{
            margin:0 0 18px;
            color:var(--wine-deep);
            font-family:"Manrope",sans-serif;
            font-size:25px;
        }

        .confirmation-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:15px;
        }

        .confirmation-item{
            padding:15px;
            border-radius:14px;
            background:rgba(255,255,255,.8);
        }

        .confirmation-item span{
            display:block;
            color:#8c7770;
            font-size:11px;
            text-transform:uppercase;
            font-weight:800;
            letter-spacing:.6px;
            margin-bottom:5px;
        }

        .confirmation-item strong{
            color:var(--wine-deep);
        }

        /* ANTES DE VENIR */

        .before-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:20px;
        }

        .before-card{
            padding:26px;
            border-radius:22px;
            background:white;
            border:1px solid var(--line);
        }

        .before-card h3{
            margin:0 0 10px;
            color:var(--wine-deep);
            font-family:"Manrope",sans-serif;
        }

        .before-card p{
            margin:0;
            color:var(--ink-soft);
            line-height:1.65;
        }

        /* FOOTER */

        footer{
            margin-top:80px;
            padding:35px 5%;
            background:var(--wine-deep);
            color:rgba(255,255,255,.8);
            text-align:center;
        }

        footer strong{
            color:white;
        }

        /* RESPONSIVE */

        @media(max-width:1000px){

            .cards,
            .info-grid,
            .food-grid,
            .before-grid{
                grid-template-columns:repeat(2,1fr);
            }
        }

        @media(max-width:750px){

            header{
                min-height:470px;
                padding:65px 7%;
            }

            header h1{
                font-size:55px;
            }

            main{
                padding:0 4%;
            }

            .stats,
            .cards,
            .info-grid,
            .food-grid,
            .before-grid,
            .confirmation-grid{
                grid-template-columns:1fr;
            }

            .form-grid{
                grid-template-columns:1fr;
            }

            .field.full{
                grid-column:auto;
            }

            .reservation,
            .hero-card{
                padding:25px;
            }
        }

        @media(max-width:450px){

            header{
                padding:55px 6%;
            }

            header h1{
                font-size:45px;
                letter-spacing:-2px;
            }

            header .sub{
                font-size:16px;
            }

            main{
                margin-top:-30px;
            }

            .section{
                margin-top:55px;
            }

            .section-title h2{
                font-size:29px;
            }
        }

    </style>

</head>

<body>

<header>

    <div class="header-content">

        <p class="eyebrow">
            Laguna Experience · Gastronomía
        </p>

        <h1>
            Restaurante Panorámico
        </h1>

        <p class="sub">
            Disfrutá de una propuesta gastronómica con vista
            a la laguna y reservá tu mesa para compartir
            el momento.
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

            <?php
            echo htmlspecialchars($mensaje);
            ?>

        </div>

    <?php endif; ?>


    <div class="hero-card">

        <h2>
            Una mesa con vista a la laguna
        </h2>

        <p>
            El Restaurante Panorámico es un espacio pensado
            para disfrutar de una experiencia gastronómica
            dentro de Laguna Experience. Elegí la fecha,
            el horario y la mesa que mejor se adapte a tu grupo.
        </p>

    </div>


    <div class="stats">

        <div class="stat">

            <strong>
                <?php echo $cantidadMesas; ?>
            </strong>

            <span>
                Mesas disponibles en el restaurante
            </span>

        </div>


        <div class="stat">

            <strong>
                <?php echo $mesasDisponibles; ?>
            </strong>

            <span>
                Mesas habilitadas para reservar
            </span>

        </div>


        <div class="stat">

            <strong>
                <?php echo $capacidadTotal; ?>
            </strong>

            <span>
                Personas de capacidad total
            </span>

        </div>

    </div>


    <!-- EXPERIENCIA -->

    <section class="section">

        <div class="section-title">

            <span>
                La experiencia
            </span>

            <h2>
                Un espacio para disfrutar
            </h2>

            <p>
                El restaurante combina gastronomía, comodidad
                y una ubicación privilegiada dentro del parque.
            </p>

        </div>


        <div class="info-grid">

            <div class="info-card">

                <h3>
                    Vista panorámica
                </h3>

                <p>
                    Disfrutá del entorno de la laguna mientras
                    compartís una comida en un espacio pensado
                    para disfrutar con tranquilidad.
                </p>

            </div>


            <div class="info-card">

                <h3>
                    Mesas para grupos
                </h3>

                <p>
                    Contamos con diferentes capacidades para
                    que puedas elegir una mesa adecuada para
                    la cantidad de personas de tu grupo.
                </p>

            </div>


            <div class="info-card">

                <h3>
                    Reserva previa
                </h3>

                <p>
                    Podés reservar tu mesa con anticipación
                    seleccionando la fecha y el horario de
                    tu visita.
                </p>

            </div>

        </div>

    </section>


    <!-- MESAS -->

    <section class="section">

        <div class="section-title">

            <span>
                Nuestro espacio
            </span>

            <h2>
                Elegí según tu grupo
            </h2>

            <p>
                Las mesas tienen diferentes capacidades para
                adaptarse a grupos pequeños y grandes.
            </p>

        </div>


        <div class="cards">

            <?php foreach ($mesas as $mesa): ?>

                <?php if ($mesa["activo"] == 1): ?>

                    <div class="table-card">

                        <div class="table-top">

                            <span class="table-type">
                                Mesa
                            </span>

                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $mesa["nombre"]
                                );
                                ?>
                            </h3>

                            <p class="table-description">
                                Espacio disponible para disfrutar
                                de la propuesta gastronómica
                                del Restaurante Panorámico.
                            </p>

                        </div>


                        <div class="table-body">

                            <div class="table-details">

                                <div class="detail">

                                    <strong>
                                        <?php
                                        echo $mesa["capacidad"];
                                        ?>
                                    </strong>

                                    <span>
                                        personas
                                    </span>

                                </div>


                                <div class="detail">

                                    <strong>
                                        Disponible
                                    </strong>

                                    <span>
                                        estado
                                    </span>

                                </div>

                            </div>


                            <span class="available">
                                Habilitada para reservar
                            </span>

                        </div>

                    </div>

                <?php endif; ?>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- PROPUESTA GASTRONÓMICA -->

    <section class="section">

        <div class="section-title">

            <span>
                Gastronomía
            </span>

            <h2>
                Una propuesta para compartir
            </h2>

            <p>
                Conocé algunos de los platos y bebidas que
                forman parte de la propuesta del restaurante.
                La reserva corresponde únicamente a la mesa.
            </p>

        </div>


        <div class="food-grid">

            <?php foreach ($productos as $producto): ?>

                <div class="food-card">

                    <span class="food-category">

                        <?php
                        echo htmlspecialchars(
                            $producto["categoria"]
                        );
                        ?>

                    </span>

                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $producto["nombre"]
                        );
                        ?>

                    </h3>

                    <p>

                        <?php
                        echo htmlspecialchars(
                            $producto["descripcion"]
                        );
                        ?>

                    </p>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <!-- RESERVA -->

    <section
        class="section"
        id="reserva"
    >

        <div class="reservation">

            <h2>
                Reservá tu mesa
            </h2>

            <p>
                Elegí la mesa, la cantidad de personas,
                la fecha y el horario de tu visita.
            </p>


            <?php if (isset($_SESSION["usuario_id"])): ?>

                <div class="login-box">

                    <h3>
                        Sesión iniciada
                    </h3>

                    <p>
                        Estás realizando la reserva como
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
                            href="restaurante-panoramico.php?cerrar=1"
                            class="logout"
                        >
                            Cerrar sesión
                        </a>

                    </div>

                </div>


                <form
                    method="POST"
                    class="form-grid"
                    style="margin-top:28px;"
                >

                    <div class="field full">

                        <label for="mesa_id">
                            Mesa
                        </label>

                        <select
                            name="mesa_id"
                            id="mesa_id"
                            required
                        >

                            <option value="">
                                Seleccioná una mesa
                            </option>

                            <?php foreach ($mesas as $mesa): ?>

                                <?php
                                if ($mesa["activo"] == 1):
                                ?>

                                    <option
                                        value="<?php echo $mesa["id"]; ?>"
                                        data-capacidad="<?php echo $mesa["capacidad"]; ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $mesa["nombre"]
                                        );
                                        ?>

                                        -
                                        capacidad para

                                        <?php
                                        echo $mesa["capacidad"];
                                        ?>

                                        personas

                                    </option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="field">

                        <label for="comensales">
                            Cantidad de personas
                        </label>

                        <input
                            type="number"
                            name="comensales"
                            id="comensales"
                            min="1"
                            max="8"
                            value="1"
                            required
                        >

                    </div>


                    <div class="field">

                        <label>
                            Capacidad de la mesa
                        </label>

                        <div
                            id="capacidadTexto"
                            style="
                                min-height:50px;
                                display:flex;
                                align-items:center;
                                color:#6b524c;
                                font-size:13px;
                            "
                        >
                            Seleccioná una mesa para consultar
                            su capacidad.
                        </div>

                    </div>


                    <div class="field">

                        <label for="fecha">
                            Fecha
                        </label>

                        <input
                            type="date"
                            name="fecha"
                            id="fecha"
                            required
                        >

                    </div>


                    <div class="field">

                        <label for="hora">
                            Horario
                        </label>

                        <select
                            name="hora"
                            id="hora"
                            required
                        >

                            <option value="">
                                Seleccioná un horario
                            </option>

                            <option value="11:00">
                                11:00
                            </option>

                            <option value="12:00">
                                12:00
                            </option>

                            <option value="13:00">
                                13:00
                            </option>

                            <option value="14:00">
                                14:00
                            </option>

                            <option value="15:00">
                                15:00
                            </option>

                            <option value="16:00">
                                16:00
                            </option>

                            <option value="17:00">
                                17:00
                            </option>

                            <option value="18:00">
                                18:00
                            </option>

                            <option value="19:00">
                                19:00
                            </option>

                            <option value="20:00">
                                20:00
                            </option>

                            <option value="21:00">
                                21:00
                            </option>

                        </select>

                    </div>


                    <div class="field full">

                        <button
                            type="submit"
                            name="reservar"
                            class="primary"
                        >
                            Confirmar reserva de mesa
                        </button>

                    </div>

                </form>


            <?php else: ?>


                <div class="login-box">

                    <h3>
                        Iniciá sesión para reservar
                    </h3>

                    <p>
                        Para confirmar una reserva de mesa
                        necesitás ingresar con tu usuario o
                        email y contraseña.
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
                        Reserva de mesa confirmada
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
                                Mesa
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $ultimaReserva["mesa"]
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="confirmation-item">

                            <span>
                                Personas
                            </span>

                            <strong>
                                <?php
                                echo $ultimaReserva[
                                    "comensales"
                                ];
                                ?>
                            </strong>

                        </div>


                        <div class="confirmation-item">

                            <span>
                                Fecha
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $ultimaReserva["fecha"]
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="confirmation-item">

                            <span>
                                Horario
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $ultimaReserva["hora"]
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="confirmation-item">

                            <span>
                                Usuario
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $_SESSION["usuario"]
                                );
                                ?>
                            </strong>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- ANTES DE VENIR -->

    <section class="section">

        <div class="section-title">

            <span>
                Antes de venir
            </span>

            <h2>
                Tené en cuenta
            </h2>

            <p>
                Algunos datos importantes para organizar
                tu visita al Restaurante Panorámico.
            </p>

        </div>


        <div class="before-grid">

            <div class="before-card">

                <h3>
                    Reserva
                </h3>

                <p>
                    La reserva corresponde únicamente a la
                    mesa. Los platos y bebidas se solicitan
                    en el restaurante.
                </p>

            </div>


            <div class="before-card">

                <h3>
                    Horarios
                </h3>

                <p>
                    Las reservas pueden realizarse entre
                    las 11:00 y las 21:00.
                </p>

            </div>


            <div class="before-card">

                <h3>
                    Capacidad
                </h3>

                <p>
                    Seleccioná una mesa cuya capacidad sea
                    suficiente para todas las personas de
                    tu grupo.
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

    const mesa =
        document.getElementById("mesa_id");

    const comensales =
        document.getElementById("comensales");

    const capacidadTexto =
        document.getElementById("capacidadTexto");

    const fecha =
        document.getElementById("fecha");


    const hoy =
        new Date()
            .toISOString()
            .split("T")[0];


    if (fecha) {

        fecha.min = hoy;
    }


    if (mesa) {

        mesa.addEventListener(
            "change",
            function () {

                const opcion =
                    mesa.options[
                        mesa.selectedIndex
                    ];

                const capacidad =
                    opcion.getAttribute(
                        "data-capacidad"
                    );


                if (capacidad) {

                    comensales.max =
                        capacidad;


                    if (
                        parseInt(
                            comensales.value
                        ) >
                        parseInt(capacidad)
                    ) {

                        comensales.value =
                            capacidad;
                    }


                    capacidadTexto.textContent =
                        "Esta mesa tiene capacidad para " +
                        capacidad +
                        " personas.";

                } else {

                    comensales.max = 8;

                    capacidadTexto.textContent =
                        "Seleccioná una mesa para consultar su capacidad.";
                }
            }
        );
    }


    if (comensales && mesa) {

        comensales.addEventListener(
            "input",
            function () {

                const opcion =
                    mesa.options[
                        mesa.selectedIndex
                    ];

                const capacidad =
                    opcion.getAttribute(
                        "data-capacidad"
                    );


                if (
                    capacidad &&
                    parseInt(comensales.value) >
                    parseInt(capacidad)
                ) {

                    comensales.value =
                        capacidad;
                }
            }
        );
    }

</script>

</body>

</html>