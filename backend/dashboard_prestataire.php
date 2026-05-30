<?php
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';


$pid = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM services WHERE prestataire_id=? ORDER BY nom ASC");
$stmt->execute([$pid]); $services = $stmt->fetchAll();

$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN services s ON r.service_id=s.id WHERE s.prestataire_id=?");
$stmt2->execute([$pid]); $total_reservations = $stmt2->fetchColumn();

$stmt3 = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND lu=0");
$stmt3->execute([$pid]); $notifs_non_lues = $stmt3->fetchColumn();

$stmt4 = $pdo->prepare("SELECT COALESCE(SUM(r.prix_total),0) FROM reservations r JOIN services s ON r.service_id=s.id WHERE s.prestataire_id=? AND r.statut!='annulee'");
$stmt4->execute([$pid]); $ca_total = $stmt4->fetchColumn();

$stmt5 = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY date_envoi DESC LIMIT 5");
$stmt5->execute([$pid]); $notifications = $stmt5->fetchAll();

foreach ($services as &$s) {
    $sc = $pdo->prepare("SELECT COALESCE(SUM(r.prix_total),0) FROM reservations r WHERE r.service_id=? AND r.statut!='annulee'");
    $sc->execute([$s['id']]); $s['ca_service'] = (float)$sc->fetchColumn();
} unset($s);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Prestataire - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin_style.css">
  <style>
    /* Panneau gauche teal pour le prestataire */
    .prest-panel-left{
      background:
        radial-gradient(circle at 20% 80%,rgba(243,178,125,.45),transparent 50%),
        radial-gradient(circle at 80% 20%,rgba(45,212,191,.4),transparent 45%),
        linear-gradient(135deg,#4a68a6 0%,#2dd4bf 60%,#79a9df 100%);
    }
    /* KPI couleurs prestataire */
    .kpi-card.teal .kpi-val{color:#0d9488}
    .kpi-card.amber .kpi-val{color:#d97706}
    .kpi-card.green .kpi-val{color:#16a34a}
    .kpi-card.blue .kpi-val{color:#4a68a6}

    /* Services grid style trend-card du frontend */
    .services-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;margin-bottom:30px}
    .svc-card{background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 10px 28px var(--shadow);transition:.35s}
    .svc-card:hover{transform:translateY(-8px);box-shadow:0 22px 40px rgba(69,139,202,.18)}
    .svc-img{width:100%;height:130px;object-fit:cover;display:block}
    .svc-body{padding:16px 18px}
    .svc-name{font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:var(--blue);margin-bottom:4px}
    .svc-type{font-size:11px;color:var(--muted2);margin-bottom:10px}
    .svc-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
    .svc-price{font-family:'Syne',sans-serif;font-weight:800;color:#16a34a;font-size:15px}

    /* Barre de revenus */
    .rev-block{padding:20px 24px}
    .rev-total{font-family:'Syne',sans-serif;font-size:32px;font-weight:800;color:#16a34a;margin-bottom:4px}
    .rev-sub{font-size:13px;color:var(--muted);margin-bottom:20px}
    .rev-bar-wrap{margin-bottom:12px}
    .rev-bar-label{display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;color:var(--text)}
    .rev-bar-label span:last-child{color:var(--muted2)}
    .rev-bar-track{height:7px;background:#edf4fb;border-radius:4px;overflow:hidden}
    .rev-bar-fill{height:100%;border-radius:4px}

    /* Notifications */
    .notif-item{display:flex;align-items:flex-start;gap:10px;padding:12px 18px;border-bottom:1px solid #edf4fb}
    .notif-item:last-child{border-bottom:none}
    .ndot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:4px}
    .ndot.unread{background:#f39b5f;box-shadow:0 0 6px rgba(243,155,95,.5)}
    .ndot.read{background:#a8c0d6}
    .notif-msg{font-size:13px;color:var(--text)}
    .notif-date{font-size:10px;color:var(--muted2);margin-top:2px}

    /* Section cards */
    .section-card{background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 10px 28px var(--shadow)}
    .section-card-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #edf4fb;background:#f7fbff}
    .section-card-head h3{font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--blue)}
    .section-card-head a{font-size:12px;color:#79a9df;text-decoration:none;font-weight:600}

    .two-col-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    @media(max-width:800px){.two-col-grid{grid-template-columns:1fr}}

    /* CTA section style frontend */
    .page-body{padding:40px 6% 60px;background:var(--bg)}
    .section-title{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--blue);margin-bottom:16px}
  </style>
</head>
<body>

<header class="navbar">
  <div class="brand"><img src="../frontend/assets/images/logo-voyagevista.png" alt="Logo VoyageVista"></div>
  <nav>
    <a href="../frontend/index.html">Accueil</a>
    <a href="../frontend/hebergements.html">Hébergements</a>
    <a href="gestion-hebergements.php">Mes hébergements</a>
    <a href="dashboard_prestataire.php" class="active">Dashboard</a>
  </nav>
  <div class="nav-icons">
    <span class="heart-icon">♥</span>
    <?php if($notifs_non_lues>0):?><span style="position:relative">🔔<span style="position:absolute;top:-4px;right:-4px;width:8px;height:8px;background:#f39b5f;border-radius:50%;border:2px solid #fff"></span></span><?php else:?><span>🔔</span><?php endif;?>
    <a href="logout.php">👤</a>
  </div>
</header>

<!-- HERO style panneau gauche login.html -->
<div style="display:grid;grid-template-columns:380px 1fr">

  <div class="auth-panel-left prest-panel-left" style="padding:60px 44px;min-height:280px">
    <div class="auth-panel-overlay"></div>
    <div class="auth-panel-content" style="max-width:300px">
      <span class="auth-tag">HOST • MANAGE • EARN</span>
      <h2 style="font-size:38px">Bienvenue,<br><?php echo htmlspecialchars($_SESSION['username']??'Prestataire');?> 👋</h2>
      <p class="auth-panel-sub" style="font-size:15px">Gérez vos services et suivez vos performances en temps réel.</p>
      <div class="auth-bubbles">
        <div class="bubble">🏨 Hébergements</div>
        <div class="bubble">🏄 Activités</div>
        <div class="bubble">📅 Disponibilités</div>
        <div class="bubble">💰 Revenus</div>
      </div>
      <div class="panel-stats">
        <div class="panel-stat"><span class="panel-stat-val"><?php echo count($services);?></span><span class="panel-stat-lbl">SERVICES</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo (int)$total_reservations;?></span><span class="panel-stat-lbl">RÉSERV.</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo (int)$notifs_non_lues;?></span><span class="panel-stat-lbl">NOTIFS</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo number_format((float)$ca_total,0,',',' ');?>€</span><span class="panel-stat-lbl">REVENUS</span></div>
      </div>
    </div>
  </div>

  <!-- KPI à droite du panneau -->
  <div style="padding:40px;background:var(--bg);display:flex;flex-direction:column;justify-content:center">
    <p style="color:var(--orange);font-weight:800;letter-spacing:3px;font-size:12px;margin-bottom:12px">VOS STATISTIQUES</p>
    <div class="kpi-grid" style="margin-bottom:0">
      <div class="kpi-card teal">
        <span class="kpi-icon">🏨</span>
        <div class="kpi-val" style="color:#0d9488"><?php echo count($services);?></div>
        <div class="kpi-lbl">Services publiés</div>
      </div>
      <div class="kpi-card blue">
        <span class="kpi-icon">📅</span>
        <div class="kpi-val"><?php echo (int)$total_reservations;?></div>
        <div class="kpi-lbl">Réservations reçues</div>
      </div>
      <div class="kpi-card amber">
        <span class="kpi-icon">🔔</span>
        <div class="kpi-val" style="color:#d97706"><?php echo (int)$notifs_non_lues;?></div>
        <div class="kpi-lbl">Notifications non lues</div>
      </div>
      <div class="kpi-card green">
        <span class="kpi-icon">💰</span>
        <div class="kpi-val" style="color:#16a34a"><?php echo number_format((float)$ca_total,0,',',' ');?>€</div>
        <div class="kpi-lbl">Revenus générés</div>
      </div>
    </div>
  </div>

</div>

<div class="page-body">

  <!-- MES SERVICES style trend-cards -->
  <p class="section-title">🏨 Mes services</p>
  <?php if(empty($services)):?>
    <div class="empty-state">Aucun service publié. <a href="gestion-hebergements.php" style="color:#79a9df;font-weight:700">Ajouter un hébergement →</a></div>
  <?php else:?>
  <div class="services-grid">
    <?php
    $svcImg = [
    1  => '../frontend/assets/images/villachill.jpg',
    2  => '../frontend/assets/images/hotel1.jpg',
    3  => '../frontend/assets/images/barcelonevilla.jpg',
    4  => '../frontend/assets/images/loftchamonix.jpg',
    5  => '../frontend/assets/images/villamarrakech.jpg',
    6  => '../frontend/assets/images/villacosta.jpg',

    7  => '../frontend/assets/images/boat.png',
    8  => '../frontend/assets/images/croisiere-sunset.png',
    9  => '../frontend/assets/images/food-tour.png',
    10 => '../frontend/assets/images/diner-marocain.png',
    11 => '../frontend/assets/images/randonnee-volcan.png',
    12 => '../frontend/assets/images/plongee-sous-marine.jpg',
    13 => '../frontend/assets/images/spa.png',
    14 => '../frontend/assets/images/tyrolienne-jungle.png',

    15 => '../frontend/assets/images/transport-avion.jpg',
    16 => '../frontend/assets/images/transport-train.jpg',
    17 => '../frontend/assets/images/transport-van.jpg',
    18 => '../frontend/assets/images/transport-avion.jpg',
    19 => '../frontend/assets/images/transport-ferry.jpg',
    20 => '../frontend/assets/images/transport-velo.jpg'
];

    $sc_map=['actif'=>'pill-green','inactif'=>'pill-red','en_attente'=>'pill-amber'];
    $tc_map=['hebergement'=>'pill-teal','activite'=>'pill-purple','transport'=>'pill-amber'];
    foreach($services as $s):
      $img = $svcImg[$s['id']]
    ?? '../frontend/assets/images/default.jpg';
      $sc2=$sc_map[$s['statut']??'en_attente']??'pill-amber';
      $tc2=$tc_map[$s['type']??'hebergement']??'pill-teal';
    ?>
    <div class="svc-card">
      <img class="svc-img" src="<?php echo $img;?>" alt="<?php echo htmlspecialchars($s['nom']);?>">
      <div class="svc-body">
        <div class="svc-name"><?php echo htmlspecialchars($s['nom']);?></div>
        <div class="svc-type"><?php echo htmlspecialchars($s['type']??'—');?></div>
        <div class="svc-row">
          <span class="svc-price"><?php echo number_format((float)($s['prix']??0),0,',',' ');?>€</span>
          <span class="pill <?php echo $sc2;?>"><?php echo htmlspecialchars($s['statut']??'en_attente');?></span>
        </div>
        <a href="gestion-hebergements.php?edit=<?php echo (int)$s['id'];?>" style="display:block;text-align:center;padding:8px;border-radius:16px;background:#f7fbff;color:#4a68a6;font-size:12px;font-weight:700;text-decoration:none;border:1.5px solid #c5defa;transition:.2s">✏️ Modifier</a>
      </div>
    </div>
    <?php endforeach;?>
  </div>
  <?php endif;?>

  <div class="two-col-grid">

    <!-- NOTIFICATIONS -->
    <div class="section-card">
      <div class="section-card-head">
        <h3>🔔 Notifications</h3>
        <?php if($notifs_non_lues>0):?><span class="pill pill-orange"><?php echo $notifs_non_lues;?> nouvelles</span><?php endif;?>
      </div>
      <?php if(empty($notifications)):?>
        <p class="empty-state" style="padding:2rem">Aucune notification.</p>
      <?php else: foreach($notifications as $n):?>
      <div class="notif-item">
        <div class="ndot <?php echo $n['lu']?'read':'unread';?>"></div>
        <div>
          <div class="notif-msg"><?php echo htmlspecialchars($n['message']);?></div>
          <div class="notif-date"><?php echo substr($n['date_envoi'],0,10);?></div>
        </div>
      </div>
      <?php endforeach; endif;?>
    </div>

    <!-- REVENUS -->
    <div class="section-card">
      <div class="section-card-head"><h3>💰 Revenus par service</h3></div>
      <div class="rev-block">
        <div class="rev-total"><?php echo number_format((float)$ca_total,0,',',' ');?>€</div>
        <div class="rev-sub">Total des réservations confirmées</div>
        <?php
        $colors=['#2dd4bf','#79a9df','#f3b27d','#4ade80','#f25ca2'];
        foreach($services as $i=>$s):
          $pct=$ca_total>0?min(100,round($s['ca_service']/$ca_total*100)):0;
          $col=$colors[$i%count($colors)];
        ?>
        <div class="rev-bar-wrap">
          <div class="rev-bar-label">
            <span><?php echo htmlspecialchars($s['nom']);?></span>
            <span><?php echo number_format($s['ca_service'],0,',',' ');?>€</span>
          </div>
          <div class="rev-bar-track">
            <div class="rev-bar-fill" style="width:<?php echo $pct;?>%;background:<?php echo $col;?>"></div>
          </div>
        </div>
        <?php endforeach;?>
      </div>
    </div>

  </div>

  <!-- NAVIGATION RAPIDE style action-items -->
  <p class="section-title">⚡ Navigation rapide</p>
  <div class="section-card">
    <a href="gestion-hebergements.php" class="action-item">
      <div class="action-num" style="background:rgba(45,212,191,.1)">🏨</div>
      <div><div class="action-label">Gérer mes hébergements</div><div class="action-sub">Ajouter, modifier, supprimer</div></div>
      <span class="action-arrow">›</span>
    </a>
    <a href="gestion-activites.php" class="action-item">
      <div class="action-num" style="background:rgba(124,92,252,.1)">🏄</div>
      <div><div class="action-label">Gérer mes activités</div><div class="action-sub">Excursions & expériences</div></div>
      <span class="action-arrow">›</span>
    </a>
    <a href="gestion-disponibilites.php" class="action-item">
      <div class="action-num" style="background:rgba(251,191,36,.1)">📅</div>
      <div><div class="action-label">Disponibilités</div><div class="action-sub">Calendrier & créneaux</div></div>
      <span class="action-arrow">›</span>
    </a>
    <a href="../frontend/hebergements.html" class="action-item">
      <div class="action-num" style="background:rgba(74,222,128,.1)">🌍</div>
      <div><div class="action-label">Vue publique</div><div class="action-sub">Ce que voient les voyageurs</div></div>
      <span class="action-arrow">›</span>
    </a>
  </div>

  <!-- CTA style frontend -->
  <div class="cta-section" style="margin-top:30px">
    <h2>Votre activité en un coup d'œil 🌴</h2>
    <p>Toutes vos performances et services accessibles depuis ce tableau de bord.</p>
    <a href="gestion-hebergements.php" class="cta-btn">🏨 Gérer mes hébergements</a>
  </div>

</div>

<footer>© 2026 VoyageVista — Host smarter 🌴</footer>
</body>
</html>