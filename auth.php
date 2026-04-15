<?php
ini_set('display_errors', 0);
error_reporting(0);

session_start();
require_once "config/Database.php";

header("Content-Type: application/json; charset=utf-8");
ini_set('display_errors', 1);
error_reporting(E_ALL);

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->usuario) || !isset($data->password)) {
    echo json_encode(["success" => false]);
    exit;
}

$db = new Database();
$conn = $db->conectar();

$stmt = $conn->prepare("SELECT * FROM usuario WHERE nombre = ?");
$stmt->execute([$data->usuario]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $data->password == $user['password']) {

    $_SESSION['usuario'] = $user['nombre'];
    $_SESSION['rol'] = $user['rol'];

    echo json_encode([
        "success" => true,
        "rol" => $user['rol']
    ]);

} else {
    echo json_encode(["success" => false]);
}