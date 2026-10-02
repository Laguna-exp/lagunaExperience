<?php

session_start();

require_once "conexion.php";

$error = "";

$volver = "index.php";

if (isset($_GET["volver"]) && $_GET["volver"] != "") {

    $volver = $_GET["volver"];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (isset($_POST["volver"]) && $_POST["volver"] != "") {

        $volver = $_POST["volver"];
    }

    if ($email == "" || $password == "") {

        $error = "Completá todos los campos.";

    } else {

        $email = mysqli_real_escape_string($conexion, $email);

        $consulta = mysqli_query(
            $conexion,
            "SELECT * FROM usuarios
             WHERE email = '$email'
             AND activo = 1"
        );

        if (mysqli_num_rows($consulta) == 1) {

            $usuario = mysqli_fetch_assoc($consulta);

            if (
                password_verify(
                    $password,
                    $usuario["password_hash"]
                )
            ) {

                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["usuario"] = $usuario["nombre"];
                $_SESSION["email"] = $usuario["email"];
                $_SESSION["rol"] = $usuario["rol"];

                if ($usuario["rol"] == "admin") {

                    header("Location: estado-sistema.php");
                    exit;

                }

                header("Location: " . $volver);
                exit;

            } else {

                $error = "Email o contraseña incorrectos.";
            }

        } else {

            $error = "Email o contraseña incorrectos.";
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

    <title>Iniciar sesión - Laguna Experience</title>


    <style>

        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap');


        /* =========================================================
           VARIABLES
        ========================================================= */

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


        /* =========================================================
           RESET
        ========================================================= */

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

            font-family: var(--font-body);

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 40px 20px;

        }


        a {

            color: inherit;

            text-decoration: none;

        }


        /* =========================================================
           CONTENEDOR
        ========================================================= */

        .login-page {

            width: 100%;

            max-width: 480px;

        }


        /* =========================================================
           LOGO
        ========================================================= */

        .login-logo {

            margin-bottom: 25px;

            text-align: center;

        }


        .login-logo a {

            color: var(--ink);

            font-family: var(--font-display);

            font-size: 25px;

            font-weight: 800;

            letter-spacing: -.04em;

        }


        .login-logo span {

            color: var(--water);

        }


        /* =========================================================
           TARJETA
        ========================================================= */

        .login-card {

            padding: 42px;

            border:

                1px solid

                rgba(23, 74, 54, .10);

            border-radius: 28px;

            background:

                rgba(255, 255, 255, .88);

            box-shadow:

                var(--shadow);

            backdrop-filter:

                blur(16px);

            -webkit-backdrop-filter:

                blur(16px);

        }


        /* =========================================================
           ENCABEZADO
        ========================================================= */

        .login-header {

            margin-bottom: 30px;

            text-align: center;

        }


        .login-kicker {

            margin-bottom: 10px;

            color: var(--water-dark);

            font-size: 11px;

            font-weight: 800;

            letter-spacing: .18em;

            text-transform: uppercase;

        }


        .login-header h1 {

            margin-bottom: 12px;

            color: var(--ink-deep);

            font-family: var(--font-display);

            font-size: 42px;

            font-weight: 800;

            line-height: 1;

            letter-spacing: -.055em;

        }


        .login-header p {

            color:

                rgba(23, 74, 54, .62);

            font-size: 14px;

            line-height: 1.7;

        }


        /* =========================================================
           ERROR
        ========================================================= */

        .error {

            margin-bottom: 22px;

            padding: 13px 15px;

            border:

                1px solid

                rgba(170, 40, 40, .15);

            border-radius: 12px;

            background:

                rgba(255, 220, 220, .65);

            color: #9b2929;

            font-size: 13px;

            text-align: center;

        }


        /* =========================================================
           CAMPOS
        ========================================================= */

        .campo {

            margin-bottom: 20px;

        }


        .campo label {

            display: block;

            margin-bottom: 8px;

            color: var(--ink);

            font-size: 12px;

            font-weight: 700;

        }


        .campo input {

            width: 100%;

            height: 50px;

            padding: 0 16px;

            border:

                1px solid

                var(--line);

            border-radius: 13px;

            outline: none;

            background:

                rgba(237, 243, 232, .55);

            color: var(--ink-deep);

            font-family: var(--font-body);

            font-size: 14px;

            transition:

                border .2s ease,

                box-shadow .2s ease,

                background .2s ease;

        }


        .campo input::placeholder {

            color:

                rgba(23, 74, 54, .38);

        }


        .campo input:focus {

            border-color:

                rgba(54, 166, 111, .65);

            background: white;

            box-shadow:

                0 0 0 4px

                rgba(54, 166, 111, .10);

        }


        /* =========================================================
           BOTÓN
        ========================================================= */

        .login-button {

            width: 100%;

            min-height: 50px;

            margin-top: 5px;

            border: 0;

            border-radius: 14px;

            background:

                var(--ink);

            color: white;

            font-family: var(--font-body);

            font-size: 13px;

            font-weight: 800;

            cursor: pointer;

            box-shadow:

                0 12px 28px

                rgba(13, 56, 41, .16);

            transition:

                transform .25s ease,

                background .25s ease,

                box-shadow .25s ease;

        }


        .login-button:hover {

            background:

                var(--water-dark);

            transform:

                translateY(-2px);

            box-shadow:

                0 18px 35px

                rgba(13, 56, 41, .20);

        }


        /* =========================================================
           REGISTRO
        ========================================================= */

        .registro {

            margin-top: 25px;

            padding-top: 22px;

            border-top:

                1px solid

                var(--line);

            color:

                rgba(23, 74, 54, .58);

            font-size: 13px;

            text-align: center;

        }


        .registro a {

            color: var(--water-dark);

            font-weight: 800;

        }


        .registro a:hover {

            color: var(--water);

        }


        /* =========================================================
           VOLVER
        ========================================================= */

        .volver {

            display: block;

            margin-top: 20px;

            color:

                rgba(23, 74, 54, .50);

            font-size: 12px;

            font-weight: 600;

            text-align: center;

            transition:

                color .2s ease;

        }


        .volver:hover {

            color: var(--water-dark);

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 600px) {

            body {

                padding: 25px 16px;

            }


            .login-card {

                padding: 32px 24px;

                border-radius: 22px;

            }


            .login-header h1 {

                font-size: 36px;

            }


            .login-logo a {

                font-size: 22px;

            }

        }


        @media (max-width: 400px) {

            .login-card {

                padding: 28px 20px;

            }


            .login-header h1 {

                font-size: 32px;

            }

        }

    </style>

</head>


<body>


    <div class="login-page">


        <!-- LOGO -->

        <div class="login-logo">

            <a href="index.php">

                Laguna <span>Experience</span>

            </a>

        </div>


        <!-- LOGIN -->

        <div class="login-card">


            <div class="login-header">

                <div class="login-kicker">

                    LAGUNA EXPERIENCE

                </div>

                <h1>

                    Iniciar sesión

                </h1>

                <p>

                    Ingresá a tu cuenta para continuar
                    disfrutando de la experiencia.

                </p>

            </div>


            <?php if ($error != "") { ?>

                <div class="error">

                    <?php echo $error; ?>

                </div>

            <?php } ?>


            <form method="POST">


                <input
                    type="hidden"
                    name="volver"
                    value="<?php echo htmlspecialchars($volver); ?>"
                >


                <div class="campo">

                    <label for="email">

                        Email

                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Ingresá tu email"
                        required
                    >

                </div>


                <div class="campo">

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

                </div>


                <button
                    type="submit"
                    class="login-button"
                >

                    Iniciar sesión

                </button>


            </form>


            <div class="registro">

                ¿No tenés una cuenta?

                <a href="registro.php">

                    Registrate

                </a>

            </div>


            <a
                href="index.php"
                class="volver"
            >

                ← Volver a Laguna Experience

            </a>


        </div>

    </div>


</body>

</html>
