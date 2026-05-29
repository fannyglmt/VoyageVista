<?php
// =============================================
// API Détail Hébergement - VoyageVista
// =============================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'configuration.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        h.*,
        d.nom AS destination_nom, d.region, d.budget, d.pays,
        u.username AS prestataire_nom
    FROM hebergements h
    JOIN destinations d ON h.destination_id = d.id
    JOIN utilisateurs u ON h.prestataire_id = u.id
    WHERE h.id = ? AND h.est_actif = 1
");
$stmt->execute([$id]);
$h = $stmt->fetch();

if (!$h) {
    echo json_encode(['success' => false, 'error' => 'Hébergement introuvable']);
    exit;
}

// Disponibilités
$dispos = $pdo->prepare("
    SELECT d.date_debut, d.date_fin, d.places_dispo, d.est_bloque
    FROM disponibilites d
    JOIN services s ON d.service_id = s.id
    WHERE s.ref_id = ? AND s.type = 'hebergement' AND d.est_bloque = 0
    AND d.date_fin >= CURDATE()
    ORDER BY d.date_debut ASC
");
$dispos->execute([$id]);
$disponibilites = $dispos->fetchAll();

echo json_encode([
    'success' => true,
    'data'    => [
        'id'              => (int)$h['id'],
        'nom'             => $h['nom'],
        'description'     => $h['description'],
        'type'            => $h['type'],
        'prix_nuit'       => (float)$h['prix_nuit'],
        'capacite'        => (int)$h['capacite'],
        'image_url'       => $h['image_url'] ?: 'hebergement-bg.jpg',
        'note_moyenne'    => (float)$h['note_moyenne'],
        'destination_nom' => $h['destination_nom'],
        'region'          => $h['region'],
        'pays'            => $h['pays'],
        'budget'          => $h['budget'],
        'prestataire_nom' => $h['prestataire_nom'],
        'disponibilites'  => $disponibilites,
    ]
], JSON_UNESCAPED_UNICODE);