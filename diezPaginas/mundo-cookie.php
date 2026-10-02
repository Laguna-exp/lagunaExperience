<?php

session_start();

require_once __DIR__ . "/../conexion.php";


/* =====================================================
   CERRAR SESIÓN
===================================================== */

if (isset($_GET["logout"]) && $_GET["logout"] == "1") {

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

    header("Location: mundo-cookie.php");

    exit;
}


/* =====================================================
   CREAR TABLAS SI NO EXISTEN
===================================================== */

$sql = "CREATE TABLE IF NOT EXISTS mc_productos (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    precio DECIMAL(12,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conexion, $sql);


$sql = "CREATE TABLE IF NOT EXISTS mc_talleres (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    horario VARCHAR(100) NOT NULL,
    cupo_max INT NOT NULL,
    cupo_ocupado INT NOT NULL DEFAULT 0,
    precio DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conexion, $sql);


$sql = "CREATE TABLE IF NOT EXISTS mc_ventas (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT DEFAULT NULL,
    visitante VARCHAR(150) NOT NULL,
    pulsera VARCHAR(50) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    puntos INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conexion, $sql);


$sql = "CREATE TABLE IF NOT EXISTS mc_venta_detalle (
    id INT NOT NULL AUTO_INCREMENT,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    producto VARCHAR(150) NOT NULL,
    cantidad INT NOT NULL,
    precio DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conexion, $sql);


$sql = "CREATE TABLE IF NOT EXISTS mc_reservas_taller (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT DEFAULT NULL,
    visitante VARCHAR(150) NOT NULL,
    pulsera VARCHAR(50) NOT NULL,
    taller_id INT NOT NULL,
    taller VARCHAR(150) NOT NULL,
    horario VARCHAR(100) NOT NULL,
    precio DECIMAL(12,2) NOT NULL,
    puntos INT NOT NULL DEFAULT 0,
    asistencia TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conexion, $sql);


/* =====================================================
   CARGAR DATOS INICIALES
===================================================== */

$resultado = mysqli_query(
    $conexion,
    "SELECT COUNT(*) AS cantidad FROM mc_productos"
);

$fila = mysqli_fetch_assoc($resultado);

if ((int)$fila["cantidad"] === 0) {

    mysqli_query(
        $conexion,
        "INSERT INTO mc_productos
        (nombre, categoria, precio, stock)
        VALUES
        ('Cookie clásica', 'Galletas', 2800, 24),
        ('Cookie doble chocolate', 'Galletas', 3200, 18),
        ('Mix de decoración', 'Kits', 8900, 6),
        ('Taza Mundo Cookie', 'Merchandising', 5400, 0),
        ('Caja regalo x12', 'Kits', 15600, 9)"
    );
}


$resultado = mysqli_query(
    $conexion,
    "SELECT COUNT(*) AS cantidad FROM mc_talleres"
);

$fila = mysqli_fetch_assoc($resultado);

if ((int)$fila["cantidad"] === 0) {

    mysqli_query(
        $conexion,
        "INSERT INTO mc_talleres
        (nombre, horario, cupo_max, cupo_ocupado, precio)
        VALUES
        ('Decoración de cookies', 'Lun 16:00', 10, 7, 12000),
        ('Cookies sin gluten', 'Mar 11:00', 8, 8, 13500),
        ('Repostería para niños', 'Mié 15:00', 12, 5, 9800),
        ('Masterclass avanzada', 'Vie 18:00', 6, 2, 19000)"
    );
}


/* =====================================================
   VARIABLES
===================================================== */

$mensajeVenta = "";
$tipoMensajeVenta = "";

$mensajeLogin = "";
$tipoMensajeLogin = "";

$mensajeTaller = "";
$tipoMensajeTaller = "";

$mensajeAsistencia = "";
$tipoMensajeAsistencia = "";

$ventaConfirmada = null;
$reservaConfirmada = null;


/* =====================================================
   INICIAR SESIÓN
===================================================== */

if (
    isset($_POST["accion"]) &&
    $_POST["accion"] === "login"
) {

    $usuario = trim(
        $_POST["loginUsuario"] ?? ""
    );

    $password = trim(
        $_POST["loginPassword"] ?? ""
    );


    if ($usuario === "") {

        $mensajeLogin = "Ingresá el usuario.";
        $tipoMensajeLogin = "err";

    } elseif ($password === "") {

        $mensajeLogin = "Ingresá la contraseña.";
        $tipoMensajeLogin = "err";

    } else {

        $usuarioSeguro = mysqli_real_escape_string(
            $conexion,
            $usuario
        );


        $sql = "
            SELECT *
            FROM usuarios
            WHERE
                (
                    email = '$usuarioSeguro'
                    OR nombre = '$usuarioSeguro'
                )
                AND activo = 1
            LIMIT 1
        ";


        $resultado = mysqli_query(
            $conexion,
            $sql
        );


        $usuarioBD = mysqli_fetch_assoc(
            $resultado
        );


        $loginCorrecto = false;


        if ($usuarioBD) {

            if (
                isset($usuarioBD["password_hash"]) &&
                password_verify(
                    $password,
                    $usuarioBD["password_hash"]
                )
            ) {

                $loginCorrecto = true;

            } elseif (
                isset($usuarioBD["password_hash"]) &&
                $password === $usuarioBD["password_hash"]
            ) {

                $loginCorrecto = true;

            } elseif (
                isset($usuarioBD["password"]) &&
                $password === $usuarioBD["password"]
            ) {

                $loginCorrecto = true;
            }
        }


        if ($loginCorrecto) {

            $_SESSION["usuario_id"] =
                $usuarioBD["id"];

            $_SESSION["usuario"] =
                $usuarioBD["nombre"];

            $_SESSION["email"] =
                $usuarioBD["email"];


            if (isset($usuarioBD["rol"])) {

                $_SESSION["rol"] =
                    $usuarioBD["rol"];
            }


            header("Location: mundo-cookie.php");

            exit;

        } else {

            $mensajeLogin =
                "Usuario o contraseña incorrectos.";

            $tipoMensajeLogin = "err";
        }
    }
}


