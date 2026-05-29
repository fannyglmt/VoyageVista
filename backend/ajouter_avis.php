<?php
// =========================================
// BACKEND/AJOUTER_AVIS.PHP — VOYAGEVISTA
// =========================================

session_start();
require_once 'configuration.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../frontend/login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id     = $_SESSION['user_id'];
    $note        = (int)($_POST['note']        ?? 0);
    $commentaire = trim($_POST['commentaire']  ?? '');

    if ($note < 1 || $note > 5 || empty($commentaire)) {
        header('Location: ../frontend/historique.html?error=avis_invalide');
        exit;
    }

    $stmt = $pdo->prepare('
        INSERT INTO avis (user_id, note, commentaire, est_valide)
        VALUES (?, ?, ?, 0)
    ');
    $stmt->execute([$user_id, $note, $commentaire]);

    header('Location: ../frontend/historique.html?success=avis_envoye');
    exit;

} else {
    header('Location: ../frontend/historique.html');
    exit;
}