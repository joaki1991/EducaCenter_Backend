<?php
// Archivo de autenticación de la API
// Este archivo se encarga de autenticar al usuario y devolver un token de acceso
require_once '../config/database.php';

// Obtener token desde cabecera Authorization
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["error" => "Missing token"]);
    exit();
} // Si no se envía el token desde el cliente, se devuelve un error 401 (no autorizado) y paramos la ejecución

$token = trim(str_replace('Bearer', '', $headers['Authorization'])); // Elimino 'Bearer' del token si está presente

// Voy a preparar la consulta para verificar el token en la base de datos y obtener el rol del usuario y la fecha de expiración del token
$stmt = $conn->prepare("SELECT id, role, token_expires_at FROM users WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid token"]);
    exit();
} // Si el token no se encuentra en la base de datos, se devuelve un error 403 (prohibido) y paramos la ejecución

// Creo la variable $user para almacenar los datos del usuario autenticado
$user = $result->fetch_assoc();
// Verifico si el token ha expirado
if (!$user || strtotime($user['token_expires_at']) < time()) {
    http_response_code(401);
    echo json_encode(["error" => "Expired token"]);
    exit(); // Si la expiracion del token es menor a la fecha actual, se devuelve un error 401 (no autorizado) y paramos la ejecución
}

// Tambien voy a crear una funcion para saber si el user es administrador o cualquier rol que le pase como parametro
function checkRole($requiredRole) {
    global $user;
    if ($user['role'] !== $requiredRole) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden - Insufficient permissions"]);
        exit(); 
    } // Si el rol del usuario no coincide con el rol requerido al llamar a esta funcion, se devuelve un error 403 (prohibido) y paramos la ejecución
}

?>