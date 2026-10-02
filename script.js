
document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       CARRUSELES DE LAS CARDS
    ====================================================== */

    var cards = document.querySelectorAll(".experience-card");

    cards.forEach(function (card) {

        var carousel = card.querySelector(".card-carousel");

        if (!carousel) {
            return;
        }

        var image = carousel.querySelector(".card-carousel-image");
        var prevButton = carousel.querySelector(".card-carousel-prev");
        var nextButton = carousel.querySelector(".card-carousel-next");
        var dotsContainer = carousel.querySelector(".card-carousel-dots");

        var images = JSON.parse(carousel.dataset.images);

        var currentImage = 0;


        /* =================================================
           CREAR LOS PUNTOS
        ================================================== */

        images.forEach(function (imageUrl, index) {

            var dot = document.createElement("button");

            dot.type = "button";
            dot.className = "card-carousel-dot";

            if (index === 0) {
                dot.classList.add("active");
            }

            dot.addEventListener("click", function (event) {

                event.preventDefault();
                event.stopPropagation();

                currentImage = index;

                updateCarousel();

            });

            dotsContainer.appendChild(dot);

        });


        var dots = dotsContainer.querySelectorAll(
            ".card-carousel-dot"
        );


        /* =================================================
           ACTUALIZAR IMAGEN
        ================================================== */

        function updateCarousel() {

            image.style.opacity = "0";

            setTimeout(function () {

                image.src = images[currentImage];

                image.style.opacity = "1";

            }, 120);


            dots.forEach(function (dot, index) {

                if (index === currentImage) {

                    dot.classList.add("active");

                } else {

                    dot.classList.remove("active");

                }

            });

        }


        /* =================================================
           SIGUIENTE
        ================================================== */

        nextButton.addEventListener("click", function (event) {

            event.preventDefault();
            event.stopPropagation();

            currentImage++;

            if (currentImage >= images.length) {
                currentImage = 0;
            }

            updateCarousel();

        });


        /* =================================================
           ANTERIOR
        ================================================== */

        prevButton.addEventListener("click", function (event) {

            event.preventDefault();
            event.stopPropagation();

            currentImage--;

            if (currentImage < 0) {
                currentImage = images.length - 1;
            }

            updateCarousel();

        });


        /* =================================================
           SWIPE EN CELULAR
        ================================================== */

        var startX = 0;

        carousel.addEventListener(
            "touchstart",
            function (event) {

                startX = event.touches[0].clientX;

            },
            { passive: true }
        );


        carousel.addEventListener(
            "touchend",
            function (event) {

                var endX = event.changedTouches[0].clientX;

                var difference = startX - endX;


                if (Math.abs(difference) < 40) {
                    return;
                }


                if (difference > 0) {

                    currentImage++;

                    if (currentImage >= images.length) {
                        currentImage = 0;
                    }

                } else {

                    currentImage--;

                    if (currentImage < 0) {
                        currentImage = images.length - 1;
                    }

                }


                updateCarousel();

            },
            { passive: true }
        );


        /* =================================================
           PRE-CARGAR LAS IMÁGENES
        ================================================== */

        images.forEach(function (imageUrl) {

            var preload = new Image();

            preload.src = imageUrl;

        });

    });


    /* =====================================================
       ANIMACIÓN DE LAS CARDS AL HACER SCROLL
    ====================================================== */

    var revealElements = document.querySelectorAll(".reveal");


    if ("IntersectionObserver" in window) {

        var observer = new IntersectionObserver(
            function (entries) {

                entries.forEach(function (entry) {

                    if (entry.isIntersecting) {

                        entry.target.classList.add("visible");

                        observer.unobserve(entry.target);

                    }

                });

            },
            {
                threshold: 0.12
            }
        );


        revealElements.forEach(function (element) {

            observer.observe(element);

        });

    } else {

        revealElements.forEach(function (element) {

            element.classList.add("visible");

        });

    }

});
