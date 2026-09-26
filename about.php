<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>TutoLink</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
        <!-- Bootstrap icons-->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="assets/css/styles.css" rel="stylesheet" />
    </head>
    <body class="d-flex flex-column">
        <main class="flex-shrink-0">
            <!-- Navigation-->
            <?php require_once 'Includes/navbar.php'; ?>
            
            <!-- Header-->
            <header class="py-5">
                <div class="container px-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 col-xxl-6">
                            <div class="text-center my-5">
                                <h1 class="fw-bolder mb-3">Nuestra misión es facilitar el acceso a tutorías académicas personalizadas para todos los estudiantes universitarios.</h1><br>
                                <p class="lead fw-normal text-muted mb-4">TutoLink nació con la idea de que el apoyo académico de calidad, confiable y adaptado a las necesidades específicas de cada estudiante, debe estar al alcance de todos. Ofrecemos una plataforma accesible y segura que conecta a estudiantes con tutores calificados, de manera gratuita o a través de planes premium que brinden beneficios adicionales. Con TutoLink, buscamos derribar barreras, optimizar el tiempo y potenciar el aprendizaje colaborativo dentro de la comunidad universitaria.</p><br>
                                <a class="btn btn-primary btn-lg" href="#scroll-target">Leer nuetra historia</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <!-- About section one-->
            <section class="py-5 bg-light" id="scroll-target">
                <div class="container px-5 my-5">
                    <div class="row gx-5 align-items-center">
                        <div class="col-lg-6"><img class="img-fluid rounded mb-5 mb-lg-0" src="assets/img/idea.png" alt="..." /></div>
                        <div class="col-lg-6">
                            <h2 class="fw-bolder">Nuestro origen</h2>
                            <p class="lead fw-normal text-muted mb-0">TutoLink nació en el entorno universitario como respuesta a una problemática frecuente: la dificultad de encontrar tutorías académicas confiables y adaptadas a las necesidades de cada estudiante. Un grupo de estudiantes detectó que, aunque había disposición de ayudar y aprender, faltaba un espacio centralizado y seguro para conectar a tutores y alumnos. Así surgió la idea de crear una plataforma que derribe barreras, facilite la búsqueda de apoyo académico y fomente una comunidad colaborativa que potencie el aprendizaje.</p>
                        </div>
                    </div>
                </div>
            </section>
            <!-- About section two-->
            <section class="py-5">
                <div class="container px-5 my-5">
                    <div class="row gx-5 align-items-center">
                        <div class="col-lg-6 order-first order-lg-last"><img class="img-fluid rounded mb-5 mb-lg-0" src="assets/img/proyeccion.png" alt="..." /></div>
                        <div class="col-lg-6">
                            <h2 class="fw-bolder">Crecimiento y proyección</h2>
                            <p class="lead fw-normal text-muted mb-0">Desde su concepción, TutoLink busca ir más allá de ser solo una plataforma de tutorías: aspira a convertirse en una comunidad académica de referencia. Nuestro plan incluye ampliar la cobertura a más universidades, incorporar nuevas herramientas de aprendizaje, integrar inteligencia artificial para recomendaciones personalizadas y ofrecer oportunidades de formación continua para tutores. Queremos que TutoLink evolucione junto a sus usuarios, impulsando el éxito académico y profesional en cada etapa.
</p>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Team members section-->
            <section class="py-5 bg-light">
                <div class="container px-5 my-5">
                    <div class="text-center">
                        <h2 class="fw-bolder">Nuesto Equipo</h2>
                        <p class="lead fw-normal text-muted mb-5">Dedicados a la calidad académica y al éxito de cada estudiante.</p>
                    </div>
                    <div class="row gx-5 row-cols-1 row-cols-sm-2 row-cols-xl-4 justify-content-center">
                        <div class="col mb-5 mb-5 mb-xl-0">
                            <div class="text-center">
                                <img class="img-fluid rounded-circle mb-4 px-4" src="assets/img/wi.jpeg" alt="perfil" />
                                <h5 class="fw-bolder">Wilfredo Hernández</h5>
                                <div class="fst-italic text-muted">Founder &amp; CEO</div>
                            </div>
                        </div>
                        <div class="col mb-5 mb-5 mb-xl-0">
                            <div class="text-center">
                                <img class="img-fluid rounded-circle mb-4 px-4" src="assets/img/al.jpeg" alt="perfil" />
                                <h5 class="fw-bolder">Alessandra Moreno</h5>
                                <div class="fst-italic text-muted">Operations Manager</div>
                            </div>
                        </div>
                        <div class="col mb-5 mb-5 mb-sm-0">
                            <div class="text-center">
                                <img class="img-fluid rounded-circle mb-4 px-4" src="assets/img/cho.jpg" alt="perfil" />
                                <h5 class="fw-bolder">Roberto Mejía</h5>
                                <div class="fst-italic text-muted">CFO</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        <!-- Footer-->
        <?php require_once 'Includes/footer.php'; ?>
        
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="assets/js/scripts.js"></script>
    </body>
</html>