/* =====================================================
   REGISTRAR VENTA
===================================================== */

if (
    isset($_POST["accion"]) &&
    $_POST["accion"] === "venta"
) {

    $usuarioId =
        $_SESSION["usuario_id"] ?? null;


    $visitante = trim(
        $_POST["vVisitante"] ?? ""
    );


    $productosCarrito =
        $_POST["producto_id"] ?? [];


    $cantidades =
        $_POST["cantidad"] ?? [];


    if ($usuarioId === null) {

        $mensajeVenta =
            "Necesitás iniciar sesión para realizar la compra.";

        $tipoMensajeVenta = "err";

    } elseif ($visitante === "") {

        $mensajeVenta =
            "Ingresá el nombre del visitante.";

        $tipoMensajeVenta = "err";

    } elseif (
        empty($productosCarrito) ||
        empty($cantidades)
    ) {

        $mensajeVenta =
            "Agregá al menos un producto al carrito.";

        $tipoMensajeVenta = "err";

    } else {

        mysqli_begin_transaction($conexion);

        $errorVenta = false;

        $detalles = [];

        $total = 0;


        for (
            $i = 0;
            $i < count($productosCarrito);
            $i++
        ) {

            $productoId =
                (int)$productosCarrito[$i];


            $cantidad =
                (int)$cantidades[$i];


            if ($cantidad < 1) {

                continue;
            }


            $resultado = mysqli_query(
                $conexion,
                "
                SELECT *
                FROM mc_productos
                WHERE id = $productoId
                FOR UPDATE
                "
            );


            $producto =
                mysqli_fetch_assoc($resultado);


            if (!$producto) {

                $errorVenta = true;

                break;
            }


            if (
                (int)$producto["stock"] <
                $cantidad
            ) {

                $mensajeVenta =
                    "No hay stock suficiente de " .
                    $producto["nombre"] .
                    ". Disponible: " .
                    $producto["stock"];

                $tipoMensajeVenta = "err";

                $errorVenta = true;

                break;
            }


            $subtotal =
                $cantidad *
                (float)$producto["precio"];


            $total += $subtotal;


            $detalles[] = [

                "id" =>
                    $producto["id"],

                "nombre" =>
                    $producto["nombre"],

                "cantidad" =>
                    $cantidad,

                "precio" =>
                    $producto["precio"],

                "subtotal" =>
                    $subtotal
            ];
        }


        if (
            !$errorVenta &&
            empty($detalles)
        ) {

            $errorVenta = true;

            $mensajeVenta =
                "Seleccioná al menos un producto y una cantidad.";

            $tipoMensajeVenta = "err";
        }


        if (!$errorVenta) {

            $puntos =
                (int)round($total / 1000);


            $visitanteSeguro =
                mysqli_real_escape_string(
                    $conexion,
                    $visitante
                );


            $usuarioSQL =
                (int)$usuarioId;


            $resultado = mysqli_query(
                $conexion,
                "
                INSERT INTO mc_ventas
                (
                    usuario_id,
                    visitante,
                    pulsera,
                    total,
                    puntos
                )
                VALUES
                (
                    $usuarioSQL,
                    '$visitanteSeguro',
                    '',
                    $total,
                    $puntos
                )
                "
            );


            if (!$resultado) {

                $errorVenta = true;

            } else {

                $ventaId =
                    mysqli_insert_id($conexion);


                foreach ($detalles as $detalle) {

                    $productoId =
                        (int)$detalle["id"];


                    $nombreProducto =
                        mysqli_real_escape_string(
                            $conexion,
                            $detalle["nombre"]
                        );


                    $cantidad =
                        (int)$detalle["cantidad"];


                    $precio =
                        (float)$detalle["precio"];


                    $subtotal =
                        (float)$detalle["subtotal"];


                    $detalleCorrecto = mysqli_query(
                        $conexion,
                        "
                        INSERT INTO mc_venta_detalle
                        (
                            venta_id,
                            producto_id,
                            producto,
                            cantidad,
                            precio,
                            subtotal
                        )
                        VALUES
                        (
                            $ventaId,
                            $productoId,
                            '$nombreProducto',
                            $cantidad,
                            $precio,
                            $subtotal
                        )
                        "
                    );


                    if (!$detalleCorrecto) {

                        $errorVenta = true;

                        break;
                    }


                    $stockCorrecto = mysqli_query(
                        $conexion,
                        "
                        UPDATE mc_productos
                        SET stock = stock - $cantidad
                        WHERE id = $productoId
                        "
                    );


                    if (!$stockCorrecto) {

                        $errorVenta = true;

                        break;
                    }
                }


                if (!$errorVenta) {

                    mysqli_query(
                        $conexion,
                        "
                        UPDATE usuarios
                        SET puntos = puntos + $puntos
                        WHERE id = $usuarioId
                        "
                    );
                }
            }
        }


        if ($errorVenta) {

            mysqli_rollback($conexion);


            if ($mensajeVenta === "") {

                $mensajeVenta =
                    "No se pudo registrar la venta.";

                $tipoMensajeVenta = "err";
            }

        } else {

            mysqli_commit($conexion);


            $ventaConfirmada = [

                "total" =>
                    $total,

                "puntos" =>
                    $puntos
            ];


            $mensajeVenta =
                "Compra registrada correctamente. " .
                "Total: $" .
                number_format(
                    $total,
                    0,
                    ",",
                    "."
                ) .
                ". " .
                $puntos .
                " puntos generados. " .
                "Podés retirar tu compra en Mundo Cookie.";


            $tipoMensajeVenta = "ok";
        }
    }
}


/* =====================================================
   RESERVAR TALLER
===================================================== */

