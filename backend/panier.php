<?php
// =========================================
// BACKEND/PANIER.PHP — VOYAGEVISTA
// Gestion du panier en session
// =========================================

session_start();
require_once 'configuration.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

// Initialise le panier en session si vide
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [
        'destination_id' => null,
        'nb_voyageurs'   => 1,
        'transport'      => null,
        'hebergement'    => null,
        'activites'      => [],
        'total'          => 0,
    ];
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

header('Content-Type: application/json');

switch ($action) {

    // -----------------------------------------------
    // Récupérer le panier
    // -----------------------------------------------
    case 'get':
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // -----------------------------------------------
    // Définir le nombre de voyageurs
    // -----------------------------------------------
    case 'set_voyageurs':
        $nb = (int)($_POST['nb'] ?? 1);
        if ($nb < 1) $nb = 1;
        $_SESSION['panier']['nb_voyageurs'] = $nb;
        recalculerTotal();
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // -----------------------------------------------
    // Ajouter / remplacer le transport
    // -----------------------------------------------
    case 'set_transport':
        $service_id = (int)($_POST['service_id'] ?? 0);
        if ($service_id) {
            $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ? AND type = "transport" LIMIT 1');
            $stmt->execute([$service_id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($service) {
                $_SESSION['panier']['transport'] = $service;
                recalculerTotal();
                echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
            } else {
                echo json_encode(['error' => 'Transport introuvable']);
            }
        }
        break;

    // -----------------------------------------------
    // Ajouter / remplacer l'hébergement
    // -----------------------------------------------
    case 'set_hebergement':
        $hebergement_id = (int)($_POST['hebergement_id'] ?? 0);
        if ($hebergement_id) {
            $stmt = $pdo->prepare('SELECT * FROM hebergements WHERE id = ? LIMIT 1');
            $stmt->execute([$hebergement_id]);
            $heberg = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($heberg) {
                $_SESSION['panier']['hebergement'] = $heberg;
                recalculerTotal();
                echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
            } else {
                echo json_encode(['error' => 'Hébergement introuvable']);
            }
        }
        break;

    // -----------------------------------------------
    // Ajouter une activité
    // -----------------------------------------------
    case 'add_activite':
        $activite_id = (int)($_POST['activite_id'] ?? 0);
        if ($activite_id) {
            // Vérifie qu'elle n'est pas déjà dans le panier
            $deja = array_filter($_SESSION['panier']['activites'], fn($a) => $a['id'] === $activite_id);
            if (!empty($deja)) {
                echo json_encode(['error' => 'Activité déjà dans le panier']);
                break;
            }

            $stmt = $pdo->prepare('SELECT * FROM activites WHERE id = ? AND est_actif = 1 LIMIT 1');
            $stmt->execute([$activite_id]);
            $activite = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($activite) {
                $_SESSION['panier']['activites'][] = $activite;
                recalculerTotal();
                echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
            } else {
                echo json_encode(['error' => 'Activité introuvable']);
            }
        }
        break;

    // -----------------------------------------------
    // Retirer une activité
    // -----------------------------------------------
    case 'remove_activite':
        $activite_id = (int)($_POST['activite_id'] ?? 0);
        $_SESSION['panier']['activites'] = array_values(
            array_filter($_SESSION['panier']['activites'], fn($a) => $a['id'] !== $activite_id)
        );
        recalculerTotal();
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // -----------------------------------------------
    // Supprimer transport ou hébergement
    // -----------------------------------------------
    case 'remove_transport':
        $_SESSION['panier']['transport'] = null;
        recalculerTotal();
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    case 'remove_hebergement':
        $_SESSION['panier']['hebergement'] = null;
        recalculerTotal();
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // -----------------------------------------------
    // Vider le panier
    // -----------------------------------------------
    case 'vider':
        $_SESSION['panier'] = [
            'destination_id' => null,
            'nb_voyageurs'   => 1,
            'transport'      => null,
            'hebergement'    => null,
            'activites'      => [],
            'total'          => 0,
        ];
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    default:
        echo json_encode(['error' => 'Action inconnue']);
}

// -----------------------------------------------
// Recalcul du total
// -----------------------------------------------
function recalculerTotal() {
    $p   = &$_SESSION['panier'];
    $nb  = $p['nb_voyageurs'];
    $tot = 0;

    if ($p['transport'])   $tot += (float)$p['transport']['prix']   * $nb;
    if ($p['hebergement']) $tot += (float)$p['hebergement']['prix_nuit']; // prix total nuits
    foreach ($p['activites'] as $a) {
        $tot += (float)$a['prix'] * $nb;
    }

    $p['total'] = round($tot, 2);
}