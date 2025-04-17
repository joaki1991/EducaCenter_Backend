<?php
// Archivo de la API para gestionar los horarios, aunque de momento no se vaya a implementar ya lo dejo echo
// Este archivo se encarga de gestionar los horarios, permitiendo crear, leer, actualizar y eliminar horarios
// La estructura de todos estos archivos es similar a la de users.php, pero en este caso se tocan los datos de la tabla schedules
require_once '../config/cors.php';
require_once '../config/database.php';
require_once 'auth.php';

$mysqli = $conn;
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $query = "SELECT * FROM schedules";
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
        checkRole('admin');
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['group_id'], $data['subject'], $data['teacher_id'], $data['weekday'], $data['start_time'], $data['end_time'], $data['classroom'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing fields"]);
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO schedules (group_id, subject, teacher_id, weekday, start_time, end_time, classroom) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isissss", $data['group_id'], $data['subject'], $data['teacher_id'], $data['day'], $data['start_time'], $data['end_time'], $data['classroom']);
        $stmt->execute();
        echo json_encode(["success" => $stmt->affected_rows > 0]);
        break;

    case 'PUT':
        checkRole('admin');
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing schedule ID"]);
            exit();
        }

        $fields = [];
        $params = [];
        $types = "";

        foreach (['group_id', 'subject', 'teacher_id', 'weekday', 'start_time', 'end_time', 'classroom'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= is_int($data[$field]) ? "i" : "s";
            }
        }

        $params[] = $data['id'];
        $types .= "i";

        $stmt = $mysqli->prepare("UPDATE schedules SET " . implode(", ", $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        echo json_encode(["success" => $stmt->affected_rows > 0]);
        break;

    case 'DELETE':
        checkRole('admin');
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing schedule ID"]);
            exit();
        }

        $stmt = $mysqli->prepare("DELETE FROM schedules WHERE id = ?");
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