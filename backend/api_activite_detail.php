<?php
// =============================================
// API_ACTIVITE_DETAIL.PHP — VoyageVista
// =============================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(0, '/', '', false, true);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Credentials: true');

require_once 'configuration.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.*, d.nom AS destination_nom, d.region, u.username AS prestataire_nom
    FROM activites a
    JOIN destinations d ON a.destination_id = d.id
    JOIN utilisateurs u ON a.prestataire_id = u.id
    WHERE a.id = ? AND a.est_actif = 1
");
$stmt->execute([$id]);
$a = $stmt->fetch();

if (!$a) {
    echo json_encode(['success' => false, 'error' => 'Activité introuvable']);
    exit;
}

echo json_encode([
    'success' => true,
    'data'    => [
        'id'              => (int)$a['id'],
        'nom'             => $a['nom'],
        'description'     => $a['description'],
        'categorie'       => $a['categorie'],
        'prix'            => (float)$a['prix'],
        'duree_heures'    => (float)$a['duree_heures'],
        'image_url'       => $a['image_url'],
        'note_moyenne'    => (float)$a['note_moyenne'],
        'destination_nom' => $a['destination_nom'],
        'region'          => $a['region'],
        'prestataire_nom' => $a['prestataire_nom'],
    ]
], JSON_UNESCAPED_UNICODE);