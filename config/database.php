<?php
// Archivo de configuración de la base de datos
// En este archivo solo tengo que decidir si voy a trabajar en local o en el servidor en remoto
$option = "local";  // Aqui puedo cambiar local por remoto si quiero trabajar en el servidor remoto

if($option == 'local'){
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "educacenter";

    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// En la siguiente condicion debo cambiar la direccion del servidor, el nombre de usuario y la contraseña por los del servidor que tenga configurado
if($option == 'remoto'){
    $host = "http://educacenter.wuaze.com/"; // Direccion de mi servidor web
    $username = ""; // Nombre de usuario del servidor
    $password = ""; // Contraseña
    $database = "educacenter";

    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

?>