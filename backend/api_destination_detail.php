<?php
// =============================================
// API Détail Destination - VoyageVista
// =============================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'configuration.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ? AND est_active = 1");
$stmt->execute([$id]);
$d = $stmt->fetch();

if (!$d) {
    echo json_encode(['success' => false, 'error' => 'Destination introuvable']);
    exit;
}

// Hébergements liés
$hStmt = $pdo->prepare("SELECT id, nom, type, prix_nuit, image_url, note_moyenne FROM hebergements WHERE destination_id = ? AND est_actif = 1 ORDER BY note_moyenne DESC LIMIT 6");
$hStmt->execute([$id]);
$hebergements = $hStmt->fetchAll();

// Activités liées
$aStmt = $pdo->prepare("SELECT id, nom, categorie, prix, duree_heures, image_url, note_moyenne FROM activites WHERE destination_id = ? AND est_actif = 1 ORDER BY note_moyenne DESC LIMIT 6");
$aStmt->execute([$id]);
$activites = $aStmt->fetchAll();

echo json_encode([
    'success' => true,
    'data'    => [
        'id'               => (int)$d['id'],
        'nom'              => $d['nom'],
        'description'      => $d['description'],
        'pays'             => $d['pays'],
        'region'           => $d['region'],
        'categorie'        => $d['categorie'],
        'budget'           => $d['budget'],
        'prix_base'        => (float)$d['prix_base'],
        'image_url'        => $d['image_url'],
        'note_moyenne'     => (float)$d['note_moyenne'],
        'nb_voyageurs_min' => (int)$d['nb_voyageurs_min'],
        'nb_voyageurs_max' => (int)$d['nb_voyageurs_max'],
        'hebergements'     => $hebergements,
        'activites'        => $activites,
    ]
], JSON_UNESCAPED_UNICODE);