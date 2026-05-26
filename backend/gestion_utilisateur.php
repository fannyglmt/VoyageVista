<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, username, email, role FROM utilisateurs");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?");
    $stmt->execute([$data['role'], $data['id']]);
    echo json_encode(['message' => 'Rôle mis à jour']);
}
?>