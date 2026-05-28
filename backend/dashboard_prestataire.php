<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';
$pid = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM services WHERE prestataire_id=? ORDER BY nom ASC"); $stmt->execute([$pid]); $services=$stmt->fetchAll();
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN services s ON r.service_id=s.id WHERE s.prestataire_id=?"); $stmt2->execute([$pid]); $total_reservations=$stmt2->fetchColumn();
$stmt3 = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND lu=0"); $stmt3->execute([$pid]); $notifs_non_lues=$stmt3->fetchColumn();
$stmt4 = $pdo->prepare("SELECT COALESCE(SUM(r.prix_total),0) FROM reservations r JOIN services s ON r.service_id=s.id WHERE s.prestataire_id=? AND r.statut!='annulee'"); $stmt4->execute([$pid]); $ca_total=$stmt4->fetchColumn();
$stmt5 = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY date_envoi DESC LIMIT 5"); $stmt5->execute([$pid]); $notifications=$stmt5->fetchAll();
foreach($services as &$s){ $sc=$pdo->prepare("SELECT COALESCE(SUM(r.prix_total),0) FROM reservations r WHERE r.service_id=? AND r.statut!='annulee'"); $sc->execute([$s['id']]); $s['ca_service']=(float)$sc->fetchColumn(); } unset($s);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Prestataire - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin_style.css">
  <style>
    .tag{display:inline-block;font-family:'Syne',sans-serif;font-size:.72rem;font-weight:800;letter-spacing:3px;color:var(--orange);background:none;border:none;padding:0;margin-bottom:.9rem}
    .page-hero::before{background:radial-gradient(circle,rgba(45,212,191,.12) 0%,transparent 70%)}
    .btn-primary{background:linear-gradient(135deg,#2dd4bf,var(--purple));box-shadow:0 4px 15px rgba(45,212,191,.3)}
    .navbar nav a.active{color:#f59e0b;background:transparent;font-weight:600}
    .dest-img-card{width:100%;height:180px;object-fit:cover;display:block;border-radius:12px 12px 0 0}
    .svc-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.05);transition:transform .2s,box-shadow .2s}
    .svc-card:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,.1)}
    .svc-body{padding:1rem}
    .svc-name{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;margin-bottom:.3rem}
    .svc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;margin-bottom:2rem}
    .rev-bar-wrap{margin-bottom:.8rem}
    .rev-bar-label{display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:.3rem;color:var(--text)}
    .rev-bar-label span:last-child{color:var(--muted)}
    .rev-bar-track{height:6px;background:#f3f4f6;border-radius:3px;overflow:hidden}
    .rev-bar-fill{height:100%;border-radius:3px}
    .notif-item{display:flex;align-items:flex-start;gap:.85rem;padding:.9rem 1.4rem;border-bottom:1px solid #f3f4f6}
    .notif-item:last-child{border-bottom:none}
    .notif-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:5px}
    .dot-unread{background:#d97706}
    .dot-read{background:#d1d5db}
    .notif-text{font-size:.83rem;color:var(--text)}
    .notif-date{font-size:.72rem;color:var(--muted);margin-top:.2rem}
  </style>
</head>
<body>
<header class="navbar">

  <div class="brand">
    <img src="../frontend/assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
  </div>

  <nav>
    <a href="../frontend/index.html">Accueil</a>
    <a href="../frontend/hebergements.html">Hébergements</a>
    <a href="../frontend/gestion-activites.html">Activités</a>
    <a href="dashboard_prestataire.php" class="active">Dashboard</a>
  </nav>

  <div class="nav-icons">
    <span class="heart-icon">♥</span>
    <span>🔔</span>
    <a href="logout.php">👤</a>
  </div>

</header>

<section class="page-hero">
  <div class="hero-top">
    <div>
      <p class="tag">HOST • MANAGE • EARN</p>
      <h1>Bienvenue, <span><?php echo htmlspecialchars($_SESSION['username']??'Prestataire');?> 👋</span></h1>
      <p>Gérez vos services et suivez vos performances</p>
    </div>
    <div class="hero-actions">
      <a href="gestion-hebergements.php" class="btn-primary">🏨 Ajouter un service</a>
      <a href="../frontend/gestion-disponibilites.html" class="btn-secondary">📅 Disponibilités</a>
    </div>
  </div>
</section>

<div class="page-body">

  <div class="kpi-row">
    <div class="kpi-mini"><div class="kpi-mini-val" style="color:#0d9488"><?php echo count($services);?></div><div class="kpi-mini-label">Services publiés</div></div>
    <div class="kpi-mini"><div class="kpi-mini-val" style="color:var(--purple)"><?php echo (int)$total_reservations;?></div><div class="kpi-mini-label">Réservations</div></div>
    <div class="kpi-mini"><div class="kpi-mini-val" style="color:#d97706"><?php echo (int)$notifs_non_lues;?></div><div class="kpi-mini-label">Notifs non lues</div></div>
    <div class="kpi-mini"><div class="kpi-mini-val" style="color:#16a34a"><?php echo number_format((float)$ca_total,0,',',' ');?>€</div><div class="kpi-mini-label">Revenus totaux</div></div>
  </div>

  <!-- Mes services style trend-card -->
  <div class="section-title">
    <h2>🏨 Mes services</h2>
    <a href="gestion-hebergements.php">Tout gérer →</a>
  </div>
  <?php
    $svcImages=['hebergement'=>'../frontend/assets/images/hebergement-bg.jpg','activite'=>'../frontend/assets/images/boat.png','transport'=>'../frontend/assets/images/barcelone.png'];
    $colors=['#2dd4bf','#7c5cfc','#fbbf24','#4ade80','#f25ca2'];
  ?>
  <?php if(empty($services)):?>
    <div class="white-card"><p class="empty-state">Aucun service publié. <a href="gestion-hebergements.php" style="color:var(--purple)">Ajouter un service →</a></p></div>
  <?php else:?>
  <div class="svc-grid">
    <?php foreach($services as $i=>$s):
      $img=$svcImages[$s['type']??'hebergement']??'../frontend/assets/images/hebergement-bg.jpg';
      $sc=['actif'=>'pill-green','inactif'=>'pill-red','en_attente'=>'pill-amber'];
      $sc2=$sc[$s['statut']]??'pill-amber'; ?>
    <div class="svc-card">
      <img class="dest-img-card" src="<?php echo $img;?>" alt="<?php echo htmlspecialchars($s['nom']);?>">
      <div class="svc-body">
        <div class="svc-name"><?php echo htmlspecialchars($s['nom']);?></div>
        <div style="font-size:.78rem;color:var(--muted);margin-bottom:.6rem"><?php echo htmlspecialchars($s['type']??'—');?></div>
        <div style="display:flex;align-items:center;justify-content:space-between">
          <span style="font-family:'Syne',sans-serif;font-weight:700;color:#16a34a"><?php echo number_format((float)($s['prix']??0),0,',',' ');?>€</span>
          <span class="pill <?php echo $sc2;?>"><?php echo htmlspecialchars($s['statut']??'en_attente');?></span>
        </div>
        <a href="gestion-hebergements.php?id=<?php echo (int)$s['id'];?>" style="display:block;margin-top:.8rem;text-align:center;font-size:.78rem;color:var(--purple);text-decoration:none;font-weight:500;padding:.4rem;border:1px solid rgba(124,92,252,.2);border-radius:8px">Modifier →</a>
      </div>
    </div>
    <?php endforeach;?>
  </div>
  <?php endif;?>

  <div class="two-col">
    <!-- Notifications -->
    <div class="white-card">
      <div class="card-head">
        <h3>🔔 Notifications récentes</h3>
        <?php if($notifs_non_lues>0):?><span class="pill pill-amber"><?php echo $notifs_non_lues;?> nouvelles</span><?php endif;?>
      </div>
      <?php if(empty($notifications)):?>
        <p class="empty-state">Aucune notification.</p>
      <?php else: foreach($notifications as $n):?>
      <div class="notif-item">
        <div class="notif-dot <?php echo $n['lu']?'dot-read':'dot-unread';?>"></div>
        <div><div class="notif-text"><?php echo htmlspecialchars($n['message']);?></div><div class="notif-date"><?php echo substr($n['date_envoi'],0,10);?></div></div>
      </div>
      <?php endforeach; endif;?>
    </div>

    <!-- Revenus -->
    <div class="white-card">
      <div class="card-head"><h3>💰 Mes revenus</h3></div>
      <div style="padding:1.4rem">
        <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:#16a34a;margin-bottom:.3rem"><?php echo number_format((float)$ca_total,0,',',' ');?>€</div>
        <div style="font-size:.78rem;color:var(--muted);margin-bottom:1.5rem">Total des réservations confirmées</div>
        <?php foreach($services as $i=>$s):
          $pct=$ca_total>0?min(100,round($s['ca_service']/$ca_total*100)):0;
          $col=$colors[$i%count($colors)]; ?>
        <div class="rev-bar-wrap">
          <div class="rev-bar-label"><span><?php echo htmlspecialchars($s['nom']);?></span><span><?php echo number_format($s['ca_service'],0,',',' ');?>€</span></div>
          <div class="rev-bar-track"><div class="rev-bar-fill" style="width:<?php echo $pct;?>%;background:<?php echo $col;?>"></div></div>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>

  <!-- Navigation rapide style concept-section -->
  <div class="section-title"><h2>⚡ Navigation rapide</h2></div>
  <div class="white-card">
    <a href="gestion-hebergements.php" class="action-item">
      <div class="action-num" style="background:rgba(45,212,191,.1)">🏨</div>
      <div><div class="action-label">Hébergements</div><div class="action-sub">Gérer mes logements</div></div>
      <span class="action-arrow">›</span>
    </a>
    <a href="../frontend/gestion-activites.html" class="action-item">
      <div class="action-num" style="background:rgba(124,92,252,.1)">🏄</div>
      <div><div class="action-label">Activités</div><div class="action-sub">Excursions & expériences</div></div>
      <span class="action-arrow">›</span>
    </a>
    <a href="../frontend/gestion-disponibilites.html" class="action-item">
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

</div>
<footer>© 2026 VoyageVista — Explore, swipe, travel together.</footer>
</body>
</html>