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
        <link rel="stylesheet" href="https://unpkg.com/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://unpkg.com/bs-brain@2.0.4/components/registrations/registration-3/assets/css/registration-3.css">
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="assets/css/styles.css" rel="stylesheet" />
    </head>
    <body class="d-flex flex-column h-100">
        <main class="flex-shrink-0">

            <!-- Navigation-->
            <?php require_once 'Includes/navbar.php'; ?>
            
            <!-- Header-->
            <header class="bg-dark py-5">
                <div class="container px-5">
                    <div class="row gx-5 align-items-center justify-content-center">
                        <div class="col-lg-8 col-xl-7 col-xxl-6">
                            <div class="my-5 text-center text-xl-start">
                                <h1 class="display-5 fw-bolder text-white mb-2">TutoLink: Conectando Estudiantes con Tutores de Confianza</h1>
                                <p class="lead fw-normal text-white-50 mb-4">TutoLink es una plataforma web diseñada para conectar de manera directa y segura a estudiantes universitarios con tutores especializados, según su carrera, materia, modalidad y horarios.</p>
                                <div class="d-grid gap-3 d-sm-flex justify-content-sm-center justify-content-xl-start">
                                    <a class="btn btn-primary btn-lg px-4 me-sm-3" href="Login.php">Iniciar</a>
                                    <a class="btn btn-outline-light btn-lg px-4" href="Registro.php">Registrarse</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-5 col-xxl-6 d-none d-xl-block text-center"><img class="img-fluid rounded-3 my-5" src="assets/img/logo.png" alt="Logo" /></div>
                    </div>
                </div>
            </header>
            <!-- Features section-->
            <section class="py-5" id="features">
                <div class="container px-5 my-5">
                    <div class="row gx-5">
                        <div class="col-lg-4 mb-5 mb-lg-0"><h2 class="fw-bolder mb-0">Una mejor manera de empezar a aprender.</h2></div>
                        <div class="col-lg-8">
                            <div class="row gx-5 row-cols-1 row-cols-md-2">
                                <div class="col mb-5 h-100">
                                    <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3"><i class="bi bi-collection"></i></div>
                                    <h2 class="h5">Aprende más, sin complicaciones</h2>
                                    <p class="mb-0">Con TutoLink encuentras tutores verificados según tu carrera, materia y horario, todo en un solo lugar y de forma segura.</p>
                                </div>
                                <div class="col mb-5 h-100">
                                    <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3"><i class="bi bi-building"></i></div>
                                    <h2 class="h5">Conexión directa y rápida</h2>
                                    <p class="mb-0">Olvídate de buscar en grupos o redes sociales. Con nuestra plataforma te comunicas directamente con el tutor ideal para ti.</p>
                                </div>
                                <div class="col mb-5 mb-md-0 h-100">
                                    <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3"><i class="bi bi-toggles2"></i></div>
                                    <h2 class="h5">Tutorías personalizadas</h2>
                                    <p class="mb-0">Cada estudiante es diferente. Programa sesiones adaptadas a tu estilo de aprendizaje y necesidades específicas.</p>
                                </div>
                                <div class="col h-100">
                                    <div class="feature bg-primary bg-gradient text-white rounded-3 mb-3"><i class="bi bi-toggles2"></i></div>
                                    <h2 class="h5">Calidad garantizada</h2>
                                    <p class="mb-0">Consulta valoraciones y comentarios de otros estudiantes para elegir siempre la mejor opción de tutoría.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Testimonial section-->
            <div class="py-5 bg-light">
                <div class="container px-5 my-5">
                    <div class="row gx-5 justify-content-center">
                        <div class="col-lg-10 col-xl-7">
                            <div class="text-center">
                                <div class="fs-4 mb-4 fst-italic">"Usar TutoLink me ha ahorrado muchísimo tiempo buscando apoyo académico. Encontrar al tutor ideal nunca había sido tan fácil. ¡TutoLink simplifica el aprendizaje!"</div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <img class="rounded-circle me-3" src="assets/img/perfil.png" width="50 px" height="50 px" alt="perfil de usuario" />
                                    <div class="fw-bold">
                                        Roberto Mejia
                                        <span class="fw-bold text-primary mx-1">/</span>
                                        Estudiante Universitario
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Blog preview section-->
            <section class="py-5">
                <div class="container px-5 my-5">
                    <div class="row gx-5 justify-content-center">
                        <div class="col-lg-8 col-xl-6">
                            <div class="text-center">
                                <h2 class="fw-bolder">Lo que dicen nuestros estudiantes</h2>
                                <p class="lead fw-normal text-muted mb-5">Descubre cómo nuestras tutorías personalizadas han ayudado a cientos de estudiantes a mejorar su rendimiento, ganar confianza y alcanzar sus metas académicas.</p>
                            </div>
                        </div>
                    </div>
                    <div class="row gx-5">
                        <div class="col-lg-4 mb-5">
                            <div class="card h-100 shadow border-0">
                                <img class="card-img-top" src="https://img.freepik.com/vector-gratis/ceremonia-virtual-graduacion-graduados_23-2148571439.jpg?semt=ais_hybrid&w=740&q=80" alt="..." />
                                <div class="card-body p-4">
                                    <div class="badge bg-primary bg-gradient rounded-pill mb-2">Comentario</div>
                                    <a class="text-decoration-none link-dark stretched-link" href="#!"><h5 class="card-title mb-3">Aprende a tu ritmo</h5></a>
                                    <p class="card-text mb-0">Cada estudiante tiene su propio camino. En nuestra plataforma puedes avanzar a tu propio ritmo, con tutores que se adaptan a tu estilo de aprendizaje y nivel de conocimiento.</p>
                                </div>
                                <div class="card-footer p-4 pt-0 bg-transparent border-top-0">
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="small">
                                                <div class="fw-bold">Wilfredo</div>
                                                <div class="text-muted">Enero 12</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-5">
                            <div class="card h-100 shadow border-0">
                                <img class="card-img-top" src="https://media.licdn.com/dms/image/v2/C4E12AQGNI1DG5aLqHQ/article-cover_image-shrink_720_1280/article-cover_image-shrink_720_1280/0/1558014635758?e=2147483647&v=beta&t=vzLxaC7sXK1O7cQPBT9J8DuhE5hr2jCQA2M_xPpdkXs" alt="..." />
                                <div class="card-body p-4">
                                    <div class="badge bg-primary bg-gradient rounded-pill mb-2">Comentario</div>
                                    <a class="text-decoration-none link-dark stretched-link" href="#!"><h5 class="card-title mb-3">Conecta con expertos reales</h5></a>
                                    <p class="card-text mb-0">Nuestros tutores no solo enseñan, sino que inspiran. Conecta con profesionales apasionados por compartir sus conocimientos y ayudarte a alcanzar tus metas académicas.</p>
                                </div>
                                <div class="card-footer p-4 pt-0 bg-transparent border-top-0">
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="small">
                                                <div class="fw-bold">Alessandra</div>
                                                <div class="text-muted">Marzo 23</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-5">
                            <div class="card h-100 shadow border-0">
                                <img class="card-img-top" src="https://media.licdn.com/dms/image/v2/D4D12AQHDXD-ZzZ0gew/article-inline_image-shrink_1000_1488/article-inline_image-shrink_1000_1488/0/1721202217763?e=2147483647&v=beta&t=8xGTRlCd2aOb0-tZI7rDysMILasvg879XjvJE0c3nWw" alt="..." />
                                <div class="card-body p-4">
                                    <div class="badge bg-primary bg-gradient rounded-pill mb-2">Comentario</div>
                                    <a class="text-decoration-none link-dark stretched-link" href="#!"><h5 class="card-title mb-3">Tu progreso, nuestra prioridad</h5></a>
                                    <p class="card-text mb-0">Monitorea tu avance, recibe retroalimentación personalizada y mejora en cada sesión. Cada tutoría está pensada para que aprendas más y mejor, sin complicaciones.</p>
                                </div>
                                <div class="card-footer p-4 pt-0 bg-transparent border-top-0">
                                    <div class="d-flex align-items-end justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="small">
                                                <div class="fw-bold">Roberto</div>
                                                <div class="text-muted">Abril 12</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