if (
    isset($_POST["accion"]) &&
    $_POST["accion"] === "reservar_taller"
) {

    $usuarioId =
        $_SESSION["usuario_id"] ?? null;


    $visitante = trim(
        $_POST["tVisitante"] ?? ""
    );


    $tallerId =
        (int)($_POST["tTaller"] ?? 0);


    if ($usuarioId === null) {

        $mensajeTaller =
            "Necesitás iniciar sesión para reservar un taller.";

        $tipoMensajeTaller = "err";

    } elseif ($visitante === "") {

        $mensajeTaller =
            "Ingresá el nombre del visitante.";

        $tipoMensajeTaller = "err";

    } elseif ($tallerId <= 0) {

        $mensajeTaller =
            "Seleccioná un taller.";

        $tipoMensajeTaller = "err";

    } else {

        mysqli_begin_transaction($conexion);


        $resultado = mysqli_query(
            $conexion,
            "
            SELECT *
            FROM mc_talleres
            WHERE id = $tallerId
            FOR UPDATE
            "
        );


        $taller =
            mysqli_fetch_assoc($resultado);


        if (!$taller) {

            mysqli_rollback($conexion);


            $mensajeTaller =
                "El taller seleccionado no existe.";

            $tipoMensajeTaller = "err";

        } else {

            $libres =
                (int)$taller["cupo_max"] -
                (int)$taller["cupo_ocupado"];


            if ($libres <= 0) {

                mysqli_rollback($conexion);


                $mensajeTaller =
                    $taller["nombre"] .
                    " (" .
                    $taller["horario"] .
                    ") no tiene cupos disponibles.";

                $tipoMensajeTaller = "err";

            } else {

                $visitanteSeguro =
                    mysqli_real_escape_string(
                        $conexion,
                        $visitante
                    );


                $nombreTaller =
                    mysqli_real_escape_string(
                        $conexion,
                        $taller["nombre"]
                    );


                $horario =
                    mysqli_real_escape_string(
                        $conexion,
                        $taller["horario"]
                    );


                $precio =
                    (float)$taller["precio"];


                $puntos =
                    (int)round($precio / 1000);


                $resultado = mysqli_query(
                    $conexion,
                    "
                    INSERT INTO mc_reservas_taller
                    (
                        usuario_id,
                        visitante,
                        pulsera,
                        taller_id,
                        taller,
                        horario,
                        precio,
                        puntos,
                        asistencia
                    )
                    VALUES
                    (
                        $usuarioId,
                        '$visitanteSeguro',
                        '',
                        $tallerId,
                        '$nombreTaller',
                        '$horario',
                        $precio,
                        $puntos,
                        0
                    )
                    "
                );


                if (!$resultado) {

                    mysqli_rollback($conexion);


                    $mensajeTaller =
                        "No se pudo registrar la reserva.";

                    $tipoMensajeTaller = "err";

                } else {

                    $actualizarCupos = mysqli_query(
                        $conexion,
                        "
                        UPDATE mc_talleres
                        SET cupo_ocupado =
                            cupo_ocupado + 1
                        WHERE id = $tallerId
                        "
                    );


                    if (!$actualizarCupos) {

                        mysqli_rollback($conexion);

                        $mensajeTaller =
                            "No se pudo actualizar el cupo.";

                        $tipoMensajeTaller = "err";

                    } else {

                        mysqli_query(
                            $conexion,
                            "
                            UPDATE usuarios
                            SET puntos =
                                puntos + $puntos
                            WHERE id = $usuarioId
                            "
                        );


                        mysqli_commit($conexion);


                        $reservaConfirmada = [

                            "taller" =>
                                $taller["nombre"],

                            "horario" =>
                                $taller["horario"],

                            "precio" =>
                                $precio
                        ];


                        $mensajeTaller =
                            "Reserva confirmada para " .
                            htmlspecialchars($visitante) .
                            " en \"" .
                            htmlspecialchars($taller["nombre"]) .
                            "\" (" .
                            htmlspecialchars($taller["horario"]) .
                            "). Pago de $" .
                            number_format(
                                $precio,
                                0,
                                ",",
                                "."
                            ) .
                            " registrado.";


                        $tipoMensajeTaller = "ok";
                    }
                }
            }
        }
    }
}


/* =====================================================
   REGISTRAR ASISTENCIA
===================================================== */

if (
    isset($_POST["accion"]) &&
    $_POST["accion"] === "asistencia"
) {

    $reservaId =
        (int)($_POST["aReserva"] ?? 0);


    if ($reservaId <= 0) {

        $mensajeAsistencia =
            "Seleccioná una reserva válida.";

        $tipoMensajeAsistencia = "err";

    } else {

        $resultado = mysqli_query(
            $conexion,
            "
            SELECT *
            FROM mc_reservas_taller
            WHERE id = $reservaId
            "
        );


        $reserva =
            mysqli_fetch_assoc($resultado);


        if (!$reserva) {

            $mensajeAsistencia =
                "La reserva seleccionada no existe.";

            $tipoMensajeAsistencia = "err";

        } elseif (
            (int)$reserva["asistencia"] === 1
        ) {

            $mensajeAsistencia =
                "Esa asistencia ya fue registrada.";

            $tipoMensajeAsistencia = "err";

        } else {

            mysqli_query(
                $conexion,
                "
                UPDATE mc_reservas_taller
                SET asistencia = 1
                WHERE id = $reservaId
                "
            );


            $mensajeAsistencia =
                "Asistencia registrada para " .
                htmlspecialchars(
                    $reserva["visitante"]
                ) .
                " en \"" .
                htmlspecialchars(
                    $reserva["taller"]
                ) .
                "\". " .
                $reserva["puntos"] .
                " puntos ya habían sido acreditados por la reserva.";


            $tipoMensajeAsistencia = "ok";
        }
    }
}


/* =====================================================
   CONSULTAR PRODUCTOS
===================================================== */

$productos = [];

