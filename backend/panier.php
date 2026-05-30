<?php
// =========================================
// BACKEND/PANIER.PHP — VOYAGEVISTA
// =========================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(0, '/', '', false, true);
session_start();

require_once 'configuration.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Credentials: true');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'non_connecte', 'redirect' => '../frontend/login.html']);
    exit;
}

// Initialise le panier en session si vide
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [
        'destination_id'  => null,
        'destination_nom' => null,
        'destination_img' => null,
        'date_debut'      => null,
        'date_fin'        => null,
        'nb_voyageurs'    => 1,
        'transport'       => null,
        'hebergement'     => null,
        'activites'       => [],
        'total'           => 0,
    ];
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {

    case 'get':
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // ── Définir la destination ────────────────────────────
    case 'set_destination':
        $dest_id = (int)($_POST['destination_id'] ?? 0);
        if (!$dest_id) { echo json_encode(['success'=>false,'error'=>'ID invalide']); break; }

        $stmt = $pdo->prepare("SELECT id, nom, image_url FROM destinations WHERE id=? AND est_active=1 LIMIT 1");
        $stmt->execute([$dest_id]);
        $dest = $stmt->fetch();

        if (!$dest) { echo json_encode(['success'=>false,'error'=>'Destination introuvable']); break; }

        $_SESSION['panier']['destination_id']  = $dest['id'];
        $_SESSION['panier']['destination_nom'] = $dest['nom'];
        $_SESSION['panier']['destination_img'] = $dest['image_url'];
        recalculerTotal();
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // ── Dates ─────────────────────────────────────────────
    case 'set_dates':
        $debut = $_POST['date_debut'] ?? null;
        $fin   = $_POST['date_fin']   ?? null;
        if ($debut && $fin && $fin > $debut) {
            $_SESSION['panier']['date_debut'] = $debut;
            $_SESSION['panier']['date_fin']   = $fin;
        }
        recalculerTotal();
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // ── Nombre de voyageurs ───────────────────────────────
    case 'set_voyageurs':
        $nb = max(1, (int)($_POST['nb'] ?? 1));
        $_SESSION['panier']['nb_voyageurs'] = $nb;
        recalculerTotal();
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // ── Transport ─────────────────────────────────────────
    case 'set_transport':
        $service_id = (int)($_POST['service_id'] ?? 0);
        if ($service_id) {
            $stmt = $pdo->prepare('SELECT * FROM services WHERE id=? AND type="transport" LIMIT 1');
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();
            if ($service) {
                $_SESSION['panier']['transport'] = $service;
                recalculerTotal();
                echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
            } else {
                echo json_encode(['success'=>false,'error'=>'Transport introuvable']);
            }
        }
        break;

    case 'remove_transport':
        $_SESSION['panier']['transport'] = null;
        recalculerTotal();
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // ── Hébergement ───────────────────────────────────────
    case 'set_hebergement':
        $heb_id = (int)($_POST['hebergement_id'] ?? 0);
        if ($heb_id) {
            $stmt = $pdo->prepare('SELECT h.*, d.nom AS dest FROM hebergements h JOIN destinations d ON h.destination_id=d.id WHERE h.id=? AND h.est_actif=1 LIMIT 1');
            $stmt->execute([$heb_id]);
            $heb = $stmt->fetch();
            if ($heb) {
                $_SESSION['panier']['hebergement'] = $heb;
                // Définir la destination si pas encore définie
                if (!$_SESSION['panier']['destination_id']) {
                    $_SESSION['panier']['destination_id']  = $heb['destination_id'];
                    $_SESSION['panier']['destination_nom'] = $heb['dest'];
                }
                recalculerTotal();
                echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
            } else {
                echo json_encode(['success'=>false,'error'=>'Hébergement introuvable']);
            }
        }
        break;

    case 'remove_hebergement':
        $_SESSION['panier']['hebergement'] = null;
        recalculerTotal();
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // ── Activités ─────────────────────────────────────────
    case 'add_activite':
        $act_id = (int)($_POST['activite_id'] ?? 0);
        if ($act_id) {
            // Déjà dans le panier ?
            $deja = array_filter($_SESSION['panier']['activites'], fn($a) => (int)$a['id'] === $act_id);
            if (!empty($deja)) {
                echo json_encode(['success'=>false,'error'=>'Activité déjà dans le panier']);
                break;
            }
            $stmt = $pdo->prepare('SELECT a.*, d.nom AS dest FROM activites a JOIN destinations d ON a.destination_id=d.id WHERE a.id=? AND a.est_actif=1 LIMIT 1');
            $stmt->execute([$act_id]);
            $act = $stmt->fetch();
            if ($act) {
                $_SESSION['panier']['activites'][] = $act;
                recalculerTotal();
                echo json_encode(['success'=>true,'panier'=>$_SESSION['panier'],'message'=>'Activité ajoutée ✅']);
            } else {
                echo json_encode(['success'=>false,'error'=>'Activité introuvable']);
            }
        }
        break;

    case 'remove_activite':
        $act_id = (int)($_POST['activite_id'] ?? 0);
        $_SESSION['panier']['activites'] = array_values(
            array_filter($_SESSION['panier']['activites'], fn($a) => (int)$a['id'] !== $act_id)
        );
        recalculerTotal();
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    // ── Vider ─────────────────────────────────────────────
    case 'vider':
        $_SESSION['panier'] = [
            'destination_id'=>null,'destination_nom'=>null,'destination_img'=>null,
            'date_debut'=>null,'date_fin'=>null,'nb_voyageurs'=>1,
            'transport'=>null,'hebergement'=>null,'activites'=>[],'total'=>0,
        ];
        echo json_encode(['success' => true, 'panier' => $_SESSION['panier']]);
        break;

    default:
        echo json_encode(['success'=>false,'error'=>'Action inconnue']);
}

function recalculerTotal() {
    $p   = &$_SESSION['panier'];
    $nb  = (int)$p['nb_voyageurs'];
    $tot = 0;

    if ($p['transport'])   $tot += (float)$p['transport']['prix'] * $nb;

    if ($p['hebergement']) {
        if ($p['date_debut'] && $p['date_fin']) {
            $nuits = (strtotime($p['date_fin']) - strtotime($p['date_debut'])) / 86400;
            $tot  += (float)$p['hebergement']['prix_nuit'] * max(1, $nuits);
        } else {
            $tot += (float)$p['hebergement']['prix_nuit'];
        }
    }

    foreach ($p['activites'] as $a) {
        $tot += (float)$a['prix'] * $nb;
    }

    $p['total'] = round($tot, 2);
}