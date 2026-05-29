<?php
// =============================================
// API Activités - VoyageVista
// =============================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'configuration.php';

$categorie = $_GET['categorie'] ?? null;
$dest_id   = $_GET['dest_id']   ?? null;
$search    = trim($_GET['search'] ?? '');
$limit     = min((int)($_GET['limit'] ?? 50), 100);

$query  = "
    SELECT
        a.id, a.nom, a.description, a.categorie,
        a.prix, a.duree_heures, a.image_url, a.note_moyenne,
        d.nom AS destination_nom, d.region,
        u.username AS prestataire_nom
    FROM activites a
    JOIN destinations d ON a.destination_id = d.id
    JOIN utilisateurs u ON a.prestataire_id = u.id
    WHERE a.est_actif = 1
";
$params = [];

if ($categorie) {
    $query .= " AND a.categorie = ?";
    $params[] = $categorie;
}
if ($dest_id) {
    $query .= " AND a.destination_id = ?";
    $params[] = (int)$dest_id;
}
if ($search !== '') {
    $query .= " AND (a.nom LIKE ? OR a.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY a.note_moyenne DESC, a.date_creation DESC LIMIT ?";
$params[] = $limit;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$activites = $stmt->fetchAll();

$result = array_map(function($a) {
    return [
        'id'              => (int)$a['id'],
        'nom'             => $a['nom'],
        'description'     => $a['description'],
        'categorie'       => $a['categorie'],
        'prix'            => (float)$a['prix'],
        'duree_heures'    => (float)$a['duree_heures'],
        'image_url'       => $a['image_url'] ?: null,
        'note_moyenne'    => (float)$a['note_moyenne'],
        'destination_nom' => $a['destination_nom'],
        'region'          => $a['region'],
        'prestataire_nom' => $a['prestataire_nom'],
    ];
}, $activites);

echo json_encode([
    'success' => true,
    'count'   => count($result),
    'data'    => $result
], JSON_UNESCAPED_UNICODE);