<?php
// Archivo de configuración de CORS (Podemos modificar la segunda linea para permitir solo el acceso desde nuestro dominio frontend)
header("Content-Type: application/json"); // Indica que la respuesta de nuestro servidor será en JSON
header("Access-Control-Allow-Origin: *"); // Permite el acceso desde cualquier origen, cambiar por nuestro dominio frontend en produccion
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE"); // Permite los métodos GET, POST, PUT y DELETE
header("Access-Control-Allow-Headers: Content-Type , Authorization"); // Permite el uso de Content-Type en las peticiones del cliente, para que estos puedan mandar cualquier contenido y Authorization para el token de autenticacion
header("Access-Control-Allow-Credentials: true"); // Permite el envío de credenciales
// Si la petición es de tipo OPTIONS (la preflight), responde con un 200 OK y termina el script.
// Así el navegador entiende que puede continuar con la petición real (POST, PUT, etc).
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>