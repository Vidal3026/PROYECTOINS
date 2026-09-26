<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">General</div>
            <a class="nav-link" href="index.php">
                <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                Dashboard
            </a>

            <!--Gestión-->
            <div class="sb-sidenav-menu-heading">Gestión</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUsuarios"
                aria-expanded="false" aria-controls="collapseUsuarios">
                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                Gestión
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseUsuarios" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="GestionarUsuarios.php">Gestionar Usuarios</a>
                    <a class="nav-link" href="GestionarMateria.php">Gestionar Materias</a>

                </nav>
            </div>


            <!--Movimientos y Finanzas-->
            <div class="sb-sidenav-menu-heading">Movimientos y Finanzas</div>
            <a class="nav-link" href="Billetera.php">
                <div class="sb-nav-link-icon"><i class="fa-solid fa-wallet"></i></div>
                Billetera
            </a>
            <a class="nav-link" href="Historial.php">
                <div class="sb-nav-link-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                Historial
            </a>
            <a class="nav-link" href="SolicitudesAdministrador.php">
                <div class="sb-nav-link-icon"><i class="fa-solid fa-inbox"></i></div>
                Solicitudes de Retiros
            </a>

            <!--Estadística-->
            <div class="sb-sidenav-menu-heading">Estadísticas</div>
            <a class="nav-link" href="Estadisticas.php">
                <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                Estadísticas/Reportes
            </a>
               

        </div>
    </div>
    <center>
        <div class="sb-sidenav-footer">
            <div class="small">Inicio de sesión como:</div>
            <b>Administrador</b>
        </div>
    </center>
</nav>