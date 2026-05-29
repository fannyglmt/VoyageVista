<?php
// =============================================
// Statistiques Plateforme - VoyageVista
// =============================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'configuration.php';

$periode = $_GET['periode'] ?? '12';
$periode = in_array($periode, ['3','6','12','24']) ? (int)$periode : 12;

$total_users        = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$nouveaux_ce_mois   = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE MONTH(date_inscription)=MONTH(NOW()) AND YEAR(date_inscription)=YEAR(NOW())")->fetchColumn();
$total_destinations = $pdo->query("SELECT COUNT(*) FROM destinations WHERE est_active=1")->fetchColumn();
$total_reservations = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$reservations_mois  = $pdo->query("SELECT COUNT(*) FROM reservations WHERE MONTH(date_reservation)=MONTH(NOW()) AND YEAR(date_reservation)=YEAR(NOW())")->fetchColumn();
$chiffre_affaires   = $pdo->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut!='annulee'")->fetchColumn();
$ca_mois            = $pdo->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut!='annulee' AND MONTH(date_reservation)=MONTH(NOW()) AND YEAR(date_reservation)=YEAR(NOW())")->fetchColumn();
$signalements_ouverts = $pdo->query("SELECT COUNT(*) FROM signalements WHERE statut='ouvert'")->fetchColumn();
$taux_annulation    = $pdo->query("SELECT ROUND(COUNT(*)*100.0/NULLIF((SELECT COUNT(*) FROM reservations),0),1) FROM reservations WHERE statut='annulee'")->fetchColumn();

$stmt = $pdo->prepare("SELECT DATE_FORMAT(date_reservation,'%Y-%m') AS mois, COUNT(*) AS nb_reservations, COALESCE(SUM(prix_total),0) AS ca FROM reservations WHERE date_reservation>=DATE_SUB(NOW(),INTERVAL ? MONTH) GROUP BY mois ORDER BY mois ASC");
$stmt->execute([$periode]);
$stats_reservations = $stmt->fetchAll();
$labels_reservations = array_column($stats_reservations,'mois');
$data_reservations   = array_column($stats_reservations,'nb_reservations');
$data_ca             = array_column($stats_reservations,'ca');

$stmt = $pdo->prepare("SELECT DATE_FORMAT(date_inscription,'%Y-%m') AS mois, COUNT(*) AS nb_inscrits FROM utilisateurs WHERE date_inscription>=DATE_SUB(NOW(),INTERVAL ? MONTH) GROUP BY mois ORDER BY mois ASC");
$stmt->execute([$periode]);
$stats_users = $stmt->fetchAll();
$labels_users = array_column($stats_users,'mois');
$data_users   = array_column($stats_users,'nb_inscrits');

$stats_statuts = $pdo->query("SELECT statut, COUNT(*) AS nb FROM reservations GROUP BY statut")->fetchAll();
$labels_statuts = array_column($stats_statuts,'statut');
$data_statuts   = array_column($stats_statuts,'nb');

$top_destinations = $pdo->query("SELECT d.nom, COUNT(r.id) AS nb_reservations FROM destinations d LEFT JOIN reservations r ON r.destination_id=d.id GROUP BY d.id,d.nom ORDER BY nb_reservations DESC LIMIT 8")->fetchAll();
$labels_dest = array_column($top_destinations,'nom');
$data_dest   = array_column($top_destinations,'nb_reservations');

$stats_regions = $pdo->query("SELECT region, COUNT(*) AS nb FROM destinations WHERE region IS NOT NULL AND est_active=1 GROUP BY region")->fetchAll();
$labels_regions = array_column($stats_regions,'region');
$data_regions   = array_column($stats_regions,'nb');

$stats_roles = $pdo->query("SELECT role, COUNT(*) AS nb FROM utilisateurs GROUP BY role")->fetchAll();
$labels_roles = array_column($stats_roles,'role');
$data_roles   = array_column($stats_roles,'nb');

$dernieres_resa = $pdo->query("SELECT r.id,u.username,d.nom AS destination,r.date_debut,r.date_fin,r.nb_voyageurs,r.prix_total,r.statut,r.date_reservation FROM reservations r JOIN utilisateurs u ON r.user_id=u.id JOIN destinations d ON r.destination_id=d.id ORDER BY r.date_reservation DESC LIMIT 10")->fetchAll();