$resultado = mysqli_query(
    $conexion,
    "
    SELECT *
    FROM mc_productos
    ORDER BY id ASC
    "
);

while ($fila = mysqli_fetch_assoc($resultado)) {

    $productos[] = $fila;
}


/* =====================================================
   CONSULTAR TALLERES
===================================================== */

$talleres = [];

$resultado = mysqli_query(
    $conexion,
    "
    SELECT *
    FROM mc_talleres
    ORDER BY id ASC
    "
);

while ($fila = mysqli_fetch_assoc($resultado)) {

    $talleres[] = $fila;
}


/* =====================================================
   RESERVAS PARA ASISTENCIA
===================================================== */

$reservasPendientes = [];

$resultado = mysqli_query(
    $conexion,
    "
    SELECT *
    FROM mc_reservas_taller
    WHERE asistencia = 0
    ORDER BY creado_en DESC
    "
);

while ($fila = mysqli_fetch_assoc($resultado)) {

    $reservasPendientes[] = $fila;
}


/* =====================================================
   HISTORIAL DE VENTAS
===================================================== */

$ventas = [];

$resultado = mysqli_query(
    $conexion,
    "
    SELECT *
    FROM mc_ventas
    ORDER BY id DESC
    "
);

while ($fila = mysqli_fetch_assoc($resultado)) {

    $ventaId =
        (int)$fila["id"];


    $detalles = [];


    $resultadoDetalles = mysqli_query(
        $conexion,
        "
        SELECT *
        FROM mc_venta_detalle
        WHERE venta_id = $ventaId
        ORDER BY id ASC
        "
    );


    while (
        $detalle =
        mysqli_fetch_assoc($resultadoDetalles)
    ) {

        $detalles[] = $detalle;
    }


    $fila["detalles"] =
        $detalles;


    $ventas[] =
        $fila;
}


/* =====================================================
   HISTORIAL DE TALLERES
===================================================== */

$reservasHistorial = [];

$resultado = mysqli_query(
    $conexion,
    "
    SELECT *
    FROM mc_reservas_taller
    ORDER BY id DESC
    "
);

while ($fila = mysqli_fetch_assoc($resultado)) {

    $reservasHistorial[] =
        $fila;
}


/* =====================================================
   USUARIO ACTUAL
===================================================== */

$usuarioActivo =
    $_SESSION["usuario"] ?? null;


/* =====================================================
   ESTADÍSTICAS
===================================================== */

$totalProductos =
    count($productos);


$productosDisponibles =
    0;


foreach ($productos as $producto) {

    if ((int)$producto["stock"] > 0) {

        $productosDisponibles++;
    }
}


$totalTalleres =
    count($talleres);


$talleresDisponibles =
    0;


foreach ($talleres as $taller) {

    $libres =
        (int)$taller["cupo_max"] -
        (int)$taller["cupo_ocupado"];


    if ($libres > 0) {

        $talleresDisponibles++;
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
    Laguna Experience · Mundo Cookie
</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
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

    --ink:#2e2016;
    --ink-soft:#71594a;

    --sand:#faf1e4;
    --paper:#fffdf9;

    --choco:#7a4a26;
    --choco-deep:#4f2f16;
    --choco-light:#f1e2d1;

    --pink:#d9678a;
    --pink-light:#fbe4ec;

    --mint:#3f9b7d;
    --mint-light:#e1f3ec;

    --coral:#d9633b;
    --coral-light:#fbe6dc;

    --line:#ecdcc6;

    --radius:14px;

    --shadow:
        0 6px 20px
        rgba(60,35,15,.08);
}


*{
    box-sizing:border-box;
}


html{
    scroll-behavior:smooth;
}


body{

    margin:0;

    background:var(--sand);

    color:var(--ink);

    font-family:"DM Sans",sans-serif;
}


button,
input,
select{
    font-family:inherit;
}


a{
    color:inherit;
}


/* =====================================================
   HEADER
===================================================== */

header{

    position:relative;

    min-height:510px;

    padding:80px 8%;

    display:flex;

    align-items:center;

    overflow:hidden;

    color:white;

    background:
        linear-gradient(
            90deg,
            rgba(79,47,22,.96),
            rgba(122,74,38,.78)
        ),
        linear-gradient(
            120deg,
            var(--choco-deep),
            var(--choco)
        );
}


header::before{

    content:"";

    position:absolute;

    width:420px;
    height:420px;

    border-radius:50%;

    background:
        rgba(255,255,255,.08);

    right:-130px;
    top:-120px;
}


header::after{

    content:"";

    position:absolute;

    width:250px;
    height:250px;

    border-radius:50%;

    background:
        rgba(255,255,255,.07);

    right:15%;
    bottom:-170px;
}


.header-content{

    position:relative;

    z-index:2;

    max-width:760px;
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

    font-size:
        clamp(48px,7vw,88px);

    line-height:.98;

    letter-spacing:-3px;
}


header .sub{

    max-width:670px;

    margin:28px 0 0;

    font-size:18px;

    line-height:1.7;

    color:
        rgba(255,255,255,.92);
}


/* =====================================================
   BOTÓN VOLVER
===================================================== */

.back-button{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:48px;

    padding:0 20px;

    margin-top:26px;

    border-radius:14px;

    background:white;

    color:var(--choco-deep);

    text-decoration:none;

    font-size:14px;

    font-weight:800;

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}


.back-button:hover{

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.15);
}


/* =====================================================
   CONTENIDO
===================================================== */

.wrap{

    max-width:1250px;

    margin:-45px auto 80px;

    padding:0 5%;

    position:relative;

    z-index:3;
}


.intro-card{

    background:var(--paper);

    border:1px solid var(--line);

    border-radius:28px;

    padding:38px;

    box-shadow:var(--shadow);
}


.intro-card h2{

    margin:0 0 12px;

    font-family:"Manrope",sans-serif;

    font-size:30px;

    color:var(--choco-deep);
}


