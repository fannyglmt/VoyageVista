<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';
session_start();

$user_id = (int)$_SESSION['user_id'];
$message = ""; $error = "";
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── AJOUT ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activite'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $nom      = trim($_POST['nom'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $cat      = trim($_POST['categorie'] ?? '');
        $prix     = (float)($_POST['prix'] ?? 0);
        $duree    = (float)($_POST['duree_heures'] ?? 0);
        $image    = trim($_POST['image_url'] ?? '');
        $destId   = (int)($_POST['destination_id'] ?? 0);

        if ($nom === '')    $error = "Le nom est requis.";
        elseif ($prix <= 0) $error = "Le prix doit être positif.";
        elseif ($destId <= 0) $error = "Sélectionnez une destination.";
        else {
            $pdo->prepare("INSERT INTO activites (nom,description,categorie,prix,duree_heures,image_url,destination_id,prestataire_id,est_actif) VALUES (?,?,?,?,?,?,?,?,1)")
                ->execute([$nom,$desc,$cat,$prix,$duree,$image,$destId,$user_id]);
            // Alimenter aussi la table services pour les disponibilités
            $newId = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO services (prestataire_id,type,ref_id,nom,prix,statut) VALUES (?,?,?,?,?,'actif')")
                ->execute([$user_id,'activite',(int)$newId,$nom,$prix]);
            $message = "Activité « $nom » ajoutée.";
        }
    }
}

// ── MODIFICATION ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_activite'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $id    = (int)$_POST['id'];
        $nom   = trim($_POST['nom'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $cat   = trim($_POST['categorie'] ?? '');
        $prix  = (float)($_POST['prix'] ?? 0);
        $duree = (float)($_POST['duree_heures'] ?? 0);
        $image = trim($_POST['image_url'] ?? '');
        $destId = (int)($_POST['destination_id'] ?? 0);

        if ($nom === '' || $prix <= 0) $error = "Nom et prix requis.";
        else {
            $pdo->prepare("UPDATE activites SET nom=?,description=?,categorie=?,prix=?,duree_heures=?,image_url=?,destination_id=? WHERE id=? AND prestataire_id=?")
                ->execute([$nom,$desc,$cat,$prix,$duree,$image,$destId,$id,$user_id]);
            $message = "Activité mise à jour.";
        }
    }
}

// ── SUPPRESSION ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $deleteId = (int)$_POST['delete_id'];
        // Sécurité : on vérifie que l'activité appartient au prestataire
        $pdo->prepare("DELETE FROM activites WHERE id=? AND prestataire_id=?")
            ->execute([$deleteId, $user_id]);
        $message = "Activité supprimée.";
    }
}

// ── DONNÉES ───────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT a.*, d.nom AS destination_nom FROM activites a JOIN destinations d ON a.destination_id=d.id WHERE a.prestataire_id=? ORDER BY a.date_creation DESC");
$stmt->execute([$user_id]);
$activites = $stmt->fetchAll();

$destinations_list = $pdo->query("SELECT id, nom FROM destinations WHERE est_active=1 ORDER BY nom ASC")->fetchAll();

$totalActivites = count($activites);
$actives   = array_sum(array_column($activites, 'est_actif'));
$totalNotes = array_sum(array_column($activites, 'note_moyenne'));
$noteMoyenne = $totalActivites > 0 ? round($totalNotes / $totalActivites, 1) : 0;
$prixMoyen = $totalActivites > 0 ? round(array_sum(array_column($activites,'prix')) / $totalActivites) : 0;

$edit_a = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM activites WHERE id=? AND prestataire_id=?");
    $s->execute([(int)$_GET['edit'], $user_id]);
    $edit_a = $s->fetch();
}

