<?php

session_start();

require_once __DIR__ . "/../conexion.php";


/* =====================================================
   MENSAJES
===================================================== */

$mensaje = "";
$tipoMensaje = "";

$mensajeLogin = "";
$tipoMensajeLogin = "";

$reservaConfirmada = false;
$codigoReserva = "";
$nombreReserva = "";
$horarioReserva = "";


/* =====================================================
   LOGOUT
===================================================== */

if (isset($_GET["logout"])) {

    unset($_SESSION["usuario_id"]);
    unset($_SESSION["usuario"]);
    unset($_SESSION["usuario_email"]);

    header("Location: parque-aventura.php");
    exit;
}


/* =====================================================
   LOGIN
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["accion"]) &&
    $_POST["accion"] === "login"
) {

    $usuarioLogin = trim(
        $_POST["loginUsuario"] ?? ""
    );

    $passwordLogin = trim(
        $_POST["loginPassword"] ?? ""
    );


    if ($usuarioLogin === "") {

        $mensajeLogin =
            "Ingresá tu usuario o email.";

        $tipoMensajeLogin = "bad";

    } elseif ($passwordLogin === "") {

        $mensajeLogin =
            "Ingresá tu contraseña.";

        $tipoMensajeLogin = "bad";

    } else {

        $usuarioSeguro =
            mysqli_real_escape_string(
                $conexion,
                $usuarioLogin
            );


        $sqlLogin = "
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


        $resultadoLogin =
            mysqli_query(
                $conexion,
                $sqlLogin
            );


        $usuarioBD =
            mysqli_fetch_assoc(
                $resultadoLogin
            );


        $loginCorrecto = false;


        if ($usuarioBD) {

            if (
                password_verify(
                    $passwordLogin,
                    $usuarioBD["password_hash"]
                )
            ) {

                $loginCorrecto = true;

            } elseif (
                $passwordLogin ===
                $usuarioBD["password_hash"]
            ) {

                $loginCorrecto = true;
            }
        }


        if ($loginCorrecto) {

            $_SESSION["usuario_id"] =
                $usuarioBD["id"];

            $_SESSION["usuario"] =
                $usuarioBD["nombre"];

            $_SESSION["usuario_email"] =
                $usuarioBD["email"];


            header(
                "Location: parque-aventura.php"
            );

            exit;

        } else {

            $mensajeLogin =
                "Usuario o contraseña incorrectos.";

            $tipoMensajeLogin = "bad";
        }
    }
}


/* =====================================================
   RESERVA
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["reservar"])
) {

    $usuario_id =
        $_SESSION["usuario_id"] ?? null;

    $usuario =
        $_SESSION["usuario"] ?? "";

    $actividad_id =
        intval($_POST["actividad_id"] ?? 0);

    $horario =
        trim($_POST["horario"] ?? "");

    $edad =
        intval($_POST["edad"] ?? 0);

    $altura =
        intval($_POST["altura"] ?? 0);

    $peso =
        intval($_POST["peso"] ?? 0);


    if ($usuario_id === null) {

        $mensaje =
            "Iniciá sesión para poder reservar.";

        $tipoMensaje = "bad";

    } elseif ($actividad_id <= 0) {

        $mensaje =
            "Seleccioná una atracción disponible.";

        $tipoMensaje = "bad";

    } elseif ($horario === "") {

        $mensaje =
            "Seleccioná un horario.";

        $tipoMensaje = "bad";

    } elseif (
        $edad <= 0 ||
        $altura <= 0 ||
        $peso <= 0
    ) {

        $mensaje =
            "Completá edad, altura y peso.";

        $tipoMensaje = "bad";

    } else {


        /* =============================================
           BUSCAR ACTIVIDAD
        ============================================= */

        $sqlActividad = "
            SELECT *
            FROM actividades
            WHERE id = $actividad_id
            AND lugar_id = 3
            LIMIT 1
        ";


        $resultadoActividad =
            mysqli_query(
                $conexion,
                $sqlActividad
            );


        if (!$resultadoActividad) {

            $mensaje =
                "Error al consultar la atracción.";

            $tipoMensaje = "bad";

        } else {

            $actividad =
                mysqli_fetch_assoc(
                    $resultadoActividad
                );


            if (!$actividad) {

                $mensaje =
                    "La atracción seleccionada no existe.";

                $tipoMensaje = "bad";

            } else {


                /* =====================================
                   ESTADO
                ===================================== */

                if (
                    $actividad["estado"] !== "disponible" &&
                    $actividad["estado"] !== "poca"
                ) {

                    $mensaje =
                        "La atracción \"" .
                        $actividad["nombre"] .
                        "\" no se encuentra disponible.";

                    $tipoMensaje = "bad";
                }


                /* =====================================
                   CUPOS
                ===================================== */

                elseif (
                    $actividad["cupos_disponibles"] <= 0
                ) {

                    $mensaje =
                        "\"" .
                        $actividad["nombre"] .
                        "\" no tiene cupos disponibles.";

                    $tipoMensaje = "bad";
                }


                /* =====================================
                   EDAD
                ===================================== */

                elseif (
                    $actividad["edad_minima"] !== null &&
                    $edad < $actividad["edad_minima"]
                ) {

                    $mensaje =
                        "No cumplís con el requisito de edad mínima de " .
                        $actividad["edad_minima"] .
                        " años.";

                    $tipoMensaje = "bad";
                }


                else {


                    /* =================================
                       REQUISITOS
                    ================================= */

                    $problemas = [];


                    if (
                        $actividad["nombre"] ===
                        "Tirolesa"
                    ) {

                        if ($altura < 140) {

                            $problemas[] =
                                "altura mínima de 140 cm";
                        }

                        if (
                            $peso < 35 ||
                            $peso > 110
                        ) {

                            $problemas[] =
                                "peso entre 35 y 110 kg";
                        }
                    }


                    elseif (
                        $actividad["nombre"] ===
                        "Escalada"
                    ) {

                        if ($altura < 120) {

                            $problemas[] =
                                "altura mínima de 120 cm";
                        }

                        if (
                            $peso < 25 ||
                            $peso > 130
                        ) {

                            $problemas[] =
                                "peso entre 25 y 130 kg";
                        }
                    }


                    elseif (
                        $actividad["nombre"] ===
                        "Puentes colgantes"
                    ) {

                        if ($altura < 130) {

                            $problemas[] =
                                "altura mínima de 130 cm";
                        }

                        if (
                            $peso < 30 ||
                            $peso > 120
                        ) {

                            $problemas[] =
                                "peso entre 30 y 120 kg";
                        }
                    }


                    elseif (
                        $actividad["nombre"] ===
                        "Circuito de obstáculos"
                    ) {

                        if ($altura < 150) {

                            $problemas[] =
                                "altura mínima de 150 cm";
                        }

                        if (
                            $peso < 40 ||
                            $peso > 115
                        ) {

                            $problemas[] =
                                "peso entre 40 y 115 kg";
                        }
                    }


                    if (count($problemas) > 0) {

                        $mensaje =
                            "No podés reservar esta atracción porque no cumplís con los requisitos: " .
                            implode(", ", $problemas) .
                            ".";

                        $tipoMensaje = "bad";

                    } else {


                        /* =============================
                           HORARIOS
                        ============================= */

                        $horariosValidos =
                            explode(
                                " / ",
                                $actividad["horario"]
                            );


                        if (
                            count($horariosValidos) === 1 &&
                            strpos(
                                $actividad["horario"],
                                "a"
                            ) !== false
                        ) {

                            $horariosValidos = [
                                $horario
                            ];
                        }


                        $horarioValido = false;


                        foreach (
                            $horariosValidos
                            as $h
                        ) {

                            if (
                                trim($h) ===
                                $horario
                            ) {

                                $horarioValido = true;
                            }
                        }


                        if (!$horarioValido) {

                            $mensaje =
                                "El horario seleccionado no está disponible para esta atracción.";

                            $tipoMensaje = "bad";

                        } else {


                            /* =========================
                               ACTUALIZAR CUPO
                            ========================= */

                            $actualizar =
                                mysqli_query(
                                    $conexion,
                                    "
                                    UPDATE actividades
                                    SET cupos_disponibles =
                                        cupos_disponibles - 1
                                    WHERE id = $actividad_id
                                    AND cupos_disponibles > 0
                                    "
                                );


                            if (!$actualizar) {

                                $mensaje =
                                    "No se pudo actualizar el cupo.";

                                $tipoMensaje = "bad";

                            } else {


                                /* =========================
                                   GENERAR CÓDIGO
                                ========================= */

                                $codigoReserva =
                                    "AVT-" .
                                    rand(1000, 9999);


                                $horaBD =
                                    $horario . ":00";


                                $fecha =
                                    date("Y-m-d");


                                $total =
                                    floatval(
                                        $actividad["precio"]
                                    );


                                /* =========================
                                   INSERTAR RESERVA
                                ========================= */

                                $insertar =
                                    mysqli_query(
                                        $conexion,
                                        "
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
                                            $actividad_id,
                                            NULL,
                                            3,
                                            '$fecha',
                                            '$horaBD',
                                            1,
                                            $total,
                                            'confirmada',
                                            '$codigoReserva'
                                        )
                                        "
                                    );


                                if (!$insertar) {

                                    /*
                                     * Si falló la reserva,
                                     * devolvemos el cupo.
                                     */

                                    mysqli_query(
                                        $conexion,
                                        "
                                        UPDATE actividades
                                        SET cupos_disponibles =
                                            cupos_disponibles + 1
                                        WHERE id = $actividad_id
                                        "
                                    );


                                    $mensaje =
                                        "No se pudo registrar la reserva.";

                                    $tipoMensaje = "bad";

                                } else {

                                    $reservaConfirmada =
                                        true;

                                    $nombreReserva =
                                        $actividad["nombre"];

                                    $horarioReserva =
                                        $horario;

                                    $mensaje =
                                        "Reserva realizada correctamente.";

                                    $tipoMensaje = "ok";
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}


/* =====================================================
   CARGAR ATRACCIONES
===================================================== */

$atracciones = [];


$resultado =
    mysqli_query(
        $conexion,
        "
        SELECT *
        FROM actividades
        WHERE lugar_id = 3
        ORDER BY id ASC
        "
    );


if ($resultado) {

    while (
        $fila =
        mysqli_fetch_assoc($resultado)
    ) {

        $atracciones[] = $fila;
    }
}


/* =====================================================
   USUARIO ACTUAL
===================================================== */

$usuarioActual =
    $_SESSION["usuario"] ?? "";

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Laguna Experience · Parque de Aventura</title>


<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
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

    background:
        linear-gradient(
            180deg,
            #fffdf7 0%,
            #fdf3e3 100%
        );

    color: var(--turq-deep);

    font-family: var(--font-body);

    line-height: 1.6;
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

    padding:
        80px 8%;

    display: flex;

    align-items: center;

    overflow: hidden;

    color: white;

    background:
        linear-gradient(
            120deg,
            rgba(10, 94, 108, .98),
            rgba(14, 135, 154, .90)
        );
}


header::before {

    content: "";

    position: absolute;

    width: 430px;
    height: 430px;

    border-radius: 50%;

    right: -130px;
    top: -150px;

    background:
        rgba(255,255,255,.08);
}


header::after {

    content: "";

    position: absolute;

    width: 280px;
    height: 280px;

    border-radius: 50%;

    left: -100px;
    bottom: -130px;

    background:
        rgba(255,255,255,.07);
}


.header-content {

    position: relative;

    z-index: 2;

    max-width: 800px;
}


.eyebrow {

    margin:
        0 0 18px;

    font-size: 13px;

    font-weight: 700;

    letter-spacing: .13em;

    text-transform: uppercase;

    opacity: .82;
}


header h1 {

    margin: 0;

    font-family: var(--font-display);

    font-size: clamp(
        52px,
        8vw,
        100px
    );

    line-height: .98;

    letter-spacing: -.055em;
}


header .sub {

    max-width: 720px;

    margin:
        28px 0 30px;

    font-size: 18px;

    line-height: 1.7;

    color:
        rgba(255,255,255,.88);
}


.back-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 48px;

    padding:
        0 20px;

    border-radius: 14px;

    background: white;

    color: var(--turq-deep);

    text-decoration: none;

    font-size: 14px;

    font-weight: 700;

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}


.back-button:hover {

    transform: translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(0,0,0,.12);
}


/* =====================================================
   MAIN
===================================================== */

main {

    width: 100%;

    max-width: 1250px;

    margin:
        -45px auto 80px;

    padding:
        0 5%;

    position: relative;

    z-index: 3;
}


/* =====================================================
   INFO
===================================================== */

.info-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 16px;

    margin-bottom: 55px;
}


.info-card {

    min-height: 145px;

    padding: 25px;

    border:
        1px solid
        var(--line);

    border-radius: 18px;

    background: var(--paper);

    box-shadow: var(--shadow);
}


.info-card .number {

    margin-bottom: 7px;

    font-family: var(--font-display);

    font-size: 36px;

    line-height: 1;

    font-weight: 800;

    color: var(--turq);
}


.info-card .label {

    color: #71594a;

    font-size: 14px;

    font-weight: 600;
}


/* =====================================================
   SECTIONS
===================================================== */

section {
    margin-bottom: 70px;
}


section h2 {

    margin:
        0 0 10px;

    font-family: var(--font-display);

    font-size: 34px;

    line-height: 1.15;

    letter-spacing: -.035em;

    color: var(--turq-deep);
}


.section-intro {

    max-width: 760px;

    margin:
        0 0 30px;

    color: #71594a;

    font-size: 16px;
}


/* =====================================================
   STEPS
===================================================== */

.steps {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 16px;
}


.step {

    padding: 26px;

    min-height: 220px;

    border:
        1px solid
        var(--line);

    border-radius: 18px;

    background: var(--paper);

    box-shadow:
        0 12px 35px
        rgba(20,60,65,.07);
}


.step-number {

    width: 42px;
    height: 42px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin-bottom: 22px;

    border-radius: 12px;

    background: var(--turq-light);

    color: var(--turq-deep);

    font-weight: 800;
}


.step h3 {

    margin:
        0 0 9px;

    font-family: var(--font-display);

    font-size: 18px;

    color: var(--turq-deep);
}


.step p {

    margin: 0;

    color: #71594a;

    font-size: 14px;

    line-height: 1.7;
}


/* =====================================================
   ATRACCIONES
===================================================== */

.attractions {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;
}


.attraction {

    position: relative;

    padding: 28px;

    border:
        1px solid
        var(--line);

    border-radius: 20px;

    background: var(--paper);

    box-shadow:
        0 15px 40px
        rgba(20,60,65,.08);

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}


.attraction:hover {

    transform: translateY(-4px);

    box-shadow:
        0 22px 50px
        rgba(20,60,65,.12);
}


.status {

    display: inline-flex;

    padding:
        6px 11px;

    border-radius: 999px;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .06em;
}


.status.available {

    background: #e1f3ec;

    color: var(--ok);
}


.status.unavailable {

    background: var(--coral-light);

    color: var(--coral);
}


.attraction h3 {

    margin:
        18px 0 8px;

    font-family: var(--font-display);

    font-size: 25px;

    color: var(--turq-deep);
}


.attraction-description {

    min-height: 55px;

    margin:
        0 20px 22px 0;

    color: #71594a;

    font-size: 14px;
}


.tags {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    margin-bottom: 24px;
}


.tag {

    padding:
        7px 10px;

    border-radius: 9px;

    background: var(--sand);

    color: #71594a;

    font-size: 12px;

    font-weight: 600;
}


.attraction-footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding-top: 18px;

    border-top:
        1px solid
        var(--line);
}


.cupos {

    color: #71594a;

    font-size: 13px;

    font-weight: 700;
}


.secondary {

    min-height: 42px;

    padding:
        0 17px;

    border:
        1px solid
        var(--turq);

    border-radius: 12px;

    background: transparent;

    color: var(--turq);

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

    transition:
        background .2s ease,
        color .2s ease;
}


.secondary:hover:not(:disabled) {

    background: var(--turq);

    color: white;
}


.secondary:disabled {

    cursor: not-allowed;

    opacity: .45;
}


/* =====================================================
   RESERVA
===================================================== */

.reservation-box {

    padding: 32px;

    border:
        1px solid
        var(--line);

    border-radius: 22px;

    background: var(--paper);

    box-shadow: var(--shadow);
}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;
}


