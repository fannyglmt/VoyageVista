<?php
// =============================================
// API_HISTORIQUE.PHP — VoyageVista
// Historique des réservations utilisateur
// =============================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(0, '/', '', false, true);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET');

require_once 'configuration.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'non_connecte']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$filtre  = $_GET['statut'] ?? 'all';

$query = "
    SELECT
        r.id, r.date_debut, r.date_fin, r.nb_voyageurs,
        r.prix_total, r.statut, r.date_reservation,
        d.id AS destination_id, d.nom AS destination_nom,
        d.pays, d.region, d.image_url AS dest_image,
        COALESCE(h.nom, a.nom) AS service_nom,
        COALESCE(h.type, 'activite') AS service_type,
        s.type AS service_categorie,
        -- Vérifier si un avis existe déjà
        (SELECT COUNT(*) FROM avis
         WHERE user_id = r.user_id
         AND destination_id = r.destination_id) AS a_deja_avis
    FROM reservations r
    JOIN destinations d ON r.destination_id = d.id
    LEFT JOIN services s ON r.service_id = s.id
    LEFT JOIN hebergements h ON s.type = 'hebergement' AND s.ref_id = h.id
    LEFT JOIN activites a    ON s.type = 'activite'    AND s.ref_id = a.id
    WHERE r.user_id = ?
";

$params = [$user_id];
if ($filtre !== 'all') {
    $query   .= " AND r.statut = ?";
    $params[] = $filtre;
}
$query .= " ORDER BY r.date_debut DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

// ── Stats globales ────────────────────────────────────────
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN statut = 'terminee' THEN nb_voyageurs ELSE 0 END) AS nuits_total,
        COUNT(DISTINCT destination_id) AS pays_visites,
        SUM(CASE WHEN statut = 'terminee' THEN DATEDIFF(date_fin, date_debut) ELSE 0 END) AS total_nuits
    FROM reservations
    WHERE user_id = ?
");
$stmtStats->execute([$user_id]);
$stats = $stmtStats->fetch();

echo json_encode([
    'success'      => true,
    'count'        => count($reservations),
    'stats'        => [
        'total'        => (int)$stats['total'],
        'pays_visites' => (int)$stats['pays_visites'],
        'total_nuits'  => (int)$stats['total_nuits'],
    ],
    'data'         => $reservations,
], JSON_UNESCAPED_UNICODE);