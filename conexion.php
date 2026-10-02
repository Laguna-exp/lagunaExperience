<?php

$conexion = mysqli_connect(
    "localhost",
    "root",
    "",
    "laguna_experience"
);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");