.form-group {

    display: flex;

    flex-direction: column;

    gap: 8px;
}


.form-group label {

    color: var(--turq-deep);

    font-size: 13px;

    font-weight: 800;
}


.form-group input,
.form-group select {

    width: 100%;

    min-height: 48px;

    padding:
        0 14px;

    border:
        1px solid
        var(--line);

    border-radius: 12px;

    outline: none;

    background: white;

    color: var(--turq-deep);

    font-size: 14px;
}


.form-group input:focus,
.form-group select:focus {

    border-color: var(--turq);

    box-shadow:
        0 0 0 4px
        rgba(14,135,154,.10);
}


/* =====================================================
   LOGIN
===================================================== */

.login-box {

    margin-top: 24px;

    padding: 24px;

    border:
        1px solid
        var(--line);

    border-radius: 16px;

    background: var(--turq-light);
}


.login-box h3 {

    margin:
        0 0 5px;

    font-family: var(--font-display);

    color: var(--turq-deep);

    font-size: 20px;
}


.login-box p {

    margin:
        0 0 15px;

    color: #71594a;

    font-size: 14px;
}


.login-row {

    display: grid;

    grid-template-columns:
        1fr 1fr auto;

    gap: 12px;
}


.login-row input {

    width: 100%;

    min-height: 48px;

    padding:
        0 14px;

    border:
        1px solid
        var(--line);

    border-radius: 12px;

    outline: none;

    background: white;

    color: var(--turq-deep);

    font-size: 14px;
}


