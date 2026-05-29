<?php
// Afficher toutes les erreurs PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Session
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(0, '/', '', false, true);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Credentials: true');

// Vérif session
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error'   => 'non_connecte',
        'session' => session_id(),
        'redirect'=> 'login.html'
    ]);
    exit;
}

require_once 'configuration.php';

$user_id = (int)$_SESSION['user_id'];

// Infos utilisateur
$stmt = $pdo->prepare("SELECT id, username, email, role, date_inscription FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
    exit;
}

// Stats
$stat_reservations = (int)$pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ?")->execute([$user_id]);
$nb_res = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ?");
$nb_res->execute([$user_id]);
$stat_reservations = (int)$nb_res->fetchColumn();

$nb_fav = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE user_id = ?");
$nb_fav->execute([$user_id]);
$stat_favoris = (int)$nb_fav->fetchColumn();

// Favoris
$stmtFav = $pdo->prepare("SELECT f.*, d.nom, d.budget, d.prix_base FROM favoris f JOIN destinations d ON f.destination_id = d.id WHERE f.user_id = ? LIMIT 6");
$stmtFav->execute([$user_id]);
$favoris = $stmtFav->fetchAll();

// Historique
$stmtH = $pdo->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY date_reservation DESC LIMIT 5");
$stmtH->execute([$user_id]);
$historique = $stmtH->fetchAll();

echo json_encode([
    'success'         => true,
    'user'            => [
        'id'               => (int)$user['id'],
        'username'         => $user['username'],
        'email'            => $user['email'],
        'role'             => $user['role'],
        'date_inscription' => $user['date_inscription'],
    ],
    'stats'           => [
        'reservations' => $stat_reservations,
        'favoris'      => $stat_favoris,
        'activites'    => 0,
    ],
    'prochain_voyage' => null,
    'historique'      => $historique,
    'favoris'         => $favoris,
], JSON_UNESCAPED_UNICODE);