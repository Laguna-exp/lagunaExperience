<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Paseo de Emprendedores | Laguna Experience</title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap"
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

    --font-body: "DM Sans", sans-serif;
    --font-display: "Manrope", sans-serif;

    --shadow:
        0 20px 60px rgba(20, 60, 65, .10);
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


/* =====================================================
   HEADER
===================================================== */

header {

    min-height: 510px;

    padding: 80px 8%;

    display: flex;

    align-items: center;

    position: relative;

    overflow: hidden;

    color: white;

    background:
        linear-gradient(
            120deg,
            var(--turq-deep),
            var(--turq)
        );
}


header::before {

    content: "";

    position: absolute;

    width: 520px;
    height: 520px;

    right: -190px;
    top: -230px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.08);
}


header::after {

    content: "";

    position: absolute;

    width: 310px;
    height: 310px;

    left: -140px;
    bottom: -180px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.06);
}


.header-content {

    position: relative;

    z-index: 2;

    max-width: 850px;
}


.eyebrow {

    margin:
        0 0 18px;

    font-size: 13px;

    font-weight: 700;

    letter-spacing: .14em;

    text-transform: uppercase;

    opacity: .8;
}


header h1 {

    margin: 0;

    font-family: var(--font-display);

    font-size: clamp(
        52px,
        8vw,
        98px
    );

    line-height: .98;

    letter-spacing: -.06em;
}


header .sub {

    max-width: 730px;

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

    transition: .25s;
}


.back-button:hover {

    transform: translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(0,0,0,.15);
}


/* =====================================================
   MAIN
===================================================== */

main {

    max-width: 1250px;

    margin:
        -45px auto 80px;

    padding:
        0 5%;

    position: relative;

    z-index: 3;
}


/* =====================================================
   INTRO
===================================================== */

.intro-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 75px;
}


.intro-card {

    min-height: 175px;

    padding: 28px;

    border:
        1px solid
        var(--line);

    border-radius: 20px;

    background: var(--paper);

    box-shadow: var(--shadow);
}


.intro-label {

    margin-bottom: 11px;

    color: var(--turq);

    font-size: 12px;

    font-weight: 800;

    letter-spacing: .08em;

    text-transform: uppercase;
}


.intro-card h3 {

    margin:
        0 0 9px;

    font-family: var(--font-display);

    font-size: 21px;
}


.intro-card p {

    margin: 0;

    color: #71594a;

    font-size: 14px;

    line-height: 1.7;
}


/* =====================================================
   SECTIONS
===================================================== */

section {

    margin-bottom: 75px;
}


section h2 {

    margin:
        0 0 12px;

    font-family: var(--font-display);

    font-size: 36px;

    line-height: 1.15;

    letter-spacing: -.04em;
}


.section-intro {

    max-width: 760px;

    margin:
        0 0 32px;

    color: #71594a;

    font-size: 16px;
}


/* =====================================================
   CATEGORÍAS
===================================================== */

.categories {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;
}


.category {

    min-height: 250px;

    padding: 30px;

    position: relative;

    overflow: hidden;

    border:
        1px solid
        var(--line);

    border-radius: 22px;

    background: var(--paper);

    box-shadow:
        0 15px 45px
        rgba(20,60,65,.08);

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}


.category:hover {

    transform: translateY(-5px);

    box-shadow:
        0 25px 55px
        rgba(20,60,65,.13);
}


.category::after {

    content: "";

    position: absolute;

    width: 150px;
    height: 150px;

    right: -55px;
    bottom: -65px;

    border-radius: 50%;

    background: var(--turq-light);
}


.category-number {

    width: 45px;
    height: 45px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin-bottom: 22px;

    border-radius: 13px;

    background: var(--turq-light);

    color: var(--turq);

    font-family: var(--font-display);

    font-weight: 800;
}


.category h3 {

    margin:
        0 0 10px;

    font-family: var(--font-display);

    font-size: 24px;
}


.category p {

    max-width: 560px;

    margin:
        0 0 20px;

    color: #71594a;

    font-size: 14px;
}


.tags {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    position: relative;

    z-index: 2;
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


/* =====================================================
   DESTACADO
===================================================== */

.featured {

    display: grid;

    grid-template-columns:
        1.2fr .8fr;

    overflow: hidden;

    border-radius: 25px;

    background: var(--turq-deep);

    color: white;

    box-shadow: var(--shadow);
}


.featured-content {

    padding: 48px;
}


.featured-label {

    margin-bottom: 14px;

    color:
        rgba(255,255,255,.65);

    font-size: 12px;

    font-weight: 800;

    letter-spacing: .1em;

    text-transform: uppercase;
}


.featured h2 {

    margin:
        0 0 18px;

    color: white;

    font-size: 42px;
}


.featured p {

    max-width: 610px;

    margin: 0;

    color:
        rgba(255,255,255,.78);

    font-size: 15px;
}


.featured-side {

    min-height: 320px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            var(--turq),
            #35a9b7
        );
}