.login-row input:focus {

    border-color: var(--turq);

    box-shadow:
        0 0 0 4px
        rgba(14,135,154,.10);
}


.login-button {

    min-height: 48px;

    padding:
        0 20px;

    border: 0;

    border-radius: 12px;

    background: var(--turq);

    color: white;

    font-size: 13px;

    font-weight: 800;

    cursor: pointer;
}


.login-button:hover {

    background: var(--turq-deep);
}


/* =====================================================
   PRIMARY
===================================================== */

.primary {

    width: 100%;

    min-height: 52px;

    border: 0;

    border-radius: 13px;

    background: var(--turq);

    color: white;

    font-size: 14px;

    font-weight: 800;

    cursor: pointer;

    transition:
        background .2s ease,
        transform .2s ease;
}


.primary:hover {

    background: var(--turq-deep);

    transform: translateY(-2px);
}


/* =====================================================
   MENSAJES
===================================================== */

.msg {

    margin-top: 18px;

    padding:
        15px 17px;

    border-radius: 13px;

    font-size: 14px;

    font-weight: 600;
}


.msg.ok {

    background: #e1f3ec;

    border:
        1px solid
        #b9dfcc;

    color: var(--ok);
}


.msg.bad {

    background: var(--coral-light);

    border:
        1px solid
        #efc6b8;

    color: var(--coral);
}


