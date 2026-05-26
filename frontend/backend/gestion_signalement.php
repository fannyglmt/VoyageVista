<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Lister tous les signalements
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM signalements ORDER BY date_creation DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Mettre à jour le statut d'un signalement
if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("UPDATE signalements SET statut = ? WHERE id = ?");
    $stmt->execute([$data['statut'], $data['id']]);
    echo json_encode(['success' => true]);
}

// Envoyer une notification (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$data['user_id'], $data['message']]);
    echo json_encode(['success' => true]);
}

// Récupérer les notifications d'un utilisateur (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY date_envoi DESC");
    $stmt->execute([$_GET['user_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>