.featured-circle {

    width: 175px;
    height: 175px;

    display: flex;

    align-items: center;
    justify-content: center;

    padding: 25px;

    border:
        1px solid
        rgba(255,255,255,.35);

    border-radius: 50%;

    color: white;

    font-family: var(--font-display);

    font-size: 18px;

    font-weight: 800;

    text-align: center;
}


/* =====================================================
   PASEO
===================================================== */

.walk-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;
}


.walk-card {

    padding: 28px;

    border:
        1px solid
        var(--line);

    border-radius: 19px;

    background: var(--paper);
}


.walk-card h3 {

    margin:
        0 0 10px;

    font-family: var(--font-display);

    font-size: 19px;
}


.walk-card p {

    margin: 0;

    color: #71594a;

    font-size: 14px;

    line-height: 1.7;
}


/* =====================================================
   EMPRENDEDORES
===================================================== */

.entrepreneurs {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 18px;
}


.entrepreneur-card {

    padding: 30px;

    border-radius: 20px;

    background: var(--sand);

    border:
        1px solid
        var(--line);
}


.entrepreneur-card h3 {

    margin:
        0 0 9px;

    font-family: var(--font-display);

    font-size: 20px;
}


.entrepreneur-card p {

    margin: 0;

    color: #71594a;

    font-size: 14px;

    line-height: 1.7;
}


/* =====================================================
   INFORMACIÓN
===================================================== */

.info-box {

    padding: 42px;

    border-radius: 23px;

    background:
        linear-gradient(
            135deg,
            var(--sun-light),
            var(--sand)
        );

    border:
        1px solid
        var(--line);
}


.info-box h2 {

    margin-bottom: 15px;
}


.info-box p {

    max-width: 760px;

    margin: 0;

    color: #71594a;

    font-size: 15px;

    line-height: 1.8;
}


/* =====================================================
   CIERRE
===================================================== */

.final {

    padding: 55px;

    border-radius: 25px;

    background: var(--paper);

    border:
        1px solid
        var(--line);

    box-shadow: var(--shadow);

    text-align: center;
}


.final h2 {

    margin-bottom: 14px;
}


.final p {

    max-width: 650px;

    margin:
        0 auto;

    color: #71594a;

    font-size: 15px;
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

@media (max-width: 900px) {

    .intro-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }


    .walk-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }


    .featured {

        grid-template-columns: 1fr;
    }


    .featured-side {

        min-height: 230px;
    }
}


@media (max-width: 700px) {

    header {

        min-height: 460px;

        padding:
            65px 7%;
    }


    header h1 {

        font-size: 57px;
    }


    main {

        margin-top: -30px;

        padding:
            0 4%;
    }


    .intro-grid,
    .categories,
    .walk-grid,
    .entrepreneurs {

        grid-template-columns: 1fr;
    }


    section h2 {

        font-size: 29px;
    }


    .featured-content {

        padding: 32px;
    }


    .featured h2 {

        font-size: 34px;
    }


    .info-box,
    .final {

        padding: 32px 24px;
    }
}


@media (max-width: 450px) {

    header h1 {

        font-size: 47px;
    }


    header .sub {

        font-size: 15px;
    }


    .category {

        padding: 24px;
    }
}

</style>

</head>


<body>


<header>

    <div class="header-content">

        <div class="eyebrow">
            Laguna Experience · Comunidad
        </div>


        <h1>
            Paseo de Emprendedores
        </h1>


        <p class="sub">
            Un espacio para recorrer, descubrir proyectos locales
            y conocer productos creados por emprendedores de la
            comunidad.
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


<!-- INTRODUCCIÓN -->

<div class="intro-grid">


    <div class="intro-card">

        <div class="intro-label">
            Comunidad
        </div>

        <h3>
            Talento local
        </h3>

        <p>
            Un espacio pensado para dar visibilidad a personas
            que desarrollan sus propios proyectos y productos.
        </p>

    </div>


    <div class="intro-card">

        <div class="intro-label">
            Recorrido
        </div>

        <h3>
            Un paseo para descubrir
        </h3>

        <p>
            Los visitantes pueden recorrer distintos puestos
            y conocer propuestas variadas en un mismo lugar.
        </p>

    </div>


    <div class="intro-card">

        <div class="intro-label">
            Experiencias
        </div>

        <h3>
            Más que un paseo
        </h3>

        <p>
            Además de los emprendimientos, el espacio puede
            incluir propuestas gastronómicas y actividades.
        </p>

    </div>


</div>


<!-- CATEGORÍAS -->

