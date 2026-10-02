<?php

session_start();

$usuarioLogueado = isset($_SESSION["usuario_id"]);

$nombreUsuario = "";

if ($usuarioLogueado) {
    $nombreUsuario = $_SESSION["usuario"] ?? $_SESSION["usuario_nombre"] ?? "";
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

    <title>Laguna Experience</title>

    <link rel="stylesheet" href="styles.css">

</head>

<body>


    <!-- NAVBAR -->

    <nav class="main-navbar">

    <div class="nav-container">

        <a href="#inicio" class="nav-logo">
            Laguna <span>Experience</span>
        </a>

        <div class="nav-links">

            <a href="#inicio">
                Inicio
            </a>

            <a href="#sobre-laguna">
                Sobre Laguna
            </a>

            <a href="#experiencias">
                Experiencias
            </a>

            <a href="#mapa">
                Mapa
            </a>

            <a href="#ubicacion">
                Dónde estamos
            </a>


            <?php if ($usuarioLogueado): ?>

                <!-- MIS RESERVAS -->
                <a href="mis-reservas.php">
                    Mis reservas
                </a>

                <!-- CERRAR SESIÓN -->
                <a href="logout.php">
                    Cerrar sesión
                </a>

                <!-- NOMBRE DEL USUARIO -->
                <span class="usuario-nombre">
                    Hola, <?= htmlspecialchars($nombreUsuario) ?>
                </span>

            <?php else: ?>

                <!-- INICIAR SESIÓN -->
                <a href="login.php">
                    Iniciar sesión
                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>

    


    <!-- HERO -->

    <header class="hero" id="inicio">

        <div class="hero-content">

            <div class="hero-kicker">
                SMART EXPERIENCE PARK
            </div>

            <h1>
                Laguna
                <span>Experience</span>
            </h1>

            <p>
                Un espacio donde naturaleza, entretenimiento,
                gastronomía y tecnología se encuentran.
            </p>

            <a
                class="hero-button"
                href="#experiencias"
            >
                Explorar Laguna Experience →
            </a>

        </div>

    </header>


    <!-- SOBRE LAGUNA -->

    <section
        class="intro-section"
        id="sobre-laguna"
    >

        <div class="section-kicker">
            DESCUBRÍ EL PARQUE
        </div>

        <h2>
            Una experiencia pensada
            para disfrutar
        </h2>

        <p>
            Laguna Experience reúne diferentes propuestas
            para disfrutar de la naturaleza, descansar,
            divertirse y compartir.
        </p>

    </section>


    <!-- EXPERIENCIAS -->

    <main
        class="experiences-section"
        id="experiencias"
    >

        <div class="section-heading">

            <div>

                <div class="section-kicker">
                    EXPERIENCIAS
                </div>

                <h2>
                    Todo lo que podés encontrar
                </h2>

            </div>

            <p>
                Explorá las diferentes propuestas
                de Laguna Experience.
            </p>

        </div>


        <div class="experience-grid">


            <!-- ACTIVIDADES ACUÁTICAS -->

            <a
                class="experience-card reveal"
                href="diezPaginas/actividades-acuaticas.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1530053969600-caed2596d242?auto=format&fit=crop&w=1400&q=85"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=1400&q=85"
                        alt="Actividades acuáticas"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        AVENTURA · AGUA
                    </div>

                    <h3>
                        Actividades Acuáticas
                    </h3>

                    <p>
                        Kayak, actividades en el agua y momentos
                        para disfrutar de la laguna.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- BEACH CLUB -->

            <a
                class="experience-card reveal"
                href="diezPaginas/beach-club.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1572331165267-854da2b10ccc?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1400&q=85"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1572331165267-854da2b10ccc?auto=format&fit=crop&w=1400&q=85"
                        alt="Beach Club"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        RELAX · VERANO
                    </div>

                    <h3>
                        Beach Club
                    </h3>

                    <p>
                        Piscina, bebidas, música y momentos
                        para compartir junto al agua.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- CABAÑAS -->

            <a
                class="experience-card reveal"
                href="diezPaginas/cabanas.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1510798831971-661eb04b3739?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1470770841072-f978cf4d019e?auto=format&fit=crop&w=1400&q=85"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?auto=format&fit=crop&w=1400&q=85"
                        alt="Cabañas"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        ALOJAMIENTO · NATURALEZA
                    </div>

                    <h3>
                        Cabañas
                    </h3>

                    <p>
                        Cabañas rodeadas de naturaleza para
                        descansar y desconectar.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- EVENTOS ESPECIALES -->

            <a
                class="experience-card reveal"
                href="diezPaginas/eventos-especiales.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=1400&q=85"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&fit=crop&w=1400&q=85"
                        alt="Eventos especiales"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        EVENTOS · EXPERIENCIAS
                    </div>

                    <h3>
                        Eventos Especiales
                    </h3>

                    <p>
                        Encuentros, música y propuestas especiales
                        para compartir.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- EXPERIENCIAS Y RECREACIÓN -->

            <a
                class="experience-card reveal"
                href="diezPaginas/experiencias-recreacion.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1544191696-102dbdaeeaa0?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=1400&q=85"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=1400&q=85"
                        alt="Experiencias y recreación"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        RECREACIÓN · NATURALEZA
                    </div>

                    <h3>
                        Experiencias y Recreación
                    </h3>

                    <p>
                        Bicicletas, actividades al aire libre
                        y propuestas para explorar el entorno.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- GLAMPING -->

            <a
                class="experience-card reveal"
                href="diezPaginas/glamping.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1523987355523-c7b5b0dd90a7?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1400&q=85"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1523987355523-c7b5b0dd90a7?auto=format&fit=crop&w=1400&q=85"
                        alt="Glamping"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        ALOJAMIENTO · NATURALEZA
                    </div>

                    <h3>
                        Glamping
                    </h3>

                    <p>
                        Una experiencia diferente que combina
                        comodidad, diseño y naturaleza.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- MUNDO COOKIE -->

            <a
                class="experience-card reveal"
                href="diezPaginas/mundo-cookie.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1558961363-fa8fdf82db35?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1590080875515-8a3a8dc5735e?auto=format&fit=crop&w=1400&q=85"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=1400&q=85"
                        alt="Mundo Cookie"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        GASTRONOMÍA · DULCE
                    </div>

                    <h3>
                        Mundo Cookie
                    </h3>

                    <p>
                        Un espacio dedicado a las cookies,
                        sabores y momentos dulces.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- PARQUE AVENTURA -->

            <a
                class="experience-card reveal"
                href="diezPaginas/parque-aventura.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://www.jungleraiderpark.com/wp-content/uploads/2020/05/Parco-Avventura-JRP-Xtreme-1600x800.jpg",
                        "https://resc.deskline.net/images/RPT/1/a997b364-b691-4f74-8c93-61b43d1e729d/99/image.jpg",
                        "https://images.squarespace-cdn.com/content/v1/62f476b4e336120dc1ece2a6/6d13f599-e7b1-40c2-9f9c-8f2b814ac1cd/DSC05903.jpg"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://www.jungleraiderpark.com/wp-content/uploads/2020/05/Parco-Avventura-JRP-Xtreme-1600x800.jpg"
                        alt="Parque Aventura"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        AVENTURA · ADRENALINA
                    </div>

                    <h3>
                        Parque Aventura
                    </h3>

                    <p>
                        Tirolesas, escalada, puentes colgantes
                        y circuitos de obstáculos en plena naturaleza.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- PASEO DE EMPRENDEDORES -->

            <a
                class="experience-card reveal"
                href="diezPaginas/paseo-emprendedores.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1452860606245-08befc0ff44b?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=1400&q=85"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1400&q=85"
                        alt="Paseo de Emprendedores"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        COMUNIDAD · ARTESANÍA
                    </div>

                    <h3>
                        Paseo de Emprendedores
                    </h3>

                    <p>
                        Productos artesanales, emprendimientos
                        locales y propuestas para descubrir.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- RESTAURANTE -->

            <a
                class="experience-card reveal"
                href="diezPaginas/restaurante.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=1400&q=85"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1400&q=85"
                        alt="Restaurante Panorámico"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        GASTRONOMÍA · SABORES
                    </div>

                    <h3>
                        Restaurante Panorámico
                    </h3>

                    <p>
                        Gastronomía, buenos sabores y una vista
                        especial para disfrutar.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- SECTOR INFANTIL -->

            <a
                class="experience-card reveal"
                href="diezPaginas/sector-infantil.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1564429238817-393bd4283f3c?auto=format&fit=crop&w=1400&q=85",
                        "https://cmsv2-assets.apptegy.net/uploads/11614/file/5119791/px600_1c04828f-2c3a-40fb-b952-6591ab46f99e.jpeg"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?auto=format&fit=crop&w=1400&q=85"
                        alt="Sector Infantil"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        FAMILIA · DIVERSIÓN
                    </div>

                    <h3>
                        Sector Infantil
                    </h3>

                    <p>
                        Juegos, espacios recreativos y propuestas
                        pensadas para que los más chicos disfruten.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


            <!-- SPA -->

            <a
                class="experience-card reveal"
                href="diezPaginas/spa.php"
            >

                <div
                    class="card-carousel"
                    data-images='[
                        "https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1600334089648-b0d9d3028eb2?auto=format&fit=crop&w=1400&q=85",
                        "https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=1400&q=85"
                    ]'
                >

                    <img
                        class="card-carousel-image"
                        src="https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=1400&q=85"
                        alt="Spa & Wellness"
                    >

                    <button
                        class="card-carousel-arrow card-carousel-prev"
                        type="button"
                        aria-label="Imagen anterior"
                    >
                        ‹
                    </button>

                    <button
                        class="card-carousel-arrow card-carousel-next"
                        type="button"
                        aria-label="Imagen siguiente"
                    >
                        ›
                    </button>

                    <div class="card-carousel-dots"></div>

                </div>


                <div class="experience-card-content">

                    <div class="experience-card-kicker">
                        RELAX · BIENESTAR
                    </div>

                    <h3>
                        Spa & Wellness
                    </h3>

                    <p>
                        Tratamientos, relajación y espacios
                        pensados para desconectar.
                    </p>

                    <span class="card-link">
                        Descubrir →
                    </span>

                </div>

            </a>


        </div>

    </main>


    <!-- MAPA -->

    <section
        class="map-section"
        id="mapa"
    >

        <div class="map-section-content">

            <div class="section-kicker">
                EXPLORÁ EL PARQUE
            </div>

            <h2>
                Descubrí Laguna Experience
            </h2>

            <p>
                Recorré el mapa y conocé dónde se encuentra
                cada experiencia dentro del parque.
            </p>

            <a
                class="hero-button"
                href="laguna-experience-map%20(1).html"
            >
                Ver mapa →
            </a>

        </div>

    </section>


    <!-- FOOTER -->
    <!-- UBICACIÓN -->

    <section class="location-section" id="ubicacion">

        <div class="location-content">

            <div class="section-kicker">
                NUESTRA UBICACIÓN
            </div>

            <h2>
                Encontranos cerca de la naturaleza
            </h2>

            <p>
                Laguna Experience está ubicado en San Pedro,
                provincia de Buenos Aires, rodeado de naturaleza
                y espacios pensados para disfrutar.
            </p>

            <div class="location-info">

                <div class="location-point">

                    <div class="location-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M20 10C20 15 12 21 12 21C12 21 4 15 4 10C4 5.58 7.58 2 12 2C16.42 2 20 5.58 20 10Z"
                                stroke="currentColor"
                                stroke-width="1.8"
                            />

                            <circle
                                cx="12"
                                cy="10"
                                r="3"
                                stroke="currentColor"
                                stroke-width="1.8"
                            />
                        </svg>
                    </div>

                    <div>
                        <span>
                            UBICACIÓN
                        </span>

                        <strong>
                            San Pedro, Buenos Aires
                        </strong>
                    </div>

                </div>


                <div class="location-point">

                    <div class="location-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                                stroke="currentColor"
                                stroke-width="1.8"
                            />

                            <path
                                d="M12 7V12L15.5 14"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <span>
                            DISTANCIA
                        </span>

                        <strong>
                            A pocos kilómetros de la ciudad
                        </strong>
                    </div>

                </div>

            </div>


            <a
                class="location-button"
                href="https://www.google.com/maps/search/?api=1&query=-33.6796,-59.6650"
                target="_blank"
                rel="noopener noreferrer"
            >
                Ver ubicación en el mapa →
            </a>

        </div>


        <div class="location-map">

            <div class="map-overlay">

                <div class="map-marker">

                    <div class="map-marker-dot"></div>

                    <div class="map-marker-label">
                        Laguna Experience
                    </div>

                </div>

            </div>

            <div class="map-grid"></div>

            <div class="map-road road-one"></div>
            <div class="map-road road-two"></div>
            <div class="map-road road-three"></div>

            <div class="map-water"></div>

        </div>

    </section>
    <footer class="footer">

        <div>

            <strong>
                Laguna Experience
            </strong>

            <span>
                Smart Experience Park
            </span>

        </div>

        <p>
            Una experiencia para descubrir, disfrutar y compartir.
        </p>

    </footer>


    <script src="script.js"></script>


    <script>

        const navbar =
            document.querySelector(".main-navbar");


        window.addEventListener("scroll", function() {

            if (window.scrollY > 20) {

                navbar.classList.add("scrolled");

            } else {

                navbar.classList.remove("scrolled");

            }

        });


        document
            .querySelectorAll(".card-carousel button")
            .forEach(function(button) {

                button.addEventListener(
                    "click",
                    function(event) {

                        event.preventDefault();
                        event.stopPropagation();

                    }
                );

            });

    </script>


</body>

</html>