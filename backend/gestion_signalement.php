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
  <link rel="stylesheet" href="admin_style.css">
</head>
<body>

<header class="navbar">

  <div class="brand">
    <img src="../frontend/assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
  </div>

  <nav>
    <a href="dashboard_admin.php">Dashboard</a>
    <a href="gestion_utilisateur.php">Utilisateurs</a>
    <a href="gestion_signalement.php" class="active">Signalements</a>
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
      <div class="kpi-mini-val" style="color:#1a1a2e"><?php echo $total_count;?></div>
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
      <div class="kpi-mini-val" style="color:#6b7280"><?php echo $counts['rejete']??0;?></div>
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