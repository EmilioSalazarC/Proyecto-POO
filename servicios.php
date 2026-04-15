<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "config/Database.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['rol'])) {
    echo json_encode([]);
    exit;
}

$db = new Database();
$conn = $db->conectar();

// ─── GET ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == "GET") {

    try {
        $stmt = $conn->prepare("
            SELECT 
                id_servicio_orden,
                Id_orden AS id_orden,
                descripcion,
                costo
            FROM servicio_orden
        ");
        $stmt->execute();

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// ─── POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $data = json_decode(file_get_contents("php://input"));

    if (!$data || !isset($data->orden) || !isset($data->costo)) {
        echo json_encode(["error" => "Datos incompletos"]);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO servicio_orden (Id_orden, descripcion, costo)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $data->orden,
            $data->descripcion ?? '',
            $data->costo
        ]);

        echo json_encode(["ok" => true]);

    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}