/* =====================================================
   CONFIRMACION
===================================================== */

.confirmation {

    margin-top: 20px;

    padding: 26px;

    border:
        1px solid
        #b9dfcc;

    border-radius: 17px;

    background: #e1f3ec;
}


.confirmation h3 {

    margin:
        0 0 8px;

    font-family: var(--font-display);

    color: var(--ok);
}


.confirmation p {

    margin:
        0 0 18px;

    color: #38604c;

    font-size: 14px;
}


.confirmation-code {

    display: inline-flex;

    align-items: center;

    min-height: 45px;

    padding:
        0 17px;

    border-radius: 11px;

    background: white;

    color: var(--turq-deep);

    font-family: var(--font-display);

    font-weight: 800;

    letter-spacing: .08em;
}


/* =====================================================
   BEFORE
===================================================== */

.before-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 16px;
}


.before-card {

    padding: 25px;

    border:
        1px solid
        var(--line);

    border-radius: 17px;

    background: var(--paper);

    box-shadow:
        0 10px 30px
        rgba(20,60,65,.06);
}


.before-card h3 {

    margin:
        0 0 9px;

    font-family: var(--font-display);

    font-size: 18px;

    color: var(--turq-deep);
}


.before-card p {

    margin: 0;

    color: #71594a;

    font-size: 14px;

    line-height: 1.7;
}


