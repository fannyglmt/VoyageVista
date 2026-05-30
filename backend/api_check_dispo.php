<?php
// =============================================
// API_CHECK_DISPO.PHP — VoyageVista
// Vérifie les disponibilités d'un hébergement
// GET  ?heb_id=1 → liste des créneaux dispo
// POST ?heb_id=1&date_debut=...&date_fin=... → vérif
// =============================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(0, '/', '', false, true);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Credentials: true');

require_once 'configuration.php';

$heb_id     = (int)($_GET['heb_id']     ?? $_POST['heb_id']     ?? 0);
$date_debut = $_GET['date_debut'] ?? $_POST['date_debut'] ?? null;
$date_fin   = $_GET['date_fin']   ?? $_POST['date_fin']   ?? null;

if (!$heb_id) {
    echo json_encode(['success' => false, 'error' => 'ID hébergement manquant']);
    exit;
}

// ── Récupérer tous les créneaux disponibles ───────────────
$stmt = $pdo->prepare("
    SELECT d.id, d.date_debut, d.date_fin, d.places_dispo
    FROM disponibilites d
    JOIN services s ON d.service_id = s.id
    WHERE s.ref_id = ? AND s.type = 'hebergement'
    AND d.est_bloque = 0
    AND d.date_fin >= CURDATE()
    ORDER BY d.date_debut ASC
");
$stmt->execute([$heb_id]);
$creneaux = $stmt->fetchAll();

// ── Si dates fournies → vérifier la disponibilité ─────────
if ($date_debut && $date_fin) {
    if ($date_fin <= $date_debut) {
        echo json_encode(['success' => false, 'error' => 'La date de fin doit être après la date de début']);
        exit;
    }

    // Vérifier que les dates sont dans un créneau disponible
    $disponible = false;
    $places     = 0;
    $nuits      = (strtotime($date_fin) - strtotime($date_debut)) / 86400;
    $prix_total = 0;

    // Récupérer le prix nuit de l'hébergement
    $heb = $pdo->prepare("SELECT prix_nuit, capacite FROM hebergements WHERE id = ?");
    $heb->execute([$heb_id]);
    $hebergement = $heb->fetch();
    $prix_nuit   = (float)($hebergement['prix_nuit'] ?? 0);
    $capacite    = (int)($hebergement['capacite']    ?? 1);
    $prix_total  = $prix_nuit * $nuits;

    foreach ($creneaux as $c) {
        if ($c['date_debut'] <= $date_debut && $c['date_fin'] >= $date_fin) {
            $disponible = true;
            $places     = (int)$c['places_dispo'];
            break;
        }
    }

    echo json_encode([
        'success'     => true,
        'disponible'  => $disponible,
        'places_dispo'=> $places,
        'nuits'       => (int)$nuits,
        'prix_nuit'   => $prix_nuit,
        'prix_total'  => $prix_total,
        'capacite'    => $capacite,
        'message'     => $disponible
            ? "✅ Disponible du $date_debut au $date_fin ($nuits nuits) — $places place(s)"
            : "❌ Pas de disponibilité pour ces dates",
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Retourner les créneaux disponibles ────────────────────
// Construire la liste des dates bloquées (déjà réservées)
$reservations = $pdo->prepare("
    SELECT r.date_debut, r.date_fin
    FROM reservations r
    WHERE r.service_id IN (
        SELECT s.id FROM services s 
        WHERE s.ref_id = ? AND s.type = 'hebergement'
    )
    AND r.statut IN ('confirmee', 'en_attente')
    AND r.date_fin >= CURDATE()
");
$reservations->execute([$heb_id]);
$reservees = $reservations->fetchAll();

echo json_encode([
    'success'     => true,
    'creneaux'    => $creneaux,
    'reservees'   => $reservees,
    'count'       => count($creneaux),
], JSON_UNESCAPED_UNICODE);