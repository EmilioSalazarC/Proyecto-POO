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
    $stmt = $conn->prepare("SELECT * FROM vehiculo");
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// POST → registrar vehículo
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($rol == "consultor") {
        http_response_code(403);
        die(json_encode(["error" => "No autorizado"]));
    }

    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->placas) || !isset($data->marca)) {
        http_response_code(400);
        die(json_encode(["error" => "Placas y marca son requeridas"]));
    }

    $stmt = $conn->prepare(
        "INSERT INTO vehiculo(Placas, Marca, Modelo, anio, Id_cliente)
         VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        strtoupper(trim($data->placas)),
        $data->marca,
        $data->modelo ?? null,
        $data->anio   ?? null,
        $data->cliente
    ]);

    echo json_encode(["mensaje" => "Vehículo registrado", "id" => $conn->lastInsertId()]);
}

// DELETE → solo admin
if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    if ($rol != "admin") {
        http_response_code(403);
        die(json_encode(["error" => "Solo el administrador puede eliminar"]));
    }

    $data = json_decode(file_get_contents("php://input"));

    $stmt = $conn->prepare("DELETE FROM vehiculo WHERE id_vehiculo = ?");
    $stmt->execute([$data->id]);

    echo json_encode(["mensaje" => "Vehículo eliminado"]);
}