<?php
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';


$user_id = (int)$_SESSION['user_id'];
$message = ""; $error = "";
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── AJOUT ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dispo'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $service_id  = (int)($_POST['service_id'] ?? 0);
        $date_debut  = $_POST['date_debut'] ?? '';
        $date_fin    = $_POST['date_fin'] ?? '';
        $places      = (int)($_POST['places_dispo'] ?? 0);
        $est_bloque  = isset($_POST['est_bloque']) ? 1 : 0;

        if ($service_id <= 0)      $error = "Sélectionnez un service.";
        elseif ($date_debut === '') $error = "La date de début est requise.";
        elseif ($date_fin === '')   $error = "La date de fin est requise.";
        elseif ($date_fin < $date_debut) $error = "La date de fin doit être après la date de début.";
        elseif ($places < 0)       $error = "Le nombre de places doit être positif.";
        else {
            // Vérifier que le service appartient au prestataire
            $chk = $pdo->prepare("SELECT id FROM services WHERE id=? AND prestataire_id=?");
            $chk->execute([$service_id, $user_id]);
            if (!$chk->fetch()) {
                $error = "Service introuvable ou non autorisé.";
            } else {
                $pdo->prepare("INSERT INTO disponibilites (service_id,date_debut,date_fin,places_dispo,est_bloque) VALUES (?,?,?,?,?)")
                    ->execute([$service_id,$date_debut,$date_fin,$places,$est_bloque]);
                $message = "Disponibilité ajoutée.";
            }
        }
    }
}

// ── SUPPRESSION ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dispo'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $deleteId = (int)$_POST['delete_dispo'];
        // Sécurité : vérifier que la dispo appartient au prestataire via le service
        $pdo->prepare("DELETE FROM disponibilites WHERE id=? AND service_id IN (SELECT id FROM services WHERE prestataire_id=?)")
            ->execute([$deleteId, $user_id]);
        $message = "Disponibilité supprimée.";
    }
}

