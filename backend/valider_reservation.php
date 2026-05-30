<?php
// =========================================
// BACKEND/VALIDER_RESERVATION.PHP
// =========================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(0, '/', '', false, true);
session_start();

require_once 'configuration.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../frontend/login.html'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../frontend/validation-reservation.html'); exit;
}

$user_id = (int)$_SESSION['user_id'];
$panier  = $_SESSION['panier'] ?? null;

// ── Vérifications panier ──────────────────────────────────
if (!$panier || !$panier['destination_id']) {
    header('Location: ../frontend/panier.html?error=panier_vide'); exit;
}

// ── Récupérer les infos du formulaire ─────────────────────
// CORRECTION : username + email au lieu de prenom + nom
$email     = trim($_POST['email']     ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$demandes  = trim($_POST['demandes']  ?? '');
$paiement  = trim($_POST['paiement']  ?? 'carte');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../frontend/validation-reservation.html?error=email_invalide'); exit;
}

// ── Données du panier ─────────────────────────────────────
$dest_id      = (int)$panier['destination_id'];
$service_id   = $panier['hebergement']['id'] ?? null;
$date_debut   = $panier['date_debut']        ?? date('Y-m-d');
$date_fin     = $panier['date_fin']          ?? date('Y-m-d', strtotime('+7 days'));
$nb_voyageurs = (int)($panier['nb_voyageurs'] ?? 1);
$prix_total   = (float)($panier['total']      ?? 0);

// ── INSERT réservation ────────────────────────────────────
$stmt = $pdo->prepare("
    INSERT INTO reservations
        (user_id, destination_id, service_id, date_debut, date_fin,
         nb_voyageurs, prix_total, statut, date_reservation)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmee', NOW())
");
$stmt->execute([$user_id, $dest_id, $service_id, $date_debut, $date_fin, $nb_voyageurs, $prix_total]);

$reservation_id = $pdo->lastInsertId();

// ── Notification de confirmation ──────────────────────────
$message_notif = "✈️ Ta réservation a été confirmée ! Bon voyage 🌴";

$stmt_notif = $pdo->prepare("
    INSERT INTO notifications (user_id, message, type, lu, date_envoi)
    VALUES (?, ?, 'reservation', 0, NOW())
");
$stmt_notif->execute([$user_id, $message_notif]);

// ── Vider le panier ───────────────────────────────────────
$_SESSION['panier'] = [
    'destination_id'=>null,'destination_nom'=>null,'destination_img'=>null,
    'date_debut'=>null,'date_fin'=>null,'nb_voyageurs'=>1,
    'transport'=>null,'hebergement'=>null,'activites'=>[],'total'=>0,
];

header("Location: http://localhost:8888/Web2026/VoyageVista/frontend/confirmation.html?id=$reservation_id&success=reservation_confirmee");
exit;