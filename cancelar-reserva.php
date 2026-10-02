<?php

session_start();

require_once "conexion.php";


/* =========================================================
   VERIFICAR SESIÓN
========================================================= */

if (!isset($_SESSION["usuario_id"])) {

    header("Location: login.php");
    exit;
}


$usuarioId =
    intval(
        $_SESSION["usuario_id"]
    );


/* =========================================================
   DATOS RECIBIDOS
========================================================= */

$reservaId =
    isset(
        $_POST["reserva_id"]
    )
    ? intval(
        $_POST["reserva_id"]
    )
    : 0;


$tipo =
    $_POST["tipo"]
    ?? "";


if (
    $reservaId <= 0 ||
    $tipo == ""
) {

    header(
        "Location: mis-reservas.php"
    );

    exit;
}


/* =========================================================
   RESERVAS GENERALES
========================================================= */

if (
    $tipo == "reserva"
) {

    $sql = "
        UPDATE reservas

        SET estado = 'cancelada'

        WHERE id = $reservaId

        AND usuario_id = $usuarioId

        AND estado != 'cancelada'
    ";


    mysqli_query(
        $conexion,
        $sql
    );


    header(
        "Location: mis-reservas.php"
    );

    exit;
}


/* =========================================================
   SECTOR INFANTIL
========================================================= */

if (
    $tipo == "infantil"
) {

    $sql = "
        DELETE FROM infantil_reservas

        WHERE id = $reservaId

        AND usuario_id = $usuarioId
    ";


    mysqli_query(
        $conexion,
        $sql
    );


    header(
        "Location: mis-reservas.php"
    );

    exit;
}


/* =========================================================
   GLAMPING
========================================================= */

if (
    $tipo == "glamping"
) {

    $sql = "
        DELETE FROM glamping_reservas

        WHERE id = $reservaId

        AND usuario_id = $usuarioId
    ";


    mysqli_query(
        $conexion,
        $sql
    );


    header(
        "Location: mis-reservas.php"
    );

    exit;
}


/* =========================================================
   RESTAURANTE
========================================================= */

if (
    $tipo == "restaurante"
) {

    $nombreUsuario =
        $_SESSION["usuario"]
        ?? "";


    $nombreUsuario =
        mysqli_real_escape_string(
            $conexion,
            $nombreUsuario
        );


    $sql = "
        UPDATE restaurante_reservas

        SET estado = 'cancelada'

        WHERE id = $reservaId

        AND usuario = '$nombreUsuario'

        AND estado != 'cancelada'
    ";


    mysqli_query(
        $conexion,
        $sql
    );


    header(
        "Location: mis-reservas.php"
    );

    exit;
}


/* =========================================================
   SPA
========================================================= */

if (
    $tipo == "spa"
) {

    $resultadoColumnas =
        mysqli_query(
            $conexion,
            "SHOW COLUMNS FROM spa_reservas"
        );


    $columnas = [];


    if ($resultadoColumnas) {

        while (
            $columna =
                mysqli_fetch_assoc(
                    $resultadoColumnas
                )
        ) {

            $columnas[] =
                $columna["Field"];
        }
    }


    $columnaUsuario = "";


    if (
        in_array(
            "user_id",
            $columnas
        )
    ) {

        $columnaUsuario =
            "user_id";
    }

    elseif (
        in_array(
            "usuario_id",
            $columnas
        )
    ) {

        $columnaUsuario =
            "usuario_id";
    }

    elseif (
        in_array(
            "id_usuario",
            $columnas
        )
    ) {

        $columnaUsuario =
            "id_usuario";
    }


    $columnaEstado = "";


    if (
        in_array(
            "status",
            $columnas
        )
    ) {

        $columnaEstado =
            "status";
    }

    elseif (
        in_array(
            "estado",
            $columnas
        )
    ) {

        $columnaEstado =
            "estado";
    }


    if (
        $columnaUsuario != "" &&
        $columnaEstado != ""
    ) {

        $sql = "
            UPDATE spa_reservas

            SET `$columnaEstado` = 'cancelada'

            WHERE id = $reservaId

            AND `$columnaUsuario` = $usuarioId
        ";


        mysqli_query(
            $conexion,
            $sql
        );

    }

    elseif (
        $columnaUsuario != ""
    ) {

        $sql = "
            DELETE FROM spa_reservas

            WHERE id = $reservaId

            AND `$columnaUsuario` = $usuarioId
        ";


        mysqli_query(
            $conexion,
            $sql
        );
    }


    header(
        "Location: mis-reservas.php"
    );

    exit;
}


/* =========================================================
   MUNDO COOKIE
========================================================= */

if (
    $tipo == "mundo_cookie"
) {

    $resultadoColumnas =
        mysqli_query(
            $conexion,
            "SHOW COLUMNS FROM mc_reservas_taller"
        );


    $columnas = [];


    if ($resultadoColumnas) {

        while (
            $columna =
                mysqli_fetch_assoc(
                    $resultadoColumnas
                )
        ) {

            $columnas[] =
                $columna["Field"];
        }
    }


    $columnaUsuario = "";


    if (
        in_array(
            "usuario_id",
            $columnas
        )
    ) {

        $columnaUsuario =
            "usuario_id";
    }

    elseif (
        in_array(
            "user_id",
            $columnas
        )
    ) {

        $columnaUsuario =
            "user_id";
    }


    $columnaEstado = "";


    if (
        in_array(
            "estado",
            $columnas
        )
    ) {

        $columnaEstado =
            "estado";
    }


    if (
        $columnaUsuario != "" &&
        $columnaEstado != ""
    ) {

        $sql = "
            UPDATE mc_reservas_taller

            SET `$columnaEstado` = 'cancelada'

            WHERE id = $reservaId

            AND `$columnaUsuario` = $usuarioId
        ";


        mysqli_query(
            $conexion,
            $sql
        );

    }

    elseif (
        $columnaUsuario != ""
    ) {

        $sql = "
            DELETE FROM mc_reservas_taller

            WHERE id = $reservaId

            AND `$columnaUsuario` = $usuarioId
        ";


        mysqli_query(
            $conexion,
            $sql
        );
    }


    header(
        "Location: mis-reservas.php"
    );

    exit;
}


/* =========================================================
   VOLVER
========================================================= */

header(
    "Location: mis-reservas.php"
);

exit;

?>