<?php
// =============================================
// API_NOTIFICATIONS.PHP — VoyageVista
// Notifications de l'utilisateur connecté
// GET  → liste des notifications
// POST → marquer comme lu / supprimer
// =============================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(0, '/', '', false, true);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST');

require_once 'configuration.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'non_connecte']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

// ── GET : récupérer les notifications ────────────────────
if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT id, message, type, lu, date_envoi
        FROM notifications
        WHERE user_id = ?
        ORDER BY date_envoi DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll();

    $non_lues = array_filter($notifs, fn($n) => !$n['lu']);

    echo json_encode([
        'success'   => true,
        'count'     => count($notifs),
        'non_lues'  => count($non_lues),
        'data'      => $notifs,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── POST : actions ────────────────────────────────────────
if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $action = $input['action'] ?? '';
    $id     = (int)($input['id'] ?? 0);

    switch ($action) {

        // Marquer une notification comme lue
        case 'lire':
            $pdo->prepare("UPDATE notifications SET lu = 1 WHERE id = ? AND user_id = ?")
                ->execute([$id, $user_id]);
            echo json_encode(['success' => true, 'action' => 'lire']);
            break;

        // Tout marquer comme lu
        case 'tout_lire':
            $pdo->prepare("UPDATE notifications SET lu = 1 WHERE user_id = ?")
                ->execute([$user_id]);
            echo json_encode(['success' => true, 'action' => 'tout_lire']);
            break;

        // Supprimer une notification
        case 'supprimer':
            $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")
                ->execute([$id, $user_id]);
            echo json_encode(['success' => true, 'action' => 'supprimer']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);