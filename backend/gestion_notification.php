<?php
// =============================================
// Gestion des Notifications - VoyageVista
// =============================================
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'configuration.php';

$message = "";
$error   = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── ENVOI d'une notification ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $cible   = $_POST['cible'] ?? 'user'; // 'user' ou 'all'
        $msg_txt = trim($_POST['message'] ?? '');

        if ($msg_txt === '') {
            $error = "Le message ne peut pas être vide.";
        } elseif ($cible === 'all') {
            // Notification broadcast : tous les utilisateurs
            $users = $pdo->query("SELECT id FROM utilisateurs")->fetchAll();
            $stmt  = $pdo->prepare("INSERT INTO notifications (user_id, message, lu) VALUES (?, ?, 0)");
            foreach ($users as $u) {
                $stmt->execute([$u['id'], $msg_txt]);
            }
            $message = "Notification envoyée à tous les utilisateurs.";
        } else {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) {
                $error = "ID utilisateur invalide.";
            } else {
                // Vérifier que l'utilisateur existe
                $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE id = ?");
                $check->execute([$user_id]);
                if (!$check->fetch()) {
                    $error = "Utilisateur introuvable.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, lu) VALUES (?, ?, 0)");
                    $stmt->execute([$user_id, $msg_txt]);
                    $message = "Notification envoyée.";
                }
            }
        }
    }
}

// ── SUPPRESSION ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notif'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([(int)$_POST['notif_id']]);
        $message = "Notification supprimée.";
    }
}

// Récupération des dernières notifications (50 max)
$notifications = $pdo->query("
    SELECT n.*, u.username 
    FROM notifications n 
    LEFT JOIN utilisateurs u ON n.user_id = u.id 
    ORDER BY n.date_envoi DESC 
    LIMIT 50
")->fetchAll();

// Liste des utilisateurs pour le select
$users_list = $pdo->query("SELECT id, username FROM utilisateurs ORDER BY username ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion Notifications - VoyageVista</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
  <div class="brand">
    <img src="assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
  </div>
  <nav>
    <a href="dashboard_admin.php">Dashboard</a>
    <a href="gestion_utilisateur.php">Utilisateurs</a>
    <a href="gestion_notification.php" class="active">Notifications</a>
    <a href="gestion_signalement.php">Signalements</a>
  </nav>
</header>

<main class="admin-page">
  <h1>Gestion des Notifications</h1>

  <?php if ($message): ?>
    <p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <!-- Formulaire d'envoi -->
  <section class="form-section">
    <h2>Envoyer une notification</h2>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

      <label>Destinataire
        <select name="cible" id="cibleSelect" onchange="toggleUserSelect()">
          <option value="user">Un utilisateur spécifique</option>
          <option value="all">Tous les utilisateurs</option>
        </select>
      </label>

      <div id="userSelectDiv">
        <label>Utilisateur
          <select name="user_id">
            <option value="">-- Choisir un utilisateur --</option>
            <?php foreach ($users_list as $u): ?>
              <option value="<?php echo (int)$u['id']; ?>">
                <?php echo htmlspecialchars($u['username']); ?> (#<?php echo (int)$u['id']; ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>

      <label>Message *
        <textarea name="message" required maxlength="500" 
                  placeholder="Votre message (500 caractères max)..."></textarea>
      </label>

      <button type="submit" name="send" class="btn">Envoyer</button>
    </form>
  </section>

  <!-- Historique des notifications -->
  <section>
    <h2>Dernières notifications envoyées</h2>
    <?php if (empty($notifications)): ?>
      <p class="empty-state">Aucune notification envoyée.</p>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Destinataire</th>
            <th>Message</th>
            <th>Date</th>
            <th>Lu</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($notifications as $n): ?>
          <tr>
            <td><?php echo htmlspecialchars($n['username'] ?? 'Inconnu'); ?></td>
            <td><?php echo htmlspecialchars($n['message']); ?></td>
            <td><?php echo htmlspecialchars($n['date_envoi'] ?? '-'); ?></td>
            <td><?php echo $n['lu'] ? '✅' : '🔵'; ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="notif_id" value="<?php echo (int)$n['id']; ?>">
                <button type="submit" name="delete_notif" class="btn btn--danger btn--sm">Supprimer</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</main>

<script>
function toggleUserSelect() {
  const cible = document.getElementById('cibleSelect').value;
  document.getElementById('userSelectDiv').style.display = cible === 'all' ? 'none' : 'block';
}
</script>

</body>
</html>