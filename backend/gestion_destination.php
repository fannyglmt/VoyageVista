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
    <a href="gestion_destination.php" class="active">Destinations</a>
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
        <thead><tr><th>Destination</th><th>Région</th><th>Catégorie</th><th>Budget</th><th>Prix</th><th>Actions</th></tr></thead>
        <tbody>
          <?php
          $rc=['Europe'=>'pill-teal','Asie'=>'pill-purple','Afrique'=>'pill-amber','Amerique'=>'pill-pink','Oceanie'=>'pill-green'];

          // Mapping nom → image
          $destImages = [
            'Bali'        => '../frontend/assets/images/bali.png',
            'Algarve'     => '../frontend/assets/images/algarve.png',
            'Barcelone'   => '../frontend/assets/images/barcelone.png',
            'Chamonix'    => '../frontend/assets/images/chamonix.png',
            'Costa Rica'  => '../frontend/assets/images/costarica.png',
            'Ibiza'       => '../frontend/assets/images/ibiza.png',
            'Santorin'    => '../frontend/assets/images/santorin.png',
            'Tokyo'       => '../frontend/assets/images/food-tour.png',
            'Maroc'       => '../frontend/assets/images/diner-marocain.png',
            'Maldives'    => '../frontend/assets/images/boat.png',
          ];

          foreach($destinations as $d):
            $rc2 = $rc[$d['region']] ?? 'pill-teal';
            // Chercher image par nom exact, sinon image par défaut
            $img = null;
            foreach($destImages as $key => $path) {
              if (stripos($d['nom'], $key) !== false) { $img = $path; break; }
            }
            if (!$img) $img = '../frontend/assets/images/hebergement-bg.jpg';
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <img src="<?php echo $img;?>" alt="<?php echo htmlspecialchars($d['nom']);?>"
                     style="width:44px;height:44px;object-fit:cover;border-radius:8px;flex-shrink:0;border:1px solid #e5e7eb">
                <span class="dest-name" style="font-weight:600;font-size:.85rem"><?php echo htmlspecialchars($d['nom']);?></span>
              </div>
            </td>
            <td><?php if($d['region']):?><span class="pill <?php echo $rc2;?>"><?php echo htmlspecialchars($d['region']);?></span><?php else:?>—<?php endif;?></td>
            <td style="color:#6b7280;font-size:.8rem"><?php echo htmlspecialchars($d['categorie']??'—');?></td>
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