// ── DONNÉES ───────────────────────────────────────────────
// Charger services existants + fallback sur hebergements/activites si services vide
$stmtSvc = $pdo->prepare("SELECT s.*, 
    CASE s.type 
        WHEN 'hebergement' THEN '🏨'
        WHEN 'activite' THEN '🏄'
        ELSE '📦' 
    END as type_icon
    FROM services s WHERE s.prestataire_id=? ORDER BY s.nom ASC");
$stmtSvc->execute([$user_id]);
$services = $stmtSvc->fetchAll();

$stmt = $pdo->prepare("SELECT d.*, s.nom AS service_nom FROM disponibilites d JOIN services s ON d.service_id=s.id WHERE s.prestataire_id=? ORDER BY d.date_debut ASC");
$stmt->execute([$user_id]);
$disponibilites = $stmt->fetchAll();

$totalDispos   = count($disponibilites);
$bloquees      = array_sum(array_column($disponibilites,'est_bloque'));
$totalPlaces   = array_sum(array_column($disponibilites,'places_dispo'));
$disponibles   = $totalDispos - $bloquees;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion Disponibilités - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin_style.css">
  <style>
    .dispo-layout{display:grid;grid-template-columns:380px 1fr;min-height:calc(100vh - 105px)}
    @media(max-width:900px){.dispo-layout{grid-template-columns:1fr}}

    /* Panneau gauche violet/indigo pour les disponibilités */
    .dispo-panel-left{
      position:relative;
      background:
        radial-gradient(circle at 20% 80%,rgba(243,178,125,.4),transparent 50%),
        radial-gradient(circle at 80% 20%,rgba(124,92,252,.45),transparent 45%),
        linear-gradient(135deg,#2d1b6e 0%,#5b3ed4 55%,#79a9df 100%);
      display:flex;align-items:center;justify-content:center;overflow:hidden;padding:60px 44px;
    }
    .dispo-panel-left::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;border:2px solid rgba(255,255,255,.12);top:-60px;right:-60px;animation:floatCircle 6s ease-in-out infinite}
    .dispo-panel-left::after{content:'';position:absolute;width:180px;height:180px;border-radius:50%;border:2px solid rgba(255,255,255,.10);bottom:-50px;left:-50px;animation:floatCircle 8s ease-in-out infinite reverse}

    .dispo-panel-right{background:var(--bg);overflow-y:auto;padding:50px 50px 80px}
    @media(max-width:900px){.dispo-panel-right{padding:30px 20px 60px}}

    /* Formulaire checkbox stylé */
    .checkbox-group{display:flex;align-items:center;gap:12px;padding:14px 18px;background:#fff;border:2px solid var(--border);border-radius:18px;cursor:pointer;transition:.3s}
    .checkbox-group:hover{border-color:#5b3ed4}
    .checkbox-group input[type=checkbox]{display:none}
    .checkbox-custom{width:22px;height:22px;border:2px solid rgba(121,169,223,.4);border-radius:7px;background:#fff;display:flex;align-items:center;justify-content:center;transition:.3s;flex-shrink:0}
    .checkbox-group input:checked + .checkbox-custom{background:linear-gradient(135deg,#5b3ed4,#79a9df);border-color:transparent}
    .checkbox-group input:checked + .checkbox-custom::after{content:"✓";color:#fff;font-size:13px;font-weight:900}
    .checkbox-label-text{font-size:14px;font-weight:700;color:#31517c}

    /* Bouton dispo */
    .btn-auth{margin-top:8px;width:100%;padding:16px;border:none;border-radius:30px;background:linear-gradient(135deg,#5b3ed4,#79a9df);color:#fff;font-size:16px;font-weight:800;cursor:pointer;transition:.3s;box-shadow:0 14px 30px rgba(91,62,212,.28);display:flex;align-items:center;justify-content:center;gap:10px;font-family:'DM Sans',sans-serif;position:relative;overflow:hidden}
    .btn-auth::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 70% 30%,rgba(255,255,255,.2),transparent 60%);pointer-events:none}
    .btn-auth:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 22px 40px rgba(91,62,212,.38)}

    /* Tableau disponibilités */
    .dispo-table{width:100%;border-collapse:collapse;background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 10px 28px var(--shadow)}
    .dispo-table thead{background:#5b3ed4}
    .dispo-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:13px 14px;text-align:left;color:#fff}
    .dispo-table td{padding:11px 14px;font-size:13px;border-bottom:1px solid #edf4fb;vertical-align:middle;color:var(--text)}
    .dispo-table tr:last-child td{border-bottom:none}
    .dispo-table tr:hover td{background:#f5f3ff}

    .pill-dispo{display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700}
    .pill-available{background:#f0fdf4;color:#16a34a;border:1px solid rgba(74,222,128,.2)}
    .pill-blocked{background:#fff0f2;color:#e64b5d;border:1px solid rgba(230,75,93,.2)}

    .btn-del-dispo{color:#e64b5d;font-size:11px;font-weight:700;padding:5px 10px;border-radius:10px;border:1.5px solid #ffc5cb;background:#fff;cursor:pointer;transition:.2s}
    .btn-del-dispo:hover{background:#fff0f2}

    .divider-table{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
    .divider-table h3{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:#5b3ed4}
    .count-badge-pu{background:#f0edff;color:#5b3ed4;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700}

    /* Calendrier mini — résumé par service */
    .service-cal{background:#fff;border-radius:16px;padding:14px 16px;box-shadow:0 4px 14px var(--shadow);margin-bottom:10px;display:flex;align-items:center;gap:12px}
    .service-cal-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#5b3ed4,#79a9df);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
    .service-cal-name{font-size:13px;font-weight:700;color:#5b3ed4}
    .service-cal-info{font-size:11px;color:var(--muted2)}

    .auth-divider{display:flex;align-items:center;gap:14px;margin:28px 0;color:var(--muted2);font-size:14px}
    .auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:rgba(121,169,223,.22)}

    @keyframes floatCircle{0%,100%{transform:translate(0,0)}50%{transform:translate(15px,-15px)}}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
  </style>
</head>
<body>

<header class="navbar">
  <div class="brand"><img src="../frontend/assets/images/logo-voyagevista.png" alt="VoyageVista"></div>
  <nav>
    <a href="../frontend/index.html">Accueil</a>
    <a href="gestion-hebergements.php">Hébergements</a>
    <a href="gestion-activites.php">Activités</a>
    <a href="gestion-disponibilites.php" class="active">Disponibilités</a>
    <a href="dashboard_prestataire.php">Dashboard</a>
  </nav>
  <div class="nav-icons">
    <span class="heart-icon">♥</span>
    <span>🔔</span>
    <a href="logout.php">👤</a>
  </div>
</header>

<div class="dispo-layout">

  <!-- ── PANNEAU GAUCHE ── -->
  <div class="dispo-panel-left">
    <div class="auth-panel-overlay"></div>
    <div class="auth-panel-content" style="max-width:300px">
      <span class="auth-tag">CALENDRIER • CRÉNEAUX</span>
      <h2 style="font-size:38px">Gérez vos<br>Disponibilités 📅</h2>
      <p class="auth-panel-sub" style="font-size:15px">Définissez les créneaux disponibles, les dates bloquées et les capacités d'accueil.</p>
      <div class="auth-bubbles">
        <div class="bubble">📅 Disponible</div>
        <div class="bubble">🚫 Bloqué</div>
        <div class="bubble">👥 Places</div>
        <div class="bubble">🏨 Services</div>
      </div>
      <div class="panel-stats">
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $totalDispos;?></span><span class="panel-stat-lbl">CRÉNEAUX</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $disponibles;?></span><span class="panel-stat-lbl">DISPONIBLES</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $bloquees;?></span><span class="panel-stat-lbl">BLOQUÉES</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $totalPlaces;?></span><span class="panel-stat-lbl">PLACES TOT.</span></div>
      </div>
    </div>
  </div>

  <!-- ── PANNEAU DROIT ── -->
  <div class="dispo-panel-right">

    <?php if($message):?><div class="alert-success">✅ <?php echo htmlspecialchars($message);?></div><?php endif;?>
    <?php if($error):?><div class="alert-error">⚠️ <?php echo htmlspecialchars($error);?></div><?php endif;?>

    <p style="font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:#5b3ed4;margin-bottom:5px">📅 Nouvelle disponibilité</p>
    <p style="font-size:14px;color:var(--muted);margin-bottom:26px">Définissez un créneau pour l'un de vos services.</p>

    <!-- MES SERVICES (aperçu rapide) -->
    <?php if(!empty($services)):?>
    <div style="margin-bottom:20px">
      <p style="font-size:12px;font-weight:700;color:var(--muted2);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Vos services</p>
      <?php foreach(array_slice($services,0,3) as $s):
        $nb=$pdo->prepare("SELECT COUNT(*) FROM disponibilites WHERE service_id=?"); $nb->execute([$s['id']]); $nbD=$nb->fetchColumn();
        $icon = $s['type']==='activite' ? '🏄' : ($s['type']==='transport' ? '🚌' : '🏨');
      ?>
      <div class="service-cal">
        <div class="service-cal-icon"><?php echo $icon;?></div>
        <div>
          <div class="service-cal-name"><?php echo htmlspecialchars($s['nom']);?></div>
          <div class="service-cal-info"><?php echo $nbD;?> créneau<?php echo $nbD>1?'x':'';?> défini<?php echo $nbD>1?'s':'';?></div>
        </div>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>

    <!-- FORMULAIRE -->
    <form class="auth-form" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">

      <div class="form-group">
        <label>Service *</label>
        <?php if(empty($services)):?>
          <div style="padding:12px 16px;background:#fff8e1;border:1.5px solid #fde68a;border-radius:14px;font-size:13px;color:#92400e">
            ⚠️ Aucun service trouvé. Commencez par ajouter un 
            <a href="gestion-hebergements.php" style="color:#5b3ed4;font-weight:700">hébergement</a> ou une 
            <a href="gestion-activites.php" style="color:#5b3ed4;font-weight:700">activité</a> pour créer des disponibilités.
          </div>
        <?php else:?>
        <div class="input-wrap">
          <span class="input-icon">🏨</span>
          <select name="service_id" required>
            <option value="">— Sélectionner un service —</option>
            <?php foreach($services as $s):?>
            <option value="<?php echo (int)$s['id'];?>">
              <?php echo htmlspecialchars(($s['type_icon']??'📦').' '.$s['nom'].' ('.$s['type'].')');?>
            </option>
            <?php endforeach;?>
          </select>
        </div>
        <?php endif;?>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Date de début *</label>
          <div class="input-wrap">
            <span class="input-icon">📅</span>
            <input type="date" name="date_debut" required min="<?php echo date('Y-m-d');?>">
          </div>
        </div>
        <div class="form-group">
          <label>Date de fin *</label>
          <div class="input-wrap">
            <span class="input-icon">📅</span>
            <input type="date" name="date_fin" required min="<?php echo date('Y-m-d');?>">
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Nombre de places disponibles</label>
        <div class="input-wrap">
          <span class="input-icon">👥</span>
          <input type="number" name="places_dispo" min="0" placeholder="ex: 10">
        </div>
      </div>

      <div class="form-group">
        <label>
          <div class="checkbox-group">
            <input type="checkbox" name="est_bloque" id="bloquer">
            <span class="checkbox-custom"></span>
            <span class="checkbox-label-text">🚫 Bloquer cette période (indisponible)</span>
          </div>
        </label>
      </div>

      <button type="submit" name="add_dispo" class="btn-auth">✈ Enregistrer la disponibilité</button>
    </form>

    <div class="auth-divider"><span>calendrier des créneaux</span></div>

    <!-- TABLEAU -->
    <div class="divider-table">
      <h3>📅 Créneaux définis</h3>
      <span class="count-badge-pu"><?php echo $totalDispos;?> créneau<?php echo $totalDispos>1?'x':'';?></span>
    </div>

    <?php if(empty($disponibilites)):?>
      <div class="empty-state">Aucun créneau défini. Utilisez le formulaire ci-dessus.</div>
    <?php else:?>
    <table class="dispo-table">
      <thead>
        <tr><th>Service</th><th>Début</th><th>Fin</th><th>Places</th><th>Statut</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach($disponibilites as $d):?>
        <tr>
          <td style="font-weight:700;color:#5b3ed4"><?php echo htmlspecialchars($d['service_nom']);?></td>
          <td><?php echo htmlspecialchars($d['date_debut']);?></td>
          <td><?php echo htmlspecialchars($d['date_fin']);?></td>
          <td style="font-weight:700;text-align:center"><?php echo (int)$d['places_dispo'];?></td>
          <td>
            <span class="pill-dispo <?php echo $d['est_bloque']?'pill-blocked':'pill-available';?>">
              <?php echo $d['est_bloque']?'🚫 Bloqué':'✅ Disponible';?>
            </span>
          </td>
          <td>
            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce créneau ?')">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
              <input type="hidden" name="delete_dispo" value="<?php echo (int)$d['id'];?>">
              <button type="submit" class="btn-del-dispo">🗑️ Supprimer</button>
            </form>
          </td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <?php endif;?>

  </div>
</div>

<footer>© 2026 VoyageVista — Explore, swipe, travel together.</footer>
<script src="js/navbar_session.js"></script>
</body>
</html>