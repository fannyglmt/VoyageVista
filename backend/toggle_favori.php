<?php
// =========================================
// BACKEND/TOGGLE_FAVORI.PHP — VOYAGEVISTA
// Ajouter ou retirer un favori
// =========================================

session_start();
require_once 'configuration.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

$user_id        = $_SESSION['user_id'];
$destination_id = (int)($_POST['destination_id'] ?? 0);
$action         = trim($_POST['action'] ?? '');

if (!$destination_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID destination manquant']);
    exit;
}

if ($action === 'retirer') {
    $stmt = $pdo->prepare('DELETE FROM favoris WHERE user_id = ? AND destination_id = ?');
    $stmt->execute([$user_id, $destination_id]);
    echo json_encode(['success' => true, 'action' => 'retire']);
} else {
    // Ajouter (ignore si déjà présent grâce au UNIQUE KEY)
    $stmt = $pdo->prepare('
        INSERT IGNORE INTO favoris (user_id, destination_id)
        VALUES (?, ?)
    ');
    $stmt->execute([$user_id, $destination_id]);
    echo json_encode(['success' => true, 'action' => 'ajoute']);
}