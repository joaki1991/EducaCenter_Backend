<?php
// Archivo de la API para gestionar los padres
// Este archivo se encarga de gestionar los padres, permitiendo crear, leer, actualizar y eliminar padres
// La estructura de todos estos archivos es similar a la de users.php, pero en este caso se tocan los datos de la tabla parents
require_once '../config/cors.php';
require_once '../config/database.php';
require_once 'auth.php';

$mysqli = $conn;
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $query = "SELECT * FROM parents";
        $params = [];

        if (!empty($_GET)) {
            $filters = [];
            foreach ($_GET as $key => $value) {
                $filters[] = "$key = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(" AND ", $filters);
        }

        $stmt = $mysqli->prepare($query);

        if (count($params) > 0) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $parents = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($parents);
        } else {
            echo json_encode(["error" => "No parents found"]);
        }
        break;

    case 'POST':
        checkRole('admin');
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['user_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing user_id"]);
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO parents (user_id) VALUES (?)");
        $stmt->bind_param("i", $data['user_id']);
        $stmt->execute();

        echo json_encode(["success" => $stmt->affected_rows > 0]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing parent ID"]);
            exit();
        }

        $fields = [];
        $params = [];
        $types = "";

        if (isset($data['user_id'])) {
            $fields[] = "user_id = ?";
            $params[] = $data['user_id'];
            $types .= "i";
        }

        $params[] = $data['id'];
        $types .= "i";

        $stmt = $mysqli->prepare("UPDATE parents SET " . implode(", ", $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        echo json_encode(["success" => $stmt->affected_rows > 0]);
        break;

    case 'DELETE':
        checkRole('admin');
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing parent ID"]);
            exit();
        }

        $stmt = $mysqli->prepare("DELETE FROM parents WHERE id = ?");
        $stmt->bind_param("i", $data['id']);
        $stmt->execute();

        echo json_encode(["success" => $stmt->affected_rows > 0]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
}

$mysqli->close();
?>