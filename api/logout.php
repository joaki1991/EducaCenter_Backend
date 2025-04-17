<?php
// Archivo de cierre de sesión de la API
// Este archivo se encarga de cerrar la sesión del usuario y eliminar el token de la base de datos
// Debería llamar a este archivo desde el cliente cuando el usuario cierra sesión o cuando yo considere oportuno
require_once '../config/cors.php'; // Incluyo el archivo de configuración de CORS
require_once '../config/database.php'; // Incluyo el archivo de configuración de la base de datos
require_once 'auth.php'; // Verifica el token y obtiene el usuario en $user

// Elimina el token del usuario actual para cerrar la sesión
$stmt = $conn->prepare("UPDATE users SET token = NULL WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();

echo json_encode(["success" => true, "message" => "Logged out successfully"]);
?>