<?php
header('Content-Type: application/json');
require_once '../config/db.php';

// Suppose qu'on reçoit l'ID du prestataire connecté
$id_prestataire = $_GET['id_prestataire'] ?? null;

if ($id_prestataire) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id_prestataire = ?");
    $stmt->execute([$id_prestataire]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode(['error' => 'ID prestataire requis']);
}
?>