.intro-card p{

    margin:0;

    max-width:800px;

    line-height:1.7;

    color:var(--ink-soft);
}


/* =====================================================
   ESTADÍSTICAS
===================================================== */

.stats{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:18px;

    margin-top:24px;
}


.stat{

    background:var(--paper);

    border:1px solid var(--line);

    border-radius:22px;

    padding:26px;

    box-shadow:var(--shadow);
}


.stat strong{

    display:block;

    font-family:"Manrope",sans-serif;

    color:var(--choco-deep);

    font-size:32px;
}


.stat span{

    display:block;

    margin-top:5px;

    color:var(--ink-soft);

    font-size:14px;
}


/* =====================================================
   TABS
===================================================== */

nav.tabs{

    display:flex;

    gap:8px;

    background:var(--paper);

    border-radius:18px;

    padding:7px;

    box-shadow:var(--shadow);

    margin:30px 0 24px;

    border:1px solid var(--line);

    flex-wrap:wrap;
}


nav.tabs button{

    border:none;

    background:transparent;

    padding:12px 20px;

    border-radius:13px;

    font-size:14px;

    font-weight:700;

    color:var(--ink-soft);

    cursor:pointer;

    transition:.25s ease;
}


nav.tabs button.active{

    background:var(--choco);

    color:#fff;
}


nav.tabs button:hover:not(.active){

    background:var(--choco-light);
}


/* =====================================================
   PANELES
===================================================== */

section.panel{

    display:none;
}


section.panel.active{

    display:block;

    animation:
        fade .25s ease;
}


@keyframes fade{

    from{

        opacity:0;

        transform:
            translateY(4px);
    }

    to{

        opacity:1;

        transform:none;
    }

}


/* =====================================================
   CARDS
===================================================== */

.card{

    background:var(--paper);

    border-radius:24px;

    box-shadow:var(--shadow);

    padding:30px;

    margin-bottom:22px;

    border:1px solid var(--line);
}


.card h2{

    margin:0 0 8px;

    font-family:"Manrope",sans-serif;

    font-size:28px;

    color:var(--choco-deep);
}


.card p.hint{

    margin:0 0 22px;

    color:var(--ink-soft);

    font-size:14px;

    line-height:1.6;
}


/* =====================================================
   PRODUCTOS
===================================================== */

.grid-prod{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:20px;
}


.prod{

    border:1px solid var(--line);

    border-radius:22px;

    padding:24px;

    background:#fff;

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}


.prod:hover{

    transform:
        translateY(-4px);

    box-shadow:
        0 15px 35px
        rgba(60,35,15,.10);
}


.prod h3{

    margin:18px 0 6px;

    font-family:"Manrope",sans-serif;

    font-size:19px;

    color:var(--choco-deep);
}


.prod .meta{

    font-size:13px;

    color:var(--ink-soft);
}


.prod .price{

    margin-top:14px;

    font-weight:800;

    color:var(--choco-deep);

    font-size:20px;
}


/* =====================================================
   BADGES
===================================================== */

.badge{

    display:inline-flex;

    font-size:11px;

    font-weight:800;

    padding:7px 11px;

    border-radius:999px;

    text-transform:uppercase;

    letter-spacing:.4px;
}


.b-stock{

    background:var(--mint-light);

    color:#1f6b52;
}


.b-sinstock{

    background:var(--coral-light);

    color:#a8391c;
}


.b-cupos{

    background:var(--mint-light);

    color:#1f6b52;
}


.b-sincupos{

    background:var(--coral-light);

    color:#a8391c;
}


/* =====================================================
   FORMULARIOS
===================================================== */

label{

    display:block;

    font-size:13px;

    font-weight:800;

    color:var(--ink-soft);

    margin:14px 0 7px;
}


select,
input{

    width:100%;

    min-height:50px;

    padding:0 14px;

    border-radius:13px;

    border:1px solid var(--line);

    font-size:14px;

    background:#fff;

    color:var(--ink);

    outline:none;
}


select:focus,
input:focus{

    border-color:var(--choco);

    box-shadow:
        0 0 0 3px
        rgba(122,74,38,.10);
}


.row2{

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:18px;
}


/* =====================================================
   BOTONES
===================================================== */

button.action{

    margin-top:20px;

    min-height:50px;

    background:var(--choco);

    color:#fff;

    border:none;

    padding:0 22px;

    border-radius:13px;

    font-size:14px;

    font-weight:800;

    cursor:pointer;

    transition:
        transform .25s ease,
        background .25s ease;
}


button.action:hover{

    background:var(--choco-deep);

    transform:
        translateY(-2px);
}


button.action.ghost{

    background:transparent;

    border:1.5px solid var(--choco);

    color:var(--choco);
}


button.action.ghost:hover{

    background:var(--choco-light);
}


button.action.pink{

    background:var(--pink);
}


button.action.pink:hover{

    background:#b34a68;
}


button.action.mint{

    background:var(--mint);
}


button.action.mint:hover{

    background:#2c7a60;
}


/* =====================================================
   MENSAJES
===================================================== */

.msg{

    padding:16px 18px;

    border-radius:14px;

    font-size:13.5px;

    margin-top:18px;

    font-weight:600;
}


.msg.ok{

    background:var(--mint-light);

    color:#1c7a45;

    border:1px solid #b9dfd0;
}


.msg.err{

    background:var(--coral-light);

    color:#a8391c;

    border:1px solid #f0c8ba;
}


/* =====================================================
   LOGIN
===================================================== */

.login-box{

    background:var(--pink-light);

    border:1px solid #efc4d2;

    border-radius:22px;

    padding:26px;

    margin-top:20px;
}


.login-box h3{

    margin:0 0 8px;

    font-family:"Manrope",sans-serif;

    font-size:22px;

    color:var(--choco-deep);
}


.login-box p{

    margin:0 0 16px;

    color:var(--ink-soft);

    font-size:14px;

    line-height:1.6;
}


