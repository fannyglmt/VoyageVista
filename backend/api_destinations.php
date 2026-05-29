<?php
// =============================================
// API Destinations - VoyageVista
// =============================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'configuration.php';

$region    = $_GET['region']    ?? null;
$categorie = $_GET['categorie'] ?? null;
$budget    = $_GET['budget']    ?? null;
$search    = trim($_GET['search'] ?? '');
$sort      = $_GET['sort']      ?? 'popular';
$limit     = min((int)($_GET['limit'] ?? 50), 100);

$query  = "
    SELECT
        d.id, d.nom, d.description, d.pays, d.region,
        d.categorie, d.budget, d.prix_base, d.image_url,
        d.note_moyenne, d.nb_voyageurs_min, d.nb_voyageurs_max,
        COUNT(r.id) AS nb_reservations
    FROM destinations d
    LEFT JOIN reservations r ON r.destination_id = d.id
    WHERE d.est_active = 1
";
$params = [];

if ($region) {
    $query .= " AND d.region = ?";
    $params[] = $region;
}
if ($categorie) {
    $query .= " AND d.categorie = ?";
    $params[] = $categorie;
}
if ($budget) {
    $query .= " AND d.budget = ?";
    $params[] = $budget;
}
if ($search !== '') {
    $query .= " AND (d.nom LIKE ? OR d.description LIKE ? OR d.pays LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " GROUP BY d.id";

switch ($sort) {
    case 'priceAsc':  $query .= " ORDER BY d.prix_base ASC"; break;
    case 'priceDesc': $query .= " ORDER BY d.prix_base DESC"; break;
    case 'rating':    $query .= " ORDER BY d.note_moyenne DESC"; break;
    case 'new':       $query .= " ORDER BY d.date_creation DESC"; break;
    case 'trend':
    case 'popular':
    default:          $query .= " ORDER BY nb_reservations DESC, d.note_moyenne DESC";
}

$query .= " LIMIT ?";
$params[] = $limit;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$destinations = $stmt->fetchAll();

$result = array_map(function($d) {
    return [
        'id'               => (int)$d['id'],
        'nom'              => $d['nom'],
        'description'      => $d['description'],
        'pays'             => $d['pays'],
        'region'           => $d['region'],
        'categorie'        => $d['categorie'],
        'budget'           => $d['budget'],
        'prix_base'        => (float)$d['prix_base'],
        'image_url'        => $d['image_url'] ?: null,
        'note_moyenne'     => (float)$d['note_moyenne'],
        'nb_voyageurs_min' => (int)$d['nb_voyageurs_min'],
        'nb_voyageurs_max' => (int)$d['nb_voyageurs_max'],
        'nb_reservations'  => (int)$d['nb_reservations'],
    ];
}, $destinations);

echo json_encode([
    'success' => true,
    'count'   => count($result),
    'data'    => $result
], JSON_UNESCAPED_UNICODE);