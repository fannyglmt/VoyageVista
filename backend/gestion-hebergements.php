<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';
session_start();

$user_id = (int)$_SESSION['user_id'];
$message = ""; $error = "";

// ── SUPPRESSION ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $pdo->prepare("DELETE FROM hebergements WHERE id=? AND prestataire_id=?")->execute([$deleteId, $user_id]);
    // Supprimer aussi de services
    $pdo->prepare("DELETE FROM services WHERE ref_id=? AND type='hebergement' AND prestataire_id=?")->execute([$deleteId, $user_id]);
    $message = "Hébergement supprimé.";
}

// ── AJOUT ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hebergement'])) {
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = (float)($_POST['prix_nuit'] ?? 0);
    $image       = trim($_POST['image_url'] ?? '');
    $destId      = (int)($_POST['destination_id'] ?? 0);
    $type        = $_POST['type'] ?? 'hotel';
    $capacite    = (int)($_POST['capacite'] ?? 1);

    $types_valides = ['hotel','villa','appartement','auberge','camping','autre'];
    if (!in_array($type, $types_valides)) $type = 'hotel';

    if ($nom === '')   $error = "Le nom est requis.";
    elseif ($prix <= 0) $error = "Le prix doit être positif.";
    elseif ($destId <= 0) $error = "Sélectionnez une destination.";
    else {
        $pdo->prepare("INSERT INTO hebergements (nom,description,type,prix_nuit,capacite,image_url,destination_id,prestataire_id,est_actif) VALUES (?,?,?,?,?,?,?,?,1)")
            ->execute([$nom,$description,$type,$prix,$capacite,$image,$destId,$user_id]);
        // Alimenter aussi la table services pour les disponibilités
        $newId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO services (prestataire_id,type,ref_id,nom,prix,statut) VALUES (?,?,?,?,?,'actif')")
            ->execute([$user_id,'hebergement',(int)$newId,$nom,$prix]);
        $message = "Hébergement « $nom » ajouté.";
    }
}

// ── MODIFICATION ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_hebergement'])) {
    $id          = (int)$_POST['id'];
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = (float)($_POST['prix_nuit'] ?? 0);
    $image       = trim($_POST['image_url'] ?? '');
    $destId      = (int)($_POST['destination_id'] ?? 0);
    $type        = $_POST['type'] ?? 'hotel';
    $capacite    = (int)($_POST['capacite'] ?? 1);

    if ($nom === '' || $prix <= 0) $error = "Nom et prix requis.";
    else {
        $pdo->prepare("UPDATE hebergements SET nom=?,description=?,type=?,prix_nuit=?,capacite=?,image_url=?,destination_id=? WHERE id=? AND prestataire_id=?")
            ->execute([$nom,$description,$type,$prix,$capacite,$image,$destId,$id,$user_id]);
        $message = "Hébergement mis à jour.";
    }
}

// ── DONNÉES ───────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT h.*, d.nom AS destination_nom FROM hebergements h JOIN destinations d ON h.destination_id=d.id WHERE h.prestataire_id=? ORDER BY h.date_creation DESC");
$stmt->execute([$user_id]);
$hebergements = $stmt->fetchAll();

$destinations_list = $pdo->query("SELECT id, nom FROM destinations WHERE est_active=1 ORDER BY nom ASC")->fetchAll();

$totalH   = count($hebergements);
$actifs   = array_sum(array_column($hebergements,'est_actif'));
$prixMoy  = $totalH > 0 ? round(array_sum(array_column($hebergements,'prix_nuit'))/$totalH) : 0;
$noteMoy  = $totalH > 0 ? round(array_sum(array_column($hebergements,'note_moyenne'))/$totalH,1) : 0;

$edit_h = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM hebergements WHERE id=? AND prestataire_id=?");
    $s->execute([(int)$_GET['edit'], $user_id]);
    $edit_h = $s->fetch();
}

