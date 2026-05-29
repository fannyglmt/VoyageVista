<?php
// =============================================
// API_PROFIL.PHP — VoyageVista
// Retourne les données du profil utilisateur connecté
// =============================================
header('Content-Type: application/json; charset=utf-8');


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'non_connecte', 'redirect' => 'login.html']);
    exit;
}

require_once 'configuration.php';
session_start();

$user_id = (int)$_SESSION['user_id'];

// ── Infos utilisateur ────────────────────────────────────
$stmt = $pdo->prepare("SELECT id, username, email, role, date_inscription, derniere_connexion FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
    exit;
}

// ── Stats ────────────────────────────────────────────────
$nb_reservations = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ?");
$nb_reservations->execute([$user_id]);
$stat_reservations = (int)$nb_reservations->fetchColumn();

$nb_favoris = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE user_id = ?");
$nb_favoris->execute([$user_id]);
$stat_favoris = (int)$nb_favoris->fetchColumn();

$nb_activites = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN services s ON r.service_id = s.id WHERE r.user_id = ? AND s.type = 'activite'");
$nb_activites->execute([$user_id]);
$stat_activites = (int)$nb_activites->fetchColumn();

// ── Prochain voyage ──────────────────────────────────────
$prochainVoyage = null;
$stmt2 = $pdo->prepare("
    SELECT r.*, 
        COALESCE(h.nom, a.nom) AS nom_service,
        COALESCE(h.image_url, a.image_url) AS image_url,
        d.nom AS destination_nom,
        dispo.date_debut, dispo.date_fin
    FROM reservations r
    LEFT JOIN services s ON r.service_id = s.id
    LEFT JOIN hebergements h ON s.type = 'hebergement' AND s.ref_id = h.id
    LEFT JOIN activites a ON s.type = 'activite' AND s.ref_id = a.id
    LEFT JOIN destinations d ON COALESCE(h.destination_id, a.destination_id) = d.id
    LEFT JOIN disponibilites dispo ON r.disponibilite_id = dispo.id
    WHERE r.user_id = ? AND r.statut IN ('confirmee','en_attente') AND dispo.date_debut >= CURDATE()
    ORDER BY dispo.date_debut ASC LIMIT 1
");
$stmt2->execute([$user_id]);
$prochainVoyage = $stmt2->fetch() ?: null;

// ── Historique des voyages ───────────────────────────────
$stmtHisto = $pdo->prepare("
    SELECT r.*,
        COALESCE(h.nom, a.nom) AS nom_service,
        d.nom AS destination_nom, d.region,
        dispo.date_debut, dispo.date_fin
    FROM reservations r
    LEFT JOIN services s ON r.service_id = s.id
    LEFT JOIN hebergements h ON s.type = 'hebergement' AND s.ref_id = h.id
    LEFT JOIN activites a ON s.type = 'activite' AND s.ref_id = a.id
    LEFT JOIN destinations d ON COALESCE(h.destination_id, a.destination_id) = d.id
    LEFT JOIN disponibilites dispo ON r.disponibilite_id = dispo.id
    WHERE r.user_id = ? AND r.statut = 'terminee'
    ORDER BY dispo.date_debut DESC LIMIT 5
");
$stmtHisto->execute([$user_id]);
$historique = $stmtHisto->fetchAll();

// ── Favoris ──────────────────────────────────────────────
$stmtFav = $pdo->prepare("
    SELECT f.*, d.nom, d.budget, d.prix_base, d.image_url, d.categorie
    FROM favoris f
    JOIN destinations d ON f.destination_id = d.id
    WHERE f.user_id = ? LIMIT 6
");
$stmtFav->execute([$user_id]);
$favoris = $stmtFav->fetchAll();

echo json_encode([
    'success' => true,
    'user'    => [
        'id'                 => (int)$user['id'],
        'username'           => $user['username'],
        'email'              => $user['email'],
        'role'               => $user['role'],
        'date_inscription'   => $user['date_inscription'],
        'derniere_connexion' => $user['derniere_connexion'],
    ],
    'stats' => [
        'reservations' => $stat_reservations,
        'favoris'      => $stat_favoris,
        'activites'    => $stat_activites,
    ],
    'prochain_voyage' => $prochainVoyage,
    'historique'      => $historique,
    'favoris'         => $favoris,
], JSON_UNESCAPED_UNICODE);