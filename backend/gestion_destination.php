<?php
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
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
        $nom=$nom=trim($_POST['nom']??''); $desc=trim($_POST['description']??'');
        $prix=(float)($_POST['prix']??0); $region=trim($_POST['region']??'');
        $categorie=trim($_POST['categorie']??''); $budget=trim($_POST['budget']??'');
        if ($nom==='') $error.="Le nom est requis. ";
        if ($prix<=0)  $error.="Le prix doit être positif. ";
        if ($region==='') $error.="La région est requise. ";
        if ($error==='') {
            $pdo->prepare("INSERT INTO destinations (nom,description,prix_base,region,categorie,budget) VALUES (?,?,?,?,?,?)")->execute([$nom,$desc,$prix,$region,$categorie,$budget]);
            $message = "Destination « $nom » ajoutée.";
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_dest'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) { $error = "Action non autorisée."; }
    else {
        $id=(int)$_POST['id']; $nom=trim($_POST['nom']??''); $prix=(float)($_POST['prix']??0);
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
$destImages = ['Bali'=>'../frontend/assets/images/bali.png','Algarve'=>'../frontend/assets/images/algarve.png','Barcelone'=>'../frontend/assets/images/barcelone.png','Chamonix'=>'../frontend/assets/images/chamonix.png','Costa Rica'=>'../frontend/assets/images/costarica.png','Ibiza'=>'../frontend/assets/images/ibiza.png','Santorin'=>'../frontend/assets/images/santorin.png','Tokyo'=>'../frontend/assets/images/food-tour.png','Maroc'=>'../frontend/assets/images/diner-marocain.png','Maldives'=>'../frontend/assets/images/boat.png'];
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
  <style>
    /* ── LAYOUT DEUX COLONNES style login.html ── */
    .dest-layout{display:grid;grid-template-columns:420px 1fr;min-height:calc(100vh - 105px)}
    @media(max-width:900px){.dest-layout{grid-template-columns:1fr}}

    /* ── PANNEAU GAUCHE style auth-panel-left ── */
    .dest-panel-left{
      position:relative;
      background:
        radial-gradient(circle at 20% 80%,rgba(243,178,125,.45),transparent 50%),
        radial-gradient(circle at 80% 20%,rgba(121,169,223,.5),transparent 45%),
        linear-gradient(135deg,#4a68a6 0%,#79a9df 50%,#9bdff4 100%);
      display:flex;align-items:center;justify-content:center;
      overflow:hidden;padding:60px 50px;
    }
    .dest-panel-left::before{content:'';position:absolute;width:320px;height:320px;border-radius:50%;border:2px solid rgba(255,255,255,.12);top:-60px;right:-60px;animation:floatCircle 6s ease-in-out infinite}
    .dest-panel-left::after{content:'';position:absolute;width:200px;height:200px;border-radius:50%;border:2px solid rgba(255,255,255,.10);bottom:-50px;left:-50px;animation:floatCircle 8s ease-in-out infinite reverse}
    .panel-overlay{position:absolute;inset:0;background:radial-gradient(circle at 60% 40%,rgba(255,255,255,.08),transparent 60%);pointer-events:none}
    .panel-content{position:relative;z-index:2;color:#fff;max-width:340px}
    .panel-tag{color:rgba(255,255,255,.75);font-weight:800;letter-spacing:3px;font-size:12px;margin-bottom:20px;display:block}
    .panel-content h2{font-size:46px;line-height:1.1;margin-bottom:16px;font-family:'Syne',sans-serif;font-weight:800;text-shadow:0 4px 20px rgba(0,0,0,.1)}
    .panel-content p{font-size:17px;line-height:1.7;color:rgba(255,255,255,.88);margin-bottom:32px}
    .panel-bubbles{display:flex;flex-wrap:wrap;gap:10px}
    .bubble{background:rgba(255,255,255,.18);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.25);padding:9px 16px;border-radius:30px;font-weight:700;font-size:13px;transition:.3s;animation:fadeUp .9s ease both}
    .bubble:nth-child(1){animation-delay:.1s}.bubble:nth-child(2){animation-delay:.2s}.bubble:nth-child(3){animation-delay:.3s}.bubble:nth-child(4){animation-delay:.4s}.bubble:nth-child(5){animation-delay:.5s}
    .bubble:hover{background:rgba(255,255,255,.3);transform:translateY(-4px)}
    .panel-stats{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:30px}
    .panel-stat{background:rgba(255,255,255,.15);border-radius:16px;padding:16px;text-align:center}
    .panel-stat-val{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;display:block}
    .panel-stat-lbl{font-size:11px;color:rgba(255,255,255,.75);font-weight:600;letter-spacing:.05em}

    /* ── PANNEAU DROIT ── */
    .dest-panel-right{background:#f7fbff;overflow-y:auto;padding:50px 50px 80px}
    @media(max-width:900px){.dest-panel-right{padding:30px 20px 60px}}

    /* ── FORMULAIRE style authentification.css ── */
    .form-section-title{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:var(--blue);margin-bottom:6px}
    .form-section-sub{font-size:15px;color:var(--muted);margin-bottom:30px}

    .auth-form{display:flex;flex-direction:column;gap:18px}
    .form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    @media(max-width:600px){.form-row-2{grid-template-columns:1fr}}

    .form-group{display:flex;flex-direction:column;gap:7px}
    .form-group label{font-size:14px;font-weight:700;color:#31517c}
    .input-wrap{position:relative;display:flex;align-items:center}
    .input-icon{position:absolute;left:18px;font-size:16px;pointer-events:none;z-index:1}
    .input-wrap input,.input-wrap textarea,.input-wrap select{
      width:100%;padding:15px 18px 15px 48px;
      border:2px solid rgba(121,169,223,.25);border-radius:20px;
      font-size:15px;color:var(--text);background:#fff;outline:none;transition:.3s;
      box-shadow:0 6px 18px rgba(69,139,202,.07);font-family:'DM Sans',sans-serif
    }
    .input-wrap textarea{padding-top:14px;resize:vertical;min-height:90px}
    .input-wrap input:focus,.input-wrap textarea:focus,.input-wrap select:focus{
      border-color:var(--blue-light);box-shadow:0 8px 24px rgba(69,139,202,.16);transform:translateY(-2px)
    }
    .input-wrap input::placeholder,.input-wrap textarea::placeholder{color:#a8c0d6}

    .btn-auth{
      margin-top:8px;width:100%;padding:17px;border:none;border-radius:30px;
      background:linear-gradient(135deg,#79a9df,#f3b27d);color:#fff;
      font-size:17px;font-weight:800;cursor:pointer;transition:.3s;
      box-shadow:0 14px 30px rgba(95,144,200,.28);
      display:flex;align-items:center;justify-content:center;gap:10px;
      font-family:'DM Sans',sans-serif;position:relative;overflow:hidden
    }
    .btn-auth::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 70% 30%,rgba(255,255,255,.2),transparent 60%);pointer-events:none}
    .btn-auth:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 22px 40px rgba(95,144,200,.35)}
    .btn-cancel-auth{display:block;text-align:center;margin-top:12px;color:#8aabb8;font-size:14px;text-decoration:none;font-weight:600;transition:.2s}
    .btn-cancel-auth:hover{color:var(--blue)}

    .auth-divider{display:flex;align-items:center;gap:14px;margin:24px 0;color:#a8c0d6;font-size:14px}
    .auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:rgba(121,169,223,.22)}

    /* ── TABLEAU ── */
    .table-title{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--blue);margin-bottom:20px;display:flex;align-items:center;justify-content:space-between}
    .table-count{font-size:14px;color:#8aabb8;font-weight:500}
    .dest-table{width:100%;border-collapse:collapse;background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 10px 28px rgba(69,139,202,.1)}
    .dest-table th{background:#4a68a6;color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:14px 16px;text-align:left}
    .dest-table td{padding:12px 16px;font-size:13px;border-bottom:1px solid #edf4fb;vertical-align:middle;color:var(--text)}
    .dest-table tr:last-child td{border-bottom:none}
    .dest-table tr:hover td{background:#f7fbff}
    .dest-cell{display:flex;align-items:center;gap:10px}
    .dest-thumb{width:42px;height:42px;border-radius:10px;object-fit:cover;flex-shrink:0;border:1px solid #e5e7eb}
    .dest-name{font-weight:700;font-size:13px;color:var(--text)}
    .pill{display:inline-block;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:700}
    .pill-teal{background:#e0f7f4;color:#0d9488}
    .pill-purple{background:#f0edff;color:#5b3ed4}
    .pill-amber{background:#fff8e1;color:#d97706}
    .pill-pink{background:#fce7f3;color:#db2777}
    .pill-green{background:#f0fdf4;color:#16a34a}
    .price-val{font-weight:800;color:var(--blue);font-size:13px}
    .btn-edit{color:var(--blue-light);font-size:11px;font-weight:700;padding:5px 10px;border-radius:10px;border:1.5px solid #c5defa;background:#fff;cursor:pointer;text-decoration:none;transition:.2s}
    .btn-edit:hover{background:#eaf4ff;transform:translateY(-2px)}
    .btn-del{color:#e64b5d;font-size:11px;font-weight:700;padding:5px 10px;border-radius:10px;border:1.5px solid #ffc5cb;background:#fff;cursor:pointer;transition:.2s}
    .btn-del:hover{background:#fff0f2;transform:translateY(-2px)}

    .alert-success{background:#eafff4;color:#1e7e50;border:1.5px solid #b7f0d4;padding:14px 20px;border-radius:18px;font-weight:700;font-size:15px;margin-bottom:22px;animation:fadeUp .4s ease both}
    .alert-error{background:#fff0f2;color:#c0392b;border:1.5px solid #f5c6cb;padding:14px 20px;border-radius:18px;font-weight:700;font-size:15px;margin-bottom:22px;animation:fadeUp .4s ease both}

    @keyframes floatCircle{0%,100%{transform:translate(0,0)}50%{transform:translate(15px,-15px)}}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
  </style>
</head>
<body>

<header class="navbar">
  <div class="brand"><img src="../frontend/assets/images/logo-voyagevista.png" alt="Logo VoyageVista"></div>
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

<div class="dest-layout">

  <!-- ── PANNEAU GAUCHE ── -->
  <div class="dest-panel-left">
    <div class="panel-overlay"></div>
    <div class="panel-content">
      <span class="panel-tag">ADMIN • CATALOGUE • DESTINATIONS</span>
      <h2><?php echo $edit_dest ? 'Modifier<br>la destination ✏️' : 'Ajouter<br>une destination 🗺️'; ?></h2>
      <p><?php echo $edit_dest ? 'Modifiez les informations de cette destination.' : 'Enrichissez le catalogue VoyageVista avec de nouvelles destinations.'; ?></p>
      <div class="panel-bubbles">
        <div class="bubble">🌴 Plage</div>
        <div class="bubble">🏔️ Aventure</div>
        <div class="bubble">🎉 Nightlife</div>
        <div class="bubble">🍜 Gastronomie</div>
        <div class="bubble">🌍 Culture</div>
      </div>
      <div class="panel-stats">
        <div class="panel-stat">
          <span class="panel-stat-val"><?php echo count($destinations); ?></span>
          <span class="panel-stat-lbl">DESTINATIONS</span>
        </div>
        <div class="panel-stat">
          <span class="panel-stat-val"><?php echo count($regions); ?></span>
          <span class="panel-stat-lbl">RÉGIONS</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ── PANNEAU DROIT ── -->
  <div class="dest-panel-right">

    <?php if($message):?><div class="alert-success">✅ <?php echo htmlspecialchars($message);?></div><?php endif;?>
    <?php if($error):?><div class="alert-error">⚠️ <?php echo htmlspecialchars($error);?></div><?php endif;?>

    <!-- FORMULAIRE -->
    <p class="form-section-title"><?php echo $edit_dest ? '✏️ Modifier' : '➕ Nouvelle destination'; ?></p>
    <p class="form-section-sub"><?php echo $edit_dest ? 'Mettez à jour les informations ci-dessous.' : 'Remplissez les informations pour ajouter une destination au catalogue.'; ?></p>

    <form class="auth-form" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
      <?php if($edit_dest):?><input type="hidden" name="id" value="<?php echo (int)$edit_dest['id'];?>"><?php endif;?>

      <div class="form-group">
        <label>Nom de la destination *</label>
        <div class="input-wrap">
          <span class="input-icon">🗺️</span>
          <input type="text" name="nom" required placeholder="ex: Tokyo, Bali, Barcelone..." value="<?php echo htmlspecialchars($edit_dest['nom']??'');?>">
        </div>
      </div>

      <div class="form-group">
        <label>Description</label>
        <div class="input-wrap">
          <span class="input-icon">✍️</span>
          <textarea name="description" placeholder="Décrivez cette destination, ses atouts, son ambiance..."><?php echo htmlspecialchars($edit_dest['description']??'');?></textarea>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Prix de base (€) *</label>
          <div class="input-wrap">
            <span class="input-icon">💶</span>
            <input type="number" name="prix" min="1" step="0.01" required placeholder="850" value="<?php echo htmlspecialchars($edit_dest['prix_base']??'');?>">
          </div>
        </div>
        <div class="form-group">
          <label>Région *</label>
          <div class="input-wrap">
            <span class="input-icon">🌍</span>
            <select name="region" required>
              <option value="">— Choisir —</option>
              <?php foreach($regions as $r):?>
              <option value="<?php echo $r;?>" <?php echo ($edit_dest['region']??'')===$r?'selected':'';?>><?php echo $r;?></option>
              <?php endforeach;?>
            </select>
          </div>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Catégorie</label>
          <div class="input-wrap">
            <span class="input-icon">🏷️</span>
            <select name="categorie">
              <option value="">— Choisir —</option>
              <?php foreach($categories as $c):?>
              <option value="<?php echo $c;?>" <?php echo ($edit_dest['categorie']??'')===$c?'selected':'';?>><?php echo $c;?></option>
              <?php endforeach;?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Budget</label>
          <div class="input-wrap">
            <span class="input-icon">💰</span>
            <select name="budget">
              <option value="">— Choisir —</option>
              <?php foreach($budgets as $b):?>
              <option value="<?php echo $b;?>" <?php echo ($edit_dest['budget']??'')===$b?'selected':'';?>><?php echo $b;?></option>
              <?php endforeach;?>
            </select>
          </div>
        </div>
      </div>

      <button type="submit" name="<?php echo $edit_dest?'edit_dest':'add_dest';?>" class="btn-auth">
        <?php echo $edit_dest ? '✈ Enregistrer les modifications' : '✈ Ajouter la destination'; ?>
      </button>
      <?php if($edit_dest):?>
      <a href="gestion_destination.php" class="btn-cancel-auth">↩ Annuler et revenir</a>
      <?php endif;?>
    </form>

    <div class="auth-divider"><span>catalogue</span></div>

    <!-- TABLEAU -->
    <div class="table-title">
      🗺️ Destinations
      <span class="table-count"><?php echo count($destinations);?> destination<?php echo count($destinations)>1?'s':'';?></span>
    </div>

    <?php if(empty($destinations)):?>
      <p style="text-align:center;color:#8aabb8;padding:40px;font-size:16px">Aucune destination. Utilisez le formulaire ci-dessus pour en ajouter une.</p>
    <?php else:?>
    <table class="dest-table">
      <thead><tr><th>Destination</th><th>Région</th><th>Catégorie</th><th>Budget</th><th>Prix</th><th>Actions</th></tr></thead>
      <tbody>
        <?php
        $rc=['Europe'=>'pill-teal','Asie'=>'pill-purple','Afrique'=>'pill-amber','Amerique'=>'pill-pink','Oceanie'=>'pill-green'];
        foreach($destinations as $d):
          $rc2=$rc[$d['region']]??'pill-teal';
          $img=null;
          foreach($destImages as $key=>$path){ if(stripos($d['nom'],$key)!==false){$img=$path;break;} }
          if(!$img) $img='../frontend/assets/images/hebergement-bg.jpg';
        ?>
        <tr>
          <td>
            <div class="dest-cell">
              <img class="dest-thumb" src="<?php echo $img;?>" alt="<?php echo htmlspecialchars($d['nom']);?>">
              <span class="dest-name"><?php echo htmlspecialchars($d['nom']);?></span>
            </div>
          </td>
          <td><?php if($d['region']):?><span class="pill <?php echo $rc2;?>"><?php echo htmlspecialchars($d['region']);?></span><?php else:?>—<?php endif;?></td>
          <td style="color:var(--muted);font-size:12px"><?php echo htmlspecialchars($d['categorie']??'—');?></td>
          <td style="color:var(--orange);font-weight:700"><?php echo htmlspecialchars($d['budget']??'—');?></td>
          <td class="price-val"><?php echo number_format((float)($d['prix_base']??0),0,',',' ');?>€</td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="?edit=<?php echo (int)$d['id'];?>" class="btn-edit">✏️ Modifier</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer <?php echo htmlspecialchars($d['nom']);?> ?')">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
              <input type="hidden" name="id" value="<?php echo (int)$d['id'];?>">
              <button type="submit" name="delete_dest" class="btn-del">🗑️ Supprimer</button>
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