$destImages = ['Bali'=>'assets/images/bali.png','Algarve'=>'assets/images/algarve.png','Barcelone'=>'assets/images/barcelone.png','Chamonix'=>'assets/images/chamonix.png','Costa Rica'=>'assets/images/costarica.png','Ibiza'=>'assets/images/ibiza.png','Santorin'=>'assets/images/santorin.png','Tokyo'=>'assets/images/food-tour.png','Maroc'=>'assets/images/diner-marocain.png','Maldives'=>'assets/images/boat.png'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion Hébergements - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="admin_style.css">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'DM Sans',system-ui,sans-serif;background:#f7fbff;color:var(--text)}

    /* ── LAYOUT DEUX COLONNES style login.html ── */
    .heb-layout{display:grid;grid-template-columns:400px 1fr;min-height:calc(100vh - 105px)}
    @media(max-width:900px){.heb-layout{grid-template-columns:1fr}}

    /* ── PANNEAU GAUCHE ── */
    .heb-panel-left{
      position:relative;
      background:radial-gradient(circle at 20% 80%,rgba(243,178,125,.45),transparent 50%),radial-gradient(circle at 80% 20%,rgba(121,169,223,.5),transparent 45%),linear-gradient(135deg,#4a68a6 0%,#79a9df 50%,#9bdff4 100%);
      display:flex;align-items:center;justify-content:center;overflow:hidden;padding:60px 50px;
    }
    .heb-panel-left::before{content:'';position:absolute;width:320px;height:320px;border-radius:50%;border:2px solid rgba(255,255,255,.12);top:-60px;right:-60px;animation:floatCircle 6s ease-in-out infinite}
    .heb-panel-left::after{content:'';position:absolute;width:200px;height:200px;border-radius:50%;border:2px solid rgba(255,255,255,.10);bottom:-50px;left:-50px;animation:floatCircle 8s ease-in-out infinite reverse}
    .panel-overlay{position:absolute;inset:0;background:radial-gradient(circle at 60% 40%,rgba(255,255,255,.08),transparent 60%);pointer-events:none}
    .panel-content{position:relative;z-index:2;color:#fff;max-width:320px}
    .panel-tag{color:rgba(255,255,255,.75);font-weight:800;letter-spacing:3px;font-size:12px;margin-bottom:20px;display:block}
    .panel-content h2{font-size:42px;line-height:1.1;margin-bottom:16px;font-family:'Syne',sans-serif;font-weight:800;text-shadow:0 4px 20px rgba(0,0,0,.1)}
    .panel-content p{font-size:16px;line-height:1.7;color:rgba(255,255,255,.88);margin-bottom:28px}
    .panel-bubbles{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:28px}
    .bubble{background:rgba(255,255,255,.18);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.25);padding:9px 16px;border-radius:30px;font-weight:700;font-size:13px;transition:.3s;animation:fadeUp .9s ease both;cursor:pointer}
    .bubble:nth-child(1){animation-delay:.1s}.bubble:nth-child(2){animation-delay:.2s}.bubble:nth-child(3){animation-delay:.3s}.bubble:nth-child(4){animation-delay:.4s}
    .bubble:hover{background:rgba(255,255,255,.3);transform:translateY(-4px)}
    .panel-stats{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .panel-stat{background:rgba(255,255,255,.15);border-radius:16px;padding:14px;text-align:center}
    .panel-stat-val{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;display:block}
    .panel-stat-lbl{font-size:10px;color:rgba(255,255,255,.75);font-weight:600;letter-spacing:.05em}

    /* ── PANNEAU DROIT ── */
    .heb-panel-right{background:#f7fbff;overflow-y:auto;padding:50px 50px 80px}
    @media(max-width:900px){.heb-panel-right{padding:30px 20px 60px}}

    /* ── FORMULAIRE style authentification.css ── */
    .form-title{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:var(--blue);margin-bottom:6px}
    .form-sub{font-size:14px;color:var(--muted);margin-bottom:28px}
    .auth-form{display:flex;flex-direction:column;gap:16px}
    .form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    @media(max-width:600px){.form-row-2{grid-template-columns:1fr}}
    .form-group{display:flex;flex-direction:column;gap:7px}
    .form-group label{font-size:14px;font-weight:700;color:#31517c}
    .input-wrap{position:relative;display:flex;align-items:center}
    .input-icon{position:absolute;left:18px;font-size:15px;pointer-events:none;z-index:1}
    .input-wrap input,.input-wrap textarea,.input-wrap select{
      width:100%;padding:14px 18px 14px 46px;border:2px solid rgba(121,169,223,.25);border-radius:20px;
      font-size:14px;color:var(--text);background:#fff;outline:none;transition:.3s;
      box-shadow:0 6px 18px rgba(69,139,202,.07);font-family:'DM Sans',sans-serif
    }
    .input-wrap textarea{padding-top:12px;resize:vertical;min-height:80px}
    .input-wrap input:focus,.input-wrap textarea:focus,.input-wrap select:focus{border-color:var(--blue-light);box-shadow:0 8px 24px rgba(69,139,202,.16);transform:translateY(-2px)}
    .input-wrap input::placeholder,.input-wrap textarea::placeholder{color:#a8c0d6}
    .btn-auth{margin-top:6px;width:100%;padding:16px;border:none;border-radius:30px;background:linear-gradient(135deg,#79a9df,#f3b27d);color:#fff;font-size:16px;font-weight:800;cursor:pointer;transition:.3s;box-shadow:0 14px 30px rgba(95,144,200,.28);display:flex;align-items:center;justify-content:center;gap:10px;font-family:'DM Sans',sans-serif;position:relative;overflow:hidden}
    .btn-auth::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 70% 30%,rgba(255,255,255,.2),transparent 60%);pointer-events:none}
    .btn-auth:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 22px 40px rgba(95,144,200,.35)}
    .btn-cancel-auth{display:block;text-align:center;margin-top:10px;color:#8aabb8;font-size:14px;text-decoration:none;font-weight:600}
    .btn-cancel-auth:hover{color:var(--blue)}

    .auth-divider{display:flex;align-items:center;gap:14px;margin:28px 0;color:#a8c0d6;font-size:14px}
    .auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:rgba(121,169,223,.22)}

    /* ── ALERTES ── */
    .alert-s{background:#eafff4;color:#1e7e50;border:1.5px solid #b7f0d4;padding:14px 20px;border-radius:18px;font-weight:700;font-size:14px;margin-bottom:22px;animation:fadeUp .4s ease both}
    .alert-e{background:#fff0f2;color:#c0392b;border:1.5px solid #f5c6cb;padding:14px 20px;border-radius:18px;font-weight:700;font-size:14px;margin-bottom:22px;animation:fadeUp .4s ease both}

    /* ── GRILLE HÉBERGEMENTS ── */
    .section-title-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
    .section-title-row h3{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--blue)}
    .count-badge{background:#eaf4ff;color:var(--blue);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700}
    .heb-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px}
    .heb-card{background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 10px 28px rgba(69,139,202,.1);transition:.35s}
    .heb-card:hover{transform:translateY(-8px);box-shadow:0 22px 40px rgba(69,139,202,.18)}
    .heb-card img{width:100%;height:160px;object-fit:cover;display:block}
    .heb-card-body{padding:18px 20px}
    .heb-status{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;margin-bottom:10px}
    .heb-status.actif{background:#dcfce7;color:#15803d}
    .heb-status.inactif{background:#fee2e2;color:#b91c1c}
    .heb-name{font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--blue);margin-bottom:6px}
    .heb-desc{font-size:12px;color:var(--muted);line-height:1.5;margin-bottom:10px}
    .heb-dest{font-size:11px;color:#8aabb8;margin-bottom:10px}
    .heb-info{display:flex;justify-content:space-between;font-weight:700;color:#31517c;font-size:13px;margin-bottom:14px}
    .heb-btns{display:flex;gap:8px}
    .btn-view{flex:1;text-align:center;padding:8px;border-radius:14px;background:#f7fbff;color:var(--blue);font-size:12px;font-weight:700;text-decoration:none;transition:.2s;border:1.5px solid #c5defa}
    .btn-view:hover{background:#eaf4ff}
    .btn-edit-c{flex:1;text-align:center;padding:8px;border-radius:14px;background:#fff8ec;color:var(--orange);font-size:12px;font-weight:700;text-decoration:none;transition:.2s;border:1.5px solid #fde8cc}
    .btn-edit-c:hover{background:#fef3e2}
    .btn-del-c{flex:1;padding:8px;border-radius:14px;background:#fff0f2;color:#e64b5d;font-size:12px;font-weight:700;border:1.5px solid #ffc5cb;cursor:pointer;transition:.2s}
    .btn-del-c:hover{background:#ffe5e9}
    .empty-state{text-align:center;padding:60px;color:#8aabb8;font-size:16px}

    @keyframes floatCircle{0%,100%{transform:translate(0,0)}50%{transform:translate(15px,-15px)}}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
  </style>
</head>
<body>

<!-- NAVBAR -->
<header class="navbar">
  <div class="brand"><img src="../frontend/assets/images/logo-voyagevista.png" alt="Logo VoyageVista"></div>
  <nav>
    <a href="index.html">Accueil</a>
    <a href="destination.html">Destinations</a>
    <a href="hebergements.html">Hébergements</a>
    <a href="../backend/dashboard_prestataire.php" class="active-link">Dashboard</a>
  </nav>
  <div class="nav-icons">
    <span class="heart-icon">♥</span>
    <span>🔔</span>
    <a href="../backend/logout.php">👤</a>
  </div>
</header>

<div class="heb-layout">

  <!-- ── PANNEAU GAUCHE ── -->
  <div class="heb-panel-left">
    <div class="panel-overlay"></div>
    <div class="panel-content">
      <span class="panel-tag">HOST • MANAGE • EARN</span>
      <h2><?php echo $edit_h ? 'Modifier<br>l\'hébergement ✏️' : 'Ajouter<br>un hébergement 🏨'; ?></h2>
      <p><?php echo $edit_h ? 'Mettez à jour les informations de votre hébergement.' : 'Publiez un nouveau logement et commencez à accueillir des voyageurs.'; ?></p>
      <div class="panel-bubbles">
        <div class="bubble">🏨 Hôtel</div>
        <div class="bubble">🏡 Villa</div>
        <div class="bubble">🏠 Appartement</div>
        <div class="bubble">⛺ Camping</div>
      </div>
      <div class="panel-stats">
        <div class="panel-stat">
          <span class="panel-stat-val"><?php echo $totalH;?></span>
          <span class="panel-stat-lbl">HÉBERGEMENTS</span>
        </div>
        <div class="panel-stat">
          <span class="panel-stat-val"><?php echo $actifs;?></span>
          <span class="panel-stat-lbl">ACTIFS</span>
        </div>
        <div class="panel-stat">
          <span class="panel-stat-val"><?php echo $prixMoy;?>€</span>
          <span class="panel-stat-lbl">PRIX MOY.</span>
        </div>
        <div class="panel-stat">
          <span class="panel-stat-val"><?php echo $noteMoy;?>★</span>
          <span class="panel-stat-lbl">NOTE MOY.</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ── PANNEAU DROIT ── -->
  <div class="heb-panel-right">

    <?php if($message):?><div class="alert-s">✅ <?php echo htmlspecialchars($message);?></div><?php endif;?>
    <?php if($error):?><div class="alert-e">⚠️ <?php echo htmlspecialchars($error);?></div><?php endif;?>

    <p class="form-title"><?php echo $edit_h ? '✏️ Modifier l\'hébergement' : '🏨 Nouvel hébergement'; ?></p>
    <p class="form-sub"><?php echo $edit_h ? 'Mettez à jour les informations ci-dessous.' : 'Renseignez les informations de votre logement.'; ?></p>

    <form class="auth-form" method="POST">
      <?php if($edit_h):?><input type="hidden" name="id" value="<?php echo (int)$edit_h['id'];?>"><?php endif;?>

      <div class="form-group">
        <label>Nom de l'hébergement *</label>
        <div class="input-wrap">
          <span class="input-icon">🏨</span>
          <input type="text" name="nom" required placeholder="ex: Bali Paradise Resort" value="<?php echo htmlspecialchars($edit_h['nom']??'');?>">
        </div>
      </div>

      <div class="form-group">
        <label>Description</label>
        <div class="input-wrap">
          <span class="input-icon">✍️</span>
          <textarea name="description" placeholder="Décrivez votre hébergement..."><?php echo htmlspecialchars($edit_h['description']??'');?></textarea>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Type *</label>
          <div class="input-wrap">
            <span class="input-icon">🏷️</span>
            <select name="type" required>
              <?php foreach(['hotel'=>'Hôtel','villa'=>'Villa','appartement'=>'Appartement','auberge'=>'Auberge','camping'=>'Camping','autre'=>'Autre'] as $v=>$l):?>
              <option value="<?php echo $v;?>" <?php echo ($edit_h['type']??'')===$v?'selected':'';?>><?php echo $l;?></option>
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
              <option value="<?php echo (int)$d['id'];?>" <?php echo ($edit_h['destination_id']??0)==$d['id']?'selected':'';?>><?php echo htmlspecialchars($d['nom']);?></option>
              <?php endforeach;?>
            </select>
          </div>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Prix / nuit (€) *</label>
          <div class="input-wrap">
            <span class="input-icon">💶</span>
            <input type="number" name="prix_nuit" min="1" step="0.01" required placeholder="320" value="<?php echo htmlspecialchars($edit_h['prix_nuit']??'');?>">
          </div>
        </div>
        <div class="form-group">
          <label>Capacité (personnes)</label>
          <div class="input-wrap">
            <span class="input-icon">👥</span>
            <input type="number" name="capacite" min="1" placeholder="4" value="<?php echo htmlspecialchars($edit_h['capacite']??'');?>">
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Image (nom du fichier)</label>
        <div class="input-wrap">
          <span class="input-icon">🖼️</span>
          <input type="text" name="image_url" placeholder="ex: hotel1.jpg" value="<?php echo htmlspecialchars($edit_h['image_url']??'');?>">
        </div>
      </div>

      <button type="submit" name="<?php echo $edit_h?'edit_hebergement':'add_hebergement';?>" class="btn-auth">
        <?php echo $edit_h ? '✈ Enregistrer les modifications' : '✈ Publier l\'hébergement'; ?>
      </button>
      <?php if($edit_h):?>
      <a href="gestion-hebergements.php" class="btn-cancel-auth">↩ Annuler et revenir</a>
      <?php endif;?>
    </form>

    <div class="auth-divider"><span>mes hébergements</span></div>

    <!-- GRILLE DES HÉBERGEMENTS -->
    <div class="section-title-row">
      <h3>🏨 Mes hébergements</h3>
      <span class="count-badge"><?php echo $totalH;?> hébergement<?php echo $totalH>1?'s':'';?></span>
    </div>

    <?php if(empty($hebergements)):?>
      <div class="empty-state">Aucun hébergement publié.<br>Utilisez le formulaire ci-dessus pour commencer.</div>
    <?php else:?>
    <div class="heb-grid">
      <?php foreach($hebergements as $h):
        $img = 'assets/images/hebergement-bg.jpg';
        if (!empty($h['image_url'])) $img = 'assets/images/'.$h['image_url'];
        foreach($destImages as $key=>$path){ if(stripos($h['destination_nom'],$key)!==false){$img=$path;break;} }
      ?>
      <div class="heb-card">
        <img src="<?php echo htmlspecialchars($img);?>" alt="<?php echo htmlspecialchars($h['nom']);?>">
        <div class="heb-card-body">
          <span class="heb-status <?php echo $h['est_actif']?'actif':'inactif';?>"><?php echo $h['est_actif']?'Disponible':'Inactif';?></span>
          <div class="heb-name"><?php echo htmlspecialchars($h['nom']);?></div>
          <div class="heb-desc"><?php echo htmlspecialchars(substr($h['description']??'',0,70)).(strlen($h['description']??'')>70?'…':'');?></div>
          <div class="heb-dest">📍 <?php echo htmlspecialchars($h['destination_nom']);?></div>
          <div class="heb-info">
            <span><?php echo $h['prix_nuit'];?>€/nuit</span>
            <span>⭐ <?php echo $h['note_moyenne'];?></span>
            <span>👥 <?php echo $h['capacite'];?> pers.</span>
          </div>
          <div class="heb-btns">
            <a href="detail-hebergement.html?id=<?php echo $h['id'];?>" class="btn-view">👁 Voir</a>
            <a href="?edit=<?php echo $h['id'];?>" class="btn-edit-c">✏️ Modifier</a>
            <form method="POST" style="flex:1" onsubmit="return confirm('Supprimer cet hébergement ?')">
              <input type="hidden" name="delete_id" value="<?php echo $h['id'];?>">
              <button type="submit" class="btn-del-c" style="width:100%">🗑️ Suppr.</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>

  </div>
</div>

<footer style="text-align:center;padding:24px;background:#fff;color:var(--muted);font-size:13px;border-top:1px solid rgba(121,169,223,.15)">
  © 2026 VoyageVista — Host smarter 🌴
</footer>
</body>
</html>