/* =====================================================
   USUARIO ACTIVO
===================================================== */

.usuario-activo{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

    background:var(--mint-light);

    border:1px solid #b9dfd0;

    border-radius:15px;

    padding:15px 17px;

    margin-bottom:20px;

    font-size:13px;
}


.usuario-activo strong{

    color:#1f6b52;
}


.logout{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    min-height:40px;

    padding:0 15px;

    border-radius:11px;

    background:var(--coral-light);

    color:#a8391c;

    text-decoration:none;

    font-size:12px;

    font-weight:800;

    transition:
        transform .2s ease,
        background .2s ease;
}


.logout:hover{

    transform:
        translateY(-1px);

    background:#f5d2c5;
}


/* =====================================================
   TABLAS
===================================================== */

.table-container{

    overflow-x:auto;

    border:1px solid var(--line);

    border-radius:16px;
}


table{

    width:100%;

    border-collapse:collapse;

    font-size:13.5px;

    background:white;
}


th,
td{

    text-align:left;

    padding:14px 15px;

    border-bottom:1px solid var(--line);
}


th{

    color:var(--ink-soft);

    font-size:11px;

    text-transform:uppercase;

    letter-spacing:.6px;

    background:var(--sand);
}


tr:last-child td{

    border-bottom:none;
}


.empty{

    color:var(--ink-soft);

    font-size:13.5px;

    font-style:italic;

    padding:20px !important;
}


/* =====================================================
   FOOTER
===================================================== */

footer{

    margin-top:80px;

    padding:35px 5%;

    background:var(--choco-deep);

    color:
        rgba(255,255,255,.8);

    text-align:center;

    font-size:13px;
}


