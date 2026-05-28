<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
require_once 'configuration.php';

$message = ""; $error = "";
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ── ENVOI ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $cible   = $_POST['cible'] ?? 'user';
        $msg_txt = trim($_POST['message'] ?? '');
        $type    = in_array($_POST['type']??'', ['info','alerte','reservation','promotion']) ? $_POST['type'] : 'info';

        if ($msg_txt === '') {
            $error = "Le message ne peut pas être vide.";
        } elseif ($cible === 'all') {
            $users = $pdo->query("SELECT id FROM utilisateurs")->fetchAll();
            $stmt  = $pdo->prepare("INSERT INTO notifications (user_id, message, type, lu) VALUES (?, ?, ?, 0)");
            foreach ($users as $u) $stmt->execute([$u['id'], $msg_txt, $type]);
            $message = "Notification envoyée à tous les utilisateurs (" . count($users) . ").";
        } else {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) { $error = "Sélectionnez un utilisateur."; }
            else {
                $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE id=?");
                $check->execute([$user_id]);
                if (!$check->fetch()) { $error = "Utilisateur introuvable."; }
                else {
                    $pdo->prepare("INSERT INTO notifications (user_id, message, type, lu) VALUES (?, ?, ?, 0)")->execute([$user_id, $msg_txt, $type]);
                    $message = "Notification envoyée.";
                }
            }
        }
    }
}

// ── SUPPRESSION ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notif'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) { $error = "Action non autorisée."; }
    else { $pdo->prepare("DELETE FROM notifications WHERE id=?")->execute([(int)$_POST['notif_id']]); $message = "Notification supprimée."; }
}

