<?php
// =============================================
// Gestion des Signalements - VoyageVista
// =============================================
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'configuration.php';

$message = "";
$error   = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_statut'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $statuts_valides = ['ouvert','en_cours','resolu','rejete'];
        $statut = $_POST['statut'] ?? '';
        $id     = (int)$_POST['id'];
        if (!in_array($statut, $statuts_valides)) {
            $error = "Statut invalide.";
        } else {
            $stmt = $pdo->prepare("UPDATE signalements SET statut=? WHERE id=?");
            $stmt->execute([$statut, $id]);
            $message = "Signalement #$id mis à jour.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $pdo->prepare("DELETE FROM signalements WHERE id=?")->execute([(int)$_POST['id']]);
        $message = "Signalement supprimé.";
    }
}

$filtre = $_GET['statut'] ?? 'all';
$query  = "SELECT s.*, u.username FROM signalements s LEFT JOIN utilisateurs u ON s.user_id=u.id";
$params = [];
if ($filtre !== 'all') { $query .= " WHERE s.statut=?"; $params[] = $filtre; }
$query .= " ORDER BY s.date_signalement DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$counts_raw = $pdo->query("SELECT statut, COUNT(*) as nb FROM signalements GROUP BY statut")->fetchAll();
$counts = [];
foreach ($counts_raw as $c) $counts[$c['statut']] = $c['nb'];
$total_count = array_sum($counts);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signalements - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--bg:#0a0a0f;--card:#1a1a26;--border:rgba(255,255,255,.07);--purple:#7c5cfc;--pink:#f25ca2;--teal:#2dd4bf;--amber:#fbbf24;--red:#f87171;--green:#4ade80;--text:#f0eeff;--muted:#8b8aa8}
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

    .navbar{display:flex;align-items:center;justify-content:space-between;padding:0 2rem;height:64px;background:rgba(10,10,15,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
    .brand img{height:32px}
    .navbar nav{display:flex;gap:.25rem}
    .navbar nav a{font-size:.85rem;font-weight:500;color:var(--muted);text-decoration:none;padding:.4rem .9rem;border-radius:20px;transition:all .2s}
    .navbar nav a:hover{color:var(--text);background:rgba(255,255,255,.06)}
    .navbar nav a.active{color:var(--text);background:rgba(124,92,252,.15);border:1px solid rgba(124,92,252,.3)}
    .nav-right{display:flex;align-items:center;gap:1rem}
    .avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--purple),var(--pink));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700}
    .logout{font-size:.8rem;color:var(--muted);text-decoration:none;padding:.35rem .8rem;border:1px solid var(--border);border-radius:20px;transition:all .2s}
    .logout:hover{color:var(--text)}

    .page-hero{padding:2.5rem 2rem 1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;top:-60px;left:-80px;width:400px;height:300px;background:radial-gradient(ellipse,rgba(248,113,113,.1) 0%,transparent 70%);pointer-events:none}
    .tag{font-family:'Syne',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--red);background:rgba(248,113,113,.12);border:1px solid rgba(248,113,113,.25);padding:.3rem .8rem;border-radius:20px;display:inline-block;margin-bottom:.75rem}
    .page-hero h1{font-family:'Syne',sans-serif;font-size:1.9rem;font-weight:800;margin-bottom:.4rem}
    .page-hero h1 span{background:linear-gradient(90deg,var(--red),var(--amber));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .page-hero p{color:var(--muted);font-size:.9rem}
    .hero-top{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem}

    .page-body{padding:0 2rem 3rem;max-width:1300px}

    .alert{padding:.9rem 1.2rem;border-radius:10px;font-size:.85rem;margin-bottom:1.5rem;border:1px solid}
    .alert-success{background:rgba(74,222,128,.1);border-color:rgba(74,222,128,.3);color:#86efac}
    .alert-error{background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.3);color:#fca5a5}

    .kpi-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
    .kpi-mini{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.3rem;text-align:center;transition:transform .2s}
    .kpi-mini:hover{transform:translateY(-2px)}
    .kpi-mini-val{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;line-height:1;margin-bottom:.3rem}
    .kpi-mini-label{font-size:.75rem;color:var(--muted)}

    .filter-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
    .filter-tab{font-size:.82rem;font-weight:500;padding:.45rem 1rem;border-radius:20px;text-decoration:none;color:var(--muted);border:1px solid var(--border);transition:all .2s}
    .filter-tab:hover{color:var(--text);border-color:rgba(255,255,255,.2)}
    .filter-tab.active{color:var(--text);background:rgba(124,92,252,.15);border-color:rgba(124,92,252,.35)}

    .section-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden}
    .section-head{display:flex;align-items:center;justify-content:space-between;padding:1.2rem 1.5rem;border-bottom:1px solid var(--border)}
    .section-head h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700}

    .report-row{display:grid;grid-template-columns:40px 1fr auto auto auto;align-items:center;gap:1rem;padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.04);transition:background .15s}
    .report-row:last-child{border-bottom:none}
    .report-row:hover{background:rgba(255,255,255,.02)}
    .report-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;margin:auto}
    .dot-red{background:var(--red);box-shadow:0 0 8px rgba(248,113,113,.6)}
    .dot-amber{background:var(--amber);box-shadow:0 0 8px rgba(251,191,36,.6)}
    .dot-green{background:var(--green)}
    .dot-gray{background:var(--muted)}
    .report-main{}
    .report-raison{font-size:.85rem;font-weight:500;margin-bottom:.2rem}
    .report-meta{font-size:.73rem;color:var(--muted)}
    .report-id{font-size:.72rem;color:var(--muted);font-family:'Syne',sans-serif}

    .pill{display:inline-block;padding:.2rem .65rem;border-radius:20px;font-size:.72rem;font-weight:600}
    .pill-purple{background:rgba(124,92,252,.15);color:#a78bfa;border:1px solid rgba(124,92,252,.25)}
    .pill-teal{background:rgba(45,212,191,.15);color:#5eead4;border:1px solid rgba(45,212,191,.25)}
    .pill-amber{background:rgba(251,191,36,.15);color:#fcd34d;border:1px solid rgba(251,191,36,.25)}
    .pill-red{background:rgba(248,113,113,.15);color:#fca5a5;border:1px solid rgba(248,113,113,.25)}
    .pill-green{background:rgba(74,222,128,.15);color:#86efac;border:1px solid rgba(74,222,128,.25)}

    .status-select{background:transparent;border:1px solid var(--border);color:var(--text);font-family:'DM Sans',sans-serif;font-size:.78rem;padding:.3rem .6rem;border-radius:8px;cursor:pointer;outline:none;transition:border-color .2s}
    .status-select:hover{border-color:rgba(255,255,255,.2)}

    .btn-delete{background:transparent;border:1px solid rgba(248,113,113,.3);color:var(--red);font-family:'DM Sans',sans-serif;font-size:.75rem;padding:.3rem .7rem;border-radius:8px;cursor:pointer;transition:all .2s}
    .btn-delete:hover{background:rgba(248,113,113,.1)}

    .empty-state{padding:3rem;text-align:center;color:var(--muted);font-size:.9rem}

    footer{text-align:center;padding:2rem;color:var(--muted);font-size:.78rem;border-top:1px solid var(--border);margin-top:2rem}
  </style>
</head>
<body>

<header class="navbar">
  <div class="brand"><img src="../frontend/assets/images/logo-voyagevista.png" alt="VoyageVista"></div>
  <nav>
    <a href="dashboard_admin.php">Dashboard</a>
    <a href="gestion_utilisateur.php">Utilisateurs</a>
    <a href="gestion_destination.php">Destinations</a>
    <a href="gestion_signalement.php" class="active">Signalements</a>
    <a href="statistiques.php">Stats</a>
  </nav>
  <div class="nav-right">
    <div class="avatar"><?php echo strtoupper(substr($_SESSION['username']??'AD',0,2));?></div>
    <a href="logout.php" class="logout">Déconnexion</a>
  </div>
</header>

<section class="page-hero">
  <div class="hero-top">
    <div>
      <p class="tag">MODÉRATION • SÉCURITÉ</p>
      <h1>Gestion des <span>Signalements</span></h1>
      <p>Traitez les rapports soumis par les voyageurs</p>
    </div>
  </div>
</section>

<div class="page-body">

  <?php if($message):?><div class="alert alert-success">✅ <?php echo htmlspecialchars($message);?></div><?php endif;?>
  <?php if($error):?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error);?></div><?php endif;?>

  <div class="kpi-row">
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--text)"><?php echo $total_count;?></div>
      <div class="kpi-mini-label">Total</div>
    </div>
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--red)"><?php echo $counts['ouvert']??0;?></div>
      <div class="kpi-mini-label">Ouverts</div>
    </div>
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--amber)"><?php echo $counts['en_cours']??0;?></div>
      <div class="kpi-mini-label">En cours</div>
    </div>
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--green)"><?php echo $counts['resolu']??0;?></div>
      <div class="kpi-mini-label">Résolus</div>
    </div>
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--muted)"><?php echo $counts['rejete']??0;?></div>
      <div class="kpi-mini-label">Rejetés</div>
    </div>
  </div>

  <div class="filter-tabs">
    <?php
      $tabs=['all'=>'Tous ('.$total_count.')','ouvert'=>'🔴 Ouverts ('.($counts['ouvert']??0).')','en_cours'=>'🟡 En cours ('.($counts['en_cours']??0).')','resolu'=>'🟢 Résolus ('.($counts['resolu']??0).')','rejete'=>'⚫ Rejetés ('.($counts['rejete']??0).')'];
      foreach($tabs as $val=>$label):?>
    <a href="?statut=<?php echo $val;?>" class="filter-tab <?php echo $filtre===$val?'active':'';?>"><?php echo $label;?></a>
    <?php endforeach;?>
  </div>

  <div class="section-card">
    <div class="section-head">
      <h2>🚩 <?php echo count($reports);?> signalement<?php echo count($reports)>1?'s':'';?></h2>
    </div>

    <?php if(empty($reports)):?>
      <div class="empty-state">Aucun signalement pour ce filtre.</div>
    <?php else: foreach($reports as $r):
      $dc=['ouvert'=>'dot-red','en_cours'=>'dot-amber','resolu'=>'dot-green','rejete'=>'dot-gray'];
      $dc2=$dc[$r['statut']]??'dot-gray'; ?>
    <div class="report-row">
      <div class="report-dot <?php echo $dc2;?>"></div>
      <div class="report-main">
        <div class="report-raison"><?php echo htmlspecialchars($r['raison']);?></div>
        <div class="report-meta">
          par <strong><?php echo htmlspecialchars($r['username']??'Inconnu');?></strong>
          <?php if(!empty($r['cible_type'])):?> · sur <?php echo htmlspecialchars($r['cible_type']);?> #<?php echo (int)$r['cible_id'];?><?php endif;?>
          · <?php echo substr($r['date_signalement'],0,10);?>
          <?php if(!empty($r['description'])):?> · <em><?php echo htmlspecialchars(substr($r['description'],0,60)).(strlen($r['description'])>60?'…':'');?></em><?php endif;?>
        </div>
      </div>
      <span class="report-id">#<?php echo (int)$r['id'];?></span>
      <form method="POST" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
        <input type="hidden" name="id" value="<?php echo (int)$r['id'];?>">
        <select name="statut" class="status-select" onchange="this.form.submit()">
          <?php foreach(['ouvert'=>'Ouvert','en_cours'=>'En cours','resolu'=>'Résolu','rejete'=>'Rejeté'] as $v=>$l):?>
          <option value="<?php echo $v;?>" <?php echo $r['statut']===$v?'selected':'';?>><?php echo $l;?></option>
          <?php endforeach;?>
        </select>
        <button type="submit" name="update_statut" style="display:none"></button>
      </form>
      <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce signalement ?')">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
        <input type="hidden" name="id" value="<?php echo (int)$r['id'];?>">
        <button type="submit" name="delete_report" class="btn-delete">Supprimer</button>
      </form>
    </div>
    <?php endforeach; endif;?>
  </div>

</div>

<footer>© 2026 VoyageVista — Admin Dashboard</footer>
</body>
</html>