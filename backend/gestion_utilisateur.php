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
    .page-hero::before{content:'';position:absolute;top:-60px;left:-80px;width:400px;height:300px;background:radial-gradient(ellipse,rgba(124,92,252,.12) 0%,transparent 70%);pointer-events:none}
    .tag{font-family:'Syne',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--purple);background:rgba(124,92,252,.15);border:1px solid rgba(124,92,252,.25);padding:.3rem .8rem;border-radius:20px;display:inline-block;margin-bottom:.75rem}
    .page-hero h1{font-family:'Syne',sans-serif;font-size:1.9rem;font-weight:800;margin-bottom:.4rem}
    .page-hero h1 span{background:linear-gradient(90deg,var(--purple),var(--pink));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .page-hero p{color:var(--muted);font-size:.9rem}
    .hero-top{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem}

    .page-body{padding:0 2rem 3rem;max-width:1300px}

    .alert{padding:.9rem 1.2rem;border-radius:10px;font-size:.85rem;margin-bottom:1.5rem;border:1px solid}
    .alert-success{background:rgba(74,222,128,.1);border-color:rgba(74,222,128,.3);color:#86efac}
    .alert-error{background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.3);color:#fca5a5}

    .kpi-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem}
    .kpi-mini{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.3rem;text-align:center;transition:transform .2s}
    .kpi-mini:hover{transform:translateY(-2px)}
    .kpi-mini-val{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;line-height:1;margin-bottom:.3rem}
    .kpi-mini-label{font-size:.75rem;color:var(--muted)}

    .toolbar{display:flex;gap:1rem;flex-wrap:wrap;align-items:center;margin-bottom:1.5rem}
    .search-wrap{position:relative;flex:1;min-width:200px}
    .search-wrap input{width:100%;background:var(--card);border:1px solid var(--border);color:var(--text);font-family:'DM Sans',sans-serif;font-size:.85rem;padding:.6rem 1rem .6rem 2.4rem;border-radius:10px;outline:none;transition:border-color .2s}
    .search-wrap input:focus{border-color:rgba(124,92,252,.5)}
    .search-wrap input::placeholder{color:var(--muted)}
    .search-icon{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);font-size:.9rem;pointer-events:none}
    .filter-select{background:var(--card);border:1px solid var(--border);color:var(--text);font-family:'DM Sans',sans-serif;font-size:.83rem;padding:.6rem 1rem;border-radius:10px;outline:none;cursor:pointer}
    .btn-search{background:linear-gradient(135deg,var(--purple),var(--pink));color:#fff;border:none;padding:.6rem 1.2rem;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.83rem;font-weight:500;cursor:pointer;transition:opacity .2s}
    .btn-search:hover{opacity:.88}

    .result-count{font-size:.8rem;color:var(--muted);margin-bottom:1rem}

    .section-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden}
    .data-table{width:100%;border-collapse:collapse}
    .data-table th{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:.75rem 1.5rem;text-align:left;border-bottom:1px solid var(--border)}
    .data-table td{padding:.9rem 1.5rem;font-size:.85rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
    .data-table tr:last-child td{border-bottom:none}
    .data-table tr:hover td{background:rgba(255,255,255,.02)}

    .user-cell{display:flex;align-items:center;gap:.75rem}
    .user-av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--purple),var(--pink));display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0}
    .user-name{font-weight:500;font-size:.85rem}
    .user-email{font-size:.73rem;color:var(--muted)}

    .pill{display:inline-block;padding:.2rem .65rem;border-radius:20px;font-size:.72rem;font-weight:600}
    .pill-purple{background:rgba(124,92,252,.15);color:#a78bfa;border:1px solid rgba(124,92,252,.25)}
    .pill-pink{background:rgba(242,92,162,.15);color:#f9a8d4;border:1px solid rgba(242,92,162,.25)}
    .pill-teal{background:rgba(45,212,191,.15);color:#5eead4;border:1px solid rgba(45,212,191,.25)}
    .pill-green{background:rgba(74,222,128,.15);color:#86efac;border:1px solid rgba(74,222,128,.25)}
    .pill-red{background:rgba(248,113,113,.15);color:#fca5a5;border:1px solid rgba(248,113,113,.25)}

    .role-select{background:transparent;border:1px solid var(--border);color:var(--text);font-family:'DM Sans',sans-serif;font-size:.78rem;padding:.3rem .6rem;border-radius:8px;cursor:pointer;outline:none;transition:border-color .2s}
    .role-select:hover{border-color:rgba(255,255,255,.2)}

    .btn-delete{background:transparent;border:1px solid rgba(248,113,113,.3);color:var(--red);font-family:'DM Sans',sans-serif;font-size:.75rem;padding:.3rem .7rem;border-radius:8px;cursor:pointer;transition:all .2s}
    .btn-delete:hover{background:rgba(248,113,113,.1)}

    .self-label{font-size:.75rem;color:var(--muted);font-style:italic}
    .empty-state{padding:3rem;text-align:center;color:var(--muted);font-size:.9rem}

    footer{text-align:center;padding:2rem;color:var(--muted);font-size:.78rem;border-top:1px solid var(--border);margin-top:2rem}
  </style>
</head>
<body>

<header class="navbar">
  <div class="brand"><img src="../frontend/assets/images/logo-voyagevista.png" alt="VoyageVista"></div>
  <nav>
    <a href="dashboard_admin.php">Dashboard</a>
    <a href="gestion_utilisateur.php" class="active">Utilisateurs</a>
    <a href="gestion_destination.php">Destinations</a>
    <a href="gestion_signalement.php">Signalements</a>
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
      <div class="kpi-mini-val" style="color:var(--text)"><?php echo $total_count;?></div>
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
          <td style="color:var(--muted);font-size:.8rem"><?php echo substr($u['date_inscription'],0,10);?></td>
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