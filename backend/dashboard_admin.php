<?php
// =============================================
// Dashboard Administrateur - VoyageVista
// =============================================
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'configuration.php';

$total_users        = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$total_dest         = $pdo->query("SELECT COUNT(*) FROM destinations WHERE est_active = 1")->fetchColumn();
$signalements_ouverts = $pdo->query("SELECT COUNT(*) FROM signalements WHERE statut = 'ouvert'")->fetchColumn();
$total_reservations = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$nouveaux_ce_mois   = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE MONTH(date_inscription)=MONTH(NOW()) AND YEAR(date_inscription)=YEAR(NOW())")->fetchColumn();
$reservations_mois  = $pdo->query("SELECT COUNT(*) FROM reservations WHERE MONTH(date_reservation)=MONTH(NOW()) AND YEAR(date_reservation)=YEAR(NOW())")->fetchColumn();
$chiffre_affaires   = $pdo->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut != 'annulee'")->fetchColumn();
$ca_mois            = $pdo->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut != 'annulee' AND MONTH(date_reservation)=MONTH(NOW()) AND YEAR(date_reservation)=YEAR(NOW())")->fetchColumn();

$derniers_users = $pdo->query("SELECT * FROM utilisateurs ORDER BY date_inscription DESC LIMIT 5")->fetchAll();

