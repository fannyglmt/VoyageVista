<?php
// =============================================
// Dashboard Prestataire - VoyageVista
// =============================================
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php");
    exit;
}

require_once 'configuration.php';

$pid = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM services WHERE prestataire_id = ? ORDER BY nom ASC");
$stmt->execute([$pid]);
$services = $stmt->fetchAll();

$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN services s ON r.service_id=s.id WHERE s.prestataire_id=?");
$stmt2->execute([$pid]);
$total_reservations = $stmt2->fetchColumn();

$stmt3 = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND lu=0");
$stmt3->execute([$pid]);
$notifs_non_lues = $stmt3->fetchColumn();

$stmt4 = $pdo->prepare("SELECT COALESCE(SUM(r.prix_total),0) FROM reservations r JOIN services s ON r.service_id=s.id WHERE s.prestataire_id=? AND r.statut!='annulee'");
$stmt4->execute([$pid]);
$ca_total = $stmt4->fetchColumn();

$stmt5 = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY date_envoi DESC LIMIT 5");
$stmt5->execute([$pid]);
$notifications = $stmt5->fetchAll();

// CA par service pour les barres de revenus
foreach ($services as &$s) {
    $stmtCa = $pdo->prepare("SELECT COALESCE(SUM(r.prix_total),0) FROM reservations r WHERE r.service_id=? AND r.statut!='annulee'");
    $stmtCa->execute([$s['id']]);
    $s['ca_service'] = (float)$stmtCa->fetchColumn();
}
unset($s);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Prestataire - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --bg:#0a0a0f;--card:#1a1a26;--border:rgba(255,255,255,0.07);
      --purple:#7c5cfc;--pink:#f25ca2;--teal:#2dd4bf;
      --amber:#fbbf24;--red:#f87171;--green:#4ade80;
      --text:#f0eeff;--muted:#8b8aa8;
    }
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

    .navbar{display:flex;align-items:center;justify-content:space-between;padding:0 2rem;height:64px;background:rgba(10,10,15,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
    .brand img{height:32px}
    .navbar nav{display:flex;gap:.25rem}
    .navbar nav a{font-size:.85rem;font-weight:500;color:var(--muted);text-decoration:none;padding:.4rem .9rem;border-radius:20px;transition:all .2s}
    .navbar nav a:hover{color:var(--text);background:rgba(255,255,255,.06)}
    .navbar nav a.active{color:var(--text);background:rgba(45,212,191,.12);border:1px solid rgba(45,212,191,.25)}
    .nav-right{display:flex;align-items:center;gap:1rem}
    .nav-badge{position:relative;font-size:1.1rem;cursor:pointer;opacity:.7;transition:opacity .2s}
    .nav-badge:hover{opacity:1}
    .badge-dot{position:absolute;top:-2px;right:-2px;width:8px;height:8px;background:var(--pink);border-radius:50%;border:2px solid var(--bg)}
    .avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--purple));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;cursor:pointer}
    .logout{font-size:.8rem;color:var(--muted);text-decoration:none;padding:.35rem .8rem;border:1px solid var(--border);border-radius:20px;transition:all .2s}
    .logout:hover{color:var(--text);border-color:rgba(255,255,255,.2)}

    .prest-hero{padding:2.5rem 2rem 1.5rem;position:relative;overflow:hidden}
    .prest-hero::before{content:'';position:absolute;top:-60px;right:-80px;width:400px;height:300px;background:radial-gradient(ellipse,rgba(45,212,191,.1) 0%,transparent 70%);pointer-events:none}
    .tag{font-family:'Syne',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--teal);background:rgba(45,212,191,.12);border:1px solid rgba(45,212,191,.25);padding:.3rem .8rem;border-radius:20px;display:inline-block;margin-bottom:.75rem}
    .prest-hero h1{font-family:'Syne',sans-serif;font-size:1.9rem;font-weight:800;line-height:1.2;margin-bottom:.4rem}
    .prest-hero h1 span{background:linear-gradient(90deg,var(--teal),var(--purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .prest-hero p{color:var(--muted);font-size:.9rem}
    .hero-top{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem}
    .hero-actions{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem}
    .btn-primary{background:linear-gradient(135deg,var(--teal),var(--purple));color:#fff;border:none;padding:.6rem 1.4rem;border-radius:24px;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;text-decoration:none;transition:opacity .2s,transform .2s;display:inline-flex;align-items:center;gap:.4rem}
    .btn-primary:hover{opacity:.88;transform:translateY(-1px)}
    .btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border);padding:.6rem 1.4rem;border-radius:24px;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;gap:.4rem}
    .btn-ghost:hover{color:var(--text);border-color:rgba(255,255,255,.2)}

    .prest-body{padding:0 2rem 3rem;max-width:1300px}

    .kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:2rem}
    .kpi-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:1.4rem 1.6rem;position:relative;overflow:hidden;transition:transform .2s,border-color .2s}
    .kpi-card:hover{transform:translateY(-3px);border-color:rgba(255,255,255,.14)}
    .kpi-card::after{content:'';position:absolute;top:0;left:0;right:0;height:2px;border-radius:16px 16px 0 0}
    .kpi-card.teal::after{background:var(--teal)}
    .kpi-card.purple::after{background:linear-gradient(90deg,var(--purple),var(--pink))}
    .kpi-card.amber::after{background:var(--amber)}
    .kpi-card.green::after{background:var(--green)}
    .kpi-icon{font-size:1.6rem;margin-bottom:.8rem}
    .kpi-value{font-family:'Syne',sans-serif;font-size:2.2rem;font-weight:800;line-height:1;margin-bottom:.3rem}
    .kpi-card.teal   .kpi-value{color:var(--teal)}
    .kpi-card.purple .kpi-value{color:var(--purple)}
    .kpi-card.amber  .kpi-value{color:var(--amber)}
    .kpi-card.green  .kpi-value{color:var(--green)}
    .kpi-label{font-size:.8rem;color:var(--muted);margin-bottom:.5rem}
    .kpi-sub{font-size:.75rem;color:var(--muted)}
    .kpi-sub.up{color:var(--green)}

    .two-col{display:grid;grid-template-columns:1fr 320px;gap:1.5rem;margin-bottom:1.5rem}
    @media(max-width:900px){.two-col{grid-template-columns:1fr}}
    .bottom-row{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
    @media(max-width:700px){.bottom-row{grid-template-columns:1fr}}

    .section-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden}
    .section-head{display:flex;align-items:center;justify-content:space-between;padding:1.2rem 1.5rem;border-bottom:1px solid var(--border)}
    .section-head h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700}
    .see-all{font-size:.78rem;color:var(--teal);text-decoration:none;font-weight:500}
    .see-all:hover{text-decoration:underline}

    .data-table{width:100%;border-collapse:collapse}
    .data-table th{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:.75rem 1.5rem;text-align:left;border-bottom:1px solid var(--border)}
    .data-table td{padding:.9rem 1.5rem;font-size:.85rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
    .data-table tr:last-child td{border-bottom:none}
    .data-table tr:hover td{background:rgba(255,255,255,.02)}
    .svc-name{font-weight:500}
    .svc-type{font-size:.72rem;color:var(--muted);margin-top:.15rem}
    .edit-link{color:var(--teal);font-size:.78rem;text-decoration:none;font-weight:500}
    .edit-link:hover{text-decoration:underline}

    .pill{display:inline-block;padding:.2rem .65rem;border-radius:20px;font-size:.72rem;font-weight:600}
    .pill-teal{background:rgba(45,212,191,.15);color:#5eead4;border:1px solid rgba(45,212,191,.25)}
    .pill-purple{background:rgba(124,92,252,.15);color:#a78bfa;border:1px solid rgba(124,92,252,.25)}
    .pill-amber{background:rgba(251,191,36,.15);color:#fcd34d;border:1px solid rgba(251,191,36,.25)}
    .pill-red{background:rgba(248,113,113,.15);color:#fca5a5;border:1px solid rgba(248,113,113,.25)}
    .pill-green{background:rgba(74,222,128,.15);color:#86efac;border:1px solid rgba(74,222,128,.25)}

    .actions-list{padding:.5rem 0}
    .action-item{display:flex;align-items:center;gap:1rem;padding:.9rem 1.5rem;text-decoration:none;color:var(--text);transition:background .15s;border-bottom:1px solid rgba(255,255,255,.04)}
    .action-item:last-child{border-bottom:none}
    .action-item:hover{background:rgba(255,255,255,.03)}
    .action-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
    .action-label{font-size:.85rem;font-weight:500}
    .action-sub{font-size:.75rem;color:var(--muted)}
    .action-arrow{margin-left:auto;color:var(--muted);font-size:.8rem}

    .notif-item{display:flex;align-items:flex-start;gap:.85rem;padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.04)}
    .notif-item:last-child{border-bottom:none}
    .notif-dot{width:8px;height:8px;border-radius:50%;background:var(--teal);box-shadow:0 0 6px rgba(45,212,191,.6);flex-shrink:0;margin-top:5px}
    .notif-dot.read{background:var(--muted);box-shadow:none}
    .notif-text{font-size:.83rem}
    .notif-date{font-size:.72rem;color:var(--muted);margin-top:.2rem}

    .revenue-block{padding:1.5rem}
    .revenue-total{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--green);margin-bottom:.3rem}
    .revenue-sub{font-size:.78rem;color:var(--muted);margin-bottom:1.5rem}
    .rev-bar-wrap{margin-bottom:1rem}
    .rev-bar-label{display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:.4rem}
    .rev-bar-label span:last-child{color:var(--muted)}
    .rev-bar-track{height:6px;background:rgba(255,255,255,.08);border-radius:3px;overflow:hidden}
    .rev-bar-fill{height:100%;border-radius:3px}

    .empty-state{padding:2rem;text-align:center;color:var(--muted);font-size:.85rem}
    footer{text-align:center;padding:2rem;color:var(--muted);font-size:.78rem;border-top:1px solid var(--border);margin-top:2rem}
  </style>
</head>
<body>

<header class="navbar">
  <div class="brand"><img src="../frontend/assets/images/logo-voyagevista.png" alt="VoyageVista"></div>
  <nav>
    <a href="../frontend/index.html">Accueil</a>
    <a href="../frontend/hebergements.html">Hébergements</a>
    <a href="../frontend/gestion-activites.html">Activités</a>
    <a href="dashboard_prestataire.php" class="active">Dashboard</a>
  </nav>
  <div class="nav-right">
    <span class="nav-badge">🔔<?php if($notifs_non_lues>0):?><span class="badge-dot"></span><?php endif;?></span>
    <div class="avatar"><?php echo strtoupper(substr($_SESSION['username']??'PR',0,2));?></div>
    <a href="logout.php" class="logout">Déconnexion</a>
  </div>
</header>

<section class="prest-hero">
  <div class="hero-top">
    <div>
      <p class="tag">HOST • MANAGE • EARN</p>
      <h1>Bienvenue, <span><?php echo htmlspecialchars($_SESSION['username']??'Prestataire');?></span> 👋</h1>
      <p>Gérez vos services et suivez vos performances</p>
      <div class="hero-actions">
        <a href="../frontend/gestion-hebergements.html" class="btn-primary">🏨 Ajouter un service</a>
        <a href="../frontend/gestion-disponibilites.html" class="btn-ghost">📅 Disponibilités</a>
      </div>
    </div>
  </div>
</section>

<div class="prest-body">

  <div class="kpi-grid">
    <div class="kpi-card teal">
      <div class="kpi-icon">🏨</div>
      <div class="kpi-value"><?php echo count($services);?></div>
      <div class="kpi-label">Services publiés</div>
      <div class="kpi-sub">Hébergements & activités</div>
    </div>
    <div class="kpi-card purple">
      <div class="kpi-icon">📅</div>
      <div class="kpi-value"><?php echo (int)$total_reservations;?></div>
      <div class="kpi-label">Réservations reçues</div>
      <div class="kpi-sub up">Total</div>
    </div>
    <div class="kpi-card amber">
      <div class="kpi-icon">🔔</div>
      <div class="kpi-value"><?php echo (int)$notifs_non_lues;?></div>
      <div class="kpi-label">Notifications non lues</div>
      <div class="kpi-sub">Messages en attente</div>
    </div>
    <div class="kpi-card green">
      <div class="kpi-icon">💰</div>
      <div class="kpi-value"><?php echo number_format((float)$ca_total,0,',',' ');?>€</div>
      <div class="kpi-label">Revenus générés</div>
      <div class="kpi-sub up">Total cumulé</div>
    </div>
  </div>

  <div class="two-col">
    <div class="section-card">
      <div class="section-head">
        <h2>🏨 Mes services</h2>
        <a href="../frontend/gestion-hebergements.html" class="see-all">Tout gérer →</a>
      </div>
      <?php if(empty($services)):?>
        <p class="empty-state">Aucun service publié pour l'instant.</p>
      <?php else:?>
      <table class="data-table">
        <thead><tr><th>Service</th><th>Type</th><th>Prix</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          <?php foreach($services as $s):
            $tc=['hebergement'=>'pill-teal','activite'=>'pill-purple','transport'=>'pill-amber'];
            $tc2=$tc[$s['type']]??'pill-teal';
            $sc=['actif'=>'pill-green','inactif'=>'pill-red','en_attente'=>'pill-amber'];
            $sc2=$sc[$s['statut']]??'pill-amber'; ?>
          <tr>
            <td>
              <div class="svc-name"><?php echo htmlspecialchars($s['nom']);?></div>
              <div class="svc-type"><?php echo htmlspecialchars($s['type']??'—');?></div>
            </td>
            <td><span class="pill <?php echo $tc2;?>"><?php echo htmlspecialchars($s['type']??'—');?></span></td>
            <td style="font-family:'Syne',sans-serif;font-weight:700;color:var(--green)"><?php echo number_format((float)($s['prix']??0),0,',',' ');?>€</td>
            <td><span class="pill <?php echo $sc2;?>"><?php echo htmlspecialchars($s['statut']??'en_attente');?></span></td>
            <td><a href="gestion-hebergements.html?id=<?php echo (int)$s['id'];?>" class="edit-link">Modifier →</a></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
      <?php endif;?>
    </div>

    <div class="section-card">
      <div class="section-head"><h2>⚡ Navigation</h2></div>
      <div class="actions-list">
        <a href="../frontend/gestion-hebergements.html" class="action-item">
          <div class="action-icon" style="background:rgba(45,212,191,.15)">🏨</div>
          <div><div class="action-label">Hébergements</div><div class="action-sub">Gérer mes logements</div></div>
          <span class="action-arrow">›</span>
        </a>
        <a href="../frontend/gestion-activites.html" class="action-item">
          <div class="action-icon" style="background:rgba(124,92,252,.15)">🏄</div>
          <div><div class="action-label">Activités</div><div class="action-sub">Excursions & expériences</div></div>
          <span class="action-arrow">›</span>
        </a>
        <a href="../frontend/gestion-disponibilites.html" class="action-item">
          <div class="action-icon" style="background:rgba(251,191,36,.15)">📅</div>
          <div><div class="action-label">Disponibilités</div><div class="action-sub">Calendrier & créneaux</div></div>
          <span class="action-arrow">›</span>
        </a>
        <a href="../frontend/hebergements.html" class="action-item">
          <div class="action-icon" style="background:rgba(74,222,128,.15)">🌍</div>
          <div><div class="action-label">Vue publique</div><div class="action-sub">Ce que voient les voyageurs</div></div>
          <span class="action-arrow">›</span>
        </a>
      </div>
    </div>
  </div>

  <div class="bottom-row">
    <div class="section-card">
      <div class="section-head">
        <h2>🔔 Notifications récentes</h2>
        <?php if($notifs_non_lues>0):?><span class="pill pill-teal"><?php echo $notifs_non_lues;?> nouvelles</span><?php endif;?>
      </div>
      <?php if(empty($notifications)):?>
        <p class="empty-state">Aucune notification.</p>
      <?php else: foreach($notifications as $n):?>
      <div class="notif-item">
        <div class="notif-dot <?php echo $n['lu']?'read':'';?>"></div>
        <div>
          <div class="notif-text"><?php echo htmlspecialchars($n['message']);?></div>
          <div class="notif-date"><?php echo substr($n['date_envoi'],0,10);?></div>
        </div>
      </div>
      <?php endforeach; endif;?>
    </div>

    <div class="section-card">
      <div class="section-head"><h2>💰 Mes revenus</h2></div>
      <div class="revenue-block">
        <div class="revenue-total"><?php echo number_format((float)$ca_total,0,',',' ');?>€</div>
        <div class="revenue-sub">Total des réservations confirmées</div>
        <?php
          $colors=['#2dd4bf','#7c5cfc','#fbbf24','#4ade80','#f25ca2'];
          foreach($services as $i=>$s):
            $pct=$ca_total>0?min(100,round($s['ca_service']/$ca_total*100)):0;
            $col=$colors[$i%count($colors)]; ?>
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

</div>

<footer>© 2026 VoyageVista — Provider Dashboard 🌴</footer>
</body>
</html>