$actImg = ['Surf'=>'boat.png','Croisière'=>'croisiere-sunset.png','Tour'=>'food-tour.png','Dîner'=>'diner-marocain.png','Randonnée'=>'chamonix.png'];
$categories = ['Surf & Sports nautiques','Randonnée & Aventure','Gastronomie & Food tour','Croisière & Bateau','Culture & Visite','Nightlife & Soirée','Bien-être & Spa','Road Trip'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion Activités - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin_style.css">
  <style>
    .act-layout{display:grid;grid-template-columns:390px 1fr;min-height:calc(100vh - 105px)}
    @media(max-width:900px){.act-layout{grid-template-columns:1fr}}

    /* Panneau gauche vert/teal pour les activités */
    .act-panel-left{
      position:relative;
      background:
        radial-gradient(circle at 20% 80%,rgba(87,197,182,.5),transparent 50%),
        radial-gradient(circle at 80% 20%,rgba(121,169,223,.5),transparent 45%),
        linear-gradient(135deg,#1e6b5e 0%,#2dd4bf 55%,#79a9df 100%);
      display:flex;align-items:center;justify-content:center;overflow:hidden;padding:60px 44px;
    }
    .act-panel-left::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;border:2px solid rgba(255,255,255,.12);top:-60px;right:-60px;animation:floatCircle 6s ease-in-out infinite}
    .act-panel-left::after{content:'';position:absolute;width:180px;height:180px;border-radius:50%;border:2px solid rgba(255,255,255,.10);bottom:-50px;left:-50px;animation:floatCircle 8s ease-in-out infinite reverse}

    .act-panel-right{background:var(--bg);overflow-y:auto;padding:50px 50px 80px}
    @media(max-width:900px){.act-panel-right{padding:30px 20px 60px}}

    /* Tableau activités */
    .act-table{width:100%;border-collapse:collapse;background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 10px 28px var(--shadow)}
    .act-table thead{background:#2dd4bf}
    .act-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:13px 14px;text-align:left;color:#fff}
    .act-table td{padding:11px 14px;font-size:13px;border-bottom:1px solid #edf4fb;vertical-align:middle;color:var(--text)}
    .act-table tr:last-child td{border-bottom:none}
    .act-table tr:hover td{background:#f0fdfa}
    .act-name{font-weight:700;font-size:13px;color:#1e6b5e}
    .act-img{width:38px;height:38px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1px solid #e5e7eb}
    .act-cell{display:flex;align-items:center;gap:9px}

    /* Pills activités */
    .pill-teal-act{background:#e0fdf4;color:#0d9488;border:1px solid rgba(45,212,191,.3);display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700}
    .pill-act-actif{background:#f0fdf4;color:#16a34a;border:1px solid rgba(74,222,128,.2);display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700}
    .pill-act-inactif{background:#fff0f2;color:#e64b5d;border:1px solid rgba(230,75,93,.2);display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700}

    .divider-table{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
    .divider-table h3{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:#1e6b5e}
    .count-badge{background:#e0fdf4;color:#0d9488;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700}

    .btn-edit-act{color:#0d9488;font-size:11px;font-weight:700;padding:5px 10px;border-radius:10px;border:1.5px solid rgba(45,212,191,.3);background:#fff;cursor:pointer;text-decoration:none;transition:.2s}
    .btn-edit-act:hover{background:#e0fdf4}
    .btn-del-act{color:#e64b5d;font-size:11px;font-weight:700;padding:5px 10px;border-radius:10px;border:1.5px solid #ffc5cb;background:#fff;cursor:pointer;transition:.2s}
    .btn-del-act:hover{background:#fff0f2}

    .auth-divider{display:flex;align-items:center;gap:14px;margin:28px 0;color:var(--muted2);font-size:14px}
    .auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:rgba(121,169,223,.22)}

    .btn-auth{margin-top:8px;width:100%;padding:16px;border:none;border-radius:30px;background:linear-gradient(135deg,#2dd4bf,#79a9df);color:#fff;font-size:16px;font-weight:800;cursor:pointer;transition:.3s;box-shadow:0 14px 30px rgba(45,212,191,.3);display:flex;align-items:center;justify-content:center;gap:10px;font-family:'DM Sans',sans-serif;position:relative;overflow:hidden}
    .btn-auth::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 70% 30%,rgba(255,255,255,.2),transparent 60%);pointer-events:none}
    .btn-auth:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 22px 40px rgba(45,212,191,.4)}
    .btn-cancel-auth{display:block;text-align:center;margin-top:10px;color:var(--muted2);font-size:14px;text-decoration:none;font-weight:600}
    .btn-cancel-auth:hover{color:#1e6b5e}

    @keyframes floatCircle{0%,100%{transform:translate(0,0)}50%{transform:translate(15px,-15px)}}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
  </style>
</head>
<body>

<header class="navbar">
  <div class="brand"><img src="../frontend/assets/images/logo-voyagevista.png" alt="VoyageVista"></div>
  <nav>
    <a href="../frontend/index.html">Accueil</a>
    <a href="gestion-hebergements.php">Hébergements</a>
    <a href="gestion-activites.php" class="active">Activités</a>
    <a href="gestion-disponibilites.php">Disponibilités</a>
    <a href="dashboard_prestataire.php">Dashboard</a>
  </nav>
  <div class="nav-icons">
    <span class="heart-icon">♥</span>
    <span>🔔</span>
    <a href="logout.php">👤</a>
  </div>
</header>

<div class="act-layout">

  <!-- ── PANNEAU GAUCHE ── -->
  <div class="act-panel-left">
    <div class="auth-panel-overlay"></div>
    <div class="auth-panel-content" style="max-width:310px">
      <span class="auth-tag">PROVIDER • ACTIVITIES</span>
      <h2 style="font-size:38px"><?php echo $edit_a ? 'Modifier<br>l\'activité ✏️' : 'Ajouter<br>une activité 🏄'; ?></h2>
      <p class="auth-panel-sub" style="font-size:15px"><?php echo $edit_a ? 'Mettez à jour les informations de cette activité.' : 'Proposez des expériences uniques à vos voyageurs.'; ?></p>
      <div class="auth-bubbles">
        <div class="bubble">🏄 Surf</div>
        <div class="bubble">🥗 Food tour</div>
        <div class="bubble">🛥️ Croisière</div>
        <div class="bubble">🧗 Aventure</div>
        <div class="bubble">🎉 Nightlife</div>
      </div>
      <div class="panel-stats">
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $totalActivites;?></span><span class="panel-stat-lbl">ACTIVITÉS</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $actives;?></span><span class="panel-stat-lbl">ACTIVES</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $prixMoyen;?>€</span><span class="panel-stat-lbl">PRIX MOY.</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo $noteMoyenne;?>★</span><span class="panel-stat-lbl">NOTE MOY.</span></div>
      </div>
    </div>
  </div>

  <!-- ── PANNEAU DROIT ── -->
  <div class="act-panel-right">

    <?php if($message):?><div class="alert-success">✅ <?php echo htmlspecialchars($message);?></div><?php endif;?>
    <?php if($error):?><div class="alert-error">⚠️ <?php echo htmlspecialchars($error);?></div><?php endif;?>

    <p style="font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:#1e6b5e;margin-bottom:5px">
      <?php echo $edit_a ? '✏️ Modifier l\'activité' : '🏄 Nouvelle activité'; ?>
    </p>
    <p style="font-size:14px;color:var(--muted);margin-bottom:26px">
      <?php echo $edit_a ? 'Mettez à jour les informations.' : 'Ajoutez une nouvelle expérience à votre catalogue.'; ?>
    </p>

    <!-- FORMULAIRE -->
    <form class="auth-form" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
      <?php if($edit_a):?><input type="hidden" name="id" value="<?php echo (int)$edit_a['id'];?>"><?php endif;?>

      <div class="form-group">
        <label>Nom de l'activité *</label>
        <div class="input-wrap">
          <span class="input-icon">🏄</span>
          <input type="text" name="nom" required placeholder="ex: Surf Experience Bali" value="<?php echo htmlspecialchars($edit_a['nom']??'');?>">
        </div>
      </div>

      <div class="form-group">
        <label>Description</label>
        <div class="input-wrap">
          <span class="input-icon">✍️</span>
          <textarea name="description" placeholder="Décrivez cette activité..."><?php echo htmlspecialchars($edit_a['description']??'');?></textarea>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Catégorie *</label>
          <div class="input-wrap">
            <span class="input-icon">🏷️</span>
            <select name="categorie" required>
              <option value="">— Choisir —</option>
              <?php foreach($categories as $c):?>
              <option value="<?php echo $c;?>" <?php echo ($edit_a['categorie']??'')===$c?'selected':'';?>><?php echo $c;?></option>
              <?php endforeach;?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Destination *</label>
          <div class="input-wrap">
            <span class="input-icon">🗺️</span>
            <select name="destination_id" required>
              <option value="">— Choisir —</option>
              <?php foreach($destinations_list as $d):?>
              <option value="<?php echo (int)$d['id'];?>" <?php echo ($edit_a['destination_id']??0)==$d['id']?'selected':'';?>><?php echo htmlspecialchars($d['nom']);?></option>
              <?php endforeach;?>
            </select>
          </div>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Prix (€) *</label>
          <div class="input-wrap">
            <span class="input-icon">💶</span>
            <input type="number" name="prix" min="1" step="0.01" required placeholder="90" value="<?php echo htmlspecialchars($edit_a['prix']??'');?>">
          </div>
        </div>
        <div class="form-group">
          <label>Durée (heures)</label>
          <div class="input-wrap">
            <span class="input-icon">⏱️</span>
            <input type="number" name="duree_heures" step="0.5" min="0.5" placeholder="2.5" value="<?php echo htmlspecialchars($edit_a['duree_heures']??'');?>">
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Image (nom du fichier)</label>
        <div class="input-wrap">
          <span class="input-icon">🖼️</span>
          <input type="text" name="image_url" placeholder="ex: surf-bali.jpg" value="<?php echo htmlspecialchars($edit_a['image_url']??'');?>">
        </div>
      </div>

      <button type="submit" name="<?php echo $edit_a?'edit_activite':'add_activite';?>" class="btn-auth">
        <?php echo $edit_a ? '✈ Enregistrer les modifications' : '✈ Publier l\'activité'; ?>
      </button>
      <?php if($edit_a):?>
      <a href="gestion-activites.php" class="btn-cancel-auth">↩ Annuler</a>
      <?php endif;?>
    </form>

    <div class="auth-divider"><span>mes activités</span></div>

    <!-- TABLEAU -->
    <div class="divider-table">
      <h3>🏄 Mes activités</h3>
      <span class="count-badge"><?php echo $totalActivites;?> activité<?php echo $totalActivites>1?'s':'';?></span>
    </div>

    <?php if(empty($activites)):?>
      <div class="empty-state">Aucune activité publiée. Utilisez le formulaire ci-dessus.</div>
    <?php else:?>
    <table class="act-table">
      <thead><tr><th>Activité</th><th>Destination</th><th>Prix</th><th>Durée</th><th>Catégorie</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($activites as $a):
          $img = null;
          foreach($actImg as $key=>$path){ if(stripos($a['nom'],$key)!==false||stripos($a['categorie']??'',$key)!==false){$img='../frontend/assets/images/'.$path;break;} }
          if(!$img) $img='../frontend/assets/images/boat.png';
        ?>
        <tr>
          <td>
            <div class="act-cell">
              <img class="act-img" src="<?php echo $img;?>" alt="<?php echo htmlspecialchars($a['nom']);?>">
              <span class="act-name"><?php echo htmlspecialchars($a['nom']);?></span>
            </div>
          </td>
          <td style="font-size:12px;color:var(--muted)"><?php echo htmlspecialchars($a['destination_nom']);?></td>
          <td style="font-weight:700;color:#16a34a"><?php echo $a['prix'];?>€</td>
          <td style="font-size:12px;color:var(--muted)"><?php echo $a['duree_heures']?$a['duree_heures'].'h':'—';?></td>
          <td><span class="pill-teal-act"><?php echo htmlspecialchars($a['categorie']??'—');?></span></td>
          <td>
            <span class="<?php echo $a['est_actif']?'pill-act-actif':'pill-act-inactif';?>">
              <?php echo $a['est_actif']?'Disponible':'Inactif';?>
            </span>
          </td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="?edit=<?php echo $a['id'];?>" class="btn-edit-act">✏️ Modifier</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette activité ?')">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
              <input type="hidden" name="delete_id" value="<?php echo $a['id'];?>">
              <button type="submit" class="btn-del-act">🗑️ Suppr.</button>
            </form>
          </td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <?php endif;?>

  </div>
</div>

<footer>© 2026 VoyageVista — Explore, swipe, travel together.</footer>
</body>
</html>