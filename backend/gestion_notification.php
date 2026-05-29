<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';
session_start();
$message=""; $error="";
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['send'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']??'')) { $error="Action non autorisée."; }
    else {
        $cible=$_POST['cible']??'user'; $msg_txt=trim($_POST['message']??'');
        $type=in_array($_POST['type']??'',['info','alerte','reservation','promotion'])?$_POST['type']:'info';
        if ($msg_txt==='') { $error="Le message ne peut pas être vide."; }
        elseif ($cible==='all') {
            $users=$pdo->query("SELECT id FROM utilisateurs")->fetchAll();
            $stmt=$pdo->prepare("INSERT INTO notifications (user_id,message,type,lu) VALUES (?,?,?,0)");
            foreach($users as $u) $stmt->execute([$u['id'],$msg_txt,$type]);
            $message="Notification envoyée à ".count($users)." utilisateurs.";
        } else {
            $uid=(int)($_POST['user_id']??0);
            if ($uid<=0) { $error="Sélectionnez un utilisateur."; }
            else {
                $chk=$pdo->prepare("SELECT id FROM utilisateurs WHERE id=?"); $chk->execute([$uid]);
                if (!$chk->fetch()) { $error="Utilisateur introuvable."; }
                else { $pdo->prepare("INSERT INTO notifications (user_id,message,type,lu) VALUES (?,?,?,0)")->execute([$uid,$msg_txt,$type]); $message="Notification envoyée."; }
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_notif'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']??'')) { $error="Action non autorisée."; }
    else { $pdo->prepare("DELETE FROM notifications WHERE id=?")->execute([(int)$_POST['notif_id']]); $message="Notification supprimée."; }
}

