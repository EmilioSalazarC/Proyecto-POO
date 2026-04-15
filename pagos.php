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
    $stmt = $conn->prepare("SELECT * FROM pagos ORDER BY id_pago DESC");
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// POST → registrar pago
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($rol == "consultor") {
        http_response_code(403);
        die(json_encode(["error" => "No autorizado"]));
    }

    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->monto) || !isset($data->orden)) {
        http_response_code(400);
        die(json_encode(["error" => "Datos incompletos"]));
    }

    $stmt = $conn->prepare(
        "INSERT INTO pagos(monto, Fecha, Id_orden, metodo) VALUES (?, ?, ?, ?)"
    );

    $stmt->execute([
        $data->monto,
        $data->fecha  ?? date('Y-m-d'),
        $data->orden,
        $data->metodo ?? 'Efectivo'
    ]);

    echo json_encode(["mensaje" => "Pago registrado", "id" => $conn->lastInsertId()]);
}
