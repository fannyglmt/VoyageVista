<?php
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';

$message=""; $error="";
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_statut'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']??'')) { $error="Action non autorisée."; }
    else {
        $statuts_valides=['ouvert','en_cours','resolu','rejete'];
        $statut=$_POST['statut']??''; $id=(int)$_POST['id'];
        if (!in_array($statut,$statuts_valides)) { $error="Statut invalide."; }
        else { $pdo->prepare("UPDATE signalements SET statut=? WHERE id=?")->execute([$statut,$id]); $message="Signalement #$id mis à jour."; }
    }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_report'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']??'')) { $error="Action non autorisée."; }
    else { $pdo->prepare("DELETE FROM signalements WHERE id=?")->execute([(int)$_POST['id']]); $message="Signalement supprimé."; }
}

$filtre=$_GET['statut']??'all';
$query="SELECT s.*, u.username FROM signalements s LEFT JOIN utilisateurs u ON s.user_id=u.id";
$params=[];
if ($filtre!=='all') { $query.=" WHERE s.statut=?"; $params[]=$filtre; }
$query.=" ORDER BY s.date_signalement DESC";
$stmt=$pdo->prepare($query); $stmt->execute($params); $reports=$stmt->fetchAll();
$counts_raw=$pdo->query("SELECT statut, COUNT(*) as nb FROM signalements GROUP BY statut")->fetchAll();
$counts=[]; foreach($counts_raw as $c) $counts[$c['statut']]=$c['nb'];
$total_count=array_sum($counts);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signalements - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin_style.css">
  <style>
    .sig-layout{display:grid;grid-template-columns:360px 1fr;min-height:calc(100vh - 105px)}
    @media(max-width:900px){.sig-layout{grid-template-columns:1fr}}
    .sig-panel-left{position:relative;background:radial-gradient(circle at 20% 80%,rgba(243,178,125,.45),transparent 50%),radial-gradient(circle at 80% 20%,rgba(230,75,93,.4),transparent 45%),linear-gradient(135deg,#4a68a6 0%,#79a9df 50%,#f3b27d 100%);display:flex;align-items:center;justify-content:center;overflow:hidden;padding:60px 44px}
    .sig-panel-left::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;border:2px solid rgba(255,255,255,.12);top:-60px;right:-60px;animation:floatCircle 6s ease-in-out infinite}
    .sig-panel-left::after{content:'';position:absolute;width:180px;height:180px;border-radius:50%;border:2px solid rgba(255,255,255,.10);bottom:-50px;left:-50px;animation:floatCircle 8s ease-in-out infinite reverse}
    .panel-overlay{position:absolute;inset:0;background:radial-gradient(circle at 60% 40%,rgba(255,255,255,.08),transparent 60%);pointer-events:none}
    .panel-content{position:relative;z-index:2;color:#fff;max-width:300px}
    .panel-tag{color:rgba(255,255,255,.75);font-weight:800;letter-spacing:3px;font-size:12px;margin-bottom:18px;display:block}
    .panel-content h2{font-size:40px;line-height:1.1;margin-bottom:14px;font-family:'Syne',sans-serif;font-weight:800}
    .panel-content p{font-size:15px;line-height:1.7;color:rgba(255,255,255,.88);margin-bottom:26px}
    .panel-bubbles{display:flex;flex-wrap:wrap;gap:9px;margin-bottom:26px}
    .bubble{background:rgba(255,255,255,.18);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.25);padding:8px 14px;border-radius:30px;font-weight:700;font-size:12px;transition:.3s;animation:fadeUp .9s ease both}
    .bubble:nth-child(1){animation-delay:.1s}.bubble:nth-child(2){animation-delay:.2s}.bubble:nth-child(3){animation-delay:.3s}.bubble:nth-child(4){animation-delay:.4s}
    .bubble:hover{background:rgba(255,255,255,.3);transform:translateY(-4px)}
    .panel-stats{display:grid;grid-template-columns:1fr 1fr;gap:11px}
    .panel-stat{background:rgba(255,255,255,.15);border-radius:14px;padding:13px;text-align:center}
    .panel-stat-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;display:block}
    .panel-stat-lbl{font-size:9px;color:rgba(255,255,255,.75);font-weight:600;letter-spacing:.05em}

    .sig-panel-right{background:#f7fbff;overflow-y:auto;padding:50px 50px 80px}
    @media(max-width:900px){.sig-panel-right{padding:30px 20px 60px}}

    .section-title-h{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:var(--blue);margin-bottom:6px}
    .section-sub{font-size:14px;color:var(--muted);margin-bottom:20px}

    .filter-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px}
    .filter-tab{font-size:12px;font-weight:700;padding:7px 14px;border-radius:20px;text-decoration:none;color:var(--muted);border:1.5px solid rgba(121,169,223,.3);transition:.2s;background:#fff}
    .filter-tab:hover{color:var(--blue);border-color:var(--blue-light);background:#eaf4ff}
    .filter-tab.active{color:var(--blue);background:#eaf4ff;border-color:var(--blue-light);font-weight:800}

    .reports-list{display:flex;flex-direction:column;gap:12px}
    .report-card{background:#fff;border-radius:18px;padding:18px 20px;box-shadow:0 6px 18px rgba(69,139,202,.08);display:flex;align-items:center;gap:14px;transition:.2s}
    .report-card:hover{box-shadow:0 10px 28px rgba(69,139,202,.14);transform:translateY(-2px)}
    .rdot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
    .d-red{background:#e64b5d;box-shadow:0 0 8px rgba(230,75,93,.5)}
    .d-am{background:#f39b5f;box-shadow:0 0 8px rgba(243,155,95,.5)}
    .d-gr{background:#57c5b6}
    .d-gray{background:#a8c0d6}
    .report-main{flex:1}
    .report-raison{font-size:14px;font-weight:700;color:var(--text);margin-bottom:3px}
    .report-meta{font-size:11px;color:#8aabb8}
    .report-desc{font-size:12px;color:var(--muted);margin-top:4px;font-style:italic}
    .status-select{background:#fff;border:1.5px solid rgba(121,169,223,.3);color:var(--text);font-family:'DM Sans',sans-serif;font-size:12px;font-weight:600;padding:6px 10px;border-radius:12px;cursor:pointer;outline:none;transition:.2s}
    .status-select:hover{border-color:var(--blue-light)}
    .btn-del-sm{background:#fff0f2;border:1.5px solid #ffc5cb;color:#e64b5d;font-family:'DM Sans',sans-serif;font-size:11px;font-weight:700;padding:6px 10px;border-radius:12px;cursor:pointer;transition:.2s}
    .btn-del-sm:hover{background:#ffe5e9}

    .alert-s{background:#eafff4;color:#1e7e50;border:1.5px solid #b7f0d4;padding:14px 20px;border-radius:18px;font-weight:700;font-size:14px;margin-bottom:22px;animation:fadeUp .4s ease both}
    .alert-e{background:#fff0f2;color:#c0392b;border:1.5px solid #f5c6cb;padding:14px 20px;border-radius:18px;font-weight:700;font-size:14px;margin-bottom:22px;animation:fadeUp .4s ease both}
    .empty-state{text-align:center;padding:60px;color:#8aabb8;font-size:16px}

    @keyframes floatCircle{0%,100%{transform:translate(0,0)}50%{transform:translate(15px,-15px)}}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
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
  <div class="nav-icons"><span class="heart-icon">♥</span><span>🔔</span><a href="logout.php">👤</a></div>
</header>

<div class="sig-layout">

  <!-- PANNEAU GAUCHE -->
  <div class="sig-panel-left">
    <div class="panel-overlay"></div>
    <div class="panel-content">
      <span class="panel-tag">MODÉRATION • SÉCURITÉ</span>
      <h2>Gestion des<br>Signalements 🚩</h2>
      <p>Traitez et modérez les rapports soumis par les voyageurs de la plateforme.</p>
      <div class="panel-bubbles">
        <div class="bubble">🔴 Ouverts</div>
        <div class="bubble">🟡 En cours</div>
        <div class="bubble">🟢 Résolus</div>
        <div class="bubble">⚫ Rejetés</div>
      </div>
      <div class="panel-stats">
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $total_count;?></span><span class="panel-stat-lbl">TOTAL</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $counts['ouvert']??0;?></span><span class="panel-stat-lbl">OUVERTS</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $counts['en_cours']??0;?></span><span class="panel-stat-lbl">EN COURS</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $counts['resolu']??0;?></span><span class="panel-stat-lbl">RÉSOLUS</span></div>
      </div>
    </div>
  </div>

  <!-- PANNEAU DROIT -->
  <div class="sig-panel-right">

    <?php if($message):?><div class="alert-s">✅ <?php echo htmlspecialchars($message);?></div><?php endif;?>
    <?php if($error):?><div class="alert-e">⚠️ <?php echo htmlspecialchars($error);?></div><?php endif;?>

    <p class="section-title-h">🚩 Signalements</p>
    <p class="section-sub">Gérez et traitez les signalements de la communauté</p>

    <!-- FILTRES -->
    <div class="filter-tabs">
      <?php $tabs=['all'=>'Tous ('.$total_count.')','ouvert'=>'🔴 Ouverts ('.($counts['ouvert']??0).')','en_cours'=>'🟡 En cours ('.($counts['en_cours']??0).')','resolu'=>'🟢 Résolus ('.($counts['resolu']??0).')','rejete'=>'⚫ Rejetés ('.($counts['rejete']??0).')'];
      foreach($tabs as $val=>$label):?>
      <a href="?statut=<?php echo $val;?>" class="filter-tab <?php echo $filtre===$val?'active':'';?>"><?php echo $label;?></a>
      <?php endforeach;?>
    </div>

    <!-- LISTE -->
    <?php if(empty($reports)):?>
      <div class="empty-state">Aucun signalement pour ce filtre. 🎉</div>
    <?php else:?>
    <div class="reports-list">
      <?php foreach($reports as $r):
        $dc=['ouvert'=>'d-red','en_cours'=>'d-am','resolu'=>'d-gr','rejete'=>'d-gray'];
        $dc2=$dc[$r['statut']]??'d-gray'; ?>
      <div class="report-card">
        <div class="rdot <?php echo $dc2;?>"></div>
        <div class="report-main">
          <div class="report-raison"><?php echo htmlspecialchars($r['raison']);?></div>
          <div class="report-meta">
            par <strong><?php echo htmlspecialchars($r['username']??'Inconnu');?></strong>
            <?php if(!empty($r['cible_type'])):?> · sur <?php echo htmlspecialchars($r['cible_type']);?> #<?php echo (int)$r['cible_id'];?><?php endif;?>
            · <?php echo substr($r['date_signalement'],0,10);?>
          </div>
          <?php if(!empty($r['description'])):?>
          <div class="report-desc"><?php echo htmlspecialchars(substr($r['description'],0,80)).(strlen($r['description'])>80?'…':'');?></div>
          <?php endif;?>
        </div>
        <!-- Changement statut -->
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
        <!-- Suppression -->
        <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce signalement ?')">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
          <input type="hidden" name="id" value="<?php echo (int)$r['id'];?>">
          <button type="submit" name="delete_report" class="btn-del-sm">🗑️</button>
        </form>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>

  </div>
</div>

<footer style="text-align:center;padding:24px;background:#fff;color:var(--muted);font-size:13px;border-top:1px solid rgba(121,169,223,.15)">© 2026 VoyageVista — Explore, swipe, travel together.</footer>
</body>
</html>