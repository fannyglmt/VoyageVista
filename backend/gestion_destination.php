<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("INSERT INTO destinations (nom, description, prix_base) VALUES (?, ?, ?)");
    $stmt->execute([$data['nom'], $data['description'], $data['prix']]);
    echo json_encode(['message' => 'Destination créée']);
} elseif ($method === 'DELETE') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM destinations WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['message' => 'Destination supprimée']);
}
?>