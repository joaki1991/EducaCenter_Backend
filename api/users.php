<?php
// Archivo de la API para obtener, crear, actualizar y eliminar usuarios en funcion del tipo de peticion que me llegue desde el cliente
require_once '../config/cors.php'; // Incluyo el archivo de configuración de CORS
require_once '../config/database.php'; // Incluyo el archivo de configuración de la base de datos
require_once 'auth.php'; // Incluyo el archivo de autenticación para verificar el token y obtener el usuario en $user
// En caso de que el usuario no este autenticado este archivo no se ejecutará y se devolverá un error 401 (no autorizado) desde el archivo auth.php
// En primer lugar, voy a renombrar el objeto de conexion a mi base de datos como mysqli
$mysqli = $conn;

// Ahora, voy a comprobar el tipo de peticion que me llega desde el cliente
$method = $_SERVER['REQUEST_METHOD'];

// En función del tipo de peticion que me llega, voy a realizar una u otra consulta a la base de datos
switch ($method) {
    case 'GET':
        // En caso de una peticion GET, voy a coprobar los parametros que me llegan por la URL para devolver toda la informacion del usuario
        // a excepcion de la contraseña. Esta consulta podrá realizarla cualquier usuario autenticado
        $query = "SELECT id, name, surname, email, role, created_at, updated_at FROM users";
        $params = []; // Inicializo un array vacio para los parametros que voy a pasar a la consulta

        if (!empty($_GET)) {
            $filters = [];
            foreach ($_GET as $key => $value) {
                if ($key !== 'password') { // Excluyo el campo 'password', ya que no quiero que se muestre
                    $filters[] = "$key = ?";
                    $params[] = $value;
                }
            }
            $query .= " WHERE " . implode(" AND ", $filters); // Agrego un where y pongo cada uno de los campos enviados por el get
        }

        $stmt = $mysqli->prepare($query); // Preparo la consulta

        if (count($params) > 0) {
            $types = str_repeat("s", count($params)); // Defino el tipo de cada uno de los parametros que voy a pasar a la consulta
            $stmt->bind_param($types, ...$params); 
        }
        
        $stmt->execute(); // Ejecuto la consulta
        $result = $stmt->get_result();        
        //Si hay datos, los enviamos:
		if($result->num_rows>0) {
	    	//Guardamos los datos obbtenidos de la bdd en un array:
            $users = $result->fetch_all(MYSQLI_ASSOC);
		    echo json_encode($users);
		} else {
			//No se han encontrado datos:
    		echo json_encode(["error"=>"No users found"]);
		}
       
        break;

    case 'POST':
        // En caso de una peticion POST, voy a crear un nuevo usuario en la base de datos, comprobando primero que me lleguen los campos necesarios
        // Este tipo de consulta solo la podra realizar un usuario administrador, por lo que voy a comprobar el rol del usuario autenticado
        checkRole('admin'); // Compruebo que el rol del usuario autenticado es admin, si no lo es, se devuelve un error 403 (prohibido) y paramos la ejecución
        $data = json_decode(file_get_contents("php://input"), true); // Decodifico el JSON que me llega en el body de la peticion
        if (!isset($data['name'], $data['surname'], $data['email'], $data['password'], $data['role'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing fields"]);
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO users (name, surname, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $password = password_hash($data['password'], PASSWORD_DEFAULT); // Encripto la contraseña antes de guardarla en la base de datos
        $stmt->bind_param("sssss", $data['name'], $data['surname'], $data['email'], $password, $data['role']);
        $stmt->execute(); // Ejecuto la sentencia SQL para insertar el nuevo usuario
        echo json_encode(["success" => $stmt->affected_rows > 0]); // Devuelvo un JSON con el resultado de la insercion 
        // En caso de que se haya insertado correctamente, el affected_rows sera mayor que 0, por lo que devolvera true, en caso contrario devolvera false
        break;

    case 'PUT':
        // En caso de una peticion PUT, voy a actualizar un usuario en la base de datos, comprobando primero que me lleguen el id y los campos necesarios
        // En este caso no voy a comprobar el rol del usuario autenticado, ya que cualquier usuario autenticado puede actualizar su propia informacion
        $data = json_decode(file_get_contents("php://input"), true);
        // Primero compriuebo que me llegue el id del usuario a actualizar, y si no me llega, devuelvo un error 400
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing user ID"]);
            exit();
        }

        $fields = [];
        $params = [];
        $types = "";
        // Recorro los campos que me llegan por el body de la peticion y voy agregando los campos a actualizar al array fields y los valores a params
        foreach (['name', 'surname', 'email', 'password', 'role'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $field === 'password' ? password_hash($data[$field], PASSWORD_DEFAULT) : $data[$field]; // Encripto la contraseña si es el campo password
                $types .= "s";
            }
        }

        $params[] = $data['id'];
        $types .= "i";

        $stmt = $mysqli->prepare("UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        echo json_encode(["success" => $stmt->affected_rows > 0]);
        // Devuelvo un JSON con el resultado de la actualizacion si se ha actualizado correctamente, el affected_rows sera mayor que 0, por lo que devolvera true, en caso contrario devolvera false
        break;

    case 'DELETE':
        // En caso de una peticion DELETE, voy a eliminar un usuario de la base de datos, comprobando primero que me llegue el id del usuario a eliminar
        // Este tipo de consulta solo la podra realizar un usuario administrador, por lo que voy a comprobar el rol del usuario autenticado
        checkRole('admin'); // Compruebo que el rol del usuario autenticado es admin, si no lo es, se devuelve un error 403 (prohibido) y paramos la ejecución
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing user ID"]);
            exit();
        }

        $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $data['id']);
        $stmt->execute(); // Si me llega el id, ejecuto la sentencia SQL para eliminar el usuario       
        echo json_encode(["success" => $stmt->affected_rows > 0]);
        // Devuelvo un JSON con el resultado de la eliminacion si se ha eliminado correctamente, el affected_rows sera mayor que 0, por lo que devolvera true, en caso contrario devolvera false
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]); // Devuelvo un error 405 si el metodo no es GET, POST, PUT o DELETE
}

$mysqli->close(); // Por último, cierro la conexion a la base de datos

?>
