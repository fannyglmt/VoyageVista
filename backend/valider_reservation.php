<?php
// =========================================
// BACKEND/VALIDER_RESERVATION.PHP
// =========================================

session_start();
require_once 'configuration.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../frontend/login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id   = $_SESSION['user_id'];
    $prenom    = trim($_POST['prenom']    ?? '');
    $nom       = trim($_POST['nom']       ?? '');
    $email     = trim($_POST['email']     ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $demandes  = trim($_POST['demandes']  ?? '');
    $paiement  = trim($_POST['paiement']  ?? '');

    if (empty($prenom) || empty($nom) || empty($email)) {
        header('Location: ../frontend/validation-reservation.html?error=champs_vides');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../frontend/validation-reservation.html?error=email_invalide');
        exit;
    }

    // Insertion de la réservation en BDD
    // Adapte les colonnes selon ta table "reservations"
    $stmt = $pdo->prepare('
        INSERT INTO reservations (user_id, statut, mode_paiement, demandes_speciales, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$user_id, 'confirmee', $paiement, $demandes]);

    // Redirige vers une page de confirmation (à créer)
    header('Location: ../frontend/confirmation.html?success=reservation_confirmee');
    exit;

} else {
    header('Location: ../frontend/validation-reservation.html');
    exit;
}