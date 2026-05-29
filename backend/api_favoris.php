<?php
// =============================================
// API_FAVORIS.PHP — VoyageVista
// Ajouter / supprimer / lister les favoris
// =============================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(0, '/', '', false, true);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST');

require_once 'configuration.php';

// ── Non connecté ──────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success'  => false,
        'error'    => 'non_connecte',
        'redirect' => '../frontend/login.html'
    ]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

// ── GET : récupérer les favoris de l'utilisateur ─────────
if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT f.id, f.destination_id, d.nom, d.budget,
               d.prix_base, d.image_url, d.categorie, d.region
        FROM favoris f
        JOIN destinations d ON f.destination_id = d.id
        WHERE f.user_id = ?
        ORDER BY f.date_ajout DESC
    ");
    $stmt->execute([$user_id]);
    $favoris = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count'   => count($favoris),
        'data'    => $favoris
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── POST : ajouter ou supprimer un favori ─────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $destination_id = (int)($input['destination_id'] ?? 0);
    $action         = $input['action'] ?? 'toggle'; // toggle / add / remove

    if ($destination_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID destination invalide']);
        exit;
    }

    // Vérifier que la destination existe
    $chk = $pdo->prepare("SELECT id FROM destinations WHERE id = ? AND est_active = 1");
    $chk->execute([$destination_id]);
    if (!$chk->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Destination introuvable']);
        exit;
    }

    // Vérifier si déjà en favori
    $exists = $pdo->prepare("SELECT id FROM favoris WHERE user_id = ? AND destination_id = ?");
    $exists->execute([$user_id, $destination_id]);
    $favori = $exists->fetch();

    if ($action === 'toggle') {
        $action = $favori ? 'remove' : 'add';
    }

    if ($action === 'add' && !$favori) {
        $pdo->prepare("INSERT INTO favoris (user_id, destination_id, date_ajout) VALUES (?, ?, NOW())")
            ->execute([$user_id, $destination_id]);

        echo json_encode([
            'success' => true,
            'action'  => 'added',
            'message' => 'Destination ajoutée aux favoris ❤️'
        ]);

    } elseif ($action === 'remove' && $favori) {
        $pdo->prepare("DELETE FROM favoris WHERE user_id = ? AND destination_id = ?")
            ->execute([$user_id, $destination_id]);

        echo json_encode([
            'success' => true,
            'action'  => 'removed',
            'message' => 'Destination retirée des favoris'
        ]);

    } else {
        echo json_encode([
            'success' => true,
            'action'  => $favori ? 'already_added' : 'already_removed',
            'message' => $favori ? 'Déjà dans les favoris' : 'Pas dans les favoris'
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);