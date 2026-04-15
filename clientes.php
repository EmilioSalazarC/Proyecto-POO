<?php
ini_set('display_errors', 0);
error_reporting(0);
header("Content-Type: application/json; charset=utf-8");
session_start();
require_once "config/Database.php";

// 🔐 Verificar sesión
if (!isset($_SESSION['rol'])) {
    die("No autorizado");
}

$rol = $_SESSION['rol'];

$db = new Database();
$conn = $db->conectar();


// 🟢 GET → TODOS pueden ver
if ($_SERVER['REQUEST_METHOD'] == "GET") {
    $stmt = $conn->prepare("SELECT * FROM cliente");
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}


// 🟡 POST → admin y editor
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($rol == "consultor") {
        die("No autorizado");
    }

    $data = json_decode(file_get_contents("php://input"));

    $stmt = $conn->prepare(
        "INSERT INTO cliente(nombre, telefono) VALUES (?, ?)"
    );

    $stmt->execute([$data->nombre, $data->telefono]);

    echo json_encode(["mensaje" => "Cliente agregado"]);
}


// 🟡 PUT → admin y editor
if ($_SERVER['REQUEST_METHOD'] == "PUT") {
    if ($rol == "consultor") {
        die("No autorizado");
    }

    $data = json_decode(file_get_contents("php://input"));

    $stmt = $conn->prepare(
        "UPDATE cliente SET nombre=?, telefono=? WHERE id_cliente=?"
    );

    $stmt->execute([$data->nombre, $data->telefono, $data->id]);

    echo json_encode(["mensaje" => "Cliente actualizado"]);
}


// 🔴 DELETE → SOLO admin
if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    if ($rol != "admin") {
        die("Solo el administrador puede eliminar");
    }

    $data = json_decode(file_get_contents("php://input"));

    $stmt = $conn->prepare(
        "DELETE FROM cliente WHERE id_cliente=?"
    );

    $stmt->execute([$data->id]);

    echo json_encode(["mensaje" => "Cliente eliminado"]);
}