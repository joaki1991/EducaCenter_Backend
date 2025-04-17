<?php
// Archivo de inicio de sesión de la API
// Este archivo se encarga de autenticar al usuario y devolver un token de acceso
require_once '../config/cors.php'; // Incluyo el archivo de configuración de CORS
require_once '../config/database.php'; // Incluyo el archivo de configuración de la base de datos
include_once '../config/tokensExp.php'; // Incluyo el archivo de configuración de la expiración del token

// Primero obtengo los datos del cuerpo de la petición
$data = json_decode(file_get_contents("php://input"), true);

// Si no existe el email o la password en el cuerpo de la petición, devuelvo un error 400 (Bad Request) y detengo la ejecución
if (!isset($data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing fields"]);
    exit();
}

$email = $data['email'];
$password = $data['password'];

// Ahora voy a comprobar si el usuario existe en la base de datos
$stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Si el usuario existe, verifico la contraseña
    if (password_verify($password, $user['password'])) {
        // En caso de ser correcta, vamos a generar un token aleatorio con la siguiente funcion
        // Esta funcion genera un token aleatorio de 64 caracteres hexadecimales de gran seguridad
        $token = bin2hex(random_bytes(32));
        // Voy a crear una fecha de expiracion para el token, que sera 4 horas a partir de ahora
        $expiration = date("Y-m-d H:i:s", time() + $horasExpiracion * 3600); // ahora + 4 horas

        $stmt = $conn->prepare("UPDATE users SET token = ?, token_expires_at = ? WHERE id = ?");
        $stmt->bind_param("ssi", $token, $expiration, $user['id']);
        $stmt->execute();
        echo json_encode([
            "success" => true,
            "token" => $token,
            "role" => $user['role']
        ]);
        exit(); // Si la contraseña es correcta, devolvemos el token y el rol del usuario al cliente y terminamos la ejecución
    }
}

// Si el usuario no existe o la contraseña es incorrecta, devolvemos un error 401 (Unauthorized) y el json de error
http_response_code(401);
echo json_encode(["error" => "Invalid credentials"]);
?>