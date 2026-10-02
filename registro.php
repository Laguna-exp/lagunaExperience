<?php

require_once "conexion.php";

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirmar = trim($_POST["confirmar"]);

    if ($nombre == "" || $email == "" || $password == "" || $confirmar == "") {

        $error = "Completá todos los campos.";

    } elseif ($password != $confirmar) {

        $error = "Las contraseñas no coinciden.";

    } else {

        $consulta = mysqli_query(
            $conexion,
            "SELECT id FROM usuarios WHERE email = '$email'"
        );

        if (mysqli_num_rows($consulta) > 0) {

            $error = "Ya existe una cuenta con ese email.";

        } else {

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios
                    (nombre, email, password_hash, rol, activo, creado_en)
                    VALUES
                    ('$nombre', '$email', '$passwordHash', 'visitante', 1, NOW())";

            if (mysqli_query($conexion, $sql)) {

                $mensaje = "Cuenta creada correctamente. Ya podés iniciar sesión.";

            } else {

                $error = "No se pudo crear la cuenta.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrarse - Laguna Experience</title>

    <style>

        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap');

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
            --shadow: 0 24px 70px rgba(13, 56, 41, .12);
            --font-body: "DM Sans", sans-serif;
            --font-display: "Manrope", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
            background:
                radial-gradient(circle at top left, rgba(130, 216, 167, .25), transparent 35%),
                linear-gradient(135deg, var(--cream), var(--sand));
            color: var(--ink);
            font-family: var(--font-body);
        }

        .registro {
            width: 430px;
            max-width: 100%;
            padding: 38px;
            background: rgba(255, 255, 255, .92);
            border: 1px solid rgba(23, 74, 54, .08);
            border-radius: 28px;
            box-shadow: var(--shadow);
        }

        .logo {
            margin-bottom: 28px;
            text-align: center;
            color: var(--ink-deep);
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -.4px;
        }

        .kicker {
            margin-bottom: 8px;
            color: var(--water-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.8px;
            text-align: center;
        }

        h1 {
            margin: 0;
            color: var(--ink-deep);
            font-family: var(--font-display);
            font-size: 32px;
            line-height: 1.1;
            text-align: center;
            letter-spacing: -1px;
        }

        .descripcion {
            margin: 12px 0 28px;
            color: rgba(23, 74, 54, .68);
            font-size: 14px;
            line-height: 1.6;
            text-align: center;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            color: var(--ink-deep);
            font-size: 13px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            outline: none;
            background: #ffffff;
            color: var(--ink);
            font-family: var(--font-body);
            font-size: 14px;
            transition: .2s ease;
        }

        input:focus {
            border-color: rgba(54, 166, 111, .65);
            box-shadow: 0 0 0 4px rgba(130, 216, 167, .18);
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 14px 18px;
            border: none;
            border-radius: 14px;
            background: var(--water);
            color: var(--white);
            font-family: var(--font-body);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s ease;
            box-shadow: 0 12px 25px rgba(54, 166, 111, .22);
        }

        button:hover {
            background: var(--water-dark);
            transform: translateY(-1px);
        }

        .error {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid rgba(160, 0, 0, .12);
            border-radius: 12px;
            background: #fff0f0;
            color: #9b2929;
            font-size: 13px;
            text-align: center;
        }

        .mensaje {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid rgba(23, 107, 53, .12);
            border-radius: 12px;
            background: #edf9f0;
            color: #176b35;
            font-size: 13px;
            text-align: center;
        }

        .login {
            margin-top: 24px;
            padding-top: 22px;
            border-top: 1px solid var(--line);
            color: rgba(23, 74, 54, .65);
            font-size: 13px;
            text-align: center;
        }

        .login a {
            color: var(--water-dark);
            font-weight: 800;
            text-decoration: none;
        }

        .login a:hover {
            text-decoration: underline;
        }

        .volver {
            display: block;
            margin-top: 18px;
            color: rgba(23, 74, 54, .65);
            font-size: 13px;
            text-align: center;
            text-decoration: none;
        }

        .volver:hover {
            color: var(--water-dark);
        }

        @media (max-width: 500px) {

            body {
                padding: 20px 14px;
            }

            .registro {
                padding: 30px 22px;
                border-radius: 22px;
            }

            h1 {
                font-size: 28px;
            }

        }

    </style>

</head>

<body>

    <div class="registro">

        <div class="logo">
            Laguna Experience
        </div>

        <div class="kicker">
            LAGUNA EXPERIENCE
        </div>

        <h1>
            Crear cuenta
        </h1>

        <p class="descripcion">
            Registrate para reservar tus experiencias.
        </p>

        <?php if ($error != "") { ?>

            <div class="error">
                <?php echo $error; ?>
            </div>

        <?php } ?>

        <?php if ($mensaje != "") { ?>

            <div class="mensaje">
                <?php echo $mensaje; ?>
            </div>

        <?php } ?>

        <form method="POST">

            <label>
                Nombre
            </label>

            <input
                type="text"
                name="nombre"
                required
            >

            <label>
                Email
            </label>

            <input
                type="email"
                name="email"
                required
            >

            <label>
                Contraseña
            </label>

            <input
                type="password"
                name="password"
                required
            >

            <label>
                Repetir contraseña
            </label>

            <input
                type="password"
                name="confirmar"
                required
            >

            <button type="submit">
                Crear cuenta
            </button>

        </form>

        <div class="login">

            ¿Ya tenés una cuenta?

            <a href="login.php">
                Iniciar sesión
            </a>

        </div>

        <a class="volver" href="index.php">
            ← Volver a Laguna Experience
        </a>

    </div>

</body>

</html>