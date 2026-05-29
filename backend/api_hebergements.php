<?php
// =============================================
// API Hébergements - VoyageVista
// Retourne la liste des hébergements en JSON
// =============================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'configuration.php';

// Filtres optionnels depuis l'URL
$type       = $_GET['type']    ?? null;
$budget_max = $_GET['budget']  ?? null;
$dest_id    = $_GET['dest_id'] ?? null;
$search     = trim($_GET['search'] ?? '');
$limit      = min((int)($_GET['limit'] ?? 50), 100);

$query  = "
    SELECT 
        h.id,
        h.nom,
        h.description,
        h.type,
        h.prix_nuit,
        h.capacite,
        h.image_url,
        h.note_moyenne,
        h.est_actif,
        d.nom AS destination_nom,
        d.region,
        d.budget,
        u.username AS prestataire_nom
    FROM hebergements h
    JOIN destinations d ON h.destination_id = d.id
    JOIN utilisateurs u ON h.prestataire_id = u.id
    WHERE h.est_actif = 1
";
$params = [];

if ($type) {
    $query .= " AND h.type = ?";
    $params[] = $type;
}
if ($budget_max) {
    $query .= " AND h.prix_nuit <= ?";
    $params[] = (float)$budget_max;
}
if ($dest_id) {
    $query .= " AND h.destination_id = ?";
    $params[] = (int)$dest_id;
}
if ($search !== '') {
    $query .= " AND (h.nom LIKE ? OR d.nom LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY h.note_moyenne DESC, h.date_creation DESC LIMIT ?";
$params[] = $limit;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$hebergements = $stmt->fetchAll();

// Formater les données pour le frontend
$result = array_map(function($h) {
    return [
        'id'               => (int)$h['id'],
        'nom'              => $h['nom'],
        'description'      => $h['description'],
        'type'             => $h['type'],
        'prix_nuit'        => (float)$h['prix_nuit'],
        'capacite'         => (int)$h['capacite'],
        'image_url'        => $h['image_url'] ?: 'hebergement-bg.jpg',
        'note_moyenne'     => (float)$h['note_moyenne'],
        'destination_nom'  => $h['destination_nom'],
        'region'           => $h['region'],
        'budget'           => $h['budget'],
        'prestataire_nom'  => $h['prestataire_nom'],
    ];
}, $hebergements);

echo json_encode([
    'success' => true,
    'count'   => count($result),
    'data'    => $result
], JSON_UNESCAPED_UNICODE);