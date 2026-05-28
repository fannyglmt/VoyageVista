<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';

$total_users          = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$total_dest           = $pdo->query("SELECT COUNT(*) FROM destinations WHERE est_active=1")->fetchColumn();
$signalements_ouverts = $pdo->query("SELECT COUNT(*) FROM signalements WHERE statut='ouvert'")->fetchColumn();
$total_reservations   = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$nouveaux_ce_mois     = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE MONTH(date_inscription)=MONTH(NOW()) AND YEAR(date_inscription)=YEAR(NOW())")->fetchColumn();
$reservations_mois    = $pdo->query("SELECT COUNT(*) FROM reservations WHERE MONTH(date_reservation)=MONTH(NOW()) AND YEAR(date_reservation)=YEAR(NOW())")->fetchColumn();
$chiffre_affaires     = $pdo->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut!='annulee'")->fetchColumn();
$ca_mois              = $pdo->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut!='annulee' AND MONTH(date_reservation)=MONTH(NOW()) AND YEAR(date_reservation)=YEAR(NOW())")->fetchColumn();

$derniers_users = $pdo->query("SELECT * FROM utilisateurs ORDER BY date_inscription DESC LIMIT 5")->fetchAll();
$derniers_signalements = $pdo->query("SELECT s.*, u.username FROM signalements s LEFT JOIN utilisateurs u ON s.user_id=u.id ORDER BY s.date_signalement DESC LIMIT 4")->fetchAll();
$top_destinations = $pdo->query("SELECT d.nom, d.region, d.budget, COUNT(r.id) AS nb FROM destinations d LEFT JOIN reservations r ON r.destination_id=d.id GROUP BY d.id ORDER BY nb DESC LIMIT 4")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--purple:#7c5cfc;--pink:#f25ca2;--teal:#2dd4bf;--amber:#fbbf24;--red:#f87171;--green:#4ade80;--text:#1a1a2e;--muted:#6b7280;--border:#e5e7eb;--bg:#ffffff;--bg2:#f9fafb}
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

    /* ── NAVBAR style index.html ── */
    .navbar{display:flex;align-items:center;justify-content:space-between;padding:0 2rem;height:64px;background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;box-shadow:0 1px 8px rgba(0,0,0,.06)}
    .brand img{height:32px}
    .navbar nav{display:flex;gap:.25rem}
    .navbar nav a{font-size:.85rem;font-weight:500;color:#4a9fd4;text-decoration:none;padding:.4rem .9rem;border-radius:20px;transition:all .2s}
    .navbar nav a:hover{color:#2d7db3;background:#f0f7ff}
    .navbar nav a.active{color:#f59e0b;background:transparent;font-weight:600}
    .nav-right{display:flex;align-items:center;gap:1rem}
    .avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--purple),var(--pink));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;cursor:pointer}
    .logout{font-size:.82rem;color:var(--muted);text-decoration:none;padding:.35rem .9rem;border:1px solid var(--border);border-radius:20px;transition:all .2s}
    .logout:hover{color:var(--text);border-color:#d1d5db}

    /* ── HERO style index.html ── */
    .page-hero{padding:3rem 2rem 2rem;background:linear-gradient(135deg,#faf5ff 0%,#fdf2f8 50%,#f0fdfa 100%);border-bottom:1px solid var(--border);position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;top:-80px;right:-80px;width:300px;height:300px;background:radial-gradient(circle,rgba(124,92,252,.12) 0%,transparent 70%);pointer-events:none}
    .tag{display:inline-block;font-family:'Syne',sans-serif;font-size:.72rem;font-weight:700;letter-spacing:.15em;color:#f59e0b;background:none;border:none;padding:0;margin-bottom:.9rem}
    .page-hero h1{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:#3b6fd4;margin-bottom:.5rem;line-height:1.2}
    .page-hero h1 span{color:#3b6fd4}
    .page-hero p{color:var(--muted);font-size:.95rem}
    .hero-top{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem}
    .hero-actions{display:flex;gap:.75rem;flex-wrap:wrap;align-self:center}
    .btn-primary{background:linear-gradient(135deg,var(--purple),var(--pink));color:#fff;border:none;padding:.65rem 1.4rem;border-radius:24px;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;transition:opacity .2s,transform .2s;display:inline-flex;align-items:center;gap:.4rem;box-shadow:0 4px 15px rgba(124,92,252,.3)}
    .btn-primary:hover{opacity:.88;transform:translateY(-1px)}
    .btn-secondary{background:#fff;color:var(--purple);border:2px solid rgba(124,92,252,.3);padding:.6rem 1.3rem;border-radius:24px;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;gap:.4rem}
    .btn-secondary:hover{background:rgba(124,92,252,.06)}

    .page-body{padding:2rem;max-width:1300px}

    /* ── KPI cards style stat de index.html ── */
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:2.5rem}
    .stat-card{background:#fff;border:1px solid var(--border);border-radius:16px;padding:1.5rem;position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s;box-shadow:0 2px 8px rgba(0,0,0,.04)}
    .stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
    .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:16px 16px 0 0}
    .stat-card.purple::before{background:linear-gradient(90deg,var(--purple),var(--pink))}
    .stat-card.teal::before{background:var(--teal)}
    .stat-card.amber::before{background:var(--amber)}
    .stat-card.red::before{background:var(--red)}
    .stat-card.green::before{background:var(--green)}
    .stat-icon{font-size:1.8rem;margin-bottom:.8rem;display:block}
    .stat-value{font-family:'Syne',sans-serif;font-size:2.2rem;font-weight:800;line-height:1;margin-bottom:.3rem}
    .stat-card.purple .stat-value{color:var(--purple)}
    .stat-card.teal   .stat-value{color:#0d9488}
    .stat-card.amber  .stat-value{color:#d97706}
    .stat-card.red    .stat-value{color:#dc2626}
    .stat-card.green  .stat-value{color:#16a34a}
    .stat-label{font-size:.82rem;color:var(--muted);margin-bottom:.4rem}
    .stat-sub{font-size:.75rem;color:var(--muted)}
    .stat-sub.up{color:#16a34a}
    .stat-sub.warn{color:#d97706}

    /* ── Section title style index.html ── */
    .section-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem}
    .section-title h2{font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:700;color:var(--text)}
    .section-title a{font-size:.82rem;color:var(--purple);text-decoration:none;font-weight:500}
    .section-title a:hover{text-decoration:underline}

    /* ── Trend cards style index.html ── */
    .trend-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;margin-bottom:2.5rem}
    .trend-card{border-radius:16px;overflow:hidden;background:#fff;border:1px solid var(--border);box-shadow:0 2px 8px rgba(0,0,0,.05);transition:transform .2s,box-shadow .2s;cursor:pointer}
    .trend-card:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(0,0,0,.12)}
    .trend-card img{width:100%;height:150px;object-fit:cover;display:block}
    .trend-content{padding:1rem}
    .badge{display:inline-block;font-size:.72rem;font-weight:600;padding:.2rem .65rem;border-radius:20px;margin-bottom:.5rem;background:rgba(124,92,252,.1);color:var(--purple)}
    .badge.hot{background:rgba(239,68,68,.1);color:#dc2626}
    .badge.chill{background:rgba(45,212,191,.1);color:#0d9488}
    .badge.vibes{background:rgba(251,191,36,.1);color:#d97706}
    .badge.new{background:rgba(74,222,128,.1);color:#16a34a}
    .trend-card h3{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;margin-bottom:.3rem;color:var(--text)}
    .trend-card p{font-size:.8rem;color:var(--muted);margin-bottom:.5rem}
    .mini-info{font-size:.75rem;color:var(--muted);font-weight:500}

    /* ── Two col layout ── */
    .two-col{display:grid;grid-template-columns:1fr 340px;gap:1.5rem;margin-bottom:2rem}
    @media(max-width:900px){.two-col{grid-template-columns:1fr}}

    /* ── Cards blanches ── */
    .white-card{background:#fff;border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.04)}
    .card-head{display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.4rem;border-bottom:1px solid var(--border)}
    .card-head h3{font-family:'Syne',sans-serif;font-size:.95rem;font-weight:700;color:var(--text)}

    /* ── Table ── */
    .data-table{width:100%;border-collapse:collapse}
    .data-table th{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);padding:.7rem 1.4rem;text-align:left;border-bottom:1px solid var(--border);background:#f9fafb}
    .data-table td{padding:.85rem 1.4rem;font-size:.84rem;border-bottom:1px solid #f3f4f6;vertical-align:middle;color:var(--text)}
    .data-table tr:last-child td{border-bottom:none}
    .data-table tr:hover td{background:#fafafa}
    .user-cell{display:flex;align-items:center;gap:.7rem}
    .user-av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--purple),var(--pink));display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0}
    .user-name{font-weight:500;font-size:.84rem;color:var(--text)}
    .user-email{font-size:.73rem;color:var(--muted)}

    /* ── Pills ── */
    .pill{display:inline-block;padding:.2rem .65rem;border-radius:20px;font-size:.72rem;font-weight:600}
    .pill-purple{background:rgba(124,92,252,.1);color:var(--purple);border:1px solid rgba(124,92,252,.2)}
    .pill-pink{background:rgba(242,92,162,.1);color:#db2777;border:1px solid rgba(242,92,162,.2)}
    .pill-teal{background:rgba(45,212,191,.1);color:#0d9488;border:1px solid rgba(45,212,191,.2)}
    .pill-amber{background:rgba(251,191,36,.1);color:#d97706;border:1px solid rgba(251,191,36,.2)}
    .pill-red{background:rgba(248,113,113,.1);color:#dc2626;border:1px solid rgba(248,113,113,.2)}
    .pill-green{background:rgba(74,222,128,.1);color:#16a34a;border:1px solid rgba(74,222,128,.2)}

    /* ── Quick actions style concept-card de index.html ── */
    .action-item{display:flex;align-items:center;gap:1rem;padding:.9rem 1.4rem;text-decoration:none;color:var(--text);transition:background .15s;border-bottom:1px solid #f3f4f6}
    .action-item:last-child{border-bottom:none}
    .action-item:hover{background:#f9fafb}
    .action-num{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
    .action-label{font-size:.85rem;font-weight:500;color:var(--text)}
    .action-sub{font-size:.75rem;color:var(--muted)}
    .action-arrow{margin-left:auto;color:var(--muted);font-size:.9rem}

    /* ── Signalements ── */
    .report-item{display:flex;align-items:center;gap:.9rem;padding:.9rem 1.4rem;border-bottom:1px solid #f3f4f6}
    .report-item:last-child{border-bottom:none}
    .report-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .dot-red{background:#dc2626}
    .dot-amber{background:#d97706}
    .dot-green{background:#16a34a}
    .report-raison{font-size:.83rem;font-weight:500;color:var(--text)}
    .report-meta{font-size:.72rem;color:var(--muted);margin-top:.15rem}

    /* ── CTA section style index.html ── */
    .cta-section{background:linear-gradient(135deg,rgba(124,92,252,.08),rgba(242,92,162,.06));border:1px solid rgba(124,92,252,.15);border-radius:20px;padding:2rem;text-align:center;margin-bottom:2rem}
    .cta-section h2{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;margin-bottom:.5rem;color:var(--text)}
    .cta-section p{color:var(--muted);font-size:.9rem;margin-bottom:1.2rem}

    /* ── Alert ── */
    .alert{padding:.9rem 1.2rem;border-radius:10px;font-size:.85rem;margin-bottom:1.5rem;border:1px solid}
    .alert-success{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}

    footer{text-align:center;padding:2rem;color:var(--muted);font-size:.78rem;border-top:1px solid var(--border);margin-top:2rem}
  </style>
</head>
<body>

<header class="navbar">

  <div class="brand">
    <img src="../frontend/assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
  </div>

  <nav>
    <a href="dashboard_admin.php" class="active">Dashboard</a>
    <a href="gestion_utilisateur.php">Utilisateurs</a>
    <a href="gestion_destination.php">Destinations</a>
    <a href="gestion_signalement.php">Signalements</a>
    <a href="gestion_notification.php">Notifications</a>
    <a href="statistiques.php">Stats</a>
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
      <p class="tag">ADMIN • CONTROL • MANAGE</p>
      <h1>Dashboard <span>Administrateur</span></h1>
      <p>Vue globale sur la plateforme VoyageVista</p>
    </div>
    <div class="hero-actions">
      <a href="gestion_utilisateur.php" class="btn-primary">👥 Utilisateurs</a>
      <a href="gestion_signalement.php" class="btn-secondary">⚠️ Signalements</a>
    </div>
  </div>
</section>

<div class="page-body">

  <!-- KPI -->
  <div class="stats-grid">
    <div class="stat-card purple">
      <span class="stat-icon">👤</span>
      <div class="stat-value"><?php echo (int)$total_users;?></div>
      <div class="stat-label">Utilisateurs inscrits</div>
      <div class="stat-sub up">+<?php echo (int)$nouveaux_ce_mois;?> ce mois</div>
    </div>
    <div class="stat-card teal">
      <span class="stat-icon">🗺️</span>
      <div class="stat-value"><?php echo (int)$total_dest;?></div>
      <div class="stat-label">Destinations actives</div>
      <div class="stat-sub">Catalogue en ligne</div>
    </div>
    <div class="stat-card amber">
      <span class="stat-icon">📅</span>
      <div class="stat-value"><?php echo (int)$total_reservations;?></div>
      <div class="stat-label">Réservations totales</div>
      <div class="stat-sub up">+<?php echo (int)$reservations_mois;?> ce mois</div>
    </div>
    <div class="stat-card red">
      <span class="stat-icon">⚠️</span>
      <div class="stat-value"><?php echo (int)$signalements_ouverts;?></div>
      <div class="stat-label">Signalements ouverts</div>
      <div class="stat-sub <?php echo $signalements_ouverts>5?'warn':'up';?>"><?php echo $signalements_ouverts>5?'À traiter':'Sous contrôle';?></div>
    </div>
    <div class="stat-card green">
      <span class="stat-icon">💰</span>
      <div class="stat-value"><?php echo number_format((float)$chiffre_affaires,0,',',' ');?>€</div>
      <div class="stat-label">Chiffre d'affaires</div>
      <div class="stat-sub up">+<?php echo number_format((float)$ca_mois,0,',',' ');?>€ ce mois</div>
    </div>
  </div>

  <!-- Top destinations style trend-grid -->
  <div class="section-title">
    <h2>🔥 Destinations les plus réservées</h2>
    <a href="gestion_destination.php">Gérer →</a>
  </div>
  <?php
    $destImages = [
      'Bali'       => '../frontend/assets/images/bali.png',
      'Algarve'    => '../frontend/assets/images/algarve.png',
      'Barcelone'  => '../frontend/assets/images/barcelone.png',
      'Chamonix'   => '../frontend/assets/images/chamonix.png',
      'Costa Rica' => '../frontend/assets/images/costarica.png',
    ];
    $destBadges = [0=>'🔥 Hot',1=>'🎉 Vibes',2=>'🌊 Chill',3=>'🆕 New'];
    $badgeClass = [0=>'hot',1=>'vibes',2=>'chill',3=>'new'];
  ?>
  <div class="trend-grid" style="margin-bottom:2.5rem">
    <?php foreach($top_destinations as $i=>$d):
      $img = $destImages[$d['nom']] ?? '../frontend/assets/images/bali.png'; ?>
    <article class="trend-card">
      <img src="<?php echo $img;?>" alt="<?php echo htmlspecialchars($d['nom']);?>">
      <div class="trend-content">
        <span class="badge <?php echo $badgeClass[$i%4];?>"><?php echo $destBadges[$i%4];?></span>
        <h3><?php echo htmlspecialchars($d['nom']);?></h3>
        <p><?php echo htmlspecialchars($d['region']??'');?> <?php echo htmlspecialchars($d['budget']??'');?></p>
        <div class="mini-info">📅 <?php echo (int)$d['nb'];?> réservation<?php echo $d['nb']>1?'s':'';?></div>
      </div>
    </article>
    <?php endforeach;?>
  </div>

  <!-- Derniers inscrits + Actions rapides -->
  <div class="two-col">
    <div class="white-card">
      <div class="card-head">
        <h3>👥 Derniers inscrits</h3>
        <a href="gestion_utilisateur.php" style="font-size:.78rem;color:var(--purple);text-decoration:none;font-weight:500">Voir tous →</a>
      </div>
      <table class="data-table">
        <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Statut</th></tr></thead>
        <tbody>
          <?php foreach($derniers_users as $u):
            $rc=['admin'=>'pill-pink','prestataire'=>'pill-teal','utilisateur'=>'pill-purple'];
            $c=$rc[$u['role']]??'pill-purple'; ?>
          <tr>
            <td>
              <div class="user-cell">
                <div class="user-av"><?php echo strtoupper(substr($u['username'],0,2));?></div>
                <div>
                  <div class="user-name"><?php echo htmlspecialchars($u['username']);?></div>
                  <div class="user-email"><?php echo htmlspecialchars($u['email']);?></div>
                </div>
              </div>
            </td>
            <td><span class="pill <?php echo $c;?>"><?php echo htmlspecialchars($u['role']);?></span></td>
            <td><span class="pill <?php echo isset($u['est_actif'])&&$u['est_actif']?'pill-green':'pill-red';?>"><?php echo isset($u['est_actif'])&&$u['est_actif']?'Actif':'Banni';?></span></td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>

    <div class="white-card">
      <div class="card-head"><h3>⚡ Actions rapides</h3></div>
      <a href="gestion_utilisateur.php" class="action-item">
        <div class="action-num" style="background:rgba(124,92,252,.1)">👥</div>
        <div><div class="action-label">Utilisateurs</div><div class="action-sub">Rôles, comptes, accès</div></div>
        <span class="action-arrow">›</span>
      </a>
      <a href="gestion_destination.php" class="action-item">
        <div class="action-num" style="background:rgba(45,212,191,.1)">🗺️</div>
        <div><div class="action-label">Destinations</div><div class="action-sub">Ajouter, modifier</div></div>
        <span class="action-arrow">›</span>
      </a>
      <a href="gestion_signalement.php" class="action-item">
        <div class="action-num" style="background:rgba(248,113,113,.1)">🚩</div>
        <div><div class="action-label">Signalements</div><div class="action-sub">Modérer les rapports</div></div>
        <span class="action-arrow">›</span>
      </a>
      <a href="gestion_notification.php" class="action-item">
        <div class="action-num" style="background:rgba(251,191,36,.1)">🔔</div>
        <div><div class="action-label">Notifications</div><div class="action-sub">Envoyer des messages</div></div>
        <span class="action-arrow">›</span>
      </a>
      <a href="statistiques.php" class="action-item">
        <div class="action-num" style="background:rgba(74,222,128,.1)">📊</div>
        <div><div class="action-label">Statistiques</div><div class="action-sub">KPI & graphiques</div></div>
        <span class="action-arrow">›</span>
      </a>
    </div>
  </div>

  <!-- Signalements récents -->
  <div class="section-title">
    <h2>🚩 Signalements récents</h2>
    <a href="gestion_signalement.php">Voir tous →</a>
  </div>
  <div class="white-card" style="margin-bottom:2rem">
    <?php if(empty($derniers_signalements)):?>
      <p style="padding:2rem;text-align:center;color:var(--muted)">Aucun signalement.</p>
    <?php else: foreach($derniers_signalements as $s):
      $dc=['ouvert'=>'dot-red','en_cours'=>'dot-amber','resolu'=>'dot-green','rejete'=>'dot-green'];
      $sc=['ouvert'=>'pill-red','en_cours'=>'pill-amber','resolu'=>'pill-green','rejete'=>'pill-purple'];?>
    <div class="report-item">
      <div class="report-dot <?php echo $dc[$s['statut']]??'dot-amber';?>"></div>
      <div style="flex:1">
        <div class="report-raison"><?php echo htmlspecialchars($s['raison']);?></div>
        <div class="report-meta">par <?php echo htmlspecialchars($s['username']??'?');?> · <?php echo substr($s['date_signalement'],0,10);?></div>
      </div>
      <span class="pill <?php echo $sc[$s['statut']]??'pill-amber';?>"><?php echo htmlspecialchars($s['statut']);?></span>
    </div>
    <?php endforeach; endif;?>
  </div>

  <!-- CTA style index.html -->
  <div class="cta-section">
    <h2>Prêt à gérer la plateforme ?</h2>
    <p>Toutes les données de VoyageVista à portée de main.</p>
    <a href="statistiques.php" class="btn-primary">📊 Voir les statistiques complètes</a>
  </div>

</div>

<footer>© 2026 VoyageVista — Explore, swipe, travel together.</footer>
</body>
</html>