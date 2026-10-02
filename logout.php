<?php

session_start();

/* Eliminar todas las variables de sesión */
$_SESSION = [];

/* Destruir la sesión */
session_destroy();

/* Volver al inicio */
header("Location: index.php");

exit;

?>