$derniers_signalements = $pdo->query("
    SELECT s.*, u.username
    FROM signalements s
    LEFT JOIN utilisateurs u ON s.user_id = u.id
    ORDER BY s.date_signalement DESC LIMIT 5
")->fetchAll();

$top_destinations = $pdo->query("
    SELECT d.nom, COUNT(r.id) AS nb_reservations
    FROM destinations d
    LEFT JOIN reservations r ON r.destination_id = d.id
    GROUP BY d.id, d.nom
    ORDER BY nb_reservations DESC LIMIT 5
")->fetchAll();
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
    :root{
      --bg:#0a0a0f;--card:#1a1a26;--border:rgba(255,255,255,0.07);
      --purple:#7c5cfc;--pink:#f25ca2;--teal:#2dd4bf;
      --amber:#fbbf24;--red:#f87171;--green:#4ade80;
      --text:#f0eeff;--muted:#8b8aa8;--tag-bg:rgba(124,92,252,0.15);
    }
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

    .navbar{display:flex;align-items:center;justify-content:space-between;padding:0 2rem;height:64px;background:rgba(10,10,15,0.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
    .brand img{height:32px}
    .navbar nav{display:flex;gap:.25rem}
    .navbar nav a{font-size:.85rem;font-weight:500;color:var(--muted);text-decoration:none;padding:.4rem .9rem;border-radius:20px;transition:all .2s}
    .navbar nav a:hover{color:var(--text);background:rgba(255,255,255,.06)}
    .navbar nav a.active{color:var(--text);background:var(--tag-bg);border:1px solid rgba(124,92,252,.3)}
    .nav-right{display:flex;align-items:center;gap:1rem}
    .nav-badge{position:relative;font-size:1.1rem;cursor:pointer;opacity:.7;transition:opacity .2s}
    .nav-badge:hover{opacity:1}
    .badge-dot{position:absolute;top:-2px;right:-2px;width:8px;height:8px;background:var(--pink);border-radius:50%;border:2px solid var(--bg)}
    .avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--purple),var(--pink));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;cursor:pointer}
    .logout{font-size:.8rem;color:var(--muted);text-decoration:none;padding:.35rem .8rem;border:1px solid var(--border);border-radius:20px;transition:all .2s}
    .logout:hover{color:var(--text);border-color:rgba(255,255,255,.2)}

    .admin-hero{padding:2.5rem 2rem 1.5rem;position:relative;overflow:hidden}
    .admin-hero::before{content:'';position:absolute;top:-60px;left:-80px;width:400px;height:300px;background:radial-gradient(ellipse,rgba(124,92,252,.12) 0%,transparent 70%);pointer-events:none}
    .hero-top{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem}
    .tag{font-family:'Syne',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--purple);background:var(--tag-bg);border:1px solid rgba(124,92,252,.25);padding:.3rem .8rem;border-radius:20px;display:inline-block;margin-bottom:.75rem}
    .admin-hero h1{font-family:'Syne',sans-serif;font-size:1.9rem;font-weight:800;line-height:1.2;margin-bottom:.4rem}
    .admin-hero h1 span{background:linear-gradient(90deg,var(--purple),var(--pink));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .admin-hero p{color:var(--muted);font-size:.9rem}
    .hero-actions{display:flex;gap:.75rem;flex-wrap:wrap}
    .btn-primary{background:linear-gradient(135deg,var(--purple),var(--pink));color:#fff;border:none;padding:.6rem 1.4rem;border-radius:24px;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;text-decoration:none;transition:opacity .2s,transform .2s;display:inline-flex;align-items:center;gap:.4rem}
    .btn-primary:hover{opacity:.88;transform:translateY(-1px)}
    .btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border);padding:.6rem 1.4rem;border-radius:24px;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;gap:.4rem}
    .btn-ghost:hover{color:var(--text);border-color:rgba(255,255,255,.2)}

    .admin-body{padding:0 2rem 3rem;max-width:1300px}

    .kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:2rem}
    .kpi-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:1.4rem 1.6rem;position:relative;overflow:hidden;transition:transform .2s,border-color .2s}
    .kpi-card:hover{transform:translateY(-3px);border-color:rgba(255,255,255,.14)}
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
    .kpi-label{font-size:.8rem;color:var(--muted);margin-bottom:.5rem}
    .kpi-sub{font-size:.75rem;color:var(--muted)}
    .kpi-sub.up{color:var(--green)}
    .kpi-sub.bad{color:var(--red)}

    .two-col{display:grid;grid-template-columns:1fr 340px;gap:1.5rem;margin-bottom:1.5rem}
    @media(max-width:900px){.two-col{grid-template-columns:1fr}}
    .bottom-row{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
    @media(max-width:700px){.bottom-row{grid-template-columns:1fr}}

    .section-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden}
    .section-head{display:flex;align-items:center;justify-content:space-between;padding:1.2rem 1.5rem;border-bottom:1px solid var(--border)}
    .section-head h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700}
    .see-all{font-size:.78rem;color:var(--purple);text-decoration:none;font-weight:500}
    .see-all:hover{text-decoration:underline}

    .data-table{width:100%;border-collapse:collapse}
    .data-table th{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:.75rem 1.5rem;text-align:left;border-bottom:1px solid var(--border)}
    .data-table td{padding:.9rem 1.5rem;font-size:.85rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
    .data-table tr:last-child td{border-bottom:none}
    .data-table tr:hover td{background:rgba(255,255,255,.02)}
    .user-cell{display:flex;align-items:center;gap:.75rem}
    .user-av{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--purple),var(--pink));display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0}
    .user-name{font-weight:500;font-size:.85rem}
    .user-email{font-size:.75rem;color:var(--muted)}

    .pill{display:inline-block;padding:.2rem .65rem;border-radius:20px;font-size:.72rem;font-weight:600}
    .pill-purple{background:rgba(124,92,252,.15);color:#a78bfa;border:1px solid rgba(124,92,252,.25)}
    .pill-pink{background:rgba(242,92,162,.15);color:#f9a8d4;border:1px solid rgba(242,92,162,.25)}
    .pill-teal{background:rgba(45,212,191,.15);color:#5eead4;border:1px solid rgba(45,212,191,.25)}
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

    .report-item{display:flex;align-items:center;gap:1rem;padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.04)}
    .report-item:last-child{border-bottom:none}
    .report-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .dot-red{background:var(--red);box-shadow:0 0 6px rgba(248,113,113,.6)}
    .dot-amber{background:var(--amber);box-shadow:0 0 6px rgba(251,191,36,.6)}
    .dot-green{background:var(--green)}
    .report-text{flex:1;font-size:.83rem}
    .report-meta{font-size:.72rem;color:var(--muted);margin-top:.15rem}

    .stat-bar-wrap{padding:.8rem 1.5rem}
    .stat-bar-label{display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:.4rem}
    .stat-bar-label span:last-child{color:var(--muted)}
    .stat-bar-track{height:6px;background:rgba(255,255,255,.08);border-radius:3px;overflow:hidden;margin-bottom:.2rem}
    .stat-bar-fill{height:100%;border-radius:3px}

    footer{text-align:center;padding:2rem;color:var(--muted);font-size:.78rem;border-top:1px solid var(--border);margin-top:2rem}
  </style>
</head>
<body>

<header class="navbar">
  <div class="brand"><img src="../frontend/assets/images/logo-voyagevista.png" alt="VoyageVista"></div>
  <nav>
    <a href="dashboard_admin.php" class="active">Dashboard</a>
    <a href="gestion_utilisateur.php">Utilisateurs</a>
    <a href="gestion_destination.php">Destinations</a>
    <a href="gestion_signalement.php">Signalements</a>
    <a href="gestion_notification.php">Notifications</a>
    <a href="statistiques.php">Stats</a>
  </nav>
  <div class="nav-right">
    <span class="nav-badge">🔔<?php if($signalements_ouverts>0): ?><span class="badge-dot"></span><?php endif; ?></span>
    <div class="avatar"><?php echo strtoupper(substr($_SESSION['username']??'AD',0,2)); ?></div>
    <a href="logout.php" class="logout">Déconnexion</a>
  </div>
</header>

<section class="admin-hero">
  <div class="hero-top">
    <div>
      <p class="tag">ADMIN • CONTROL • MANAGE</p>
      <h1>Dashboard <span>Administrateur</span></h1>
      <p>Vue globale sur la plateforme VoyageVista</p>
    </div>
    <div class="hero-actions">
      <a href="gestion_utilisateur.php" class="btn-primary">👥 Utilisateurs</a>
      <a href="gestion_signalement.php" class="btn-ghost">⚠️ Signalements</a>
    </div>
  </div>
</section>

<div class="admin-body">

  <div class="kpi-grid">
    <div class="kpi-card purple">
      <div class="kpi-icon">👤</div>
      <div class="kpi-value"><?php echo (int)$total_users; ?></div>
      <div class="kpi-label">Utilisateurs inscrits</div>
      <div class="kpi-sub up">+<?php echo (int)$nouveaux_ce_mois; ?> ce mois</div>
    </div>
    <div class="kpi-card teal">
      <div class="kpi-icon">🗺️</div>
      <div class="kpi-value"><?php echo (int)$total_dest; ?></div>
      <div class="kpi-label">Destinations actives</div>
      <div class="kpi-sub">Catalogue en ligne</div>
    </div>
    <div class="kpi-card amber">
      <div class="kpi-icon">📅</div>
      <div class="kpi-value"><?php echo (int)$total_reservations; ?></div>
      <div class="kpi-label">Réservations totales</div>
      <div class="kpi-sub up">+<?php echo (int)$reservations_mois; ?> ce mois</div>
    </div>
    <div class="kpi-card red">
      <div class="kpi-icon">⚠️</div>
      <div class="kpi-value"><?php echo (int)$signalements_ouverts; ?></div>
      <div class="kpi-label">Signalements ouverts</div>
      <div class="kpi-sub <?php echo $signalements_ouverts>5?'bad':''; ?>"><?php echo $signalements_ouverts>5?'À traiter rapidement':'Sous contrôle'; ?></div>
    </div>
    <div class="kpi-card green">
      <div class="kpi-icon">💰</div>
      <div class="kpi-value"><?php echo number_format((float)$chiffre_affaires,0,',',' '); ?>€</div>
      <div class="kpi-label">Chiffre d'affaires</div>
      <div class="kpi-sub up">+<?php echo number_format((float)$ca_mois,0,',',' '); ?>€ ce mois</div>
    </div>
  </div>

  <div class="two-col">
    <div class="section-card">
      <div class="section-head">
        <h2>👥 Derniers inscrits</h2>
        <a href="gestion_utilisateur.php" class="see-all">Voir tous →</a>
      </div>
      <table class="data-table">
        <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Inscrit le</th><th>Statut</th></tr></thead>
        <tbody>
          <?php foreach($derniers_users as $u):
            $rc=['admin'=>'pill-pink','prestataire'=>'pill-teal','utilisateur'=>'pill-purple'];
            $c=$rc[$u['role']]??'pill-purple'; ?>
          <tr>
            <td>
              <div class="user-cell">
                <div class="user-av"><?php echo strtoupper(substr($u['username'],0,2)); ?></div>
                <div>
                  <div class="user-name"><?php echo htmlspecialchars($u['username']); ?></div>
                  <div class="user-email"><?php echo htmlspecialchars($u['email']); ?></div>
                </div>
              </div>
            </td>
            <td><span class="pill <?php echo $c; ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
            <td style="color:var(--muted);font-size:.8rem"><?php echo substr($u['date_inscription'],0,10); ?></td>
            <td><span class="pill <?php echo $u['est_actif']?'pill-green':'pill-red'; ?>"><?php echo $u['est_actif']?'Actif':'Banni'; ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="section-card">
      <div class="section-head"><h2>⚡ Actions rapides</h2></div>
      <div class="actions-list">
        <a href="gestion_utilisateur.php" class="action-item">
          <div class="action-icon" style="background:rgba(124,92,252,.15)">👥</div>
          <div><div class="action-label">Gestion utilisateurs</div><div class="action-sub">Rôles, suppression, recherche</div></div>
          <span class="action-arrow">›</span>
        </a>
        <a href="gestion_destination.php" class="action-item">
          <div class="action-icon" style="background:rgba(45,212,191,.15)">🗺️</div>
          <div><div class="action-label">Gestion destinations</div><div class="action-sub">Ajouter, modifier, supprimer</div></div>
          <span class="action-arrow">›</span>
        </a>
        <a href="gestion_signalement.php" class="action-item">
          <div class="action-icon" style="background:rgba(248,113,113,.15)">🚩</div>
          <div><div class="action-label">Signalements</div><div class="action-sub">Traiter les rapports ouverts</div></div>
          <span class="action-arrow">›</span>
        </a>
        <a href="gestion_notification.php" class="action-item">
          <div class="action-icon" style="background:rgba(251,191,36,.15)">🔔</div>
          <div><div class="action-label">Notifications</div><div class="action-sub">Envoyer des messages</div></div>
          <span class="action-arrow">›</span>
        </a>
        <a href="statistiques.php" class="action-item">
          <div class="action-icon" style="background:rgba(74,222,128,.15)">📊</div>
          <div><div class="action-label">Statistiques</div><div class="action-sub">Graphiques & KPI détaillés</div></div>
          <span class="action-arrow">›</span>
        </a>
      </div>
    </div>
  </div>

  <div class="bottom-row">
    <div class="section-card">
      <div class="section-head">
        <h2>🚩 Signalements récents</h2>
        <a href="gestion_signalement.php" class="see-all">Voir tous →</a>
      </div>
      <?php foreach($derniers_signalements as $s):
        $dc=['ouvert'=>'dot-red','en_cours'=>'dot-amber','resolu'=>'dot-green','rejete'=>'dot-green'];
        $dc2=$dc[$s['statut']]??'dot-amber';
        $sc=['ouvert'=>'pill-red','en_cours'=>'pill-amber','resolu'=>'pill-green','rejete'=>'pill-purple'];
        $sc2=$sc[$s['statut']]??'pill-amber'; ?>
      <div class="report-item">
        <div class="report-dot <?php echo $dc2; ?>"></div>
        <div>
          <div class="report-text"><?php echo htmlspecialchars($s['raison']); ?></div>
          <div class="report-meta">par <?php echo htmlspecialchars($s['username']??'Inconnu'); ?> · <?php echo substr($s['date_signalement'],0,10); ?></div>
        </div>
        <span class="pill <?php echo $sc2; ?>"><?php echo htmlspecialchars($s['statut']); ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="section-card">
      <div class="section-head">
        <h2>🔥 Top destinations</h2>
        <a href="gestion_destination.php" class="see-all">Gérer →</a>
      </div>
      <?php
        $emojis=['🌴','🏖️','🗼','🏔️','🌺'];
        $maxR = !empty($top_destinations) ? max(1,(int)$top_destinations[0]['nb_reservations']) : 1;
        foreach($top_destinations as $i=>$d):
          $pct = max(4, round(((int)$d['nb_reservations']/$maxR)*100)); ?>
      <div class="stat-bar-wrap">
        <div class="stat-bar-label">
          <span><?php echo $emojis[$i%count($emojis)].' '.htmlspecialchars($d['nom']); ?></span>
          <span><?php echo (int)$d['nb_reservations']; ?> resas</span>
        </div>
        <div class="stat-bar-track">
          <div class="stat-bar-fill" style="width:<?php echo $pct; ?>%;background:linear-gradient(90deg,var(--purple),var(--pink))"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<footer>© 2026 VoyageVista — Admin Dashboard</footer>
</body>
</html>