footer strong{

    color:white;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:1000px){

    .grid-prod{

        grid-template-columns:
            repeat(2,1fr);
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


    .wrap{

        padding:0 4%;
    }


    .stats{

        grid-template-columns:1fr;
    }


    .grid-prod{

        grid-template-columns:1fr;
    }


    .row2{

        grid-template-columns:1fr;
    }


    .card,
    .intro-card{

        padding:25px;
    }


    .usuario-activo{

        flex-direction:column;

        align-items:flex-start;
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


    .wrap{

        margin-top:-30px;
    }


    nav.tabs{

        border-radius:18px;
    }


    nav.tabs button{

        width:100%;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header>

    <div class="header-content">

        <p class="eyebrow">
            Laguna Experience · Gastronomía
        </p>

        <h1>
            Mundo Cookie
        </h1>

        <p class="sub">
            Descubrí productos, talleres y experiencias de
            repostería dentro de Laguna Experience.
        </p>

        <a
            href="../index.php"
            class="back-button"
        >
            ← Volver al inicio
        </a>

    </div>

</header>


<div class="wrap">


    <!-- =================================================
         INTRO
    ================================================== -->

    <div class="intro-card">

        <h2>
            Una experiencia dulce para disfrutar
        </h2>

        <p>
            Mundo Cookie combina una tienda de productos,
            talleres de repostería y un sistema de compras
            y reservas para los visitantes de Laguna Experience.
        </p>

    </div>


    <!-- =================================================
         ESTADÍSTICAS
    ================================================== -->

    <div class="stats">

        <div class="stat">

            <strong>
                <?php echo $totalProductos; ?>
            </strong>

            <span>
                Productos en catálogo
            </span>

        </div>


        <div class="stat">

            <strong>
                <?php echo $productosDisponibles; ?>
            </strong>

            <span>
                Productos con stock
            </span>

        </div>


        <div class="stat">

            <strong>
                <?php echo $talleresDisponibles; ?>
            </strong>

            <span>
                Talleres con cupos disponibles
            </span>

        </div>

    </div>


    <!-- =================================================
         TABS
    ================================================== -->

    <nav class="tabs">

        <button
            type="button"
            data-tab="productos"
            class="active"
            onclick="mostrarTab('productos')"
        >
            Comprar productos
        </button>


        <button
            type="button"
            data-tab="talleres"
            onclick="mostrarTab('talleres')"
        >
            Talleres
        </button>


        <button
            type="button"
            data-tab="historial"
            onclick="mostrarTab('historial')"
        >
            Historial
        </button>

    </nav>


    <!-- =================================================
         PRODUCTOS
    ================================================== -->

    <section
        class="panel active"
        id="tab-productos"
    >

        <div class="card">

            <h2>
                Catálogo de productos
            </h2>

            <p class="hint">
                Elegí los productos que querés comprar.
            </p>


            <div class="grid-prod">

                <?php foreach ($productos as $producto): ?>

                    <div class="prod">

                        <span
                            class="badge
                            <?php

                            echo $producto["stock"] > 0
                                ? "b-stock"
                                : "b-sinstock";

                            ?>"
                        >

                            <?php

                            if ($producto["stock"] > 0) {

                                echo $producto["stock"] .
                                     " en stock";

                            } else {

                                echo "sin stock";
                            }

                            ?>

                        </span>


                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $producto["nombre"]
                            );

                            ?>

                        </h3>


                        <div class="meta">

                            <?php

                            echo htmlspecialchars(
                                $producto["categoria"]
                            );

                            ?>

                        </div>


                        <div class="price">

                            $

                            <?php

                            echo number_format(
                                $producto["precio"],
                                0,
                                ",",
                                "."
                            );

                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>


        <!-- =================================================
             NUEVA VENTA
        ================================================== -->

        <div class="card">

            <h2>
                Comprar y retirar después
            </h2>

            <p class="hint">
                Iniciá sesión para realizar tu compra y
                retirarla posteriormente en Mundo Cookie.
            </p>


            <?php if ($usuarioActivo): ?>


                <div class="usuario-activo">

                    <span>

                        Usuario iniciado:

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $usuarioActivo
                            );

                            ?>

                        </strong>

                    </span>


                    <a
                        class="logout"
                        href="mundo-cookie.php?logout=1"
                    >
                        Cerrar sesión
                    </a>

                </div>


                <form method="POST">

                    <input
                        type="hidden"
                        name="accion"
                        value="venta"
                    >


                    <div>

                        <label>
                            Visitante
                        </label>

                        <input
                            name="vVisitante"
                            placeholder="Nombre y apellido"
                            required
                        >

                    </div>


                    <div
                        class="card"
                        style="
                            margin-top:25px;
                            margin-bottom:0;
                            background:var(--sand);
                        "
                    >

                        <h2>
                            Productos
                        </h2>

                        <p class="hint">
                            Seleccioná las cantidades que querés comprar.
                        </p>


                        <?php foreach ($productos as $producto): ?>

                            <?php if ($producto["stock"] > 0): ?>

                                <div class="row2">

                                    <div>

                                        <label>

                                            <?php

                                            echo htmlspecialchars(
                                                $producto["nombre"]
                                            );

                                            ?>

                                        </label>


                                        <input
                                            type="hidden"
                                            name="producto_id[]"
                                            value="<?php
                                            echo $producto["id"];
                                            ?>"
                                        >


                                        <input
                                            value="<?php

                                            echo "$" .
                                                number_format(
                                                    $producto["precio"],
                                                    0,
                                                    ",",
                                                    "."
                                                );

                                            ?>"
                                            readonly
                                        >

                                    </div>


                                    <div>

                                        <label>
                                            Cantidad
                                        </label>

                                        <input
                                            type="number"
                                            name="cantidad[]"
                                            min="0"
                                            max="<?php
                                            echo $producto["stock"];
                                            ?>"
                                            value="0"
                                        >

                                    </div>

                                </div>

                            <?php endif; ?>

                        <?php endforeach; ?>


                        <button
                            type="submit"
                            class="action"
                        >
                            Comprar y reservar para retirar
                        </button>

                    </div>

                </form>


            <?php else: ?>


                <div class="login-box">

                    <h3>
                        Iniciar sesión para comprar
                    </h3>

                    <p>
                        Ingresá con tu usuario y contraseña
                        para realizar la compra y dejarla
                        registrada a tu nombre.
                    </p>


                    <form method="POST">

                        <input
                            type="hidden"
                            name="accion"
                            value="login"
                        >


                        <label>
                            Usuario o email
                        </label>

                        <input
                            name="loginUsuario"
                            placeholder="Usuario o email"
                            required
                        >


                        <label>
                            Contraseña
                        </label>

                        <input
                            name="loginPassword"
                            type="password"
                            placeholder="Contraseña"
                            required
                        >


                        <button
                            type="submit"
                            class="action mint"
                        >
                            Iniciar sesión
                        </button>

                    </form>


                    <?php if ($mensajeLogin !== ""): ?>

                        <div
                            class="msg
                            <?php echo $tipoMensajeLogin; ?>"
                        >

                            <?php

                            echo $mensajeLogin;

                            ?>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


            <?php if ($mensajeVenta !== ""): ?>

                <div
                    class="msg
                    <?php echo $tipoMensajeVenta; ?>"
                >

                    <?php

                    echo $mensajeVenta;

                    ?>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- =================================================
         TALLERES
    ================================================== -->

    <section
        class="panel"
        id="tab-talleres"
    >

        <div class="card">

            <h2>
                Talleres disponibles
            </h2>

            <p class="hint">
                Consultá los talleres, horarios, precios y
                cupos disponibles.
            </p>


            <div class="grid-prod">

                <?php foreach ($talleres as $taller): ?>

                    <?php

                    $libres =
                        $taller["cupo_max"] -
                        $taller["cupo_ocupado"];

                    ?>


                    <div class="prod">

                        <span
                            class="badge
                            <?php

                            echo $libres > 0
                                ? "b-cupos"
                                : "b-sincupos";

                            ?>"
                        >

                            <?php

                            if ($libres > 0) {

                                echo $libres .
                                     " cupos libres";

                            } else {

                                echo "sin cupos";
                            }

                            ?>

                        </span>


                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $taller["nombre"]
                            );

                            ?>

                        </h3>


                        <div class="meta">

                            <?php

                            echo htmlspecialchars(
                                $taller["horario"]
                            );

                            ?>

                            · cupo máximo

                            <?php
                            echo $taller["cupo_max"];
                            ?>

                        </div>


                        <div class="price">

                            $

                            <?php

                            echo number_format(
                                $taller["precio"],
                                0,
                                ",",
                                "."
                            );

                            ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>


        <!-- RESERVAR TALLER -->

        <div class="card">

            <h2>
                Reservar taller
            </h2>

            <p class="hint">
                Para reservar un taller es necesario tener
                una sesión iniciada.
            </p>


            <?php if ($usuarioActivo): ?>

                <div class="usuario-activo">

                    <span>

                        Usuario iniciado:

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $usuarioActivo
                            );

                            ?>

                        </strong>

                    </span>


                    <a
                        class="logout"
                        href="mundo-cookie.php?logout=1"
                    >
                        Cerrar sesión
                    </a>

                </div>


                <form method="POST">

                    <input
                        type="hidden"
                        name="accion"
                        value="reservar_taller"
                    >


                    <div>

                        <label>
                            Visitante
                        </label>

                        <input
                            name="tVisitante"
                            placeholder="Nombre y apellido"
                            required
                        >

                    </div>


                    <label>
                        Taller y horario
                    </label>


                    <select
                        name="tTaller"
                        required
                    >

                        <option value="">
                            Seleccioná un taller
                        </option>


                        <?php foreach ($talleres as $taller): ?>

                            <?php

                            $libres =
                                $taller["cupo_max"] -
                                $taller["cupo_ocupado"];

                            ?>


                            <option
                                value="<?php
                                echo $taller["id"];
                                ?>"
                                <?php

                                echo $libres <= 0
                                    ? "disabled"
                                    : "";

                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $taller["nombre"]
                                );

                                ?>

                                ·

                                <?php

                                echo htmlspecialchars(
                                    $taller["horario"]
                                );

                                ?>


                                <?php

                                if ($libres <= 0) {

                                    echo "(sin cupos)";

                                } else {

                                    echo "(" .
                                         $libres .
                                         " libres)";
                                }

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <button
                        type="submit"
                        class="action mint"
                    >
                        Reservar y pagar
                    </button>

                </form>


            <?php else: ?>


                <div class="login-box">

                    <h3>
                        Iniciar sesión
                    </h3>

                    <p>
                        Necesitás tener un usuario para
                        realizar una reserva.
                    </p>


                    <form method="POST">

                        <input
                            type="hidden"
                            name="accion"
                            value="login"
                        >


                        <label>
                            Usuario o email
                        </label>

                        <input
                            name="loginUsuario"
                            placeholder="Usuario o email"
                            required
                        >


                        <label>
                            Contraseña
                        </label>

                        <input
                            name="loginPassword"
                            type="password"
                            placeholder="Contraseña"
                            required
                        >


                        <button
                            type="submit"
                            class="action mint"
                        >
                            Iniciar sesión
                        </button>

                    </form>


                    <?php if ($mensajeLogin !== ""): ?>

                        <div
                            class="msg
                            <?php echo $tipoMensajeLogin; ?>"
                        >

                            <?php

                            echo $mensajeLogin;

                            ?>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


            <?php if ($mensajeTaller !== ""): ?>

                <div
                    class="msg
                    <?php echo $tipoMensajeTaller; ?>"
                >

                    <?php

                    echo $mensajeTaller;

                    ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- ASISTENCIA -->

        <div class="card">

            <h2>
                Registrar asistencia
            </h2>

            <p class="hint">
                Seleccioná una reserva pendiente para registrar
                la asistencia del visitante.
            </p>


            <form method="POST">

                <input
                    type="hidden"
                    name="accion"
                    value="asistencia"
                >


                <label>
                    Reserva de taller
                </label>


                <select name="aReserva">

                    <option value="">
                        Seleccioná una reserva
                    </option>


                    <?php foreach ($reservasPendientes as $reserva): ?>

                        <option
                            value="<?php
                            echo $reserva["id"];
                            ?>"
                        >

                            <?php

                            echo htmlspecialchars(
                                $reserva["visitante"]
                            );

                            ?>

                            ·

                            <?php

                            echo htmlspecialchars(
                                $reserva["taller"]
                            );

                            ?>

                            (

                            <?php

                            echo htmlspecialchars(
                                $reserva["horario"]
                            );

                            ?>

                            )

                        </option>

                    <?php endforeach; ?>

                </select>


                <button
                    type="submit"
                    class="action pink"
                >
                    Registrar asistencia
                </button>

            </form>


            <?php if ($mensajeAsistencia !== ""): ?>

                <div
                    class="msg
                    <?php echo $tipoMensajeAsistencia; ?>"
                >

                    <?php

                    echo $mensajeAsistencia;

                    ?>

                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- =================================================
         HISTORIAL
    ================================================== -->

    <section
        class="panel"
        id="tab-historial"
    >

        <div class="card">

            <h2>
                Ventas
            </h2>

            <p class="hint">
                Historial de ventas realizadas en Mundo Cookie.
            </p>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Visitante
                            </th>

                            <th>
                                Items
                            </th>

                            <th>
                                Total
                            </th>

                            <th>
                                Puntos
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (empty($ventas)): ?>

                            <tr>

                                <td
                                    colspan="4"
                                    class="empty"
                                >
                                    Todavía no hay ventas registradas.
                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($ventas as $venta): ?>

                                <tr>

                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $venta["visitante"]
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php foreach (
                                            $venta["detalles"]
                                            as $detalle
                                        ): ?>

                                            <?php

                                            echo $detalle["cantidad"] .
                                                 "× " .
                                                 htmlspecialchars(
                                                     $detalle["producto"]
                                                 );

                                            ?>

                                            <br>

                                        <?php endforeach; ?>

                                    </td>


                                    <td>

                                        $

                                        <?php

                                        echo number_format(
                                            $venta["total"],
                                            0,
                                            ",",
                                            "."
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo $venta["puntos"];

                                        ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <div class="card">

            <h2>
                Reservas de talleres
            </h2>

            <p class="hint">
                Historial de reservas y asistencias registradas.
            </p>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Visitante
                            </th>

                            <th>
                                Taller
                            </th>

                            <th>
                                Horario
                            </th>

                            <th>
                                Asistencia
                            </th>

                            <th>
                                Puntos
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (
                            empty($reservasHistorial)
                        ): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty"
                                >
                                    Todavía no hay reservas de talleres.
                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $reservasHistorial
                                as $reserva
                            ): ?>

                                <tr>

                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $reserva["visitante"]
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $reserva["taller"]
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $reserva["horario"]
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo $reserva["asistencia"]
                                            ? "Presente"
                                            : "Pendiente";

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo $reserva["puntos"];

                                        ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

</div>


<footer>

    <strong>
        Laguna Experience
    </strong>

    <br>

    Mundo Cookie · Sistema de productos y talleres

</footer>


<script>

function mostrarTab(tab){

    document
        .querySelectorAll("nav.tabs button")
        .forEach(function(btn){

            btn.classList.remove("active");

        });


    document
        .querySelectorAll("section.panel")
        .forEach(function(panel){

            panel.classList.remove("active");

        });


    const boton =
        document.querySelector(
            'nav.tabs button[data-tab="' +
            tab +
            '"]'
        );


    const panel =
        document.getElementById(
            "tab-" + tab
        );


    if(boton){

        boton.classList.add("active");

    }


    if(panel){

        panel.classList.add("active");

    }

}

</script>


</body>

</html>