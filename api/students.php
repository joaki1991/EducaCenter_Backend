<?php
// Archivo de la API para gestionar los estudiantes
// Este archivo se encarga de gestionar los estudiantes, permitiendo crear, leer, actualizar y eliminar estudiantes
// La estructura de todos estos archivos es similar a la de users.php, pero en este caso se tocan los datos de la tabla students
require_once '../config/cors.php';
require_once '../config/database.php';
require_once 'auth.php';

$mysqli = $conn;
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $query = "SELECT * FROM students";
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
            $students = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($students);
        } else {
            echo json_encode(["error" => "No students found"]);
        }
        break;

    case 'POST':
        checkRole('admin');
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['user_id'], $data['group_id'], $data['parent_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing fields"]);
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO students (user_id, group_id, parent_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $data['user_id'], $data['group_id'], $data['parent_id']);
        $stmt->execute();
        echo json_encode(["success" => $stmt->affected_rows > 0]);
        break;

    case 'PUT':
        checkRole('admin');
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing student ID"]);
            exit();
        }

        $fields = [];
        $params = [];
        $types = "";

        foreach (['user_id', 'group_id', 'parent_id'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= "i";
            }
        }

        $params[] = $data['id'];
        $types .= "i";

        $stmt = $mysqli->prepare("UPDATE students SET " . implode(", ", $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        echo json_encode(["success" => $stmt->affected_rows > 0]);
        break;

    case 'DELETE':
        checkRole('admin');
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing student ID"]);
            exit();
        }

        $stmt = $mysqli->prepare("DELETE FROM students WHERE id = ?");
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