$notifications=$pdo->query("SELECT n.*, u.username FROM notifications n LEFT JOIN utilisateurs u ON n.user_id=u.id ORDER BY n.date_envoi DESC LIMIT 50")->fetchAll();
$users_list=$pdo->query("SELECT id, username FROM utilisateurs ORDER BY username ASC")->fetchAll();
$total_notifs=$pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
$non_lues=$pdo->query("SELECT COUNT(*) FROM notifications WHERE lu=0")->fetchColumn();
$lues=$pdo->query("SELECT COUNT(*) FROM notifications WHERE lu=1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications - VoyageVista</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin_style.css">
  <style>
    .notif-layout{display:grid;grid-template-columns:380px 1fr;min-height:calc(100vh - 105px)}
    @media(max-width:900px){.notif-layout{grid-template-columns:1fr}}
    .notif-panel-left{position:relative;background:radial-gradient(circle at 20% 80%,rgba(243,178,125,.45),transparent 50%),radial-gradient(circle at 80% 20%,rgba(121,169,223,.5),transparent 45%),linear-gradient(135deg,#4a68a6 0%,#79a9df 60%,#f3b27d 100%);display:flex;align-items:center;justify-content:center;overflow:hidden;padding:60px 44px}
    .notif-panel-left::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;border:2px solid rgba(255,255,255,.12);top:-60px;right:-60px;animation:floatCircle 6s ease-in-out infinite}
    .notif-panel-left::after{content:'';position:absolute;width:180px;height:180px;border-radius:50%;border:2px solid rgba(255,255,255,.10);bottom:-50px;left:-50px;animation:floatCircle 8s ease-in-out infinite reverse}
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

    .notif-panel-right{background:#f7fbff;overflow-y:auto;padding:50px 50px 80px}
    @media(max-width:900px){.notif-panel-right{padding:30px 20px 60px}}

    .form-title{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:var(--blue);margin-bottom:6px}
    .form-sub{font-size:14px;color:var(--muted);margin-bottom:26px}
    .auth-form{display:flex;flex-direction:column;gap:16px}
    .form-group{display:flex;flex-direction:column;gap:7px}
    .form-group label{font-size:14px;font-weight:700;color:#31517c}
    .input-wrap{position:relative;display:flex;align-items:center}
    .input-icon{position:absolute;left:18px;font-size:15px;pointer-events:none;z-index:1}
    .input-wrap input,.input-wrap textarea,.input-wrap select{width:100%;padding:14px 18px 14px 46px;border:2px solid rgba(121,169,223,.25);border-radius:20px;font-size:14px;color:var(--text);background:#fff;outline:none;transition:.3s;box-shadow:0 6px 18px rgba(69,139,202,.07);font-family:'DM Sans',sans-serif}
    .input-wrap textarea{padding-top:12px;resize:vertical;min-height:90px}
    .input-wrap input:focus,.input-wrap textarea:focus,.input-wrap select:focus{border-color:var(--blue-light);box-shadow:0 8px 24px rgba(69,139,202,.16);transform:translateY(-2px)}
    .input-wrap input::placeholder,.input-wrap textarea::placeholder{color:#a8c0d6}

    /* Types de notification en boutons */
    .type-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .type-btn{background:#fff;border:2px solid rgba(121,169,223,.25);color:var(--muted);font-family:'DM Sans',sans-serif;font-size:13px;font-weight:700;padding:12px;border-radius:16px;cursor:pointer;transition:.3s;text-align:center}
    .type-btn:hover{border-color:var(--blue-light);background:#eaf4ff;color:var(--blue)}
    .type-btn.selected{border-color:var(--blue-light);background:linear-gradient(135deg,rgba(121,169,223,.15),rgba(243,178,125,.15));color:var(--blue)}
    input[name=type]{display:none}

    .btn-auth{margin-top:6px;width:100%;padding:16px;border:none;border-radius:30px;background:linear-gradient(135deg,#79a9df,#f3b27d);color:#fff;font-size:16px;font-weight:800;cursor:pointer;transition:.3s;box-shadow:0 14px 30px rgba(95,144,200,.28);display:flex;align-items:center;justify-content:center;gap:10px;font-family:'DM Sans',sans-serif;position:relative;overflow:hidden}
    .btn-auth::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 70% 30%,rgba(255,255,255,.2),transparent 60%);pointer-events:none}
    .btn-auth:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 22px 40px rgba(95,144,200,.35)}

    .auth-divider{display:flex;align-items:center;gap:14px;margin:28px 0;color:#a8c0d6;font-size:14px}
    .auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:rgba(121,169,223,.22)}

    /* Historique */
    .notif-list{display:flex;flex-direction:column;gap:10px}
    .notif-card{background:#fff;border-radius:16px;padding:16px 18px;box-shadow:0 6px 18px rgba(69,139,202,.08);display:flex;align-items:flex-start;gap:12px;transition:.2s}
    .notif-card:hover{box-shadow:0 10px 28px rgba(69,139,202,.14)}
    .ndot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:5px}
    .ndot.unread{background:#f39b5f;box-shadow:0 0 6px rgba(243,155,95,.6)}
    .ndot.read{background:#a8c0d6}
    .notif-msg{font-size:13px;color:var(--text);font-weight:500;margin-bottom:3px}
    .notif-to{font-size:11px;color:#8aabb8;margin-bottom:2px}
    .notif-date{font-size:10px;color:#a8c0d6}
    .ntag{display:inline-block;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;margin-left:auto;flex-shrink:0}
    .ntag.info{background:#eaf4ff;color:var(--blue)}
    .ntag.alerte{background:#fff0f2;color:#e64b5d}
    .ntag.reservation{background:#e0f7f4;color:#0d9488}
    .ntag.promotion{background:#fff8e1;color:#d97706}
    .btn-del-sm{background:#fff0f2;border:1.5px solid #ffc5cb;color:#e64b5d;font-family:'DM Sans',sans-serif;font-size:11px;font-weight:700;padding:5px 9px;border-radius:10px;cursor:pointer;flex-shrink:0}
    .section-title-h{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--blue);margin-bottom:16px;display:flex;align-items:center;justify-content:space-between}
    .count-badge{font-size:12px;color:#8aabb8;font-weight:500}

    .alert-s{background:#eafff4;color:#1e7e50;border:1.5px solid #b7f0d4;padding:14px 20px;border-radius:18px;font-weight:700;font-size:14px;margin-bottom:22px;animation:fadeUp .4s ease both}
    .alert-e{background:#fff0f2;color:#c0392b;border:1.5px solid #f5c6cb;padding:14px 20px;border-radius:18px;font-weight:700;font-size:14px;margin-bottom:22px;animation:fadeUp .4s ease both}
    .empty-state{text-align:center;padding:40px;color:#8aabb8;font-size:15px}

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
    <a href="gestion_signalement.php">Signalements</a>
    <a href="gestion_notification.php" class="active">Notifications</a>
    <a href="statistiques.php">Stats</a>
  </nav>
  <div class="nav-icons"><span class="heart-icon">♥</span><span>🔔</span><a href="logout.php">👤</a></div>
</header>

<div class="notif-layout">

  <!-- PANNEAU GAUCHE -->
  <div class="notif-panel-left">
    <div class="panel-overlay"></div>
    <div class="panel-content">
      <span class="panel-tag">ADMIN • MESSAGES • BROADCAST</span>
      <h2>Envoyer des<br>Notifications 🔔</h2>
      <p>Communiquez avec vos voyageurs — envois ciblés ou broadcast à toute la communauté.</p>
      <div class="panel-bubbles">
        <div class="bubble">ℹ️ Info</div>
        <div class="bubble">⚠️ Alerte</div>
        <div class="bubble">📅 Réservation</div>
        <div class="bubble">🎉 Promotion</div>
      </div>
      <div class="panel-stats">
        <div class="panel-stat"><span class="panel-stat-val"><?php echo (int)$total_notifs;?></span><span class="panel-stat-lbl">ENVOYÉES</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo (int)$non_lues;?></span><span class="panel-stat-lbl">NON LUES</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo (int)$lues;?></span><span class="panel-stat-lbl">LUES</span></div>
        <div class="panel-stat"><span class="panel-stat-val"><?php echo count($users_list);?></span><span class="panel-stat-lbl">MEMBRES</span></div>
      </div>
    </div>
  </div>

  <!-- PANNEAU DROIT -->
  <div class="notif-panel-right">

    <?php if($message):?><div class="alert-s">✅ <?php echo htmlspecialchars($message);?></div><?php endif;?>
    <?php if($error):?><div class="alert-e">⚠️ <?php echo htmlspecialchars($error);?></div><?php endif;?>

    <p class="form-title">✉️ Nouvelle notification</p>
    <p class="form-sub">Envoyez un message ciblé ou à toute la communauté</p>

    <form class="auth-form" method="POST" id="notifForm">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">

      <div class="form-group">
        <label>Destinataire</label>
        <div class="input-wrap">
          <span class="input-icon">👥</span>
          <select name="cible" id="cibleSelect" onchange="toggleUser()">
            <option value="user">Un utilisateur spécifique</option>
            <option value="all">📢 Tous les utilisateurs</option>
          </select>
        </div>
      </div>

      <div id="userSelectDiv" class="form-group">
        <label>Utilisateur</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <select name="user_id">
            <option value="">— Sélectionner —</option>
            <?php foreach($users_list as $u):?>
            <option value="<?php echo (int)$u['id'];?>"><?php echo htmlspecialchars($u['username']);?> #<?php echo (int)$u['id'];?></option>
            <?php endforeach;?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Type de notification</label>
        <div class="type-grid">
          <?php foreach(['info'=>'ℹ️ Info','alerte'=>'⚠️ Alerte','reservation'=>'📅 Réservation','promotion'=>'🎉 Promotion'] as $v=>$l):?>
          <div class="type-btn <?php echo $v==='info'?'selected':'';?>" onclick="selectType('<?php echo $v;?>',this)"><?php echo $l;?></div>
          <?php endforeach;?>
        </div>
        <input type="hidden" name="type" id="typeInput" value="info">
      </div>

      <div class="form-group">
        <label>Message *</label>
        <div class="input-wrap">
          <span class="input-icon">✍️</span>
          <textarea name="message" required maxlength="500" placeholder="Votre message... (500 caractères max)"></textarea>
        </div>
      </div>

      <button type="submit" name="send" class="btn-auth">✈ Envoyer la notification</button>
    </form>

    <div class="auth-divider"><span>historique</span></div>

    <div class="section-title-h">
      📬 Notifications envoyées
      <span class="count-badge"><?php echo count($notifications);?> / 50 dernières</span>
    </div>

    <?php if(empty($notifications)):?>
      <div class="empty-state">Aucune notification envoyée pour l'instant.</div>
    <?php else:?>
    <div class="notif-list">
      <?php foreach($notifications as $n):
        $tc=['info'=>'info','alerte'=>'alerte','reservation'=>'reservation','promotion'=>'promotion'];
        $tc2=$tc[$n['type']??'info']??'info'; ?>
      <div class="notif-card">
        <div class="ndot <?php echo $n['lu']?'read':'unread';?>"></div>
        <div style="flex:1">
          <div class="notif-msg"><?php echo htmlspecialchars($n['message']);?></div>
          <div class="notif-to">→ <?php echo htmlspecialchars($n['username']??'?');?></div>
          <div class="notif-date"><?php echo substr($n['date_envoi'],0,10);?></div>
        </div>
        <span class="ntag <?php echo $tc2;?>"><?php echo htmlspecialchars($n['type']??'info');?></span>
        <form method="POST" style="display:inline;margin-left:8px">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
          <input type="hidden" name="notif_id" value="<?php echo (int)$n['id'];?>">
          <button type="submit" name="delete_notif" class="btn-del-sm">🗑️</button>
        </form>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>

  </div>
</div>

<footer style="text-align:center;padding:24px;background:#fff;color:var(--muted);font-size:13px;border-top:1px solid rgba(121,169,223,.15)">© 2026 VoyageVista — Explore, swipe, travel together.</footer>

<script>
function toggleUser(){
  document.getElementById('userSelectDiv').style.display=document.getElementById('cibleSelect').value==='all'?'none':'flex';
}
function selectType(val,el){
  document.querySelectorAll('.type-btn').forEach(b=>b.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('typeInput').value=val;
}
</script>
</body>
</html>