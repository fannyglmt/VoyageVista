<?php
// =============================================
// API_TRANSPORTS.PHP — VoyageVista
// =============================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(0, '/', '', false, true);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Credentials: true');

require_once 'configuration.php';

$type   = $_GET['type']   ?? null;
$search = trim($_GET['search'] ?? '');
$limit  = min((int)($_GET['limit'] ?? 50), 100);

$query  = "SELECT t.*, s.id AS service_id
           FROM transports t
           LEFT JOIN services s ON s.ref_id = t.id AND s.type = 'transport'
           WHERE t.est_actif = 1";
$params = [];

if ($type && $type !== 'all') {
    $query   .= " AND t.type = ?";
    $params[] = $type;
}
if ($search !== '') {
    $query   .= " AND (t.nom LIKE ? OR t.depart LIKE ? OR t.arrivee LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY t.note_moyenne DESC LIMIT ?";
$params[] = $limit;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transports = $stmt->fetchAll();

$result = array_map(function($t) {
    return [
        'id'          => (int)$t['id'],
        'service_id'  => (int)$t['service_id'],
        'nom'         => $t['nom'],
        'description' => $t['description'],
        'type'        => $t['type'],
        'depart'      => $t['depart'],
        'arrivee'     => $t['arrivee'],
        'duree'       => $t['duree'],
        'prix'        => (float)$t['prix'],
        'note_moyenne'=> (float)$t['note_moyenne'],
        'image_url'   => $t['image_url'] ?: 'transport-avion.jpg',
        'co2_reduit'  => (bool)$t['co2_reduit'],
    ];
}, $transports);

echo json_encode([
    'success' => true,
    'count'   => count($result),
    'data'    => $result,
], JSON_UNESCAPED_UNICODE);