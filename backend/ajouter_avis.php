<?php
// =========================================
// BACKEND/AJOUTER_AVIS.PHP — VOYAGEVISTA
// =========================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(0, '/', '', false, true);
session_start();

require_once 'configuration.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../frontend/login.html'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../frontend/historique.html'); exit;
}

$user_id     = (int)$_SESSION['user_id'];
$note        = (int)($_POST['note']           ?? 0);
$commentaire = trim($_POST['commentaire']     ?? '');
$dest_id     = (int)($_POST['destination_id'] ?? 0);

// ── Validations ──────────────────────────────────────────
if ($note < 1 || $note > 5) {
    header('Location: ../frontend/historique.html?error=note_invalide'); exit;
}
if (empty($commentaire)) {
    header('Location: ../frontend/historique.html?error=commentaire_vide'); exit;
}

// ── Vérifier que l'utilisateur a réservé cette destination ─
if ($dest_id > 0) {
    $chk = $pdo->prepare("SELECT id FROM reservations WHERE user_id=? AND destination_id=? LIMIT 1");
    $chk->execute([$user_id, $dest_id]);
    if (!$chk->fetch()) {
        header('Location: ../frontend/historique.html?error=non_autorise'); exit;
    }
}

// ── Insérer ou mettre à jour l'avis ──────────────────────
if ($dest_id > 0) {
    // Vérifier si un avis existe déjà
    $existing = $pdo->prepare("SELECT id FROM avis WHERE user_id=? AND destination_id=?");
    $existing->execute([$user_id, $dest_id]);

    if ($existing->fetch()) {
        // Mettre à jour
        $pdo->prepare("UPDATE avis SET note=?, commentaire=? WHERE user_id=? AND destination_id=?")
            ->execute([$note, $commentaire, $user_id, $dest_id]);
    } else {
        // Insérer avec destination_id
        $pdo->prepare("INSERT INTO avis (user_id, destination_id, note, commentaire, est_valide) VALUES (?,?,?,?,0)")
            ->execute([$user_id, $dest_id, $note, $commentaire]);
    }
} else {
    // Pas de destination_id → insérer sans (compatibilité ancien code)
    $pdo->prepare("INSERT INTO avis (user_id, note, commentaire, est_valide) VALUES (?,?,?,0)")
        ->execute([$user_id, $note, $commentaire]);
}

header('Location: ../frontend/historique.html?success=avis_envoye'); exit;