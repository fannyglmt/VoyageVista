<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';

$message = ""; $error = "";
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $id = (int)$_POST['user_id'];
        if ($id === (int)$_SESSION['user_id']) {
            $error = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            $pdo->prepare("DELETE FROM utilisateurs WHERE id=?")->execute([$id]);
            $message = "Utilisateur supprimé.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $roles_valides = ['utilisateur','prestataire','admin'];
        $role = $_POST['role'] ?? '';
        if (!in_array($role, $roles_valides)) { $error = "Rôle invalide."; }
        else {
            $pdo->prepare("UPDATE utilisateurs SET role=? WHERE id=?")->execute([$role, (int)$_POST['user_id']]);
            $message = "Rôle mis à jour.";
        }
    }
}

$search      = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? 'all';
$query  = "SELECT * FROM utilisateurs WHERE 1=1";
$params = [];
if ($search !== '') { $query .= " AND (username LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($role_filter !== 'all') { $query .= " AND role=?"; $params[] = $role_filter; }
$query .= " ORDER BY date_inscription DESC";
$stmt = $pdo->prepare($query); $stmt->execute($params);
$users = $stmt->fetchAll();

$counts_raw = $pdo->query("SELECT role, COUNT(*) as nb FROM utilisateurs GROUP BY role")->fetchAll();
$counts = [];
foreach ($counts_raw as $c) $counts[$c['role']] = $c['nb'];
$total_count = array_sum($counts);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Utilisateurs - VoyageVista</title>
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
    <a href="gestion_utilisateur.php" class="active">Utilisateurs</a>
    <a href="gestion_destination.php">Destinations</a>
    <a href="gestion_signalement.php">Signalements</a>
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
      <p class="tag">ADMIN • USERS • ROLES</p>
      <h1>Gestion des <span>Utilisateurs</span></h1>
      <p>Gérez les comptes, rôles et accès de la plateforme</p>
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
      <div class="kpi-mini-val" style="color:var(--purple)"><?php echo $counts['utilisateur']??0;?></div>
      <div class="kpi-mini-label">Voyageurs</div>
    </div>
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--teal)"><?php echo $counts['prestataire']??0;?></div>
      <div class="kpi-mini-label">Prestataires</div>
    </div>
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--pink)"><?php echo $counts['admin']??0;?></div>
      <div class="kpi-mini-label">Admins</div>
    </div>
  </div>

  <form method="GET" class="toolbar">
    <div class="search-wrap">
      <span class="search-icon">🔎</span>
      <input type="text" name="search" placeholder="Rechercher par nom ou email..." value="<?php echo htmlspecialchars($search);?>">
    </div>
    <select name="role" class="filter-select">
      <option value="all" <?php echo $role_filter==='all'?'selected':'';?>>Tous les rôles</option>
      <option value="utilisateur" <?php echo $role_filter==='utilisateur'?'selected':'';?>>Voyageur</option>
      <option value="prestataire" <?php echo $role_filter==='prestataire'?'selected':'';?>>Prestataire</option>
      <option value="admin" <?php echo $role_filter==='admin'?'selected':'';?>>Admin</option>
    </select>
    <button type="submit" class="btn-search">Filtrer</button>
  </form>

  <p class="result-count"><?php echo count($users);?> utilisateur<?php echo count($users)>1?'s':'';?> trouvé<?php echo count($users)>1?'s':'';?></p>

  <div class="section-card">
    <table class="data-table">
      <thead>
        <tr><th>Utilisateur</th><th>Rôle</th><th>Inscrit le</th><th>Statut</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if(empty($users)):?>
        <tr><td colspan="5"><div class="empty-state">Aucun utilisateur trouvé.</div></td></tr>
        <?php else: foreach($users as $u):
          $rc=['admin'=>'pill-pink','prestataire'=>'pill-teal','utilisateur'=>'pill-purple'];
          $c=$rc[$u['role']]??'pill-purple';
          $isSelf=((int)$u['id']===(int)$_SESSION['user_id']); ?>
        <tr>
          <td>
            <div class="user-cell">
              <div class="user-av"><?php echo strtoupper(substr($u['username'],0,2));?></div>
              <div>
                <div class="user-name"><?php echo htmlspecialchars($u['username']);?><?php if($isSelf):?> <span style="font-size:.7rem;color:var(--purple)">(vous)</span><?php endif;?></div>
                <div class="user-email"><?php echo htmlspecialchars($u['email']);?></div>
              </div>
            </div>
          </td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
              <input type="hidden" name="user_id" value="<?php echo (int)$u['id'];?>">
              <select name="role" class="role-select" onchange="this.form.submit()">
                <?php foreach(['utilisateur'=>'Voyageur','prestataire'=>'Prestataire','admin'=>'Admin'] as $v=>$l):?>
                <option value="<?php echo $v;?>" <?php echo $u['role']===$v?'selected':'';?>><?php echo $l;?></option>
                <?php endforeach;?>
              </select>
              <button type="submit" name="change_role" style="display:none"></button>
            </form>
          </td>
          <td style="color:#6b7280;font-size:.8rem"><?php echo substr($u['date_inscription'],0,10);?></td>
          <td><span class="pill <?php echo isset($u['est_actif'])&&$u['est_actif']?'pill-green':'pill-red';?>"><?php echo isset($u['est_actif'])&&$u['est_actif']?'Actif':'Banni';?></span></td>
          <td>
            <?php if(!$isSelf):?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer <?php echo htmlspecialchars($u['username']);?> ? Cette action est irréversible.')">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
              <input type="hidden" name="user_id" value="<?php echo (int)$u['id'];?>">
              <button type="submit" name="delete_user" class="btn-delete">Supprimer</button>
            </form>
            <?php else:?><span class="self-label">Compte actuel</span><?php endif;?>
          </td>
        </tr>
        <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>

</div>

<footer>© 2026 VoyageVista — Admin Dashboard</footer>
</body>
</html>