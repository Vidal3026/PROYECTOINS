<?php
//Datos de conexión a la base de datos
$host = "localhost";
$usuario = "wilfredo"; //usuario de MySQL
$password = "wilfredo3026"; //contraseña de MySQL
$base_datos = "tutolink"; //nombre de la base

try
{
    $conn = new PDO("mysql:host=$host;dbname=$base_datos;charset=utf8mb4", $usuario, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Conexión exitosa a la base de datos.";
} 
catch (PDOException $e)
{
    die("Error en la conexión: " . $e->getMessage());
}
?>