$notifications = $pdo->query("
    SELECT n.*, u.username
    FROM notifications n
    LEFT JOIN utilisateurs u ON n.user_id = u.id
    ORDER BY n.date_envoi DESC LIMIT 50
")->fetchAll();

$users_list = $pdo->query("SELECT id, username FROM utilisateurs ORDER BY username ASC")->fetchAll();

$total_notifs  = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
$non_lues      = $pdo->query("SELECT COUNT(*) FROM notifications WHERE lu=0")->fetchColumn();
$lues          = $pdo->query("SELECT COUNT(*) FROM notifications WHERE lu=1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications - VoyageVista</title>
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
    .navbar nav a.active{color:var(--text);background:rgba(251,191,36,.15);border:1px solid rgba(251,191,36,.3)}
    .nav-right{display:flex;align-items:center;gap:1rem}
    .avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--purple),var(--pink));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700}
    .logout{font-size:.8rem;color:var(--muted);text-decoration:none;padding:.35rem .8rem;border:1px solid var(--border);border-radius:20px;transition:all .2s}
    .logout:hover{color:var(--text)}

    .page-hero{padding:2.5rem 2rem 1.5rem;position:relative;overflow:hidden}
    .page-hero::before{content:'';position:absolute;top:-60px;right:-80px;width:400px;height:300px;background:radial-gradient(ellipse,rgba(251,191,36,.1) 0%,transparent 70%);pointer-events:none}
    .tag{font-family:'Syne',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--amber);background:rgba(251,191,36,.12);border:1px solid rgba(251,191,36,.25);padding:.3rem .8rem;border-radius:20px;display:inline-block;margin-bottom:.75rem}
    .page-hero h1{font-family:'Syne',sans-serif;font-size:1.9rem;font-weight:800;margin-bottom:.4rem}
    .page-hero h1 span{background:linear-gradient(90deg,var(--amber),var(--pink));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .page-hero p{color:var(--muted);font-size:.9rem}
    .hero-top{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem}

    .page-body{padding:0 2rem 3rem;max-width:1300px}

    .alert{padding:.9rem 1.2rem;border-radius:10px;font-size:.85rem;margin-bottom:1.5rem;border:1px solid}
    .alert-success{background:rgba(74,222,128,.1);border-color:rgba(74,222,128,.3);color:#86efac}
    .alert-error{background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.3);color:#fca5a5}

    .kpi-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
    .kpi-mini{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.3rem;text-align:center;transition:transform .2s}
    .kpi-mini:hover{transform:translateY(-2px)}
    .kpi-mini-val{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;line-height:1;margin-bottom:.3rem}
    .kpi-mini-label{font-size:.75rem;color:var(--muted)}

    .two-col{display:grid;grid-template-columns:380px 1fr;gap:1.5rem;align-items:start}
    @media(max-width:900px){.two-col{grid-template-columns:1fr}}

    .form-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;position:sticky;top:80px}
    .form-head{padding:1.2rem 1.5rem;border-bottom:1px solid var(--border)}
    .form-head h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700}
    .form-body{padding:1.5rem;display:flex;flex-direction:column;gap:1rem}
    label{display:flex;flex-direction:column;gap:.4rem;font-size:.8rem;color:var(--muted);font-weight:500}
    input[type=text],textarea,select.fs{background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--text);font-family:'DM Sans',sans-serif;font-size:.85rem;padding:.6rem .9rem;border-radius:8px;outline:none;transition:border-color .2s;width:100%}
    input:focus,textarea:focus,select.fs:focus{border-color:rgba(251,191,36,.5)}
    input::placeholder,textarea::placeholder{color:var(--muted)}
    textarea{resize:vertical;min-height:90px}
    #userSelectDiv{transition:opacity .3s}

    .type-grid{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}
    .type-btn{background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--muted);font-family:'DM Sans',sans-serif;font-size:.78rem;padding:.5rem;border-radius:8px;cursor:pointer;transition:all .2s;text-align:center}
    .type-btn.selected{border-color:rgba(251,191,36,.5);background:rgba(251,191,36,.12);color:var(--amber)}
    input[name=type]{display:none}

    .btn-submit{background:linear-gradient(135deg,var(--amber),var(--pink));color:#fff;border:none;padding:.7rem 1.4rem;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;transition:opacity .2s;width:100%}
    .btn-submit:hover{opacity:.88}

    .section-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden}
    .section-head{display:flex;align-items:center;justify-content:space-between;padding:1.2rem 1.5rem;border-bottom:1px solid var(--border)}
    .section-head h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700}

    .notif-row{display:grid;grid-template-columns:10px 1fr auto auto auto;align-items:center;gap:1rem;padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.04);transition:background .15s}
    .notif-row:last-child{border-bottom:none}
    .notif-row:hover{background:rgba(255,255,255,.02)}
    .notif-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .dot-unread{background:var(--amber);box-shadow:0 0 6px rgba(251,191,36,.6)}
    .dot-read{background:var(--muted)}
    .notif-msg{font-size:.83rem;line-height:1.4}
    .notif-to{font-size:.72rem;color:var(--muted);margin-top:.2rem}
    .notif-date{font-size:.72rem;color:var(--muted);white-space:nowrap}

    .pill{display:inline-block;padding:.18rem .6rem;border-radius:20px;font-size:.7rem;font-weight:600}
    .pill-info{background:rgba(124,92,252,.15);color:#a78bfa;border:1px solid rgba(124,92,252,.25)}
    .pill-alerte{background:rgba(248,113,113,.15);color:#fca5a5;border:1px solid rgba(248,113,113,.25)}
    .pill-reservation{background:rgba(45,212,191,.15);color:#5eead4;border:1px solid rgba(45,212,191,.25)}
    .pill-promotion{background:rgba(251,191,36,.15);color:#fcd34d;border:1px solid rgba(251,191,36,.25)}

    .btn-del{background:transparent;border:1px solid rgba(248,113,113,.25);color:var(--red);font-family:'DM Sans',sans-serif;font-size:.72rem;padding:.25rem .6rem;border-radius:6px;cursor:pointer;transition:all .2s;white-space:nowrap}
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
    <a href="gestion_signalement.php">Signalements</a>
    <a href="gestion_notification.php" class="active">Notifications</a>
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
      <p class="tag">ADMIN • MESSAGES • BROADCAST</p>
      <h1>Gestion des <span>Notifications</span></h1>
      <p>Envoyez des messages ciblés ou globaux aux voyageurs</p>
    </div>
  </div>
</section>

<div class="page-body">

  <?php if($message):?><div class="alert alert-success">✅ <?php echo htmlspecialchars($message);?></div><?php endif;?>
  <?php if($error):?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error);?></div><?php endif;?>

  <div class="kpi-row">
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--text)"><?php echo (int)$total_notifs;?></div>
      <div class="kpi-mini-label">Total envoyées</div>
    </div>
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--amber)"><?php echo (int)$non_lues;?></div>
      <div class="kpi-mini-label">Non lues</div>
    </div>
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--green)"><?php echo (int)$lues;?></div>
      <div class="kpi-mini-label">Lues</div>
    </div>
    <div class="kpi-mini">
      <div class="kpi-mini-val" style="color:var(--purple)"><?php echo count($users_list);?></div>
      <div class="kpi-mini-label">Utilisateurs</div>
    </div>
  </div>

  <div class="two-col">

    <!-- Formulaire -->
    <div class="form-card">
      <div class="form-head"><h2>🔔 Envoyer une notification</h2></div>
      <div class="form-body">
        <form method="POST" id="notifForm">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">

          <label>Destinataire
            <select name="cible" class="fs" id="cibleSelect" onchange="toggleUser()">
              <option value="user">Un utilisateur spécifique</option>
              <option value="all">📢 Tous les utilisateurs</option>
            </select>
          </label>

          <div id="userSelectDiv">
            <label>Utilisateur
              <select name="user_id" class="fs">
                <option value="">— Sélectionner —</option>
                <?php foreach($users_list as $u):?>
                <option value="<?php echo (int)$u['id'];?>"><?php echo htmlspecialchars($u['username']);?> #<?php echo (int)$u['id'];?></option>
                <?php endforeach;?>
              </select>
            </label>
          </div>

          <label>Type de notification
            <div class="type-grid">
              <?php foreach(['info'=>'ℹ️ Info','alerte'=>'⚠️ Alerte','reservation'=>'📅 Réservation','promotion'=>'🎉 Promotion'] as $v=>$l):?>
              <div class="type-btn <?php echo $v==='info'?'selected':'';?>" onclick="selectType('<?php echo $v;?>', this)"><?php echo $l;?></div>
              <?php endforeach;?>
            </div>
            <input type="hidden" name="type" id="typeInput" value="info">
          </label>

          <label>Message *
            <textarea name="message" required maxlength="500" placeholder="Votre message... (500 caractères max)"></textarea>
          </label>

          <button type="submit" name="send" class="btn-submit">✈ Envoyer la notification</button>
        </form>
      </div>
    </div>

    <!-- Historique -->
    <div class="section-card">
      <div class="section-head">
        <h2>📬 Historique (<?php echo count($notifications);?>)</h2>
        <?php if($non_lues>0):?><span style="font-size:.78rem;color:var(--amber)"><?php echo $non_lues;?> non lues</span><?php endif;?>
      </div>
      <?php if(empty($notifications)):?>
        <div class="empty-state">Aucune notification envoyée.</div>
      <?php else: foreach($notifications as $n):
        $tc=['info'=>'pill-info','alerte'=>'pill-alerte','reservation'=>'pill-reservation','promotion'=>'pill-promotion'];
        $tc2=$tc[$n['type']??'info']??'pill-info'; ?>
      <div class="notif-row">
        <div class="notif-dot <?php echo $n['lu']?'dot-read':'dot-unread';?>"></div>
        <div>
          <div class="notif-msg"><?php echo htmlspecialchars($n['message']);?></div>
          <div class="notif-to">→ <?php echo htmlspecialchars($n['username']??'?');?></div>
        </div>
        <span class="pill <?php echo $tc2;?>"><?php echo htmlspecialchars($n['type']??'info');?></span>
        <span class="notif-date"><?php echo substr($n['date_envoi'],0,10);?></span>
        <form method="POST" style="display:inline">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'];?>">
          <input type="hidden" name="notif_id" value="<?php echo (int)$n['id'];?>">
          <button type="submit" name="delete_notif" class="btn-del">Supprimer</button>
        </form>
      </div>
      <?php endforeach; endif;?>
    </div>

  </div>
</div>

<footer>© 2026 VoyageVista — Admin Dashboard</footer>
<script>
function toggleUser(){
  const v=document.getElementById('cibleSelect').value;
  document.getElementById('userSelectDiv').style.display=v==='all'?'none':'block';
}
function selectType(val,el){
  document.querySelectorAll('.type-btn').forEach(b=>b.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('typeInput').value=val;
}
</script>
</body>
</html>