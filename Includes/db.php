<?php
// Datos de conexión a la base de datos
$host = "localhost";
$usuario = "root"; // usuario de MySQL
$password = ""; // contraseña de MySQL
$base_datos = "tutolink"; // nombre de la base

try {
    $conn = new PDO("mysql:host=$host;dbname=$base_datos;charset=utf8mb4", $usuario, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

/**
 * Crea, si aún no existe, el registro persistente de cambios de horario.
 * La inscripción queda vinculada por solicitud, estudiante, tutor y materia.
 */
if (!function_exists('asegurarTablaNotificacionesHorario')) {
    function asegurarTablaNotificacionesHorario(PDO $conn): void
    {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS notificaciones_horario (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_solicitud INT NOT NULL,
                id_estudiante INT NOT NULL,
                id_tutor INT NOT NULL,
                id_materia INT NOT NULL,
                fecha_tutoria DATE NOT NULL,
                hora_anterior_inicio TIME NOT NULL,
                hora_anterior_fin TIME NOT NULL,
                horario_nuevo TEXT NULL,
                mensaje VARCHAR(500) NOT NULL,
                cambio_hash CHAR(64) NOT NULL,
                leida TINYINT(1) NOT NULL DEFAULT 0,
                creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_solicitud_cambio (id_solicitud, cambio_hash),
                INDEX idx_estudiante_leida (id_estudiante, leida),
                INDEX idx_solicitud (id_solicitud),
                INDEX idx_materia (id_materia)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
?>