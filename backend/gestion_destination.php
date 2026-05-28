<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';

$message = ""; $error = "";
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dest'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) { $error = "Action non autorisée."; }
    else {
        $nom       = trim($_POST['nom'] ?? '');
        $desc      = trim($_POST['description'] ?? '');
        $prix      = (float)($_POST['prix'] ?? 0);
        $region    = trim($_POST['region'] ?? '');
        $categorie = trim($_POST['categorie'] ?? '');
        $budget    = trim($_POST['budget'] ?? '');
        if ($nom === '')    $error .= "Le nom est requis. ";
        if ($prix <= 0)     $error .= "Le prix doit être positif. ";
        if ($region === '') $error .= "La région est requise. ";
        if ($error === '') {
            $pdo->prepare("INSERT INTO destinations (nom,description,prix_base,region,categorie,budget) VALUES (?,?,?,?,?,?)")->execute([$nom,$desc,$prix,$region,$categorie,$budget]);
            $message = "Destination « $nom » ajoutée.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_dest'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) { $error = "Action non autorisée."; }
    else {
        $id=$id=(int)$_POST['id']; $nom=trim($_POST['nom']??''); $prix=(float)($_POST['prix']??0);
        $desc=trim($_POST['description']??''); $region=trim($_POST['region']??'');
        $categorie=trim($_POST['categorie']??''); $budget=trim($_POST['budget']??'');
        if ($nom===''||$prix<=0) { $error="Nom et prix requis."; }
        else {
            $pdo->prepare("UPDATE destinations SET nom=?,description=?,prix_base=?,region=?,categorie=?,budget=? WHERE id=?")->execute([$nom,$desc,$prix,$region,$categorie,$budget,$id]);
            $message = "Destination mise à jour.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dest'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) { $error = "Action non autorisée."; }
    else { $pdo->prepare("DELETE FROM destinations WHERE id=?")->execute([(int)$_POST['id']]); $message = "Destination supprimée."; }
}

$destinations = $pdo->query("SELECT * FROM destinations ORDER BY nom ASC")->fetchAll();
$edit_dest = null;
if (isset($_GET['edit'])) {
    $s=$pdo->prepare("SELECT * FROM destinations WHERE id=?"); $s->execute([(int)$_GET['edit']]); $edit_dest=$s->fetch();
}
$regions    = ['Europe','Asie','Afrique','Amerique','Oceanie'];
$categories = ['Aventure','Nightlife','Plage','Gastronomie','Culture','Nature','Sport','Detente','Road Trip'];
$budgets    = ['€','€€','€€€'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Destinations - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--bg:#0a0a0f;--bg2:#111118;--card:#1a1a26;--border:rgba(255,255,255,.07);--purple:#7c5cfc;--pink:#f25ca2;--teal:#2dd4bf;--amber:#fbbf24;--red:#f87171;--green:#4ade80;--text:#f0eeff;--muted:#8b8aa8}
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

    .navbar{display:flex;align-items:center;justify-content:space-between;padding:0 2rem;height:64px;background:rgba(10,10,15,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
    .brand img{height:32px}
    .navbar nav{display:flex;gap:.25rem}
    .navbar nav a{font-size:.85rem;font-weight:500;color:var(--muted);text-decoration:none;padding:.4rem .9rem;border-radius:20px;transition:all .2s}
    .navbar nav a:hover{color:var(--text);background:rgba(255,255,255,.06)}
    .navbar nav a.active{color:var(--text);background:rgba(45,212,191,.15);border:1px solid rgba(45,212,191,.3)}
    .nav-right{display:flex;align-items:center;gap:1rem}
    .avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--purple),var(--pink));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700}
    .logout{font-size:.8rem;color:var(--muted);text-decoration:none;padding:.35rem .8rem;border:1px solid var(--border);border-radius:20px;transition:all .2s}
    .logout:hover{color:var(--text)}

    .page-hero{padding:2.5rem 2rem 1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;top:-60px;left:-80px;width:400px;height:300px;background:radial-gradient(ellipse,rgba(45,212,191,.1) 0%,transparent 70%);pointer-events:none}
    .tag{font-family:'Syne',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--teal);background:rgba(45,212,191,.12);border:1px solid rgba(45,212,191,.25);padding:.3rem .8rem;border-radius:20px;display:inline-block;margin-bottom:.75rem}
    .page-hero h1{font-family:'Syne',sans-serif;font-size:1.9rem;font-weight:800;margin-bottom:.4rem}
    .page-hero h1 span{background:linear-gradient(90deg,var(--teal),var(--purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .page-hero p{color:var(--muted);font-size:.9rem}
    .hero-top{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem}

    .page-body{padding:0 2rem 3rem;max-width:1300px}

    .alert{padding:.9rem 1.2rem;border-radius:10px;font-size:.85rem;margin-bottom:1.5rem;border:1px solid}
    .alert-success{background:rgba(74,222,128,.1);border-color:rgba(74,222,128,.3);color:#86efac}
    .alert-error{background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.3);color:#fca5a5}

    .two-col{display:grid;grid-template-columns:360px 1fr;gap:1.5rem;align-items:start}
    @media(max-width:900px){.two-col{grid-template-columns:1fr}}

    .form-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;position:sticky;top:80px}
    .form-head{padding:1.2rem 1.5rem;border-bottom:1px solid var(--border)}
    .form-head h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700}
    .form-body{padding:1.5rem;display:flex;flex-direction:column;gap:1rem}

    label{display:flex;flex-direction:column;gap:.4rem;font-size:.8rem;color:var(--muted);font-weight:500}
    input[type=text],input[type=number],textarea,select.form-select{background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--text);font-family:'DM Sans',sans-serif;font-size:.85rem;padding:.6rem .9rem;border-radius:8px;outline:none;transition:border-color .2s;width:100%}
    input:focus,textarea:focus,select.form-select:focus{border-color:rgba(45,212,191,.5)}
    input::placeholder,textarea::placeholder{color:var(--muted)}
    textarea{resize:vertical;min-height:80px}

    .btn-submit{background:linear-gradient(135deg,var(--teal),var(--purple));color:#fff;border:none;padding:.7rem 1.4rem;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;transition:opacity .2s;width:100%}
    .btn-submit:hover{opacity:.88}
    .btn-cancel{display:block;text-align:center;color:var(--muted);font-size:.8rem;text-decoration:none;margin-top:.5rem;transition:color .2s}
    .btn-cancel:hover{color:var(--text)}

    .section-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden}
    .section-head{display:flex;align-items:center;justify-content:space-between;padding:1.2rem 1.5rem;border-bottom:1px solid var(--border)}
    .section-head h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700}
    .dest-count{font-size:.78rem;color:var(--muted)}

    .data-table{width:100%;border-collapse:collapse}
    .data-table th{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:.75rem 1.2rem;text-align:left;border-bottom:1px solid var(--border)}
    .data-table td{padding:.85rem 1.2rem;font-size:.83rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
    .data-table tr:last-child td{border-bottom:none}
    .data-table tr:hover td{background:rgba(255,255,255,.02)}

    .dest-name{font-weight:500}
    .price-val{font-family:'Syne',sans-serif;font-weight:700;color:var(--green)}

    .pill{display:inline-block;padding:.2rem .65rem;border-radius:20px;font-size:.72rem;font-weight:600}
    .pill-teal{background:rgba(45,212,191,.15);color:#5eead4;border:1px solid rgba(45,212,191,.25)}
    .pill-purple{background:rgba(124,92,252,.15);color:#a78bfa;border:1px solid rgba(124,92,252,.25)}
    .pill-amber{background:rgba(251,191,36,.15);color:#fcd34d;border:1px solid rgba(251,191,36,.25)}
    .pill-pink{background:rgba(242,92,162,.15);color:#f9a8d4;border:1px solid rgba(242,92,162,.25)}
    .pill-green{background:rgba(74,222,128,.15);color:#86efac;border:1px solid rgba(74,222,128,.25)}

    .btn-edit{color:var(--teal);font-size:.78rem;text-decoration:none;font-weight:500;padding:.25rem .6rem;border-radius:6px;border:1px solid rgba(45,212,191,.25);transition:all .2s}
    .btn-edit:hover{background:rgba(45,212,191,.1)}
    .btn-del{background:transparent;border:1px solid rgba(248,113,113,.25);color:var(--red);font-family:'DM Sans',sans-serif;font-size:.75rem;padding:.25rem .6rem;border-radius:6px;cursor:pointer;transition:all .2s}
    .btn-del:hover{background:rgba(248,113,113,.1)}

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
    <a href="gestion_destination.php" class="active">Destinations</a>
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
      <p class="tag">ADMIN • CATALOGUE • DESTINATIONS</p>
      <h1>Gestion des <span>Destinations</span></h1>
      <p>Ajoutez, modifiez et gérez le catalogue de voyages</p>
    </div>
  </div>
</section>

<div class="page-body">

  <?php if($message):?><div class="alert alert-success">✅ <?php echo htmlspecialchars($message);?></div><?php endif;?>
  <?php if($error):?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error);?></div><?php endif;?>

  <div class="two-col">

    <div class="form-card">
      <div class="form-head">
        <h2><?php echo $edit_dest ? '✏️ Modifier' : '➕ Ajouter';?> une destination</h2>
      </div>
      <div class="form-body">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
          <?php if($edit_dest):?><input type="hidden" name="id" value="<?php echo (int)$edit_dest['id'];?>"><?php endif;?>

          <label>Nom *
            <input type="text" name="nom" required placeholder="ex: Tokyo" value="<?php echo htmlspecialchars($edit_dest['nom']??'');?>">
          </label>
          <label>Description
            <textarea name="description" placeholder="Décrivez cette destination..."><?php echo htmlspecialchars($edit_dest['description']??'');?></textarea>
          </label>
          <label>Prix de base (€) *
            <input type="number" name="prix" min="1" step="0.01" required placeholder="ex: 850" value="<?php echo htmlspecialchars($edit_dest['prix_base']??'');?>">
          </label>
          <label>Région *
            <select name="region" class="form-select" required>
              <option value="">— Choisir —</option>
              <?php foreach($regions as $r):?>
              <option value="<?php echo $r;?>" <?php echo ($edit_dest['region']??'')===$r?'selected':'';?>><?php echo $r;?></option>
              <?php endforeach;?>
            </select>
          </label>
          <label>Catégorie
            <select name="categorie" class="form-select">
              <option value="">— Choisir —</option>
              <?php foreach($categories as $c):?>
              <option value="<?php echo $c;?>" <?php echo ($edit_dest['categorie']??'')===$c?'selected':'';?>><?php echo $c;?></option>
              <?php endforeach;?>
            </select>
          </label>
          <label>Budget
            <select name="budget" class="form-select">
              <option value="">— Choisir —</option>
              <?php foreach($budgets as $b):?>
              <option value="<?php echo $b;?>" <?php echo ($edit_dest['budget']??'')===$b?'selected':'';?>><?php echo $b;?></option>
              <?php endforeach;?>
            </select>
          </label>

          <button type="submit" name="<?php echo $edit_dest?'edit_dest':'add_dest';?>" class="btn-submit">
            <?php echo $edit_dest?'Enregistrer les modifications':'Ajouter la destination';?>
          </button>
          <?php if($edit_dest):?><a href="gestion_destination.php" class="btn-cancel">Annuler</a><?php endif;?>
        </form>
      </div>
    </div>

    <div class="section-card">
      <div class="section-head">
        <h2>🗺️ Catalogue</h2>
        <span class="dest-count"><?php echo count($destinations);?> destination<?php echo count($destinations)>1?'s':'';?></span>
      </div>
      <?php if(empty($destinations)):?>
        <div class="empty-state">Aucune destination. Ajoutez-en une avec le formulaire.</div>
      <?php else:?>
      <table class="data-table">
        <thead><tr><th>Nom</th><th>Région</th><th>Catégorie</th><th>Budget</th><th>Prix</th><th>Actions</th></tr></thead>
        <tbody>
          <?php
          $rc=['Europe'=>'pill-teal','Asie'=>'pill-purple','Afrique'=>'pill-amber','Amerique'=>'pill-pink','Oceanie'=>'pill-green'];
          foreach($destinations as $d):
            $rc2=$rc[$d['region']]??'pill-teal'; ?>
          <tr>
            <td class="dest-name"><?php echo htmlspecialchars($d['nom']);?></td>
            <td><?php if($d['region']):?><span class="pill <?php echo $rc2;?>"><?php echo htmlspecialchars($d['region']);?></span><?php else:?>—<?php endif;?></td>
            <td style="color:var(--muted);font-size:.8rem"><?php echo htmlspecialchars($d['categorie']??'—');?></td>
            <td style="color:var(--amber)"><?php echo htmlspecialchars($d['budget']??'—');?></td>
            <td class="price-val"><?php echo number_format((float)($d['prix_base']??0),0,',',' ');?>€</td>
            <td style="display:flex;gap:.5rem;flex-wrap:wrap">
              <a href="?edit=<?php echo (int)$d['id'];?>" class="btn-edit">Modifier</a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer <?php echo htmlspecialchars($d['nom']);?> ?')">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
                <input type="hidden" name="id" value="<?php echo (int)$d['id'];?>">
                <button type="submit" name="delete_dest" class="btn-del">Supprimer</button>
              </form>
            </td>
          </tr>
          <?php endforeach;?>
        </tbody>
      </table>
      <?php endif;?>
    </div>

  </div>
</div>

<footer>© 2026 VoyageVista — Admin Dashboard</footer>
</body>
</html>