<?php
// Archivo para gestionar las ausencias
// Este archivo se encarga de gestionar las ausencias, permitiendo crear, leer, actualizar y eliminar ausencias
// La estructura de todos estos archivos es similar a la de users.php, pero en este caso se tocan los datos de la tabla absences
require_once '../config/cors.php';
require_once '../config/database.php';
require_once 'auth.php';

$mysqli = $conn;
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $query = "SELECT * date FROM absences";
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
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        checkRole('teacher');
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['student_id'], $data['teacher_id'], $data['date'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing fields"]);
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO absences (student_id, teacher_id, date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $data['student_id'], $data['teacher_id'], $data['date']);
        $stmt->execute();
        echo json_encode(["success" => $stmt->affected_rows > 0]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing absence ID"]);
            exit();
        }

        $fields = [];
        $params = [];
        $types = "";

        foreach (['student_id', 'teacher_id', 'date', 'justified'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= is_int($data[$field]) ? "i" : "s";
            }
        }

        $params[] = $data['id'];
        $types .= "i";

        $stmt = $mysqli->prepare("UPDATE absences SET " . implode(", ", $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        echo json_encode(["success" => $stmt->affected_rows > 0]);
        break;

    case 'DELETE':
        checkRole('admin');
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing absence ID"]);
            exit();
        }

        $stmt = $mysqli->prepare("DELETE FROM absences WHERE id = ?");
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