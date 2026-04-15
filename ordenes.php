<?php
ini_set('display_errors', 0);
error_reporting(0);
header("Content-Type: application/json; charset=utf-8");
session_start();
require_once "config/Database.php";

if (!isset($_SESSION['rol'])) {
    http_response_code(401);
    die(json_encode(["error" => "No autorizado"]));
}

header("Content-Type: application/json");

$rol  = $_SESSION['rol'];
$db   = new Database();
$conn = $db->conectar();

// GET → todos pueden ver
if ($_SERVER['REQUEST_METHOD'] == "GET") {

    $stmt = $conn->prepare("
        SELECT 
            id_orden,
            fecha,
            Id_cliente AS id_cliente,
            Id_vehiculo AS id_vehiculo,
            estatus,
            total
        FROM orden_servicio
        ORDER BY id_orden DESC
    ");

    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// POST → crear nueva orden
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($rol == "consultor") {
        http_response_code(403);
        die(json_encode(["error" => "No autorizado"]));
    }

    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->cliente) || !isset($data->vehiculo) || !isset($data->fecha)) {
        http_response_code(400);
        die(json_encode(["error" => "Datos incompletos"]));
    }

    $stmt = $conn->prepare(
        "INSERT INTO orden_servicio(fecha, Id_cliente, Id_vehiculo, estatus, total)
         VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $data->fecha,
        $data->cliente,
        $data->vehiculo,
        $data->estatus ?? 'pendiente',
        $data->total   ?? 0
    ]);

    echo json_encode(["mensaje" => "Orden creada", "id" => $conn->lastInsertId()]);
}

// PUT → actualizar estatus y/o total
if ($_SERVER['REQUEST_METHOD'] == "PUT") {
    if ($rol == "consultor") {
        http_response_code(403);
        die(json_encode(["error" => "No autorizado"]));
    }

    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->id)) {
        http_response_code(400);
        die(json_encode(["error" => "ID requerido"]));
    }

    $campos = [];
    $vals   = [];

    if (isset($data->estatus)) {
        $campos[] = "estatus = ?";
        $vals[]   = $data->estatus;
    }

    if (isset($data->total)) {
        $campos[] = "total = ?";
        $vals[]   = $data->total;
    }

    if (empty($campos)) {
        http_response_code(400);
        die(json_encode(["error" => "Nada que actualizar"]));
    }

    $vals[] = $data->id;

    $stmt = $conn->prepare(
        "UPDATE orden_servicio SET " . implode(", ", $campos) . " WHERE id_orden = ?"
    );

    $stmt->execute($vals);

    echo json_encode(["mensaje" => "Orden actualizada"]);
}
