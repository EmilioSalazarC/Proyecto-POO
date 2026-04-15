<?php
ini_set('display_errors', 0);
error_reporting(0);
header("Content-Type: application/json; charset=utf-8");
session_start();
require_once "config/Database.php";

header("Content-Type: application/json; charset=utf-8");

// ❗ Quitar errores visibles (rompen JSON)
error_reporting(0);

if (!isset($_SESSION['rol'])) {
    echo json_encode([]);
    exit;
}

$db = new Database();
$conn = $db->conectar();

try {
    $stmt = $conn->prepare("SELECT id_servicio, descripcion, costo FROM servicio");
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode([]);
}