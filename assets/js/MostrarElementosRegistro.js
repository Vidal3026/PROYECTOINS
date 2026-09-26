function mostrarCampos() {
    const rol = document.getElementById("rol").value;
    document.getElementById("camposEstudiante").style.display = (rol === "estudiante") ? "flex" : "none";
    document.getElementById("camposTutor").style.display = (rol === "tutor") ? "flex" : "none";
}