<section>

    <h2>
        ¿Qué podés encontrar?
    </h2>


    <p class="section-intro">
        El paseo puede reunir diferentes tipos de
        emprendimientos para que cada recorrido sea distinto.
    </p>


    <div class="categories">


        <div class="category">

            <div class="category-number">
                01
            </div>

            <h3>
                Artesanías
            </h3>

            <p>
                Objetos realizados de manera artesanal,
                piezas decorativas y productos hechos
                con diferentes materiales.
            </p>

            <div class="tags">

                <span class="tag">
                    Hecho a mano
                </span>

                <span class="tag">
                    Decoración
                </span>

                <span class="tag">
                    Diseño
                </span>

            </div>

        </div>


        <div class="category">

            <div class="category-number">
                02
            </div>

            <h3>
                Gastronomía
            </h3>

            <p>
                Propuestas gastronómicas de emprendedores
                locales, con diferentes opciones para
                disfrutar durante el recorrido.
            </p>

            <div class="tags">

                <span class="tag">
                    Productos caseros
                </span>

                <span class="tag">
                    Dulce
                </span>

                <span class="tag">
                    Salado
                </span>

            </div>

        </div>


        <div class="category">

            <div class="category-number">
                03
            </div>

            <h3>
                Indumentaria y accesorios
            </h3>

            <p>
                Emprendimientos relacionados con ropa,
                accesorios y propuestas de diseño
                independientes.
            </p>

            <div class="tags">

                <span class="tag">
                    Ropa
                </span>

                <span class="tag">
                    Accesorios
                </span>

                <span class="tag">
                    Diseño independiente
                </span>

            </div>

        </div>


        <div class="category">

            <div class="category-number">
                04
            </div>

            <h3>
                Productos naturales
            </h3>

            <p>
                Propuestas vinculadas con productos
                naturales, bienestar y elaboración artesanal.
            </p>

            <div class="tags">

                <span class="tag">
                    Natural
                </span>

                <span class="tag">
                    Artesanal
                </span>

                <span class="tag">
                    Bienestar
                </span>

            </div>

        </div>


    </div>

</section>


<!-- DESTACADO -->

<section>

    <div class="featured">

        <div class="featured-content">

            <div class="featured-label">
                Una propuesta diferente
            </div>


            <h2>
                Conocé a quienes están detrás de cada proyecto
            </h2>


            <p>
                El Paseo de Emprendedores busca generar un
                punto de encuentro entre visitantes y proyectos
                locales. Cada puesto puede contar una historia,
                mostrar un producto o presentar una propuesta
                creada por sus propios emprendedores.
            </p>

        </div>


        <div class="featured-side">

            <div class="featured-circle">
                Ideas que<br>
                se convierten<br>
                en proyectos
            </div>

        </div>

    </div>

</section>


<!-- RECORRIDO -->

<section>

    <h2>
        Un paseo para recorrer
    </h2>


    <p class="section-intro">
        La propuesta está pensada para que los visitantes
        puedan recorrer los diferentes espacios con
        tranquilidad y descubrir nuevas propuestas.
    </p>


    <div class="walk-grid">


        <div class="walk-card">

            <h3>
                Descubrí
            </h3>

            <p>
                Recorré los diferentes puestos y conocé
                los proyectos que forman parte del paseo.
            </p>

        </div>


        <div class="walk-card">

            <h3>
                Conocé
            </h3>

            <p>
                Podés conocer el trabajo, las historias
                y las ideas detrás de cada emprendimiento.
            </p>

        </div>


        <div class="walk-card">

            <h3>
                Compartí
            </h3>

            <p>
                El paseo también puede convertirse en un
                punto de encuentro para la comunidad.
            </p>

        </div>


    </div>

</section>


<!-- EMPRENDEDORES -->

<section>

    <h2>
        Espacio para emprendedores
    </h2>


    <p class="section-intro">
        El paseo puede funcionar como una plataforma para
        que pequeños proyectos tengan un lugar donde
        mostrar lo que hacen.
    </p>


    <div class="entrepreneurs">


        <div class="entrepreneur-card">

            <h3>
                Proyectos locales
            </h3>

            <p>
                Una oportunidad para mostrar productos,
                servicios e ideas desarrolladas por
                emprendedores de la zona.
            </p>

        </div>


        <div class="entrepreneur-card">

            <h3>
                Producción independiente
            </h3>

            <p>
                Espacio para propuestas que nacen de
                pequeños proyectos y producciones propias.
            </p>

        </div>


        <div class="entrepreneur-card">

            <h3>
                Nuevas propuestas
            </h3>

            <p>
                El paseo puede incorporar nuevos
                emprendimientos y categorías con el tiempo.
            </p>

        </div>


        <div class="entrepreneur-card">

            <h3>
                Comunidad
            </h3>

            <p>
                Un lugar que conecta a emprendedores,
                visitantes y diferentes propuestas locales.
            </p>

        </div>


    </div>

</section>


<!-- INFORMACIÓN -->

<section>

    <div class="info-box">

        <h2>
            Un espacio que puede crecer
        </h2>


        <p>
            El Paseo de Emprendedores está pensado como
            un espacio flexible dentro de Laguna Experience.
            La propuesta puede incorporar diferentes
            emprendimientos, actividades y sectores a medida
            que el proyecto crezca, manteniendo siempre el
            objetivo de generar un punto de encuentro para
            la comunidad.
        </p>

    </div>

</section>


<!-- CIERRE -->

<section>

    <div class="final">

        <h2>
            Descubrí algo nuevo en cada recorrido
        </h2>


        <p>
            Visitá el Paseo de Emprendedores y conocé
            las diferentes propuestas que pueden formar
            parte de Laguna Experience.
        </p>

    </div>

</section>


</main>


<footer>

    Laguna Experience — Paseo de Emprendedores

</footer>


</body>

</html>