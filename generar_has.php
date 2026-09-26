<?php
$contrasena_plana = "wil3026"; // cambiar contraseña
$hash_seguro = password_hash($contrasena_plana, PASSWORD_BCRYPT);
echo $hash_seguro;
?>