$top_prestataires = $pdo->query("SELECT u.username, COUNT(s.id) AS nb_services, COUNT(r.id) AS nb_reservations, COALESCE(SUM(r.prix_total),0) AS ca_total FROM utilisateurs u LEFT JOIN services s ON s.prestataire_id=u.id LEFT JOIN reservations r ON r.service_id=s.id AND r.statut!='annulee' WHERE u.role='prestataire' GROUP BY u.id,u.username ORDER BY ca_total DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistiques - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="admin_style.css">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--bg:#ffffff;--card:#ffffff;--border:rgba(255,255,255,.07);--purple:#7c5cfc;--pink:#f25ca2;--teal:#2dd4bf;--amber:#fbbf24;--red:#f87171;--green:#4ade80;--text:#1a1a2e;--muted:#6b7280}
    body{font-family:'DM Sans',sans-serif;background:#fff;color:#1a1a2e;min-height:100vh}

    .navbar{display:flex;align-items:center;justify-content:space-between;padding:0 2rem;height:105px;background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
    .brand img{height:100px}
    .navbar nav{display:flex;gap:.25rem}
    .navbar nav a{font-size:.85rem;font-weight:500;color:#4a9fd4;text-decoration:none;padding:.4rem .9rem;border-radius:20px;transition:all .2s}
    .navbar nav a:hover{color:#31517c;background:#f0f7ff}
    .navbar nav a.active{color:#f59e0b;background:transparent;font-weight:600}
    .nav-right{display:flex;align-items:center;gap:1rem}
    .avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#79a9df,#f3b27d);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700}
    .logout{font-size:.8rem;color:#6b7280;text-decoration:none;padding:.35rem .8rem;border:1px solid #e5e7eb;border-radius:20px;transition:all .2s}
    .logout:hover{color:#1a1a2e}

    .page-hero{padding:2.5rem 2rem 1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;top:-60px;right:-80px;width:400px;height:300px;background:radial-gradient(ellipse,rgba(74,222,128,.1) 0%,transparent 70%);pointer-events:none}
    .tag{display:inline-block;font-family:'Syne',sans-serif;font-size:.72rem;font-weight:800;letter-spacing:3px;color:var(--orange);background:none;border:none;padding:0;margin-bottom:.9rem}
    .page-hero h1{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--blue);margin-bottom:.5rem;line-height:1.2}
    .page-hero h1 span{color:var(--blue)}
    .page-hero p{color:#6b7280;font-size:.9rem}
    .hero-top{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem}

    .periode-filter{display:flex;gap:.5rem;flex-wrap:wrap;align-self:center}
    .periode-filter a{font-size:.82rem;font-weight:500;padding:.4rem .9rem;border-radius:20px;text-decoration:none;color:#6b7280;border:1px solid #e5e7eb;transition:all .2s}
    .periode-filter a:hover{color:#1a1a2e;border-color:rgba(255,255,255,.2)}
    .periode-filter a.active{color:#1a1a2e;background:rgba(74,222,128,.15);border-color:rgba(74,222,128,.3)}

    .page-body{padding:0 2rem 3rem;max-width:1400px}

    .kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:2rem}
    .kpi-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:1.4rem 1.6rem;position:relative;overflow:hidden;transition:transform .2s}
    .kpi-card:hover{transform:translateY(-3px)}
    .kpi-card::after{content:'';position:absolute;top:0;left:0;right:0;height:2px;border-radius:16px 16px 0 0}
    .kpi-card.purple::after{background:linear-gradient(90deg,var(--purple),var(--pink))}
    .kpi-card.teal::after{background:var(--teal)}
    .kpi-card.amber::after{background:var(--amber)}
    .kpi-card.red::after{background:var(--red)}
    .kpi-card.green::after{background:var(--green)}
    .kpi-icon{font-size:1.6rem;margin-bottom:.8rem}
    .kpi-value{font-family:'Syne',sans-serif;font-size:2.2rem;font-weight:800;line-height:1;margin-bottom:.3rem}
    .kpi-card.purple .kpi-value{color:var(--purple)}
    .kpi-card.teal   .kpi-value{color:var(--teal)}
    .kpi-card.amber  .kpi-value{color:var(--amber)}
    .kpi-card.red    .kpi-value{color:var(--red)}
    .kpi-card.green  .kpi-value{color:var(--green)}
    .kpi-label{font-size:.8rem;color:#6b7280;margin-bottom:.5rem}
    .kpi-sub{font-size:.75rem;color:#6b7280}
    .kpi-sub.up{color:var(--green)}
    .kpi-sub.bad{color:var(--red)}

    .charts-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(520px,1fr));gap:1.5rem;margin-bottom:2rem}
    @media(max-width:700px){.charts-grid{grid-template-columns:1fr}}
    .chart-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:1.5rem}
    .chart-card h3{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;margin-bottom:1.2rem;color:#1a1a2e}
    .chart-card canvas{max-height:260px}
    .chart-card.full{grid-column:1/-1}

    .section-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;margin-bottom:1.5rem}
    .section-head{display:flex;align-items:center;justify-content:space-between;padding:1.2rem 1.5rem;border-bottom:1px solid var(--border)}
    .section-head h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700}

    .data-table{width:100%;border-collapse:collapse}
    .data-table th{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;padding:.75rem 1.5rem;text-align:left;border-bottom:1px solid var(--border)}
    .data-table td{padding:.85rem 1.5rem;font-size:.83rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
    .data-table tr:last-child td{border-bottom:none}
    .data-table tr:hover td{background:rgba(255,255,255,.02)}

    .pill{display:inline-block;padding:.2rem .65rem;border-radius:20px;font-size:.72rem;font-weight:600}
    .pill-green{background:rgba(74,222,128,.15);color:#86efac;border:1px solid rgba(74,222,128,.25)}
    .pill-amber{background:rgba(251,191,36,.15);color:#fcd34d;border:1px solid rgba(251,191,36,.25)}
    .pill-red{background:rgba(248,113,113,.15);color:#fca5a5;border:1px solid rgba(248,113,113,.25)}
    .pill-purple{background:rgba(124,92,252,.15);color:#a78bfa;border:1px solid rgba(124,92,252,.25)}
    .pill-teal{background:rgba(45,212,191,.15);color:#5eead4;border:1px solid rgba(45,212,191,.25)}

    footer{text-align:center;padding:2rem;color:#6b7280;font-size:.78rem;border-top:1px solid var(--border);margin-top:2rem}
  </style>
</head>
<body>

<header class="navbar">

  <div class="brand">
    <img src="../frontend/assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
  </div>

  <nav>
    <a href="dashboard_admin.php">Dashboard</a>
    <a href="gestion_utilisateur.php">Utilisateurs</a>
    <a href="gestion_signalement.php">Signalements</a>
    <a href="statistiques.php" class="active">Stats</a>
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
      <p class="tag">DATA • INSIGHTS • ANALYTICS</p>
      <h1>Statistiques <span>plateforme</span></h1>
      <p>Analyse des performances de VoyageVista</p>
    </div>
    <div class="periode-filter">
      <?php foreach(['3'=>'3 mois','6'=>'6 mois','12'=>'12 mois','24'=>'2 ans'] as $v=>$l):?>
      <a href="?periode=<?php echo $v;?>" class="<?php echo $periode==$v?'active':'';?>"><?php echo $l;?></a>
      <?php endforeach;?>
    </div>
  </div>
</section>

<div class="page-body">

  <div class="kpi-grid">
    <div class="kpi-card purple">
      <div class="kpi-icon">👤</div>
      <div class="kpi-value"><?php echo (int)$total_users;?></div>
      <div class="kpi-label">Utilisateurs</div>
      <div class="kpi-sub up">+<?php echo (int)$nouveaux_ce_mois;?> ce mois</div>
    </div>
    <div class="kpi-card teal">
      <div class="kpi-icon">🗺️</div>
      <div class="kpi-value"><?php echo (int)$total_destinations;?></div>
      <div class="kpi-label">Destinations actives</div>
    </div>
    <div class="kpi-card amber">
      <div class="kpi-icon">📅</div>
      <div class="kpi-value"><?php echo (int)$total_reservations;?></div>
      <div class="kpi-label">Réservations</div>
      <div class="kpi-sub up">+<?php echo (int)$reservations_mois;?> ce mois</div>
    </div>
    <div class="kpi-card green">
      <div class="kpi-icon">💰</div>
      <div class="kpi-value"><?php echo number_format((float)$chiffre_affaires,0,',',' ');?>€</div>
      <div class="kpi-label">Chiffre d'affaires</div>
      <div class="kpi-sub up">+<?php echo number_format((float)$ca_mois,0,',',' ');?>€ ce mois</div>
    </div>
    <div class="kpi-card red">
      <div class="kpi-icon">⚠️</div>
      <div class="kpi-value"><?php echo (int)$signalements_ouverts;?></div>
      <div class="kpi-label">Signalements ouverts</div>
    </div>
    <div class="kpi-card <?php echo $taux_annulation>20?'red':'teal';?>">
      <div class="kpi-icon">❌</div>
      <div class="kpi-value"><?php echo $taux_annulation??0;?>%</div>
      <div class="kpi-label">Taux d'annulation</div>
    </div>
  </div>

  <div class="charts-grid">
    <div class="chart-card full">
      <h3>📅 Réservations & CA — <?php echo $periode;?> derniers mois</h3>
      <canvas id="chartResas"></canvas>
    </div>
    <div class="chart-card">
      <h3>👤 Nouvelles inscriptions</h3>
      <canvas id="chartUsers"></canvas>
    </div>
    <div class="chart-card">
      <h3>📋 Réservations par statut</h3>
      <canvas id="chartStatuts"></canvas>
    </div>
    <div class="chart-card">
      <h3>🔥 Top destinations</h3>
      <canvas id="chartDest"></canvas>
    </div>
    <div class="chart-card">
      <h3>🗺️ Destinations par région</h3>
      <canvas id="chartRegions"></canvas>
    </div>
    <div class="chart-card">
      <h3>👥 Répartition des rôles</h3>
      <canvas id="chartRoles"></canvas>
    </div>
  </div>

  <div class="section-card">
    <div class="section-head"><h2>🕐 10 dernières réservations</h2></div>
    <table class="data-table">
      <thead><tr><th>#</th><th>Utilisateur</th><th>Destination</th><th>Dates</th><th>Voyageurs</th><th>Prix</th><th>Statut</th></tr></thead>
      <tbody>
        <?php if(empty($dernieres_resa)):?>
        <tr><td colspan="7" style="text-align:center;color:#6b7280;padding:2rem">Aucune réservation.</td></tr>
        <?php else: foreach($dernieres_resa as $r):
          $sc=['confirmee'=>'pill-green','en_attente'=>'pill-amber','annulee'=>'pill-red','terminee'=>'pill-purple'];
          $sc2=$sc[$r['statut']]??'pill-amber';?>
        <tr>
          <td style="color:#6b7280">#<?php echo (int)$r['id'];?></td>
          <td style="font-weight:500"><?php echo htmlspecialchars($r['username']);?></td>
          <td><?php echo htmlspecialchars($r['destination']);?></td>
          <td style="color:#6b7280;font-size:.78rem"><?php echo htmlspecialchars($r['date_debut']);?> → <?php echo htmlspecialchars($r['date_fin']);?></td>
          <td style="text-align:center"><?php echo (int)$r['nb_voyageurs'];?></td>
          <td style="font-family:'Syne',sans-serif;font-weight:700;color:var(--green)"><?php echo number_format((float)$r['prix_total'],0,',',' ');?>€</td>
          <td><span class="pill <?php echo $sc2;?>"><?php echo htmlspecialchars($r['statut']);?></span></td>
        </tr>
        <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>

  <div class="section-card">
    <div class="section-head"><h2>🏆 Top prestataires</h2></div>
    <table class="data-table">
      <thead><tr><th>Prestataire</th><th>Services</th><th>Réservations</th><th>CA généré</th></tr></thead>
      <tbody>
        <?php if(empty($top_prestataires)):?>
        <tr><td colspan="4" style="text-align:center;color:#6b7280;padding:2rem">Aucun prestataire.</td></tr>
        <?php else: foreach($top_prestataires as $p):?>
        <tr>
          <td style="font-weight:500"><?php echo htmlspecialchars($p['username']);?></td>
          <td><?php echo (int)$p['nb_services'];?></td>
          <td><?php echo (int)$p['nb_reservations'];?></td>
          <td style="font-family:'Syne',sans-serif;font-weight:700;color:var(--green)"><?php echo number_format((float)$p['ca_total'],0,',',' ');?>€</td>
        </tr>
        <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>

</div>

<footer>© 2026 VoyageVista — Admin Statistics</footer>

<script>
const palette=['#7c5cfc','#2dd4bf','#fbbf24','#f87171','#4ade80','#f25ca2','#818cf8','#34d399'];
const gridColor='rgba(255,255,255,0.06)';
const textColor='#6b7280';
const defaults={color:textColor,font:{family:"'DM Sans',sans-serif",size:11}};
Chart.defaults.color=textColor;
Chart.defaults.font.family="'DM Sans',sans-serif";

new Chart(document.getElementById('chartResas'),{data:{labels:<?php echo json_encode($labels_reservations);?>,datasets:[{type:'bar',label:'Réservations',data:<?php echo json_encode($data_reservations);?>,backgroundColor:'rgba(124,92,252,.3)',borderColor:'#7c5cfc',borderWidth:2,yAxisID:'y'},{type:'line',label:'CA (€)',data:<?php echo json_encode($data_ca);?>,borderColor:'#4ade80',backgroundColor:'rgba(74,222,128,.08)',tension:.4,pointRadius:4,fill:true,yAxisID:'y1'}]},options:{responsive:true,interaction:{mode:'index',intersect:false},scales:{y:{beginAtZero:true,grid:{color:gridColor},ticks:{color:textColor}},y1:{beginAtZero:true,position:'right',grid:{drawOnChartArea:false},ticks:{color:textColor}}},plugins:{legend:{labels:{color:textColor}}}}});

new Chart(document.getElementById('chartUsers'),{type:'line',data:{labels:<?php echo json_encode($labels_users);?>,datasets:[{label:'Inscriptions',data:<?php echo json_encode($data_users);?>,borderColor:'#f25ca2',backgroundColor:'rgba(242,92,162,.08)',fill:true,tension:.4,pointRadius:4}]},options:{responsive:true,scales:{y:{beginAtZero:true,grid:{color:gridColor},ticks:{color:textColor}},x:{grid:{color:gridColor},ticks:{color:textColor}}},plugins:{legend:{labels:{color:textColor}}}}});

new Chart(document.getElementById('chartStatuts'),{type:'doughnut',data:{labels:<?php echo json_encode($labels_statuts);?>,datasets:[{data:<?php echo json_encode($data_statuts);?>,backgroundColor:palette,borderColor:'#ffffff',borderWidth:3}]},options:{responsive:true,plugins:{legend:{position:'right',labels:{color:textColor}}}}});

new Chart(document.getElementById('chartDest'),{type:'bar',data:{labels:<?php echo json_encode($labels_dest);?>,datasets:[{label:'Réservations',data:<?php echo json_encode($data_dest);?>,backgroundColor:palette}]},options:{responsive:true,indexAxis:'y',scales:{x:{beginAtZero:true,grid:{color:gridColor},ticks:{color:textColor}},y:{grid:{color:gridColor},ticks:{color:textColor}}},plugins:{legend:{display:false}}}});

new Chart(document.getElementById('chartRegions'),{type:'pie',data:{labels:<?php echo json_encode($labels_regions);?>,datasets:[{data:<?php echo json_encode($data_regions);?>,backgroundColor:palette,borderColor:'#ffffff',borderWidth:3}]},options:{responsive:true,plugins:{legend:{position:'right',labels:{color:textColor}}}}});

new Chart(document.getElementById('chartRoles'),{type:'doughnut',data:{labels:<?php echo json_encode($labels_roles);?>,datasets:[{data:<?php echo json_encode($data_roles);?>,backgroundColor:['#7c5cfc','#2dd4bf','#fbbf24'],borderColor:'#ffffff',borderWidth:3}]},options:{responsive:true,plugins:{legend:{position:'right',labels:{color:textColor}}}}});
</script>
</body>
</html>