/* =====================================================
   FOOTER
===================================================== */

footer {

    padding:
        35px 5%;

    border-top:
        1px solid
        var(--line);

    background: var(--paper);

    color: #71594a;

    text-align: center;

    font-size: 13px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1000px) {

    .info-grid {

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

    .login-row {

        grid-template-columns: 1fr;
    }
}


@media (max-width: 750px) {

    header {

        min-height: 470px;

        padding:
            70px 7%;
    }

    header h1 {

        font-size: 58px;
    }

    main {

        margin-top: -30px;

        padding:
            0 4%;
    }

    .attractions {

        grid-template-columns: 1fr;
    }

    .form-grid {

        grid-template-columns: 1fr;
    }

    .before-grid {

        grid-template-columns: 1fr;
    }

    .reservation-box {

        padding: 22px;
    }
}


@media (max-width: 500px) {

    header {

        padding:
            55px 6%;
    }

    header h1 {

        font-size: 47px;
    }

    header .sub {

        font-size: 15px;
    }

    .info-grid {

        grid-template-columns: 1fr;
    }

    .steps {

        grid-template-columns: 1fr;
    }

    section h2 {

        font-size: 28px;
    }

    .attraction-footer {

        align-items: flex-start;

        flex-direction: column;
    }

    .secondary {

        width: 100%;
    }
}

</style>

</head>


<body>


<header>

    <div class="header-content">

        <p class="eyebrow">
            Laguna Experience · Aventura
        </p>

        <h1>
            Parque de Aventura
        </h1>

        <p class="sub">
            Viví experiencias de altura, desafío y naturaleza con
            tirolesa, escalada, puentes colgantes y circuitos de obstáculos.
            Cada actividad cuenta con requisitos específicos de seguridad.
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


<div class="info-grid">

    <div class="info-card">

        <div class="number">
            <?= count($atracciones) ?>
        </div>

        <div class="label">
            Atracciones
        </div>

    </div>


    <div class="info-card">

        <div class="number">
            <?= count($atracciones) ?>
        </div>

        <div class="label">
            Experiencias diferentes
        </div>

    </div>


    <div class="info-card">

        <div class="number">
            8+
        </div>

        <div class="label">
            Edad mínima según actividad
        </div>

    </div>


    <div class="info-card">

        <div class="number">
            100%
        </div>

        <div class="label">
            Equipamiento de seguridad
        </div>

    </div>

</div>


<section>

    <h2>
        ¿Cómo funciona?
    </h2>

    <p class="section-intro">
        Elegí una atracción, verificá que cumplas sus requisitos
        y reservá el horario que prefieras.
    </p>


    <div class="steps">


        <div class="step">

            <div class="step-number">
                1
            </div>

            <h3>
                Elegí una atracción
            </h3>

            <p>
                Explorá las diferentes experiencias del Parque
                de Aventura y elegí la que quieras realizar.
            </p>

        </div>


        <div class="step">

            <div class="step-number">
                2
            </div>

            <h3>
                Revisá los requisitos
            </h3>

            <p>
                Cada actividad tiene requisitos de edad,
                altura y peso.
            </p>

        </div>


        <div class="step">

            <div class="step-number">
                3
            </div>

            <h3>
                Reservá tu horario
            </h3>

            <p>
                Seleccioná el horario disponible y completá
                tus datos para generar la reserva.
            </p>

        </div>


        <div class="step">

            <div class="step-number">
                4
            </div>

            <h3>
                Disfrutá la experiencia
            </h3>

            <p>
                Presentate en el parque y recibí las
                indicaciones correspondientes.
            </p>

        </div>


    </div>

</section>


<section id="atraccionesSection">

    <h2>
        Experiencias disponibles
    </h2>

    <p class="section-intro">
        Conocé cada atracción antes de reservar.
        Los requisitos pueden variar según la actividad.
    </p>


    <div class="attractions">


<?php foreach ($atracciones as $a): ?>


<?php

$disponible =
    (
        $a["cupos_disponibles"] > 0 &&
        (
            $a["estado"] === "disponible" ||
            $a["estado"] === "poca"
        )
    );


$alturaMin = 120;
$pesoMin = 25;
$pesoMax = 130;


if ($a["nombre"] === "Tirolesa") {

    $alturaMin = 140;
    $pesoMin = 35;
    $pesoMax = 110;

} elseif ($a["nombre"] === "Escalada") {

    $alturaMin = 120;
    $pesoMin = 25;
    $pesoMax = 130;

} elseif ($a["nombre"] === "Puentes colgantes") {

    $alturaMin = 130;
    $pesoMin = 30;
    $pesoMax = 120;

} elseif ($a["nombre"] === "Circuito de obstáculos") {

    $alturaMin = 150;
    $pesoMin = 40;
    $pesoMax = 115;
}

?>


<div class="attraction">


<span class="status <?= $disponible ? "available" : "unavailable" ?>">

<?php

if (!$disponible) {

    if ($a["estado"] === "mantenimiento") {

        echo "Mantenimiento";

    } elseif ($a["cupos_disponibles"] <= 0) {

        echo "Sin cupos";

    } else {

        echo "No disponible";
    }

} else {

    echo "Disponible";
}

?>

</span>


<h3>
    <?= htmlspecialchars($a["nombre"]) ?>
</h3>


<p class="attraction-description">
    <?= htmlspecialchars($a["descripcion"]) ?>
</p>


<div class="tags">

    <span class="tag">

        Edad mínima:
        <?= intval($a["edad_minima"]) ?>

    </span>


    <span class="tag">

        Altura mínima:
        <?= $alturaMin ?> cm

    </span>


    <span class="tag">

        Peso:
        <?= $pesoMin ?>-<?= $pesoMax ?> kg

    </span>

</div>


<div class="attraction-footer">


<span class="cupos">

    <?= intval($a["cupos_disponibles"]) ?>

    /

    <?= intval($a["capacidad"]) ?>

    cupos disponibles

</span>


<button
    class="secondary"
    onclick="seleccionarAtraccion(<?= intval($a["id"]) ?>)"
    <?= !$disponible ? "disabled" : "" ?>
>

    Reservar

</button>


</div>


</div>


<?php endforeach; ?>


    </div>

</section>


<section id="reservaSection">

    <h2>
        Reservá tu experiencia
    </h2>

    <p class="section-intro">
        Seleccioná una atracción y horario.
        Antes de confirmar, el sistema verificará
        automáticamente los requisitos.
    </p>


<div class="reservation-box">


<?php if ($usuarioActual === ""): ?>


<div class="login-box">

    <h3>
        Iniciá sesión para reservar
    </h3>

    <p>
        Ingresá tu usuario o email y contraseña para continuar.
    </p>


    <form method="POST">

        <input
            type="hidden"
            name="accion"
            value="login"
        >


        <div class="login-row">

            <input
                type="text"
                name="loginUsuario"
                placeholder="Usuario o email"
                required
            >

            <input
                type="password"
                name="loginPassword"
                placeholder="Contraseña"
                required
            >

            <button
                type="submit"
                class="login-button"
            >
                Iniciar sesión
            </button>

        </div>


        <?php if ($mensajeLogin !== ""): ?>

        <div class="msg <?= $tipoMensajeLogin ?>">

            <?= htmlspecialchars($mensajeLogin) ?>

        </div>

        <?php endif; ?>

    </form>

</div>


<?php else: ?>


<div class="login-box">

    <h3>
        Sesión iniciada
    </h3>

    <p>
        Estás ingresado como
        <strong>
            <?= htmlspecialchars($usuarioActual) ?>
        </strong>.
    </p>


    <a
        href="parque-aventura.php?logout=1"
        class="secondary"
        style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;"
    >
        Cerrar sesión
    </a>

</div>


<form method="POST">


<div class="form-grid">


<div class="form-group">

<label for="atraccion">
    Atracción
</label>


<select
    name="actividad_id"
    id="atraccion"
    required
>


<?php foreach ($atracciones as $a): ?>


<?php

$disponible =
    (
        $a["cupos_disponibles"] > 0 &&
        (
            $a["estado"] === "disponible" ||
            $a["estado"] === "poca"
        )
    );

?>


<option
    value="<?= intval($a["id"]) ?>"
    <?= !$disponible ? "disabled" : "" ?>
>

<?= htmlspecialchars($a["nombre"]) ?>

<?php

if (!$disponible) {

    echo " (no disponible)";

} else {

    echo " (" .
        intval($a["cupos_disponibles"]) .
        " cupos)";
}

?>

</option>


<?php endforeach; ?>


</select>

</div>


<div class="form-group">

<label for="horario">
    Horario
</label>


<select
    name="horario"
    id="horario"
    required
>

</select>

</div>


<div class="form-group">

<label for="edad">
    Edad
</label>


<input
    type="number"
    name="edad"
    id="edad"
    min="1"
    max="99"
    placeholder="Ej. 18"
    required
>

</div>


<div class="form-group">

<label for="altura">
    Altura (cm)
</label>


<input
    type="number"
    name="altura"
    id="altura"
    min="80"
    max="220"
    placeholder="Ej. 170"
    required
>

</div>


<div class="form-group">

<label for="peso">
    Peso (kg)
</label>


<input
    type="number"
    name="peso"
    id="peso"
    min="10"
    max="200"
    placeholder="Ej. 70"
    required
>

</div>


</div>


<button
    type="submit"
    name="reservar"
    class="primary"
    style="margin-top:18px;"
>
    Confirmar reserva
</button>


<?php if ($mensaje !== ""): ?>

<div class="msg <?= $tipoMensaje ?>">

    <?= htmlspecialchars($mensaje) ?>

</div>

<?php endif; ?>


<?php if ($reservaConfirmada): ?>


<div class="confirmation">

    <h3>
        Reserva confirmada
    </h3>


    <p>

        <?= htmlspecialchars($usuarioActual) ?>,

        reservaste

        "<?= htmlspecialchars($nombreReserva) ?>"

        para las

        <?= htmlspecialchars($horarioReserva) ?>.

        Presentate con anticipación para realizar
        la preparación y recibir las indicaciones
        de seguridad.

    </p>


    <div class="confirmation-code">

        <?= htmlspecialchars($codigoReserva) ?>

    </div>

</div>


<?php endif; ?>


</form>


<?php endif; ?>


</div>

</section>


<section>

<h2>
    Antes de participar
</h2>


<p class="section-intro">
    Algunas recomendaciones para disfrutar
    la experiencia de forma segura y cómoda.
</p>


<div class="before-grid">


<div class="before-card">

<h3>
    Ropa cómoda
</h3>

<p>
    Usá ropa que te permita moverte con libertad
    y calzado cerrado y cómodo.
</p>

</div>


<div class="before-card">

<h3>
    Condiciones climáticas
</h3>

<p>
    Algunas actividades pueden suspenderse temporalmente
    ante condiciones climáticas desfavorables.
</p>

</div>


<div class="before-card">

<h3>
    Seguridad
</h3>

<p>
    Antes de comenzar recibirás las indicaciones
    necesarias y el equipamiento correspondiente.
</p>

</div>


<div class="before-card">

<h3>
    Llegá con anticipación
</h3>

<p>
    Recomendamos llegar unos minutos antes del horario
    reservado.
</p>

</div>


<div class="before-card">

<h3>
    Requisitos
</h3>

<p>
    La edad, altura y peso permitidos dependen
    de la atracción seleccionada.
</p>

</div>


<div class="before-card">

<h3>
    Disfrutá responsablemente
</h3>

<p>
    Seguí siempre las instrucciones del personal
    durante toda la actividad.
</p>

</div>


</div>

</section>


</main>


<footer>

    Laguna Experience — Parque de Aventura

</footer>


<script>

const atracciones =
    <?= json_encode(
        $atracciones,
        JSON_UNESCAPED_UNICODE
    ) ?>;


function seleccionarAtraccion(id) {

    const select =
        document.getElementById("atraccion");

    if (!select) {
        return;
    }

    select.value = id;

    actualizarHorarios();

    document
        .getElementById("reservaSection")
        .scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
}


function actualizarHorarios() {

    const selectAtraccion =
        document.getElementById("atraccion");

    const selectHorario =
        document.getElementById("horario");


    if (!selectAtraccion || !selectHorario) {
        return;
    }


    const id =
        Number(selectAtraccion.value);


    const atraccion =
        atracciones.find(
            a => Number(a.id) === id
        );


    if (!atraccion) {

        selectHorario.innerHTML =
            `<option value="">
                Sin horarios disponibles
            </option>`;

        return;
    }


    let horarios =
        atraccion.horario
            .split("/")
            .map(
                h => h.trim()
            );


    if (
        horarios.length === 1 &&
        !horarios[0].match(
            /^\d{1,2}:\d{2}$/
        )
    ) {

        selectHorario.innerHTML =
            `<option value="10:00">
                ${horarios[0]}
            </option>`;

        return;
    }


    selectHorario.innerHTML =
        horarios
            .map(
                h =>
                    `<option value="${h}">
                        ${h}
                    </option>`
            )
            .join("");
}


const selectAtraccion =
    document.getElementById("atraccion");


if (selectAtraccion) {

    selectAtraccion.addEventListener(
        "change",
        actualizarHorarios
    );

    actualizarHorarios();
}

</script>


</body>

</html>