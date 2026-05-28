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
    <a href="gestion_signalement.php">Signalements</a>
    <a href="gestion_notification.php" class="active">Notifications</a>
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
      <div class="kpi-mini-val" style="color:#1a1a2e"><?php echo (int)$total_notifs;?></div>
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